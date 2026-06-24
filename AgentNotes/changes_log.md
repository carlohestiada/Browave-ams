Change log (repo-scoped) — updated 2026-06-20

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
