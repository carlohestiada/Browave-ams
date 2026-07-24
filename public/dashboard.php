<?php include 'layouts/header.php'; ?>
<?php include 'layouts/sidebar.php'; ?>

<link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(__DIR__ . '/assets/css/style.css') ?>">

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
            <p class="kpi-label"><i class="bi bi-people-fill"></i> Total Employees</p>
            <div class="kpi-value-row">
                <p class="kpi-value kpi-value--primary" id="kpi-total-employees">-</p>
                <span class="kpi-badge badge-default">All</span>
            </div>
        </a>

        <a href="employees.php" class="kpi-card kpi-card--link">
            <p class="kpi-label"><i class="bi bi-person-check-fill"></i> Active Employees</p>
            <div class="kpi-value-row">
                <p class="kpi-value kpi-value--success" id="kpi-active-employees">-</p>
                <span class="kpi-badge badge-active" id="kpi-active-pct">-</span>
            </div>
        </a>

        <a href="rooms.php" class="kpi-card kpi-card--link">
            <p class="kpi-label"><i class="bi bi-door-open-fill"></i> Total Rooms</p>
            <div class="kpi-value-row">
                <p class="kpi-value kpi-value--neutral" id="kpi-total-rooms">-</p>
                <span class="kpi-badge badge-default">100%</span>
            </div>
        </a>

        <a href="rooms.php" class="kpi-card kpi-card--link">
            <p class="kpi-label"><i class="bi bi-house-fill"></i> Occupied Rooms</p>
            <div class="kpi-value-row">
                <p class="kpi-value kpi-value--primary" id="kpi-occupied">-</p>
                <span class="kpi-badge badge-default" id="kpi-occupied-pct">-</span>
            </div>
        </a>

        <a href="rooms.php" class="kpi-card kpi-card--link">
            <p class="kpi-label"><i class="bi bi-door-open-fill"></i> Available Rooms</p>
            <div class="kpi-value-row">
                <p class="kpi-value kpi-value--success" id="kpi-available">-</p>
                <span class="kpi-badge badge-active" id="kpi-available-pct">-</span>
            </div>
        </a>

        <a href="departments.php" class="kpi-card kpi-card--link">
            <p class="kpi-label"><i class="bi bi-diagram-3-fill"></i> Departments</p>
            <div class="kpi-value-row">
                <p class="kpi-value kpi-value--primary" id="kpi-departments">-</p>
                <span class="kpi-badge badge-default">Total</span>
            </div>
        </a>

        <a href="company-car.php" class="kpi-card kpi-card--link">
            <p class="kpi-label"><i class="bi bi-car-front-fill"></i> Company Car Requests</p>
            <div class="kpi-value-row">
                <p class="kpi-value kpi-value--primary" id="kpi-companycar-total">-</p>
                <span class="kpi-badge badge-default">Total</span>
            </div>
        </a>

        <a href="company-car.php" class="kpi-card kpi-card--link">
            <p class="kpi-label"><i class="bi bi-calendar-check-fill"></i> Scheduled Today</p>
            <div class="kpi-value-row">
                <p class="kpi-value kpi-value--success" id="kpi-companycar-scheduled">-</p>
                <span class="kpi-badge badge-active">Today</span>
            </div>
        </a>

        <a href="company-car.php" class="kpi-card kpi-card--link">
            <p class="kpi-label"><i class="bi bi-truck"></i> Available Vehicles</p>
            <div class="kpi-value-row">
                <p class="kpi-value kpi-value--neutral" id="kpi-companycar-available">-</p>
                <span class="kpi-badge badge-default">Available</span>
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
                <div id="department-chart-empty" class="dashboard-chart-empty-state" style="display:none;">
                    <svg class="dashboard-chart-empty-illustration" viewBox="0 0 120 80" aria-hidden="true">
                        <rect x="12" y="22" width="18" height="38" rx="4" fill="#dbeafe"></rect>
                        <rect x="40" y="14" width="18" height="46" rx="4" fill="#bfdbfe"></rect>
                        <rect x="68" y="28" width="18" height="32" rx="4" fill="#93c5fd"></rect>
                        <rect x="96" y="18" width="12" height="42" rx="4" fill="#60a5fa"></rect>
                        <path d="M12 58c10-8 19-12 30-12s20 4 30 10 19 8 36 8" stroke="#00639d" stroke-width="3" stroke-linecap="round" fill="none"></path>
                    </svg>
                    <p class="dashboard-chart-empty-title"><strong>No data available.</strong></p>
                    <p class="dashboard-chart-empty-text">Please add employee records to generate chart statistics.</p>
                </div>
                <svg id="trend-chart-svg" class="dashboard-trend-chart" viewBox="0 0 800 420" preserveAspectRatio="none">
                    <!-- Points injected by JS -->
                </svg>
                <div class="dashboard-trend-chart-labels" id="trend-chart-labels">
                    <!-- Month labels injected by JS -->
                </div>
            </div>
        </div>

        <!-- Gender Distribution — enhanced donut -->
        <div class="ams-card dashboard-card-panel gender-chart-card">
            <div class="dashboard-card-header">
                <div class="dashboard-card-title">
                    <span>Gender Distribution</span>
                </div>
                <span class="gender-chart-subtitle">All employees</span>
            </div>
            <div class="dashboard-card-body dashboard-donut-body">

                <!-- Donut ring -->
                <div class="dashboard-donut-wrap">
                    <div id="gender-chart-tooltip" class="dashboard-gender-tooltip" role="status" aria-live="polite"></div>
                    <svg class="dashboard-donut-chart" viewBox="0 0 36 36">
                        <!-- Decorative outer glow ring -->
                        <circle cx="18" cy="18" r="15.9"
                            fill="none"
                            stroke="#eef4ff"
                            stroke-width="5"></circle>
                        <!-- Track ring -->
                        <circle cx="18" cy="18" r="15.9"
                            fill="none"
                            stroke="#e2e8f0"
                            stroke-width="3.2"></circle>
                        <!-- Female arc (background, rendered first) -->
                        <circle id="gender-donut-arc-female"
                            cx="18" cy="18" r="15.9"
                            fill="none"
                            stroke="#e879a0"
                            stroke-width="3.2"
                            stroke-linecap="round"
                            stroke-dasharray="0 100"
                            transform="rotate(-90 18 18)"
                            class="gender-arc-animate"></circle>
                        <!-- Male arc (rendered on top) -->
                        <circle id="gender-donut-arc"
                            cx="18" cy="18" r="15.9"
                            fill="none"
                            stroke="#00639d"
                            stroke-width="3.2"
                            stroke-linecap="round"
                            stroke-dasharray="0 100"
                            transform="rotate(-90 18 18)"
                            class="gender-arc-animate"></circle>
                    </svg>
                    <div id="gender-chart-empty" class="dashboard-chart-empty-state dashboard-gender-empty-state" style="display:none;">
                        <svg class="dashboard-chart-empty-illustration" viewBox="0 0 120 80" aria-hidden="true">
                            <circle cx="60" cy="40" r="24" fill="none" stroke="#cbd5e1" stroke-width="8"></circle>
                            <path d="M60 20v20l14 8" stroke="#00639d" stroke-width="6" stroke-linecap="round"></path>
                            <path d="M32 64c10-12 18-18 28-18s18 6 28 18" stroke="#93c5fd" stroke-width="5" stroke-linecap="round" fill="none"></path>
                        </svg>
                        <p class="dashboard-chart-empty-title"><strong>No data available.</strong></p>
                        <p class="dashboard-chart-empty-text">Please add employee records to generate chart statistics.</p>
                    </div>
                    <!-- Centre label -->
                    <div class="dashboard-donut-center">
                        <span class="dashboard-donut-total" id="gender-donut-total">-</span>
                        <span class="dashboard-donut-total-label">Total</span>
                    </div>
                </div>

                <!-- Legend — two columns side by side -->
                <div class="dashboard-donut-legend">
                    <div class="dashboard-donut-legend-item gender-legend-male">
                        <div class="gender-legend-icon" style="background: linear-gradient(135deg, #00639d, #094cb2);">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="8" r="4" />
                                <path d="M12 14c-5 0-8 2-8 4v1h16v-1c0-2-3-4-8-4z" />
                            </svg>
                        </div>
                        <div>
                            <p class="dashboard-donut-legend-label">Male</p>
                            <p class="dashboard-donut-legend-value" id="gender-pct-male">-</p>
                        </div>
                    </div>
                    <div class="dashboard-donut-legend-item gender-legend-female">
                        <div class="gender-legend-icon" style="background: linear-gradient(135deg, #e879a0, #c026d3);">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="8" r="4" />
                                <path d="M12 14c-5 0-8 2-8 4v1h16v-1c0-2-3-4-8-4z" />
                            </svg>
                        </div>
                        <div>
                            <p class="dashboard-donut-legend-label">Female</p>
                            <p class="dashboard-donut-legend-value" id="gender-pct-female">-</p>
                        </div>
                    </div>
                </div>

                <!-- Bar breakdown -->
                <div class="gender-bar-breakdown">
                    <div class="gender-bar-track">
                        <div class="gender-bar-male" id="gender-bar-male" style="width:0%"></div>
                        <div class="gender-bar-female" id="gender-bar-female" style="width:0%"></div>
                    </div>
                    <div class="gender-bar-labels">
                        <span id="gender-bar-label-male">Male 0%</span>
                        <span id="gender-bar-label-female">Female 0%</span>
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



<script src="assets/js/dashboard.js"></script>

<?php include 'layouts/footer.php'; ?>