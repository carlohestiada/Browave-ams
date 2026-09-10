function apiUrl(path) {
    const base = window.location.pathname.replace(/\/[^\/]+$/, '');
    return base + '/' + path.replace(/^\/+/, '');
}

const requestStatuses = ['Pending', 'Scheduled', 'Picked Up', 'Completed', 'Cancelled'];
const transportationTypes = ['Company Car', 'Airport Transfer', 'Shuttle Service', 'Private Hire', 'Other'];
const pageSize = 12;
let transportationRows = [];
let allTransportationRows = [];
let currentPage = 1;
let currentScheduleView = 'active';
let employeeSearchTimer = null;
let filterEmployeeData = [];
let modalMode = 'create';
let selectedTransportationIds = new Set();
let tripDetailsRestoreTripId = null;

function escapeTripHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/\"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function formatBadge(status) {
    const key = String(status || 'Pending').toLowerCase().replace(/ /g, '-');
    return `<span class="badge status-badge status-${key}">${status}</span>`;
}

function formatTripType(tripType) {
    if (!tripType) {
        return `<span class="trip-type-badge trip-type-normal">Normal Trip</span>`;
    }

    const display = tripType === 'ROUND_TRIP' ? 'Round Trip' : 'Normal Trip';
    const className = tripType === 'ROUND_TRIP' ? 'trip-type-round' : 'trip-type-normal';
    return `<span class="trip-type-badge ${className}">${display}</span>`;
}

function formatEmployeeName(row) {
    const chinese = row.chinese_name ? ` <span class="text-muted">(${row.chinese_name})</span>` : '';
    return `${row.employee_code} - ${row.english_name}${chinese}`;
}

function isRowOverdue(row) {
    if (!row.pickup_date || !row.pickup_time) {
        return false;
    }

    if (!['Pending', 'Scheduled'].includes(row.status)) {
        return false;
    }

    const scheduled = new Date(`${row.pickup_date}T${row.pickup_time}`);
    return scheduled.getTime() > 0 && scheduled < new Date();
}

function loadStats() {
    $.get(apiUrl('api/company-car/index.php?stats=1'), function(data) {
        const stats = typeof data === 'string' ? JSON.parse(data) : data;
        $('#kpiCompanyCarRequest').text(stats.todays_requests ?? 0);
        $('#kpi-companycar-scheduled').text(stats.scheduled_today ?? 0);
        $('#kpiScheduledToday').text(stats.scheduled_today ?? 0);
        $('#kpiAvailableVehicles').text(stats.available_vehicles ?? 0);
    });
}

function loadDrivers() {
    $.get(apiUrl('api/drivers/index.php'), function(data) {
        const drivers = typeof data === 'string' ? JSON.parse(data) : data;
        let options = '<option value="">All drivers</option>';
        let modalOptions = '<option value="">Select driver</option>';
        let bulkOptions = '<option value="">Select driver</option>';
        const activeDrivers = drivers.filter(driver => String(driver.status || '').toLowerCase() === 'active');

        activeDrivers.forEach(driver => {
            const label = `${driver.driver_name}${driver.phone ? ' (' + driver.phone + ')' : ''}`;
            options += `<option value="${driver.id}">${label}</option>`;
            modalOptions += `<option value="${driver.id}">${label}</option>`;
            bulkOptions += `<option value="${driver.id}">${label}</option>`;
        });

        $('#filterDriver').html(options);
        $('#companyCar_driver_id').html(modalOptions);
        $('#bulkDriverId').html(bulkOptions);
        $('#kpiAvailableDrivers').text(activeDrivers.length);
    });
}

function loadVehicles() {
    $.get(apiUrl('api/vehicles/index.php'), function(data) {
        const vehicles = typeof data === 'string' ? JSON.parse(data) : data;
        let options = '<option value="">All vehicles</option>';
        let modalOptions = '<option value="">Select vehicle</option>';
        let bulkOptions = '<option value="">Select vehicle</option>';

        vehicles.forEach(vehicle => {
            const label = `${vehicle.vehicle_name}${vehicle.license_plate ? ' (' + vehicle.license_plate + ')' : ''}`;
            options += `<option value="${vehicle.id}">${label}</option>`;
            modalOptions += `<option value="${vehicle.id}">${label}</option>`;
            bulkOptions += `<option value="${vehicle.id}">${label}</option>`;
        });

        $('#filterVehicle').html(options);
        $('#companyCar_vehicle_id').html(modalOptions);
        $('#bulkVehicleId').html(bulkOptions);
    });
}

function openManageDrivers() {
    $('#driverManageList').html('Loading drivers...');
    $.get(apiUrl('api/drivers/index.php'), function(data) {
        const drivers = typeof data === 'string' ? JSON.parse(data) : data;
        renderDriverManageList(drivers);
        const modal = new bootstrap.Modal(document.getElementById('driverManageModal'));
        modal.show();
    }).fail(function() {
        swalError('Unable to load drivers');
    });
}

function renderDriverManageList(drivers) {
    if (!drivers || drivers.length === 0) {
        $('#driverManageList').html('<div class="text-muted">No drivers found.</div>');
        return;
    }

    let html = '<div class="list-group">';
    drivers.forEach(d => {
        html += `
            <div class="list-group-item d-flex justify-content-between align-items-center">
                <div>
                    <div class="fw-semibold">${d.driver_name || ''}</div>
                    <div class="text-muted small">${d.phone || ''} ${d.status ? ' • ' + d.status : ''}</div>
                </div>
                <div>
                    <button type="button" class="btn btn-sm btn-danger" data-id="${d.id}" data-action="delete-driver">Delete</button>
                </div>
            </div>
        `;
    });
    html += '</div>';
    $('#driverManageList').html(html);

    $('#driverManageList button[data-action="delete-driver"]').on('click', function() {
        const id = $(this).data('id');
        deleteDriver(id);
    });
}

function deleteDriver(id) {
    swalConfirm('Delete this driver?', function() {
        $.ajax({
            url: apiUrl(`api/drivers/index.php/${id}`),
            type: 'DELETE',
            success: function(response) {
                const result = typeof response === 'string' ? JSON.parse(response) : response;
                if (result.success) {
                    loadDrivers();
                    swalSuccess('Driver deleted successfully');
                    openManageDrivers();
                } else {
                    swalError(result.error || 'Unable to delete driver');
                }
            },
            error: function(xhr) {
                swalError(xhr.responseJSON?.error || xhr.responseText || 'Unable to delete driver');
            }
        });
    });
}

function openManageVehicles() {
    $('#vehicleManageList').html('Loading vehicles...');
    $.get(apiUrl('api/vehicles/index.php'), function(data) {
        const vehicles = typeof data === 'string' ? JSON.parse(data) : data;
        renderVehicleManageList(vehicles);
        const modal = new bootstrap.Modal(document.getElementById('vehicleManageModal'));
        modal.show();
    }).fail(function() {
        swalError('Unable to load vehicles');
    });
}

function renderVehicleManageList(vehicles) {
    if (!vehicles || vehicles.length === 0) {
        $('#vehicleManageList').html('<div class="text-muted">No vehicles found.</div>');
        return;
    }

    let html = '<div class="list-group">';
    vehicles.forEach(v => {
        html += `
            <div class="list-group-item d-flex justify-content-between align-items-center">
                <div>
                    <div class="fw-semibold">${v.vehicle_name || ''}</div>
                    <div class="text-muted small">${v.license_plate || ''} ${v.status ? ' • ' + v.status : ''}</div>
                </div>
                <div>
                    <button type="button" class="btn btn-sm btn-danger" data-id="${v.id}" data-action="delete-vehicle">Delete</button>
                </div>
            </div>
        `;
    });
    html += '</div>';
    $('#vehicleManageList').html(html);

    $('#vehicleManageList button[data-action="delete-vehicle"]').on('click', function() {
        const id = $(this).data('id');
        deleteVehicle(id);
    });
}

function deleteVehicle(id) {
    swalConfirm('Delete this vehicle?', function() {
        $.ajax({
            url: apiUrl(`api/vehicles/index.php/${id}`),
            type: 'DELETE',
            success: function(response) {
                const result = typeof response === 'string' ? JSON.parse(response) : response;
                if (result.success) {
                    loadVehicles();
                    swalSuccess('Vehicle deleted successfully');
                    openManageVehicles();
                } else {
                    swalError(result.error || 'Unable to delete vehicle');
                }
            },
            error: function(xhr) {
                swalError(xhr.responseJSON?.error || xhr.responseText || 'Unable to delete vehicle');
            }
        });
    });
}

function loadEmployees(search = '', targetList = '#filterEmployeeList', inputId = '#filterEmployeeSearch', hiddenId = '#filterEmployeeId') {
    const params = [];
    const query = search.trim();

    if (query) {
        params.push('search=' + encodeURIComponent(query));
    }
    params.push('status=Active');

    $.get(apiUrl('api/employees/index.php' + (params.length ? '?' + params.join('&') : '')), function(data) {
        const employees = typeof data === 'string' ? JSON.parse(data) : data;
        filterEmployeeData = employees;

        const list = $(targetList);
        let html = '';

        if (employees.length === 0) {
            html = '<div class="dropdown-item" style="color:#999;">No employees found</div>';
        } else {
            employees.forEach(emp => {
                    html += `<div class="dropdown-item" data-id="${emp.id}" data-label="${emp.employee_code} - ${emp.english_name}">${emp.employee_code} - ${emp.english_name}</div>`;
            });
        }

        list.html(html).addClass('show');
        $(`${targetList} .dropdown-item`).on('click', function() {
            const id = $(this).attr('data-id');
            const label = $(this).data('label');

            $(inputId).val(label);
            $(hiddenId).val(id);
            list.removeClass('show');

            if (hiddenId === '#filterEmployeeId') {
                loadTransportationSchedule();
            } else {
                fetchEmployeeDetails(id);
            }
        });
    });
}

function fetchEmployeeDetails(employeeId, tripLegId = '') {
    const normalizedEmployeeId = String(employeeId || '').trim();
    console.log('Selected Employee ID:', normalizedEmployeeId);

    if (!normalizedEmployeeId) {
        clearEmployeeDetails();
        return;
    }

    const tripLegQuery = tripLegId ? `?trip_leg_id=${encodeURIComponent(tripLegId)}` : '';
    $.get(apiUrl(`api/company-car/index.php/employee/${encodeURIComponent(normalizedEmployeeId)}${tripLegQuery}`), function(data) {
        const employee = typeof data === 'string' ? JSON.parse(data) : data;

        if (!employee || !employee.id) {
            console.error('Employee details API returned an invalid employee:', employee);
            return;
        }

        $('#companyCar_employee_id').val(employee.id || '');
        $('#companyCar_employee_search').val(`${employee.employee_code || ''} - ${employee.english_name || ''}`);
        $('#companyCar_department').val(employee.department_name || '');
        $('#companyCar_chinese_name').val(employee.chinese_name || '');
        $('#companyCar_gender').val(employee.gender || '');
        $('#companyCar_arrival_date').val(employee.last_arrival_date ? employee.last_arrival_date.split(' ')[0] : '');
        $('#companyCar_departure_date').val(employee.last_departure_date ? employee.last_departure_date.split(' ')[0] : '');
        $('#companyCar_accommodation_room').val([employee.accommodation_name, employee.room_number].filter(Boolean).join(' / '));
    }).fail(function(xhr) {
        console.error('Employee details API failed:', {
            status: xhr.status,
            response: xhr.responseText
        });

        if (xhr.status === 404) {
            clearEmployeeDetails();
        }
    });
}

function clearEmployeeDetails() {
    $('#companyCar_employee_id').val('');
    $('#companyCar_department').val('');
    $('#companyCar_chinese_name').val('');
    $('#companyCar_gender').val('');
    $('#companyCar_arrival_date').val('');
    $('#companyCar_departure_date').val('');
    $('#companyCar_accommodation_room').val('');
}

function getViewStatusFilter() {
    if (currentScheduleView === 'archive') {
        return ['COMPLETED'];
    }

    return ['SCHEDULED', 'IN_PROGRESS'];
}

function applyScheduleViewFilter(rows) {
    const allowedStatuses = getViewStatusFilter();
    return rows.filter(row => {
        const tripStatus = String(row.trip_status || row.status || 'SCHEDULED').toUpperCase().replace(/\s+/g, '_');
        return allowedStatuses.includes(tripStatus);
    });
}

function setScheduleView(view) {
    currentScheduleView = view;

    $('.schedule-view-tab').removeClass('active');
    $(`.schedule-view-tab[data-view="${view}"]`).addClass('active');

    transportationRows = applyScheduleViewFilter(allTransportationRows);
    selectedTransportationIds.clear();
    currentPage = 1;
    renderTable();
    renderTimeline();

    $('#scheduleCount').text(`${transportationRows.length} trips found`);
    $('#scheduleViewSummary').text(currentScheduleView === 'archive' ? 'Showing completed and cancelled requests' : 'Showing active requests');
}

function loadTransportationSchedule() {
    const params = [];
    const employeeId = $('#filterEmployeeId').val();
    const pickupDate = $('#filterPickupDate').val();
    const type = $('#filterTransportationType').val();
    const vehicleId = $('#filterVehicle').val();
    const driverId = $('#filterDriver').val();
    const status = $('#filterStatus').val();
    const legType = $('#filterLegType').val();

    if (employeeId) params.push(`employee_id=${encodeURIComponent(employeeId)}`);
    if (pickupDate) params.push(`pickup_date=${encodeURIComponent(pickupDate)}`);
    if (type) params.push(`transportation_type=${encodeURIComponent(type)}`);
    if (vehicleId) params.push(`vehicle_id=${encodeURIComponent(vehicleId)}`);
    if (driverId) params.push(`driver_id=${encodeURIComponent(driverId)}`);
    if (status) params.push(`status=${encodeURIComponent(status)}`);
    if (legType) params.push(`leg_type=${encodeURIComponent(legType)}`);

    const url = apiUrl('api/company-car/index.php' + (params.length ? '?' + params.join('&') : ''));

    $.get(url, function(data) {
        const rows = typeof data === 'string' ? JSON.parse(data) : data;
        allTransportationRows = rows || [];
        transportationRows = applyScheduleViewFilter(allTransportationRows);
        selectedTransportationIds.clear();
        currentPage = 1;
        renderTable();
        renderTimeline();
        $('#scheduleCount').text(`${transportationRows.length} trips found`);
        $('#scheduleViewSummary').text(currentScheduleView === 'archive' ? 'Showing completed and cancelled requests' : 'Showing active requests');
    });
}

function updateTransportationSelectionControls() {
    const selectedCount = selectedTransportationIds.size;
    const rowCheckboxes = $('.transportation-select-checkbox');
    const checkedCount = rowCheckboxes.filter(':checked').length;
    const selectAll = document.getElementById('selectAllTransportation');

    $('#selectedTransportationText').text(`${selectedCount} selected`);
    $('#bulkDeleteTransportationBtn').prop('disabled', selectedCount === 0);

    if (selectAll) {
        selectAll.checked = rowCheckboxes.length > 0 && checkedCount === rowCheckboxes.length;
        selectAll.indeterminate = checkedCount > 0 && checkedCount < rowCheckboxes.length;
    }
}

function toggleTransportationSelection(id, checked) {
    if (checked) {
        selectedTransportationIds.add(String(id));
    } else {
        selectedTransportationIds.delete(String(id));
    }

    updateTransportationSelectionControls();
}

function toggleAllTransportation(checked) {
    $('.transportation-select-checkbox').each(function() {
        this.checked = checked;

        if (checked) {
            selectedTransportationIds.add(String(this.value));
        } else {
            selectedTransportationIds.delete(String(this.value));
        }
    });

    updateTransportationSelectionControls();
}

function renderTable() {
    const start = (currentPage - 1) * pageSize;
    const rows = transportationRows.slice(start, start + pageSize);
    const body = $('#companyCarTableBody');
    let html = '';

    rows.forEach(row => {
        const overdue = isRowOverdue(row) ? 'overdue-row' : '';
        const checked = selectedTransportationIds.has(String(row.id)) ? 'checked' : '';
        const tripLabel = row.trip_id ? `Trip #${row.trip_id}` : '<span class="text-muted">Legacy / Unlinked</span>';
        const overallStatus = String(row.trip_status || row.status || 'SCHEDULED').toUpperCase().replace(/\s+/g, '_');
        const arrivalDate = row.arrival_date || '';
        const departureDate = row.departure_date || '';
        const tripType = formatTripType(row.trip_type || 'NORMAL_TRIP');
        const transportId = row.id || row.transportation_id || row.trip_id;

        html += `
            <tr class="${overdue}">
                <td style="text-align:center;">
                    <input
                        type="checkbox"
                        class="transportation-select-checkbox"
                        value="${transportId}"
                        aria-label="Select transportation request"
                        onchange="toggleTransportationSelection(${transportId}, this.checked)"
                        ${checked}>
                </td>
                <td>${formatEmployeeName(row)}</td>
                <td>${row.department_name || ''}</td>
                <td>${tripLabel}</td>
                <td>${tripType}</td>
                <td>${arrivalDate}</td>
                <td>${departureDate}</td>
                <td>${row.transportation_type || ''}</td>
                <td>${row.driver_name || ''}</td>
                <td>${row.vehicle_name || ''}</td>
                <td>${formatBadge(overallStatus)}</td>
                <td style="white-space:nowrap;">
                    <button type="button" class="btn btn-sm btn-secondary me-1" data-action="view" data-id="${row.trip_id}">View Details</button>
                    <button type="button" class="btn btn-sm btn-warning me-1" data-action="edit" data-id="${transportId}">Edit</button>
                    <button type="button" class="btn btn-sm btn-danger me-1" data-action="delete" data-id="${transportId}">Delete</button>
                </td>
            </tr>
        `;
    });

    body.html(html || '<tr><td colspan="12" class="text-center text-muted">No transportation requests found.</td></tr>');
    renderPagination();
    $('#tableSummary').text(`Showing ${rows.length} of ${transportationRows.length} records`);
    bindRowActions();
    updateTransportationSelectionControls();
}

function renderPagination() {
    const totalPages = Math.max(1, Math.ceil(transportationRows.length / pageSize));
    const nav = $('#schedulePagination');
    let html = '';

    for (let page = 1; page <= totalPages; page++) {
        html += `<li class="page-item ${page === currentPage ? 'active' : ''}"><button type="button" class="page-link" data-page="${page}">${page}</button></li>`;
    }

    nav.html(html);
    nav.find('[data-page]').on('click', function() {
        currentPage = Number($(this).data('page')) || 1;
        renderTable();
    });
}

function bindRowActions() {
    $('#companyCarTableBody button[data-action]').on('click', function() {
        const action = $(this).data('action');
        const id = $(this).data('id');

        if (action === 'view') {
            openTripDetailsModal(id);
        } else if (action === 'edit') {
            openModal('edit', id);
        } else if (action === 'delete') {
            confirmDelete(id);
        } else if (action === 'assign') {
            openModal('assign', id);
        }
    });
}

function openModal(mode, id = null) {
    modalMode = mode;
    const modalTitle = {
        create: 'Assign Transportation',
        edit: 'Edit Transportation',
        view: 'View Transportation',
        assign: 'Assign Driver & Vehicle'
    }[mode] || 'Assign Transportation';

    if (mode === 'edit' && id) {
        const tripDetailsModalEl = document.getElementById('tripDetailsModal');
        const tripDetailsModalInstance = bootstrap.Modal.getInstance(tripDetailsModalEl) || new bootstrap.Modal(tripDetailsModalEl);
        const tripDetailsVisible = tripDetailsModalEl && tripDetailsModalEl.classList.contains('show');
        if (tripDetailsVisible) {
            tripDetailsRestoreTripId = Number($('#tripDetailsModal').data('trip-id')) || null;
            tripDetailsModalInstance.hide();
        }
    }

    $('#companyCarModalLabel').text(modalTitle);
    $('#companyCarForm')[0].reset();
    clearEmployeeDetails();
    $('#companyCar_id').val('');
    $('#companyCar_trip_leg_id').val('');
    $('#companyCar_status').val('Pending');
    $('#companyCar_transportation_type').val('Company Car');
    $('#companyCar_trip_type').val('NORMAL_TRIP');

    if (mode === 'view') {
        $('#companyCarForm input, #companyCarForm select, #companyCarForm textarea').prop('disabled', true);
        $('#companyCarSaveButton').hide();
    } else {
        $('#companyCarForm input, #companyCarForm select, #companyCarForm textarea').prop('disabled', false);
        $('#companyCar_employee_search').prop('disabled', false);
        $('#companyCarSaveButton').show();
    }

    if (mode === 'create') {
        $('#companyCar_status').val('Pending');
        $('#companyCar_pickup_date').val(new Date().toISOString().slice(0, 10));
        $('#companyCar_pickup_time').val('08:00');

        const query = new URLSearchParams(window.location.search);
        const tripLegId = query.get('trip_leg_id');
        const employeeId = query.get('employee_id');
        const pickupDate = query.get('pickup_date');
        
        if (tripLegId) {
            $('#companyCar_trip_leg_id').val(tripLegId);
            // Phase 4: Fetch and display trip leg context
            $.get(apiUrl(`api/trip-legs/index.php/${tripLegId}`), function(data) {
                const tripLeg = typeof data === 'string' ? JSON.parse(data) : data;
                if (tripLeg) {
                    const route = `${tripLeg.origin || '—'} → ${tripLeg.destination || '—'}`;
                    $('#tripContextTripId').text(`#${tripLeg.trip_id || '—'}`);
                    $('#tripContextEmployee').text(employeeId ? `${employeeId}` : '—');
                    $('#tripContextLegType').text(tripLeg.leg_type || '—');
                    $('#tripContextDate').text(tripLeg.leg_date || '—');
                    $('#tripContextRoute').text(route);
                    $('#tripContextSection').removeClass('d-none');
                }
            });
        } else {
            $('#tripContextSection').addClass('d-none');
        }
        
        if (pickupDate) $('#companyCar_pickup_date').val(pickupDate);
        if (employeeId) fetchEmployeeDetails(employeeId, tripLegId);
    }

    if (id) {
        $.get(apiUrl(`api/company-car/index.php/${id}`), function(data) {
            const row = typeof data === 'string' ? JSON.parse(data) : data;
            $('#companyCar_id').val(row.id || '');
            $('#companyCar_trip_leg_id').val(row.trip_leg_id || '');
            $('#companyCar_employee_id').val(row.employee_id || '');
            $('#companyCar_employee_search').val(formatEmployeeName(row));
            $('#companyCar_transportation_type').val(row.transportation_type || 'Company Car');
            $('#companyCar_trip_type').val(row.trip_type || 'NORMAL_TRIP');
            $('#companyCar_driver_id').val(row.driver_id || '');
            $('#companyCar_vehicle_id').val(row.vehicle_id || '');
            $('#companyCar_pickup_date').val(row.pickup_date || new Date().toISOString().slice(0, 10));
            $('#companyCar_pickup_time').val(row.pickup_time || '08:00');
            $('#companyCar_pickup_location').val(row.pickup_location || '');
            $('#companyCar_status').val(row.status || 'Pending');
            $('#companyCar_remarks').val(row.remarks || '');
            fetchEmployeeDetails(row.employee_id, row.trip_leg_id);

            // Phase 4: Show trip context if linked to trip leg
            if (row.trip_leg_id) {
                const route = `${row.origin || '—'} → ${row.destination || '—'}`;
                $('#tripContextTripId').text(`#${row.trip_id || '—'}`);
                $('#tripContextEmployee').text(`${row.employee_code} - ${row.english_name}`);
                $('#tripContextLegType').text(row.leg_type || '—');
                $('#tripContextDate').text(row.leg_date || '—');
                $('#tripContextRoute').text(route);
                $('#tripContextSection').removeClass('d-none');
            } else {
                $('#tripContextSection').addClass('d-none');
            }

            if (mode === 'assign') {
                $('#companyCar_status').val('Scheduled');
            }

            const modal = new bootstrap.Modal(document.getElementById('companyCarModal'));
            modal.show();
        }).fail(function() {
            swalError('Unable to load transportation request details');
        });
    } else {
        const modal = new bootstrap.Modal(document.getElementById('companyCarModal'));
        modal.show();
    }
}

function openTripDetailsModal(tripId) {
    if (!tripId) return;
    tripDetailsRestoreTripId = Number(tripId) || null;
    $.get(apiUrl(`api/company-car/index.php/trip/${encodeURIComponent(tripId)}`), function(response) {
        const payload = typeof response === 'string' ? JSON.parse(response) : response;
        const trip = payload?.data || payload;
        if (!trip) {
            swalError('Unable to load trip details.');
            return;
        }

        const legs = Array.isArray(trip.legs) ? trip.legs : [];
        const assigned = legs.filter((leg) => leg.transportation_id).length;
        const pending = Math.max(legs.length - assigned, 0);
        const accommodation = trip.accommodation_name || trip.room_number || '—';

        const legRows = legs.map((leg) => `
            <tr>
                <td>${escapeTripHtml(leg.leg_type || '—')}</td>
                <td>${escapeTripHtml(leg.leg_date || '—')}</td>
                <td>${escapeTripHtml(leg.origin || '—')}</td>
                <td>${escapeTripHtml(leg.destination || '—')}</td>
                <td>${escapeTripHtml(leg.arrival_airport || leg.departure_airport || '—')}</td>
                <td>
                    ${leg.transportation_id ? `
                        <div class="text-sm">
                            <strong>${escapeTripHtml(leg.transportation_type || '—')}</strong><br>
                            ${leg.driver_name ? `${escapeTripHtml(leg.driver_name)}<br>` : ''}
                            ${leg.vehicle_name ? `${escapeTripHtml(leg.vehicle_name)}<br>` : ''}
                            <span class="badge status-badge status-${String(leg.status || '').toLowerCase()}">${escapeTripHtml(leg.status || 'Pending')}</span><br>
                            <button type="button" class="btn btn-sm btn-outline-primary mt-2 btn-edit-transportation" data-transportation-id="${leg.transportation_id}">Edit</button>
                            <button type="button" class="btn btn-sm btn-outline-danger mt-2 delete-transportation" data-id="${leg.transportation_id}">Delete</button>
                        </div>
                    ` : `<div class="text-muted"><em>No transportation assigned</em><br><a class="btn btn-sm btn-outline-primary mt-2" href="company-car.php?trip_leg_id=${encodeURIComponent(leg.trip_leg_id)}&employee_id=${encodeURIComponent(trip.employee_id)}&pickup_date=${encodeURIComponent(leg.leg_date)}">+ Add Transportation</a></div>`}
                </td>
            </tr>`).join('');

        const html = `<div class="row g-3 mb-4">
            <div class="col-md-3"><strong>Employee</strong><br>${formatEmployeeName(trip)}</div>
            <div class="col-md-3"><strong>Employee ID</strong><br>${trip.employee_code || trip.employee_id || '—'}</div>
            <div class="col-md-3"><strong>Department</strong><br>${trip.department_name || '—'}</div>
            <div class="col-md-3"><strong>Accommodation Room</strong><br>${escapeTripHtml(accommodation)}</div>
            <div class="col-md-3"><strong>Trip Type</strong><br>${formatTripType(trip.trip_type || 'NORMAL_TRIP')}</div>
            <div class="col-md-3"><strong>Status</strong><br>${formatBadge(trip.trip_status || trip.status || 'Pending')}</div>
            <div class="col-md-6"><strong>Remarks</strong><br>${escapeTripHtml(trip.remarks || '—')}</div>
        </div>
        <div class="alert alert-info" style="margin-bottom:1rem;">
          <strong>Transportation Summary</strong>
          <div class="mt-2">
            <small>
              <strong>Total Trip Legs:</strong> ${legs.length}<br>
              <strong>Transportation Assigned:</strong> <span style="color:green;">${assigned}</span><br>
              <strong>Transportation Pending:</strong> <span style="color:orange;">${pending}</span>
            </small>
          </div>
        </div>
        <h6>Trip Legs & Transportation</h6>
        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead><tr><th>Type</th><th>Date</th><th>Origin</th><th>Destination</th><th>Airport</th><th>Transportation</th></tr></thead>
            <tbody>${legRows}</tbody>
          </table>
        </div>`;

        $('#tripDetailsContent').html(html);
        $('#tripDetailsModal').data('trip-id', trip.trip_id);
        $('#tripDetailsModal').data('arrival-status', trip.legs?.find(leg => leg.leg_type === 'ARRIVAL')?.status || 'Pending');
        $('#tripDetailsModal').data('departure-status', trip.legs?.find(leg => leg.leg_type === 'DEPARTURE')?.status || 'Pending');
        const modal = bootstrap.Modal.getInstance(document.getElementById('tripDetailsModal')) || new bootstrap.Modal(document.getElementById('tripDetailsModal'));
        modal.show();
    }).fail(function(xhr) {
        swalError(getAjaxErrorMessage(xhr, 'Unable to load trip details.'));
    });
}

$('#saveTripDetailsStatusBtn').on('click', function() {
    const tripId = Number($('#tripDetailsModal').data('trip-id'));
    if (!tripId) return;

    const arrivalStatus = $('#tripDetailsModal .trip-leg-status[data-leg-type="ARRIVAL"]').val();
    const departureStatus = $('#tripDetailsModal .trip-leg-status[data-leg-type="DEPARTURE"]').val();

    $.ajax({
        url: apiUrl(`api/company-car/index.php/trip/${tripId}`),
        type: 'PUT',
        data: {
            arrival_status: arrivalStatus,
            departure_status: departureStatus
        },
        success: function(response) {
            const result = typeof response === 'string' ? JSON.parse(response) : response;
            if (result.success) {
                const modal = bootstrap.Modal.getInstance(document.getElementById('tripDetailsModal'));
                if (modal) modal.hide();
                loadTransportationSchedule();
                loadStats();
                swalSuccess('Trip leg statuses saved successfully');
            } else {
                swalError(result.error || 'Unable to save leg statuses');
            }
        },
        error: function(xhr) {
            swalError(getAjaxErrorMessage(xhr, 'Unable to save leg statuses.'));
        }
    });
});

function confirmDelete(id) {
    swalConfirm('Delete this transportation request?', function() {
        $.ajax({
            url: apiUrl(`api/company-car/index.php/${id}`),
            type: 'DELETE',
            success: function(response) {
                const result = typeof response === 'string' ? JSON.parse(response) : response;
                if (result.success) {
                    selectedTransportationIds.delete(String(id));
                    loadTransportationSchedule();
                    loadStats();

                    const tripId = Number($('#tripDetailsModal').data('trip-id')) || Number($('#tripDetailsContent').data('trip-id')) || null;
                    if (tripId) {
                        openTripDetailsModal(tripId);
                    }

                    swalSuccess('Request deleted successfully');
                } else {
                    swalError(result.error || 'Unable to delete request');
                }
            },
            error: function(xhr) {
                swalError(getAjaxErrorMessage(xhr, 'Unable to delete request'));
            }
        });
    });
}

function deleteSelectedTransportation() {
    const ids = Array.from(selectedTransportationIds);

    if (ids.length === 0) {
        swalInfo('Select at least one transportation request to delete.');
        return;
    }

    swalConfirm(`Delete ${ids.length} selected transportation request${ids.length === 1 ? '' : 's'}?`, function() {
        $('#bulkDeleteTransportationBtn').prop('disabled', true).text('Deleting...');

        const deletePromises = ids.map(id => new Promise(resolve => {
            $.ajax({
                url: apiUrl(`api/company-car/index.php/${id}`),
                type: 'DELETE',
                success: function(response) {
                    const result = typeof response === 'string' ? JSON.parse(response) : response;
                    resolve({ success: result.success !== false, id, error: result.error });
                },
                error: function(xhr) {
                    const message = xhr.responseJSON?.error || xhr.responseText || 'Unknown error';
                    resolve({ success: false, id, error: message });
                }
            });
        }));

        Promise.all(deletePromises).then(results => {
            const failed = results.filter(result => !result.success);
            const deletedCount = results.length - failed.length;

            selectedTransportationIds.clear();
            loadTransportationSchedule();
            loadStats();
            $('#bulkDeleteTransportationBtn').text('Delete Selected');

            if (failed.length > 0) {
                const firstError = failed[0].error;
                swalError(`${deletedCount} deleted. ${failed.length} could not be deleted. ${firstError}`, 'Bulk delete incomplete');
                return;
            }

            swalSuccess(`${deletedCount} transportation request${deletedCount === 1 ? '' : 's'} deleted successfully.`);
        });
    });
}

function getAjaxErrorMessage(xhr, fallback = 'Unable to save transportation request.') {
    if (xhr?.responseJSON?.error) {
        return xhr.responseJSON.error;
    }

    if (xhr?.responseText) {
        try {
            const parsed = JSON.parse(xhr.responseText);
            if (parsed?.error) {
                return parsed.error;
            }
        } catch (error) {
            // Ignore parse issues and fall back to the raw response text.
        }

        const cleaned = xhr.responseText.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();
        if (cleaned) {
            return cleaned;
        }
    }

    return fallback;
}

function submitDriverForm() {
    $('#driverForm').on('submit', function(event) {
        event.preventDefault();

        const form = $(this);
        const payload = form.serialize();
        const driverName = $('#driver_name').val()?.trim() || '';

        if (!driverName) {
            swalError('Please enter a driver name before saving.');
            return;
        }

        $.ajax({
            url: apiUrl('api/drivers/index.php'),
            type: 'POST',
            data: payload,
            success: function(response) {
                const result = typeof response === 'string' ? JSON.parse(response) : response;
                if (result.success) {
                    const modalElement = document.getElementById('driverModal');
                    const bsModal = bootstrap.Modal.getInstance(modalElement) || new bootstrap.Modal(modalElement);
                    bsModal.hide();
                    form[0].reset();
                    loadDrivers();
                    $('#companyCar_driver_id').val(result.id);
                    swalSuccess('Driver saved successfully');
                } else {
                    const errorMessage = result.error || 'Unable to save driver.';
                    swalError(`Unable to add driver. ${errorMessage}`);
                }
            },
            error: function(xhr) {
                const error = getAjaxErrorMessage(xhr, 'Unknown server error.');
                const details = error.includes('SQLSTATE') ? 'The server could not save the driver. Please check the data and try again.' : error;
                swalError(`Unable to add driver. ${details}`);
            }
        });
    });
}

function submitVehicleForm() {
    $('#vehicleForm').on('submit', function(event) {
        event.preventDefault();

        const form = $(this);
        const payload = form.serialize();
        const vehicleName = $('#vehicle_name').val()?.trim() || '';
        const vehiclePlate = $('#vehicle_license_plate').val()?.trim() || '';

        if (!vehicleName) {
            swalError('Please enter a vehicle name before saving.');
            return;
        }

        $.ajax({
            url: apiUrl('api/vehicles/index.php'),
            type: 'POST',
            data: payload,
            success: function(response) {
                const result = typeof response === 'string' ? JSON.parse(response) : response;
                if (result.success) {
                    const modalElement = document.getElementById('vehicleModal');
                    const bsModal = bootstrap.Modal.getInstance(modalElement) || new bootstrap.Modal(modalElement);
                    bsModal.hide();
                    form[0].reset();
                    loadVehicles();
                    $('#companyCar_vehicle_id').val(result.id);
                    swalSuccess('Vehicle saved successfully');
                } else {
                    const errorMessage = result.error || 'Unable to save vehicle.';
                    swalError(`Unable to add vehicle. ${errorMessage}`);
                }
            },
            error: function(xhr) {
                const error = getAjaxErrorMessage(xhr, 'Unknown server error.');
                const details = error.includes('SQLSTATE') ? 'The server could not save the vehicle. Please check the data and try again.' : error;
                swalError(`Unable to add vehicle. ${details}`);
            }
        });
    });
}

function loadBulkAssignmentEmployees() {
    $.get(apiUrl('api/employees/index.php?status=Active'), function(data) {
        const employees = typeof data === 'string' ? JSON.parse(data) : data;
        let html = '';

        employees.forEach(employee => {
            html += `
                <div class="form-check">
                    <input class="form-check-input bulk-employee-checkbox" type="checkbox" value="${employee.id}" id="bulkEmployee_${employee.id}">
                    <label class="form-check-label" for="bulkEmployee_${employee.id}">
                        ${employee.employee_code || ''} - ${employee.english_name || ''}
                    </label>
                </div>
            `;
        });

        $('#bulkEmployeeIds').html(html || '<div class="text-muted">No active employees found.</div>');
    });
}

function submitCompanyCarForm() {
    $('#companyCarForm').on('submit', function(event) {
        event.preventDefault();

        const employeeId = $('#companyCar_employee_id').val();
        if (!employeeId) {
            swalError('Please select an employee from the dropdown.');
            return;
        }

        const id = $('#companyCar_id').val();
        const method = id ? 'PUT' : 'POST';
        const requestUrl = id ? apiUrl(`api/company-car/index.php/${id}`) : apiUrl('api/company-car/index.php');

        $.ajax({
            url: requestUrl,
            type: method,
            data: $(this).serialize(),
            success: function(response) {
                const result = typeof response === 'string' ? JSON.parse(response) : response;
                if (result.success) {
                    const modalElement = document.getElementById('companyCarModal');
                    const bsModal = bootstrap.Modal.getInstance(modalElement) || new bootstrap.Modal(modalElement);
                    bsModal.hide();
                    loadTransportationSchedule();
                    loadStats();

                    const tripId = Number($('#tripDetailsModal').data('trip-id')) || null;
                    if (tripId) {
                        openTripDetailsModal(tripId);
                    }

                    swalSuccess('Transportation request saved successfully');
                } else {
                    swalError(result.error || 'Unable to save transportation request');
                }
            },
            error: function(xhr) {
                const error = getAjaxErrorMessage(xhr, 'Unable to save transportation request.');
                const details = error.includes('SQLSTATE') ? 'The server could not save the transportation request. Please check the fields and try again.' : error;
                swalError(details);
            }
        });
    });
}

function submitBulkCompanyCarForm() {
    $('#bulkCompanyCarForm').on('submit', function(event) {
        event.preventDefault();

        const selectedEmployees = $('.bulk-employee-checkbox:checked').map(function() {
            return $(this).val();
        }).get();

        if (!selectedEmployees.length) {
            swalError('Please select at least one employee for the bulk assignment.');
            return;
        }

        const formData = $(this).serializeArray();
        formData.push(...selectedEmployees.map(id => ({ name: 'employee_ids[]', value: id })));

        $.ajax({
            url: apiUrl('api/company-car/index.php/bulk'),
            type: 'POST',
            data: formData,
            success: function(response) {
                const result = typeof response === 'string' ? JSON.parse(response) : response;
                if (result.success) {
                    const modalElement = document.getElementById('bulkCompanyCarModal');
                    const bsModal = bootstrap.Modal.getInstance(modalElement) || new bootstrap.Modal(modalElement);
                    bsModal.hide();
                    $('#bulkCompanyCarForm')[0].reset();
                    loadTransportationSchedule();
                    loadStats();
                    swalSuccess(`${result.count || selectedEmployees.length} transportation request${(result.count || selectedEmployees.length) === 1 ? '' : 's'} saved successfully`);
                } else {
                    swalError(result.error || 'Unable to save bulk transportation requests');
                }
            },
            error: function(xhr) {
                const error = getAjaxErrorMessage(xhr, 'Unable to save bulk transportation requests.');
                swalError(error);
            }
        });
    });
}

function renderTimeline() {
    const today = new Date().toISOString().slice(0, 10);
    const timeline = transportationRows
        .filter(row => row.pickup_date === today)
        .sort((a, b) => (`${a.pickup_time || ''}` > `${b.pickup_time || ''}` ? 1 : -1))
        .slice(0, 6);

    const container = $('#pickupTimeline');
    if (timeline.length === 0) {
        container.html('<div class="text-muted">No pickups scheduled for today.</div>');
        return;
    }

    let html = '';
    timeline.forEach(row => {
        html += `
            <div class="timeline-item ${isRowOverdue(row) ? 'overdue-row' : ''}">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div>
                        <div class="fw-semibold">${formatEmployeeName(row)}</div>
                        <div class="text-muted small">${row.transportation_type || ''} • ${row.pickup_location || ''}</div>
                    </div>
                    <div class="text-end">
                        <div class="fw-semibold">${row.pickup_time || ''}</div>
                        <div>${formatBadge(row.status || '')}</div>
                    </div>
                </div>
            </div>
        `;
    });

    container.html(html);
}

function exportToCsv() {
    if (!transportationRows.length) {
        swalInfo('No data to export.');
        return;
    }

    const header = ['Employee', 'Department', 'Departure Date', 'Pickup Time', 'Transportation', 'Driver', 'Vehicle', 'Pickup Location', 'Status', 'Remarks'];
    const lines = [header.join(',')];

    transportationRows.forEach(row => {
            const values = [
            `"${(row.employee_code || '')} - ${(row.english_name || '')}${row.chinese_name ? ' (' + row.chinese_name + ')' : ''}"`,
            `"${row.department_name || ''}"`,
            `"${row.pickup_date || ''}"`,
            `"${row.pickup_time || ''}"`,
            `"${row.transportation_type || ''}"`,
            `"${row.driver_name || ''}"`,
            `"${row.vehicle_name || ''}"`,
            `"${row.pickup_location || ''}"`,
            `"${row.status || ''}"`,
            `"${(row.remarks || '').replace(/"/g, '""')}"`
        ];
        lines.push(values.join(','));
    });

    const csvData = lines.join('\n');
    const blob = new Blob([csvData], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);

    link.setAttribute('href', url);
    link.setAttribute('download', `transportation_schedule_${new Date().toISOString().slice(0, 10)}.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
}

$(function() {
    const companyCarModalElement = document.getElementById('companyCarModal');
    const companyCarModalInstance = bootstrap.Modal.getInstance(companyCarModalElement) || new bootstrap.Modal(companyCarModalElement);
    const tripDetailsModalElement = document.getElementById('tripDetailsModal');

    $(companyCarModalElement).on('hidden.bs.modal', function() {
        if (tripDetailsRestoreTripId) {
            openTripDetailsModal(tripDetailsRestoreTripId);
            tripDetailsRestoreTripId = null;
        }
    });

    loadStats();
    loadDrivers();
    loadVehicles();
    loadBulkAssignmentEmployees();
    loadTransportationSchedule();
    submitDriverForm();
    submitVehicleForm();
    submitCompanyCarForm();
    submitBulkCompanyCarForm();

    $('#addDriverBtn').on('click', function() {
        $('#driverForm')[0].reset();
        const modal = bootstrap.Modal.getInstance(document.getElementById('driverModal')) || new bootstrap.Modal(document.getElementById('driverModal'));
        modal.show();
    });

    $('#addVehicleBtn').on('click', function() {
        $('#vehicleForm')[0].reset();
        const modal = bootstrap.Modal.getInstance(document.getElementById('vehicleModal')) || new bootstrap.Modal(document.getElementById('vehicleModal'));
        modal.show();
    });

    $('#bulkAssignmentBtn').on('click', function() {
        $('#bulkCompanyCarForm')[0].reset();
        $('#bulkPickupDate').val(new Date().toISOString().slice(0, 10));
        $('#bulkPickupTime').val('08:00');
        $('#bulkStatus').val('Scheduled');
        $('#bulkTransportationType').val('Company Car');
        const modal = bootstrap.Modal.getInstance(document.getElementById('bulkCompanyCarModal')) || new bootstrap.Modal(document.getElementById('bulkCompanyCarModal'));
        modal.show();
    });

    $('#manageDriverBtn').on('click', function() {
        openManageDrivers();
    });

    $('#manageVehicleBtn').on('click', function() {
        openManageVehicles();
    });

    $('#applyFilters').on('click', loadTransportationSchedule);
    $('#resetFilters').on('click', function() {
        $('#filterEmployeeSearch').val('');
        $('#filterEmployeeId').val('');
        $('#filterPickupDate').val('');
        $('#filterTransportationType').val('');
        $('#filterVehicle').val('');
        $('#filterDriver').val('');
        $('#filterStatus').val('');
        selectedTransportationIds.clear();
        loadTransportationSchedule();
    });

    $('.schedule-view-tab').on('click', function() {
        setScheduleView($(this).data('view'));
    });

    $('#filterEmployeeSearch').on('input', function() {
        clearTimeout(employeeSearchTimer);
        const search = $(this).val();
        employeeSearchTimer = setTimeout(function() {
            loadEmployees(search, '#filterEmployeeList', '#filterEmployeeSearch', '#filterEmployeeId');
        }, 250);
    });

    $('#companyCar_employee_search').on('input', function() {
        clearTimeout(employeeSearchTimer);
        const search = $(this).val();
        employeeSearchTimer = setTimeout(function() {
            loadEmployees(search, '#companyCar_employee_list', '#companyCar_employee_search', '#companyCar_employee_id');
        }, 250);
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('.search-input-wrapper').length) {
            $('#filterEmployeeList').removeClass('show');
            $('#companyCar_employee_list').removeClass('show');
        }
    });

    $('#exportScheduleBtn').on('click', exportToCsv);
    $('#selectAllTransportation').on('change', function() {
        toggleAllTransportation(this.checked);
    });

    $(document).on('click', '#tripDetailsContent .btn-edit-transportation', function() {
        const transportationId = Number($(this).data('transportation-id'));
        if (!transportationId) {
            swalError('Unable to locate the transportation record.');
            return;
        }
        openModal('edit', transportationId);
    });

    $(document).on('click', '#tripDetailsContent .delete-transportation', function() {
        const transportId = Number($(this).data('id'));
        if (!transportId) {
            swalError('Unable to locate the transportation record.');
            return;
        }
        confirmDelete(transportId);
    });

    if (new URLSearchParams(window.location.search).get('trip_leg_id')) {
        openModal('create');
    }
});
