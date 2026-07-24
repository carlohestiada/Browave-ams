function apiUrl(path) {
    const base = window.location.pathname.replace(/\/[^\/]+$/, '');
    return base + '/' + path.replace(/^\/+/, '');
}

const requestStatuses = ['Pending', 'Scheduled', 'Picked Up', 'Completed', 'Cancelled'];
const transportationTypes = ['Company Car', 'Airport Transfer', 'Shuttle Service', 'Private Hire', 'Other'];
const pageSize = 12;
let transportationRows = [];
let currentPage = 1;
let employeeSearchTimer = null;
let filterEmployeeData = [];
let modalMode = 'create';
let selectedTransportationIds = new Set();

function formatBadge(status) {
    const key = status.toLowerCase().replace(/ /g, '-');
    return `<span class="badge status-badge status-${key}">${status}</span>`;
}

function formatEmployeeName(row) {
    const chinese = row.chinese_name ? ` <span class="text-muted">(${row.chinese_name})</span>` : '';
    return `${row.employee_code} - ${row.full_name}${chinese}`;
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
        $('#kpiTodayRequests').text(stats.todays_requests ?? 0);
        $('#kpiScheduledToday').text(stats.scheduled_today ?? 0);
        $('#kpiCompleted').text(stats.completed ?? 0);
        $('#kpiPending').text(stats.pending_assignment ?? 0);
        $('#kpiAvailableVehicles').text(stats.available_vehicles ?? 0);
    });
}

function loadDrivers() {
    $.get(apiUrl('api/drivers/index.php'), function(data) {
        const drivers = typeof data === 'string' ? JSON.parse(data) : data;
        let options = '<option value="">All drivers</option>';
        let modalOptions = '<option value="">Select driver</option>';

        drivers.forEach(driver => {
            const label = `${driver.driver_name}${driver.phone ? ' (' + driver.phone + ')' : ''}`;
            options += `<option value="${driver.id}">${label}</option>`;
            modalOptions += `<option value="${driver.id}">${label}</option>`;
        });

        $('#filterDriver').html(options);
        $('#companyCar_driver_id').html(modalOptions);
    });
}

function loadVehicles() {
    $.get(apiUrl('api/vehicles/index.php'), function(data) {
        const vehicles = typeof data === 'string' ? JSON.parse(data) : data;
        let options = '<option value="">All vehicles</option>';
        let modalOptions = '<option value="">Select vehicle</option>';

        vehicles.forEach(vehicle => {
            const label = `${vehicle.vehicle_name}${vehicle.license_plate ? ' (' + vehicle.license_plate + ')' : ''}`;
            options += `<option value="${vehicle.id}">${label}</option>`;
            modalOptions += `<option value="${vehicle.id}">${label}</option>`;
        });

        $('#filterVehicle').html(options);
        $('#companyCar_vehicle_id').html(modalOptions);
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
                html += `<div class="dropdown-item" data-id="${emp.id}" data-label="${emp.employee_code} - ${emp.full_name}">${emp.employee_code} - ${emp.full_name}</div>`;
            });
        }

        list.html(html).addClass('show');
        $(`${targetList} .dropdown-item`).on('click', function() {
            const id = $(this).data('id');
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

function fetchEmployeeDetails(employeeId) {
    if (!employeeId) {
        clearEmployeeDetails();
        return;
    }

    $.get(apiUrl(`api/company-car/index.php/employee/${employeeId}`), function(data) {
        const employee = typeof data === 'string' ? JSON.parse(data) : data;

        $('#companyCar_employee_id').val(employee.id || '');
        $('#companyCar_employee_search').val(`${employee.employee_code || ''} - ${employee.full_name || ''}`);
        $('#companyCar_department').val(employee.department_name || '');
        $('#companyCar_chinese_name').val(employee.chinese_name || '');
        $('#companyCar_gender').val(employee.gender || '');
        $('#companyCar_arrival_date').val(employee.last_arrival_date ? employee.last_arrival_date.split(' ')[0] : '');
        $('#companyCar_departure_date').val(employee.last_departure_date ? employee.last_departure_date.split(' ')[0] : '');
        $('#companyCar_accommodation_room').val([employee.accommodation_name, employee.room_number].filter(Boolean).join(' / '));
    }).fail(function() {
        clearEmployeeDetails();
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

function loadTransportationSchedule() {
    const params = [];
    const employeeId = $('#filterEmployeeId').val();
    const pickupDate = $('#filterPickupDate').val();
    const type = $('#filterTransportationType').val();
    const vehicleId = $('#filterVehicle').val();
    const driverId = $('#filterDriver').val();
    const status = $('#filterStatus').val();

    if (employeeId) params.push(`employee_id=${encodeURIComponent(employeeId)}`);
    if (pickupDate) params.push(`pickup_date=${encodeURIComponent(pickupDate)}`);
    if (type) params.push(`transportation_type=${encodeURIComponent(type)}`);
    if (vehicleId) params.push(`vehicle_id=${encodeURIComponent(vehicleId)}`);
    if (driverId) params.push(`driver_id=${encodeURIComponent(driverId)}`);
    if (status) params.push(`status=${encodeURIComponent(status)}`);

    const url = apiUrl('api/company-car/index.php' + (params.length ? '?' + params.join('&') : ''));

    $.get(url, function(data) {
        transportationRows = typeof data === 'string' ? JSON.parse(data) : data;
        selectedTransportationIds.clear();
        currentPage = 1;
        renderTable();
        renderTimeline();
        $('#scheduleCount').text(`${transportationRows.length} trips found`);
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
        html += `
            <tr class="${overdue}">
                <td style="text-align:center;">
                    <input
                        type="checkbox"
                        class="transportation-select-checkbox"
                        value="${row.id}"
                        aria-label="Select transportation request"
                        onchange="toggleTransportationSelection(${row.id}, this.checked)"
                        ${checked}>
                </td>
                <td>${formatEmployeeName(row)}</td>
                <td>${row.department_name || ''}</td>
                <td>${row.pickup_date || ''}</td>
                <td>${row.pickup_time || ''}</td>
                <td>${row.transportation_type || ''}</td>
                <td>${row.driver_name || ''}</td>
                <td>${row.vehicle_name || ''}</td>
                <td>${row.pickup_location || ''}</td>
                <td>${formatBadge(row.status || '')}</td>
                <td style="white-space:nowrap;">
                    <button type="button" class="btn btn-sm btn-secondary me-1" data-action="view" data-id="${row.id}">View</button>
                    <button type="button" class="btn btn-sm btn-warning me-1" data-action="edit" data-id="${row.id}">Edit</button>
                    <button type="button" class="btn btn-sm btn-danger me-1" data-action="delete" data-id="${row.id}">Delete</button>
                    ${row.status === 'Pending' ? `<button type="button" class="btn btn-sm btn-primary" data-action="assign" data-id="${row.id}">Assign Driver</button>` : ''}
                </td>
            </tr>
        `;
    });

    body.html(html || '<tr><td colspan="11" class="text-center text-muted">No transportation requests found.</td></tr>');
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
            openModal('view', id);
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

    $('#companyCarModalLabel').text(modalTitle);
    $('#companyCarForm')[0].reset();
    clearEmployeeDetails();
    $('#companyCar_id').val('');
    $('#companyCar_status').val('Pending');
    $('#companyCar_transportation_type').val('Company Car');

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
    }

    if (id) {
        $.get(apiUrl(`api/company-car/index.php/${id}`), function(data) {
            const row = typeof data === 'string' ? JSON.parse(data) : data;
            $('#companyCar_id').val(row.id || '');
            $('#companyCar_employee_id').val(row.employee_id || '');
            $('#companyCar_employee_search').val(formatEmployeeName(row));
            $('#companyCar_transportation_type').val(row.transportation_type || 'Company Car');
            $('#companyCar_driver_id').val(row.driver_id || '');
            $('#companyCar_vehicle_id').val(row.vehicle_id || '');
            $('#companyCar_pickup_date').val(row.pickup_date || new Date().toISOString().slice(0, 10));
            $('#companyCar_pickup_time').val(row.pickup_time || '08:00');
            $('#companyCar_pickup_location').val(row.pickup_location || '');
            $('#companyCar_status').val(row.status || 'Pending');
            $('#companyCar_remarks').val(row.remarks || '');
            fetchEmployeeDetails(row.employee_id);

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
                    swalSuccess('Request deleted successfully');
                } else {
                    swalError(result.error || 'Unable to delete request');
                }
            },
            error: function(xhr) {
                swalError(xhr.responseJSON?.error || xhr.responseText || 'Unable to delete request');
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
            `"${(row.employee_code || '')} - ${(row.full_name || '')}${row.chinese_name ? ' (' + row.chinese_name + ')' : ''}"`,
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
    loadStats();
    loadDrivers();
    loadVehicles();
    loadTransportationSchedule();
    submitDriverForm();
    submitVehicleForm();
    submitCompanyCarForm();

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
});
