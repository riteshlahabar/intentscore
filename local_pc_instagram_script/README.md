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
3. Fill in all four values:
   - `portal_url` — the portal's address
   - `worker_token` — **exactly** the same string as `INSTAGRAM_WORKER_TOKEN` on the server
   - `instagram_session_id` — see below
   - `poll_seconds` — leave at 300
4. Test it: `php worker.php`

### Getting the Instagram session id

1. Log in to Instagram in Chrome. **Use a throwaway account, not the company account** —
   Instagram may restrict an account that is queried this way.
2. Press `F12` → **Application** tab → **Cookies** → `https://www.instagram.com`
3. Copy the **Value** of the `sessionid` row into `instagram_session_id`.

Pasting the whole cookie header works too and is slightly more reliable, since it carries
`csrftoken` and `mid` with it.

The value expires when that account logs out or changes its password — typically after
weeks or months. When it does, the portal shows "The Instagram session has expired" and
you paste a fresh one here.

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
- `config.php` holds a live Instagram login. It is gitignored; never commit it, and never
  copy it onto the server.
