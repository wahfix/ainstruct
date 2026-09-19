# SECURITY — Rules, Auth, Validation

This file defines security rules, authentication/authorization patterns, and validation requirements. Rules are universal unless marked project-specific where security patterns diverge.

---

## Authentication

- Laravel Breeze + Inertia (Vue) — routes in `routes/auth.php`, controllers in `app/Http/Controllers/Auth/`, pages in `resources/js/pages/Auth/`.
- Session-based authentication.
- Email verification required for application access (`verified` middleware on protected application routes).
- Password confirmation for sensitive actions (`password.confirm` route).

---

## Authorization

### Middleware

- `auth` middleware for protected routes.
- `verified` middleware for email verification.
- `guest` middleware for public-only routes.
- `signed` middleware for email verification links.
- `throttle` middleware for rate limiting.

### Policy-Based

- Policies exist for model-level authorization.
- New features MUST implement actual authorization logic.
- Do not ship placeholder policies that return `false` for everything.

### Role-Based (Spatie Permission)

- `User` model uses `HasRoles` / `HasPermissions` (backed by `App\Contracts\Model\HasRolesContract` / `HasPermissionsContract`).
- Enforce permissions server-side via middleware gates or `can:`/policies — never hide UI alone.
- Audit role/permission changes (records via `spatie/laravel-activitylog`).

---

## Validation

### Primary: In Actions

RuledActions validate data before business logic:

```php
public function rules(array $payload): array
{
    return [
        'nik' => ['required', 'string', 'max:16', Rule::unique(SidResident::class)],
        'name' => ['required', 'string', 'max:255'],
    ];
}
```

### Secondary: In Controllers

Simple inline validation for basic cases:

```php
$validatedData = $request->validate([
    'nik' => 'required|unique:residents|max:255',
    'nama_lengkap' => 'required|max:255',
]);
```

### Form Requests

Used for authentication flows and settings (complex request-level validation).

- Business logic validation belongs in RuledActions, not Form Requests.

---

## Rate Limiting

Implement rate limiting for sensitive endpoints:

```php
if (RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
    throw ValidationException::withMessages([...]);
}
```

And in routes:

```php
Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
    ->middleware(['signed', 'throttle:6,1']);
```

### Per-route rate limiting

- Define named limiters in `App\Providers\AppServiceProvider` (`RateLimiter::for('{name}', …)`) and reference them on routes: `->middleware('throttle:{name}')`.
- Apply `throttle:` to auth-adjacent endpoints (login, register, verify-email, password reset), file uploads, and any route that triggers external side-effects (email, SMS, exports).
- Defaults: login/register `throttle:5,1`; general authenticated API `throttle:60,1` where applicable.

```php
// In a service provider
RateLimiter::for('uploads', fn (Request $request) => Limit::perMinute(10)->by($request->user()?->id ?: $request->ip()));
```

### CAPTCHA / anti-automation (when bots are a concern)

- Add a honeypot field and/or rate limiting on public forms before applying CAPTCHA — CAPTCHA is a last resort, not a default.

---

## File Upload Security

- All uploads go through `spatie/laravel-medialibrary` on the model (conversions, validation) — **never** store raw uploaded paths manually.
- Validate server-side in the RuledAction:

  ```php
  'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
  'document' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx', 'max:10240'],
  ```

- Rule set:
  - `image` for images; explicit `mimes:` whitelist (never allow `*` / arbitrary extensions).
  - `max:{kb}` size limit per entity (2 MB images, 10 MB documents are reasonable ceilings).
  - `dimensions:` when exact dimensions matter (e.g. avatars).
- Store on private disks for sensitive documents; public disk only for non-sensitive public assets.
- Never trust the client filename — MediaLibrary sanitizes; do not reconstruct paths from user input.
- Reject empty uploads / oversized files before MediaLibrary processing (fail fast with `ValidationException`).

---

## CORS

- Only enable cross-origin access when an authenticated cross-origin client actually exists (e.g. a separate frontend domain).
- Configure in `config/cors.php`: restrict `allowed_origins`, `allowed_methods`, `allowed_headers` — do not use `*` for `allowed_origins` when credentials/cookies are involved.
- Session-based auth uses cookies — `supports_credentials` must be `true` for cross-origin sessions to work; cross-origin then requires `withCredentials: true` in the client and CSRF token handling.
- Inertia (same-origin) does NOT need CORS; never enable CORS just "in case".

---

## Sensitive Data Handling

### Indonesian Personal Data (NIK / KK / Addresses)

The SID domain handles Indonesian personal data (`nik`, family/address records). These are sensitive:

- Never log, dump, or expose NIK/identity fields in responses, errors, or debug output.
- Omit personal identity fields from lists/datasets unless the feature truly needs them; prefer `select()` over `*` in repository queries that expose them.
- Mask or exclude identity attributes in audit/activity logs where feasible.

### Hidden Attributes

```php
protected $hidden = ['password', 'remember_token'];
```

### Media Uploads (Spatie MediaLibrary)

- Handle user-uploaded files through `spatie/laravel-medialibrary` on the model (conversions, validation) — never store raw uploaded paths manually.
- Validate upload MIME/size server-side; sanitize filenames.

### Audit Trails (Spatie ActivityLog)

- Important mutations (user, resident, group/menu settings) MUST be recorded via `activity()` / the model's `LogsActivity` trait so history is not lost.
- Never delete activity/history records.

### Telescope (dev validation)

Hide sensitive request details even when Telescope is enabled:

```php
Telescope::hideRequestParameters(['_token', 'nik']);
Telescope::hideRequestHeaders(['cookie', 'x-csrf-token', 'x-xsrf-token']);
```

### Password Hashing

- `password` cast as `hashed` in User model.
- `Hash::make()` used in password creation/reset.
- Configure appropriate BCRYPT_ROUNDS for production and testing.

---

## Session Security

```php
$request->session()->invalidate();
$request->session()->regenerateToken();
```

Used after logout and password changes.

---

## CSRF Protection

- Use Laravel's built-in CSRF token handling.
- Use `@routes` directive / Ziggy for route generation in JavaScript.
- Never disable CSRF middleware.

---

## Key Security Rules

1. **NEVER** store plaintext passwords.
2. **ALWAYS** use `Hash::make()` for password creation.
3. **ALWAYS** validate input at the Action layer.
4. **ALWAYS** use `auth` middleware for protected routes.
5. **ALWAYS** use `Rule::unique()` for unique validation (not raw SQL).
6. **ALWAYS** use `Rule::exists()` for foreign key validation.
7. **NEVER** expose sensitive data in responses.
8. **NEVER** trust user input — always validate.
9. Use `constrained()` on foreign key migrations.
10. **NEVER** commit secrets or API keys to the repository.
11. **ALWAYS** whitelist upload MIME types and enforce a size limit server-side (see File Upload Security).
12. **ALWAYS** rate-limit auth-adjacent and side-effect endpoints (see Rate Limiting).
13. **NEVER** enable CORS for same-origin Inertia apps, or with `*` origins on credentialed setups (see CORS).

---

## Prohibited in Committed Code

The authoritative forbidden list is `11-forbidden-behavior.md` (single source of truth). The
security-specific highlights below MUST NOT diverge from it — if a rule belongs in the master
list, update it there, not here:

- Using `dd()`, `dump()`, or `ray()` in committed code.
- Logging passwords, tokens, or sensitive data.
- Returning raw database errors to users.
- Storing secrets in plaintext config files committed to git.
