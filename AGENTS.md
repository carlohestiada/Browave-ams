# Browave AMS Agent Guide

## Project Shape

- This is a PHP 8+ application served from Apache/XAMPP.
- Requests enter through `public/*.php` pages or `public/api/**` endpoints.
- Controllers in `app/controllers/` coordinate requests and business rules; models in `app/models/` own PDO queries and persistence.
- Shared page setup, authentication, RBAC, CSRF, and AJAX behavior live in `public/layouts/` and `app/config/`.
- Frontend JavaScript and CSS live under `public/assets/`.

## Database And Runtime

- PostgreSQL (`pgsql`) is the active database driver. Treat the older MySQL/MariaDB wording in the SDS as historical unless the task explicitly targets it.
- Read environment-specific settings through the existing database configuration. Never commit credentials or populated `.env` files; use `.env.example` as the reference.
- Preserve the Apache `PATH_INFO` routing contract used by nested API endpoints.
- `PUT` request bodies are currently parsed as URL-encoded form data, not JSON.

## Security And Access

- API entry points must keep `app/config/api_auth.php` loaded. It enforces the authenticated session and CSRF checks for mutating requests.
- Keep page-level and API-level RBAC behavior aligned with the existing controller rules.
- Preserve session hardening, login rate limiting, session ID regeneration, and CSRF token lifecycle when changing authentication code.
- Use prepared PDO statements and existing validation/error-response patterns. Do not expose secrets or raw sensitive data in responses or logs.

## Development Workflow

- Keep changes scoped to the owning layer and match the existing naming style: PascalCase classes, camelCase PHP variables, and snake_case database identifiers.
- Before changing an API, inspect its page-side JavaScript caller and a neighboring endpoint/controller for the established request and response shape.
- For database changes, update the appropriate migration/schema artifact and verify behavior against PostgreSQL; do not assume the legacy SQL dump is authoritative.
- There is no configured Composer test suite, linter, or static analyzer. For PHP changes, run `php -l` on each changed PHP file. For JavaScript changes, use the available browser workflow and inspect the browser console/network responses.
- For user-facing changes, manually test through `http://localhost/Browave-ams/public/` with Apache and PostgreSQL running. Include authentication, permissions, success, validation failure, and refresh/persistence checks where relevant.

## Documentation

- Product scope and historical requirements: [BROWAVE-AMS-SDS-v3.md](BROWAVE-AMS-SDS-v3.md)
- Project notes and current troubleshooting material: [AgentNotes/README.md](AgentNotes/README.md) and [AgentNotes/QUICK-REFERENCE.md](AgentNotes/QUICK-REFERENCE.md)
- PostgreSQL migration guidance: [AgentNotes/postgresql-migration.md](AgentNotes/postgresql-migration.md)
- Database configuration: [app/config/database.php](app/config/database.php)
- API authentication boundary: [app/config/api_auth.php](app/config/api_auth.php)
- Representative API entry point: [public/api/accommodations/index.php](public/api/accommodations/index.php)

When documentation conflicts with executable configuration, follow the current code and call out the discrepancy in the change summary.
