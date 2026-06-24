const mealsApiUrl = 'api/meals.php';

function parseJsonResponse(data) {
    return typeof data === 'string' ? JSON.parse(data) : data;
}

function getLocalDateString(date = new Date()) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
}

function renderMealRow(meal) {
    return `
        <tr>
            <td>${meal.date}</td>
            <td>${meal.active_count}</td>
            <td>${meal.meal_count}</td>
            <td>
                <button class="btn btn-warning btn-sm me-1" onclick="editMeal(${meal.id})">Edit</button>
                <button class="btn btn-danger btn-sm" onclick="deleteMeal(${meal.id})">Delete</button>
            </td>
        </tr>
    `;
}

function loadMealPlans()
{
    $.get(mealsApiUrl, function(data) {
        const meals = parseJsonResponse(data);

        renderPaginatedTable({
            data: meals,
            tableSelector: '#mealTable',
            currentPage: 1,
            perPage: 10,
            renderRow: renderMealRow,
            sortColumns: [
                { index: 0, key: 'date' },
                { index: 1, key: 'active_count' },
                { index: 2, key: 'meal_count' }
            ]
        });
    });
}

function loadMealsByDate()
{
    const date = $('#filterDate').val();
    if (!date) {
        loadMealPlans();
        return;
    }

    $.get(`${mealsApiUrl}/${date}/date`, function(data) {
        const meal = parseJsonResponse(data);
        let rows = '';

        if (meal.id) {
            renderPaginatedTable({
                data: [meal],
                tableSelector: '#mealTable',
                currentPage: 1,
                perPage: 10,
                renderRow: renderMealRow,
                sortColumns: [
                    { index: 0, key: 'date' },
                    { index: 1, key: 'active_count' },
                    { index: 2, key: 'meal_count' }
                ]
            });
            return;
        } else if (meal.date) {
            rows = `
                <tr class="table-warning">
                    <td>${meal.date}</td>
                    <td>${meal.active_count}</td>
                    <td>${meal.meal_count}</td>
                    <td>
                        <button class="btn btn-primary btn-sm me-1" onclick="openMealModal({date: '${meal.date}', active_count: ${meal.active_count}, meal_count: ${meal.meal_count}})">Create Plan</button>
                        <span class="text-muted">Unsaved totals for this date. Save to store the meal plan.</span>
                    </td>
                </tr>
            `;
        } else {
            rows = '<tr><td colspan="4" class="text-center">No meal plan data available</td></tr>';
        }

        $('#mealTable').html(rows);
        ensurePaginationContainer('#mealTable').empty();
    });
}

function resetMealForm()
{
    $('#mealForm')[0].reset();
    $('#mealId').val('');
    $('#mealModalLabel').text('Add Meal Plan');
    $('#meal_date').val('');
    $('#active_count').val('');
    $('#meal_count').val('');
}

function openMealModal(meal)
{
    resetMealForm();

    if (meal && meal.id) {
        $('#mealId').val(meal.id);
        $('#meal_date').val(meal.date);
        $('#active_count').val(meal.active_count);
        $('#meal_count').val(meal.meal_count);
        $('#mealModalLabel').text('Edit Meal Plan');
    } else {
        const selectedDate = $('#filterDate').val();
        $('#meal_date').val(selectedDate || getLocalDateString());
    }

    $('#mealModal').modal('show');
}

function editMeal(id)
{
    if (id === 0) {
        swalError('Cannot edit unsaved meal plan. Please add it first.');
        return;
    }

    $.get(`${mealsApiUrl}/${id}`, function(data) {
        const meal = parseJsonResponse(data);
        openMealModal(meal);
    });
}

function saveMeal(event)
{
    event.preventDefault();

    const id = $('#mealId').val();
    const url = id ? `${mealsApiUrl}/${id}` : mealsApiUrl;
    const method = id ? 'PUT' : 'POST';

    $.ajax({
        url: url,
        type: method,
        data: $('#mealForm').serialize(),
        success: function() {
            loadMealPlans();
            $('#mealModal').modal('hide');
            swalSuccess('Meal plan saved successfully');
        },
        error: function(xhr) {
            swalError('Error: ' + (xhr.responseJSON?.error || 'Unknown error'));
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
                loadMealPlans();
                swalSuccess('Meal plan deleted successfully');
            },
            error: function(xhr) {
                swalError('Error: ' + (xhr.responseJSON?.error || 'Unknown error'));
            }
        });
    });
}

function recalculateHeadcount()
{
    const today = getLocalDateString();

    $.post(`${mealsApiUrl}/recalculate`, { date: today }, function(data) {
        const result = parseJsonResponse(data);
        swalSuccess(`Recalculated successfully.\nActive Employees: ${result.active_count}\nMeal Headcount: ${result.meal_count}`);
        loadMealPlans();
    }).fail(function(xhr) {
        swalError('Error recalculating meal headcount: ' + (xhr.responseJSON?.error || 'Unknown error'));
    });
}

$(function() {
    loadMealPlans();
    $('#mealForm').on('submit', saveMeal);
});
