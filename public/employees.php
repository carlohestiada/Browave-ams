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

    /* Bulk upload presentation layer — existing upload workflow remains unchanged. */
    .bulk-upload-dialog { max-width: 1000px; }
    .bulk-upload-dialog .ams-modal-content { border: 1px solid #dbe2ee !important; border-radius: 16px !important; }
    .bulk-upload-dialog .ams-modal-body { max-height: calc(100vh - 190px); overflow-y: auto; }
    .bulk-upload-intro { margin: 0; font-size: 13px; color: #434653; }
    .bulk-upload-template { color: #003686; font-size: 12px; font-weight: 700; text-decoration: none; }
    .bulk-dropzone { position: relative; display: flex; min-height: 220px; align-items: center; justify-content: center; padding: 28px; border: 2px dashed #aeb8c9; border-radius: 14px; background: #f8faff; text-align: center; cursor: pointer; transition: border-color .2s, background .2s, transform .2s, box-shadow .2s; }
    .bulk-dropzone:hover, .bulk-dropzone:focus-within { border-color: #003686; background: #f0f6ff; }
    .bulk-dropzone.is-dragging { border-color: #003686; background: #eaf2ff; transform: scale(1.02); box-shadow: 0 8px 24px rgba(0,54,134,.12); }
    .bulk-dropzone input[type=file] { position: absolute; width: 1px; height: 1px; opacity: 0; pointer-events: none; }
    .bulk-dropzone-icon { display: inline-flex; width: 54px; height: 54px; align-items: center; justify-content: center; border-radius: 50%; background: #e5eeff; color: #003686; margin-bottom: 11px; }
    .bulk-dropzone-icon .material-symbols-outlined { font-size: 30px; }
    .bulk-dropzone-title { color: #121c28; font-size: 16px; font-weight: 800; }
    .bulk-dropzone-copy { margin: 5px 0 13px; color: #626775; font-size: 13px; }
    .bulk-dropzone-help { display: block; margin-top: 12px; color: #737784; font-size: 11px; }
    .bulk-dropzone-release { display: none; color: #003686; font-size: 13px; font-weight: 700; }
    .bulk-dropzone.is-dragging .bulk-dropzone-release { display: block; }
    .bulk-file-selected { display: none; align-items: center; justify-content: space-between; gap: 12px; width: 100%; padding: 13px 15px; border: 1px solid #bbd1f7; border-radius: 10px; background: #fff; text-align: left; }
    .bulk-file-selected.is-visible { display: flex; }
    .bulk-file-meta { display: flex; align-items: center; gap: 10px; min-width: 0; }
    .bulk-file-meta .material-symbols-outlined { color: #003686; }
    .bulk-file-name { overflow: hidden; color: #121c28; font-size: 13px; font-weight: 700; text-overflow: ellipsis; white-space: nowrap; }
    .bulk-file-size { color: #737784; font-size: 11px; }
    .bulk-clear-file { flex: 0 0 auto; border: 0; background: transparent; color: #b91c1c; font-size: 12px; font-weight: 700; cursor: pointer; }
    .bulk-preview { display: none; animation: bulkFadeIn .2s ease-out; }
    .bulk-preview.is-visible { display: block; }
    .bulk-preview-heading { display: flex; align-items: start; justify-content: space-between; gap: 12px; margin-bottom: 12px; }
    .bulk-preview-heading h6 { margin: 0; color: #121c28; font-size: 15px; font-weight: 800; }
    .bulk-preview-heading p { margin: 3px 0 0; color: #737784; font-size: 11px; }
    .bulk-preview-stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; margin-bottom: 14px; }
    .bulk-stat { padding: 12px; border: 1px solid #dbe2ee; border-radius: 10px; background: #fff; }
    .bulk-stat span { display: block; color: #626775; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; }
    .bulk-stat strong { display: block; margin-top: 4px; color: #121c28; font-size: 19px; }
    .bulk-stat.ready { background: #f0fdf4; border-color: #bbf7d0; }.bulk-stat.ready strong { color: #15803d; }.bulk-stat.warning { background: #fffbeb; border-color: #fde68a; }.bulk-stat.warning strong { color: #b45309; }.bulk-stat.error { background: #fff7f7; border-color: #fecaca; }.bulk-stat.error strong { color: #b91c1c; }
    .bulk-preview-controls { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; flex-wrap: wrap; }
    .bulk-preview-controls .ams-input { flex: 1; min-width: 190px; }
    .bulk-preview-controls select { width: auto; min-width: 130px; }
    .bulk-table-wrap { max-height: 320px; overflow: auto; border: 1px solid #dbe2ee; border-radius: 10px; }
    .bulk-preview-table { width: 100%; margin: 0; border-collapse: separate; border-spacing: 0; font-size: 12px; }
    .bulk-preview-table th { position: sticky; top: 0; z-index: 1; padding: 10px 12px; background: #eef4ff; color: #434653; font-size: 10px; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; white-space: nowrap; }
    .bulk-preview-table td { padding: 10px 12px; border-top: 1px solid #eef1f6; color: #333946; white-space: nowrap; }.bulk-preview-table tbody tr:nth-child(even) td { background: #fbfcfe; }.bulk-preview-table tbody tr:hover td { background: #f0f6ff; }
    .bulk-pagination { display: flex; justify-content: space-between; align-items: center; gap: 10px; margin-top: 10px; color: #626775; font-size: 11px; }
    .bulk-validation-panel { margin-top: 14px; border: 1px solid #dbe2ee; border-radius: 10px; background: #fff; overflow: hidden; }.bulk-validation-panel summary { padding: 12px 14px; color: #121c28; cursor: pointer; font-size: 13px; font-weight: 700; }.bulk-validation-details { padding: 0 14px 13px; color: #626775; font-size: 12px; }.bulk-validation-details p { margin: 5px 0; }
    .bulk-uploading #progressBar { width: 100% !important; animation: bulkProgress 1.2s ease-in-out infinite; }.bulk-uploading #bulkUploadBtn { pointer-events: none; }
    @keyframes bulkFadeIn { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: translateY(0); } } @keyframes bulkProgress { 0% { transform: translateX(-75%); } 100% { transform: translateX(100%); } }
    @media (max-width: 700px) { .bulk-upload-dialog { margin: .5rem; }.bulk-preview-stats { grid-template-columns: repeat(2, 1fr); }.bulk-pagination { align-items: flex-start; flex-direction: column; } }
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
            <div style="width:140px;">
                <label class="ams-label">Gender</label>
                <select id="filterGender" class="ams-input">
                    <option value="">Any Gender</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
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
                        <label for="chinese_name" class="ams-label">Chinese Name</label>
                        <input type="text" class="ams-input" id="chinese_name" name="chinese_name" placeholder="Optional Chinese name">
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
    <div class="modal-dialog modal-dialog-centered modal-xl bulk-upload-dialog">
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
                <p class="bulk-upload-intro">Upload a CSV file with columns: <strong>Employee ID, Full Name, Gender, Department</strong>. Status will default to Active. <a class="bulk-upload-template" href="assets/templates/employees_bulk_template.csv" download>Download Template</a></p>

                <div class="bulk-dropzone" id="bulkDropzone">
                    <input type="file" class="ams-input" id="bulkUploadFile" name="file" accept=".csv" required aria-describedby="bulkDropzoneHelp">
                    <div id="bulkDropzoneEmpty">
                        <div class="bulk-dropzone-title">Drag &amp; Drop Employee CSV File</div>
                        <p class="bulk-dropzone-copy">Drop your CSV file here or <label for="bulkUploadFile" class="bulk-upload-template" style="cursor:pointer;">click Browse</label>.</p>
                        <label for="bulkUploadFile" class="btn-ams-primary" style="cursor:pointer;">Browse File</label>
                        <span class="bulk-dropzone-help" id="bulkDropzoneHelp">Supported Format: CSV (.csv) &bull; Maximum File Size: 10 MB</span>
                        <div class="bulk-dropzone-release">Release to Upload</div>
                    </div>
                    <div class="bulk-file-selected" id="bulkFileSelected" aria-live="polite">
                        <div class="bulk-file-meta"><span class="material-symbols-outlined">description</span><div><div class="bulk-file-name" id="bulkFileName"></div><div class="bulk-file-size" id="bulkFileSize"></div></div></div>
                        <button type="button" class="bulk-clear-file" id="bulkClearFile">Clear File</button>
                    </div>
                </div>

                <section class="bulk-preview" id="bulkPreview" aria-label="CSV preview">
                    <div class="bulk-preview-heading"><div><h6 id="bulkPreviewFileName">CSV Preview</h6><p id="bulkPreviewMeta">Preview is for review only. Server validation occurs during import.</p></div></div>
                    <div class="bulk-preview-stats"><div class="bulk-stat"><span>📄 Total Records</span><strong id="bulkTotalRecords">0</strong></div><div class="bulk-stat ready"><span>✅ Preview Rows</span><strong id="bulkReadyRecords">0</strong></div><div class="bulk-stat warning"><span>⚠ Warnings</span><strong id="bulkWarningRecords">—</strong></div><div class="bulk-stat error"><span>❌ Errors</span><strong id="bulkErrorRecords">—</strong></div></div>
                    <div class="bulk-preview-controls"><input class="ams-input" id="bulkPreviewSearch" type="search" placeholder="Search preview rows"><select class="ams-input" id="bulkPreviewFilter"><option value="all">All Records</option><option value="valid">Valid</option><option value="warning">Warning</option><option value="error">Error</option></select></div>
                    <div class="bulk-table-wrap"><table class="bulk-preview-table"><thead id="bulkPreviewHead"></thead><tbody id="bulkPreviewBody"></tbody></table></div>
                    <div class="bulk-pagination"><span id="bulkPreviewCount">0 records shown</span><label>Rows per page <select class="ams-input" id="bulkPreviewPageSize"><option value="25">25</option><option value="50">50</option><option value="100">100</option><option value="all">All</option></select></label></div>
                    <details class="bulk-validation-panel"><summary>✔ Ready to Import &nbsp; <span style="color:#737784; font-weight:500;">Validation details are confirmed by the existing import process.</span></summary><div class="bulk-validation-details"><p>✔ Preview loaded successfully.</p><p>⚠ Validation Warnings: shown after import if returned by the server.</p><p>✖ Validation Errors: shown after import if returned by the server.</p></div></details>
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
            <div class="ams-modal-footer">
                <button type="button" class="btn-ams-ghost" data-bs-dismiss="modal">Cancel</button>
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
