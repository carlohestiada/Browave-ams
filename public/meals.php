<?php include 'layouts/header.php'; ?>
<?php include 'layouts/sidebar.php'; ?>

<div class="content-wrapper">

    <div class="d-flex justify-content-between mb-3">
        <div>
            <h2 style="font-size:26px; font-weight:700; color:#003686; margin:0; letter-spacing:-0.02em;">Weekly Meal Planner</h2>
            <p style="font-size:13px; color:#434653; margin:4px 0 0;">Weekly meal requirements generated from active employee assignments.</p>
        </div>

        <button class="btn btn-secondary" type="button" onclick="loadMealPlans()">Reset</button>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">View</label>
                    <select id="mealViewMode" class="form-select" onchange="changeMealViewMode()">
                        <option value="weekly">Weekly Planner</option>
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
                    <div class="form-text">The planner automatically calculates the lunch box requirement and highlights arrivals, departures, and Sunday rest days.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="meal-headcount-summary mb-3">
        <div>
            <div class="meal-headcount-summary__label" id="mealSummaryLabel">Weekly Meal Planner</div>
            <div class="meal-headcount-summary__range" id="mealSummaryRange">-</div>
        </div>
        <div class="meal-headcount-summary__value" id="mealSummaryTotal">0</div>
    </div>

    <div class="meal-planner-summary mb-3" id="mealPlannerSummary"></div>

    <div class="ams-card" style="padding:0; overflow:hidden;">
        <div style="overflow-x:auto;">
            <table class="table table-bordered meal-planner-table" data-export-title="Weekly Meal Planner">
                <thead>
                    <tr>
                        <th>Day</th>
                        <th>Date</th>
                        <th>Headcount</th>
                        <th>Company Pay</th>
                        <th>Lunch Box</th>
                        <th>Arrival</th>
                        <th>Departure</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody id="mealTable"></tbody>
            </table>
        </div>
    </div>

</div>

<script src="assets/js/meals.js"></script>

<?php include 'layouts/footer.php'; ?>
