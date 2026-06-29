<?php include 'layouts/header.php'; ?>
<?php include 'layouts/sidebar.php'; ?>

<div class="content-wrapper">

    <div class="d-flex justify-content-between mb-3">
        <div>
            <h2 style="font-size:26px; font-weight:700; color:#003686; margin:0; letter-spacing:-0.02em;">Meal Planning</h2>
            <p style="font-size:13px; color:#434653; margin:4px 0 0;">Manage and organize company meals.</p>
        </div>

        <button
            class="btn btn-primary"
            type="button"
            onclick="openMealModal()">
            Add Meal Plan
        </button>
    </div>

    <!-- Week Filter -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">View</label>
                    <select id="mealViewMode" class="form-select" onchange="changeMealViewMode()">
                        <option value="weekly">Weekly List</option>
                        <option value="monthly">Monthly View</option>
                    </select>
                </div>
                <div class="col-md-4 meal-weekly-filter">
                    <label class="form-label">Week</label>
                    <div class="input-group">
                        <button class="btn btn-outline-secondary" type="button" onclick="changeMealWeek(-1)">Previous</button>
                        <input type="week" id="filterWeek" class="form-control" onchange="loadMealsByWeek()">
                        <button class="btn btn-outline-secondary" type="button" onclick="changeMealWeek(1)">Next</button>
                    </div>
                </div>
                <div class="col-md-4 meal-monthly-filter d-none">
                    <label class="form-label">Month</label>
                    <div class="input-group">
                        <button class="btn btn-outline-secondary" type="button" onclick="changeMealMonth(-1)">Previous</button>
                        <input type="month" id="filterMonth" class="form-control" onchange="loadMealsByMonth()">
                        <button class="btn btn-outline-secondary" type="button" onclick="changeMealMonth(1)">Next</button>
                    </div>
                </div>
                <div class="col-md-5 d-flex align-items-end">
                    <button class="btn btn-info me-2" onclick="recalculateHeadcount()">Calculate Today</button>
                    <button class="btn btn-secondary" onclick="loadMealPlans()">Reset Filter</button>
                </div>
            </div>
            <div class="row">
                <div class="col-12 mt-3">
                    <div class="form-text">Select a week to view the saved totals for each day. If a meal plan already exists, saving will update the record instead of creating a duplicate.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="meal-headcount-summary mb-3">
        <div>
            <div class="meal-headcount-summary__label" id="mealSummaryLabel">Weekly Meal Headcount</div>
            <div class="meal-headcount-summary__range" id="mealSummaryRange">-</div>
        </div>
        <div class="meal-headcount-summary__value" id="mealSummaryTotal">0</div>
    </div>

    <!-- Meal Plans Table -->
    <div class="ams-card" style="padding:0; overflow:hidden;">
        <div style="overflow-x:auto;">
            <table class="table table-bordered" data-export-title="Meals Data">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Active Employees</th>
                        <th>Meal Headcount</th>
                        <th>Arrival</th>
                        <th>Departure</th>
                        <th width="180">Action</th>
                    </tr>
                </thead>
                <tbody id="mealTable"></tbody>
            </table>
        </div>
    </div>

</div>

<!-- Meal Modal -->
<div class="modal fade" id="mealModal" tabindex="-1" aria-labelledby="mealModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="mealModalLabel">Add Meal Plan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="mealForm">
                <input type="hidden" id="mealTransactionId" name="transaction_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="meal_transaction_type" class="form-label">Type</label>
                        <select class="form-select" id="meal_transaction_type" name="transaction_type" required>
                            <option value="arrival">Arrival</option>
                            <option value="departure">Departure</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="meal_employee_id" class="form-label">Employee Name</label>
                        <select class="form-select" id="meal_employee_id" name="employee_id" required>
                            <option value="">Select employee</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="meal_transaction_date" class="form-label">Date</label>
                        <input type="date" class="form-control" id="meal_transaction_date" name="transaction_date" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Meal Plan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="assets/js/meals.js"></script>

<?php include 'layouts/footer.php'; ?>
