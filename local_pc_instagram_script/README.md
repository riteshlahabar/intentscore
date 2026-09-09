# Instagram worker (runs on a local PC)

Instagram refuses the portal's shared-hosting IP address whatever cookie it sends, so the
portal cannot fetch profiles itself. It queues them instead, and this script — running on
an ordinary PC on a home or office connection, which Instagram still answers — does the
fetching and posts the results back.

Every request goes **out** from this PC. Nothing needs to reach it, so there is no port
forwarding, no fixed IP and no firewall change.

```
Admin clicks "Fetch Profile"  ->  portal saves a pending row
This PC asks the portal        ->  "anything queued?"
This PC asks Instagram         ->  profile data
This PC posts back             ->  portal scores it and shows the card
```

## Setup on the server (once)

Add to the portal's `.env`:

```
INSTAGRAM_SOURCE=worker
INSTAGRAM_WORKER_TOKEN=<a long random string>
```

The Instagram cookie does **not** go in `.env`. It is pasted in the portal under
**Settings > Instagram Session**, and this script reads it from there, so refreshing it
later is one edit in one place.

Generate the token with:

```
php -r "echo bin2hex(random_bytes(32));"
```

Then:

```
php artisan config:clear
php artisan migrate
```

## Setup on this PC (once)

1. PHP must be installed. Check with `php -v`. If it is missing, XAMPP is the easiest way
   to get it on Windows.
2. Copy `config.example.php` to `config.php`.
3. Fill in the two values:
   - `portal_url` — the portal's address
   - `worker_token` — **exactly** the same string as `INSTAGRAM_WORKER_TOKEN` on the server

   Leave `instagram_session_id` empty — the cookie comes from the portal — and leave
   `poll_seconds` at 10. That interval only asks the portal whether anything is queued;
   Instagram is called only when an audit is actually waiting.
4. Test it: `php worker.php`

### Getting the Instagram session id

This is done in the portal, under **Settings > Instagram Session**, not in this folder.

1. Log in to Instagram in Chrome. **Use a throwaway account, not the company account** —
   Instagram may restrict an account that is queried this way.
2. Press `F12` → **Network** tab → click any `instagram.com` request → **Request Headers**
3. Copy the whole `Cookie:` value and paste it into the portal page.

Copy the **whole** cookie header, not only the `sessionid` row. A bare `sessionid` is
answered with HTTP 429 far sooner, and the audit then falls back to the profile page —
which carries follower counts but no likes, comments or posting frequency, so those
columns stay empty.

The cookie expires when that account logs out or changes its password — typically after
weeks or months. When it does, the portal shows "The Instagram session has expired" and
you paste a fresh one on that page.

## Running it automatically

Open **Task Scheduler** → *Create Task*:

- **General** → *Run whether user is logged on or not*
- **Triggers** → *New* → *Daily*, then tick *Repeat task every 5 minutes* for *Indefinitely*
- **Actions** → *New* → *Start a program* → browse to `run.bat` in this folder
- **Conditions** → untick *Start the task only if the computer is on AC power*

Output is appended to `worker.log` next to the script.

Alternatively, leave a console window open with `php worker.php --loop`.

## Notes

- **The PC has to be switched on.** While it is off, audits stay queued and are picked up
  when it comes back — nothing is lost.
- Profiles are fetched 20 seconds apart on purpose. Instagram throttles bursts.
- `config.php` holds the worker token. It is gitignored; never commit it.
