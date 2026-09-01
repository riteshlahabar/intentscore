# Architecture Notes

## Request flow

```text
WhatsApp private link
        |
        v
/p/{public_token}
        |
        +--> Laravel loads Client + Product + Presentation + enabled Sections
        |
        +--> One-page Blade presentation
                 |
                 +--> section views / active time
                 +--> buttons / credentials / video / URL events
                 +--> 20-second live heartbeat
                          |
                          v
                 POST /p/{token}/track
                          |
                          +--> presentation_sessions
                          +--> presentation_events
                          +--> engagement score
```

## MVC and reusable code

Controllers are split by module. Models are split by business domain. Views are split by module. Shared behavior is kept in services/components rather than copied into controllers.

- `AccessService` — salesperson ownership enforcement
- `UploadService` — sanitized `public/upload` storage
- `CsvService` — reusable streaming import/export
- `AnalyticsService` — event/session tracking + scoring
- `PresentationBuilderService` — public token/reference + default sections
- Blade components — data tools/status badges

## Shared-hosting design decisions

- `SESSION_DRIVER=file`
- `CACHE_STORE=file`
- `QUEUE_CONNECTION=sync`
- AJAX polling for live visitors
- no daemon process
- no WebSocket server
- no Redis dependency
- no cron dependency for core functions
- static CSS/JS from `/public`

This makes the first version deployable on common cPanel hosting while preserving a clean path to Redis/queues/WebSockets later if traffic grows.
