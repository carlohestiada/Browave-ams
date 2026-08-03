<?php include 'layouts/header.php'; ?>
<?php include 'layouts/sidebar.php'; ?>

<link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(__DIR__ . '/assets/css/style.css') ?>">

<div class="content-wrapper">

    <!-- Page Header -->
    <div class="d-flex justify-content-between mb-3 flex-wrap gap-2">
        <div>
            <h2 class="ams-page-title">Department Management</h2>
            <p class="ams-page-subtitle">Manage and organize company departments.</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#departmentModal" onclick="resetDepartmentForm()">Add Department</button>
                        <button class="btn-ams-ghost" type="button" data-bs-toggle="modal" data-bs-target="#departmentBulkUploadModal">Bulk Upload</button>
        </div>
    </div>

    <!-- Filters -->
    <div class="ams-card" style="padding:16px; margin-bottom:16px;">
        <div style="display:flex; flex-wrap:wrap; gap:16px; align-items:flex-end;">
            <div style="flex:1; min-width:220px;">
                <label class="ams-label">Search Department</label>
                <input
                    id="departmentSearchInput"
                    type="text"
                    class="ams-input"
                    placeholder="Department name">
            </div>
            <button type="button" onclick="resetDepartmentFilters()" class="btn-ams-ghost" style="height:40px;">
                Reset
            </button>
        </div>
    </div>

    <!-- Table Card -->
    <div class="ams-card" style="padding:0; overflow:hidden;">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; padding:12px 16px; border-bottom:1px solid #e5e7eb;">
            <div id="selectedDepartmentsText" style="font-size:13px; color:#434653;">0 selected</div>
            <button
                type="button"
                class="btn btn-danger btn-sm"
                id="bulkDeleteDepartmentsBtn"
                onclick="deleteSelectedDepartments()"
                disabled>
                Delete Selected
            </button>
        </div>
        <div style="overflow-x:auto;">
            <table class="table table-bordered" data-export-title="Department Data">
                <thead>
                    <tr>
                        <th style="width:44px; text-align:center;">
                            <input type="checkbox" id="selectAllDepartments" aria-label="Select all departments">
                        </th>
                        <th>Department Name</th>
                        <th style="text-align:right; width:160px;">Actions</th>
                    </tr>
                </thead>
                <tbody id="departmentTable">
                    <tr>
                        <td colspan="3" style="text-align:center; padding:48px 24px; color:#737784;">
                            <i class="bi bi-building" style="font-size:28px; display:block; margin-bottom:8px; opacity:0.5;"></i>
                            Loading departments…
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</div>

<div class="modal fade" id="departmentBulkUploadModal" tabindex="-1" aria-labelledby="departmentBulkUploadModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered department-upload-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <div><h5 class="modal-title" id="departmentBulkUploadModalLabel">Bulk Upload Departments</h5><small class="text-muted">Upload a CSV with one <strong>Department Name</strong> column.</small></div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <a href="assets/templates/departments_bulk_template.csv" download class="bulk-upload-template">Download Template</a>
                <div class="department-dropzone mt-2" id="departmentDropzone">
                    <input type="file" id="departmentBulkUploadFile" accept=".csv" aria-describedby="departmentUploadHelp">
                    <div id="departmentUploadEmpty"><div class="department-upload-icon"><i class="bi bi-cloud-arrow-up"></i></div><div class="fw-bold">Drag &amp; Drop Department CSV File</div><div class="text-muted small my-2">Drop your CSV file here or <label for="departmentBulkUploadFile" class="bulk-upload-template" style="cursor:pointer;">click Browse</label>.</div><label for="departmentBulkUploadFile" class="btn-ams-primary" style="cursor:pointer;">Browse File</label><small class="d-block text-muted mt-2" id="departmentUploadHelp">Supported Format: CSV (.csv) &bull; Maximum File Size: 10 MB</small></div>
                    <div class="department-upload-file" id="departmentUploadFileState"><div><i class="bi bi-file-earmark-text text-primary me-2"></i><strong id="departmentUploadFileName"></strong><small class="d-block text-muted ms-4" id="departmentUploadFileSize"></small></div><button type="button" class="btn btn-link btn-sm text-danger" id="departmentClearUploadFile">Clear File</button></div>
                </div>
                <section class="department-preview" id="departmentUploadPreview"><div class="d-flex justify-content-between align-items-center mb-2"><strong>CSV Preview</strong><small class="text-muted" id="departmentPreviewCount"></small></div><div class="department-preview-wrap"><table class="table table-sm table-hover"><thead id="departmentPreviewHead"></thead><tbody id="departmentPreviewBody"></tbody></table></div></section>
                <div class="mt-3" id="departmentUploadProgress" style="display:none;"><div class="d-flex justify-content-between small mb-1"><span id="departmentUploadProgressText">Importing...</span><span id="departmentUploadProgressCount"></span></div><div class="progress" style="height:6px;"><div class="progress-bar" id="departmentUploadProgressBar" style="width:0%"></div></div></div>
                <div class="department-upload-results" id="departmentUploadResults" aria-live="polite"></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn-ams-primary" id="departmentBulkUploadBtn">Import Departments</button></div>
        </div>
    </div>
</div>

<div class="modal fade" id="departmentModal" tabindex="-1" aria-labelledby="departmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <!-- Header -->
            <div class="modal-header">
                <div style="display:flex; align-items:center; gap:10px;">
                    <div style="width:36px; height:36px; border-radius:8px; background:#ffffff; border:1px solid #c3c6d5; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                        <i class="bi bi-building" style="font-size:16px; color:#003686;"></i>
                    </div>
                    <h5 class="modal-title" id="departmentModalLabel" style="margin:0;">Add Department</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="departmentForm">
                <input type="hidden" id="departmentId" name="id">

                <!-- Body -->
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="department_name" class="form-label">Department Name</label>
                        <input type="text" class="form-control" id="department_name" name="department_name" placeholder="e.g. Engineering" required>
                    </div>
                </div>

                <!-- Footer -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i>
                        Save Department
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

<script src="assets/js/department.js?v=<?= filemtime(__DIR__ . '/assets/js/department.js') ?>"></script>

<?php include 'layouts/footer.php'; ?>
