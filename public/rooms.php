<?php include 'layouts/header.php'; ?>
<?php include 'layouts/sidebar.php'; ?>


<div class="content-wrapper">

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-3 mb-2">
        <div class="flex-grow-1">
            <h2 class="ams-page-title">Room Management</h2>
            <p class="ams-page-subtitle">Manage and organize company rooms.</p>
        </div>
        <div class="d-flex align-items-start">
            <button class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#roomModal">
                Add Room
            </button>
        </div>
    </div>
    <div class="alert alert-info py-3 px-3 mb-3" role="alert" style="font-size:0.95rem;">
        <strong>Important:</strong> Add accommodation first, then add a building to that accommodation, and then add a floor before creating a room. Use the <a href="accommodations.php" class="alert-link text-decoration-underline" style="font-weight:600;">Accommodations</a> page to begin.
    </div>

    <!-- Room Status Summary -->
    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card p-3 h-100">
                <p class="text-uppercase text-muted small mb-2">Total Rooms</p>
                <h3 id="roomSummaryTotal" class="mb-0">—</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 h-100">
                <p class="text-uppercase text-muted small mb-2">Occupied</p>
                <h3 id="roomSummaryOccupied" class="mb-0">—</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 h-100">
                <p class="text-uppercase text-muted small mb-2">Available</p>
                <h3 id="roomSummaryAvailable" class="mb-0">—</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 h-100">
                <p class="text-uppercase text-muted small mb-2">Maintenance</p>
                <h3 id="roomSummaryMaintenance" class="mb-0">—</h3>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Search Room</label>
                    <input type="text" class="form-control" id="roomSearchInput" placeholder="Room, accommodation, floor, status">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Accommodation</label>
                    <select class="form-select" id="filterAccommodation" onchange="loadBuildingsByAccommodation()">
                        <option value="">All Accommodations</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Building</label>
                    <select class="form-select" id="filterBuilding" onchange="loadFloorsByBuilding()">
                        <option value="">All Buildings</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Floor</label>
                    <select class="form-select" id="filterFloor" onchange="loadRoomsByFloor()">
                        <option value="">All Floors</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="button" class="btn btn-outline-secondary w-100" onclick="resetRoomFilters()">Reset</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Rooms Table -->
    <div class="ams-card" style="padding:0; overflow:hidden;">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; padding:12px 16px; border-bottom:1px solid #e5e7eb;">
            <div id="selectedRoomsText" style="font-size:13px; color:#434653;">0 selected</div>
            <button
                type="button"
                class="btn btn-danger btn-sm"
                id="bulkDeleteRoomsBtn"
                onclick="deleteSelectedRooms()"
                disabled>
                Delete Selected
            </button>
        </div>
        <div style="overflow-x:auto;">
            <table class="table ams-table table-bordered" data-export-title="Rooms Data">
                <thead>
                    <tr>
                        <th style="width:44px; text-align:center;">
                            <input type="checkbox" id="selectAllRooms" aria-label="Select all rooms">
                        </th>
                        <th>Room No</th>
                        <th>Accommodation</th>
                        <th>Floor</th>
                        <th>Type</th>
                        <th>Capacity</th>
                        <th>Occupancy</th>
                        <th>Status</th>
                        <th>Employee Name(s)</th>
                        <th>Gender</th>
                        <th width="180">Action</th>
                    </tr>
                </thead>
                <tbody id="roomTable"></tbody>
            </table>
        </div>
    </div>

</div>

<!-- Room Modal -->
<div class="modal fade" id="roomModal" tabindex="-1" aria-labelledby="roomModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="roomModalLabel">Add Room</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="roomForm">
                <input type="hidden" id="roomId" name="id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Accommodation</label>
                        <select class="form-select" id="accommodation_id" name="accommodation_id" required onchange="loadBuildingsForModal()">
                            <option value="">Select accommodation</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Building</label>
                        <select class="form-select" id="building_id" name="building_id" required onchange="loadFloorsForModal()">
                            <option value="">Select building</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Floor</label>
                        <select class="form-select" id="floor_id" name="floor_id" required>
                            <option value="">Select floor</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Room Number</label>
                        <input type="text" class="form-control" id="room_no" name="room_no">
                    </div>

                    <div class="border rounded p-3 mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label mb-0">Generate Room Range</label>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="generateRoomRangePreview()">Generate Rooms</button>
                        </div>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label small">Start Room No.</label>
                                <input type="text" class="form-control form-control-sm" id="start_room_no" name="start_room_no" placeholder="C1">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">End Room No.</label>
                                <input type="text" class="form-control form-control-sm" id="end_room_no" name="end_room_no" placeholder="C5">
                            </div>
                        </div>
                        <input type="hidden" id="generate_range" name="generate_range" value="0">
                        <div id="roomRangePreview" class="small text-muted mt-2"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Room Type</label>
                        <select class="form-select" id="room_type" name="room_type" required>
                            <option value="">Select type</option>
                            <option value="Single">Single</option>
                            <option value="Double">Double</option>
                            <option value="Triple">Triple</option>
                            <option value="Quadruple">Quadruple</option>
                            <option value="Suite">Suite</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Capacity</label>
                        <input type="number" class="form-control" id="capacity" name="capacity" required min="1">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="Available">Available</option>
                            <option value="Occupied">Occupied</option>
                            <option value="Reserved">Reserved</option>
                            <option value="Maintenance">Maintenance</option>
                        </select>
                    </div>
                    <div class="mb-3" id="reservedEmployeeGroup" style="display:none;">
                        <label class="form-label">Reserved By</label>
                        <select class="form-select" id="reserved_by_employee_id" name="reserved_by_employee_id">
                            <option value="">Select employee</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Gender Restriction</label>
                        <select class="form-select" id="gender_restriction" name="gender_restriction">
                            <option value="">None</option>
                            <option value="Male">Male Only</option>
                            <option value="Female">Female Only</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Remarks</label>
                        <textarea class="form-control" id="remarks" name="remarks" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Room</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="assets/js/rooms.js?v=<?= filemtime(__DIR__ . '/assets/js/rooms.js') ?>"></script>

<?php include 'layouts/footer.php'; ?>
