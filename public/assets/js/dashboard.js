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

    function showChartTooltip(event, label, value) {
        const tooltip = document.getElementById('dashboard-chart-tooltip');
        if (!tooltip) return;

        const container = tooltip.parentElement;
        tooltip.innerHTML = `<strong>${label}</strong><br>${value}`;
        tooltip.style.display = 'block';

        if (container) {
            const rect = container.getBoundingClientRect();
            const pointerX = typeof event.clientX === 'number' ? event.clientX - rect.left : (event.offsetX ?? 0);
            const pointerY = typeof event.clientY === 'number' ? event.clientY - rect.top : (event.offsetY ?? 0);
            const tooltipWidth = tooltip.offsetWidth || 110;
            const tooltipHeight = tooltip.offsetHeight || 44;
            const left = Math.min(Math.max(pointerX, tooltipWidth / 2 + 8), rect.width - tooltipWidth / 2 - 8);
            const top = Math.min(Math.max(pointerY - 12, tooltipHeight + 8), rect.height - 8);

            tooltip.style.left = left + 'px';
            tooltip.style.top = top + 'px';
        } else {
            tooltip.style.left = (event.offsetX ?? 0) + 'px';
            tooltip.style.top = (event.offsetY ?? 0) + 'px';
        }
    }

    function hideChartTooltip() {
        const tooltip = document.getElementById('dashboard-chart-tooltip');
        if (tooltip) {
            tooltip.style.display = 'none';
        }
    }

    function renderTrendChart(trendData) {
        const svg = document.getElementById('trend-chart-svg');
        const labelsEl = document.getElementById('trend-chart-labels');
        const legendEl = document.getElementById('trend-chart-legend');
        if (!svg || !trendData || !Array.isArray(trendData.series)) return;

        const labels = Array.isArray(trendData.labels) && trendData.labels.length
            ? trendData.labels
            : ['No data'];
        const chartHeight = 180;
        const chartTop = 10;
        const chartLeft = 20;
        const chartRight = 780;
        const chartWidth = chartRight - chartLeft;
        const maxValue = Math.max(1, ...trendData.series.flatMap(s => s.values));
        const seriesPointSets = [];

        let svgContent = `
            <line x1="${chartLeft}" y1="180" x2="${chartRight}" y2="180" stroke="#f1f5f9" stroke-width="1"></line>
            <line x1="${chartLeft}" y1="130" x2="${chartRight}" y2="130" stroke="#f1f5f9" stroke-width="1"></line>
            <line x1="${chartLeft}" y1="80" x2="${chartRight}" y2="80" stroke="#f1f5f9" stroke-width="1"></line>
            <line x1="${chartLeft}" y1="30" x2="${chartRight}" y2="30" stroke="#f1f5f9" stroke-width="1"></line>
        `;

        trendData.series.forEach((series) => {
            const points = series.values.map((value, index) => {
                const x = chartLeft + (labels.length > 1 ? (index * chartWidth) / (labels.length - 1) : chartWidth / 2);
                const y = chartTop + (chartHeight - Math.round((Number(value || 0) / maxValue) * chartHeight));
                return { x, y, value, label: labels[index] || '' };
            });

            seriesPointSets.push(points);
            const path = points.map((point, index) => `${index === 0 ? 'M' : 'L'} ${point.x} ${point.y}`).join(' ');
            svgContent += `<path class="dashboard-chart-line" d="${path}" fill="none" stroke="${series.color}" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"></path>`;

            points.forEach((point, pointIndex) => {
                const labelX = Math.min(chartRight - 36, point.x + 8);
                const labelY = point.y - 2;
                svgContent += `<circle class="dashboard-chart-point" data-label="${labels[pointIndex] || ''}" data-value="${point.value}" cx="${point.x}" cy="${point.y}" r="4" fill="${series.color}" data-point-color="${series.color}"></circle>`;
                svgContent += `<text class="dashboard-chart-value-label" x="${labelX}" y="${labelY}" text-anchor="start" dominant-baseline="middle">${point.value}</text>`;
            });
        });

        svg.innerHTML = svgContent;

        svg.querySelectorAll('.dashboard-chart-line').forEach((line, index) => {
            const points = seriesPointSets[index] || [];
            const showLineTooltip = (event) => {
                if (!points.length) return;

                const hoverX = event.offsetX ?? 0;
                const hoverY = event.offsetY ?? 0;
                const nearestPoint = points.reduce((closest, point) => {
                    const currentDistance = Math.abs(point.x - hoverX) + Math.abs(point.y - hoverY);
                    const closestDistance = Math.abs(closest.x - hoverX) + Math.abs(closest.y - hoverY);
                    return currentDistance < closestDistance ? point : closest;
                }, points[0]);

                if (!nearestPoint) return;
                showChartTooltip(event, nearestPoint.label, `${nearestPoint.value} employee${Number(nearestPoint.value) === 1 ? '' : 's'}`);
            };

            line.addEventListener('mouseenter', showLineTooltip);
            line.addEventListener('mousemove', showLineTooltip);
            line.addEventListener('mouseleave', hideChartTooltip);
        });

        svg.querySelectorAll('.dashboard-chart-point').forEach((point) => {
            point.addEventListener('mouseenter', function (event) {
                const label = point.getAttribute('data-label');
                const value = point.getAttribute('data-value');
                showChartTooltip(event, label, `${value} employee${Number(value) === 1 ? '' : 's'}`);
            });
            point.addEventListener('mousemove', function (event) {
                const label = point.getAttribute('data-label');
                const value = point.getAttribute('data-value');
                showChartTooltip(event, label, `${value} employee${Number(value) === 1 ? '' : 's'}`);
            });
            point.addEventListener('mouseleave', hideChartTooltip);
        });

        labelsEl.innerHTML = labels.map(label => `<span>${label}</span>`).join('');

        legendEl.innerHTML = trendData.series
            .map(series => `
                <span class="dashboard-chart-legend-item">
                    <span class="dashboard-chart-legend-swatch" style="background:${series.color};"></span>
                    ${series.label}
                </span>`)
            .join('');
    }

    function loadDepartmentChart() {
        Promise.all([
            fetchJSON('api/departments.php'),
            fetchJSON('api/employees.php')
        ]).then(([departments, employees]) => {
            const departmentList = Array.isArray(departments) ? departments : [];
            const employeeList = Array.isArray(employees) ? employees : [];

            const labels = departmentList.length
                ? departmentList.map(dept => dept.department_name || 'Unnamed')
                : [...new Set(employeeList.map(emp => emp.department_name || 'Unassigned').filter(Boolean))];

            if (!labels.length) {
                labels.push('No departments');
            }

            const values = labels.map(label => {
                return employeeList.filter(emp => (emp.department_name || 'Unassigned') === label).length;
            });

            renderTrendChart({
                labels,
                series: [{
                    label: 'Employees',
                    color: '#00639d',
                    values
                }]
            });
        });
    }

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
        loadDepartmentChart();

        fetchJSON('api/reports.php?action=summary&date=' + today).then(data => {
            if (!data) return;
            const maleCount = Number(data.male_employees ?? 0);
            const femaleCount = Number(data.female_employees ?? 0);
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
