<?php include 'layouts/header.php'; ?>
<?php include 'layouts/sidebar.php'; ?>

<div class="content-wrapper">

    <div class="d-flex justify-content-between mb-3">
        <div>
            <h2 style="font-size:26px; font-weight:700; color:#003686; margin:0; letter-spacing:-0.02em;">Departures</h2>
            <p style="font-size:13px; color:#434653; margin:4px 0 0;">Manage and organize company departures.</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#departureModal">Record Departure</button>
    </div>

    <div class="ams-card" style="padding:16px; margin-bottom:16px;">
        <div style="display:flex; flex-wrap:wrap; gap:16px; align-items:flex-end;">
            <div style="flex:1; min-width:220px;">
                <label class="ams-label">Search Departure</label>
                <input
                    id="departureSearchInput"
                    type="text"
                    class="ams-input"
                    placeholder="Date, employee, department, remarks">
            </div>
            <button type="button" onclick="resetDepartureFilters()" class="btn-ams-ghost" style="height:40px;">
                Reset
            </button>
        </div>
    </div>

    <div class="ams-card" style="padding:0; overflow:hidden;">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; padding:12px 16px; border-bottom:1px solid #e5e7eb;">
            <div id="selectedDeparturesText" style="font-size:13px; color:#434653;">0 selected</div>
            <button
                type="button"
                class="btn btn-danger btn-sm"
                id="bulkDeleteDeparturesBtn"
                onclick="deleteSelectedDepartures()"
                disabled>
                Delete Selected
            </button>
        </div>
        <div style="overflow-x:auto;">
            <table class="table table-bordered" data-export-title="Departures Data">
                <thead>
                    <tr>
                        <th style="width:44px; text-align:center;">
                            <input type="checkbox" id="selectAllDepartures" aria-label="Select all departures">
                        </th>
                        <th>Date</th>
                        <th>Employee</th>
                        <th>Department</th>
                        <th>Remarks</th>
                        <th style="width:180px;">Actions</th>
                    </tr>
                </thead>
                <tbody id="departureTable"></tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="departureModal" tabindex="-1" aria-labelledby="departureModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="departureModalLabel">Record Departure</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="departureForm">
                <input type="hidden" id="departure_transaction_id" name="transaction_id" value="">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Employee</label>
                        <div class="search-input-wrapper">
                            <input type="text" id="departure_employee_search" class="form-control" placeholder="Search or type employee name..." autocomplete="off">
                            <input type="hidden" id="departure_employee_id" name="employee_id" required>
                            <div id="departure_employee_list" class="dropdown-list"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="date" id="departure_transaction_date" class="form-control" name="transaction_date" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Remarks</label>
                        <textarea class="form-control" id="departure_remarks" name="remarks"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Departure</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="assets/js/transactions.js?v=<?= filemtime(__DIR__ . '/assets/js/transactions.js') ?>"></script>
<?php include 'layouts/footer.php'; ?>
