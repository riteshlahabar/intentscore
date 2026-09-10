# CLAUDE.md

Laravel 13 + MySQL "IntentScore" portal (smart links, prospects, website + Instagram
audits, engagement scoring). Deployed on shared cPanel hosting: no queue daemon, no
Redis, no cron dependency for core features. See `README.md` and `ARCHITECTURE.md`.

## Working conventions

- Keep replies short; the user often writes Marathi in Latin script — reply in the language they used.
- Explain the approach and wait for explicit go-ahead before writing or changing code.
- Keep the existing admin/public UI layout; retheme colors only, do not redesign.
- "Please correct: <error text>" means fix the root cause, not the wording.

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
