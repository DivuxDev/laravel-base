# CLAUDE.md — Laravel Base API

## Project overview
REST API backend (Laravel 12, PHP 8.4) that pairs with `vue-base-template` frontend. Provides authentication, admin user management, audit logging, and real-time WebSocket events.

## Quick commands
```bash
composer dev              # Start Laravel + Reverb (development)
composer test             # Run PHPUnit tests
php artisan migrate --seed # Seed DB with test users
php artisan storage:link  # Required for avatar uploads
```

## Architecture

### Response envelope
ALL endpoints return: `{ success: bool, data: T|null, message: string }`. Validation errors (422) add an `errors` field.

### Authentication
- Sanctum Bearer tokens stored in `personal_access_tokens` table (24h expiration by default, configurable via `SANCTUM_TOKEN_EXPIRATION`)
- Google OAuth via Socialite (stateless)
- Password reset flow: token-based via email, 60-minute expiry, anti-enumeration (always returns success)
- Email verification: HMAC-signed links, sent on registration + resend endpoint
- Brute force: 5 failed logins → 15 min lockout (fields: `failed_login_attempts`, `locked_until` on User model)
- All previous tokens revoked on login/logout (single-session enforcement)

### Middleware stack (order matters)
1. `HandleCors` (prepended — must run first for preflight)
2. `throttleApi` (global API throttle)
3. `LogHttpRequests` (logs method, URL, IP, timing)
4. `SecurityHeaders` (X-Frame-Options, HSTS, etc.)
5. `EnsureAdmin` (alias: `admin`) — applied to admin routes only

### Rate limiters (defined in AppServiceProvider)
- `api`: 60/min per user/IP (global default)
- `register`: 5/min per IP
- `password-reset`: 5/min per IP
- `admin`: 30/min per user
- `user-api`: 60/min per user
- Login: 10/min per IP (inline in routes)
- Email verification send: 6/min per user (inline in routes)

### Key patterns
- **Filterable trait** (`app/Traits/Filterable.php`): Generic search + sort + pagination. Used by `AdminUserController` and `AuditLogController`. Pass `$searchable` and `$sortable` arrays from the controller, not the model.
- **AuditService** (`app/Services/AuditService.php`): Static `log($action, $data)` method. Auto-captures `auth()->id()`, `request()->ip()`, `request()->userAgent()`.
- **Form Request validation**: `RegisterRequest` uses `Password::min(8)->mixedCase()->numbers()->symbols()`.
- **UserResource**: Serializes only safe fields (hides password, google_id, remember_token).

### Database
- MySQL with migrations. Key tables: `users`, `personal_access_tokens`, `audit_logs`, `password_reset_tokens`
- `audit_logs` schema: `id, user_id, action, auditable_type, auditable_id, old_values (json), new_values (json), ip_address, user_agent, timestamps`
- Composite indexes on `audit_logs`: `[user_id, created_at]`, `[action, created_at]`, `[auditable_type, auditable_id]`
- User `role` field: `'admin'` or `'user'` (default)
- Users have soft deletes (`deleted_at` column) — admin deletion is recoverable
- Admin destructive operations (password reset, delete) wrapped in DB transactions

### Pagination format
Paginated endpoints return:
```json
{
  "data": {
    "<items_key>": [...],
    "meta": { "current_page": 1, "last_page": N, "per_page": 15, "total": N }
  }
}
```
Query params: `page`, `per_page` (max 100), `search`, `sort`, `sort_dir` (asc/desc).

### File uploads
- Avatar uploads go to `storage/app/public/avatars/`
- Requires `php artisan storage:link` for public access
- `ProfileController` stores via `$request->file('avatar')->store('avatars', 'public')`
- Old avatars are automatically deleted when a new one is uploaded

### WebSockets
- Laravel Reverb on port 8081
- `UserLoggedIn` event broadcasts on public `notifications` channel
- Wrapped in try/catch — login succeeds even if Reverb is down

### Mail
- `WelcomeEmail` sent on registration via `MailService`
- `VerificationEmail` sent on registration and via resend endpoint
- `ResetPasswordEmail` sent via forgot-password endpoint
- All wrapped in try/catch — core operations succeed even if mail is down
- Mailtrap SMTP (production: live.smtp, testing: sandbox.smtp)

## Seeded test accounts
| Email | Password | Role |
|---|---|---|
| admin@example.com | password | admin |
| user@example.com | password | user |
| test@example.com | password | user |

## Key files to modify for common tasks
- **Add new API endpoint**: `routes/api.php` → new Controller in `app/Http/Controllers/Api/`
- **Add audit logging**: Call `AuditService::log('action.name', [...])` in the controller
- **Add pagination to endpoint**: Use `Filterable` trait, call `applyFilters()` + `paginate()`
- **Add rate limiter**: Define in `AppServiceProvider::boot()`, apply via `throttle:name` middleware
- **Add validation rule**: Create/edit FormRequest in `app/Http/Requests/`
