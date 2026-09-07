const workCalendarApi = 'api/work-calendar/index.php';
const workCalendarMonths = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
let workCalendarRows = [];
let workCalendarModal;

function workCalendarUrl(path = '') { return `${workCalendarApi}${path ? `/${path}` : ''}`; }
function selectedCalendarFilters() { return { year: $('#calendarYear').val(), month: $('#calendarMonth').val(), status: $('#calendarStatus').val(), reason: $('#calendarReason').val() }; }
function statusLabel(status) { return status === 'working_day' ? 'WORKING' : 'NON-WORKING'; }
function statusBadge(status) { const working = status === 'working_day'; return `<span class="badge ${working ? 'bg-success' : 'bg-secondary'}">${statusLabel(status)}</span>`; }
function escapeCalendar(value) { return $('<div>').text(value ?? '').html(); }

function setupCalendarFilters() {
    $('#calendarMonth').html(workCalendarMonths.map((month, index) => `<option value="${index + 1}">${month}</option>`).join(''));
    const year = new Date().getFullYear();
    $('#calendarYear').html(Array.from({ length: 7 }, (_, index) => year - 3 + index).map(value => `<option value="${value}">${value}</option>`).join(''));
    $('#calendarMonth').val(new Date().getMonth() + 1);
    $('#calendarYear').val(year);
}

function loadWorkCalendar() {
    const filters = selectedCalendarFilters();
    $.get(workCalendarUrl(), filters, function (response) {
        const result = typeof response === 'string' ? JSON.parse(response) : response;
        workCalendarRows = result.rows || [];
        renderSummary(result.summary || {});
        renderCalendar(result.year, result.month);
        renderTable();
    }).fail(function (xhr) { swalError(xhr.responseJSON?.error || 'Unable to load work calendar.'); });
}

function renderSummary(summary) {
    $('#workingDaysCount').text(summary.working_days || 0);
    $('#nonWorkingDaysCount').text(summary.non_working_days || 0);
    $('#specialWorkingDaysCount').text(summary.special_working_days || 0);
    $('#companyHolidaysCount').text(summary.company_holidays || 0);
}

function renderCalendar(year, month) {
    const first = new Date(year, month - 1, 1);
    const offset = (first.getDay() + 6) % 7;
    const days = new Date(year, month, 0).getDate();
    const byDate = Object.fromEntries(workCalendarRows.map(row => [row.work_date, row]));
    let html = '';
    for (let index = 0; index < offset; index += 1) html += '<div class="work-calendar-day work-calendar-day--empty"></div>';
    for (let day = 1; day <= days; day += 1) {
        const date = `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        const row = byDate[date];
        html += `<button type="button" class="work-calendar-day ${row.status === 'working_day' ? 'work-calendar-day--working' : 'work-calendar-day--non-working'}" data-date="${date}" ${window.workCalendarIsAdmin ? '' : 'disabled'}>
            <strong>${day}</strong><span>${row.day}</span>${statusBadge(row.status)}<small>${escapeCalendar(row.reason)}</small>
        </button>`;
    }
    $('#workCalendarGrid').html(html);
    $('#calendarTitle').text(`${workCalendarMonths[month - 1]} ${year}`);
    $('#calendarRecordCount').text(`${workCalendarRows.length} records`);
    $('.work-calendar-day[data-date]').on('click', function () { openWorkDayModal($(this).data('date')); });
}

function renderTable() {
    const rows = workCalendarRows;
    $('#tableRecordCount').text(`${rows.length} records`);
    $('#workCalendarTable').html(rows.length ? rows.map(row => `<tr>
        <td>${escapeCalendar(row.work_date)}</td><td>${escapeCalendar(row.day)}</td><td>${statusBadge(row.status)}</td><td>${escapeCalendar(row.reason)}</td><td>${escapeCalendar(row.notes)}</td>
        <td>${window.workCalendarIsAdmin ? `<button type="button" class="btn btn-sm btn-warning edit-work-day" data-date="${row.work_date}"><i class="bi bi-pencil-square me-1"></i>Edit</button>` : '<span class="text-muted">View only</span>'}</td>
    </tr>`).join('') : '<tr><td colspan="6" class="text-center text-muted py-4">No records match the selected filters.</td></tr>');
    $('.edit-work-day').on('click', function () { openWorkDayModal($(this).data('date')); });
}

function openWorkDayModal(date) {
    if (!window.workCalendarIsAdmin) return;
    const row = workCalendarRows.find(item => item.work_date === date);
    if (!row) return;
    $('#workDayDate').val(row.work_date);
    $('#workDayName').val(row.day);
    $('#workDayStatus').val(row.status);
    $('#workDayReason').val(row.reason);
    $('#workDayNotes').val(row.notes || '');
    workCalendarModal.show();
}

function moveCalendarMonth(offset) {
    const date = new Date(Number($('#calendarYear').val()), Number($('#calendarMonth').val()) - 1 + offset, 1);
    $('#calendarYear').val(date.getFullYear());
    $('#calendarMonth').val(date.getMonth() + 1);
    loadWorkCalendar();
}

function exportWorkCalendar(format) {
    const filters = selectedCalendarFilters();
    const query = new URLSearchParams({ ...filters, format }).toString();
    $.ajax({ url: `${workCalendarUrl('export')}?${query}`, method: 'GET', xhrFields: { responseType: 'blob' } }).done(function (blob, status, xhr) {
        const disposition = xhr.getResponseHeader('Content-Disposition') || '';
        const match = disposition.match(/filename="?([^";]+)"?/);
        const link = document.createElement('a'); link.href = URL.createObjectURL(blob); link.download = match?.[1] || `work-calendar.${format === 'excel' ? 'xls' : format}`; link.click(); URL.revokeObjectURL(link.href);
    }).fail(function (xhr) { swalError(xhr.responseJSON?.error || 'No records available to export.'); });
}

$(function () {
    setupCalendarFilters();
    workCalendarModal = new bootstrap.Modal(document.getElementById('workDayModal'));
    $('#calendarMonth, #calendarYear, #calendarStatus, #calendarReason').on('change', loadWorkCalendar);
    $('#previousMonthBtn').on('click', () => moveCalendarMonth(-1));
    $('#nextMonthBtn').on('click', () => moveCalendarMonth(1));
    $('#currentMonthBtn').on('click', () => { const date = new Date(); $('#calendarYear').val(date.getFullYear()); $('#calendarMonth').val(date.getMonth() + 1); loadWorkCalendar(); });
    $('#workCalendarExportBtn').on('click', () => new bootstrap.Modal(document.getElementById('workCalendarExportModal')).show());
    $('.export-format').on('click', function () { exportWorkCalendar($(this).data('format')); });
    $('#addWorkDayBtn').on('click', () => openWorkDayModal(`${$('#calendarYear').val()}-${String($('#calendarMonth').val()).padStart(2, '0')}-01`));
    $('#workDayForm').on('submit', function (event) {
        event.preventDefault();
        $.post(workCalendarUrl(), $(this).serialize(), function (response) {
            const result = typeof response === 'string' ? JSON.parse(response) : response;
            if (!result.success) { swalError(result.error || 'Unable to save work day.'); return; }
            workCalendarModal.hide(); loadWorkCalendar(); swalSuccess('Work day saved successfully.');
        }).fail(xhr => swalError(xhr.responseJSON?.error || 'Unable to save work day.'));
    });
    loadWorkCalendar();
});
