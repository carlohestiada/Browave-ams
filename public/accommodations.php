<?php include 'layouts/header.php'; ?>
<?php include 'layouts/sidebar.php'; ?>


<div class="content-wrapper">

    <div class="d-flex justify-content-between mb-2">
        <div>
            <h2 class="ams-page-title">Accommodation Management</h2>
            <p class="ams-page-subtitle">Manage and organize company accommodations.</p>
        </div>

        <button
            class="btn btn-primary"
            data-bs-toggle="modal"
            data-bs-target="#accommodationModal">
            Add Accommodation
        </button>
    </div>
    <div class="alert alert-info py-3 px-3 mb-3" role="alert" style="font-size:0.95rem;">
        <strong>Important:</strong> First add an accommodation, then add buildings to it, and finally add floors to each building. This creates the structure needed for room management.
    </div>

    <div class="ams-card" style="padding:16px; margin-bottom:16px;">
        <div style="display:flex; flex-wrap:wrap; gap:16px; align-items:flex-end;">
            <div style="flex:1; min-width:220px;">
                <label class="ams-label">Search Accommodation</label>
                <input
                    id="accommodationSearchInput"
                    type="text"
                    class="ams-input"
                    placeholder="Name, type, address, contact, status">
            </div>
            <button type="button" onclick="resetAccommodationFilters()" class="btn-ams-ghost" style="height:40px;">
                Reset
            </button>
        </div>
    </div>

    <div class="ams-card" style="padding:0; overflow:hidden;">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; padding:12px 16px; border-bottom:1px solid #e5e7eb;">
            <div id="selectedAccommodationsText" style="font-size:13px; color:#434653;">0 selected</div>
            <button
                type="button"
                class="btn btn-danger btn-sm"
                id="bulkDeleteAccommodationsBtn"
                onclick="deleteSelectedAccommodations()"
                disabled>
                Delete Selected
            </button>
        </div>
        <div style="overflow-x:auto;">
            <table class="table table-bordered" data-export-title="Accommodation Data">
                <thead>
                    <tr>
                        <th style="width:44px; text-align:center;">
                            <input type="checkbox" id="selectAllAccommodations" aria-label="Select all accommodations">
                        </th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Address</th>
                        <th>Contact Person</th>
                        <th>Contact Number</th>
                        <th>Status</th>
                        <th width="300">Action</th>
                    </tr>
                </thead>
                <tbody id="accommodationTable"></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Accommodation Modal -->
<div class="modal fade" id="accommodationModal" tabindex="-1" aria-labelledby="accommodationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="accommodationModalLabel">Add Accommodation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="accommodationForm">
                <input type="hidden" id="accommodationId" name="id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="accommodation_name" class="form-label">Accommodation Name</label>
                        <input type="text" class="form-control" id="accommodation_name" name="accommodation_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="accommodation_type" class="form-label">Type</label>
                        <select class="form-select" id="accommodation_type" name="accommodation_type" required>
                            <option value="">Select type</option>
                            <option value="Hotel">Hotel</option>
                            <option value="Dormitory">Dormitory</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="contact_person" class="form-label">Contact Person</label>
                        <input type="text" class="form-control" id="contact_person" name="contact_person">
                    </div>
                    <div class="mb-3">
                        <label for="contact_number" class="form-label">Contact Number</label>
                        <input type="text" class="form-control" id="contact_number" name="contact_number">
                    </div>
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Accommodation</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Building Modal -->
<div class="modal fade" id="buildingModal" tabindex="-1" aria-labelledby="buildingModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="buildingModalLabel">Buildings in <span id="accomName"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#newBuildingModal">Add Building</button>
                </div>
                <div id="buildingList"></div>
            </div>
        </div>
    </div>
</div>

<!-- New Building Modal -->
<div class="modal fade" id="newBuildingModal" tabindex="-1" aria-labelledby="newBuildingModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newBuildingModalLabel">Add Building</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="buildingForm">
                <input type="hidden" id="building_accommodation_id" name="accommodation_id">
                <input type="hidden" id="buildingId" name="id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="building_name" class="form-label">Building Name</label>
                        <input type="text" class="form-control" id="building_name" name="building_name" required>
                        <div class="form-text">Notes: Enter a descriptive building name, for example "Main Building" or "Annex Wing". This building groups floors under the selected accommodation.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Building</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Floor Modal -->
<div class="modal fade" id="floorModal" tabindex="-1" aria-labelledby="floorModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="floorModalLabel">Add Floor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="floorForm">
                <input type="hidden" id="floorId" name="id">
                <input type="hidden" id="floor_building_id" name="building_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="floor_name" class="form-label">Floor Name</label>
                        <input type="text" class="form-control" id="floor_name" name="floor_name" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Floor</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="assets/js/accommodations.js"></script>

<?php include 'layouts/footer.php'; ?>
