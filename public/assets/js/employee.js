let currentEmployeeId = null;
let selectedEmployeeIds = new Set();
let employeeRows = [];
let employeeCurrentPage = 1;
let employeePerPage = 10;
let employeeSortKey = '';
let employeeSortDirection = 'asc';
let employeeSearchTimer = null;

function loadDepartments(callback)
{
    $.get('api/departments.php', function(data) {
        const departments = typeof data === 'string' ? JSON.parse(data) : data;
        let options = '<option value="">Select department</option>';
        let filterOptions = '<option value="">All Departments</option>';

        departments.forEach(dep => {
            options += `<option value="${dep.id}">${dep.department_name}</option>`;
            filterOptions += `<option value="${dep.id}">${dep.department_name}</option>`;
        });

        $('#department_id').html(options);
        $('#filterDepartment').html(filterOptions);

        if (typeof callback === 'function') {
            callback();
        }
    });
}

function applyEmployeeFiltersFromUrl()
{
    const params = new URLSearchParams(window.location.search);
    const search = params.get('search');
    const departmentId = params.get('department_id');
    const gender = params.get('gender');
    const status = params.get('status');

    if (search) {
        $('#searchInput').val(search);
    }

    if (departmentId) {
        $('#filterDepartment').val(departmentId);
    }

    if (gender) {
        $('#filterGender').val(gender);
    }

    if (status) {
        $('#filterStatus').val(status);
    }
}

function loadEmployees()
{
    const params = new URLSearchParams();
    const search = $('#searchInput').val()?.trim();
    const departmentId = $('#filterDepartment').val();
    const gender = $('#filterGender').val();
    const status = $('#filterStatus').val();

    if (search) {
        params.set('search', search);
    }

    if (departmentId) {
        params.set('department_id', departmentId);
    }

    if (gender) {
        params.set('gender', gender);
    }

    if (status) {
        params.set('status', status);
    }

    const query = params.toString();
    const url = query ? `api/employees.php?${query}` : 'api/employees.php';

    $.get(url, function(data) {
        employeeRows = typeof data === 'string' ? JSON.parse(data) : data;
        employeeCurrentPage = 1;
        selectedEmployeeIds.clear();
        renderEmployeeTable();
    });
}

function resetFilters()
{
    $('#searchInput').val('');
    $('#filterDepartment').val('');
    $('#filterGender').val('');
    $('#filterStatus').val('');
    loadEmployees();
}

function renderEmployeeRow(emp)
{
    const employeeId = String(emp.id);
    const checked = selectedEmployeeIds.has(employeeId) ? 'checked' : '';

    return `
        <tr>
            <td style="text-align:center;">
                <input
                    type="checkbox"
                    class="employee-select-checkbox"
                    value="${employeeId}"
                    aria-label="Select employee"
                    onchange="toggleEmployeeSelection(${employeeId}, this.checked)"
                    ${checked}>
            </td>
            <td>${displayValue(emp.employee_code)}</td>
            <td>${displayValue(emp.full_name)}</td>
            <td>${displayValue(emp.chinese_name)}</td>
            <td>${displayValue(emp.gender)}</td>
            <td>${displayValue(emp.department_name)}</td>
            <td>${renderStatusBadge(emp.status)}</td>
            <td>
                <button class="btn btn-warning btn-sm me-1" onclick="editEmployee(${employeeId})">
                    Edit
                </button>
                <button class="btn btn-danger btn-sm" onclick="deleteEmployee(${employeeId})">
                    Delete
                </button>
            </td>
        </tr>
    `;
}

function renderEmployeeTable()
{
    const sortedEmployees = getSortedEmployees();
    const pagination = paginateArray(sortedEmployees, employeeCurrentPage, employeePerPage);
    employeeCurrentPage = pagination.currentPage;

    let rows = pagination.items.map(renderEmployeeRow).join('');

    if (!rows) {
        rows = renderEmptyTableRow($('#employeeTable').closest('table').find('thead th').length);
    }

    $('#employeeTable').html(rows);

    const startItem = pagination.totalItems === 0 ? 0 : ((pagination.currentPage - 1) * employeePerPage) + 1;
    const endItem = Math.min(pagination.currentPage * employeePerPage, pagination.totalItems);
    const paginationInfo = `${startItem}-${endItem} of ${pagination.totalItems} employees`;
    const paginationHtml = renderPaginationControls(pagination.currentPage, pagination.totalPages);
    const paginationContainer = ensurePaginationContainer('#employeeTable');

    paginationContainer.html(`
        <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;">
            <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                <label for="employeeEntriesSelect" style="font-size:13px; color:#434653; display:flex; align-items:center; gap:6px; margin:0;">
                    Entries
                    <select id="employeeEntriesSelect" class="form-select form-select-sm" style="width:78px;">
                        <option value="10" ${employeePerPage === 10 ? 'selected' : ''}>10</option>
                        <option value="25" ${employeePerPage === 25 ? 'selected' : ''}>25</option>
                        <option value="50" ${employeePerPage === 50 ? 'selected' : ''}>50</option>
                        <option value="100" ${employeePerPage === 100 ? 'selected' : ''}>100</option>
                    </select>
                </label>
                <div style="font-size:13px; color:#434653;">${paginationInfo}</div>
            </div>
            ${paginationHtml}
        </div>
    `);

    $('#employeeEntriesSelect').on('change', function() {
        employeePerPage = Number(this.value) || 10;
        employeeCurrentPage = 1;
        renderEmployeeTable();
    });

    paginationContainer.find('.page-link').on('click', function(event) {
        event.preventDefault();
        const page = Number($(this).attr('data-page'));

        if (!isNaN(page) && page >= 1 && page <= pagination.totalPages && page !== pagination.currentPage) {
            employeeCurrentPage = page;
            renderEmployeeTable();
        }
    });

    updateEmployeeSelectionControls();
    updateEmployeeSortIndicators();
}

function getSortedEmployees()
{
    if (!employeeSortKey) {
        return employeeRows.slice();
    }

    return employeeRows.slice().sort((a, b) => {
        const first = normalizeSortValue(a[employeeSortKey]);
        const second = normalizeSortValue(b[employeeSortKey]);
        const result = first.localeCompare(second, undefined, { numeric: true, sensitivity: 'base' });

        return employeeSortDirection === 'asc' ? result : -result;
    });
}

function normalizeSortValue(value)
{
    return value === undefined || value === null ? '' : String(value).trim();
}

function sortEmployeesBy(key)
{
    if (employeeSortKey === key) {
        employeeSortDirection = employeeSortDirection === 'asc' ? 'desc' : 'asc';
    } else {
        employeeSortKey = key;
        employeeSortDirection = 'asc';
    }

    employeeCurrentPage = 1;
    renderEmployeeTable();
}

function updateEmployeeSortIndicators()
{
    $('.employee-sort-indicator').text('');

    if (!employeeSortKey) {
        return;
    }

    const indicator = employeeSortDirection === 'asc' ? '^' : 'v';
    $(`[data-sort-indicator="${employeeSortKey}"]`).text(indicator);
}

function updateEmployeeSelectionControls()
{
    const selectedCount = selectedEmployeeIds.size;
    const rowCheckboxes = $('.employee-select-checkbox');
    const checkedCount = rowCheckboxes.filter(':checked').length;
    const selectAll = document.getElementById('selectAllEmployees');

    $('#selectedEmployeesText').text(`${selectedCount} selected`);
    $('#bulkDeleteEmployeesBtn').prop('disabled', selectedCount === 0);

    if (selectAll) {
        selectAll.checked = rowCheckboxes.length > 0 && checkedCount === rowCheckboxes.length;
        selectAll.indeterminate = checkedCount > 0 && checkedCount < rowCheckboxes.length;
    }
}

function toggleEmployeeSelection(id, checked)
{
    if (checked) {
        selectedEmployeeIds.add(String(id));
    } else {
        selectedEmployeeIds.delete(String(id));
    }

    updateEmployeeSelectionControls();
}

function toggleAllEmployees(checked)
{
    $('.employee-select-checkbox').each(function() {
        this.checked = checked;

        if (checked) {
            selectedEmployeeIds.add(String(this.value));
        } else {
            selectedEmployeeIds.delete(String(this.value));
        }
    });

    updateEmployeeSelectionControls();
}

function resetEmployeeForm()
{
    $('#employeeForm')[0].reset();
    $('#employeeId').val('');
    currentEmployeeId = null;
    $('#employeeModalLabel').text('Add Employee');
}

function openEmployeeModal(employee)
{
    resetEmployeeForm();

    if (employee) {
        currentEmployeeId = employee.id;
        $('#employeeId').val(employee.id);
        $('#employee_code').val(employee.employee_code);
        $('#full_name').val(employee.full_name);
        $('#chinese_name').val(employee.chinese_name);
        $('#gender').val(employee.gender);
        $('#department_id').val(employee.department_id);
        $('#status').val(employee.status);
        $('#employeeModalLabel').text('Edit Employee');
    }

    $('#employeeModal').modal('show');
}

function editEmployee(id)
{
    $.get(`api/employees.php/${id}`, function(data) {
        const employee = typeof data === 'string' ? JSON.parse(data) : data;
        loadDepartments(function() {
            openEmployeeModal(employee);
        });
    });
}

const employeeApiUrl = 'api/employees.php';

function saveEmployee(event)
{
    event.preventDefault();

    const id = $('#employeeId').val();
    const url = id ? `${employeeApiUrl}/${id}` : employeeApiUrl;
    const method = id ? 'PUT' : 'POST';

    $.ajax({
        url: url,
        type: method,
        data: $('#employeeForm').serialize(),
        success: function() {
            loadEmployees();
            $('#employeeModal').modal('hide');
            swalSuccess('Employee saved successfully');
        },
        error: function(xhr) {
            const error = xhr.responseJSON?.error || xhr.responseText || 'Unknown error';
            swalError('Error saving employee: ' + error);
        }
    });
}

function deleteEmployee(id)
{
    swalConfirm('Delete employee?', function() {
        $.ajax({
            url: `${employeeApiUrl}/${id}`,
            type: 'DELETE',
            success: function() {
                loadEmployees();
                selectedEmployeeIds.delete(String(id));
                swalSuccess('Employee deleted successfully');
            },
            error: function(xhr) {
                const message = xhr.responseJSON?.error || xhr.responseText || 'Unknown error';
                swalError(message, 'Delete failed');
            }
        });
    });
}

function deleteEmployeeById(id)
{
    return new Promise(resolve => {
        $.ajax({
            url: `${employeeApiUrl}/${id}`,
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

function deleteSelectedEmployees()
{
    const ids = Array.from(selectedEmployeeIds);

    if (ids.length === 0) {
        swalInfo('Select at least one employee to delete.');
        return;
    }

    swalConfirm(`Delete ${ids.length} selected employee${ids.length === 1 ? '' : 's'}?`, function() {
        $('#bulkDeleteEmployeesBtn').prop('disabled', true).text('Deleting...');

        Promise.all(ids.map(deleteEmployeeById)).then(results => {
            const failed = results.filter(result => !result.success);
            const deletedCount = results.length - failed.length;

            selectedEmployeeIds.clear();
            loadEmployees();
            $('#bulkDeleteEmployeesBtn').text('Delete Selected');

            if (failed.length > 0) {
                const firstError = failed[0].error;
                swalError(`${deletedCount} deleted. ${failed.length} could not be deleted. ${firstError}`, 'Bulk delete incomplete');
                return;
            }

            swalSuccess(`${deletedCount} employee${deletedCount === 1 ? '' : 's'} deleted successfully.`);
        });
    });
}

$(function() {
    loadDepartments(function() {
        applyEmployeeFiltersFromUrl();
        loadEmployees();
    });
    $('#selectAllEmployees').on('change', function() {
        toggleAllEmployees(this.checked);
    });
    $('.employee-sort-btn').on('click', function() {
        sortEmployeesBy($(this).attr('data-sort-key'));
    });
    $('#searchInput').on('input', function() {
        clearTimeout(employeeSearchTimer);
        employeeSearchTimer = setTimeout(loadEmployees, 250);
    });
    $('#filterDepartment, #filterGender, #filterStatus').on('change', loadEmployees);
    $('#employeeForm').on('submit', saveEmployee);
    $('#employeeModal').on('hidden.bs.modal', function () {
        resetEmployeeForm();
    });
});
