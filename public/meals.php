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
            data-bs-toggle="modal"
            data-bs-target="#mealModal">
            Add Meal Plan
        </button>
    </div>

    <!-- Date Filter -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <label class="form-label">Filter by Date</label>
                    <input type="date" id="filterDate" class="form-control" onchange="loadMealsByDate()">
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <button class="btn btn-info me-2" onclick="recalculateHeadcount()">Calculate Today</button>
                    <button class="btn btn-secondary" onclick="loadMealPlans()">Reset Filter</button>
                </div>
            </div>
            <div class="row">
                <div class="col-12 mt-3">
                    <div class="form-text">Select a date to view the saved totals for that day. If a meal plan already exists, saving will update the record instead of creating a duplicate.</div>
                </div>
            </div>
        </div>
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
                <input type="hidden" id="mealId" name="id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="meal_date" class="form-label">Date</label>
                        <input type="date" class="form-control" id="meal_date" name="date" required>
                    </div>
                    <div class="mb-3">
                        <label for="active_count" class="form-label">Active Employees</label>
                        <input type="number" class="form-control" id="active_count" name="active_count" required min="0">
                    </div>
                    <div class="mb-3">
                        <label for="meal_count" class="form-label">Meal Headcount</label>
                        <input type="number" class="form-control" id="meal_count" name="meal_count" required min="0">
                    </div>
                    <div class="alert alert-info" role="alert">
                        <small><strong>Note:</strong> Active Employees count is automatically calculated from the system. Adjust if needed.</small>
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
