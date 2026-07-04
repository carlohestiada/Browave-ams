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
        Promise.all([
            fetchJSON('api/employees.php'),
            fetchJSON('api/rooms.php'),
            fetchJSON('api/departments.php')
        ]).then(([employees, rooms, departments]) => {
            const employeeList = Array.isArray(employees) ? employees : [];
            const roomList = Array.isArray(rooms) ? rooms : [];
            const departmentList = Array.isArray(departments) ? departments : [];

            const totalEmployees = employeeList.length;
            const activeEmployees = employeeList.filter(emp => String(emp.status || '').toLowerCase() === 'active').length;
            const occupiedRooms = roomList.filter(room => String(room.status || '').toLowerCase() === 'occupied').length;
            const availableRooms = roomList.filter(room => String(room.status || '').toLowerCase() === 'available').length;
            const reservedRooms = roomList.filter(room => String(room.status || '').toLowerCase() === 'reserved').length;
            const maintenanceRooms = roomList.filter(room => String(room.status || '').toLowerCase() === 'maintenance').length;
            const totalRooms = roomList.length;
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
            document.getElementById('kpi-departments').textContent = departmentList.length;

            renderRoomStatusPanel({
                Occupied: occupiedRooms,
                Available: availableRooms,
                Reserved: reservedRooms,
                Maintenance: maintenanceRooms,
                Other: 0
            }, totalRooms);
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

    function navigateToEmployees(filters) {
        const params = new URLSearchParams();
        Object.entries(filters || {}).forEach(([key, value]) => {
            if (value !== null && value !== undefined && value !== '') {
                params.set(key, String(value));
            }
        });

        const query = params.toString();
        window.location.href = `employees.php${query ? `?${query}` : ''}`;
    }

    function showChartTooltip(event, content) {
        const tooltip = document.getElementById('dashboard-chart-tooltip');
        if (!tooltip) return;

        const container = tooltip.parentElement;
        tooltip.innerHTML = content;
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

    function showDepartmentChartEmptyState() {
        const emptyState = document.getElementById('department-chart-empty');
        const svg = document.getElementById('trend-chart-svg');
        const labelsEl = document.getElementById('trend-chart-labels');
        const legendEl = document.getElementById('trend-chart-legend');

        if (emptyState) emptyState.style.display = 'flex';
        if (svg) svg.style.display = 'none';
        if (labelsEl) labelsEl.style.display = 'none';
        if (legendEl) legendEl.innerHTML = '';
    }

    function hideDepartmentChartEmptyState() {
        const emptyState = document.getElementById('department-chart-empty');
        const svg = document.getElementById('trend-chart-svg');
        const labelsEl = document.getElementById('trend-chart-labels');

        if (emptyState) emptyState.style.display = 'none';
        if (svg) svg.style.display = 'block';
        if (labelsEl) labelsEl.style.display = 'flex';
    }

    function showGenderChartEmptyState() {
        const emptyState = document.getElementById('gender-chart-empty');
        const donutChart = document.querySelector('.dashboard-donut-chart');
        const center = document.querySelector('.dashboard-donut-center');
        const legend = document.querySelector('.dashboard-donut-legend');

        if (emptyState) emptyState.style.display = 'flex';
        if (donutChart) donutChart.style.display = 'none';
        if (center) center.style.display = 'none';
        if (legend) legend.style.opacity = '0.5';
    }

    function hideGenderChartEmptyState() {
        const emptyState = document.getElementById('gender-chart-empty');
        const donutChart = document.querySelector('.dashboard-donut-chart');
        const center = document.querySelector('.dashboard-donut-center');
        const legend = document.querySelector('.dashboard-donut-legend');

        if (emptyState) emptyState.style.display = 'none';
        if (donutChart) donutChart.style.display = 'block';
        if (center) center.style.display = 'flex';
        if (legend) legend.style.opacity = '1';
    }

    function renderTrendChart(trendData) {
        const svg = document.getElementById('trend-chart-svg');
        const labelsEl = document.getElementById('trend-chart-labels');
        const legendEl = document.getElementById('trend-chart-legend');
        if (!svg || !trendData || !Array.isArray(trendData.series)) return;

        const labels = Array.isArray(trendData.labels) && trendData.labels.length
            ? trendData.labels
            : ['No data'];
        const chartHeight = 360;
        const chartTop = 20;
        const chartLeft = 24;
        const chartRight = 776;
        const chartWidth = chartRight - chartLeft;
        const pointMeta = Array.isArray(trendData.pointMeta) ? trendData.pointMeta : [];
        const rawValues = trendData.series.flatMap(s => s.values.map(value => Number(value || 0)));
        const hasData = rawValues.some(value => value > 0);

        if (!hasData) {
            showDepartmentChartEmptyState();
            return;
        }

        hideDepartmentChartEmptyState();
        const maxValue = Math.max(1, ...rawValues);
        const baseline = Math.max(3, maxValue * 0.08);
        const scaledMax = maxValue + baseline;
        const totalEmployees = rawValues.reduce((sum, value) => sum + value, 0);
        const seriesPointSets = [];

        const gridLines = [chartTop + chartHeight, chartTop + chartHeight * 0.66, chartTop + chartHeight * 0.33, chartTop + 8];
        let svgContent = gridLines.map(y => `
            <line x1="${chartLeft}" y1="${y}" x2="${chartRight}" y2="${y}" stroke="#e2e8f0" stroke-width="1"></line>`).join('');

        function smoothPath(points) {
            if (points.length < 2) return '';
            let path = `M ${points[0].x} ${points[0].y}`;
            for (let i = 0; i < points.length - 1; i += 1) {
                const p0 = points[i === 0 ? i : i - 1];
                const p1 = points[i];
                const p2 = points[i + 1];
                const p3 = points[i + 2] || p2;
                const tension = 0.3;
                const cp1x = p1.x + (p2.x - p0.x) * tension;
                const cp1y = p1.y + (p2.y - p0.y) * tension;
                const cp2x = p2.x - (p3.x - p1.x) * tension;
                const cp2y = p2.y - (p3.y - p1.y) * tension;
                path += ` C ${cp1x} ${cp1y} ${cp2x} ${cp2y} ${p2.x} ${p2.y}`;
            }
            return path;
        }

        trendData.series.forEach((series) => {
            const points = series.values.map((value, index) => {
                const numericValue = Number(value || 0);
                const x = chartLeft + (labels.length > 1 ? (index * chartWidth) / (labels.length - 1) : chartWidth / 2);
                const y = chartTop + Math.round(chartHeight - ((numericValue + baseline) / scaledMax) * chartHeight);
                const meta = pointMeta[index] || {};
                return {
                    x,
                    y,
                    value: numericValue,
                    label: labels[index] || '',
                    departmentId: meta.departmentId ?? null,
                    departmentName: meta.departmentName || labels[index] || ''
                };
            });

            seriesPointSets.push(points);
            const smooth = smoothPath(points);
            const areaPath = `${smooth} L ${points[points.length - 1].x} ${chartTop + chartHeight} L ${points[0].x} ${chartTop + chartHeight} Z`;
            svgContent += `<path class="dashboard-chart-area" d="${areaPath}" fill="rgba(0, 99, 157, 0.12)" stroke="none"></path>`;
            svgContent += `<path class="dashboard-chart-line dimmed" d="${smooth}" fill="none" stroke="${series.color}" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"></path>`;

            points.forEach((point) => {
                const labelX = point.x;
                const labelY = point.y - 16;
                svgContent += `<circle class="dashboard-chart-point" data-label="${point.label}" data-value="${point.value}" cx="${point.x}" cy="${point.y}" r="5" fill="${series.color}" data-point-color="${series.color}"></circle>`;
                svgContent += `<text class="dashboard-chart-value-label" x="${labelX}" y="${labelY}" text-anchor="middle" dominant-baseline="middle">${point.value}</text>`;
            });
        });

        svg.innerHTML = svgContent;

        const lineElements = svg.querySelectorAll('.dashboard-chart-line');
        const pointElements = svg.querySelectorAll('.dashboard-chart-point');

        function setActivePoint(activePoint) {
            pointElements.forEach(point => {
                point.classList.toggle('active', point === activePoint);
            });
            lineElements.forEach(line => {
                line.classList.toggle('active', Boolean(activePoint));
            });
        }

        function showDepartmentTooltip(event, point) {
            if (!point) return;
            const label = point.label;
            const value = Number(point.value);
            const percentage = totalEmployees > 0 ? Math.round((value / totalEmployees) * 100) : 0;
            const html = `<div>Department: <strong>${label}</strong></div>` +
                         `<div>Employees: <strong>${value}</strong></div>` +
                         `<div>Percentage: <strong>${percentage}%</strong></div>`;
            showChartTooltip(event, html);
        }

        function getNearestPoint(points, x) {
            return points.reduce((closest, p) => {
                return Math.abs(p.x - x) < Math.abs(closest.x - x) ? p : closest;
            }, points[0]);
        }

        const points = seriesPointSets[0] || [];

        lineElements.forEach((line) => {
            line.addEventListener('mouseenter', function () {
                setActivePoint(null);
            });
            line.addEventListener('mousemove', function (event) {
                if (!points.length) return;
                const hoverX = event.offsetX ?? 0;
                const nearest = getNearestPoint(points, hoverX);
                const nearestIndex = points.indexOf(nearest);
                const activePoint = pointElements[nearestIndex] || null;
                setActivePoint(activePoint);
                showDepartmentTooltip(event, nearest);
            });
            line.addEventListener('mouseleave', function () {
                setActivePoint(null);
                hideChartTooltip();
            });
            line.addEventListener('click', function (event) {
                if (!points.length) return;
                const hoverX = event.offsetX ?? 0;
                const nearest = getNearestPoint(points, hoverX);
                if (nearest && nearest.departmentId) {
                    navigateToEmployees({ department_id: nearest.departmentId });
                }
            });
        });

        pointElements.forEach((point) => {
            point.addEventListener('mouseenter', function (event) {
                const pointIndex = Array.from(pointElements).indexOf(point);
                setActivePoint(point);
                showDepartmentTooltip(event, points[pointIndex]);
            });
            point.addEventListener('mousemove', function (event) {
                const pointIndex = Array.from(pointElements).indexOf(point);
                setActivePoint(point);
                showDepartmentTooltip(event, points[pointIndex]);
            });
            point.addEventListener('mouseleave', function () {
                setActivePoint(null);
                hideChartTooltip();
            });
            point.addEventListener('click', function () {
                const pointIndex = Array.from(pointElements).indexOf(point);
                const clickedPoint = points[pointIndex];
                if (clickedPoint && clickedPoint.departmentId) {
                    navigateToEmployees({ department_id: clickedPoint.departmentId });
                }
            });
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
            const departmentIds = departmentList.length
                ? departmentList.map(dept => dept.id)
                : labels.map(() => null);

            if (!labels.length) {
                labels.push('No departments');
            }

            const values = labels.map(label => {
                return employeeList.filter(emp => (emp.department_name || 'Unassigned') === label).length;
            });

            renderTrendChart({
                labels,
                pointMeta: labels.map((label, index) => ({
                    departmentId: departmentIds[index] ?? null,
                    departmentName: label
                })),
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

        if (total <= 0) {
            showGenderChartEmptyState();
            document.getElementById('gender-donut-total').textContent = '0';
            document.getElementById('gender-pct-male').textContent = '0%';
            document.getElementById('gender-pct-female').textContent = '0%';
            return;
        }

        hideGenderChartEmptyState();
        const femalePct = total > 0 ? 100 - malePct : 0;

        const maleArc = document.getElementById('gender-donut-arc');
        const femaleArc = document.getElementById('gender-donut-arc-female');
        const tooltip = document.getElementById('gender-chart-tooltip');

        const setArcState = (activeArc) => {
            [maleArc, femaleArc].filter(Boolean).forEach((arc) => {
                const isActive = arc === activeArc;
                arc.classList.toggle('is-active', isActive);
                arc.classList.toggle('is-dimmed', !isActive && Boolean(activeArc));
            });
        };

        const showTooltip = (event, label, count, pct) => {
            if (!tooltip) return;
            const safePct = Number.isFinite(pct) ? pct : 0;
            tooltip.innerHTML = `
                <span class="dashboard-gender-tooltip-indicator" style="background:${label === 'Female' ? '#e879a0' : '#00639d'}"></span>
                <div>
                    <div class="dashboard-gender-tooltip-title">${label}</div>
                    <div class="dashboard-gender-tooltip-value">${count} employees</div>
                    <div class="dashboard-gender-tooltip-meta">${safePct}% of total</div>
                </div>`;

            const wrap = tooltip.parentElement;
            if (!wrap) return;
            const rect = wrap.getBoundingClientRect();
            const x = ((event.clientX || 0) - rect.left);
            const y = ((event.clientY || 0) - rect.top);
            tooltip.style.left = `${Math.min(Math.max(x, 70), rect.width - 70)}px`;
            tooltip.style.top = `${Math.max(Math.min(y - 12, rect.height - 40), 36)}px`;
            tooltip.classList.add('is-visible');
        };

        const hideTooltip = () => {
            if (tooltip) {
                tooltip.classList.remove('is-visible');
            }
            setArcState(null);
        };

        if (maleArc) {
            maleArc.setAttribute('stroke-dasharray', `${malePct} 100`);
            maleArc.setAttribute('data-gender', 'Male');
            maleArc.setAttribute('data-count', maleCount);
            maleArc.setAttribute('data-percent', malePct);
            maleArc.addEventListener('click', () => {
                navigateToEmployees({ gender: 'Male' });
            });
            maleArc.addEventListener('mouseenter', (event) => {
                setArcState(maleArc);
                showTooltip(event, 'Male', maleCount, malePct);
            });
            maleArc.addEventListener('mousemove', (event) => {
                setArcState(maleArc);
                showTooltip(event, 'Male', maleCount, malePct);
            });
            maleArc.addEventListener('mouseleave', hideTooltip);
        }

        if (femaleArc) {
            femaleArc.setAttribute('stroke-dasharray', `${femalePct} 100`);
            femaleArc.setAttribute('data-gender', 'Female');
            femaleArc.setAttribute('data-count', femaleCount);
            femaleArc.setAttribute('data-percent', femalePct);
            femaleArc.addEventListener('click', () => {
                navigateToEmployees({ gender: 'Female' });
            });
            femaleArc.addEventListener('mouseenter', (event) => {
                setArcState(femaleArc);
                showTooltip(event, 'Female', femaleCount, femalePct);
            });
            femaleArc.addEventListener('mousemove', (event) => {
                setArcState(femaleArc);
                showTooltip(event, 'Female', femaleCount, femalePct);
            });
            femaleArc.addEventListener('mouseleave', hideTooltip);
        }

        document.querySelectorAll('.dashboard-donut-legend-item').forEach((item) => {
            item.addEventListener('mouseenter', () => {
                const label = item.querySelector('.dashboard-donut-legend-label')?.textContent?.trim();
                const arc = label === 'Female' ? femaleArc : maleArc;
                setArcState(arc);
                if (arc && tooltip) {
                    showTooltip({ clientX: 0, clientY: 0 }, label, Number(arc.getAttribute('data-count') || 0), Number(arc.getAttribute('data-percent') || 0));
                }
            });
            item.addEventListener('mouseleave', hideTooltip);
        });

        document.getElementById('gender-donut-total').textContent = total;
        document.getElementById('gender-pct-male').textContent = malePct + '%';
        document.getElementById('gender-pct-female').textContent = femalePct + '%';
    }

    function loadCharts() {
        loadDepartmentChart();

        fetchJSON('api/employees.php').then(data => {
            const employeeList = Array.isArray(data) ? data : [];
            const maleCount = employeeList.filter(emp => String(emp.gender || '').toLowerCase() === 'male').length;
            const femaleCount = employeeList.filter(emp => String(emp.gender || '').toLowerCase() === 'female').length;
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

// Pie chart for gender

(function () {
    function parsePct(text) {
        const m = String(text || '').match(/([\d.]+)/);
        return m ? parseFloat(m[1]) : null;
    }

    function updateBar() {
        const maleEl   = document.getElementById('gender-pct-male');
        const femaleEl = document.getElementById('gender-pct-female');
        const barM     = document.getElementById('gender-bar-male');
        const barF     = document.getElementById('gender-bar-female');
        const lblM     = document.getElementById('gender-bar-label-male');
        const lblF     = document.getElementById('gender-bar-label-female');

        if (!maleEl || !barM) return;

        const malePct   = parsePct(maleEl.textContent);
        const femalePct = parsePct(femaleEl ? femaleEl.textContent : null);

        if (malePct === null) return;

        const fp = femalePct !== null ? femalePct : (100 - malePct);

        barM.style.width = malePct + '%';
        barF.style.width = fp + '%';
        if (lblM) lblM.textContent = 'Male ' + malePct + '%';
        if (lblF) lblF.textContent = 'Female ' + fp + '%';

        /* also tint the female arc */
        const femArc = document.getElementById('gender-donut-arc-female');
        if (femArc) {
            const offset = malePct; /* female starts where male ends */
            femArc.setAttribute('stroke-dasharray', fp + ' ' + (100 - fp));
            femArc.setAttribute('stroke-dashoffset', -offset);
        }
    }

    /* Run once on load in case dashboard.js already ran */
    document.addEventListener('DOMContentLoaded', function () {
        updateBar();

        /* Watch for dashboard.js updating the text nodes */
        const targets = [
            document.getElementById('gender-pct-male'),
            document.getElementById('gender-pct-female'),
            document.getElementById('gender-donut-total')
        ].filter(Boolean);

        if (!targets.length) return;

        const obs = new MutationObserver(updateBar);
        targets.forEach(function (el) {
            obs.observe(el, { childList: true, characterData: true, subtree: true });
        });
    });
}());
