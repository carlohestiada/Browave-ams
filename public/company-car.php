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
            <button type="button" class="btn btn-outline-primary" id="bulkAssignmentBtn">
                <i class="bi bi-people-fill"></i> Bulk Assignment
            </button>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#companyCarModal" id="newTransportationBtn">
                <i class="bi bi-plus-lg"></i> New Transportation
            </button>
        </div>
    </div>

    <div class="dashboard-kpi-grid">
        <a href="company-car.php" class="kpi-card kpi-card--link">
            <p class="kpi-label"><i class="bi bi-car-front-fill"></i> Company Car Request</p>
            <div class="kpi-value-row">
                <p class="kpi-value kpi-value--primary" id="kpiCompanyCarRequest">0</p>
                <span class="kpi-badge badge-default">Live</span>
            </div>
        </a>
        <a href="company-car.php" class="kpi-card kpi-card--link">
            <p class="kpi-label"><i class="bi bi-calendar-check-fill"></i> Scheduled Today</p>
            <div class="kpi-value-row">
                <p class="kpi-value kpi-value--success" id="kpi-companycar-scheduled">-</p>
                <span class="kpi-badge badge-active">Today</span>
            </div>
        </a>
        <a href="company-car.php" class="kpi-card kpi-card--link">
            <p class="kpi-label"><i class="bi bi-truck"></i> Available Vehicles</p>
            <div class="kpi-value-row">
                <p class="kpi-value kpi-value--neutral" id="kpiAvailableVehicles">0</p>
                <span class="kpi-badge badge-default">Ready</span>
            </div>
        </a>
        <a href="company-car.php" class="kpi-card kpi-card--link">
            <p class="kpi-label"><i class="bi bi-person-badge-fill"></i> Available Driver</p>
            <div class="kpi-value-row">
                <p class="kpi-value kpi-value--success" id="kpiAvailableDrivers">0</p>
                <span class="kpi-badge badge-active">Ready</span>
            </div>
        </a>
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
                <label class="ams-label" for="filterLegType">Leg Type</label>
                <select id="filterLegType" class="ams-input">
                    <option value="">All leg types</option>
                    <option value="ARRIVAL">Arrival</option>
                    <option value="DEPARTURE">Departure</option>
                </select>
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
               <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                   <ul class="nav nav-tabs" id="scheduleViewTabs">
                       <li class="nav-item">
                           <button class="nav-link schedule-view-tab active" type="button" data-view="active">Active</button>
                       </li>
                       <li class="nav-item">
                           <button class="nav-link schedule-view-tab" type="button" data-view="archive">Archive</button>
                       </li>
                   </ul>
                   <div id="scheduleViewSummary" class="text-muted small">Showing active requests</div>
               </div>
               <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; padding:0 0 12px;">
                   <div id="selectedTransportationText" class="text-muted small">0 selected</div>
                   <button type="button" class="btn btn-danger btn-sm" id="bulkDeleteTransportationBtn" onclick="deleteSelectedTransportation()" disabled>
                       Delete Selected
                   </button>
               </div>
               <div class="table-responsive">
                   <table class="table table-hover align-middle">
                       <thead class="table-light">
                           <tr>
                               <th style="width:44px; text-align:center;">
                                   <input type="checkbox" id="selectAllTransportation" aria-label="Select all transportation requests">
                               </th>
                               <th>Employee</th>
                               <th>Department</th>
                               <th>Trip / Leg</th>
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
                           <tr><td colspan="12" class="text-center text-muted">Loading requests...</td></tr>
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
                    <input type="hidden" id="companyCar_trip_leg_id" name="trip_leg_id">

                    <!-- Phase 4: Trip Context Section (read-only) -->
                    <div id="tripContextSection" class="alert alert-info d-none mb-3">
                        <strong>Trip Context</strong>
                        <div class="row g-2 mt-2 text-sm">
                            <div class="col-md-6">
                                <small><strong>Trip:</strong> <span id="tripContextTripId">—</span></small>
                            </div>
                            <div class="col-md-6">
                                <small><strong>Employee:</strong> <span id="tripContextEmployee">—</span></small>
                            </div>
                            <div class="col-md-6">
                                <small><strong>Leg Type:</strong> <span id="tripContextLegType">—</span></small>
                            </div>
                            <div class="col-md-6">
                                <small><strong>Travel Date:</strong> <span id="tripContextDate">—</span></small>
                            </div>
                            <div class="col-md-12">
                                <small><strong>Route:</strong> <span id="tripContextRoute">—</span></small>
                            </div>
                        </div>
                    </div>

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
                                <div class="d-flex">
                                    <button type="button" class="btn btn-outline-secondary me-1" id="addDriverBtn" title="Add driver">
                                        <i class="bi bi-plus-lg"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" id="manageDriverBtn" title="Manage drivers">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="ams-label" for="companyCar_vehicle_id">Vehicle</label>
                            <div class="d-flex gap-2">
                                <select id="companyCar_vehicle_id" name="vehicle_id" class="ams-input flex-grow-1"></select>
                                <div class="d-flex">
                                    <button type="button" class="btn btn-outline-secondary me-1" id="addVehicleBtn" title="Add vehicle">
                                        <i class="bi bi-plus-lg"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" id="manageVehicleBtn" title="Manage vehicles">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </div>
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

<div class="modal fade" id="bulkCompanyCarModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Bulk Assignment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="bulkCompanyCarForm">
                    <div class="mb-3">
                        <label class="ams-label">Employees</label>
                        <div id="bulkEmployeeIds" class="border rounded p-2" style="max-height: 260px; overflow:auto;"></div>
                        <div class="form-text">Select multiple employees for one shared trip.</div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="ams-label" for="bulkTransportationType">Transportation Type</label>
                            <select id="bulkTransportationType" name="transportation_type" class="ams-input">
                                <option value="Company Car">Company Car</option>
                                <option value="Airport Transfer">Airport Transfer</option>
                                <option value="Shuttle Service">Shuttle Service</option>
                                <option value="Private Hire">Private Hire</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="ams-label" for="bulkStatus">Status</label>
                            <select id="bulkStatus" name="status" class="ams-input">
                                <option value="Pending">Pending</option>
                                <option value="Scheduled">Scheduled</option>
                                <option value="Picked Up">Picked Up</option>
                                <option value="Completed">Completed</option>
                                <option value="Cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="ams-label" for="bulkDriverId">Driver</label>
                            <select id="bulkDriverId" name="driver_id" class="ams-input"></select>
                        </div>
                        <div class="col-md-6">
                            <label class="ams-label" for="bulkVehicleId">Vehicle</label>
                            <select id="bulkVehicleId" name="vehicle_id" class="ams-input"></select>
                        </div>
                        <div class="col-md-6">
                            <label class="ams-label" for="bulkPickupDate">Pickup Date</label>
                            <input id="bulkPickupDate" name="pickup_date" type="date" class="ams-input" required>
                        </div>
                        <div class="col-md-6">
                            <label class="ams-label" for="bulkPickupTime">Pickup Time</label>
                            <input id="bulkPickupTime" name="pickup_time" type="time" class="ams-input" required>
                        </div>
                        <div class="col-12">
                            <label class="ams-label" for="bulkPickupLocation">Pickup Location</label>
                            <input id="bulkPickupLocation" name="pickup_location" type="text" class="ams-input" placeholder="Airport / Station / Terminal" required>
                        </div>
                        <div class="col-12">
                            <label class="ams-label" for="bulkRemarks">Remarks</label>
                            <textarea id="bulkRemarks" name="remarks" class="ams-input" rows="3" placeholder="Add notes for the shared trip"></textarea>
                        </div>
                    </div>

                    <div class="mt-4 text-end">
                        <button type="submit" class="btn btn-primary">Save Bulk Assignment</button>
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

<!-- Manage Drivers Modal -->
<div class="modal fade" id="driverManageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Manage Drivers</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="driverManageList">Loading drivers...</div>
            </div>
        </div>
    </div>
</div>

<!-- Manage Vehicles Modal -->
<div class="modal fade" id="vehicleManageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Manage Vehicles</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="vehicleManageList">Loading vehicles...</div>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/company-car.js?v=<?= filemtime(__DIR__ . '/assets/js/company-car.js') ?>"></script>
<?php include 'layouts/footer.php'; ?>
