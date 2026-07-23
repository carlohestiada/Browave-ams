<?php include 'layouts/header.php'; ?>
<?php include 'layouts/sidebar.php'; ?>

<div class="content-wrapper">
    <div class="dashboard-header">
        <div>
            <h2 class="dashboard-title">Company Car Departure</h2>
            <p class="dashboard-subtitle">Transportation Management for Employee Departures</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button type="button" id="exportScheduleBtn" class="btn btn-outline-secondary">
                <i class="bi bi-file-earmark-arrow-down"></i> Export
            </button>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#companyCarModal" id="newTransportationBtn">
                <i class="bi bi-plus-lg"></i> New Transportation
            </button>
        </div>
    </div>

    <div class="dashboard-kpi-grid">
        <div class="ams-card p-4 kpi-card">
            <div>
                <div class="text-uppercase text-muted small">Today's Requests</div>
                <div id="kpiTodayRequests" class="h2 mb-0">0</div>
            </div>
        </div>
        <div class="ams-card p-4 kpi-card">
            <div>
                <div class="text-uppercase text-muted small">Scheduled Today</div>
                <div id="kpiScheduledToday" class="h2 mb-0">0</div>
            </div>
        </div>
        <div class="ams-card p-4 kpi-card">
            <div>
                <div class="text-uppercase text-muted small">Completed</div>
                <div id="kpiCompleted" class="h2 mb-0">0</div>
            </div>
        </div>
        <div class="ams-card p-4 kpi-card">
            <div>
                <div class="text-uppercase text-muted small">Pending Assignment</div>
                <div id="kpiPending" class="h2 mb-0">0</div>
            </div>
        </div>
        <div class="ams-card p-4 kpi-card">
            <div>
                <div class="text-uppercase text-muted small">Available Vehicles</div>
                <div id="kpiAvailableVehicles" class="h2 mb-0">0</div>
            </div>
        </div>
    </div>

    <div class="ams-card p-4 mb-4">
        <div class="row g-3 align-items-end">
            <div class="col-lg-4">
                <label class="ams-label" for="filterEmployeeSearch">Employee</label>
                <div class="search-input-wrapper">
                    <input id="filterEmployeeSearch" type="text" class="ams-input" placeholder="Search employee">
                    <input type="hidden" id="filterEmployeeId">
                    <div id="filterEmployeeList" class="dropdown-list"></div>
                </div>
            </div>
            <div class="col-lg-2">
                <label class="ams-label" for="filterPickupDate">Departure Date</label>
                <input id="filterPickupDate" type="date" class="ams-input">
            </div>
            <div class="col-lg-2">
                <label class="ams-label" for="filterTransportationType">Transportation Type</label>
                <select id="filterTransportationType" class="ams-input">
                    <option value="">All types</option>
                    <option value="Company Car">Company Car</option>
                    <option value="Airport Transfer">Airport Transfer</option>
                    <option value="Shuttle Service">Shuttle Service</option>
                    <option value="Private Hire">Private Hire</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div class="col-lg-2">
                <label class="ams-label" for="filterVehicle">Vehicle</label>
                <select id="filterVehicle" class="ams-input"></select>
            </div>
            <div class="col-lg-2">
                <label class="ams-label" for="filterDriver">Driver</label>
                <select id="filterDriver" class="ams-input"></select>
            </div>
            <div class="col-lg-2">
                <label class="ams-label" for="filterStatus">Status</label>
                <select id="filterStatus" class="ams-input">
                    <option value="">All statuses</option>
                    <option value="Pending">Pending</option>
                    <option value="Scheduled">Scheduled</option>
                    <option value="Picked Up">Picked Up</option>
                    <option value="Completed">Completed</option>
                    <option value="Cancelled">Cancelled</option>
                </select>
            </div>
            <div class="col-12 d-flex gap-2">
                <button type="button" id="applyFilters" class="btn btn-primary">Apply</button>
                <button type="button" id="resetFilters" class="btn btn-outline-secondary">Reset</button>
            </div>
        </div>
    </div>

    <div class="dashboard-grid-charts">
        <div class="ams-card dashboard-card-panel">
            <div class="dashboard-card-header">
                <div class="dashboard-card-title"><i class="bi bi-table"></i> Transportation Schedule</div>
                <div id="scheduleCount" class="text-muted small">0 trips found</div>
            </div>
            <div class="dashboard-card-body dashboard-card-table-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Employee</th>
                                <th>Department</th>
                                <th>Departure Date</th>
                                <th>Pickup Time</th>
                                <th>Transportation</th>
                                <th>Driver</th>
                                <th>Vehicle</th>
                                <th>Pickup Location</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="companyCarTableBody">
                            <tr><td colspan="10" class="text-center text-muted">Loading requests...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center p-3 border-top">
                <div id="tableSummary" class="text-muted small">Showing 0 of 0 records</div>
                <nav aria-label="Schedule pagination">
                    <ul class="pagination mb-0" id="schedulePagination"></ul>
                </nav>
            </div>
        </div>

        <div class="ams-card dashboard-card-panel">
            <div class="dashboard-card-header">
                <div class="dashboard-card-title"><i class="bi bi-clock-history"></i> Today's Pickup Timeline</div>
            </div>
            <div class="dashboard-card-body">
                <div id="pickupTimeline"></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="companyCarModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="companyCarModalLabel">Assign Transportation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="companyCarForm">
                    <input type="hidden" id="companyCar_id" name="id">
                    <input type="hidden" id="companyCar_employee_id" name="employee_id">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="ams-label" for="companyCar_employee_search">Employee</label>
                            <div class="search-input-wrapper">
                                <input id="companyCar_employee_search" type="text" class="ams-input" placeholder="Search employee for assignment">
                                <div id="companyCar_employee_list" class="dropdown-list"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="ams-label" for="companyCar_department">Department</label>
                            <input id="companyCar_department" type="text" class="ams-input" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="ams-label" for="companyCar_chinese_name">Chinese Name</label>
                            <input id="companyCar_chinese_name" type="text" class="ams-input" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="ams-label" for="companyCar_gender">Gender</label>
                            <input id="companyCar_gender" type="text" class="ams-input" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="ams-label" for="companyCar_accommodation_room">Accommodation / Room</label>
                            <input id="companyCar_accommodation_room" type="text" class="ams-input" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="ams-label" for="companyCar_arrival_date">Arrival Date</label>
                            <input id="companyCar_arrival_date" type="date" class="ams-input" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="ams-label" for="companyCar_departure_date">Departure Date</label>
                            <input id="companyCar_departure_date" type="date" class="ams-input" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="ams-label" for="companyCar_transportation_type">Transportation Type</label>
                            <select id="companyCar_transportation_type" name="transportation_type" class="ams-input">
                                <option value="Company Car">Company Car</option>
                                <option value="Airport Transfer">Airport Transfer</option>
                                <option value="Shuttle Service">Shuttle Service</option>
                                <option value="Private Hire">Private Hire</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="ams-label" for="companyCar_driver_id">Driver</label>
                            <div class="d-flex gap-2">
                                <select id="companyCar_driver_id" name="driver_id" class="ams-input flex-grow-1"></select>
                                <button type="button" class="btn btn-outline-secondary" id="addDriverBtn" title="Add driver">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="ams-label" for="companyCar_vehicle_id">Vehicle</label>
                            <div class="d-flex gap-2">
                                <select id="companyCar_vehicle_id" name="vehicle_id" class="ams-input flex-grow-1"></select>
                                <button type="button" class="btn btn-outline-secondary" id="addVehicleBtn" title="Add vehicle">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="ams-label" for="companyCar_pickup_date">Pickup Date</label>
                            <input id="companyCar_pickup_date" name="pickup_date" type="date" class="ams-input" required>
                        </div>
                        <div class="col-md-4">
                            <label class="ams-label" for="companyCar_pickup_time">Pickup Time</label>
                            <input id="companyCar_pickup_time" name="pickup_time" type="time" class="ams-input" required>
                        </div>
                        <div class="col-md-4">
                            <label class="ams-label" for="companyCar_pickup_location">Pickup Location</label>
                            <input id="companyCar_pickup_location" name="pickup_location" type="text" class="ams-input" required>
                        </div>
                        <div class="col-md-4">
                            <label class="ams-label" for="companyCar_status">Status</label>
                            <select id="companyCar_status" name="status" class="ams-input" required>
                                <option value="Pending">Pending</option>
                                <option value="Scheduled">Scheduled</option>
                                <option value="Picked Up">Picked Up</option>
                                <option value="Completed">Completed</option>
                                <option value="Cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="ams-label" for="companyCar_remarks">Remarks</label>
                            <textarea id="companyCar_remarks" name="remarks" class="ams-input" rows="3" placeholder="Add notes or special instructions"></textarea>
                        </div>
                    </div>

                    <div class="mt-4 text-end">
                        <button type="submit" id="companyCarSaveButton" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="driverModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Driver</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="driverForm">
                    <div class="mb-3">
                        <label class="ams-label" for="driver_name">Driver Name</label>
                        <input id="driver_name" name="driver_name" type="text" class="ams-input" required>
                    </div>
                    <div class="mb-3">
                        <label class="ams-label" for="driver_phone">Phone</label>
                        <input id="driver_phone" name="phone" type="text" class="ams-input">
                    </div>
                    <div class="mb-3">
                        <label class="ams-label" for="driver_status">Status</label>
                        <select id="driver_status" name="status" class="ams-input">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">Save Driver</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="vehicleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Vehicle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="vehicleForm">
                    <div class="mb-3">
                        <label class="ams-label" for="vehicle_name">Vehicle Name</label>
                        <input id="vehicle_name" name="vehicle_name" type="text" class="ams-input" required>
                    </div>
                    <div class="mb-3">
                        <label class="ams-label" for="vehicle_license_plate">License Plate</label>
                        <input id="vehicle_license_plate" name="license_plate" type="text" class="ams-input">
                    </div>
                    <div class="mb-3">
                        <label class="ams-label" for="vehicle_status">Status</label>
                        <select id="vehicle_status" name="status" class="ams-input">
                            <option value="Available">Available</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">Save Vehicle</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/company-car.js?v=<?= filemtime(__DIR__ . '/assets/js/company-car.js') ?>"></script>
<?php include 'layouts/footer.php'; ?>
