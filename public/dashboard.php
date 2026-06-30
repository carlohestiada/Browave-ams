<?php include 'layouts/header.php'; ?>
<?php include 'layouts/sidebar.php'; ?>

<link rel="stylesheet" href="assets/css/style.css">

<div class="content-wrapper">

    <!-- Page Header -->
    <div class="dashboard-header">
        <div>
            <h2 class="dashboard-title">Dashboard</h2>
            <p class="dashboard-subtitle">Real-time overview of accommodation, staffing, and operations.</p>
        </div>
        <button type="button" class="btn btn-outline-primary btn-sm" id="dashboardRefreshBtn">
            <i class="bi bi-arrow-clockwise"></i>
            Refresh
        </button>
    </div>

    <!-- KPI Cards -->
    <div class="kpi-grid dashboard-kpi-grid">

        <a href="employees.php" class="kpi-card kpi-card--link">
            <p class="kpi-label">Total Employees</p>
            <div class="kpi-value-row">
                <p class="kpi-value kpi-value--primary" id="kpi-total-employees">-</p>
                <span class="kpi-badge badge-default">All</span>
            </div>
        </a>

        <a href="employees.php" class="kpi-card kpi-card--link">
            <p class="kpi-label">Active Employees</p>
            <div class="kpi-value-row">
                <p class="kpi-value kpi-value--success" id="kpi-active-employees">-</p>
                <span class="kpi-badge badge-active" id="kpi-active-pct">-</span>
            </div>
        </a>

        <a href="rooms.php" class="kpi-card kpi-card--link">
            <p class="kpi-label">Total Rooms</p>
            <div class="kpi-value-row">
                <p class="kpi-value kpi-value--neutral" id="kpi-total-rooms">-</p>
                <span class="kpi-badge badge-default">100%</span>
            </div>
        </a>

        <a href="rooms.php" class="kpi-card kpi-card--link">
            <p class="kpi-label">Occupied Rooms</p>
            <div class="kpi-value-row">
                <p class="kpi-value kpi-value--primary" id="kpi-occupied">-</p>
                <span class="kpi-badge badge-default" id="kpi-occupied-pct">-</span>
            </div>
        </a>

        <a href="rooms.php" class="kpi-card kpi-card--link">
            <p class="kpi-label">Available Rooms</p>
            <div class="kpi-value-row">
                <p class="kpi-value kpi-value--success" id="kpi-available">-</p>
                <span class="kpi-badge badge-active" id="kpi-available-pct">-</span>
            </div>
        </a>

        <a href="departments.php" class="kpi-card kpi-card--link">
            <p class="kpi-label">Departments</p>
            <div class="kpi-value-row">
                <p class="kpi-value kpi-value--primary" id="kpi-departments">-</p>
                <span class="kpi-badge badge-default">Total</span>
            </div>
        </a>

    </div>

    <!-- Analytical Charts -->
    <div class="dashboard-grid-charts dashboard-section-spacing">

        <!-- Department -->
        <div class="ams-card dashboard-card-panel dashboard-chart-panel--wide">
            <div class="dashboard-card-header">
                <div class="dashboard-card-title">  
                    <span>Department Chart</span>
                </div>
                <div class="dashboard-chart-legend" id="trend-chart-legend">
                    <!-- Legend items injected by JS -->
                </div>
            </div>
            <div class="dashboard-card-body dashboard-chart-body">
                <div id="dashboard-chart-tooltip" class="dashboard-chart-tooltip"></div>
                <svg id="trend-chart-svg" class="dashboard-trend-chart" viewBox="0 0 800 200" preserveAspectRatio="none">
                    <!-- Points injected by JS -->
                </svg>
                <div class="dashboard-trend-chart-labels" id="trend-chart-labels">
                    <!-- Month labels injected by JS -->
                    </div>
            </div>
        </div>

        <!-- Gender Distribution -->
        <div class="ams-card dashboard-card-panel">
            <div class="dashboard-card-header">
                <div class="dashboard-card-title">
                    <span>Gender Chart</span>
                </div>
            </div>
            <div class="dashboard-card-body dashboard-donut-body">
                <div class="dashboard-donut-wrap">
                    <svg class="dashboard-donut-chart" viewBox="0 0 36 36">
                        <circle cx="18" cy="18" r="15.9" fill="none" stroke="#e2e8f0" stroke-width="4"></circle>
                        <circle id="gender-donut-arc" cx="18" cy="18" r="15.9" fill="none" stroke="#00639d"
                                stroke-width="4" stroke-linecap="round"
                                stroke-dasharray="0 100" transform="rotate(-90 18 18)"></circle>
                    </svg>
                    <div class="dashboard-donut-center">
                        <span class="dashboard-donut-total" id="gender-donut-total">-</span>
                        <span class="dashboard-donut-total-label">Total</span>
                    </div>
                </div>
                <div class="dashboard-donut-legend">
                    <div class="dashboard-donut-legend-item">
                        <div class="dashboard-donut-legend-dot" style="background:#00639d;"></div>
                        <div>
                            <p class="dashboard-donut-legend-label">Male</p>
                            <p class="dashboard-donut-legend-value" id="gender-pct-male">-</p>
                        </div>
                    </div>
                    <div class="dashboard-donut-legend-item">
                        <div class="dashboard-donut-legend-dot" style="background:#cbd5e1;"></div>
                        <div>
                            <p class="dashboard-donut-legend-label">Female</p>
                            <p class="dashboard-donut-legend-value" id="gender-pct-female">-</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Two-column section: Recent Employees + Room Status -->
    <div class="dashboard-grid-twocol dashboard-section-spacing">

        <!-- Recent Employees -->
        <div class="ams-card dashboard-card-panel">
            <div class="dashboard-card-header">
                <div class="dashboard-card-title">
                    <span>Recent Employees</span>
                </div>
                <a href="employees.php" class="dashboard-card-link">View all &rarr;</a>
            </div>
            <div class="dashboard-card-table-body">
                <table class="ams-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Department</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="dashboard-employees">
                        <tr>
                            <td colspan="3" class="dashboard-loading-state">
                                Loading...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Room Status Summary -->
        <div class="ams-card dashboard-card-panel">
            <div class="dashboard-card-header">
                <div class="dashboard-card-title">
                    <span>Room Status</span>
                </div>
                <a href="rooms.php" class="dashboard-card-link">View all &rarr;</a>
            </div>
            <div class="dashboard-card-body" id="dashboard-room-status">
                <!-- Status rows injected by JS -->
                <div class="dashboard-loading-state">Loading...</div>
            </div>
        </div>

    </div>

    <!-- Arrivals & Departures Today -->
    <div class="dashboard-grid-twocol">

        <!-- Today's Arrivals -->
        <div class="ams-card dashboard-card-panel">
            <div class="dashboard-card-header">
                <div class="dashboard-card-title">
                    <span>Today's Arrivals</span>
                </div>
                <a href="arrivals.php" class="dashboard-card-link">Manage &rarr;</a>
            </div>
            <div class="dashboard-card-body" id="dashboard-arrivals">
                <div class="dashboard-loading-state">Loading...</div>
            </div>
        </div>

        <!-- Today's Departures -->
        <div class="ams-card dashboard-card-panel">
            <div class="dashboard-card-header">
                <div class="dashboard-card-title">
                    <span>Today's Departures</span>
                </div>
                <a href="departures.php" class="dashboard-card-link">Manage &rarr;</a>
            </div>
            <div class="dashboard-card-body" id="dashboard-departures">
                <div class="dashboard-loading-state">Loading...</div>
            </div>
        </div>

    </div>

</div>

<!-- end .content-wrapper -->

<script src="assets/js/dashboard.js"></script>

<?php include 'layouts/footer.php'; ?>  