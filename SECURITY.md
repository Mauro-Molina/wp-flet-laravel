# Security review — Fase 5

Checklist de cierre para WP Fleet Core. Cada ítem debe permanecer verdadero en PRs futuros.

## Multi-tenancy

- [x] Modelos operativos tenant-aware (Site, Command, Backup, credentials, licenses, pending updates) extienden `TenantScopedModel` (scope fail-closed).
- [x] Sin `TenantContext` las queries lanzan `TenantContextMissingException` (no leak).
- [x] Plugin HMAC usa `Site::findForPlugin()` / `TenantContext::bypass()` de forma explícita.
- [x] JWT setea `TenantContext` desde claim `tenant_id` y valida membership.
- [x] `audit_log` / `events_log` guardan `tenant_id` pero no usan el global scope (append-only / ingest); filtros por tenant en queries de lectura.

## Autenticación / autorización

- [x] Auth usuarios: JWT Bearer (no Sanctum/Passport como auth principal).
- [x] Auth plugin: HMAC (`X-Site-Id`, `X-Timestamp`, `X-Signature`) con ventana anti-replay.
- [x] RBAC Spatie teams + `site_user_access` para Developers/Client.
- [x] 2FA TOTP obligatorio para Owner/Admin cuando está habilitado.
- [x] Licencia suspended bloquea comandos y contenido **antes** del transporte.

## Rate limiting

| Superficie | Limiter | Clave |
|------------|---------|-------|
| `POST /auth/login` | `auth-login` | IP |
| `POST /auth/register` | `auth-register` | IP |
| `POST /auth/refresh` | `auth-refresh` | IP |
| `POST /auth/2fa/verify` | `auth-2fa` | IP |
| `/api/v1/*` autenticado | `api-tenant` | `tenant_id` + límite del plan |
| `/plugin/v1/*` | `plugin` | `X-Site-Id` |

Límites por plan (default): starter 60/min, pro 300/min, enterprise 1000/min.
Sobreescribibles vía `plan.features.api_rate_limit_per_minute` o env `RATE_LIMIT_*`.

Respuesta 429: envelope `{ errors: [{ code: rate_limit_exceeded }] }`.

## Headers

Middleware `SecurityHeaders` en API y plugin:
`X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`, `Referrer-Policy: no-referrer`, `Cache-Control: no-store`.

## Audit

- [x] `audit_log` append-only (updates/deletes rechazados).
- [x] Middleware `AuditSensitiveRequest` en rutas JWT autenticadas.

## Secretos

- [x] Credenciales de sitio: secret encriptado + hash; rotación con versión.
- [x] JWT secret / Stripe secrets solo vía env.
- [x] Fake Agent deshabilitado por default (`FAKE_AGENT_ENABLED=false`).

## OpenAPI

- Docs: `/docs/api` (UI), `/docs/api.json` (spec OpenAPI 3.1 vía Scramble).
- Gate `viewApiDocs`: abierto en `local`/`testing`; en prod requiere usuario autenticado con permiso de billing view (ajustar según política de ops).
