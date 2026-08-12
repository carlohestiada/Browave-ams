<?php include 'layouts/header.php'; ?>
<?php include 'layouts/sidebar.php'; ?>

<link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(__DIR__ . '/assets/css/style.css') ?>">

<div class="content-wrapper">

    <!-- Page Header -->
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px; flex-wrap:wrap; gap:12px;">
        <div>
            <h2 style="font-size:26px; font-weight:700; color:#003686; margin:0; letter-spacing:-0.02em;">Employee Management</h2>
            <p style="font-size:13px; color:#434653; margin:4px 0 0;">Manage staffing, accommodation assignments, and personnel records.</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn-ams-primary" type="button" data-bs-toggle="modal" data-bs-target="#employeeModal">Add Employee</button>
            <button class="btn-ams-ghost" type="button" data-bs-toggle="modal" data-bs-target="#bulkUploadModal" style="border:1px solid #c3c6d5;">Bulk Upload</button>
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
            <div style="width:140px;">
                <label class="ams-label">Gender</label>
                <select id="filterGender" class="ams-input">
                    <option value="">Any Gender</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Others">Others</option>
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
                        <th><button type="button" class="employee-sort-btn" data-sort-key="english_name">English Name <span class="employee-sort-indicator" data-sort-indicator="english_name"></span></button></th>
                        <th><button type="button" class="employee-sort-btn" data-sort-key="chinese_name">Chinese Name <span class="employee-sort-indicator" data-sort-indicator="chinese_name"></span></button></th>
                        <th><button type="button" class="employee-sort-btn" data-sort-key="gender">Gender <span class="employee-sort-indicator" data-sort-indicator="gender"></span></button></th>
                        <th><button type="button" class="employee-sort-btn" data-sort-key="department_name">Department <span class="employee-sort-indicator" data-sort-indicator="department_name"></span></button></th>
                        <th><button type="button" class="employee-sort-btn" data-sort-key="status">Status <span class="employee-sort-indicator" data-sort-indicator="status"></span></button></th>
                        <th style="text-align:right; width:160px;">Actions</th>
                    </tr>
                </thead>
                <tbody id="employeeTable">
                    <tr>
                        <td colspan="8" style="text-align:center; padding:48px 24px; color:#737784;">
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
                        <label for="english_name" class="ams-label">English Name</label>
                        <input type="text" class="ams-input" id="english_name" name="english_name" placeholder="Optional English name">
                    </div>

                    <div class="ams-field">
                        <label for="chinese_name" class="ams-label">Chinese Name</label>
                        <input type="text" class="ams-input" id="chinese_name" name="chinese_name" placeholder="Optional Chinese name">
                    </div>

                    <div class="ams-field">
                        <label class="ams-label">Gender</label>
                        <select class="ams-input" id="gender" name="gender">
                            <option value="Other">Other</option>
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
    <div class="modal-dialog modal-dialog-centered employee-upload-dialog">
        <div class="modal-content">

            <!-- Header -->
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="bulkUploadModalLabel">Bulk Upload Employees</h5><small class="text-muted">Upload a UTF-8 CSV with Employee ID, English Name, Gender, and Department. Chinese Name is optional. Save the file as UTF-8 to support Traditional Chinese characters.</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Body -->
            <div class="modal-body">
                <a class="bulk-upload-template" href="assets/templates/employees_bulk_template.csv" download>Download Template</a>
                <p class="text-muted mb-3">Tip: Save the CSV as UTF-8 encoding to preserve Traditional Chinese names like 謝德偉, 張志明, and 林美玲.</p>

                <div class="bulk-dropzone" id="bulkDropzone">
                    <input type="file" class="ams-input" id="bulkUploadFile" name="file" accept=".csv" required aria-describedby="bulkDropzoneHelp">
                    <div id="bulkDropzoneEmpty">
                        <div class="department-upload-icon"><i class="bi bi-cloud-arrow-up"></i></div>
                        <div class="bulk-dropzone-title">Drag &amp; Drop Employee CSV File</div>
                        <p class="bulk-dropzone-copy">Drop your CSV file here or <label for="bulkUploadFile" class="bulk-upload-template" style="cursor:pointer;">click Browse</label>.</p>
                        <label for="bulkUploadFile" class="btn-ams-primary" style="cursor:pointer;">Browse File</label>
                        <span class="bulk-dropzone-help" id="bulkDropzoneHelp">Supported Format: CSV (.csv) &bull; Maximum File Size: 10 MB</span>
                        <div class="bulk-dropzone-release">Release to Upload</div>
                    </div>
                    <div class="bulk-file-selected" id="bulkFileSelected" aria-live="polite">
                        <div class="bulk-file-meta"><span class="material-symbols-outlined">description</span>
                            <div>
                                <div class="bulk-file-name" id="bulkFileName"></div>
                                <div class="bulk-file-size" id="bulkFileSize"></div>
                            </div>
                        </div>
                        <button type="button" class="bulk-clear-file bulk-action-button bulk-action-neutral" id="bulkClearFile"><span class="material-symbols-outlined"></span>Clear File</button>
                    </div>
                </div>

                <section class="bulk-preview" id="bulkPreview" aria-label="CSV preview">
                    <div class="bulk-preview-heading">
                        <div>
                            <h6 id="bulkPreviewFileName">CSV Preview</h6>
                            <p id="bulkPreviewMeta">Preview is for review only. Server validation occurs during import.</p>
                        </div><small class="text-muted" id="bulkPreviewCount">0 records shown</small>
                    </div>
                    <div class="bulk-preview-stats">
                        <div class="bulk-stat"><span>📄 Total Records</span><strong id="bulkTotalRecords">0</strong></div>
                        <div class="bulk-stat ready"><span>✅ Preview Rows</span><strong id="bulkReadyRecords">0</strong></div>
                        <div class="bulk-stat warning"><span>⚠ Warnings</span><strong id="bulkWarningRecords">—</strong></div>
                        <div class="bulk-stat error"><span>❌ Errors</span><strong id="bulkErrorRecords">—</strong></div>
                    </div>
                    <div class="bulk-preview-controls"><input class="ams-input" id="bulkPreviewSearch" type="search" placeholder="Search preview rows"><select class="ams-input" id="bulkPreviewFilter">
                            <option value="all">All Records</option>
                            <option value="valid">Valid</option>
                            <option value="warning">Warning</option>
                            <option value="error">Error</option>
                        </select></div>
                    <div class="bulk-table-wrap">
                        <table class="bulk-preview-table">
                            <thead id="bulkPreviewHead"></thead>
                            <tbody id="bulkPreviewBody"></tbody>
                        </table>
                    </div>
                    <div class="bulk-pagination"><span>Rows shown</span><label>Rows per page <select class="ams-input" id="bulkPreviewPageSize">
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                                <option value="all">All</option>
                            </select></label></div>
                    <details class="bulk-validation-panel">
                        <summary>✔ Ready to Import &nbsp; <span style="color:#737784; font-weight:500;">Validation details are confirmed by the existing import process.</span></summary>
                        <div class="bulk-validation-details">
                            <p>✔ Preview loaded successfully.</p>
                            <p>⚠ Validation Warnings: shown after import if returned by the server.</p>
                            <p>✖ Validation Errors: shown after import if returned by the server.</p>
                        </div>
                    </details>
                </section>

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
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn-ams-primary" id="bulkUploadBtn" onclick="startBulkUpload()">
                    Import Employees
                </button>
            </div>

        </div>
    </div>
</div>


<script src="assets/js/employee.js?v=<?= filemtime(__DIR__ . '/assets/js/employee.js') ?>"></script>
<script src="assets/js/bulk-upload.js?v=<?= filemtime(__DIR__ . '/assets/js/bulk-upload.js') ?>"></script>

<?php include 'layouts/footer.php'; ?>