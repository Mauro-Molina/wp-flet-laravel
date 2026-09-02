# WP Fleet Core

Backend multi-tenant Laravel 12 para administrar flotas de sitios WordPress.

## Fase actual

**Fase 5 — API pública, versionado, documentación**: rate limiting por tenant/plan, OpenAPI (Scramble), hardening de seguridad.

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan serve
```

## Versionado y envelope

Todas las rutas de app viven bajo `/api/v1` y responden:

```json
{ "data": {}, "meta": {}, "errors": [] }
```

Plugin: `/plugin/v1` (HMAC). Webhook Stripe: `/webhooks/stripe`.

## Rate limiting

| Superficie | Limiter | Clave | Default |
|------------|---------|-------|---------|
| Login | `auth-login` | IP | 10/min |
| Register | `auth-register` | IP | 5/min |
| Refresh | `auth-refresh` | IP | 20/min |
| 2FA verify | `auth-2fa` | IP | 10/min |
| API JWT | `api-tenant` | `tenant_id` | plan: starter 60 / pro 300 / enterprise 1000 |
| Plugin HMAC | `plugin` | `X-Site-Id` | 120/min |

429 → `{ errors: [{ code: "rate_limit_exceeded" }] }`

Vars: `RATE_LIMIT_*` en `.env.example`.

## OpenAPI (Scramble)

```
/docs/api       # UI
/docs/api.json  # OpenAPI 3.1
```

Solo `local`/`testing` por default; en prod el gate `viewApiDocs` exige usuario autenticado.

## Seguridad

Ver [SECURITY.md](SECURITY.md) — checklist multi-tenant, HMAC, rate limits, headers, audit append-only.

Headers en API/plugin: `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Cache-Control: no-store`.

## Contenido (proxy JWT → plugin)

La app usa `/api/v1/sites/{id}/content/*` (JWT). El Core reenvía HMAC al plugin en:

```
{site.url}/{CONTENT_AGENT_PATH_PREFIX}/...
```

Default / prod: `CONTENT_AGENT_PATH_PREFIX=wp-json/wpfleet/v1` y `FAKE_AGENT_ENABLED=false`.

| Recurso | Rutas |
|---------|-------|
| Posts | list, CRUD, publish, schedule — body se reenvía **as-is** (`author`, `categories`, `tags`, `featured_media`, etc.) |
| Pages | list, CRUD |
| Users WP | list, invite, CRUD |
| Categories | `GET/POST .../content/categories` |
| Tags | `GET/POST .../content/tags?search=` |
| Media | `GET/POST .../content/media`, `GET/DELETE .../media/{id}` |
| Settings | get, update |

### Media upload

La app puede enviar:

1. **JSON (recomendado móvil):**
   ```json
   { "file_base64": "...", "filename": "photo.jpg", "mime_type": "image/jpeg" }
   ```
2. **multipart/form-data** con campo `file` — el Core lo convierte a JSON+base64 antes de firmar hacia el plugin.

Respuesta de post puede incluir: `author`, `categories[{id,name}]`, `tags[{id,name}]`, `featured_media`, `featured_image_url`.

Fake Agent (solo stub): `FAKE_AGENT_ENABLED=true` → dispatch in-process / rutas `/fake-agent/wp/v2/*`.

## Operacional (Fases 2–3)

Updates, backups, security, uptime, billing vía comandos + heartbeat.

## Tests

```bash
php artisan test
```

## Fases

| Fase | Estado |
|------|--------|
| 1 Multi-tenant + JWT + RBAC + audit | ✅ |
| 2 Sites + HMAC + commands + events | ✅ |
| 3 Updates, backups, security, billing | ✅ |
| 4 Content proxy + Fake Agent | ✅ |
| 5 Rate limits + OpenAPI + security review | ✅ |
