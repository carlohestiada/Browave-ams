<?php include 'layouts/header.php'; ?>
<?php include 'layouts/sidebar.php'; ?>

<div class="content-wrapper">

    <div>
        <h2 style="font-size:26px; font-weight:700; color:#003686; margin:0; letter-spacing:-0.02em;">Reports</h2>
        <p style="font-size:13px; color:#434653; margin:4px 0 0;">Generate and view various reports.</p>
    </div>

    <!-- Report Tabs -->
    <div class="card">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="headcount-tab" data-bs-toggle="tab" href="#headcount" role="tab">Headcount Report</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="occupancy-tab" data-bs-toggle="tab" href="#occupancy" role="tab">Occupancy Report</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="arrival-tab" data-bs-toggle="tab" href="#arrival" role="tab">Arrival/Departure</a>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content">
                <!-- Headcount Report -->
                <div class="tab-pane fade show active" id="headcount" role="tabpanel">
                    <div class="ams-card" style="padding:0; overflow:hidden;">
                        <div style="overflow-x:auto;">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Active Employees</th>
                                        <th>Meal Headcount</th>
                                    </tr>
                                </thead>
                                <tbody id="headcountTable"></tbody>
                            </table>
                        </div>
                    </div>


                </div>

                <!-- Occupancy Report -->
                <div class="tab-pane fade" id="occupancy" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Accommodation</th>
                                    <th>Type</th>
                                    <th>Total Rooms</th>
                                    <th>Capacity</th>
                                    <th>Occupied</th>
                                    <th>Available</th>
                                    <th>Occupancy %</th>
                                </tr>
                            </thead>
                            <tbody id="occupancyTable"></tbody>
                        </table>
                    </div>
                </div>

                <!-- Arrival/Departure Report -->
                <div class="tab-pane fade" id="arrival" role="tabpanel">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Count</th>
                            </tr>
                        </thead>
                        <tbody id="arrivalTable"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

<script src="assets/js/reports.js"></script>

<?php include 'layouts/footer.php'; ?>