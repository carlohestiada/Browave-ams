<?php include 'layouts/header.php'; ?>
<?php include 'layouts/sidebar.php'; ?>

<link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(__DIR__ . '/assets/css/style.css') ?>">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@500;600;700&family=Sora:wght@600;700&display=swap" rel="stylesheet">

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
            <p class="kpi-label"><i class="bi bi-door-open-fill"></i> Available Rooms</p>
            <div class="kpi-value-row">
                <p class="kpi-value kpi-value--success" id="kpi-available">-</p>
                <span class="kpi-badge badge-active" id="kpi-available-pct">-</span>
            </div>
        </a>

    </div>

    <!-- Analytical Charts -->
    <div class="dashboard-grid-charts dashboard-section-spacing">

        <!-- Department -->
        <div class="ams-card dashboard-card-panel">
            <div class="dept-chart-header">
                <div class="dashboard-card-title">
                    <span>Department Chart</span>
                </div>
                <div class="dept-chart-legend">

                    <span class="gender-chart-subtitle"><span class="dept-chart-legend-swatch"></span>
                        &nbsp;&nbsp;Employees
                    </span>
                    <a href="departments.php" class="dashboard-card-link">View all &rarr;</a>
                </div>
            </div>
            <div class="dept-chart-body">
                <div class="dept-chart-canvas-wrap">
                    <canvas id="departmentChart" role="img" aria-label="Bar chart of employee count by department"></canvas>
                    <div id="departmentChartStatus" class="dept-chart-empty">Loading department data...</div>
                </div>
            </div>
        </div>

        <!-- Gender Distribution — enhanced donut -->
        <div class="ams-card dashboard-card-panel gender-chart-card">
            <div class="dashboard-card-header">
                <div class="dashboard-card-title">
                    <span>Gender Distribution</span>
                </div>

                <span class="gender-chart-subtitle"><span class="dept-chart-legend-swatch">
                    </span>&nbsp;&nbsp;&nbsp;All employees
                </span>
                <a href="employees.php" class="dashboard-card-link">View all &rarr;</a>
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
                        <!-- Male arc -->
                        <circle id="gender-donut-arc"
                            cx="18" cy="18" r="15.9"
                            fill="none"
                            stroke="#00639d"
                            stroke-width="3.2"
                            stroke-linecap="round"
                            stroke-dasharray="0 100"
                            transform="rotate(-90 18 18)"
                            class="gender-arc-animate"></circle>
                        <!-- Other arc (topmost) -->
                        <circle id="gender-donut-arc-other"
                            cx="18" cy="18" r="15.9"
                            fill="none"
                            stroke="#f59e0b"
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
                    <div class="dashboard-donut-legend-item gender-legend-other">
                        <div class="gender-legend-icon" style="background: linear-gradient(135deg, #f59e0b, #f97316);">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="8" r="4" />
                                <path d="M12 14c-5 0-8 2-8 4v1h16v-1c0-2-3-4-8-4z" />
                            </svg>
                        </div>
                        <div>
                            <p class="dashboard-donut-legend-label">Others</p>
                            <p class="dashboard-donut-legend-value" id="gender-pct-other">-</p>
                        </div>
                    </div>
                </div>

                <!-- Bar breakdown -->
                <div class="gender-bar-breakdown">
                    <div class="gender-bar-track">
                        <div class="gender-bar-male" id="gender-bar-male" style="width:0%"></div>
                        <div class="gender-bar-female" id="gender-bar-female" style="width:0%"></div>
                        <div class="gender-bar-other" id="gender-bar-other" style="width:0%"></div>
                    </div>
                    <div class="gender-bar-labels">
                        <span id="gender-bar-label-male">Male 0%</span>
                        <span id="gender-bar-label-female">Female 0%</span>
                        <span id="gender-bar-label-other">Others 0%</span>
                    </div>
                </div>

            </div>
        </div>

    </div>

    <!-- Meal Summary -->
    <!-- Lunch Box Requirement -->
    <div class="ams-card dashboard-card-panel lunchbox-chart-card dashboard-section-spacing">
        <div class="dashboard-card-header">
            <div>
                <div class="dashboard-card-title">
                    <span>Lunch Box</span>
                </div>
                <p class="lunchbox-chart-subtitle">Daily Meal</p>
            </div>
            <a href="meals.php" class="dashboard-card-link">Meal Planner &rarr;</a>
        </div>
        <div class="dashboard-card-body lunchbox-chart-body">
            <div class="lunchbox-summary-grid">
                <div class="lunchbox-summary-item">
                    <span class="lunchbox-summary-label">This Week</span>
                    <span class="lunchbox-summary-value" id="lunchbox-week-total">-</span>
                </div>
                <div class="lunchbox-summary-divider"></div>
                <div class="lunchbox-summary-item">
                    <span class="lunchbox-summary-label">Next Week</span>
                    <span class="lunchbox-summary-value" id="lunchbox-nextweek-total">-</span>
                </div>
            </div>
            <div class="lunchbox-chart-canvas-wrap">
                <canvas id="lunchboxChart" role="img" aria-label="Bar chart of daily lunch box requirement"></canvas>
                <div id="lunchboxChartStatus" class="dept-chart-empty">Loading lunch box data...</div>
            </div>
            <div class="lunchbox-chart-source">Meal Module</div>
        </div>
    </div>
    <!-- End Meal Summary -->

    <!-- Company Car / Transportation Overview & Room Status -->
    <div class="dashboard-grid-twocol dashboard-section-spacing">

        <!-- Company Car / Transportation Overview -->
        <div class="ams-card dashboard-card-panel">
            <div class="dashboard-card-header">
                <div class="dashboard-card-title">
                    <span>Company Car / Transportation Overview</span>
                </div>
                <a href="company-car.php" class="dashboard-card-link">View all &rarr;</a>
            </div>
            <div class="dashboard-card-body">
                <!-- Transportation Summary Grid -->
                <div class="transportation-summary-grid">
                    <div class="transportation-summary-item">
                        <div class="transportation-summary-label">Available Vehicles</div>
                        <div class="transportation-summary-value" id="transportation-available-vehicles">-</div>
                    </div>
                    <div class="transportation-summary-item">
                        <div class="transportation-summary-label">Available Drivers</div>
                        <div class="transportation-summary-value" id="transportation-available-drivers">-</div>
                    </div>
                    <div class="transportation-summary-item">
                        <div class="transportation-summary-label">Scheduled This Week</div>
                        <div class="transportation-summary-value" id="transportation-scheduled-week">-</div>
                    </div>
                    <div class="transportation-summary-item">
                        <div class="transportation-summary-label">Scheduled Today</div>
                        <div class="transportation-summary-value" id="transportation-scheduled-today">-</div>
                    </div>
                </div>

                <!-- Transportation Daily Chart -->
                <div style="margin-top: 20px;">
                    <h4 style="font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 12px;">Transportation Schedule - Current Week</h4>
                    <div class="transportation-chart-canvas-wrap">
                        <div id="transportationChartStatus" class="transportation-chart-empty">Loading transportation data...</div>
                    </div>
                </div>
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

    <!-- Trip Activity Overview -->
    <div class="ams-card dashboard-card-panel dashboard-section-spacing traffic-chart-card">
        <div class="dashboard-card-header traffic-chart-header">
            <div>
                <div class="dashboard-card-title">
                    <span>Trip Activity Overview</span>
                </div>
            </div>
            <div class="traffic-chart-links">
                <a href="trips.php" class="dashboard-card-link">Trips &rarr;</a>
            </div>
        </div>
        <div class="dashboard-card-body traffic-chart-body">
            <div class="traffic-summary-grid">
                <div class="traffic-summary-item">
                    <div class="traffic-summary-label">Today</div>
                    <div class="traffic-summary-value" id="traffic-today-summary">Loading...</div>
                </div>
                <div class="traffic-summary-item">
                    <div class="traffic-summary-label">This Week</div>
                    <div class="traffic-summary-value" id="traffic-week-summary">Loading...</div>
                </div>
            </div>
            <div class="traffic-chart-legend">
                <div class="traffic-chart-legend-item">
                    <span class="traffic-legend-swatch traffic-legend-arrival"></span>
                    <span>Arrival</span>
                </div>
                <div class="traffic-chart-legend-item">
                    <span class="traffic-legend-swatch traffic-legend-departure"></span>
                    <span>Departure</span>
                </div>
            </div>
            <div class="traffic-chart-canvas-wrap">
                <canvas id="groupedTrafficChart" role="img" aria-label="Bar chart showing daily arrival and departure activity for the current week"></canvas>
            </div>
        </div>
    </div>

    <div id="departmentEmployeeDrawer" class="department-employee-drawer" aria-hidden="true">
        <div class="department-employee-backdrop" data-close-drawer="true"></div>
        <aside class="department-employee-panel" role="dialog" aria-modal="true" aria-labelledby="departmentEmployeeTitle">
            <div class="department-employee-header">
                <div>
                    <p id="departmentEmployeeKicker" class="department-employee-kicker">Department</p>
                    <h3 id="departmentEmployeeTitle">Employee Details</h3>
                    <div id="departmentEmployeeMeta" class="department-employee-meta">0 employees</div>
                </div>
                <button type="button" class="department-employee-close" aria-label="Close employee details" data-close-drawer="true">&times;</button>
            </div>

            <div class="department-employee-search-wrap">
                <input id="departmentEmployeeSearch" type="search" placeholder="Search employees..." aria-label="Search employees in selected department" />
            </div>

            <div id="departmentEmployeeContent" class="department-employee-content">
                <div class="department-employee-loading">Loading employees...</div>
            </div>
        </aside>
    </div>

</div>

<!-- Room Status Details Drawer -->
<div id="roomStatusDrawer" class="drawer drawer--right">
    <div class="drawer-backdrop" id="roomStatusDrawerBackdrop"></div>
    <div class="drawer-panel">
        <div class="drawer-header">
            <div class="drawer-header-content">
                <h2 class="drawer-title">
                    <span id="roomStatusDrawerTitle">Room Status</span>
                    <span id="roomStatusDrawerCount" class="drawer-count-badge">0</span>
                </h2>
                <p id="roomStatusDrawerSubtitle" class="drawer-subtitle">Loading...</p>
            </div>
            <button type="button" class="drawer-close-btn" id="roomStatusDrawerClose" title="Close">
                <i class="bi bi-x"></i>
            </button>
        </div>

        <div class="drawer-search">
            <div class="drawer-search-input-wrap">
                <i class="bi bi-search"></i>
                <input
                    type="text"
                    id="roomStatusDrawerSearch"
                    class="drawer-search-input"
                    placeholder="Search..."
                    aria-label="Search employees or rooms" />
                <button type="button" class="drawer-search-clear" id="roomStatusDrawerSearchClear" title="Clear search" style="display: none;">
                    <i class="bi bi-x-circle-fill"></i>
                </button>
            </div>
        </div>

        <div class="drawer-content">
            <div id="roomStatusDrawerContent" class="drawer-table-container">
                <div class="drawer-loading-state">
                    <div class="drawer-spinner"></div>
                    <p>Loading room details...</p>
                </div>
            </div>
        </div>

        <div id="roomStatusDrawerEmpty" class="drawer-empty-state" style="display: none;">
            <div class="drawer-empty-icon">
                <i class="bi bi-inbox"></i>
            </div>
            <p class="drawer-empty-title">No records found</p>
            <p class="drawer-empty-text" id="roomStatusDrawerEmptyText">Unable to find matching records for this room status.</p>
        </div>

        <div id="roomStatusDrawerError" class="drawer-error-state" style="display: none;">
            <div class="drawer-error-icon">
                <i class="bi bi-exclamation-triangle"></i>
            </div>
            <p class="drawer-error-title">Unable to load details</p>
            <p class="drawer-error-text" id="roomStatusDrawerErrorText">Please try again.</p>
            <button type="button" class="btn btn-primary btn-sm" id="roomStatusDrawerRetry">Retry</button>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>
<script src="assets/js/dashboard.js?v=<?= filemtime(__DIR__ . '/assets/js/dashboard.js') ?>"></script>
<?php include 'layouts/footer.php'; ?>