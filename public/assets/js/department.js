const departmentApiUrl = 'api/departments.php';
let departmentRows = [];
let selectedDepartmentIds = new Set();
let departmentSearchTimer = null;

function renderDepartmentRow(dept) {
    const departmentId = String(dept.id);
    const checked = selectedDepartmentIds.has(departmentId) ? 'checked' : '';

    return `
        <tr>
            <td style="text-align:center;">
                <input
                    type="checkbox"
                    class="department-select-checkbox"
                    value="${departmentId}"
                    aria-label="Select department"
                    onchange="toggleDepartmentSelection(${departmentId}, this.checked)"
                    ${checked}>
            </td>
            <td>${displayValue(dept.department_name)}</td>
            <td style="text-align:right; white-space:nowrap;">
                <button type="button" class="btn btn-warning btn-sm me-1" onclick="editDepartment(${dept.id})">
                    Edit
                </button>
                <button type="button" class="btn btn-danger btn-sm" onclick="deleteDepartment(${dept.id})">
                    Delete
                </button>
            </td>
        </tr>
    `;
}

function filterDepartmentRows(departments)
{
    const search = ($('#departmentSearchInput').val() || '').trim().toLowerCase();

    if (!search) {
        return departments.slice();
    }

    return departments.filter(dept => {
        return String(dept.department_name ?? '').toLowerCase().includes(search);
    });
}

function renderDepartments()
{
    renderPaginatedTable({
        data: filterDepartmentRows(departmentRows),
        tableSelector: '#departmentTable',
        currentPage: 1,
        perPage: 10,
        renderRow: renderDepartmentRow,
        sortColumns: [
            { index: 1, key: 'department_name' }
        ]
    });

    updateDepartmentSelectionControls();
}

function loadDepartments()
{
    $.get(departmentApiUrl, function(data) {
        departmentRows = typeof data === 'string' ? JSON.parse(data) : data;
        selectedDepartmentIds.clear();
        renderDepartments();
    });
}

function resetDepartmentFilters()
{
    $('#departmentSearchInput').val('');
    selectedDepartmentIds.clear();
    renderDepartments();
}

function toggleDepartmentSelection(id, checked)
{
    if (checked) {
        selectedDepartmentIds.add(String(id));
    } else {
        selectedDepartmentIds.delete(String(id));
    }

    updateDepartmentSelectionControls();
}

function toggleAllDepartments(checked)
{
    $('.department-select-checkbox').each(function() {
        this.checked = checked;

        if (checked) {
            selectedDepartmentIds.add(String(this.value));
        } else {
            selectedDepartmentIds.delete(String(this.value));
        }
    });

    updateDepartmentSelectionControls();
}

function updateDepartmentSelectionControls()
{
    const selectedCount = selectedDepartmentIds.size;
    const rowCheckboxes = $('.department-select-checkbox');
    const checkedCount = rowCheckboxes.filter(':checked').length;
    const selectAll = document.getElementById('selectAllDepartments');

    $('#selectedDepartmentsText').text(`${selectedCount} selected`);
    $('#bulkDeleteDepartmentsBtn').prop('disabled', selectedCount === 0);

    if (selectAll) {
        selectAll.checked = rowCheckboxes.length > 0 && checkedCount === rowCheckboxes.length;
        selectAll.indeterminate = checkedCount > 0 && checkedCount < rowCheckboxes.length;
    }
}

function resetDepartmentForm()
{
    $('#departmentForm')[0].reset();
    $('#departmentId').val('');
    $('#departmentModalLabel').text('Add Department');
}

function openDepartmentModal(department)
{
    resetDepartmentForm();

    if (department) {
        $('#departmentId').val(department.id);
        $('#department_name').val(department.department_name);
        $('#departmentModalLabel').text('Edit Department');
    }

    $('#departmentModal').modal('show');
}

function editDepartment(id)
{
    $.get(`${departmentApiUrl}/${id}`, function(data) {
        const department = typeof data === 'string' ? JSON.parse(data) : data;
        openDepartmentModal(department);
    });
}

function saveDepartment(event)
{
    event.preventDefault();

    const id = $('#departmentId').val();
    const url = id ? `${departmentApiUrl}/${id}` : departmentApiUrl;
    const method = id ? 'PUT' : 'POST';

    $.ajax({
        url: url,
        type: method,
        data: $('#departmentForm').serialize(),
        success: function(response) {
            const result = typeof response === 'string' ? JSON.parse(response) : response;

            if (!result.success) {
                swalError(result.error || 'Unable to save department');
                return;
            }

            loadDepartments();
            $('#departmentModal').modal('hide');
            swalSuccess('Department saved successfully');
        },
        error: function(xhr) {
            swalError(xhr.responseJSON?.error || 'Unknown error');
        }
    });
}

function deleteDepartment(id)
{
    swalConfirm('Delete department?', function() {
        $.ajax({
            url: `${departmentApiUrl}/${id}`,
            type: 'DELETE',
            success: function() {
                selectedDepartmentIds.delete(String(id));
                loadDepartments();
                swalSuccess('Department deleted successfully');
            },
            error: function(xhr) {
                swalError(xhr.responseJSON?.error || 'Unknown error');
            }
        });
    });
}

function deleteDepartmentById(id)
{
    return new Promise(resolve => {
        $.ajax({
            url: `${departmentApiUrl}/${id}`,
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

function deleteSelectedDepartments()
{
    const ids = Array.from(selectedDepartmentIds);

    if (ids.length === 0) {
        swalInfo('Select at least one department to delete.');
        return;
    }

    swalConfirm(`Delete ${ids.length} selected department${ids.length === 1 ? '' : 's'}?`, function() {
        $('#bulkDeleteDepartmentsBtn').prop('disabled', true).text('Deleting...');

        Promise.all(ids.map(deleteDepartmentById)).then(results => {
            const failed = results.filter(result => !result.success);
            const deletedCount = results.length - failed.length;

            selectedDepartmentIds.clear();
            loadDepartments();
            $('#bulkDeleteDepartmentsBtn').text('Delete Selected');

            if (failed.length > 0) {
                const firstError = failed[0].error;
                swalError(`${deletedCount} deleted. ${failed.length} could not be deleted. ${firstError}`, 'Bulk delete incomplete');
                return;
            }

            swalSuccess(`${deletedCount} department${deletedCount === 1 ? '' : 's'} deleted successfully.`);
        });
    });
}

$(function() {
    loadDepartments();
    $('#selectAllDepartments').on('change', function() {
        toggleAllDepartments(this.checked);
    });
    $('#departmentSearchInput').on('input', function() {
        clearTimeout(departmentSearchTimer);
        departmentSearchTimer = setTimeout(function() {
            selectedDepartmentIds.clear();
            renderDepartments();
        }, 250);
    });
    $('#departmentForm').on('submit', saveDepartment);
});
