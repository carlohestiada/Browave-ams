let currentAccommodationId = null;
let currentBuildingId = null;
let currentBuildingName = null;
let accommodationRows = [];
let selectedAccommodationIds = new Set();
let accommodationSearchTimer = null;
const accommodationApiUrl = 'api/accommodations.php';
const buildingApiUrl = 'api/buildings.php';
const floorApiUrl = 'api/floors.php';
const roomApiUrl = 'api/rooms.php';

const accommodationSortColumns = [
    { index: 1, key: 'accommodation_name' },
    { index: 2, key: 'accommodation_type' },
    { index: 3, key: 'address' },
    { index: 4, key: 'contact_person' },
    { index: 5, key: 'contact_number' },
    { index: 6, key: 'status' }
];

function parseJsonResponse(data) {
    return typeof data === 'string' ? JSON.parse(data) : data;
}

function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function renderAccommodationRow(acc) {
    const accommodationId = String(acc.id);
    const checked = selectedAccommodationIds.has(accommodationId) ? 'checked' : '';

    return `
        <tr>
            <td style="text-align:center;">
                <input
                    type="checkbox"
                    class="accommodation-select-checkbox"
                    value="${accommodationId}"
                    aria-label="Select accommodation"
                    onchange="toggleAccommodationSelection(${accommodationId}, this.checked)"
                    ${checked}>
            </td>
            <td>${displayValue(acc.accommodation_name)}</td>
            <td><span class="badge bg-primary">${displayValue(acc.accommodation_type)}</span></td>
            <td>${displayValue(acc.address)}</td>
            <td>${displayValue(acc.contact_person)}</td>
            <td>${displayValue(acc.contact_number)}</td>
            <td><span class="badge ${acc.status === 'Active' ? 'bg-success' : 'bg-danger'}">${displayValue(acc.status)}</span></td>
            <td style="white-space:nowrap;">
                <button type="button" class="btn btn-info btn-sm me-1" onclick="viewBuildings(${acc.id})">Buildings</button>
                <button type="button" class="btn btn-warning btn-sm me-1" onclick="editAccommodation(${acc.id})">Edit</button>
                <button type="button" class="btn btn-danger btn-sm" onclick="deleteAccommodation(${acc.id})">Delete</button>
            </td>
        </tr>
    `;
}

function filterAccommodationRows(accommodations) {
    const search = ($('#accommodationSearchInput').val() || '').trim().toLowerCase();

    if (!search) {
        return accommodations.slice();
    }

    return accommodations.filter(acc => {
        return [
            acc.accommodation_name,
            acc.accommodation_type,
            acc.address,
            acc.contact_person,
            acc.contact_number,
            acc.status
        ].some(value => String(value ?? '').toLowerCase().includes(search));
    });
}

function renderAccommodations() {
    renderPaginatedTable({
        data: filterAccommodationRows(accommodationRows),
        tableSelector: '#accommodationTable',
        currentPage: 1,
        perPage: 10,
        renderRow: renderAccommodationRow,
        sortColumns: accommodationSortColumns
    });

    updateAccommodationSelectionControls();
}

function loadAccommodations()
{
    $.get(accommodationApiUrl, function(data) {
        const accommodations = parseJsonResponse(data);
        accommodationRows = accommodations;
        selectedAccommodationIds.clear();
        renderAccommodations();

        let options = '<option value="">Select accommodation</option>';
        accommodations.forEach(acc => {
            options += `<option value="${acc.id}">${acc.accommodation_name}</option>`;
        });

        $('#accommodation_id').html(options);
        $('#filterAccommodation').html('<option value="">All Accommodations</option>' + 
            accommodations.map(acc => `<option value="${acc.id}">${acc.accommodation_name}</option>`).join(''));
    });
}

function resetAccommodationFilters()
{
    $('#accommodationSearchInput').val('');
    selectedAccommodationIds.clear();
    renderAccommodations();
}

function toggleAccommodationSelection(id, checked)
{
    if (checked) {
        selectedAccommodationIds.add(String(id));
    } else {
        selectedAccommodationIds.delete(String(id));
    }

    updateAccommodationSelectionControls();
}

function toggleAllAccommodations(checked)
{
    $('.accommodation-select-checkbox').each(function() {
        this.checked = checked;

        if (checked) {
            selectedAccommodationIds.add(String(this.value));
        } else {
            selectedAccommodationIds.delete(String(this.value));
        }
    });

    updateAccommodationSelectionControls();
}

function updateAccommodationSelectionControls()
{
    const selectedCount = selectedAccommodationIds.size;
    const rowCheckboxes = $('.accommodation-select-checkbox');
    const checkedCount = rowCheckboxes.filter(':checked').length;
    const selectAll = document.getElementById('selectAllAccommodations');

    $('#selectedAccommodationsText').text(`${selectedCount} selected`);
    $('#bulkDeleteAccommodationsBtn').prop('disabled', selectedCount === 0);

    if (selectAll) {
        selectAll.checked = rowCheckboxes.length > 0 && checkedCount === rowCheckboxes.length;
        selectAll.indeterminate = checkedCount > 0 && checkedCount < rowCheckboxes.length;
    }
}

function resetAccommodationForm()
{
    $('#accommodationForm')[0].reset();
    $('#accommodationId').val('');
    $('#accommodationModalLabel').text('Add Accommodation');
}

function openAccommodationModal(accommodation)
{
    resetAccommodationForm();

    if (accommodation) {
        $('#accommodationId').val(accommodation.id);
        $('#accommodation_name').val(accommodation.accommodation_name);
        $('#accommodation_type').val(accommodation.accommodation_type);
        $('#address').val(accommodation.address);
        $('#contact_person').val(accommodation.contact_person);
        $('#contact_number').val(accommodation.contact_number);
        $('#status').val(accommodation.status);
        $('#accommodationModalLabel').text('Edit Accommodation');
    }

    $('#accommodationModal').modal('show');
}

function editAccommodation(id)
{
    $.get(`${accommodationApiUrl}/${id}`, function(data) {
        const accommodation = parseJsonResponse(data);
        openAccommodationModal(accommodation);
    });
}

function saveAccommodation(event)
{
    event.preventDefault();

    const id = $('#accommodationId').val();
    const url = id ? `${accommodationApiUrl}/${id}` : accommodationApiUrl;
    const method = id ? 'PUT' : 'POST';

    $.ajax({
        url: url,
        type: method,
        data: $('#accommodationForm').serialize(),
        success: function() {
            loadAccommodations();
            $('#accommodationModal').modal('hide');
            swalSuccess('Accommodation saved successfully');
        },
        error: function(xhr) {
            swalError('Error: ' + (xhr.responseJSON?.error || xhr.responseText || 'Unknown error'));
        }
    });
}

function deleteAccommodation(id)
{
    swalConfirm('Delete accommodation?', function() {
        $.ajax({
            url: `${accommodationApiUrl}/${id}`,
            type: 'DELETE',
            success: function(response) {
                const result = typeof response === 'string' ? JSON.parse(response) : response;
                if (result.success === false) {
                    swalError(result.error || 'Unable to delete accommodation');
                    return;
                }

                selectedAccommodationIds.delete(String(id));
                loadAccommodations();
                swalSuccess('Accommodation deleted successfully');
            },
            error: function(xhr) {
                swalError('Error: ' + (xhr.responseJSON?.error || xhr.responseText || 'Unknown error'));
            }
        });
    });
}

function deleteAccommodationById(id)
{
    return new Promise(resolve => {
        $.ajax({
            url: `${accommodationApiUrl}/${id}`,
            type: 'DELETE',
            success: function(response) {
                const result = typeof response === 'string' ? JSON.parse(response) : response;
                resolve({ success: result.success !== false, id: id, error: result.error || 'Delete failed' });
            },
            error: function(xhr) {
                const message = xhr.responseJSON?.error || xhr.responseText || 'Delete failed';
                resolve({ success: false, id: id, error: message });
            }
        });
    });
}

function deleteSelectedAccommodations()
{
    const ids = Array.from(selectedAccommodationIds);

    if (ids.length === 0) {
        swalInfo('Select at least one accommodation to delete.');
        return;
    }

    swalConfirm(`Delete ${ids.length} selected accommodation${ids.length === 1 ? '' : 's'}?`, function() {
        $('#bulkDeleteAccommodationsBtn').prop('disabled', true).text('Deleting...');

        Promise.all(ids.map(deleteAccommodationById)).then(results => {
            const failed = results.filter(result => !result.success);
            const deletedCount = results.length - failed.length;

            selectedAccommodationIds.clear();
            loadAccommodations();
            $('#bulkDeleteAccommodationsBtn').text('Delete Selected');

            if (failed.length > 0) {
                const firstError = failed[0].error;
                swalError(`${deletedCount} deleted. ${failed.length} could not be deleted. ${firstError}`, 'Bulk delete incomplete');
                return;
            }

            swalSuccess(`${deletedCount} accommodation${deletedCount === 1 ? '' : 's'} deleted successfully.`);
        });
    });
}

function viewBuildings(accommodationId, accommodationName = null)
{
    const accommodation = accommodationRows.find(acc => String(acc.id) === String(accommodationId));
    accommodationName = accommodationName || accommodation?.accommodation_name || '';
    currentAccommodationId = accommodationId;
    $('#accomName').text(accommodationName);

    $.get(`${accommodationApiUrl}/${accommodationId}/buildings`, function(data) {
        const buildings = parseJsonResponse(data);
        let html = '';

        if (buildings.length === 0) {
            html = '<p class="text-muted">No buildings found</p>';
        } else {
            html = '<div class="list-group">';
            buildings.forEach(bld => {
                html += `
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">${escapeHtml(displayValue(bld.building_name))}</h6>
                            <div>
                                <button class="btn btn-sm btn-secondary me-1 btn-view-floors" data-building-id="${bld.id}" data-building-name="${escapeHtml(displayValue(bld.building_name))}">Floors</button>
                                <button class="btn btn-sm btn-warning me-1 btn-edit-building" data-building-id="${bld.id}" data-building-name="${escapeHtml(displayValue(bld.building_name))}">Edit</button>
                                <button class="btn btn-sm btn-danger btn-delete-building" data-building-id="${bld.id}">Delete</button>
                            </div>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
        }

        $('#buildingList').html(html);
        $('#buildingModal').modal('show');
    });
}

function editBuilding(buildingId, buildingName)
{
    $('#buildingId').val(buildingId);
    $('#building_name').val(buildingName);
    $('#building_accommodation_id').val(currentAccommodationId);
    $('#newBuildingModalLabel').text('Edit Building');
    $('#newBuildingModal').modal('show');
}

function saveBuildingModal(event)
{
    event.preventDefault();

    const buildingId = $('#buildingId').val();
    const url = buildingId ? `${buildingApiUrl}/${buildingId}` : buildingApiUrl;
    const method = buildingId ? 'PUT' : 'POST';

    $.ajax({
        url: url,
        type: method,
        data: $('#buildingForm').serialize(),
        success: function() {
            $('#buildingForm')[0].reset();
            $('#buildingId').val('');
            $('#newBuildingModalLabel').text('Add Building');
            viewBuildings(currentAccommodationId, $('#accomName').text());
            $('#newBuildingModal').modal('hide');
            swalSuccess('Building saved successfully');
        },
        error: function(xhr) {
            swalError('Error: ' + (xhr.responseJSON?.error || xhr.responseText || 'Unknown error'));
        }
    });
}

function deleteBuilding(buildingId)
{
    if (!confirm('Delete building?')) {
        return;
    }

    $.ajax({
        url: `${buildingApiUrl}/${buildingId}`,
        type: 'DELETE',
        success: function() {
            viewBuildings(currentAccommodationId, $('#accomName').text());
            swalSuccess('Building deleted successfully');
        },
        error: function(xhr) {
            swalError('Error: ' + (xhr.responseJSON?.error || xhr.responseText || 'Unknown error'));
        }
    });
}

function viewFloors(buildingId, buildingName)
{
    currentBuildingId = buildingId;
    currentBuildingName = buildingName;
    $.get(`${buildingApiUrl}/${buildingId}/floors`, function(data) {
        const floors = parseJsonResponse(data);
        let html = `<div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0">Floors in ${escapeHtml(displayValue(buildingName))}</h6>
                        <button class="btn btn-sm btn-primary btn-add-floor" data-building-id="${buildingId}" data-building-name="${escapeHtml(displayValue(buildingName))}">Add Floor</button>
                    </div>`;

        if (floors.length === 0) {
            html += '<p class="text-muted">No floors found</p>';
        } else {
            html += '<div class="list-group mt-2">';
            floors.forEach(flr => {
                html += `
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>${escapeHtml(displayValue(flr.floor_name))}</span>
                            <div>
                                <button class="btn btn-sm btn-secondary me-1 btn-view-rooms" data-floor-id="${flr.id}" data-floor-name="${escapeHtml(displayValue(flr.floor_name))}">Rooms</button>
                                <button class="btn btn-sm btn-warning me-1 btn-edit-floor" data-floor-id="${flr.id}" data-building-id="${buildingId}" data-floor-name="${escapeHtml(displayValue(flr.floor_name))}">Edit</button>
                                <button class="btn btn-sm btn-danger btn-delete-floor" data-floor-id="${flr.id}">Delete</button>
                            </div>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
        }

        $('#buildingList').html(html);
    });
}

function showFloorModal(buildingId, buildingName)
{
    resetFloorForm();
    currentBuildingId = buildingId;
    $('#floor_building_id').val(buildingId);
    $('#floorModalLabel').text(`Add Floor to ${buildingName}`);
    $('#floorModal').modal('show');
}

function editFloor(floorId, buildingId, floorName)
{
    resetFloorForm();
    currentBuildingId = buildingId;
    $('#floorId').val(floorId);
    $('#floor_building_id').val(buildingId);
    $('#floor_name').val(floorName);
    $('#floorModalLabel').text('Edit Floor');
    $('#floorModal').modal('show');
}

function resetFloorForm()
{
    $('#floorForm')[0].reset();
    $('#floorId').val('');
    $('#floor_building_id').val('');
    $('#floorModalLabel').text('Add Floor');
}

function saveFloorModal(event)
{
    event.preventDefault();

    const floorId = $('#floorId').val();
    const url = floorId ? `${floorApiUrl}/${floorId}` : floorApiUrl;
    const method = floorId ? 'PUT' : 'POST';

    $.ajax({
        url: url,
        type: method,
        data: $('#floorForm').serialize(),
        success: function() {
            $('#floorForm')[0].reset();
            $('#floorId').val('');
            $('#floorModal').modal('hide');
            viewFloors(currentBuildingId, currentBuildingName);
            swalSuccess('Floor saved successfully');
        },
        error: function(xhr) {
            swalError('Error: ' + (xhr.responseJSON?.error || xhr.responseText || 'Unknown error'));
        }
    });
}

function deleteFloor(floorId)
{
    if (!confirm('Delete floor?')) {
        return;
    }

    $.ajax({
        url: `${floorApiUrl}/${floorId}`,
        type: 'DELETE',
        success: function() {
            if (currentBuildingId && currentBuildingName) {
                viewFloors(currentBuildingId, currentBuildingName);
            }
            swalSuccess('Floor deleted successfully');
        },
        error: function(xhr) {
            swalError('Error: ' + (xhr.responseJSON?.error || xhr.responseText || 'Unknown error'));
        }
    });
}

function viewRooms(floorId, floorName)
{
    $.get(`${roomApiUrl}/${floorId}/byfloor`, function(data) {
        const rooms = parseJsonResponse(data);
        let html = `<h6>Rooms on ${floorName}</h6>`;

        if (rooms.length === 0) {
            html += '<p class="text-muted">No rooms found</p>';
        } else {
            html += '<div class="list-group mt-2">';
            rooms.forEach(room => {
                html += `
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>${room.room_no}</strong> - ${room.room_type} (${room.current_occupancy}/${room.capacity})
                                <span class="badge ${room.status === 'Available' ? 'bg-success' : (room.status === 'Occupied' ? 'bg-warning' : 'bg-secondary')}">${room.status}</span>
                            </div>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
        }

        $('#buildingList').html(html);
    });
}

$(function() {
    loadAccommodations();
    $('#selectAllAccommodations').on('change', function() {
        toggleAllAccommodations(this.checked);
    });
    $('#accommodationSearchInput').on('input', function() {
        clearTimeout(accommodationSearchTimer);
        accommodationSearchTimer = setTimeout(function() {
            selectedAccommodationIds.clear();
            renderAccommodations();
        }, 250);
    });
    $('#accommodationForm').on('submit', saveAccommodation);
    $('#buildingForm').on('submit', saveBuildingModal);
    $('#floorForm').on('submit', saveFloorModal);

    $(document).on('click', '.btn-view-floors', function() {
        const buildingId = $(this).data('building-id');
        const buildingName = $(this).data('building-name');
        viewFloors(buildingId, buildingName);
    });

    $(document).on('click', '.btn-edit-building', function() {
        const buildingId = $(this).data('building-id');
        const buildingName = $(this).data('building-name');
        editBuilding(buildingId, buildingName);
    });

    $(document).on('click', '.btn-delete-building', function() {
        const buildingId = $(this).data('building-id');
        deleteBuilding(buildingId);
    });

    $(document).on('click', '.btn-add-floor', function() {
        const buildingId = $(this).data('building-id');
        const buildingName = $(this).data('building-name');
        showFloorModal(buildingId, buildingName);
    });

    $(document).on('click', '.btn-edit-floor', function() {
        const floorId = $(this).data('floor-id');
        const buildingId = $(this).data('building-id');
        const floorName = $(this).data('floor-name');
        editFloor(floorId, buildingId, floorName);
    });

    $(document).on('click', '.btn-delete-floor', function() {
        const floorId = $(this).data('floor-id');
        deleteFloor(floorId);
    });

    $(document).on('click', '.btn-view-rooms', function() {
        const floorId = $(this).data('floor-id');
        const floorName = $(this).data('floor-name');
        viewRooms(floorId, floorName);
    });
    
    // Set accommodation ID for new buildings
    $('#buildingModal').on('show.bs.modal', function() {
        $('#building_accommodation_id').val(currentAccommodationId);
    });
});
