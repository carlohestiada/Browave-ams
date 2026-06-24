<?php include 'layouts/header.php'; ?>
<?php include 'layouts/sidebar.php'; ?>

<style>
    .employee-sort-btn {
        border: 0;
        background: transparent;
        color: inherit;
        font: inherit;
        font-weight: inherit;
        padding: 0;
        text-align: left;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .employee-sort-indicator {
        display: inline-block;
        min-width: 10px;
        font-size: 10px;
        line-height: 1;
    }
</style>

<div class="content-wrapper">

    <!-- Page Header -->
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px; flex-wrap:wrap; gap:12px;">
        <div>
            <h2 style="font-size:26px; font-weight:700; color:#003686; margin:0; letter-spacing:-0.02em;">Employee Management</h2>
            <p style="font-size:13px; color:#434653; margin:4px 0 0;">Manage staffing, accommodation assignments, and personnel records.</p>
        </div>
        <div>
            <button
                class="btn-ams-primary"
                data-bs-toggle="modal"
                data-bs-target="#employeeModal">
                Add Employee
            </button>
            <button
                class="btn-ams-ghost"
                data-bs-toggle="modal"
                data-bs-target="#bulkUploadModal"
                style="border:1px solid #c3c6d5;">
                Bulk Upload
            </button>
        </div>
    </div>
    <div class="alert alert-info py-3 px-3 mb-3" role="alert" style="font-size:0.95rem;">
        <strong>Note:</strong> Create departments first before adding employees. Use the <a href="departments.php" class="alert-link text-decoration-underline" style="font-weight:600;">Departments</a> page to begin.
    </div>

    <!-- Filter Bar -->
    <div class="ams-card" style="padding:16px; margin-bottom:20px;">
        <div style="display:flex; flex-wrap:wrap; gap:16px; align-items:flex-end;">
            <div style="flex:1; min-width:200px;">
                <label class="ams-label">Search Employee</label>
                <div style="position:relative;">
                    <input id="searchInput" type="text"
                        class="ams-input"
                        style="padding-left:36px;"
                        placeholder="ID, Name, or Department">
                </div>
            </div>
            <div style="width:180px;">
                <label class="ams-label">Department</label>
                <select id="filterDepartment" class="ams-input">
                    <option value="">All Departments</option>
                </select>
            </div>
            <div style="width:150px;">
                <label class="ams-label">Status</label>
                <select id="filterStatus" class="ams-input">
                    <option value="">Any Status</option>
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                </select>
            </div>
            <button onclick="resetFilters()" class="btn-ams-ghost" style="height:40px;">
                Reset
            </button>
        </div>
    </div>

    <!-- Table Card -->
    <div class="ams-card" style="padding:0; overflow:hidden;">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; padding:12px 16px; border-bottom:1px solid #e5e7eb;">
            <div id="selectedEmployeesText" style="font-size:13px; color:#434653;">0 selected</div>
            <button
                type="button"
                class="btn btn-danger btn-sm"
                id="bulkDeleteEmployeesBtn"
                onclick="deleteSelectedEmployees()"
                disabled>
                Delete Selected
            </button>
        </div>
        <div style="overflow-x:auto;">
            <table class="ams-table" data-export-title="Employee Data">
                <thead>
                    <tr>
                        <th style="width:44px; text-align:center;">
                            <input type="checkbox" id="selectAllEmployees" aria-label="Select all employees">
                        </th>
                        <th><button type="button" class="employee-sort-btn" data-sort-key="employee_code">Employee ID <span class="employee-sort-indicator" data-sort-indicator="employee_code"></span></button></th>
                        <th><button type="button" class="employee-sort-btn" data-sort-key="full_name">Full Name <span class="employee-sort-indicator" data-sort-indicator="full_name"></span></button></th>
                        <th><button type="button" class="employee-sort-btn" data-sort-key="gender">Gender <span class="employee-sort-indicator" data-sort-indicator="gender"></span></button></th>
                        <th><button type="button" class="employee-sort-btn" data-sort-key="department_name">Department <span class="employee-sort-indicator" data-sort-indicator="department_name"></span></button></th>
                        <th><button type="button" class="employee-sort-btn" data-sort-key="status">Status <span class="employee-sort-indicator" data-sort-indicator="status"></span></button></th>
                        <th style="text-align:right; width:160px;">Actions</th>
                    </tr>
                </thead>
                <tbody id="employeeTable">
                    <tr>
                        <td colspan="7" style="text-align:center; padding:48px 24px; color:#737784;">
                            <span class="material-symbols-outlined" style="font-size:32px; display:block; margin-bottom:8px; opacity:0.5;">group</span>
                            Loading employees…
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="employeeModal" tabindex="-1" aria-labelledby="employeeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content ams-modal-content">

            <!-- Header -->
            <div class="ams-modal-header">
                <div style="display:flex; align-items:center; gap:10px;">
                    <h5 class="modal-title" id="employeeModalLabel" style="margin:0; font-size:16px; font-weight:700; color:#121c28;">Add Employee</h5>
                </div>
                <button type="button"
                    style="width:32px; height:32px; border:none; background:transparent; border-radius:8px; display:flex; align-items:center; justify-content:center; color:#737784; cursor:pointer; transition:all 0.15s;"
                    data-bs-dismiss="modal" aria-label="Close"
                    onmouseover="this.style.background='#e5eeff'; this.style.color='#003686';"
                    onmouseout="this.style.background='transparent'; this.style.color='#737784';">
                    <span class="material-symbols-outlined" style="font-size:20px;">close</span>
                </button>
            </div>

            <form id="employeeForm">
                <input type="hidden" id="employeeId" name="id">

                <!-- Body -->
                <div class="ams-modal-body">

                    <div class="ams-field">
                        <label for="employee_code" class="ams-label">Employee ID</label>
                        <input type="text" class="ams-input" id="employee_code" name="employee_code" placeholder="e.g. PS26Y087" required>
                    </div>

                    <div class="ams-field">
                        <label for="full_name" class="ams-label">Full Name</label>
                        <input type="text" class="ams-input" id="full_name" name="full_name" placeholder="Full legal name" required>
                    </div>

                    <div class="ams-field">
                        <label class="ams-label">Gender</label>
                        <select class="ams-input" id="gender" name="gender" required>
                            <option value="">Select gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>

                    <div class="ams-field">
                        <label for="department_id" class="ams-label">Department</label>
                        <select class="ams-input" id="department_id" name="department_id" required>
                            <option value="">Loading departments...</option>
                        </select>
                    </div>

                    <div class="ams-field">
                        <label class="ams-label">Status</label>
                        <select class="ams-input" id="status" name="status" required>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>

                </div>

                <!-- Footer -->
                <div class="ams-modal-footer">
                    <button type="button" class="btn-ams-ghost" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-ams-primary">
                        Save Employee
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>


<div class="modal fade" id="bulkUploadModal" tabindex="-1" aria-labelledby="bulkUploadModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content ams-modal-content">

            <!-- Header -->
            <div class="ams-modal-header">
                <div style="display:flex; align-items:center; gap:10px;">
                    <h5 class="modal-title" id="bulkUploadModalLabel" style="margin:0; font-size:16px; font-weight:700; color:#121c28;">Bulk Upload Employees</h5>
                </div>
                <button type="button"
                    style="width:32px; height:32px; border:none; background:transparent; border-radius:8px; display:flex; align-items:center; justify-content:center; color:#737784; cursor:pointer; transition:all 0.15s;"
                    data-bs-dismiss="modal" aria-label="Close"
                    onmouseover="this.style.background='#e5eeff'; this.style.color='#003686';"
                    onmouseout="this.style.background='transparent'; this.style.color='#737784';">
                    <span class="material-symbols-outlined" style="font-size:20px;">close</span>
                </button>
            </div>

            <!-- Body -->
            <div class="ams-modal-body">
                <p style="font-size:13px; color:#434653; margin-bottom:12px;">Upload a CSV file with columns: <strong>Employee ID, Full Name, Gender, Department</strong>. Status will default to Active.</p>
                <a href="assets/templates/employees_bulk_template.csv" download style="font-size:12px; color:#003686; text-decoration:none; font-weight:600; display:inline-flex; align-items:center; gap:4px;">
                    Download Template
                </a>

                <div class="ams-field" style="margin-top:16px;">
                    <label for="bulkUploadFile" class="ams-label">Select CSV File</label>
                    <input type="file" class="ams-input" id="bulkUploadFile" name="file" accept=".csv" required>
                </div>

                <div id="uploadProgress" style="display:none; margin-top:16px;">
                    <div style="font-size:13px; color:#434653; margin-bottom:8px;">
                        <span id="progressText">Uploading...</span>
                    </div>
                    <div style="width:100%; height:6px; background:#eef4ff; border-radius:3px; overflow:hidden;">
                        <div id="progressBar" style="height:100%; background:#003686; width:0%; transition:width 0.2s;"></div>
                    </div>
                </div>

                <div id="uploadResults" style="display:none; margin-top:16px; padding:12px; background:#f8f9ff; border-radius:6px; max-height:200px; overflow-y:auto; font-size:13px;">
                    <!-- Results injected here -->
                </div>
            </div>

            <!-- Footer -->
            <div class="ams-modal-footer">
                <button type="button" class="btn-ams-ghost" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn-ams-primary" id="bulkUploadBtn" onclick="startBulkUpload()">
                    Upload
                </button>
            </div>

        </div>
    </div>
</div>


<script src="assets/js/employee.js"></script>
<script src="assets/js/bulk-upload.js"></script>

<?php include 'layouts/footer.php'; ?>
