<?php

namespace App\Http\Controllers\Admin\Setting;

use App\Http\Controllers\Controller;
use App\Services\SmartLink\Instagram\SessionStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The Instagram session cookie on its own page, deliberately apart from Company Profile:
 * it is a credential rather than branding, it is admin-only, and it is re-pasted on its
 * own schedule - whenever the throwaway Instagram account logs out.
 */
class InstagramSettingController extends Controller
{
    public function __construct(private readonly SessionStore $sessions)
    {
    }

    public function index(): View
    {
        return view('admin.settings.instagram', [
            'session' => $this->sessions->summary(),
            'source' => config('services.instagram.source'),
            'workerReady' => filled(config('services.instagram.worker_token')),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'session_cookie' => ['nullable', 'string', 'max:8000'],
        ]);

        $cookie = trim((string) ($data['session_cookie'] ?? ''));

        if ($cookie === '') {
            $this->sessions->forget();

            return back()->with('success', 'Instagram session cleared.');
        }

        /* A pasted profile URL or username here is a mis-paste, not a cookie. */
        if (! str_contains($cookie, 'sessionid=') && ! str_contains(urldecode($cookie), ':')) {
            return back()->withErrors(['session_cookie' => 'That does not look like an Instagram cookie. Paste the sessionid value, or the whole Cookie header from devtools.']);
        }

        $this->sessions->put($cookie);

        return back()->with('success', 'Instagram session saved. The worker PC picks it up on its next check.');
    }
}
