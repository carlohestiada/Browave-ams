const roomsApiUrl = 'api/rooms.php';
const accommodationsApiUrl = 'api/accommodations.php';
const buildingsApiUrl = 'api/buildings.php';
let roomRows = [];
let selectedRoomIds = new Set();
let roomSearchTimer = null;
let currentRoomTab = 'all';
let availableRoomPrefixes = [];
let preselectedAccommodationId = null;
let roomReservationOriginal = null;
let roomSavePending = false;
let roomEmployees = [];

function getUrlParameter(name) {
    const params = new URLSearchParams(window.location.search);
    return params.get(name);
}

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

        if (preselectedAccommodationId) {
            $('#accommodation_id').val(preselectedAccommodationId);
            const url = new URL(window.location.href);
            url.searchParams.delete('accommodation_id');
            window.history.replaceState({}, '', url.toString());
            openRoomModal();
        }
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
        $('#building_id').html('<option value="">No building</option>');
        $('#floor_id').html('<option value="">No floor</option>');
        if (typeof callback === 'function') callback();
        return;
    }

    $.get(`${accommodationsApiUrl}/${accommodationId}/buildings`, function(data) {
        const buildings = parseJsonResponse(data);
        let options = '<option value="">No building</option>';

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
    $('#filterFloor').html('<option value="">All Floors</option>');
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
        $('#floor_id').html('<option value="">No floor</option>');
        if (typeof callback === 'function') callback();
        return;
    }

    $.get(`${buildingsApiUrl}/${buildingId}/floors`, function(data) {
        const floors = parseJsonResponse(data);
        let options = '<option value="">No floor</option>';

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
    $('#filterFloor').html('<option value="">All Floors</option>');
    if (!buildingId) {
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

function getDefaultCapacityByRoomType(roomType) {
    switch (String(roomType || '').trim()) {
        case 'Single':
            return 1;
        case 'Double':
            return 2;
        case 'Triple':
            return 3;
        case 'Quadruple':
            return 4;
        case 'Suit':
            return 5;
        default:
            return '';
    }
}

function applyRoomTypeCapacity(roomType) {
    const capacityInput = $('#capacity');
    if (!capacityInput.length) return;

    const defaultCapacity = getDefaultCapacityByRoomType(roomType);
    if (defaultCapacity !== '') {
        capacityInput.val(defaultCapacity);
    }
}

function updateRoomStatusSummary(rooms) {
    const total = rooms.length;
    const occupied = rooms.filter(r => r.status === 'Occupied').length;
    const available = rooms.filter(r => r.status === 'Available').length;
    const reserved = rooms.filter(r => r.status === 'Reserved').length;
    const maintenance = rooms.filter(r => r.status === 'Maintenance').length;

    document.getElementById('roomSummaryTotal').textContent = total;
    document.getElementById('roomSummaryOccupied').textContent = occupied;
    document.getElementById('roomSummaryAvailable').textContent = available;
    document.getElementById('roomSummaryReserved').textContent = reserved;
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

    const reservationNote = room.reserved_by_employee_id && room.reserved_by_employee_name
        ? `<div class="small text-muted mt-1">Reserved for <a href="#" class="employee-room-link text-decoration-none" data-room-id="${escapeHtml(roomId)}" data-employee-name="${escapeHtml(room.reserved_by_employee_name)}" data-type="reserved">${escapeHtml(room.reserved_by_employee_name)}</a></div>`
        : '';

    const assignedEmployeeNames = room.assigned_employee_names
        ? room.assigned_employee_names.split('\n').filter(Boolean).map(name => `<div><a href="#" class="employee-room-link text-decoration-none" data-room-id="${escapeHtml(roomId)}" data-employee-name="${escapeHtml(name)}" data-type="assigned">${escapeHtml(name)}</a> -</div>`).join('')
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

function showEmployeeRoom(roomId, employeeName, type) {
    const room = roomRows.find(item => String(item.id) === String(roomId));
    if (!room) {
        swalError('Room details could not be found.');
        return;
    }

    const title = type === 'reserved'
        ? `${employeeName} reserved Room ${room.room_no}`
        : `${employeeName} is assigned to Room ${room.room_no}`;
    const roomDetails = [
        ['Room No.', room.room_no],
        ['Accommodation', room.accommodation_name],
        ['Building', room.building_name],
        ['Floor', room.floor_name],
        ['Room Type', room.room_type],
        ['Status', room.status]
    ].map(([label, value]) => `
        <tr>
            <th scope="row">${escapeHtml(label)}</th>
            <td>${escapeHtml(displayValue(value))}</td>
        </tr>
    `).join('');

    Swal.fire({
        title: escapeHtml(title),
        html: `
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0">
                    <tbody>${roomDetails}</tbody>
                </table>
            </div>
        `
    });

}

function getRoomPrefix(roomNo) {
    const value = String(roomNo || '').trim();
    if (!value) return '';

    const match = value.match(/^([A-Za-z]+)/);
    return match ? match[1].toUpperCase() : '';
}

function naturalSortRooms(rooms) {
    return rooms.slice().sort((a, b) => {
        const aValue = String(a.room_no || '').trim();
        const bValue = String(b.room_no || '').trim();
        const aMatch = aValue.match(/^([A-Za-z]+)(\d+)/i);
        const bMatch = bValue.match(/^([A-Za-z]+)(\d+)/i);

        if (aMatch && bMatch && aMatch[1].toLowerCase() === bMatch[1].toLowerCase()) {
            const aNumber = Number(aMatch[2]);
            const bNumber = Number(bMatch[2]);
            if (aNumber !== bNumber) {
                return aNumber - bNumber;
            }
        }

        return aValue.localeCompare(bValue, undefined, { numeric: true, sensitivity: 'base' });
    });
}

function buildRoomPrefixTabs(rooms) {
    const prefixes = new Set();
    prefixes.add('all');

    rooms.forEach(room => {
        const prefix = getRoomPrefix(room.room_no);
        if (prefix) {
            prefixes.add(prefix);
        }
    });

    availableRoomPrefixes = Array.from(prefixes).filter(prefix => prefix !== 'all').sort((a, b) => a.localeCompare(b));

    const tabsContainer = $('#roomPrefixTabs');
    if (!tabsContainer.length) return;

    const activePrefix = currentRoomTab || 'all';
    const tabsHtml = [
        `<li class="nav-item"><button class="nav-link ${activePrefix === 'all' ? 'active' : ''}" type="button" data-room-tab="all">ALL</button></li>`,
        ...availableRoomPrefixes.map(prefix => `<li class="nav-item"><button class="nav-link ${activePrefix === prefix ? 'active' : ''}" type="button" data-room-tab="${prefix}">${prefix}</button></li>`)
    ].join('');

    tabsContainer.html(tabsHtml);

    tabsContainer.find('[data-room-tab]').on('click', function() {
        currentRoomTab = $(this).data('room-tab');
        renderRooms();
    });
}

function filterRoomRows(rooms) {
    const search = ($('#roomSearchInput').val() || '').trim().toLowerCase();
    const accommodationId = String($('#filterAccommodation').val() || '');
    const buildingId = String($('#filterBuilding').val() || '');
    const floorId = String($('#filterFloor').val() || '');
    const activeRooms = rooms.filter(room => {
        if (String(room.status || '').toLowerCase() === 'archived') return false;
        if (accommodationId && String(room.accommodation_id || '') !== accommodationId) return false;
        if (buildingId && String(room.building_id || '') !== buildingId) return false;
        if (floorId && String(room.floor_id || '') !== floorId) return false;
        return true;
    });
    let tabFilteredRooms = activeRooms;

    if (currentRoomTab && currentRoomTab !== 'all') {
        tabFilteredRooms = activeRooms.filter(room => getRoomPrefix(room.room_no) === currentRoomTab);
    }

    if (!search) {
        return naturalSortRooms(tabFilteredRooms.slice());
    }

    const searchedRooms = tabFilteredRooms.filter(room => {
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

    return naturalSortRooms(searchedRooms);
}

function renderRooms() {
    const filteredRooms = filterRoomRows(roomRows);

    buildRoomPrefixTabs(roomRows);

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
    currentRoomTab = 'all';
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
    roomReservationOriginal = null;
    $('#roomModalLabel').text('Add Room');
    $('#accommodation_id').val('');
    $('#building_id').html('<option value="">No building</option>');
    $('#floor_id').html('<option value="">No floor</option>');
    $('#capacity').val('');
    $('#reservedEmployeeGroup').hide();
    const reservedBySelect = $('#reserved_by_employee_id');
    if (reservedBySelect.hasClass('select2-hidden-accessible')) {
        reservedBySelect.select2('destroy');
    }
    reservedBySelect.html('<option value="">Select employee</option>').val('').trigger('change.select2');
}

function initializeReservedBySelect() {
    const select = $('#reserved_by_employee_id');
    const modal = $('#roomModal');
    if (
        !select.length ||
        !$.fn.select2 ||
        !select.is(':visible') ||
        !modal.hasClass('show')
    ) {
        return;
    }

    if (select.data('select2')) {
        return;
    }

    select.select2({
        dropdownParent: modal.find('.modal-content'),
        theme: 'bootstrap-5',
        placeholder: 'Select employee',
        allowClear: true,
        width: '100%',
        language: {
            noResults: function() {
                return 'No employees found';
            }
        },
        matcher: function(params, data) {
            const term = $.trim(params.term || '').toLowerCase();
            if (!term) {
                return data;
            }

            return data.text && data.text.toLowerCase().includes(term) ? data : null;
        }
    });
    select
        .off('select2:open.reservedByFocus')
        .on('select2:open.reservedByFocus', function() {
            setTimeout(function() {
                const searchField = $('.select2-container--open .select2-search__field')[0];
                if (searchField) {
                    searchField.focus();
                }
            }, 0);
        });
}

function repositionReservedByDropdown() {
        const select = $('#reserved_by_employee_id');
        const instance = select.data('select2');
        if (!instance || !instance.isOpen()) {
            return;
        }

        requestAnimationFrame(function() {
            if (select.data('select2') !== instance || !instance.isOpen()) {
                return;
            }

            if (typeof instance.dropdown._positionDropdown === 'function') {
                instance.dropdown._positionDropdown();
            }
            if (typeof instance.dropdown._resizeDropdown === 'function') {
                instance.dropdown._resizeDropdown();
            }
        });
}

function loadEmployeesForRoomReservation(selectedEmployeeId = '') {
    return $.get('api/employees.php', function(data) {
        const employees = typeof data === 'string' ? JSON.parse(data) : data;
        roomEmployees = Array.isArray(employees) ? employees : [];
        const select = $('#reserved_by_employee_id');
        if (!select.length) {
            return;
        }

        const options = ['<option value="">Select employee</option>']
            .concat((roomEmployees || []).map(emp => `<option value="${escapeHtml(emp.id)}">${escapeHtml(displayEmployeeCode(emp.employee_code))} - ${escapeHtml(emp.english_name || 'Unnamed Employee')}</option>`))
            .join('');

        select.html(options).val(selectedEmployeeId ? String(selectedEmployeeId) : '');
        initializeReservedBySelect();
        select.trigger('change.select2');
        toggleReservedEmployeeField();
    });
}

function toggleReservedEmployeeField() {
    const status = $('#status').val();
    const group = $('#reservedEmployeeGroup');
    const select = $('#reserved_by_employee_id');
    if (status === 'Reserved' || select.val()) {
        group.show();
        if (!select.data('select2')) {
            initializeReservedBySelect();
        }
    } else {
        group.hide();
    }
}

function openRoomModal(room)
{
    resetRoomForm();
    loadEmployeesForRoomReservation(room ? room.reserved_by_employee_id : '')
        .done(function() {
            if (room) {
                $('#roomId').val(room.id);
                $('#roomModalLabel').text('Edit Room');
                $('#accommodation_id').val(room.accommodation_id || '');
                const assignedNames = String(room.assigned_employee_names || '')
                    .split('\n')
                    .map(name => name.trim())
                    .filter(Boolean);
                const reservationOwnerName = room.reserved_by_employee_name || '';
                roomReservationOriginal = room.reserved_by_employee_id
                    ? {
                        status: room.status || '',
                        employeeId: String(room.reserved_by_employee_id),
                        employeeName: reservationOwnerName,
                        roomNo: room.room_no || '',
                        occupantName: room.status === 'Occupied'
                            ? assignedNames.find(name => name === reservationOwnerName) || ''
                            : ''
                    }
                    : null;

                loadBuildingsForModal(room.building_id || '', function() {
                    loadFloorsForModal(room.floor_id || '', function() {
                        $('#room_no').val(room.room_no);
                        $('#room_type').val(room.room_type);
                        $('#capacity').val(room.capacity);
                        $('#status').val(room.status);
                        $('#reserved_by_employee_id').val(room.reserved_by_employee_id || '').trigger('change.select2');
                        toggleReservedEmployeeField();
                        $('#gender_restriction').val(room.gender_restriction || '');
                        $('#remarks').val(room.remarks || '');
                        $('#roomModal').modal('show');
                    });
                });

                return;
            }

            if (preselectedAccommodationId) {
                $('#accommodation_id').val(preselectedAccommodationId);
                loadBuildingsForModal(null, function() {
                    $('#roomModal').modal('show');
                });
            } else {
                $('#roomModal').modal('show');
            }
        })
        .fail(function() {
            swalError('Unable to load employees for room reservations.');
        });
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

    if (roomSavePending) {
        return;
    }

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

    const roomId = $('#roomId').val();
    const reservationChange = roomId ? getReservationChange() : null;
    if (!reservationChange) {
        saveRoomRequest(false);
        return;
    }

    roomSavePending = true;
    swalConfirmReservationChange(reservationChange.alert)
        .then(result => {
            if (!result.isConfirmed) {
                restoreOriginalReservationFields();
                roomSavePending = false;
                return;
            }

            saveRoomRequest(reservationChange.type === 'remove');
        })
        .catch(error => {
            roomSavePending = false;
            console.error('Reservation confirmation failed:', error);
            swalError('Unable to confirm this reservation change.');
        });
}

function getReservationChange() {
    if (!roomReservationOriginal || !roomReservationOriginal.employeeId) {
        return null;
    }

    const status = String($('#status').val() || '');
    const employeeId = String($('#reserved_by_employee_id').val() || '');
    const statusRemovesReservation =
        status !== roomReservationOriginal.status && status !== 'Reserved';
    const reservationRemoved = !employeeId && status !== 'Reserved';
    const reservationReassigned =
        employeeId && employeeId !== roomReservationOriginal.employeeId;

    if (statusRemovesReservation || reservationRemoved) {
        const occupantLine = roomReservationOriginal.occupantName
            ? `<p>Currently occupied by ${escapeHtml(roomReservationOriginal.occupantName)}.</p>`
            : '';
        const removalReason = statusRemovesReservation
            ? 'Changing the status will remove this reservation.'
            : 'Clearing Reserved By will remove this reservation.';
        return {
            type: 'remove',
            alert: {
                title: 'Remove reservation?',
                html: `<p>Room ${escapeHtml(roomReservationOriginal.roomNo)} is reserved for ${escapeHtml(roomReservationOriginal.employeeName)}. ${removalReason}</p>${occupantLine}`,
                confirmText: 'Yes, remove reservation'
            }
        };
    }

    if (reservationReassigned) {
        const newEmployee = roomEmployees.find(
            employee => String(employee.id) === employeeId
        );
        const selectedEmployeeText = $('#reserved_by_employee_id option:selected').text();
        const employeeCode = newEmployee ? displayEmployeeCode(newEmployee.employee_code) : '';
        const newEmployeeName = employeeCode && selectedEmployeeText.startsWith(`${employeeCode} - `)
            ? selectedEmployeeText.slice(employeeCode.length + 3)
            : selectedEmployeeText;
        const occupantLine = roomReservationOriginal.occupantName
            ? `<p>Currently occupied by ${escapeHtml(roomReservationOriginal.occupantName)}.</p>`
            : '';
        return {
            type: 'transfer',
            alert: {
                title: 'Change reservation?',
                html: `<p>Room ${escapeHtml(roomReservationOriginal.roomNo)} is reserved for ${escapeHtml(roomReservationOriginal.employeeName)}. Do you want to reassign it to ${escapeHtml(newEmployeeName)}?</p>${occupantLine}`,
                confirmText: 'Yes, change reservation'
            }
        };
    }

    return null;
}

function restoreOriginalReservationFields() {
    if (!roomReservationOriginal) {
        return;
    }

    $('#status').val(roomReservationOriginal.status);
    $('#reserved_by_employee_id').val(roomReservationOriginal.employeeId).trigger('change.select2');
    toggleReservedEmployeeField();
}

function saveRoomRequest(removeReservation) {
    roomSavePending = true;
    const id = $('#roomId').val();
    const url = id ? `${roomsApiUrl}/${id}` : roomsApiUrl;
    const method = id ? 'PUT' : 'POST';

    if (removeReservation) {
        $('#reserved_by_employee_id').val('').trigger('change.select2');
    }

    $.ajax({
        url: url,
        type: method,
        data: $('#roomForm').serialize(),
        success: function(response) {
            const payload = typeof response === 'string' ? JSON.parse(response) : response;
            if (payload && payload.success === false) {
                roomSavePending = false;
                swalError(payload.error || 'Unknown error');
                return;
            }
            loadRooms();
            $('#roomModal').modal('hide');
            currentRoomTab = 'all';
            roomSavePending = false;
            swalSuccess('Room saved successfully');
        },
        error: function(xhr) {
            roomSavePending = false;
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
    preselectedAccommodationId = getUrlParameter('accommodation_id');
    loadAccommodations();
    loadRooms();
    $(document).on('click', '.employee-room-link', function(event) {
        event.preventDefault();
        showEmployeeRoom(
            $(this).data('room-id'),
            $(this).data('employee-name'),
            $(this).data('type')
        );
    });
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
    $('#roomModal').on('hidden.bs.modal', function() {
        const select = $('#reserved_by_employee_id');
        if (select.data('select2')) {
            select.select2('destroy');
        }
        roomReservationOriginal = null;
        roomSavePending = false;
    });
    $('#roomModal').on('shown.bs.modal', initializeReservedBySelect);
    $('#roomModal .modal-body').on('scroll.reservedBySelect2', repositionReservedByDropdown);
    $(window).on('resize.reservedBySelect2', repositionReservedByDropdown);
    $('#accommodation_id').on('change', loadBuildingsForModal);
    $('#building_id').on('change', loadFloorsForModal);
    $('#status').on('change', function() {
        const status = $(this).val();
        if (status !== 'Reserved') {
            $('#reserved_by_employee_id').val('').trigger('change.select2');
        }
        toggleReservedEmployeeField();
    });
    $('#reserved_by_employee_id').on('change', toggleReservedEmployeeField);
    $('#room_type').on('change', function() {
        applyRoomTypeCapacity($(this).val());
    });
});
