const raApi = 'api/room_assignments/index.php';
let activeAssignedEmployees = new Set();
let assignEmployees = [];
let assignmentRows = [];
let selectedAssignmentIds = new Set();
let assignmentSearchTimer = null;

const assignmentSortColumns = [
    { index: 1, key: function(row) { return `${row.employee_code || ''} ${row.full_name || ''}`; } },
    { index: 2, key: 'department_name' },
    { index: 3, key: 'gender' },
    { index: 4, key: 'checkin_date' },
    { index: 5, key: function(row) { return row.expected_checkout_date || row.actual_checkout_date || ''; } },
    { index: 6, key: 'accommodation_name' },
    { index: 7, key: 'room_no' },
    { index: 8, key: function(row) { return `${row.transferred_room_no || ''} ${row.transferred_accommodation_name || ''}`; } }
];

function getTodayDate() {
    return new Date().toISOString().slice(0,10);
}

function renderAssignEmployeeDropdown() {
    const assignSelect = $('#assign_employee');
    if (!assignSelect.length) {
        return;
    }

    let opts = '<option value="">Select employee</option>';
    assignEmployees.forEach(e => {
        const employeeId = String(e.id);
        const disabled = activeAssignedEmployees.has(employeeId) ? ' disabled' : '';
        const label = activeAssignedEmployees.has(employeeId)
            ? `${e.employee_code} - ${e.full_name} (already assigned)`
            : `${e.employee_code} - ${e.full_name}`;
        opts += `<option value="${e.id}"${disabled}>${label}</option>`;
    });
    assignSelect.html(opts);
}

function resetAssignForm() {
    $('#assignForm')[0].reset();
    initializeAssignmentDateBounds();
    loadEmployeesForAssign();
    loadRoomsForAssign('#assign_room');
}

function resetTransferForm() {
    $('#transferForm')[0].reset();
    $('#transfer_assignment_id').val('');
    $('#transfer_assignment').prop('disabled', false);
    $('#transfer_preview').text('--');
    $('#transfer_date').val(getTodayDate());
    loadRoomsForAssign('#transfer_room');
    renderTransferAssignmentDropdown();
}

function initializeAssignmentDateBounds() {
    const checkin = document.getElementById('assign_checkin_date');
    const checkout = document.getElementById('assign_checkout_date');
    if (!checkin || !checkout) {
        return;
    }

    const today = getTodayDate();
    checkin.min = today;

    if (!checkin.value || checkin.value < today) {
        checkin.value = today;
    }

    checkout.min = checkin.value || today;
    if (!checkout.value || checkout.value < checkout.min) {
        checkout.value = checkout.min;
    }

    checkin.addEventListener('change', function () {
        const minCheckout = checkin.value || today;
        checkout.min = minCheckout;
        if (!checkout.value || checkout.value < minCheckout) {
            checkout.value = minCheckout;
        }
    });
}

function renderAssignmentRow(r, lookup) {
    const trClass = r.status === 'Transferred' ? 'table-warning' : '';
    const assignmentId = String(r.id);
    const checked = selectedAssignmentIds.has(assignmentId) ? 'checked' : '';
    let transferredTo = '';

    if (r.transferred_to_room_id && r.transferred_room_no) {
        transferredTo = `${displayValue(r.transferred_accommodation_name)} - ${displayValue(r.transferred_room_no)} on ${displayValue(r.actual_checkout_date)}`;
    }

    return `
        <tr class="${trClass}">
            <td style="text-align:center;">
                <input
                    type="checkbox"
                    class="assignment-select-checkbox"
                    value="${assignmentId}"
                    aria-label="Select assignment"
                    onchange="toggleAssignmentSelection(${assignmentId}, this.checked)"
                    ${checked}>
            </td>          
            <td>${displayValue(r.employee_code)} - ${displayValue(r.full_name)}</td>
            <td>${displayValue(r.department_name)}</td>
            <td>${displayValue(r.gender)}</td>
            <td>${displayValue(r.checkin_date)}</td>
            <td>${displayValue(r.expected_checkout_date || r.actual_checkout_date)}</td>
            <td>${displayValue(r.accommodation_name)}</td>
            <td>${displayValue(r.room_no)}</td>
            <td>${displayValue(transferredTo)}</td>
            <td style="text-align:right; white-space:nowrap;">
                <button type="button" class="btn btn-warning btn-sm me-1" onclick="editAssignment(${r.id})">Edit</button>
                <button type="button" class="btn btn-danger btn-sm" onclick="deleteAssignment(${r.id})">Delete</button>
            </td>
        </tr>
    `;
}

function filterAssignmentRows(rows) {
    const search = ($('#assignmentSearchInput').val() || '').trim().toLowerCase();

    if (!search) {
        return rows.slice();
    }

    return rows.filter(r => {
        const transferredTo = `${r.transferred_room_no || ''} ${r.transferred_accommodation_name || ''} ${r.actual_checkout_date || ''}`;
        return [
            r.employee_code,
            r.full_name,
            r.department_name,
            r.gender,
            r.checkin_date,
            r.expected_checkout_date,
            r.accommodation_name,
            r.room_no,
            transferredTo,
            r.status
        ].some(value => String(value ?? '').toLowerCase().includes(search));
    });
}

function renderAssignments() {
    const lookup = {};
    assignmentRows.forEach(r => {
        const key = `${r.employee_id}::${r.checkin_date}`;
        lookup[key] = r;
    });

    renderPaginatedTable({
        data: filterAssignmentRows(assignmentRows),
        tableSelector: '#assignmentTable',
        currentPage: 1,
        perPage: 10,
        renderRow: function(room) {
            return renderAssignmentRow(room, lookup);
        },
        sortColumns: assignmentSortColumns
    });

    updateAssignmentSelectionControls();
}

function loadAssignments() {
    $.get(raApi, function(data) {
        const rows = typeof data === 'string' ? JSON.parse(data) : data;
        assignmentRows = rows;
        selectedAssignmentIds.clear();

        activeAssignedEmployees = new Set();
        rows.forEach(r => {
            if (r.status === 'Active' || r.status === 'Transferred') {
                activeAssignedEmployees.add(String(r.employee_id));
            }
        });

        renderAssignEmployeeDropdown();
        renderTransferAssignmentDropdown();
        renderAssignments();
    });
}

function resetAssignmentFilters() {
    $('#assignmentSearchInput').val('');
    selectedAssignmentIds.clear();
    renderAssignments();
}

function toggleAssignmentSelection(id, checked)
{
    if (checked) {
        selectedAssignmentIds.add(String(id));
    } else {
        selectedAssignmentIds.delete(String(id));
    }

    updateAssignmentSelectionControls();
}

function toggleAllAssignments(checked)
{
    $('.assignment-select-checkbox').each(function() {
        this.checked = checked;

        if (checked) {
            selectedAssignmentIds.add(String(this.value));
        } else {
            selectedAssignmentIds.delete(String(this.value));
        }
    });

    updateAssignmentSelectionControls();
}

function updateAssignmentSelectionControls()
{
    const selectedCount = selectedAssignmentIds.size;
    const rowCheckboxes = $('.assignment-select-checkbox');
    const checkedCount = rowCheckboxes.filter(':checked').length;
    const selectAll = document.getElementById('selectAllAssignments');

    $('#selectedAssignmentsText').text(`${selectedCount} selected`);
    $('#bulkDeleteAssignmentsBtn').prop('disabled', selectedCount === 0);

    if (selectAll) {
        selectAll.checked = rowCheckboxes.length > 0 && checkedCount === rowCheckboxes.length;
        selectAll.indeterminate = checkedCount > 0 && checkedCount < rowCheckboxes.length;
    }
}

function renderTransferAssignmentDropdown(selectedId = '') {
    const transferSelect = $('#transfer_assignment');
    if (!transferSelect.length) {
        return;
    }

    let opts = '<option value="">Select assignment</option>';
    assignmentRows
        .filter(r => r.status === 'Active' || r.status === 'Transferred')
        .forEach(r => {
            const selected = String(r.id) === String(selectedId) ? ' selected' : '';
            const roomLabel = r.transferred_room_no ? ` -> ${r.transferred_room_no}` : '';
            opts += `<option value="${r.id}"${selected}>${displayValue(r.employee_code)} - ${displayValue(r.full_name)} (${displayValue(r.room_no)}${roomLabel})</option>`;
        });
    transferSelect.html(opts);
}

function loadEmployeesForAssign() {
    $.get('api/employees.php', function(data) {
        assignEmployees = typeof data === 'string' ? JSON.parse(data) : data;
        renderAssignEmployeeDropdown();
    });
}

function loadRoomsForAssign(selector = '#assign_room', onlyAvailable = true) {
    $.get('api/rooms.php', function(data) {
        const rooms = typeof data === 'string' ? JSON.parse(data) : data;
        const selectedRoom = String($(selector).data('selected-room') || '');
        let opts = '<option value="">Select room</option>';
        rooms
            .filter(r => {
                if (!onlyAvailable) {
                    return String(r.id) === selectedRoom || r.status !== 'Reserved';
                }
                return (r.status === 'Available' || String(r.id) === selectedRoom) && r.status !== 'Reserved';
            })
            .forEach(r => {
                const selected = String(r.id) === selectedRoom ? ' selected' : '';
                const label = r.status === 'Reserved' ? `${r.room_no} (${r.accommodation_name || ''}) - Reserved` : `${r.room_no} (${r.accommodation_name || ''})`;
                opts += `<option value="${r.id}"${selected}>${label}</option>`;
            });
        $(selector).html(opts);
        $(selector).removeData('selected-room');
        updateTransferPreview();
    });
}

$(function() {
    loadAssignments();
    loadEmployeesForAssign();
    loadRoomsForAssign('#assign_room');
    loadRoomsForAssign('#transfer_room');
    initializeAssignmentDateBounds();

    $('#assignModal').on('hidden.bs.modal', resetAssignForm);
    $('#transferModal').on('hidden.bs.modal', resetTransferForm);
    $('#selectAllAssignments').on('change', function() {
        toggleAllAssignments(this.checked);
    });
    $('#assignmentSearchInput').on('input', function() {
        clearTimeout(assignmentSearchTimer);
        assignmentSearchTimer = setTimeout(function() {
            selectedAssignmentIds.clear();
            renderAssignments();
        }, 250);
    });

    $('#assignForm').on('submit', function(e) {
        e.preventDefault();

        const checkinDate = $('#assign_checkin_date').val();
        const checkoutDate = $('#assign_checkout_date').val();
        const today = getTodayDate();

        if (!checkinDate || !checkoutDate) {
            swalError('Please select both arrival and departure dates.');
            return;
        }

        if (checkinDate < today) {
            swalError('Arrival date cannot be earlier than today.');
            return;
        }

        if (checkoutDate < checkinDate) {
            swalError('Departure date cannot be before arrival date.');
            return;
        }

        const selectedEmployee = $('#assign_employee').val();
        if (activeAssignedEmployees.has(selectedEmployee)) {
            swalError('This employee already has an active room assignment. Use transfer instead.');
            return;
        }

        $.post(raApi, $(this).serialize(), function(resp) {
            const res = typeof resp === 'string' ? JSON.parse(resp) : resp;
            if (res.success) {
                swalSuccess('Room assigned successfully');
                const assignEl = document.getElementById('assignModal');
                const assignModal = bootstrap.Modal.getInstance(assignEl) || new bootstrap.Modal(assignEl);
                assignModal.hide();
                resetAssignForm();
                loadAssignments();
            } else {
                swalError(res.error || 'Unable to assign room');
            }
        });
    });

    // transfer modal submit
    $('#transferForm').on('submit', function(e) {
        e.preventDefault();
        const assignmentId = $('#transfer_assignment_id').val();
        const selectedAssignment = $('#transfer_assignment').val();
        const newRoom = $('#transfer_room').val();
        const date = $('#transfer_date').val();
        const targetAssignment = assignmentId || selectedAssignment;
        if (!targetAssignment || !newRoom || !date) {
            swalError('Please fill all transfer fields');
            return;
        }

        $.ajax({
            url: raApi + '/' + targetAssignment,
            type: 'PUT',
            data: { new_room_id: newRoom, transfer_date: date },
            success: function(r) {
                const res = typeof r === 'string' ? JSON.parse(r) : r;
                if (res.success) {
                    swalSuccess('Transfer recorded');
                    const transferEl = document.getElementById('transferModal');
                    const transferModal = bootstrap.Modal.getInstance(transferEl) || new bootstrap.Modal(transferEl);
                    transferModal.hide();
                    resetTransferForm();
                    loadAssignments();
                } else {
                    swalError(res.error || 'Transfer failed');
                }
            },
            error: function(xhr) {
                const msg = xhr.responseJSON?.error || xhr.responseText || 'Transfer failed';
                swalError(msg);
            }
        });
    });

    $('#transfer_assignment').on('change', function() {
        const id = $(this).val();
        $('#transfer_assignment_id').val(id);
        const assignment = assignmentRows.find(r => String(r.id) === String(id));
        if (!assignment) {
            $('#transfer_room').val('');
            $('#transfer_preview').text('--');
            return;
        }

        $('#transfer_date').val(assignment.actual_checkout_date || getTodayDate());
        const selectedRoom = assignment.transferred_to_room_id || '';
        $('#transfer_room').data('selected-room', selectedRoom);
        loadRoomsForAssign('#transfer_room');
    });
});

function updateTransferPreview() {
    const text = $('#transfer_room').find('option:selected').text();
    const date = $('#transfer_date').val();
    $('#transfer_preview').text(text ? `${text} on ${date}` : '--');
}

function openTransfer(id = null, lockAssignment = false) {
    $('#transfer_assignment_id').val(id || '');
    renderTransferAssignmentDropdown(id || '');
    $('#transfer_assignment').prop('disabled', Boolean(lockAssignment));

    const assignment = assignmentRows.find(r => String(r.id) === String(id));
    $('#transfer_date').val(assignment?.actual_checkout_date || getTodayDate());
    $('#transfer_room').data('selected-room', assignment?.transferred_to_room_id || '');
    loadRoomsForAssign('#transfer_room');
    updateTransferPreview();

    const transferEl = document.getElementById('transferModal');
    const transferModal = bootstrap.Modal.getInstance(transferEl) || new bootstrap.Modal(transferEl);
    transferModal.show();

    $('#transfer_room').off('change.preview').on('change.preview', function() {
        updateTransferPreview();
    });

    $('#transfer_date').off('change.preview').on('change.preview', function() {
        updateTransferPreview();
    });
}

function editAssignment(id) {
    openTransfer(id, true);
}

function deleteAssignment(id) {
    swalConfirm('Delete this room assignment?', function() {
        $.ajax({
            url: raApi + '/' + id,
            type: 'DELETE',
            success: function(r) {
                const res = typeof r === 'string' ? JSON.parse(r) : r;
                if (res.success) {
                    selectedAssignmentIds.delete(String(id));
                    swalSuccess('Room assignment deleted successfully');
                    loadAssignments();
                    loadRoomsForAssign('#assign_room');
                    loadRoomsForAssign('#transfer_room');
                } else {
                    swalError(res.error || 'Delete failed');
                }
            },
            error: function(xhr) {
                swalError(xhr.responseJSON?.error || xhr.responseText || 'Delete failed');
            }
        });
    });
}

function deleteAssignmentById(id)
{
    return new Promise(resolve => {
        $.ajax({
            url: raApi + '/' + id,
            type: 'DELETE',
            success: function(r) {
                const res = typeof r === 'string' ? JSON.parse(r) : r;
                resolve({ success: Boolean(res.success), id: id, error: res.error || 'Delete failed' });
            },
            error: function(xhr) {
                const message = xhr.responseJSON?.error || xhr.responseText || 'Delete failed';
                resolve({ success: false, id: id, error: message });
            }
        });
    });
}

function deleteSelectedAssignments()
{
    const ids = Array.from(selectedAssignmentIds);

    if (ids.length === 0) {
        swalInfo('Select at least one room assignment to delete.');
        return;
    }

    swalConfirm(`Delete ${ids.length} selected room assignment${ids.length === 1 ? '' : 's'}?`, function() {
        $('#bulkDeleteAssignmentsBtn').prop('disabled', true).text('Deleting...');

        Promise.all(ids.map(deleteAssignmentById)).then(results => {
            const failed = results.filter(result => !result.success);
            const deletedCount = results.length - failed.length;

            selectedAssignmentIds.clear();
            loadAssignments();
            loadRoomsForAssign('#assign_room');
            loadRoomsForAssign('#transfer_room');
            $('#bulkDeleteAssignmentsBtn').text('Delete Selected');

            if (failed.length > 0) {
                const firstError = failed[0].error;
                swalError(`${deletedCount} deleted. ${failed.length} could not be deleted. ${firstError}`, 'Bulk delete incomplete');
                return;
            }

            swalSuccess(`${deletedCount} room assignment${deletedCount === 1 ? '' : 's'} deleted successfully.`);
        });
    });
}
