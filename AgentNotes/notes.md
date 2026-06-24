# AI Assistant Notes

## Purpose
This file records what the AI assistant learns and fixes while working in this repository. It is kept in a dedicated folder so the assistant can refer back to previous work.

## Current session
- Fixed department save/display issues and verified with tests.
- Fixed employee dropdown and save behavior, verified with tests.
- Fixed room feature JSON parsing and controller path issues, verified with room save API test.
- Fixed accommodation feature relative API paths and JSON parsing in `public/assets/js/accommodations.js`.
- Created accommodation test files in `Testing/` to validate API save and page render.
 - Implemented role-based permissions: `Admin`, `HR`, and `Viewer`.
   - Added login role selector and enforced role checks in `public/layouts/header.php` and `public/layouts/sidebar.php`.
   - Restricted user-management API to `Admin` and adjusted UI navigation per role.
   - Added `Testing/seed_users.php` to create/update `admin`, `hr`, and `viewer` accounts.

## Future behavior
- The assistant will continue to update this file with each significant fix or discovery.
- When the user changes code, the assistant will re-check linked notes and update this file accordingly.
- The assistant will always update agent notes immediately after making code changes or applying fixes.

## Arrivals Feature - CURRENT IMPLEMENTATION (Updated)

### What You See on arrivals.php
1. **"Record Arrival" Button** — Opens the modal for recording an arrival
2. **Employee Search Input** (in modal) — "Search or type employee name..." textbox with:
   - Search functionality for finding employees by code or name
   - Type to search, results dynamically filter
   - "Create new: [name]" option appears if employee doesn't exist
   - Shows employees with "(already arrived)" label when "Show all" is checked
3. **"Show all employees (mark already arrived)" Checkbox** — Toggles between:
   - Default: excludes employees who already arrived on selected date
   - Checked: shows all employees with already-arrived ones disabled/labeled
4. **Date Field** — Select the arrival date (defaults to today)
5. **Remarks Field** — Optional notes
6. **"Create new Employee" Modal** — Opens when user clicks "Create new: [name]"
   - Collects: Employee Code, Full Name (pre-filled), Gender, Department

### API Endpoints (Backend Ready)
- `GET /api/employees/index.php?exclude_arrived_date=YYYY-MM-DD` — employees without arrival on date
- `GET /api/employees/index.php?mark_arrived_date=YYYY-MM-DD` — all employees with `arrived_count`
- `POST /api/employees/index.php` — create new employee
- `POST /api/transactions/arrival` — record arrival (HR/Admin only)
- Duplicate check: rejects if arrival already exists for employee on that date

### Server-side Validation
- `Transaction::exists($employee_id, $type, $date)` — checks for duplicates
- Prevents duplicate arrivals even if UI filter fails
- Returns HTTP 400 with error message if duplicate detected

### Frontend JavaScript (transactions.js)
- `loadTransactionOptions(date)` — loads employees and renders dropdown
- `renderEmployeeList(searchTerm, employees, showAll)` — filters and displays employees
- "Create new" handler — opens create employee modal
- Employee creation — POSTs to API, refreshes dropdown
- Search input listener — filters as user types
- Click-outside listener — closes dropdown
- Date/checkbox change listeners — reload dropdown with appropriate filters


## Building input notes
- A building belongs to a specific accommodation.
- The user input for a building should be:
  - `accommodation_id`: selected from the parent accommodation dropdown
  - `building_name`: text name for the physical building (e.g. "Main Hall", "North Tower", "West Wing")
- Example building sample rows:
  - accommodation_id=1, building_name="Main Building"
  - accommodation_id=1, building_name="Annex Wing"
  - accommodation_id=2, building_name="Lakeview Tower"
- In the UI, add building should only appear after an accommodation is selected, and building names should be human-friendly.
- Floors and rooms are created under the selected building, so building names should describe the physical structure.

## 2026-06-20 — Room assignment & transfer updates

- Moved Transfer modal out of nested forms and switched to Bootstrap 5 modal API in `public/assets/js/room_assignments.js`.
- Implemented transfer modal preview and submit flow (`openTransfer`, `#transferForm` submit handler).
- Enforced one active room per employee in `app/models/RoomAssignment.php` via `hasActiveAssignment()`.
- `create()` now validates expected checkout and returns descriptive errors; controller validates arrival/departure dates in `app/controllers/RoomAssignmentController.php`.
- Transfer logic now checks target room availability and same-room selection; returns descriptive messages like "The selected room is already assigned." (controller returns JSON errors handled by SweetAlert).
- Updated UI labels and table headers in `public/room-assignments.php` to show `Check In` / `Check Out` and `Transferred To` columns.

These notes will be appended for future reference whenever code affecting room assignments or transfers changes.

## 2026-06-21 — Sidebar header & collapse improvements

- Date: 2026-06-21
- Files changed: public/layouts/header.php, public/assets/css/style.css, AgentNotes/notes.md
- Summary: Removed the duplicate topbar brand so the application header appears only inside the sidebar; improved the sidebar toggle to animate smoothly by using computed CSS variables and updated styles so collapsed state shows icons only.
- Tests run: Manual verification in browser (toggled sidebar open/closed, confirmed topbar shifts smoothly and sidebar shows icons-only when collapsed).
- Next steps: If desired, run cross-browser checks and adjust collapsed width for narrow screens; add an accessible tooltip for icon-only nav items on hover/focus.

## 2026-06-21 — Collapsed sidebar tooltip + chevron visibility fix

- Date: 2026-06-21
- Files changed: public/layouts/sidebar.php, public/assets/js/sidebar-utils.js, AgentNotes/notes.md
- Summary: Added CSS-based hover tooltips for collapsed sidebar items using a `data-label` attribute and kept dropdown chevrons visible when the sidebar is collapsed so expandable menus are still discoverable.
- Tests run: Manual verification in browser — hovering collapsed items shows their labels; parent items with nested menus display chevrons in collapsed state.
- Next steps: Consider adding keyboard focus styles and aria-expanded handling for screen readers.

## 2026-06-21 — Collapsed submenu overlay and click-fix

- Date: 2026-06-21
- Files changed: public/assets/js/sidebar-utils.js, public/layouts/sidebar.php, AgentNotes/notes.md
- Summary: Implemented a floating submenu overlay that appears when clicking expandable parent items while the sidebar is collapsed. This prevents the hidden inline collapses from being inaccessible and keeps chevrons visible.
- Tests run: Manual verification — clicking a parent when collapsed opens a floating submenu positioned beside the sidebar; clicking outside closes it.
- Next steps: Add keyboard navigation/ARIA roles for accessibility and ensure positioning works for items near the viewport bottom.

## 2026-06-21 — Make collapsed sidebar width 0

- Date: 2026-06-21
- Files changed: public/layouts/sidebar.php, public/assets/css/style.css, AgentNotes/notes.md
- Summary: Set `--sidebar-width-collapsed` to `0px` and ensured `.sidebar.collapsed` applies zero width/padding so the topbar and content use the full viewport when collapsed.
- Tests run: Manual verification — toggling sidebar collapses it to zero width and the topbar/content shift to full width.
- Next steps: Verify tooltip placement and floating submenu visibility when the sidebar is fully hidden; consider adding a visible affordance for reopening the sidebar on very small screens.

## 2026-06-21 — Remove remaining inline style blocks

- Date: 2026-06-21
- Files changed: public/layouts/header.php, public/layouts/sidebar.php, public/assets/css/style.css
- Summary: Moved remaining inline <style> blocks from `header.php` and `sidebar.php` into `public/assets/css/style.css` and removed the inline tags so styles are centralized.
- Tests run: Manual verification — pages still render with expected topbar and sidebar styles after the change.
- Next steps: Run a full visual check across pages to ensure no style regressions.
