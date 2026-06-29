---
name: Shaheen Bot Railway Deployment
description: What was needed to make the bot work on Railway (500 error fixes)
---

## Root Causes of 500 Error

1. `limit_access_to_telegram_only()` calls `die()` on Railway because REMOTE_ADDR is Railway's proxy IP, not Telegram's. **Fix:** remove this call; token query-param check is sufficient.
2. PHP `const` cannot call `getenv()` — Railway DB env vars were never read. **Fix:** change all `const` to `define()` with `getenv()` fallbacks.
3. `DB_CHARSET` was set to a collation (`utf8mb4_general_ci`) not a charset — mysqli::set_charset() fails. **Fix:** use `utf8mb4`.
4. `start.sh` hardcoded PORT 5000; Railway uses `PORT` env var. **Fix:** `PORT="${PORT:-5000}"`.
5. `start.sh` tried to start local MySQL on Railway. **Fix:** detect Railway via `RAILWAY_PUBLIC_DOMAIN` and skip local MySQL startup.
6. No `try-catch` in webhook.php — any exception returned 500. **Fix:** wrap all logic in try/catch, send 200 header before any processing.
7. `set_language_by_user_id(NULL)` called when update_from is null. **Fix:** null-guard before calling.

## Railway Environment Variables Required

| Variable | Used for |
|---|---|
| `MYSQLHOST` | DB host |
| `MYSQLPORT` | DB port |
| `MYSQLUSER` | DB user |
| `MYSQLPASSWORD` | DB password |
| `MYSQLDATABASE` | DB name |
| `RAILWAY_PUBLIC_DOMAIN` | Webhook URL base |
| `PORT` | PHP server port |
| `BOT_TOKEN` | (optional) override hardcoded token |
| `WEBHOOK_SECRET` | (optional) Telegram secret_token for extra security |

## Railway Build Config
- `nixpacks.toml` at project root: php82, composer, mysql80 client, runs `composer install` then `bash start.sh`

**Why:** Railway is a PaaS with a reverse proxy — IP allowlisting breaks webhook delivery. Token check + optional secret_token header is the correct security model.

**How to apply:** If a new Railway service is added, ensure all MYSQL* env vars are set from the Railway MySQL plugin. RAILWAY_PUBLIC_DOMAIN is set automatically.
