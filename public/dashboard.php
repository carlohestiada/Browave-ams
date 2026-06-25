<?php include 'layouts/header.php'; ?>
<?php include 'layouts/sidebar.php'; ?>

<!--
    NOTE: styles below are scoped to the new chart components added to the dashboard.
    Move these into your main stylesheet whenever convenient — they follow the same
    dashboard-* / ams-* naming convention already used in this file and don't touch
    any existing class or rule.
-->
<style>
    .dashboard-grid-charts {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 1.5rem;
    }
    @media (max-width: 992px) {
        .dashboard-grid-charts {
            grid-template-columns: 1fr;
        }
    }

    .dashboard-chart-panel--wide {
        min-width: 0;
    }

    .dashboard-chart-legend {
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    .dashboard-chart-legend-item {
        display: flex;
        align-items: center;
        font-size: 10px;
        font-weight: 600;
        color: #6b7280;
    }

    .dashboard-chart-legend-swatch {
        width: 10px;
        height: 6px;
        border-radius: 1px;
        margin-right: 4px;
        display: inline-block;
    }

    .dashboard-chart-body {
        padding-top: 0.5rem;
    }

    .dashboard-trend-chart {
        width: 100%;
        height: 220px;
        display: block;
    }

    .dashboard-trend-chart-labels {
        display: flex;
        justify-content: space-between;
        margin-top: 0.75rem;
        padding: 0 0.5rem;
        font-size: 10px;
        font-weight: 700;
        color: #9ca3af;
    }

    .dashboard-donut-body {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding-top: 1rem;
        padding-bottom: 1rem;
    }

    .dashboard-donut-wrap {
        position: relative;
        width: 160px;
        height: 160px;
    }

    .dashboard-donut-chart {
        width: 100%;
        height: 100%;
    }

    .dashboard-donut-center {
        position: absolute;
        inset: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .dashboard-donut-total {
        font-size: 1.25rem;
        font-weight: 700;
        color: #003366;
    }

    .dashboard-donut-total-label {
        font-size: 8px;
        font-weight: 700;
        text-transform: uppercase;
        color: #9ca3af;
    }

    .dashboard-donut-legend {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
        width: 100%;
        margin-top: 2rem;
        padding: 0 1rem;
    }

    .dashboard-donut-legend-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .dashboard-donut-legend-dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        flex-shrink: 0;
    }

    .dashboard-donut-legend-label {
        font-size: 10px;
        font-weight: 700;
        color: #6b7280;
        margin: 0;
    }

    .dashboard-donut-legend-value {
        font-size: 0.875rem;
        font-weight: 700;
        color: #003366;
        margin: 0;
    }
</style>

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

        <div class="kpi-card">
            <p class="kpi-label">Total Employees</p>
            <div class="kpi-value-row">
                <p class="kpi-value kpi-value--primary" id="kpi-total-employees">-</p>
                <span class="kpi-badge badge-default">All</span>
            </div>
        </div>

        <div class="kpi-card">
            <p class="kpi-label">Active Employees</p>
            <div class="kpi-value-row">
                <p class="kpi-value kpi-value--success" id="kpi-active-employees">-</p>
                <span class="kpi-badge badge-active" id="kpi-active-pct">-</span>
            </div>
        </div>

        <div class="kpi-card">
            <p class="kpi-label">Total Rooms</p>
            <div class="kpi-value-row">
                <p class="kpi-value kpi-value--neutral" id="kpi-total-rooms">-</p>
                <span class="kpi-badge badge-default">100%</span>
            </div>
        </div>

        <div class="kpi-card">
            <p class="kpi-label">Occupied Rooms</p>
            <div class="kpi-value-row">
                <p class="kpi-value kpi-value--primary" id="kpi-occupied">-</p>
                <span class="kpi-badge badge-default" id="kpi-occupied-pct">-</span>
            </div>
        </div>

        <div class="kpi-card kpi-card--accent">
            <p class="kpi-label">Available Rooms</p>
            <div class="kpi-value-row">
                <p class="kpi-value kpi-value--success" id="kpi-available">-</p>
                <span class="kpi-badge badge-active" id="kpi-available-pct">-</span>
            </div>
        </div>

        <div class="kpi-card">
            <p class="kpi-label">Departments</p>
            <div class="kpi-value-row">
                <p class="kpi-value kpi-value--primary" id="kpi-departments">-</p>
                <span class="kpi-badge badge-default">Total</span>
            </div>
        </div>

    </div>

    <!-- Analytical Charts -->
    <!-- <div class="dashboard-grid-charts dashboard-section-spacing"> -->

        <!-- Department -->
        <!-- <div class="ams-card dashboard-card-panel dashboard-chart-panel--wide">
            <div class="dashboard-card-header">
                <div class="dashboard-card-title">  
                    <span>Department Chart</span>
                </div>
                <div class="dashboard-chart-legend" id="trend-chart-legend"> -->
                    <!-- Legend items injected by JS -->
                <!-- </div>
            </div>
            <div class="dashboard-card-body dashboard-chart-body">
                <svg id="trend-chart-svg" class="dashboard-trend-chart" viewBox="0 0 800 200" preserveAspectRatio="none"> -->
                    <!-- Bars injected by JS -->
                <!-- </svg>
                <div class="dashboard-trend-chart-labels" id="trend-chart-labels"> -->
                    <!-- Month labels injected by JS -->
                <!-- </div>
            </div>
        </div> -->

        <!-- Gender Distribution -->
        <!-- <div class="ams-card dashboard-card-panel">
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

    </div> -->

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

<script>
document.addEventListener('DOMContentLoaded', function () {
    function getLocalDateString(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    const today = getLocalDateString(new Date());

    function fetchJSON(url) {
        return fetch(url)
            .then(response => response.ok ? response.json() : Promise.reject(response))
            .catch(() => null);
    }

    function renderRoomStatusPanel(statusCounts, total) {
        const panel = document.getElementById('dashboard-room-status');
        const palette = {
            Occupied: '#00639d',
            Available: '#22c55e',
            Reserved: '#f97316',
            Maintenance: '#ba1a1a',
            Other: '#6b7280'
        };

        const rows = Object.keys(statusCounts).map(label => {
            const count = statusCounts[label];
            const pct = total > 0 ? Math.round((count / total) * 100) : 0;
            const color = palette[label] || palette.Other;
            return `
            <div class="room-status-row">
                <div class="dashboard-room-status-label">
                    <div class="room-status-dot" style="background:${color};"></div>
                    <span class="dashboard-status-label">${label}</span>
                </div>
                <div class="room-status-bar-track">
                    <div class="room-status-bar-fill" style="width:${pct}%; background:${color};"></div>
                </div>
                <span class="dashboard-room-status-value">${count} <span class="dashboard-room-status-percent">(${pct}%)</span></span>
            </div>`;
        });

        panel.innerHTML = rows.join('');
    }

    function loadDashboardSummary() {
        fetchJSON('api/reports.php?action=summary&date=' + today).then(data => {
            if (!data) return;

            const totalEmployees = data.total_employees ?? 0;
            const activeEmployees = data.active_employees ?? 0;
            const occupiedRooms = data.occupied_rooms ?? 0;
            const availableRooms = data.available_rooms ?? 0;
            const totalRooms = data.total_rooms ?? (occupiedRooms + availableRooms + (data.reserved_rooms ?? 0) + (data.maintenance_rooms ?? 0));
            const activePct = totalEmployees > 0 ? Math.round(activeEmployees / totalEmployees * 100) : 0;
            const occupiedPct = totalRooms > 0 ? Math.round(occupiedRooms / totalRooms * 100) : 0;
            const availablePct = totalRooms > 0 ? Math.round(availableRooms / totalRooms * 100) : 0;

            document.getElementById('kpi-total-employees').textContent = totalEmployees;
            document.getElementById('kpi-active-employees').textContent = activeEmployees;
            document.getElementById('kpi-active-pct').textContent = activePct + '%';

            document.getElementById('kpi-total-rooms').textContent = totalRooms;
            document.getElementById('kpi-occupied').textContent = occupiedRooms;
            document.getElementById('kpi-available').textContent = availableRooms;
            document.getElementById('kpi-occupied-pct').textContent = occupiedPct + '%';
            document.getElementById('kpi-available-pct').textContent = availablePct + '%';

            renderRoomStatusPanel({
                Occupied: occupiedRooms,
                Available: availableRooms,
                Reserved: data.reserved_rooms ?? 0,
                Maintenance: data.maintenance_rooms ?? 0,
                Other: 0
            }, totalRooms);
        });

        fetchJSON('api/departments.php').then(data => {
            if (!Array.isArray(data)) return;
            document.getElementById('kpi-departments').textContent = data.length;
        });
    }

    function loadRecentEmployees() {
        fetchJSON('api/employees.php').then(data => {
            const tbody = document.getElementById('dashboard-employees');
            if (!Array.isArray(data) || data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="3" class="dashboard-loading-state">No employees found.</td></tr>';
                return;
            }

            tbody.innerHTML = data.slice(0, 5).map(emp => {
                const initials = (emp.full_name || '').split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase();
                const colors = ['#dbeafe:#1d4ed8', '#ede9fe:#6d28d9', '#fce7f3:#be185d', '#ffedd5:#c2410c', '#d1fae5:#065f46'];
                const [bg, fg] = (colors[Math.abs((emp.id || 0)) % colors.length]).split(':');
                const badge = emp.status === 'Active' ? 'badge-active' : 'badge-inactive';
                return `
                <tr>
                    <td>
                        <div class="dashboard-activity-name-wrap">
                            <div class="dashboard-employee-avatar" style="background:${bg}; color:${fg};">${initials}</div>
                            <span class="dashboard-employee-name">${emp.full_name ?? '-'}</span>
                        </div>
                    </td>
                    <td class="dashboard-status-label">${emp.department_name ?? '-'}</td>
                    <td><span class="${badge}">${emp.status ?? '-'}</span></td>
                </tr>`;
            }).join('');
        });
    }

    // --- Department Distribution (Monthly Trend) ---
    // TODO: replace STUB_TREND_DATA with a real endpoint, e.g.
    // fetchJSON('api/reports.php?action=department_trend') returning the same shape.
    const STUB_TREND_DATA = {
        months: ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP'],
        series: [
            { label: 'IT', color: '#00639d', values: [8, 9, 11, 10, 13, 14, 15, 16, 17] },
            { label: 'HR', color: '#4ade80', values: [5, 6, 7, 8, 9, 10, 8, 9, 10] },
            { label: 'Operations', color: '#f59e0b', values: [3, 4, 5, 4, 6, 7, 5, 6, 7] }
        ]
    };

    function renderTrendChart(trendData) {
        const svg = document.getElementById('trend-chart-svg');
        const labelsEl = document.getElementById('trend-chart-labels');
        const legendEl = document.getElementById('trend-chart-legend');
        if (!svg || !trendData || !Array.isArray(trendData.series)) return;

        const chartHeight = 180;
        const chartTop = 10;
        const groupWidth = 800 / trendData.months.length;
        const barWidth = 12;
        const barGap = 2;
        const maxValue = Math.max(1, ...trendData.series.flatMap(s => s.values));

        let svgContent = `
            <line x1="0" y1="180" x2="800" y2="180" stroke="#f1f5f9" stroke-width="1"></line>
            <line x1="0" y1="130" x2="800" y2="130" stroke="#f1f5f9" stroke-width="1"></line>
            <line x1="0" y1="80" x2="800" y2="80" stroke="#f1f5f9" stroke-width="1"></line>
            <line x1="0" y1="30" x2="800" y2="30" stroke="#f1f5f9" stroke-width="1"></line>
        `;

        trendData.months.forEach((month, monthIndex) => {
            const groupStart = monthIndex * groupWidth + 20;
            trendData.series.forEach((series, seriesIndex) => {
                const value = series.values[monthIndex] ?? 0;
                const barHeight = Math.round((value / maxValue) * chartHeight);
                const x = groupStart + seriesIndex * (barWidth + barGap);
                const y = chartTop + (chartHeight - barHeight);
                svgContent += `<rect x="${x}" y="${y}" width="${barWidth}" height="${barHeight}" rx="2" fill="${series.color}"></rect>`;
            });
        });

        svg.innerHTML = svgContent;

        labelsEl.innerHTML = trendData.months
            .map(month => `<span>${month}</span>`)
            .join('');

        legendEl.innerHTML = trendData.series
            .map(series => `
                <span class="dashboard-chart-legend-item">
                    <span class="dashboard-chart-legend-swatch" style="background:${series.color};"></span>
                    ${series.label}
                </span>`)
            .join('');
    }

    // --- Gender Distribution donut ---
    function renderGenderDonut(maleCount, femaleCount) {
        const total = maleCount + femaleCount;
        const malePct = total > 0 ? Math.round((maleCount / total) * 100) : 0;
        const femalePct = total > 0 ? 100 - malePct : 0;

        const arc = document.getElementById('gender-donut-arc');
        if (arc) {
            arc.setAttribute('stroke-dasharray', `${malePct} 100`);
        }

        document.getElementById('gender-donut-total').textContent = total;
        document.getElementById('gender-pct-male').textContent = malePct + '%';
        document.getElementById('gender-pct-female').textContent = femalePct + '%';
    }

    function loadCharts() {
        // Trend chart: stub data for now (see TODO above).
        renderTrendChart(STUB_TREND_DATA);

        // Gender donut: pulled from the real summary endpoint.
        // Assumes api/reports.php?action=summary also returns male_employees / female_employees.
        // If it doesn't yet, add those fields server-side and this will pick them up automatically.
        fetchJSON('api/reports.php?action=summary&date=' + today).then(data => {
            if (!data) return;
            const maleCount = data.male_employees ?? 0;
            const femaleCount = data.female_employees ?? 0;
            renderGenderDonut(maleCount, femaleCount);
        });
    }

    const loadTransactionCards = (type, elementId, badgeClass, emptyText) => {
        fetchJSON('api/transactions/index.php/type/' + type + '?date_from=' + today + '&date_to=' + today).then(data => {
            const el = document.getElementById(elementId);
            if (!Array.isArray(data) || data.length === 0) {
                el.innerHTML = `<div class="dashboard-loading-state">${emptyText}</div>`;
                return;
            }
            el.innerHTML = data.slice(0, 4).map(tx => `
            <div class="tx-row">
                <div>
                    <p class="dashboard-activity-name">${tx.full_name ?? '-'}</p>
                    <p class="dashboard-activity-meta">${tx.employee_code ? tx.employee_code : 'Employee'}</p>
                </div>
                <span class="dashboard-activity-pill ${badgeClass}">${type === 'arrival' ? 'Arriving' : 'Departing'}</span>
            </div>`).join('');
        });
    };

    const loadDashboard = () => {
        loadDashboardSummary();
        loadRecentEmployees();
        loadCharts();
        loadTransactionCards('arrival', 'dashboard-arrivals', 'badge-status-arriving', 'No arrivals scheduled today.');
        loadTransactionCards('departure', 'dashboard-departures', 'badge-status-departing', 'No departures scheduled today.');
    };

    const refreshButton = document.getElementById('dashboardRefreshBtn');
    if (refreshButton) {
        refreshButton.addEventListener('click', loadDashboard);
    }

    loadDashboard();
    window.dashboardRefreshInterval = setInterval(loadDashboard, 30000);
});
</script>

<?php include 'layouts/footer.php'; ?>  