const usersApiUrl = 'api/users.php';
let selectedUserIds = new Set();

function parseJsonResponse(data) {
    return typeof data === 'string' ? JSON.parse(data) : data;
}

function updateUserSelectionControls() {
    const selectedCount = selectedUserIds.size;
    const rowCheckboxes = $('.user-select-checkbox');
    const checkedCount = rowCheckboxes.filter(':checked').length;
    const selectAll = document.getElementById('selectAllUsers');

    $('#selectedUsersText').text(`${selectedCount} selected`);
    $('#bulkDeleteUsersBtn').prop('disabled', selectedCount === 0);

    if (selectAll) {
        selectAll.checked = rowCheckboxes.length > 0 && checkedCount === rowCheckboxes.length;
        selectAll.indeterminate = checkedCount > 0 && checkedCount < rowCheckboxes.length;
    }
}

function toggleUserSelection(id, checked) {
    if (checked) {
        selectedUserIds.add(String(id));
    } else {
        selectedUserIds.delete(String(id));
    }

    updateUserSelectionControls();
}

function toggleAllUsers(checked) {
    $('.user-select-checkbox').each(function() {
        this.checked = checked;

        if (checked) {
            selectedUserIds.add(String(this.value));
        } else {
            selectedUserIds.delete(String(this.value));
        }
    });

    updateUserSelectionControls();
}

function renderUserRow(user) {
    const checked = selectedUserIds.has(String(user.id)) ? 'checked' : '';

    return `
        <tr>
            <td style="text-align:center;">
                <input
                    type="checkbox"
                    class="user-select-checkbox"
                    value="${user.id}"
                    aria-label="Select user"
                    onchange="toggleUserSelection(${user.id}, this.checked)"
                    ${checked}>
            </td>
            <td>${displayValue(user.username)}</td>
            <td><span class="badge bg-info">${displayValue(user.role)}</span></td>
            <td><span class="badge ${user.status === 'Active' ? 'bg-success' : 'bg-danger'}">${displayValue(user.status)}</span></td>
            <td>${displayValue(user.created_at)}</td>
            <td>
                <button class="btn btn-warning btn-sm me-1" onclick="editUser(${user.id})">
                    Edit
                </button>
                <button class="btn btn-danger btn-sm" onclick="deleteUser(${user.id})">
                    Delete
                </button>
            </td>
        </tr>
    `;
}

function loadUsers(page = 1)
{
    const params = [];
    const username = $('#filterUsername').val().trim();
    const role = $('#filterRole').val();
    const status = $('#filterStatus').val();

    if (username) {
        params.push(`username=${encodeURIComponent(username)}`);
    }

    if (role && role !== 'All') {
        params.push(`role=${encodeURIComponent(role)}`);
    }

    if (status && status !== 'All') {
        params.push(`status=${encodeURIComponent(status)}`);
    }

    const url = params.length ? `${usersApiUrl}?${params.join('&')}` : usersApiUrl;

    $.get(url, function(data) {
        const users = parseJsonResponse(data);
        selectedUserIds.clear();
        renderPaginatedTable({
            data: users,
            tableSelector: '#userTable',
            currentPage: page,
            perPage: 10,
            renderRow: renderUserRow,
            sortColumns: [
                { index: 1, key: 'username' },
                { index: 2, key: 'role' },
                { index: 3, key: 'status' },
                { index: 4, key: 'created_at' }
            ]
        });
        updateUserSelectionControls();
    });
}

function resetUserFilters()
{
    $('#filterUsername').val('');
    $('#filterRole').val('All');
    $('#filterStatus').val('All');
    selectedUserIds.clear();
    loadUsers();
}

function resetUserForm()
{
    $('#userForm')[0].reset();
    $('#userId').val('');
    $('#userModalLabel').text('Add User');
    $('#password').prop('required', true);
    $('#passwordLabel').text('(Required)');
    $('#passwordHint').hide();
}

function openUserModal(user)
{
    resetUserForm();

    if (user) {
        $('#userId').val(user.id);
        $('#username').val(user.username);
        $('#role').val(user.role);
        $('#status').val(user.status);
        $('#userModalLabel').text('Edit User');
        $('#password').prop('required', false);
        $('#passwordLabel').text('(Optional)');
        $('#passwordHint').show();
    }

    $('#userModal').modal('show');
}

function editUser(id)
{
    $.get(`${usersApiUrl}/${id}`, function(data) {
        const user = parseJsonResponse(data);
        openUserModal(user);
    });
}

function saveUser(event)
{
    event.preventDefault();

    const id = $('#userId').val();
    const url = id ? `${usersApiUrl}/${id}` : usersApiUrl;
    const method = id ? 'PUT' : 'POST';

    $.ajax({
        url: url,
        type: method,
        data: $('#userForm').serialize(),
        success: function() {
            loadUsers();
            $('#userModal').modal('hide');
            swalSuccess('User saved successfully');
        },
        error: function(xhr) {
            const error = xhr.responseJSON?.error || 'Unknown error';
            swalError('Error: ' + error);
        }
    });
}

function deleteUser(id)
{
    swalConfirm('Delete user?', function() {
        $.ajax({
            url: `${usersApiUrl}/${id}`,
            type: 'DELETE',
            success: function() {
                selectedUserIds.delete(String(id));
                loadUsers();
                swalSuccess('User deleted successfully');
            },
            error: function(xhr) {
                swalError(xhr.responseJSON?.error || 'Unknown error');
            }
        });
    });
}

function deleteSelectedUsers()
{
    const ids = Array.from(selectedUserIds);

    if (ids.length === 0) {
        swalInfo('Select at least one user to delete.');
        return;
    }

    swalConfirm(`Delete ${ids.length} selected user${ids.length === 1 ? '' : 's'}?`, function() {
        $('#bulkDeleteUsersBtn').prop('disabled', true).text('Deleting...');

        const deletePromises = ids.map(id => new Promise(resolve => {
            $.ajax({
                url: `${usersApiUrl}/${id}`,
                type: 'DELETE',
                success: function() {
                    resolve({ success: true, id });
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

            selectedUserIds.clear();
            loadUsers();
            $('#bulkDeleteUsersBtn').text('Delete Selected');

            if (failed.length > 0) {
                const firstError = failed[0].error;
                swalError(`${deletedCount} deleted. ${failed.length} could not be deleted. ${firstError}`, 'Bulk delete incomplete');
                return;
            }

            swalSuccess(`${deletedCount} user${deletedCount === 1 ? '' : 's'} deleted successfully.`);
        });
    });
}

$(function() {
    loadUsers();
    $('#userForm').on('submit', saveUser);
    $('#filterUsername').on('input', function() {
        loadUsers();
    });
    $('#filterRole, #filterStatus').on('change', function() {
        loadUsers();
    });
    $('#selectAllUsers').on('change', function() {
        toggleAllUsers(this.checked);
    });
});
