<?php include 'layouts/header.php'; ?>
<?php include 'layouts/sidebar.php'; ?>

<div class="content-wrapper">

    <div class="d-flex justify-content-between mb-2">
        <div>
            <h2 style="font-size:26px; font-weight:700; color:#003686; margin:0; letter-spacing:-0.02em;">Arrivals</h2>
            <p style="font-size:13px; color:#434653; margin:4px 0 0;">Manage and organize company arrivals.</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#arrivalModal">Record Arrival</button>
    </div>
    <div class="alert alert-info py-3 px-3 mb-3" role="alert" style="font-size:0.95rem;">
        <strong>Important:</strong> If an employee is not showing in the dropdown, they need to be added to the <a href="employees.php" style="text-decoration: underline; font-weight: 600; color: inherit;">Employees</a> page first. Employees with existing arrivals on the selected date will not appear in the list.
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form id="arrivalFilterForm" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Date From</label>
                    <input type="date" id="filterDateFrom" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date To</label>
                    <input type="date" id="filterDateTo" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Department</label>
                    <select id="filterDepartment" class="form-select">
                        <option value="">All departments</option>
                    </select>
                </div>
                <div class="col-md-2 text-end">
                    <button type="button" class="btn btn-primary w-100" id="applyArrivalFilters">Filter</button>
                    <button type="button" class="btn btn-secondary w-100 mt-2" id="resetArrivalFilters">Reset</button>
                </div>
            </form>
        </div>
    </div>

    <div class="ams-card" style="padding:0; overflow:hidden;">
        <div style="overflow-x:auto;">
            <table class="table table-bordered" data-export-title="Arrivals Data">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Employee</th>
                        <th>Department</th>
                        <th>Remarks</th>
                        <th style="width:180px;">Actions</th>
                    </tr>
                </thead>
                <tbody id="arrivalTable"></tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="arrivalModal" tabindex="-1" aria-labelledby="arrivalModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="arrivalModalLabel">Record Arrival</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="arrivalForm">
                <input type="hidden" id="arrival_transaction_id" name="transaction_id" value="">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Employee</label>
                        <div class="search-input-wrapper">
                            <input type="text" id="arrival_employee_search" class="form-control" placeholder="Search or type employee name..." autocomplete="off">
                            <input type="hidden" id="arrival_employee_id" name="employee_id" required>
                            <div id="arrival_employee_list" class="dropdown-list"></div>
                        </div>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" value="1" id="arrival_show_all">
                            <label class="form-check-label" for="arrival_show_all">Show all employees (mark already arrived)</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="date" id="arrival_transaction_date" class="form-control" name="transaction_date" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Remarks</label>
                        <textarea class="form-control" id="arrival_remarks" name="remarks"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Arrival</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="createEmployeeModal" tabindex="-1" aria-labelledby="createEmployeeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createEmployeeModalLabel">Create New Employee</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createEmployeeForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Employee Code</label>
                        <input type="text" class="form-control" id="new_employee_code" name="employee_code" placeholder="e.g. EMP-001" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="new_employee_name" name="full_name" placeholder="Full name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Gender</label>
                        <select class="form-select" id="new_employee_gender" name="gender" required>
                            <option value="">Select gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Department</label>
                        <select class="form-select" id="new_employee_department" name="department_id" required></select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Employee</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="assets/js/transactions.js?v=<?= filemtime(__DIR__ . '/assets/js/transactions.js') ?>"></script>
<?php include 'layouts/footer.php'; ?>
