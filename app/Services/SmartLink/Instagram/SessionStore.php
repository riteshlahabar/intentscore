<?php

namespace App\Services\SmartLink\Instagram;

use App\Models\Setting\Setting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Throwable;

/**
 * The one place the Instagram session cookie lives.
 *
 * It used to be pasted twice - into the portal's .env and again into the worker PC's
 * config.php - which meant a cookie refresh was two edits on two machines and the two
 * copies drifted apart. It is stored here instead, so a salesperson pastes it once under
 * Settings and both the portal and the worker read the same value.
 *
 * The cookie is a live Instagram login, so it is encrypted at rest with the app key and
 * only ever shown back masked.
 */
class SessionStore
{
    public const KEY = 'instagram_session_id';

    /** The cookie as Instagram expects it, or '' when nothing is configured. */
    public function get(): string
    {
        $stored = Setting::query()->where('key', self::KEY)->value('value');

        if (filled($stored)) {
            try {
                return trim(Crypt::decryptString($stored));
            } catch (Throwable) {
                /* Written under a different APP_KEY - unreadable, so treat it as unset. */
            }
        }

        /* Kept so an existing .env install keeps working until it is moved to the page. */
        return trim((string) config('services.instagram.session_id'));
    }

    public function put(string $cookie): void
    {
        Setting::updateOrCreate(
            ['key' => self::KEY],
            ['group' => 'instagram', 'type' => 'secret', 'value' => Crypt::encryptString(trim($cookie))],
        );
    }

    public function forget(): void
    {
        Setting::query()->where('key', self::KEY)->delete();
    }

    public function updatedAt(): ?Carbon
    {
        return Setting::query()->where('key', self::KEY)->value('updated_at');
    }

    /**
     * What the settings page shows: enough to recognise the cookie and to see whether it
     * carries the parts Instagram's API wants, without printing the login itself.
     *
     * A bare sessionid is accepted by Instagram but answered with HTTP 429 far sooner
     * than a full cookie header, which is why csrftoken and mid are reported separately
     * rather than folded into a single "configured" flag.
     *
     * @return array<string,mixed>
     */
    public function summary(): array
    {
        $cookie = $this->get();

        if ($cookie === '') {
            return ['configured' => false];
        }

        $names = [];

        foreach (explode(';', $cookie) as $pair) {
            $name = trim(explode('=', trim($pair), 2)[0]);

            if ($name !== '') {
                $names[] = strtolower($name);
            }
        }

        $whole = str_contains($cookie, 'sessionid=');

        return [
            'configured' => true,
            'stored_in_db' => filled(Setting::query()->where('key', self::KEY)->value('value')),
            'whole_header' => $whole,
            'has_csrftoken' => in_array('csrftoken', $names, true),
            'has_mid' => in_array('mid', $names, true),
            'length' => strlen($cookie),
            'preview' => $this->mask($cookie),
            'updated_at' => $this->updatedAt(),
        ];
    }

    /** Shows only enough of the cookie to tell one paste from the next. */
    private function mask(string $cookie): string
    {
        return strlen($cookie) <= 14
            ? str_repeat('•', strlen($cookie))
            : substr($cookie, 0, 8).str_repeat('•', 12).substr($cookie, -6);
    }
}
