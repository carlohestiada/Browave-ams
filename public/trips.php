<?php include 'layouts/header.php'; ?>
<?php include 'layouts/sidebar.php'; ?>

<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<div class="content-wrapper">
    <div class="dashboard-header">
        <div>
            <h2 class="dashboard-title">Trips</h2>
            <p class="dashboard-subtitle">Manage employee arrival and departure travel plans.</p>
        </div>
        <?php if (in_array(currentUserRole(), ['Admin', 'HR'], true)): ?>
            <button type="button" class="btn btn-primary" id="createTripButton">
                <i class="bi bi-plus-lg" aria-hidden="true"></i> Create Trip
            </button>
        <?php endif; ?>
    </div>

    <div class="ams-card p-4 mb-4">
        <form id="tripFilterForm" class="row g-3 align-items-end">
            <div class="col-lg-2 col-md-6">
                <label class="ams-label" for="tripFilterDepartment">Department</label>
                <select id="tripFilterDepartment" class="ams-input"><option value="">All departments</option></select>
            </div>
            <div class="col-lg-2 col-md-6">
                <label class="ams-label" for="tripFilterEmployee">Employee</label>
                <select id="tripFilterEmployee" class="ams-input"><option value="">All employees</option></select>
            </div>
            <div class="col-lg-2 col-md-6">
                <label class="ams-label" for="tripFilterAssignment">Trip Assignment</label>
                <select id="tripFilterAssignment" class="ams-input">
                    <option value="">All trips</option>
                    <option value="none">Employees without a trip</option>
                </select>
            </div>
            <div class="col-lg-2 col-md-6">
                <label class="ams-label" for="tripFilterType">Trip Type</label>
                <select id="tripFilterType" class="ams-input">
                    <option value="">All trip types</option>
                    <option value="NORMAL_TRIP">Normal trip</option>
                    <option value="ROUND_TRIP">Round trip</option>
                </select>
            </div>
            <div class="col-lg-1 col-md-6">
                <label class="ams-label" for="tripFilterStatus">Status</label>
                <select id="tripFilterStatus" class="ams-input">
                    <option value="">All statuses</option>
                    <option value="PLANNED">Planned</option>
                    <option value="ACTIVE">Active</option>
                    <option value="COMPLETED">Completed</option>
                    <option value="CANCELLED">Cancelled</option>
                </select>
            </div>
            <div class="col-lg-1 col-md-6">
                <label class="ams-label" for="tripFilterFrom">From</label>
                <input id="tripFilterFrom" type="date" class="ams-input">
            </div>
            <div class="col-lg-1 col-md-6">
                <label class="ams-label" for="tripFilterTo">To</label>
                <input id="tripFilterTo" type="date" class="ams-input">
            </div>
            <div class="col-lg-1 col-md-6 d-flex gap-1">
                <button type="submit" class="btn btn-primary" title="Search"><i class="bi bi-search" aria-hidden="true"></i><span class="visually-hidden">Search</span></button>
                <button type="button" class="btn btn-outline-secondary px-1" id="resetTripFilters" title="Reset"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i><span class="visually-hidden">Reset</span></button>
            </div>
        </form>
    </div>

    <div class="ams-card dashboard-card-panel">
        <div class="dashboard-card-header">
            <div class="dashboard-card-title"><i class="bi bi-table"></i> Trips Schedule</div>
            <div id="tripCount" class="text-muted small">0 trips found</div>
        </div>
        <div class="dashboard-card-body dashboard-card-table-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <ul class="nav nav-tabs" id="scheduleViewTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link schedule-view-tab trip-view-tab active" id="activeTripsTab" type="button" data-view="active" role="tab" aria-controls="tripSchedulePane" aria-selected="true">Active</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link schedule-view-tab trip-view-tab" id="completedTripsTab" type="button" data-view="completed" role="tab" aria-controls="tripSchedulePane" aria-selected="false">Completed</button>
                    </li>
                </ul>
                <div id="tripViewSummary" class="text-muted small">Showing active trips</div>
            </div>
            <section id="tripSchedulePane" role="tabpanel" aria-labelledby="activeTripsTab">
                <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Employee ID</th>
                            <th>Employee Name</th>
                            <th>Department</th>
                            <th>Arrival</th>
                            <th>Departure</th>
                            <th>Arrival Airport</th>
                            <th>Departure Airport</th>
                            <th>Room No.</th>
                            <th>Trip Type</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="tripsTableBody"><tr><td colspan="11" class="text-center text-muted py-4">Loading trips...</td></tr></tbody>
                </table>
            </div>
            </section>
        </div>
    </div>
</div>

<div class="modal fade" id="tripFormModal" tabindex="-1" aria-labelledby="tripFormModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="tripFormModalLabel">Create Trip</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <form id="tripForm">
                <div class="modal-body">
                    <input type="hidden" id="tripEditId">
                    <div id="tripFormError" class="alert alert-danger d-none"></div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6"><label class="form-label" for="tripEmployee">Employee</label><select id="tripEmployee" class="form-select" required><option value="">Select employee</option></select></div>
                        <div class="col-md-3"><label class="form-label" for="tripType">Trip Type</label><select id="tripType" class="form-select" required><option value="NORMAL_TRIP">Normal trip</option><option value="ROUND_TRIP">Round trip</option></select></div>
                        <div class="col-md-3"><label class="form-label" for="tripStatus">Status</label><select id="tripStatus" class="form-select" disabled><option value="PLANNED">Planned</option><option value="ACTIVE">Active</option><option value="COMPLETED">Completed</option><option value="CANCELLED">Cancelled</option></select><div class="form-text">Status updates automatically from the schedule.</div></div>
                    </div>
                    <div id="employeePreview" class="alert alert-light border d-none mb-4"></div>
                    <div class="form-text mb-2">Enter an arrival, a departure, or both. You can add the missing one later by editing the trip.</div>
                    <div id="tripLegsForm" class="row g-3"></div>
                    <div class="mt-3"><label class="form-label" for="tripRemarks">Remarks</label><textarea id="tripRemarks" class="form-control" rows="2"></textarea></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary" id="saveTripButton">Save Trip</button></div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="tripDetailsModal" data-can-manage-trips="<?= in_array(currentUserRole(), ['Admin', 'HR'], true) ? 'true' : 'false' ?>" tabindex="-1" aria-labelledby="tripDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tripDetailsModalLabel">Trip Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                </button>
            </div>
            <div class="modal-body" id="tripDetailsBody">
                <div class="text-center text-muted py-4">Loading...</div>
            </div>
            <div class="modal-footer" id="tripDetailsFooter"></div>
        </div>
    </div>
</div>

<script src="assets/js/employee-utils.js?v=<?= filemtime(__DIR__ . '/assets/js/employee-utils.js') ?>"></script>
<script src="assets/js/trips.js?v=<?= filemtime(__DIR__ . '/assets/js/trips.js') ?>"></script>
<?php include 'layouts/footer.php'; ?>
