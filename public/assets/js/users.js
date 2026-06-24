const usersApiUrl = 'api/users.php';

function parseJsonResponse(data) {
    return typeof data === 'string' ? JSON.parse(data) : data;
}

function renderUserRow(user) {
    return `
        <tr>
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
        renderPaginatedTable({
            data: users,
            tableSelector: '#userTable',
            currentPage: page,
            perPage: 10,
            renderRow: renderUserRow,
            sortColumns: [
                { index: 0, key: 'username' },
                { index: 1, key: 'role' },
                { index: 2, key: 'status' },
                { index: 3, key: 'created_at' }
            ]
        });
    });
}

function resetUserFilters()
{
    $('#filterUsername').val('');
    $('#filterRole').val('All');
    $('#filterStatus').val('All');
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
                loadUsers();
                swalSuccess('User deleted successfully');
            },
            error: function(xhr) {
                swalError(xhr.responseJSON?.error || 'Unknown error');
            }
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
});
