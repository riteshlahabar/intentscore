# CLAUDE.md

Laravel 13 + MySQL "IntentScore" portal (smart links, prospects, website + Instagram
audits, engagement scoring). Deployed on shared cPanel hosting: no queue daemon, no
Redis, no cron dependency for core features. See `README.md` and `ARCHITECTURE.md`.

## Working conventions

- Keep replies short; the user often writes Marathi in Latin script — reply in the language they used.
- Explain the approach and wait for explicit go-ahead before writing or changing code.
- Keep the existing admin/public UI layout; retheme colors only, do not redesign.
- "Please correct: <error text>" means fix the root cause, not the wording.

## Session notes — 2026-09-10

### Branding: Groomer Loop

- Palette sampled from `public/images/Groomer-Logo.png` (the earlier `logo.jpeg` was
  replaced mid-session and no longer exists): navy `#001040` for the "Groomer" wordmark
  and the dog/cat mark, `#693DD2` for the "L" of Loop, `#A040B0` mid-sweep, `#E14A80`
  for the "p" and the paw.
- **Smart Pages are purple, the admin panel is pink** — deliberate, not drift. The Smart
  Page override file `public/smart-templates/assets/css/groomer-loop-theme.css` keys off
  `--gl-primary: #6a3ad6`; the admin panel keys off `--brand: #d22f68`.
- `#d22f68` is a deliberately darkened `#E14A80`: white text on the exact logo pink is
  3.82:1 and fails WCAG AA, `#d22f68` is 4.83:1. Same rule produced `#c9265f` for pink
  text on white (5.35:1). Use the exact logo colours for tints, icons and borders; use
  the darkened ones anywhere text sits on them.
- Status colours (red danger, amber medium-intent, green, info blue) were left alone —
  they signal meaning, not brand.
- Favicon is `public/images/Groomer-Favicon.png`. **The capitals matter** — the server is
  Linux. The old `images/favicon.svg` was deleted, which had silently broken the favicon
  on every page; the login page never had one and now does.

### Why colour changes "did not reflect" (twice)

- First cause: `groomer-loop-theme.css` existed but was never `<link>`ed by any design
  blade. A file being written is not a file being loaded — grep for the reference.
- Second cause: browser caching of `/public` assets, which have no build step and so no
  content hash.
- Fix, now permanent: the `@assetv('path')` Blade directive registered in
  `AppServiceProvider`, which appends `?v=<filemtime>`. Applied to the files that
  actually change — `groomer-loop-theme.css`, `nav-overrides.css`, `portal-public.css`,
  `smart-page-tracker.js`, the favicon — and not to vendor builds.

### Smart Page URLs: /s/{slug} → /{id}/{name}

- New public form is `https://offer.groomerloop.com/5/ritesh-technology` —
  `smart_links.id` plus the prospect's business name slugified.
- **The id was the user's explicit choice.** The trade-off was raised: ids are sequential,
  so `/1/x`, `/2/x`, … can be walked to reach every prospect page, and they leak how many
  prospects exist. The user reaffirmed the id; do not re-litigate it. The random `slug`
  column still exists and still feeds the legacy route.
- Only the id is matched; `{name}` is cosmetic and a stale one 301-redirects to the
  canonical URL, so a renamed prospect never breaks a link already sent to a client.
- Duplicate business names were the reason for the change — two prospects called
  "Happy Paws Salon" get `/1/happy-paws-salon` and `/103/happy-paws-salon`.
- `/s/{slug}` is kept as a permanent redirect (`PublicSmartPageController::legacy`).
- The route is registered **last** in `routes/web.php` and constrained with
  `->whereNumber('link')`. A two-segment route at the root would otherwise swallow
  `/admin/...`; both guards are load-bearing.
- `ProspectController` eager-loads `smartLink.prospect` so building the URL for a list of
  prospects does not fire a query per row.

### Deploy gotcha, cost a debugging round trip

- After deploying the new routes the page 404'd while `publicUrl()` already produced the
  new address. Cause: a stale `bootstrap/cache/routes-v7.php` on the server — the model
  change shipped, the route table did not.
- **Always run `php artisan route:clear` and `php artisan view:clear` on the server after
  a deploy that touches routes or Blade.** Laravel does not log 404s, so an empty
  `laravel.log` proves nothing; `php artisan route:list | grep smart` is the diagnostic.

### Smart Page header is now configurable

- Settings → Company Settings gained two fields, both Smart-Page-header-only (the admin
  sidebar, footer and login page are unaffected):
  - `header_display` — `logo` / `name` / `both`, radio, default `both`.
  - `header_logo_height` — number input, 24–160px, default 72.
- No migration: both are `settings` rows in the `company` group, and a missing row reads
  as the default at render time.
- `partials/brand.blade.php` holds the what-to-show rule; `partials/brand-vars.blade.php`
  publishes the height as `--brand-logo-h` on `:root` in `<head>`. The variable goes on
  `:root` rather than the `<img>` because the fallback page's nav bar has a fixed height
  that must grow with the logo, and a property set on a child cannot be read by a parent.
- One number drives three breakpoints: the stylesheets scale it to 85% at ≤991px and 65%
  at ≤576px. Height is clamped again at render, so a hand-edited row cannot break layout.
- "Logo only" with no logo uploaded falls back to the name — the header is never empty.
  This also covers `SmartTemplateController::preview`, which passes a settings array with
  no logo at all.

### Spacing and the admin sidebar

- Landrick ships `.section{padding:100px 0}`, sized for a marketing site with few long
  sections. A Smart Page stacks six to eight short ones, so it was cut to 38px (28px on
  mobile) in the override file; `.client-section` on the fallback page matches.
- The gap below a section heading was doubled up — `.section-title.mb-4.pb-2` plus the
  `<h4 class="title mb-4">` inside it. The override zeroes the h4's margin **only via
  `:last-child`**: in the intro and free-tools sections a `<p>` follows the heading inside
  the same wrapper, and there that margin is the only separation.
- Admin sidebar shows the logo alone; the company name is kept only as a text fallback
  for when no logo is uploaded. Its old `images/logo.svg` fallback no longer existed.

### Instagram: diagnosed live on the server, not guessed

Access this time was through cPanel (MilesWeb, `turnkeyinfotech.in:2083`) → Terminal,
driven from the browser. **The Laravel root is `/home/hrnkutuc/public_html/offer.groomerloop.com`**
— not a folder named after the domain in `~`, which cost a couple of wrong `cd`s.

What the evidence ruled out, in order:

- All three worker routes registered (`pending`, `result`, `session`).
- **0 pending audits** (11 completed, 9 failed) — so the queue was not stuck and the
  worker was reaching the portal. The "worker PC is off" theory was wrong.
- The stored cookie is complete: 411 chars with `sessionid`, `csrftoken`, `mid`,
  `ds_user_id`, `ig_did` all present. So the 2026-09-09 bare-cookie cause was not it.
- `portal_url` in the PC's `config.php` still pointed at the old
  `intentscore.turnkeyinfotech.live`. Tempting, but **not** the cause: both domains serve
  the same app and database, and each returned the identical 411-char cookie. Worth
  correcting anyway; the user did that themselves.

What it actually is: a probe with the exact worker headers returned

    HTTP/1.1 429 Too Many Requests
    Content-Type: text/html            <- an HTML error page, not JSON
    <html class="no-js logged-in ">    <- the session is alive
    <title>Page Not Found</title>

`logged-in` is the key: the cookie is valid, the request is well-formed
(`x-ig-app-id`, `x-asbd-id`, `x-csrftoken`, referer, origin all present), and Instagram
still refuses. **The account is soft-blocked at the API level.** It may still browse,
which is why the profile-page fallback keeps working. No code change can fix this.

**The permanent split to remember: profile information is never blocked; per-post likes
and comments come only from the blocked JSON endpoint.** So follower/following/post
counts, bio, category and picture always arrive, while engagement rate, avg likes,
avg comments, posts per month, last-post date, and the engagement and consistency
scores cannot be obtained at all until the account is swapped or the Graph API is used.

### Decisions taken as a result

- **The engagement request is switched off.** `instagramProfile()` is commented out — not
  deleted — at both call sites in `worker.php`, with a note on how to restore it. Every
  audit went straight to a blocked request before falling back anyway. Both copies were
  edited, `C:\local_pc_instagram_script\worker.php` and the one in the repo, so they do
  not drift.
- **A throttled read is no longer recorded as `completed`.** `InstagramProfileService`
  saves `partial` with the reason in `error_message` when a public account returns no
  posts. A private account stays `completed` — it has no posts to read, so the audit is
  as complete as it will ever be. `Prospect::latestInstagramAudit()` includes `partial`,
  and the admin badge keys off the status rather than inferring it from a null rate.
- Instagram Graph API was raised as the permanent fix and **the user declined it** for now.
- Three `$user['key'] ?: null` reads became `($user['key'] ?? null) ?: null`. Latent —
  real payloads always carry those keys — but it warned on a sparse fixture.

### The worker was never running automatically

`schtasks` showed **no scheduled task for the worker at all**. Every run in `worker.log`
was someone double-clicking `run.bat` by hand. `run.bat` does ONE pass and exits — it was
written for Task Scheduler to repeat — so `poll_seconds: 10` had never had any effect,
because that setting only applies to `--loop`, which nothing was running. Clicking
"Fetch Profile" queued a row that nobody watched.

Added `run-loop.bat`: runs `worker.php --loop`, restarts itself 15 seconds after any
exit, and a shortcut to it now sits in the user's Startup folder. It must be started by
hand once after being created — a Startup shortcut does nothing retroactively, which
briefly looked like a second bug.

### Admin: the audit card polls instead of being refreshed by hand

The click queues a job the worker fulfils seconds later, but nothing watched for the
result, so the card kept showing the PREVIOUS audit — which reads as "my edit was
ignored" when the username has just been changed, and had the user refreshing four or
five times. `admin/prospects/show.blade.php` now reloads every 5s while an audit is
pending, capped at 24 tries (2 minutes) with an on-screen reason when it gives up, keyed
per pending audit id so a new fetch gets a fresh budget, and paused while the tab is
hidden. Whole-page reload is deliberate: the card is server-rendered, so a status
endpoint would only duplicate that rendering.

### Open

- **The `partial` message is now inaccurate.** It says "Instagram limited the detailed
  request", but the request is no longer sent at all. Reword to something like
  "Engagement scoring is switched off — profile information only".
- **11 rows still need backfilling.** They are `completed` with a null engagement rate on
  public accounts, i.e. really `partial`. Because the badge now keys off the status, those
  rows currently show *no* "Limited data" note where they used to — the change made old
  data less informative until this runs. Claude is blocked from writing to the live
  database, so the user must run it:

      php artisan tinker --execute='App\Models\SmartLink\InstagramAudit::where("status","completed")->whereNull("engagement_rate")->where("is_private",0)->update(["status"=>"partial","error_message"=>"..."]);'

- **`overallScore()` overstates a partial audit.** It averages only the non-null scores,
  so a throttled row shows "45/100" computed from 2 of 4 checks and looks like a full
  score. Flagged, deliberately not changed — labelling it "based on 2 of 4 checks" was
  offered and not yet taken up.
- **`ProspectController::regenerateLink` is misleading.** It rotates `slug`, which no
  longer affects the public URL, yet still tells the user "The old link no longer works".
  It only invalidates legacy `/s/...` links. Decide between removing the button and
  making it deactivate the row and create a new one (new id = genuinely new URL).
- Nothing was tested against the local database — MySQL refused connections on
  127.0.0.1:3306 all session. Verification was by rendering partials through
  `artisan tinker`, matching URLs against the router, compiling every Blade, and reading
  the live production database through cPanel.
- Still no automated tests for the worker endpoints, audit services, or the new routes.

## Session notes — 2026-09-09

- Diagnosed empty Instagram audit metrics (engagement rate, avg likes/comments, posts per month, last post): the worker's JSON call gets HTTP 429 and falls back to `profileFromPage()`, which returns `edges => []`, so only follower/following/post counts survive.
- Root cause of the 429: the cookie was only a bare `sessionid` — Instagram throttles signed-in API calls without `csrftoken` and `mid`. Fix is to paste the whole Cookie header from devtools → Network → Request Headers.
- Decision: the session cookie is stored once in the DB, not in `.env` and `config.php`. New page **Settings → Instagram Session** (`InstagramSettingController`, `admin.settings.instagram` view), admin-only, deliberately separate from Company Profile.
- `SessionStore` (`app/Services/SmartLink/Instagram/SessionStore.php`) is the single accessor: value encrypted with `Crypt`, `.env` kept as fallback, masked preview + `csrftoken`/`mid` presence reported to the page.
- Worker gets the cookie from `GET /api/instagram/session`, guarded by the existing `X-Worker-Token`; `worker.php` uses it only when `config.php` leaves `instagram_session_id` empty, cached once per run.
- `poll_seconds` 300 → 10. Polling hits the portal only, never Instagram, so it does not raise ban risk; it just starts a clicked audit sooner.
- Decision: no 429 back-off — user explicitly declined it.
- Rejected: a "run worker.php on my desktop" button. Shared hosting cannot execute anything on a local PC; only a local agent listening on 127.0.0.1 could, and that was not pursued.
- Committed as `c51e1c0` on `main` (not pushed).
- Next steps: deploy, `php artisan config:clear`, paste the full cookie on the new page, copy the new `worker.php` to the PC and blank `instagram_session_id` there, then `php worker.php --check <username>` — expect `read via API` and `recent posts read : 12`.
- Open: nothing was tested against a database — MySQL was not running locally and this PHP has no sqlite driver; only route registration and Blade compilation were verified.
- Open: still no automated tests for the worker endpoints or audit services.

## Session notes — 2026-09-08

- Decision: shared hosting cannot fetch Instagram at all (blocked by IP, any cookie), so the portal no longer tries — it queues instead.
- Added `local_pc_instagram_script/` — a standalone PHP worker run on a local PC via Task Scheduler / `run.bat`; polls the portal, fetches Instagram, posts results back.
- Pattern: outbound-only worker. Nothing connects *to* the PC, so no port forwarding, fixed IP or firewall change.
- New endpoints `GET /api/instagram/pending` and `POST /api/instagram/result`, throttled `120,1`, authenticated by shared secret `INSTAGRAM_WORKER_TOKEN`, CSRF-exempt via `bootstrap/app.php`.
- `config/services.php` → `instagram.source` picks the strategy: `web` (anonymous, login-walled fallback), `session` (auto-selected when `INSTAGRAM_SESSION_ID` is set), `worker` (queue for the local PC).
- Profile sources are swappable classes behind `Instagram/ProfileSource` (`WebProfileSource`, `SessionCookieSource`).
- Convention: worker `config.php` holds a live Instagram login — gitignored, never committed, never copied to the server; use a throwaway IG account.
- Fetches are spaced 20 seconds apart deliberately; Instagram throttles bursts.
- Open: session cookie expires after weeks/months — user must paste a fresh one; portal surfaces "The Instagram session has expired".
- Open: audits stall silently while the worker PC is off (queued, not lost).

## Earlier decisions (through 2026-09-07)

- Shared-hosting-first architecture: `SESSION_DRIVER=file`, `CACHE_STORE=file`, `QUEUE_CONNECTION=sync`, AJAX polling for live visitors, static assets from `/public`.
- Controllers/models/views split per module; shared behaviour lives in `app/Services/*` (`AccessService`, `UploadService`, `CsvService`, `AnalyticsService`, `PresentationBuilderService`), not duplicated in controllers.
- SmartLink domain services: `SmartLinkService`, `SmartTrackingService`, `IntentScoreService`, `PageSpeedInsightsService`, `InstagramProfileService`.
- Website audit runs on Google PageSpeed Insights (`config/services.php` → `pagespeed`); results stored in `website_audits`, Instagram results in `instagram_audits` (migrations `2026_09_07_000100/000200`).
- Uploads confined to `public/upload` with `.htaccess` blocking script execution; controllers validate type, size and MIME.
- Demo credentials use Laravel's encrypted cast — `APP_KEY` must never change after data exists.
- Analytics: per-visit session UUID + persistent visitor UUID; 20s heartbeats update the live session but are not stored as event rows; section time counts only while the tab is visible and active.
- Roles: `admin` (everything) vs `salesperson` (own records only); product master and settings are admin-only.
- UI is a Fastkart-derived green admin theme; September work was color/theme-level only (transparent header, sidebar, search bar, scrollbar).
