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

function mealDisplayValue(value) {
    const text = mealEscapeHtml(value);

    return text || '-';
}

function mealHeadcountValue(meal) {
    const mealCount = Number(meal?.meal_count);

    if (meal?.id || mealCount > 0) {
        return Number.isFinite(mealCount) ? mealCount : 0;
    }

    return Number(meal?.active_count) || 0;
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

function formatMealDate(dateString) {
    if (!dateString) {
        return '-';
    }

    const date = parseLocalDate(dateString);
    const dayName = date.toLocaleDateString('en-US', { weekday: 'long' });

    return `${mealEscapeHtml(dateString)} <span class="text-muted d-block small">${dayName}</span>`;
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

function updateMealHeadcountSummary(meals, label, range) {
    const total = (meals || []).reduce((sum, meal) => {
        return sum + mealHeadcountValue(meal);
    }, 0);

    $('#mealSummaryLabel').text(label);
    $('#mealSummaryRange').text(range);
    $('#mealSummaryTotal').text(total.toLocaleString());
}

function mealEmployeeLabel(employee) {
    const code = employee.employee_code ? `${employee.employee_code} - ` : '';

    return `${code}${employee.full_name || 'Employee'}`;
}

function loadMealEmployeeOptions(selectedEmployee) {
    const type = $('#meal_transaction_type').val() || 'arrival';
    const date = $('#meal_transaction_date').val() || getLocalDateString();
    const params = new URLSearchParams();

    params.set('exclude_transaction_type', type);
    params.set('exclude_transaction_date', date);

    if (type === 'departure') {
        params.set('status', 'Active');
    }

    $.get(mealApiUrl(`api/employees/index.php?${params.toString()}`), function(data) {
        const employees = parseJsonResponse(data);
        const selectedId = selectedEmployee?.id ? String(selectedEmployee.id) : $('#meal_employee_id').val();
        let options = '<option value="">Select employee</option>';
        let hasSelected = false;

        employees.forEach(employee => {
            const employeeId = String(employee.id);
            const selected = employeeId === String(selectedId) ? ' selected' : '';

            if (selected) {
                hasSelected = true;
            }

            options += `<option value="${employeeId}"${selected}>${mealEscapeHtml(mealEmployeeLabel(employee))}</option>`;
        });

        if (selectedEmployee?.id && !hasSelected) {
            options += `<option value="${selectedEmployee.id}" selected>${mealEscapeHtml(mealEmployeeLabel(selectedEmployee))}</option>`;
        }

        $('#meal_employee_id').html(options);
    });
}

function renderMealTransactions(transactions, type) {
    if (!transactions || !transactions.length) {
        return '<span class="text-muted">-</span>';
    }

    return `
        <div class="d-flex flex-column gap-1">
            ${transactions.map(tx => {
                const name = tx.full_name || tx.employee_code || 'Employee';
                const code = tx.employee_code && tx.full_name ? `${mealEscapeHtml(tx.employee_code)} - ` : '';

                return `
                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <span>${code}${mealEscapeHtml(name)}</span>
                        <span class="d-inline-flex gap-1">
                            <button
                                type="button"
                                class="btn btn-outline-warning btn-sm py-0 px-1"
                                onclick="editMealTransaction(${tx.id}, '${type}')">
                                Edit
                            </button>
                            <button
                                type="button"
                                class="btn btn-outline-danger btn-sm py-0 px-1"
                                onclick="deleteMealTransaction(${tx.id}, '${type}')">
                                Remove
                            </button>
                        </span>
                    </div>
                `;
            }).join('')}
        </div>
    `;
}

function renderMealRow(meal) {
    const mealCount = mealHeadcountValue(meal);
    const action = meal.id ? `
        <button class="btn btn-primary btn-sm" onclick="openMealModal({date: '${meal.date}'})">Create Plan</button>
    ` : `
        <button class="btn btn-primary btn-sm me-1" onclick="openMealModal({date: '${meal.date}'})">Create Plan</button>
    `;

    return `
        <tr class="${meal.id ? '' : 'table-warning'}">
            <td>${formatMealDate(meal.date)}</td>
            <td>${mealDisplayValue(meal.active_count)}</td>
            <td>${mealDisplayValue(mealCount)}</td>
            <td>${renderMealTransactions(meal.arrivals, 'arrival')}</td>
            <td>${renderMealTransactions(meal.departures, 'departure')}</td>
            <td>
                ${action}
            </td>
        </tr>
    `;
}

function getMealSortColumns()
{
    return [
        { index: 0, key: 'date' },
        { index: 1, key: 'active_count' },
        { index: 2, key: 'meal_count' },
        { index: 3, key: 'arrivals' },
        { index: 4, key: 'departures' }
    ];
}

function loadMealPlans()
{
    $('#mealViewMode').val('weekly');
    $('.meal-weekly-filter').removeClass('d-none');
    $('.meal-monthly-filter').addClass('d-none');
    $('#filterWeek').val(getLocalWeekString());

    loadMealsByWeek();
}

function loadMealsByWeek()
{
    const week = $('#filterWeek').val() || getLocalWeekString();
    const range = getWeekDateRange(week);

    $('#filterWeek').val(week);

    $.get(`${mealsApiUrl}/range?start_date=${range.startDate}&end_date=${range.endDate}`, function(data) {
        const meals = parseJsonResponse(data);

        updateMealHeadcountSummary(
            meals,
            'Weekly Meal Headcount',
            formatMealRange(range.startDate, range.endDate)
        );

        renderPaginatedTable({
            data: meals,
            tableSelector: '#mealTable',
            currentPage: 1,
            perPage: 7,
            renderRow: renderMealRow,
            sortColumns: getMealSortColumns()
        });
    });
}

function loadMealsByMonth()
{
    const month = $('#filterMonth').val() || getLocalMonthString();
    const range = getMonthDateRange(month);

    $('#filterMonth').val(month);

    $.get(`${mealsApiUrl}/range?start_date=${range.startDate}&end_date=${range.endDate}`, function(data) {
        const meals = parseJsonResponse(data);

        updateMealHeadcountSummary(
            meals,
            'Monthly Meal Headcount',
            formatMealRange(range.startDate, range.endDate)
        );

        renderPaginatedTable({
            data: meals,
            tableSelector: '#mealTable',
            currentPage: 1,
            perPage: 31,
            renderRow: renderMealRow,
            sortColumns: getMealSortColumns()
        });
    });
}

function changeMealViewMode()
{
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

function changeMealWeek(offset)
{
    const weekValue = $('#filterWeek').val() || getLocalWeekString();
    const range = getWeekDateRange(weekValue);
    const nextWeek = parseLocalDate(range.startDate);

    nextWeek.setDate(nextWeek.getDate() + (offset * 7));
    $('#filterWeek').val(getLocalWeekString(nextWeek));
    loadMealsByWeek();
}

function changeMealMonth(offset)
{
    const monthValue = $('#filterMonth').val() || getLocalMonthString();
    const [year, month] = monthValue.split('-').map(Number);
    const nextMonth = new Date(year, month - 1 + offset, 1);

    $('#filterMonth').val(getLocalMonthString(nextMonth));
    loadMealsByMonth();
}

function resetMealForm()
{
    $('#mealForm')[0].reset();
    $('#mealTransactionId').val('');
    $('#mealModalLabel').text('Add Meal Plan');
    $('#meal_transaction_type').val('arrival').prop('disabled', false);
    $('#meal_transaction_date').val(getLocalDateString());
    $('#meal_employee_id').html('<option value="">Select employee</option>');
}

function openMealModal(meal)
{
    resetMealForm();

    if (meal?.date) {
        $('#meal_transaction_date').val(meal.date);
    }

    loadMealEmployeeOptions();
    $('#mealModal').modal('show');
}

function editMealTransaction(id, type)
{
    $.get(mealApiUrl(`api/transactions/index.php/${id}`), function(data) {
        const transaction = parseJsonResponse(data);
        const selectedEmployee = {
            id: transaction.employee_id,
            employee_code: transaction.employee_code,
            full_name: transaction.full_name
        };

        resetMealForm();
        $('#mealTransactionId').val(transaction.id);
        $('#meal_transaction_type').val(type || transaction.transaction_type).prop('disabled', true);
        $('#meal_transaction_date').val(transaction.transaction_date);
        $('#mealModalLabel').text('Edit Meal Plan');
        loadMealEmployeeOptions(selectedEmployee);
        $('#mealModal').modal('show');
    }).fail(function() {
        swalError('Failed to load meal plan transaction');
    });
}

function saveMeal(event)
{
    event.preventDefault();

    if (!$('#meal_employee_id').val()) {
        swalError('Please select an employee');
        return;
    }

    const id = $('#mealTransactionId').val();
    const type = $('#meal_transaction_type').val();
    const url = id
        ? mealApiUrl(`api/transactions/index.php/${id}`)
        : mealApiUrl(`api/transactions/index.php/${type}`);
    const method = id ? 'PUT' : 'POST';

    $.ajax({
        url: url,
        type: method,
        data: $('#mealForm').serialize(),
        success: function() {
            refreshCurrentMealView();
            $('#mealModal').modal('hide');
            swalSuccess('Meal plan saved successfully');
        },
        error: function(xhr) {
            swalError((xhr.responseJSON?.error || 'Unknown error'));
        }
    });
}

function deleteMeal(id)
{
    if (id === 0) {
        swalError('Cannot delete unsaved meal plan.');
        return;
    }

    swalConfirm('Delete meal plan?', function() {
        $.ajax({
            url: `${mealsApiUrl}/${id}`,
            type: 'DELETE',
            success: function() {
                refreshCurrentMealView();
                swalSuccess('Meal plan deleted successfully');
            },
            error: function(xhr) {
                swalError('Error: ' + (xhr.responseJSON?.error || 'Unknown error'));
            }
        });
    });
}

function refreshCurrentMealView()
{
    if ($('#mealViewMode').val() === 'monthly') {
        loadMealsByMonth();
        return;
    }

    loadMealsByWeek();
}

function deleteMealTransaction(transactionId, transactionType)
{
    const label = transactionType === 'arrival' ? 'arrival' : 'departure';

    swalConfirm(`Remove this ${label} employee from the meal view?`, function() {
        $.ajax({
            url: mealApiUrl(`api/transactions/index.php/${transactionId}`),
            type: 'DELETE',
            success: function(response) {
                const result = parseJsonResponse(response);

                if (result.success) {
                    refreshCurrentMealView();
                    swalSuccess('Removed successfully');
                    return;
                }

                swalError(result.error || 'Unable to remove record');
            },
            error: function(xhr) {
                swalError('Error: ' + (xhr.responseJSON?.error || 'Request failed'));
            }
        });
    });
}

$(function() {
    $('#filterWeek').val(getLocalWeekString());
    $('#filterMonth').val(getLocalMonthString());
    loadMealPlans();
    $('#mealForm').on('submit', saveMeal);
    $('#meal_transaction_type, #meal_transaction_date').on('change', function() {
        $('#meal_employee_id').val('');
        loadMealEmployeeOptions();
    });
});
