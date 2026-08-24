Change log (repo-scoped) — updated 2026-08-24

- 2026-08-24: Security — stop committing DB credentials to git
  - `app/config/database.local.php` and `database.production.php` now read `DB_HOST`/`DB_PORT`/`DB_NAME`/`DB_USER`/`DB_PASS` via `getenv()` instead of hardcoded values. Production throws a `RuntimeException` if `DB_USER`/`DB_PASS` aren't set, rather than silently connecting with nothing.
  - Added a minimal `.env` loader (no Composer dependency) in `app/config/environment.php` — reads a repo-root `.env` for local dev only, never overrides real environment variables.
  - Added `.env.example` documenting required variables with placeholder values.
  - `.gitignore`: un-commented `app/config/database.production.php`, added `app/config/database.local.php` and `.env.*` (except `.env.example`).
  - **Still required (cannot be done from this environment — no access to your Postgres server or GitHub push access):**
    1. Rotate the actual Postgres password (`browave_user` / local `postgres`) on the real server — it's already exposed in git history regardless of the code fix above.
    2. Purge `database.production.php`/`database.local.php` from git history (6 prior commits contain the old password) via `git filter-repo` or BFG, then force-push and have all collaborators re-clone.
    3. Create a real `.env` (or set OS-level env vars) on each environment with the new password — the app will throw a clear error in production until this is done.

- 2026-06-20: Transfer modal fixed
  - Moved Transfer modal out of the nested assign form in `public/room-assignments.php`.
  - Switched jQuery `.modal(...)` calls to Bootstrap 5 API (`bootstrap.Modal(...)`) in `public/assets/js/room_assignments.js`.
  - Added transfer modal wiring and preview (`openTransfer`, `#transferForm` submit handler).

- 2026-06-20: Room assignment UX and validation
  - Enforced one active room per employee via `RoomAssignment::hasActiveAssignment()` in `app/models/RoomAssignment.php`.
  - `create()` now checks for existing active assignment and returns structured error if present.
  - `app/controllers/RoomAssignmentController.php` validates `expected_checkout_date` presence and date order, and returns clear JSON errors.
  - Updated `public/room-assignments.php` labels: `Arrival Date` / `Departure Date` (UI) and later changed to `Check In`/`Check Out` headers.

- 2026-06-20: Transfer logic improvements
  - `RoomAssignment::transfer()` now checks for room availability and same-room selection, returning descriptive errors when appropriate.
  - Controller returns descriptive JSON errors; frontend displays via SweetAlert.

Notes / How I'll use this
- I will append this file with a short entry every time I make edits in the workspace so I "remember" changes across sessions.
- If you prefer a different memory scope (`/memories/` user-level or `/memories/session/`) or a different filename, tell me and I'll move it.
