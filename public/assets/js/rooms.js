const roomsApiUrl = 'api/rooms.php';
const accommodationsApiUrl = 'api/accommodations.php';
const buildingsApiUrl = 'api/buildings.php';
let roomRows = [];
let selectedRoomIds = new Set();
let roomSearchTimer = null;

const roomSortColumns = [
    { index: 1, key: 'room_no' },
    { index: 2, key: 'accommodation_name' },
    { index: 3, key: 'floor_name' },
    { index: 4, key: 'room_type' },
    { index: 5, key: 'capacity' },
    { index: 6, key: 'current_occupancy' },
    { index: 7, key: 'status' },
    { index: 8, key: 'gender_restriction' }
];

function parseJsonResponse(data) {
    return typeof data === 'string' ? JSON.parse(data) : data;
}

function loadAccommodations()
{
    $.get(accommodationsApiUrl, function(data) {
        const accommodations = parseJsonResponse(data);
        let options = '<option value="">Select accommodation</option>';

        accommodations.forEach(acc => {
            options += `<option value="${acc.id}">${acc.accommodation_name}</option>`;
        });

        $('#accommodation_id').html(options);
        $('#filterAccommodation').html('<option value="">All Accommodations</option>' + 
            accommodations.map(acc => `<option value="${acc.id}">${acc.accommodation_name}</option>`).join(''));
    });
}

function loadBuildingsForModal(selectedBuildingIdOrEvent = null, callback = null)
{
    let selectedBuildingId = selectedBuildingIdOrEvent;
    if (selectedBuildingIdOrEvent && typeof selectedBuildingIdOrEvent === 'object' && selectedBuildingIdOrEvent.type) {
        selectedBuildingId = null;
    }

    const accommodationId = $('#accommodation_id').val();
    if (!accommodationId) {
        $('#building_id').html('<option value="">Select building</option>');
        if (typeof callback === 'function') callback();
        return;
    }

    $.get(`${accommodationsApiUrl}/${accommodationId}/buildings`, function(data) {
        const buildings = parseJsonResponse(data);
        let options = '<option value="">Select building</option>';

        buildings.forEach(bld => {
            options += `<option value="${bld.id}">${bld.building_name}</option>`;
        });

        $('#building_id').html(options);

        if (selectedBuildingId) {
            $('#building_id').val(selectedBuildingId);
        }

        if (typeof callback === 'function') {
            callback();
        }
    });
}

function loadBuildingsByAccommodation()
{
    const accommodationId = $('#filterAccommodation').val();
    if (!accommodationId) {
        $('#filterBuilding').html('<option value="">All Buildings</option>');
        loadRooms();
        return;
    }

    $.get(`${accommodationsApiUrl}/${accommodationId}/buildings`, function(data) {
        const buildings = parseJsonResponse(data);
        let options = '<option value="">All Buildings</option>';

        buildings.forEach(bld => {
            options += `<option value="${bld.id}">${bld.building_name}</option>`;
        });

        $('#filterBuilding').html(options);
        loadRooms();
    });
}

function loadFloorsForModal(selectedFloorIdOrEvent = null, callback = null)
{
    let selectedFloorId = selectedFloorIdOrEvent;
    if (selectedFloorIdOrEvent && typeof selectedFloorIdOrEvent === 'object' && selectedFloorIdOrEvent.type) {
        selectedFloorId = null;
    }

    const buildingId = $('#building_id').val();
    if (!buildingId) {
        $('#floor_id').html('<option value="">Select floor</option>');
        if (typeof callback === 'function') callback();
        return;
    }

    $.get(`${buildingsApiUrl}/${buildingId}/floors`, function(data) {
        const floors = parseJsonResponse(data);
        let options = '<option value="">Select floor</option>';

        floors.forEach(flr => {
            options += `<option value="${flr.id}">${flr.floor_name}</option>`;
        });

        $('#floor_id').html(options);

        if (selectedFloorId) {
            $('#floor_id').val(selectedFloorId);
        }

        if (typeof callback === 'function') {
            callback();
        }
    });
}

function loadFloorsByBuilding()
{
    const buildingId = $('#filterBuilding').val();
    if (!buildingId) {
        $('#filterFloor').html('<option value="">All Floors</option>');
        loadRooms();
        return;
    }

    $.get(`${buildingsApiUrl}/${buildingId}/floors`, function(data) {
        const floors = parseJsonResponse(data);
        let options = '<option value="">All Floors</option>';

        floors.forEach(flr => {
            options += `<option value="${flr.id}">${flr.floor_name}</option>`;
        });

        $('#filterFloor').html(options);
        loadRooms();
    });
}

function updateRoomStatusSummary(rooms) {
    const total = rooms.length;
    const occupied = rooms.filter(r => r.status === 'Occupied').length;
    const available = rooms.filter(r => r.status === 'Available').length;
    const maintenance = rooms.filter(r => r.status === 'Maintenance').length;

    document.getElementById('roomSummaryTotal').textContent = total;
    document.getElementById('roomSummaryOccupied').textContent = occupied;
    document.getElementById('roomSummaryAvailable').textContent = available;
    document.getElementById('roomSummaryMaintenance').textContent = maintenance;
}

function updateRoomTypeSummary(rooms) {
    const container = document.getElementById('roomTypeSummaryList');
    if (!container) return;

    const typeCounts = rooms.reduce((acc, room) => {
        const type = String(room.room_type || '').trim();
        if (!type) return acc;
        acc[type] = (acc[type] || 0) + 1;
        return acc;
    }, {});

    const orderedTypes = ['Single', 'Double', 'Triple', 'Quadruple', 'Suit'];
    const normalizedTypeCounts = Object.entries(typeCounts).reduce((acc, [type, count]) => {
        const normalizedType = type === 'Suite' ? 'Suit' : type;
        acc[normalizedType] = (acc[normalizedType] || 0) + count;
        return acc;
    }, {});

    const entries = orderedTypes
        .filter(type => normalizedTypeCounts[type])
        .map(type => ({ type, count: normalizedTypeCounts[type] }))
        .concat(Object.entries(normalizedTypeCounts)
            .filter(([type]) => !orderedTypes.includes(type))
            .map(([type, count]) => ({ type, count }))
            .sort((a, b) => a.type.localeCompare(b.type)));

    if (entries.length === 0) {
        container.innerHTML = '<div class="text-muted">No room types found.</div>';
        return;
    }

    container.innerHTML = entries.map(({ type, count }) => `
        <div class="d-flex justify-content-between align-items-center mb-1">
            <span>${type}</span>
            <span class="fw-semibold text-dark">${count}</span>
        </div>
    `).join('');
}

function renderRoomRow(room) {
    const roomId = String(room.id);
    const checked = selectedRoomIds.has(roomId) ? 'checked' : '';

    const reservationNote = room.status === 'Reserved' && room.reserved_by_employee_name
        ? `<div class="small text-muted mt-1">by ${displayValue(room.reserved_by_employee_name)}</div>`
        : '';

    const assignedEmployeeNames = room.assigned_employee_names
        ? room.assigned_employee_names.split('\n').filter(Boolean).map(name => `<div>${displayValue(name)} -</div>`).join('')
        : '<span class="text-muted">—</span>';

    return `
        <tr>
            <td style="text-align:center;">
                <input
                    type="checkbox"
                    class="room-select-checkbox"
                    value="${roomId}"
                    aria-label="Select room"
                    onchange="toggleRoomSelection(${roomId}, this.checked)"
                    ${checked}>
            </td>
            <td>${displayValue(room.room_no)}</td>
            <td>${displayValue(room.accommodation_name)}</td>
            <td>${displayValue(room.floor_name)}</td>
            <td>${displayValue(room.room_type)}</td>
            <td>${displayValue(room.capacity)}</td>
            <td>${displayValue(room.current_occupancy)}</td>
            <td>
                <span class="badge ${room.status === 'Available' ? 'bg-success' : (room.status === 'Occupied' ? 'bg-warning' : (room.status === 'Reserved' ? 'bg-info' : 'bg-secondary'))}">${displayValue(room.status)}</span>
                ${reservationNote}
            </td>
            <td>${assignedEmployeeNames}</td>
            <td>${displayValue(room.gender_restriction)}</td>
            <td>
                <button class="btn btn-warning btn-sm me-1" onclick="editRoom(${room.id})">Edit</button>
                <button class="btn btn-danger btn-sm" onclick="deleteRoom(${room.id})">Delete</button>
            </td>
        </tr>
    `;
}

function filterRoomRows(rooms) {
    const search = ($('#roomSearchInput').val() || '').trim().toLowerCase();

    if (!search) {
        return rooms.slice();
    }

    return rooms.filter(room => {
        return [
            room.room_no,
            room.accommodation_name,
            room.building_name,
            room.floor_name,
            room.room_type,
            room.capacity,
            room.current_occupancy,
            room.status,
            room.gender_restriction
        ].some(value => String(value ?? '').toLowerCase().includes(search));
    });
}

function renderRooms() {
    const filteredRooms = filterRoomRows(roomRows);

    renderPaginatedTable({
        data: filteredRooms,
        tableSelector: '#roomTable',
        currentPage: 1,
        perPage: 10,
        renderRow: renderRoomRow,
        sortColumns: roomSortColumns
    });

    updateRoomStatusSummary(filteredRooms);
    updateRoomTypeSummary(filteredRooms);
    updateRoomSelectionControls();
}

function loadRooms()
{
    $.get(roomsApiUrl, function(data) {
        roomRows = parseJsonResponse(data);
        selectedRoomIds.clear();
        renderRooms();
    });
}

function loadRoomsByFloor()
{
    const floorId = $('#filterFloor').val();
    if (!floorId) {
        loadRooms();
        return;
    }

    $.get(`${roomsApiUrl}/${floorId}/byfloor`, function(data) {
        roomRows = parseJsonResponse(data);
        selectedRoomIds.clear();
        renderRooms();
    });
}

function resetRoomFilters()
{
    $('#roomSearchInput').val('');
    $('#filterAccommodation').val('');
    $('#filterBuilding').html('<option value="">All Buildings</option>');
    $('#filterFloor').html('<option value="">All Floors</option>');
    loadRooms();
}

function toggleRoomSelection(id, checked)
{
    if (checked) {
        selectedRoomIds.add(String(id));
    } else {
        selectedRoomIds.delete(String(id));
    }

    updateRoomSelectionControls();
}

function toggleAllRooms(checked)
{
    $('.room-select-checkbox').each(function() {
        this.checked = checked;

        if (checked) {
            selectedRoomIds.add(String(this.value));
        } else {
            selectedRoomIds.delete(String(this.value));
        }
    });

    updateRoomSelectionControls();
}

function updateRoomSelectionControls()
{
    const selectedCount = selectedRoomIds.size;
    const rowCheckboxes = $('.room-select-checkbox');
    const checkedCount = rowCheckboxes.filter(':checked').length;
    const selectAll = document.getElementById('selectAllRooms');

    $('#selectedRoomsText').text(`${selectedCount} selected`);
    $('#bulkDeleteRoomsBtn').prop('disabled', selectedCount === 0);

    if (selectAll) {
        selectAll.checked = rowCheckboxes.length > 0 && checkedCount === rowCheckboxes.length;
        selectAll.indeterminate = checkedCount > 0 && checkedCount < rowCheckboxes.length;
    }
}

function reloadCurrentRoomList()
{
    if ($('#filterFloor').val()) {
        loadRoomsByFloor();
        return;
    }

    loadRooms();
}

function resetRoomForm()
{
    $('#roomForm')[0].reset();
    $('#roomId').val('');
    $('#roomModalLabel').text('Add Room');
    $('#accommodation_id').val('');
    $('#building_id').html('<option value="">Select building</option>');
    $('#floor_id').html('<option value="">Select floor</option>');
    $('#reservedEmployeeGroup').hide();
    $('#reserved_by_employee_id').html('<option value="">Select employee</option>');
}

function loadEmployeesForRoomReservation() {
    $.get('api/employees.php', function(data) {
        const employees = typeof data === 'string' ? JSON.parse(data) : data;
        const select = $('#reserved_by_employee_id');
        if (!select.length) {
            return;
        }

        const options = ['<option value="">Select employee</option>']
            .concat((employees || []).map(emp => `<option value="${emp.id}">${emp.employee_code ? `${emp.employee_code} - ` : ''}${emp.full_name || 'Unnamed Employee'}</option>`))
            .join('');

        select.html(options);
    });
}

function toggleReservedEmployeeField() {
    const status = $('#status').val();
    const group = $('#reservedEmployeeGroup');
    if (status === 'Reserved') {
        group.show();
    } else {
        group.hide();
        $('#reserved_by_employee_id').val('');
    }
}

function openRoomModal(room)
{
    resetRoomForm();
    loadEmployeesForRoomReservation();

    if (room) {
        $('#roomId').val(room.id);
        $('#roomModalLabel').text('Edit Room');
        $('#accommodation_id').val(room.accommodation_id || '');

        loadBuildingsForModal(room.building_id, function() {
            loadFloorsForModal(room.floor_id, function() {
                $('#room_no').val(room.room_no);
                $('#room_type').val(room.room_type);
                $('#capacity').val(room.capacity);
                $('#status').val(room.status);
                $('#reserved_by_employee_id').val(room.reserved_by_employee_id || '');
                toggleReservedEmployeeField();
                $('#gender_restriction').val(room.gender_restriction || '');
                $('#remarks').val(room.remarks || '');
                $('#roomModal').modal('show');
            });
        });

        return;
    }

    $('#roomModal').modal('show');
}

function editRoom(id)
{
    $.get(`${roomsApiUrl}/${id}`, function(data) {

        const room = parseJsonResponse(data);

        if (!room || !room.id) {
            swalError('Unable to load room details');
            return;
        }

        openRoomModal(room);

    });
}

function generateRoomRangePreview()
{
    const startRoomNo = $('#start_room_no').val()?.trim();
    const endRoomNo = $('#end_room_no').val()?.trim();

    if (!startRoomNo || !endRoomNo) {
        $('#roomRangePreview').text('Enter both start and end room numbers to preview the range.');
        return;
    }

    $('#generate_range').val('1');
    const previewText = `Will generate: ${startRoomNo} to ${endRoomNo}`;
    $('#roomRangePreview').text(previewText);
}

function saveRoom(event)
{
    event.preventDefault();

    if ($('#status').val() === 'Reserved' && !$('#reserved_by_employee_id').val()) {
        swalError('Please select the employee who reserved this room.');
        return;
    }

    const hasRange = $('#generate_range').val() === '1' && $('#start_room_no').val()?.trim() && $('#end_room_no').val()?.trim();
    if (hasRange) {
        const startRoomNo = $('#start_room_no').val().trim();
        const endRoomNo = $('#end_room_no').val().trim();
        if (!startRoomNo || !endRoomNo) {
            swalError('Please enter both start and end room numbers for batch generation.');
            return;
        }
    }

    const id = $('#roomId').val();
    const url = id ? `${roomsApiUrl}/${id}` : roomsApiUrl;
    const method = id ? 'PUT' : 'POST';

    $.ajax({
        url: url,
        type: method,
        data: $('#roomForm').serialize(),
        success: function(response) {
            const payload = typeof response === 'string' ? JSON.parse(response) : response;
            if (payload && payload.success === false) {
                swalError(payload.error || 'Unknown error');
                return;
            }
            loadRooms();
            $('#roomModal').modal('hide');
            swalSuccess('Room saved successfully');
        },
        error: function(xhr) {
            swalError(xhr.responseJSON?.error || 'Unknown error');
        }
    });
}

function deleteRoom(id)
{
    swalConfirm('Delete room?', function() {
        $.ajax({
            url: `${roomsApiUrl}/${id}`,
            type: 'DELETE',
            success: function() {
                selectedRoomIds.delete(String(id));
                reloadCurrentRoomList();
                swalSuccess('Room deleted successfully');
            },
            error: function(xhr) {
                swalError(xhr.responseJSON?.error || 'Unknown error');
            }
        });
    });
}

function deleteRoomById(id)
{
    return new Promise(resolve => {
        $.ajax({
            url: `${roomsApiUrl}/${id}`,
            type: 'DELETE',
            success: function() {
                resolve({ success: true, id: id });
            },
            error: function(xhr) {
                const message = xhr.responseJSON?.error || xhr.responseText || 'Unknown error';
                resolve({ success: false, id: id, error: message });
            }
        });
    });
}

function deleteSelectedRooms()
{
    const ids = Array.from(selectedRoomIds);

    if (ids.length === 0) {
        swalInfo('Select at least one room to delete.');
        return;
    }

    swalConfirm(`Delete ${ids.length} selected room${ids.length === 1 ? '' : 's'}?`, function() {
        $('#bulkDeleteRoomsBtn').prop('disabled', true).text('Deleting...');

        Promise.all(ids.map(deleteRoomById)).then(results => {
            const failed = results.filter(result => !result.success);
            const deletedCount = results.length - failed.length;

            selectedRoomIds.clear();
            reloadCurrentRoomList();
            $('#bulkDeleteRoomsBtn').text('Delete Selected');

            if (failed.length > 0) {
                const firstError = failed[0].error;
                swalError(`${deletedCount} deleted. ${failed.length} could not be deleted. ${firstError}`, 'Bulk delete incomplete');
                return;
            }

            swalSuccess(`${deletedCount} room${deletedCount === 1 ? '' : 's'} deleted successfully.`);
        });
    });
}

$(function() {
    loadAccommodations();
    loadRooms();
    loadEmployeesForRoomReservation();
    $('#selectAllRooms').on('change', function() {
        toggleAllRooms(this.checked);
    });
    $('#roomSearchInput').on('input', function() {
        clearTimeout(roomSearchTimer);
        roomSearchTimer = setTimeout(function() {
            selectedRoomIds.clear();
            renderRooms();
        }, 250);
    });
    $('#roomForm').on('submit', saveRoom);
    $('#accommodation_id').on('change', loadBuildingsForModal);
    $('#building_id').on('change', loadFloorsForModal);
    $('#status').on('change', toggleReservedEmployeeField);
});
