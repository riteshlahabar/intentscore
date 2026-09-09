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

- No code changes today; working tree clean, last commits are from 2026-09-08.
- Added this `CLAUDE.md` as the running log of decisions, conventions and open items.
- Next steps: end-to-end test of the Instagram worker loop from the live server, and confirm `INSTAGRAM_SOURCE=worker` + `INSTAGRAM_WORKER_TOKEN` are set in production `.env`.
- Open: no automated tests cover the worker endpoints or the audit services.
- Open: commit messages are all "instagram" / "for web audit api" — no useful history granularity.

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
