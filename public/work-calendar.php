<?php include 'layouts/header.php'; ?>
<?php include 'layouts/sidebar.php'; ?>

<div class="content-wrapper">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
        <div>
            <h2 class="ams-page-title">Work Calendar</h2>
            <p class="ams-page-subtitle">Manage working days, non-working days, holidays, and special working schedules.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <button type="button" class="btn btn-outline-secondary" id="workCalendarExportBtn"><i class="bi bi-download me-1"></i>Export</button>
            <?php if (currentUserRole() === 'Admin'): ?>
                <button type="button" class="btn btn-primary" id="addWorkDayBtn"><i class="bi bi-plus-lg me-1"></i>Add Work Day</button>
            <?php endif; ?>
        </div>
    </div>

    <div class="dashboard-kpi-grid mb-3">
        <div class="kpi-card"><p class="kpi-label"><i class="bi bi-calendar-check"></i> Working Days</p><div class="kpi-value-row"><p class="kpi-value kpi-value--success" id="workingDaysCount">0</p><span class="kpi-badge badge-active">Month</span></div></div>
        <div class="kpi-card"><p class="kpi-label"><i class="bi bi-calendar-x"></i> Non-Working Days</p><div class="kpi-value-row"><p class="kpi-value kpi-value--neutral" id="nonWorkingDaysCount">0</p><span class="kpi-badge badge-default">Month</span></div></div>
        <div class="kpi-card"><p class="kpi-label"><i class="bi bi-star"></i> Special Working Days</p><div class="kpi-value-row"><p class="kpi-value kpi-value--primary" id="specialWorkingDaysCount">0</p><span class="kpi-badge badge-default">Overrides</span></div></div>
        <div class="kpi-card"><p class="kpi-label"><i class="bi bi-building-slash"></i> Company Holidays</p><div class="kpi-value-row"><p class="kpi-value kpi-value--neutral" id="companyHolidaysCount">0</p><span class="kpi-badge badge-default">Month</span></div></div>
    </div>

    <div class="ams-card p-3 mb-3">
        <div class="row g-3 align-items-end">
            <div class="col-md-2"><label class="ams-label" for="calendarMonth">Month</label><select id="calendarMonth" class="ams-input"></select></div>
            <div class="col-md-2"><label class="ams-label" for="calendarYear">Year</label><select id="calendarYear" class="ams-input"></select></div>
            <div class="col-md-3"><label class="ams-label" for="calendarStatus">Status</label><select id="calendarStatus" class="ams-input"><option value="">All</option><option value="working_day">Working Day</option><option value="non_working_day">Non-Working Day</option></select></div>
            <div class="col-md-3"><label class="ams-label" for="calendarReason">Reason</label><select id="calendarReason" class="ams-input"><option value="">All</option><option>Regular Work</option><option>Weekend</option><option>Company Holiday</option><option>Special Working Day</option><option>Other</option></select></div>
            <div class="col-md-2 d-flex gap-2"><button type="button" class="btn btn-outline-secondary flex-fill" id="previousMonthBtn" title="Previous month"><i class="bi bi-chevron-left"></i></button><button type="button" class="btn btn-outline-primary flex-fill" id="currentMonthBtn">Current</button><button type="button" class="btn btn-outline-secondary flex-fill" id="nextMonthBtn" title="Next month"><i class="bi bi-chevron-right"></i></button></div>
        </div>
    </div>

    <div class="ams-card p-3 mb-3">
        <div class="d-flex justify-content-between align-items-center mb-3"><strong id="calendarTitle">Work Calendar</strong><span class="text-muted small" id="calendarRecordCount">0 records</span></div>
        <div class="work-calendar-weekdays"><span>Monday</span><span>Tuesday</span><span>Wednesday</span><span>Thursday</span><span>Friday</span><span>Saturday</span><span>Sunday</span></div>
        <div class="work-calendar-grid" id="workCalendarGrid"></div>
    </div>

    <div class="ams-card p-0 overflow-hidden">
        <div class="d-flex justify-content-between align-items-center p-3 border-bottom"><strong>Work Day Records</strong><span class="text-muted small" id="tableRecordCount">0 records</span></div>
        <div class="table-responsive"><table class="table table-bordered mb-0"><thead><tr><th>Date</th><th>Day</th><th>Status</th><th>Reason</th><th>Notes</th><th>Actions</th></tr></thead><tbody id="workCalendarTable"></tbody></table></div>
    </div>
</div>

<div class="modal fade" id="workDayModal" tabindex="-1" aria-labelledby="workDayModalLabel" aria-hidden="true">
    <div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" id="workDayModalLabel">Edit Work Day</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
        <form id="workDayForm"><div class="modal-body"><div class="mb-3"><label class="form-label" for="workDayDate">Date</label><input id="workDayDate" name="work_date" class="form-control" type="date" readonly required></div><div class="mb-3"><label class="form-label" for="workDayName">Day</label><input id="workDayName" class="form-control" type="text" readonly></div><div class="mb-3"><label class="form-label" for="workDayStatus">Work Day Status</label><select id="workDayStatus" name="status" class="form-select" required><option value="working_day">Working Day</option><option value="non_working_day">Non-Working Day</option></select></div><div class="mb-3"><label class="form-label" for="workDayReason">Reason</label><select id="workDayReason" name="reason" class="form-select" required><option>Regular Work</option><option>Weekend</option><option>Company Holiday</option><option>Special Working Day</option><option>Other</option></select></div><div class="mb-3"><label class="form-label" for="workDayNotes">Notes</label><textarea id="workDayNotes" name="notes" class="form-control" rows="3"></textarea></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Save Changes</button></div></form>
    </div></div>
</div>

<div class="modal fade" id="workCalendarExportModal" tabindex="-1" aria-labelledby="workCalendarExportLabel" aria-hidden="true"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" id="workCalendarExportLabel">Export Work Calendar</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div><div class="modal-body"><p class="text-muted">Export the records matching the currently selected month, year, status, and reason filters.</p><div class="d-grid gap-2"><button type="button" class="btn btn-outline-success export-format" data-format="excel"><i class="bi bi-file-earmark-spreadsheet me-2"></i>Export Excel</button><button type="button" class="btn btn-outline-secondary export-format" data-format="csv"><i class="bi bi-filetype-csv me-2"></i>Export CSV</button><button type="button" class="btn btn-outline-danger export-format" data-format="pdf"><i class="bi bi-file-earmark-pdf me-2"></i>Export PDF</button></div></div></div></div></div>

<script>window.workCalendarIsAdmin = <?= json_encode(currentUserRole() === 'Admin') ?>;</script>
<script src="assets/js/work-calendar.js?v=<?= filemtime(__DIR__ . '/assets/js/work-calendar.js') ?>"></script>
<?php include 'layouts/footer.php'; ?>
