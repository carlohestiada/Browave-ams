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

// Department CSV importer. It reuses the existing department POST endpoint so all
// required-field and duplicate checks continue to be handled by the server.
$(function() {
    const modal = document.getElementById('departmentBulkUploadModal');
    const input = document.getElementById('departmentBulkUploadFile');
    if (!modal || !input) return;

    const dropzone = document.getElementById('departmentDropzone');
    const empty = document.getElementById('departmentUploadEmpty');
    const fileState = document.getElementById('departmentUploadFileState');
    const preview = document.getElementById('departmentUploadPreview');
    const previewHead = document.getElementById('departmentPreviewHead');
    const previewBody = document.getElementById('departmentPreviewBody');
    const importButton = document.getElementById('departmentBulkUploadBtn');
    const progress = document.getElementById('departmentUploadProgress');
    const resultBox = document.getElementById('departmentUploadResults');
    let departmentNames = [];

    const parseCsv = text => {
        const rows = []; let row = []; let value = ''; let quoted = false;
        for (let i = 0; i < text.length; i++) {
            const char = text[i];
            if (char === '"') { if (quoted && text[i + 1] === '"') { value += char; i++; } else quoted = !quoted; }
            else if (char === ',' && !quoted) { row.push(value.trim()); value = ''; }
            else if ((char === '\n' || char === '\r') && !quoted) { if (char === '\r' && text[i + 1] === '\n') i++; row.push(value.trim()); if (row.some(cell => cell)) rows.push(row); row = []; value = ''; }
            else value += char;
        }
        row.push(value.trim()); if (row.some(cell => cell)) rows.push(row);
        return rows;
    };
    const resetUpload = () => {
        input.value = ''; departmentNames = []; empty.style.display = ''; fileState.classList.remove('visible'); preview.classList.remove('visible'); progress.style.display = 'none'; resultBox.classList.remove('visible'); resultBox.innerHTML = ''; importButton.disabled = false; importButton.textContent = 'Import Departments';
    };
    const renderPreview = () => {
        previewHead.innerHTML = '<tr><th class="px-3 py-2">Department Name</th></tr>';
        previewBody.innerHTML = departmentNames.slice(0, 100).map(name => `<tr><td class="px-3 py-2">${$('<div>').text(name).html()}</td></tr>`).join('');
        document.getElementById('departmentPreviewCount').textContent = `${departmentNames.length} department${departmentNames.length === 1 ? '' : 's'} found${departmentNames.length > 100 ? ' · showing first 100' : ''}`;
        preview.classList.toggle('visible', departmentNames.length > 0);
    };
    const showFile = file => {
        if (!file) { resetUpload(); return; }
        empty.style.display = 'none'; fileState.classList.add('visible');
        document.getElementById('departmentUploadFileName').textContent = file.name;
        document.getElementById('departmentUploadFileSize').textContent = `${(file.size / 1024).toFixed(1)} KB`;
        if (!file.name.toLowerCase().endsWith('.csv') || file.size > 10 * 1024 * 1024) { departmentNames = []; preview.classList.remove('visible'); return; }
        const reader = new FileReader();
        reader.onload = event => {
            const rows = parseCsv(event.target.result);
            const header = (rows.shift()?.[0] || '').replace(/^\uFEFF/, '').trim().toLowerCase();
            departmentNames = header === 'department name' || header === 'department_name' ? rows.map(row => row[0]).filter(Boolean) : [];
            renderPreview();
        };
        reader.readAsText(file);
    };
    const importDepartments = async () => {
        if (!input.files[0]) { swalError('Please select a CSV file to upload.'); return; }
        if (!departmentNames.length) { swalError('The CSV must have a Department Name header and at least one department.'); return; }
        importButton.disabled = true; importButton.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span> Importing...'; progress.style.display = 'block'; resultBox.classList.remove('visible');
        const successes = []; const failures = [];
        for (let index = 0; index < departmentNames.length; index++) {
            const name = departmentNames[index];
            try {
                const response = await $.ajax({ url: departmentApiUrl, type: 'POST', data: { department_name: name } });
                const data = typeof response === 'string' ? JSON.parse(response) : response;
                if (data.success) successes.push(name); else failures.push({ name, error: data.error || 'Unable to save department' });
            } catch (xhr) {
                failures.push({ name, error: xhr.responseJSON?.error || xhr.responseText || 'Unable to save department' });
            }
            const completed = index + 1;
            document.getElementById('departmentUploadProgressBar').style.width = `${(completed / departmentNames.length) * 100}%`;
            document.getElementById('departmentUploadProgressCount').textContent = `${completed} / ${departmentNames.length}`;
        }
        importButton.disabled = false; importButton.textContent = 'Import Departments'; loadDepartments();
        resultBox.innerHTML = `<strong style="color:${failures.length ? '#b45309' : '#15803d'};">${successes.length} of ${departmentNames.length} departments imported.</strong>${failures.length ? `<div class="mt-2 text-danger">${failures.slice(0, 20).map(item => `${$('<div>').text(item.name).html()}: ${$('<div>').text(item.error).html()}`).join('<br>')}${failures.length > 20 ? `<br>…and ${failures.length - 20} more.` : ''}</div>` : ''}`;
        resultBox.classList.add('visible');
        if (!failures.length) swalSuccess(`${successes.length} department${successes.length === 1 ? '' : 's'} imported successfully.`);
    };

    input.addEventListener('change', () => showFile(input.files[0]));
    document.getElementById('departmentClearUploadFile').addEventListener('click', resetUpload);
    ['dragenter', 'dragover'].forEach(type => dropzone.addEventListener(type, event => { event.preventDefault(); dropzone.classList.add('is-dragging'); }));
    ['dragleave', 'drop'].forEach(type => dropzone.addEventListener(type, event => { event.preventDefault(); dropzone.classList.remove('is-dragging'); }));
    dropzone.addEventListener('drop', event => { const file = event.dataTransfer.files[0]; if (!file) return; const transfer = new DataTransfer(); transfer.items.add(file); input.files = transfer.files; input.dispatchEvent(new Event('change')); });
    importButton.addEventListener('click', importDepartments);
    $('#departmentBulkUploadModal').on('hidden.bs.modal', resetUpload);
});
