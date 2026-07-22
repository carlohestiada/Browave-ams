const mealsApiUrl = 'api/meals.php';

function mealApiUrl(path) {
    const base = window.location.pathname.replace(/\/[^\/]+$/, '');
    return base + '/' + path.replace(/^\/+/, '');
}

function parseJsonResponse(data) {
    return typeof data === 'string' ? JSON.parse(data) : data;
}

function mealEscapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function getLocalDateString(date = new Date()) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
}

function getLocalMonthString(date = new Date()) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');

    return `${year}-${month}`;
}

function getLocalWeekString(date = new Date()) {
    const weekDate = new Date(date.getFullYear(), date.getMonth(), date.getDate());
    const day = weekDate.getDay() || 7;

    weekDate.setDate(weekDate.getDate() + 4 - day);

    const yearStart = new Date(weekDate.getFullYear(), 0, 1);
    const week = Math.ceil((((weekDate - yearStart) / 86400000) + 1) / 7);

    return `${weekDate.getFullYear()}-W${String(week).padStart(2, '0')}`;
}

function parseLocalDate(dateString) {
    const [year, month, day] = dateString.split('-').map(Number);
    return new Date(year, month - 1, day);
}

function getMonthDateRange(monthString) {
    const [year, month] = monthString.split('-').map(Number);
    const start = new Date(year, month - 1, 1);
    const end = new Date(year, month, 0);

    return {
        startDate: getLocalDateString(start),
        endDate: getLocalDateString(end)
    };
}

function getWeekDateRange(weekString) {
    const [yearText, weekText] = weekString.split('-W');
    const year = Number(yearText);
    const week = Number(weekText);
    const januaryFourth = new Date(year, 0, 4);
    const day = januaryFourth.getDay() || 7;
    const start = new Date(year, 0, 4 - day + 1 + ((week - 1) * 7));
    const end = new Date(start);

    end.setDate(start.getDate() + 6);

    return {
        startDate: getLocalDateString(start),
        endDate: getLocalDateString(end)
    };
}

function formatMealRange(startDate, endDate) {
    return `${mealEscapeHtml(startDate)} to ${mealEscapeHtml(endDate)}`;
}

function formatPlannerDate(dateString) {
    if (!dateString) {
        return '-';
    }

    const date = parseLocalDate(dateString);
    const dayName = date.toLocaleDateString('en-US', { weekday: 'short' });
    return `${mealEscapeHtml(dateString)}<br><span class="meal-planner-muted">${mealEscapeHtml(dayName)}</span>`;
}

function updateMealHeadcountSummary(meals, label, range) {
    const totalHeadcount = (meals || []).reduce((sum, meal) => sum + Number(meal?.headcount ?? meal?.active_count ?? 0), 0);
    const totalCompanyPay = (meals || []).reduce((sum, meal) => sum + Number(meal?.company_pay ?? meal?.headcount ?? 0), 0);
    const totalLunchBox = (meals || []).reduce((sum, meal) => sum + Number(meal?.lunch_box ?? meal?.meal_count ?? 0), 0);
    const totalArrivals = (meals || []).reduce((sum, meal) => sum + (meal?.arrivals?.length || 0), 0);
    const totalDepartures = (meals || []).reduce((sum, meal) => sum + (meal?.departures?.length || 0), 0);
    const averageDailyHeadcount = meals.length ? Math.round(totalHeadcount / meals.length) : 0;

    $('#mealSummaryLabel').text(label);
    $('#mealSummaryRange').text(range);
    $('#mealSummaryTotal').text(totalLunchBox.toLocaleString());
    $('#mealPlannerSummary').html(`
        <div class="meal-planner-summary__card">
            <div class="meal-planner-summary__label">Total Headcount</div>
            <div class="meal-planner-summary__value">${totalHeadcount.toLocaleString()}</div>
        </div>
        <div class="meal-planner-summary__card">
            <div class="meal-planner-summary__label">Total Company Pay</div>
            <div class="meal-planner-summary__value">${totalCompanyPay.toLocaleString()}</div>
        </div>
        <div class="meal-planner-summary__card">
            <div class="meal-planner-summary__label">Total Lunch Box</div>
            <div class="meal-planner-summary__value">${totalLunchBox.toLocaleString()}</div>
        </div>
        <div class="meal-planner-summary__card">
            <div class="meal-planner-summary__label">Total Arrivals</div>
            <div class="meal-planner-summary__value">${totalArrivals.toLocaleString()}</div>
        </div>
        <div class="meal-planner-summary__card">
            <div class="meal-planner-summary__label">Total Departures</div>
            <div class="meal-planner-summary__value">${totalDepartures.toLocaleString()}</div>
        </div>
        <div class="meal-planner-summary__card">
            <div class="meal-planner-summary__label">Average Daily Headcount</div>
            <div class="meal-planner-summary__value">${averageDailyHeadcount.toLocaleString()}</div>
        </div>
    `);
}

function renderPlannerPeople(people, type) {
    if (!people || !people.length) {
        return '<span class="meal-planner-muted">—</span>';
    }

    return `
        <div class="meal-planner-people meal-planner-people--${type}">
            ${people.map((person) => {
                const name = person.full_name || person.employee_code || 'Employee';
                return `<div class="meal-planner-person">• ${mealEscapeHtml(name)}</div>`;
            }).join('')}
        </div>
    `;
}

function renderMealPlannerRow(meal) {
    const rowClass = meal?.is_sunday ? 'meal-planner-row--sunday' : '';
    const dayLabel = parseLocalDate(meal.date).toLocaleDateString('en-US', { weekday: 'long' });
    const lunchValue = Number(meal?.lunch_box ?? meal?.meal_count ?? 0);

    let lunchCell = `<div class="meal-planner-muted">${lunchValue} Lunch Box</div>`;

    if (meal?.is_sunday) {
        lunchCell = `
            <div class="d-flex align-items-center gap-2">
                <input type="number" min="0" class="form-control form-control-sm meal-planner-lunch-box-input" value="${lunchValue}" data-date="${meal.date}">
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="saveSundayLunchBoxValue('${meal.date}', this.previousElementSibling.value)">Save</button>
            </div>
        `;
    }

    return `
        <tr class="${rowClass}">
            <td>
                <div class="meal-planner-day-title">
                    <span>${mealEscapeHtml(dayLabel)}</span>
                    ${meal?.is_sunday ? '<span class="meal-planner-badge">Rest Day</span>' : ''}
                </div>
            </td>
            <td>${formatPlannerDate(meal.date)}</td>
            <td>${mealEscapeHtml(meal?.headcount ?? meal?.active_count ?? 0)}</td>
            <td>${mealEscapeHtml(meal?.company_pay ?? meal?.headcount ?? 0)}</td>
            <td>${lunchCell}</td>
            <td>${renderPlannerPeople(meal?.arrivals, 'arrival')}</td>
            <td>${renderPlannerPeople(meal?.departures, 'departure')}</td>
            <td><div class="meal-planner-remarks">${mealEscapeHtml(meal?.remarks || `${lunchValue} Lunch Box`)}</div></td>
        </tr>
    `;
}

function renderMealPlannerTable(meals) {
    window.currentMealPlannerRows = meals || [];

    if (!meals || !meals.length) {
        $('#mealTable').html('<tr><td colspan="8" class="text-center text-muted">No meal data available.</td></tr>');
        return;
    }

    $('#mealTable').html(meals.map(renderMealPlannerRow).join(''));
}

function loadMealPlans() {
    $('#mealViewMode').val('weekly');
    $('.meal-weekly-filter').removeClass('d-none');
    $('.meal-monthly-filter').addClass('d-none');
    $('#filterWeek').val(getLocalWeekString());

    loadMealsByWeek();
}

function loadMealsByWeek() {
    const week = $('#filterWeek').val() || getLocalWeekString();
    const range = getWeekDateRange(week);

    $('#filterWeek').val(week);

    $.get(mealApiUrl(`api/meals.php/range?start_date=${range.startDate}&end_date=${range.endDate}`), function(data) {
        const meals = parseJsonResponse(data);

        updateMealHeadcountSummary(meals, `Week ${week.split('-W')[1]}`, formatMealRange(range.startDate, range.endDate));
        renderMealPlannerTable(meals);
    });
}

function loadMealsByMonth() {
    const month = $('#filterMonth').val() || getLocalMonthString();
    const range = getMonthDateRange(month);

    $('#filterMonth').val(month);

    $.get(mealApiUrl(`api/meals.php/range?start_date=${range.startDate}&end_date=${range.endDate}`), function(data) {
        const meals = parseJsonResponse(data);

        updateMealHeadcountSummary(meals, month, formatMealRange(range.startDate, range.endDate));
        renderMealPlannerTable(meals);
    });
}

function changeMealViewMode() {
    const mode = $('#mealViewMode').val();

    if (mode === 'monthly') {
        $('.meal-weekly-filter').addClass('d-none');
        $('.meal-monthly-filter').removeClass('d-none');
        $('#filterMonth').val($('#filterMonth').val() || getLocalMonthString());
        loadMealsByMonth();
        return;
    }

    $('.meal-weekly-filter').removeClass('d-none');
    $('.meal-monthly-filter').addClass('d-none');
    $('#filterWeek').val($('#filterWeek').val() || getLocalWeekString());
    loadMealsByWeek();
}

function changeMealWeek(offset) {
    const weekValue = $('#filterWeek').val() || getLocalWeekString();
    const range = getWeekDateRange(weekValue);
    const nextWeek = parseLocalDate(range.startDate);

    nextWeek.setDate(nextWeek.getDate() + (offset * 7));
    $('#filterWeek').val(getLocalWeekString(nextWeek));
    loadMealsByWeek();
}

function changeMealMonth(offset) {
    const monthValue = $('#filterMonth').val() || getLocalMonthString();
    const [year, month] = monthValue.split('-').map(Number);
    const nextMonth = new Date(year, month - 1 + offset, 1);

    $('#filterMonth').val(getLocalMonthString(nextMonth));
    loadMealsByMonth();
}

function saveSundayLunchBoxValue(date, value) {
    const payload = {
        date: date,
        lunch_box: value,
        mode: 'sunday_lunch_box'
    };

    $.post(mealApiUrl('api/meals.php'), payload, function(response) {
        const result = parseJsonResponse(response);

        if (result.success) {
            refreshCurrentMealView();
            if (typeof swalSuccess === 'function') {
                swalSuccess('Sunday lunch box updated');
            }
            return;
        }

        if (typeof swalError === 'function') {
            swalError(result.error || 'Unable to save Sunday lunch box');
        }
    }).fail(function(xhr) {
        if (typeof swalError === 'function') {
            swalError(xhr.responseJSON?.error || 'Unable to save Sunday lunch box');
        }
    });
}

function refreshCurrentMealView() {
    if ($('#mealViewMode').val() === 'monthly') {
        loadMealsByMonth();
        return;
    }

    loadMealsByWeek();
}

$(function() {
    $('#filterWeek').val(getLocalWeekString());
    $('#filterMonth').val(getLocalMonthString());
    loadMealPlans();
});
