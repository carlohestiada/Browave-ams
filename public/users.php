<?php include 'layouts/header.php'; ?>
<?php include 'layouts/sidebar.php'; ?>

<div class="content-wrapper">

    <div class="d-flex justify-content-between mb-3">
        <div>
            <h2 style="font-size:26px; font-weight:700; color:#003686; margin:0; letter-spacing:-0.02em;">User Management</h2>
            <p style="font-size:13px; color:#434653; margin:4px 0 0;">Manage and organize company users.</p>
        </div>

        <button
            class="btn btn-primary"
            type="button"
            data-bs-toggle="modal"
            data-bs-target="#userModal"
            onclick="resetUserForm()">
            Add User
        </button>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form id="userFilterForm" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="filterUsername" class="form-label">Username</label>
                    <input type="text" class="form-control" id="filterUsername" name="username" placeholder="Search by username">
                </div>
                <div class="col-md-3">
                    <label for="filterRole" class="form-label">Role</label>
                    <select class="form-select" id="filterRole" name="role">
                        <option value="All">All</option>
                        <option value="Viewer">Viewer</option>
                        <option value="HR">HR</option>
                        <option value="Admin">Admin</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="filterStatus" class="form-label">Status</label>
                    <select class="form-select" id="filterStatus" name="status">
                        <option value="All">All</option>
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
                <div class="col-md-2 text-end">
                    <button type="button" class="btn btn-primary w-100" onclick="loadUsers()">Filter</button>
                    <button type="button" class="btn btn-secondary w-100 mt-2" onclick="resetUserFilters()">Reset</button>
                </div>
            </form>
        </div>
    </div>

    <!-- User Table -->
    <div class="ams-card" style="padding:0; overflow:hidden;">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; padding:12px 16px; border-bottom:1px solid #e5e7eb;">
            <div id="selectedUsersText" style="font-size:13px; color:#434653;">0 selected</div>
            <button
                type="button"
                class="btn btn-danger btn-sm"
                id="bulkDeleteUsersBtn"
                onclick="deleteSelectedUsers()"
                disabled>
                Delete Selected
            </button>
        </div>
        <div style="overflow-x:auto;">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th style="width:44px; text-align:center;">
                            <input type="checkbox" id="selectAllUsers" aria-label="Select all users">
                        </th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Created At</th>
                        <th width="180">Action</th>
                    </tr>
                </thead>
                <tbody id="userTable"></tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="userModalLabel">Add User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="userForm">
                <input type="hidden" id="userId" name="id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password <span id="passwordLabel"></span></label>
                        <input type="password" class="form-control" id="password" name="password">
                        <small class="form-text text-muted" id="passwordHint">Leave empty to keep current password</small>
                    </div>
                    <div class="mb-3">
                        <label for="role" class="form-label">Role</label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="Viewer">Viewer</option>
                            <option value="HR">HR</option>
                            <option value="Admin">Admin</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="assets/js/users.js?v=<?= filemtime(__DIR__ . '/assets/js/users.js') ?>"></script>

<?php include 'layouts/footer.php'; ?>