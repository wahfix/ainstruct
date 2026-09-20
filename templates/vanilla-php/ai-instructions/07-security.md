# SECURITY — Rules, Auth, Validation

This file defines security rules, authentication/authorization patterns, and validation
requirements. Rules are universal unless marked project-specific where security patterns diverge.
The project's security specifics (session driver, auth flows, CSRF strategy, upload rules) are
recorded in `MASTER_BUILD_SPECIFICATION.md` — never invent them.

---

## Authentication (project-defined, no framework auth)

The template does not bundle an auth framework. The project's auth approach is decided and
recorded in `MASTER_BUILD_SPECIFICATION.md`. Universal rules apply regardless of the chosen
approach:

- **Passwords are NEVER stored in plaintext.** Use `password_hash()` with the `PASSWORD_DEFAULT`
  algorithm; verify with `password_verify()`.
- **Sessions are server-side and secure.** Session cookie: `HttpOnly`, `Secure` (in production),
  `SameSite=Lax` (or `Strict` for a higher bar) — per project decision.
- **Regenerate session id on privilege change** (login/logout/password change):
  `session_regenerate_id(true)`.
- **Logout destroys the session:** `session_unset()` + `session_destroy()` + clear cookie.
- **Email/OTP verification** for sensitive application access is a project decision; when it
  exists, verification links are signed and expire (never accept an unsigned verification link).

---

## Authorization

- **Authorization is decided server-side in the Entry layer** (or in a reusable guard class),
  never by hiding UI alone.
- Use a `Guard`/authorization service when rules are reused across handlers (per
  `03-architecture.md` — Services when warranted).
- New features MUST implement actual authorization logic.
- Do not ship placeholder guards that return `false` for everything.

---

## Validation

### Primary: In Actions

RuledActions validate data before business logic:

```php
public function rules(array $payload): array
{
    return [
        'nik' => ['required', 'string', 'max:16', 'regex:/^[0-9]{16}$/'],
        'name' => ['required', 'string', 'max:255'],
    ];
}
```

The validation engine is project-defined (the templated RuledAction in
`12-project-specific/canonical-snippets.md` ships a minimal validator; a library such as
`respect/validation` or `illuminate/validation` MAY be adopted — record the decision in
`MASTER_BUILD_SPECIFICATION.md`).

### Transport-Level

Entry handlers perform only transport-level checks (content-type, size limits, required route
params). Business validation lives in RuledActions.

---

## Rate Limiting

Implement rate limiting for sensitive endpoints (login, registration, password reset, file
uploads, any endpoint that triggers external side-effects). The mechanism is project-defined
(middleware layer, a rate-limiter library, or a simple counter in the data store) — record it in
`MASTER_BUILD_SPECIFICATION.md`.

### CAPTCHA / anti-automation (when bots are a concern)

Add a honeypot field and/or rate limiting on public forms before applying CAPTCHA — CAPTCHA is a
last resort, not a default.

---

## File Upload Security

- Validate uploads server-side in the RuledAction:
  - explicit MIME whitelist (never allow `*` / arbitrary extensions);
  - size limit per entity (record ceilings in `MASTER_BUILD_SPECIFICATION.md`);
  - validate against the actual file content (`finfo`) where feasible, not just the client
    filename/extension.
- Store sensitive documents on a private disk; public disk only for non-sensitive public assets.
- Never trust the client filename — sanitize/generate storage names; do not reconstruct paths
  from user input.
- Reject empty uploads / oversized files before processing (fail fast with `ValidationException`).

---

## CORS

- Only enable cross-origin access when an authenticated cross-origin client actually exists.
- Restrict `allowed_origins`, `allowed_methods`, `allowed_headers` — do not use `*` for
  `allowed_origins` when credentials/cookies are involved.
- Same-origin applications do NOT need CORS; never enable CORS just "in case".

---

## CSRF Protection

- All state-changing HTTP requests (POST/PUT/PATCH/DELETE) MUST be protected against CSRF when
  cookies/sessions are used.
- Generate a CSRF token per session, embed it in forms/headers, and verify on every
  state-changing request.
- Never disable CSRF verification. Never accept a missing token silently.

---

## Sensitive Data Handling

- Never log, dump, or expose personal identity fields in responses, errors, or debug output
  (passwords, tokens, NIK/SSN, addresses where the project says so).
- Omit identity fields from lists/datasets unless the feature truly needs them.
- Mask or exclude identity attributes in audit/activity logs where feasible.

### Secrets / Configuration

- Secrets (DB credentials, API keys, app keys) live in environment variables / `.env` — NEVER in
  committed files, config arrays committed to git, or inline constants.
- `.env` is NOT committed; `.env.example` documents the required keys with placeholder values.
- Rotate secrets before/after exposure; never commit a real secret "temporarily".

### Password Hashing

- `password_hash()` on creation/reset; `password_verify()` on login.
- Never rehash plaintext values that arrive from a client — hash only on set.
- Use `password_needs_rehash()` when rehashing on login for algorithm upgrades.

---

## Key Security Rules

1. **NEVER** store plaintext passwords.
2. **ALWAYS** use `password_hash()` for password creation.
3. **ALWAYS** validate input at the Action layer.
4. **ALWAYS** decide authorization server-side.
5. **ALWAYS** protect state-changing requests against CSRF.
6. **NEVER** expose sensitive data in responses.
7. **NEVER** trust user input — always validate.
8. **NEVER** commit secrets or API keys to the repository.
9. **ALWAYS** whitelist upload MIME types and enforce a size limit server-side.
10. **ALWAYS** rate-limit auth-adjacent and side-effect endpoints.
11. **NEVER** enable CORS with `*` origins on credentialed setups; don't enable it "just in case".
12. **ALWAYS** use prepared statements / parameter binding for any database query that includes
    user input (`13-database.md`).

---

## Prohibited in Committed Code

The authoritative forbidden list is `11-forbidden-behavior.md` (single source of truth). The
security-specific highlights below MUST NOT diverge from it — if a rule belongs in the master
list, update it there, not here:

- Using `dd()`, `dump()`, or `ray()` in committed code.
- Logging passwords, tokens, or sensitive data.
- Returning raw database errors to users.
- Storing secrets in plaintext config files committed to git.
- Building SQL query strings by concatenating user input.
