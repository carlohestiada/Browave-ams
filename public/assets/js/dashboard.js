function initDashboard() {
  function getLocalDateString(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, "0");
    const day = String(date.getDate()).padStart(2, "0");
    return `${year}-${month}-${day}`;
  }

  const today = getLocalDateString(new Date());

  let departmentChartInstance = null;

  function fetchJSON(url, options = {}) {
    return fetch(url, options)
      .then((response) =>
        response.ok ? response.json() : Promise.reject(response),
      )
      .catch(() => null);
  }

  function setKpiValue(id, value, fallback = "-") {
    const el = document.getElementById(id);
    if (!el) return;
    const numericValue = Number(value);
    el.textContent = Number.isFinite(numericValue)
      ? numericValue
      : (fallback ?? 0);
  }

  function setTextValue(id, value) {
    const el = document.getElementById(id);
    if (el) {
      el.textContent = value;
    }
  }

  function escapeHtml(value) {
    return String(value ?? "").replace(/[&<>'"]/g, (character) => ({
      "&": "&amp;",
      "<": "&lt;",
      ">": "&gt;",
      "'": "&#039;",
      '"': "&quot;",
    })[character]);
  }

//   Rooms Status Panel

function renderRoomStatusPanel(summary, total) {
  const panel = document.getElementById("dashboard-room-status");
  if (!panel) return;

  const meta = {
    Occupied: { color: "var(--blue)", icon: "bi-person-fill" },
    Available: { color: "var(--teal-dim)", icon: "bi-door-open-fill" },
    Reserved: { color: "var(--amber)", icon: "bi-bookmark-fill" },
    Maintenance: { color: "var(--coral)", icon: "bi-tools" },
  };

  const totalRooms = summary.total_rooms ?? total ?? 0;

  const statuses = [
    { label: "Occupied", value: summary.occupied_rooms ?? 0 },
    { label: "Available", value: summary.available_rooms ?? 0 },
    { label: "Reserved", value: summary.reserved_rooms ?? 0 },
    { label: "Maintenance", value: summary.maintenance_rooms ?? 0 },
  ];

  const rows = statuses
    .map(({ label, value }) => {
      const pct = totalRooms > 0 ? Math.round((value / totalRooms) * 100) : 0;
      const { color, icon } = meta[label];
      const isZero = value === 0;
      return `
      <div class="room-status-hbar-row${isZero ? " is-zero" : ""}">
        <div class="room-status-hbar-label" style="--rs-color:${color}"><i class="bi ${icon}"></i>${label}</div>
        <div class="room-status-hbar-track">
          <div class="room-status-hbar-fill" style="width:${pct}%; background:${color};"></div>
        </div>
        <div class="room-status-hbar-value">${value}<span class="pct">${pct}%</span></div>
      </div>`;
    })
    .join("");

  const roomTypes = Object.entries(summary.room_types || {})
    .map(([name, count]) => ({ name: name.trim(), count: Number(count) || 0 }))
    .filter((type) => type.name);

  const chips = roomTypes.length
    ? roomTypes
        .map(
          (t) =>
            `<span class="room-status-chip" title="${escapeHtml(t.name)}">
              <span class="room-status-chip-name">${escapeHtml(t.name)}</span>
              <span class="room-status-chip-count">${t.count}</span>
            </span>`,
        )
        .join("")
    : `<span class="room-status-chip room-status-chip--empty">None</span>`;

  panel.innerHTML = `
  <div class="room-status-total-line">
    <span class="total-count">${totalRooms}</span>
    <span class="total-label">Total Rooms</span>
  </div>
  <div class="room-status-hbar-list">${rows}</div>
  <div class="room-status-types-card">
    <div class="room-status-types-heading">
      <div class="room-status-summary-label"><i class="bi bi-grid-fill"></i>Room Types</div>
      <span class="room-status-types-total">${roomTypes.length} ${roomTypes.length === 1 ? "type" : "types"}</span>
    </div>
    <div class="room-status-chip-row">${chips}</div>
  </div>`;
}

function renderRoomStatusMessage(message) {
  const panel = document.getElementById("dashboard-room-status");
  if (panel)
    panel.innerHTML = `<div class="dashboard-loading-state">${message}</div>`;
}

  function loadDashboardSummary() {
    Promise.all([
      fetchJSON("api/employees.php"),
      fetchJSON("api/room_status_summary.php", { cache: "no-store" }),
      fetchJSON("api/departments.php"),
    ])
      .then(([employees, roomSummary, departments]) => {
        if (!roomSummary || roomSummary.success === false) {
          throw new Error("Invalid room status summary response");
        }

        const employeeList = Array.isArray(employees) ? employees : [];
        const summary = roomSummary;
        const departmentList = Array.isArray(departments) ? departments : [];

        const totalEmployees = employeeList.length;
        const activeEmployees = employeeList.filter(
          (emp) => String(emp.status || "").toLowerCase() === "active",
        ).length;

        const totalRooms = Number(summary.total_rooms || 0);
        const occupiedRooms = Number(summary.occupied_rooms || 0);
        const availableRooms = Number(summary.available_rooms || 0);
        const reservedRooms = Number(summary.reserved_rooms || 0);
        const maintenanceRooms = Number(summary.maintenance_rooms || 0);
        const roomTypes = summary.room_types || {};
        const roomTypesText = Object.entries(roomTypes)
          .map(([type, count]) => `${type} ${count}`)
          .join(" | ");

        const activePct =
          totalEmployees > 0
            ? Math.round((activeEmployees / totalEmployees) * 100)
            : 0;
        const occupiedPct =
          totalRooms > 0 ? Math.round((occupiedRooms / totalRooms) * 100) : 0;
        const availablePct =
          totalRooms > 0 ? Math.round((availableRooms / totalRooms) * 100) : 0;

        setKpiValue("kpi-total-employees", totalEmployees, 0);
        setKpiValue("kpi-active-employees", activeEmployees, 0);
        setTextValue("kpi-active-pct", activePct + "%");

        setKpiValue("kpi-total-rooms", totalRooms, 0);
        setKpiValue("kpi-occupied", occupiedRooms, 0);
        setKpiValue("kpi-available", availableRooms, 0);
        setTextValue("kpi-occupied-pct", occupiedPct + "%");
        setTextValue("kpi-available-pct", availablePct + "%");
        setKpiValue("kpi-departments", departmentList.length, 0);
        loadCompanyCarKPIs();

        renderRoomStatusPanel(
          {
            total_rooms: totalRooms,
            occupied_rooms: occupiedRooms,
            available_rooms: availableRooms,
            reserved_rooms: reservedRooms,
          maintenance_rooms: maintenanceRooms,
          room_types: roomTypes,
          room_types_text: roomTypesText || "None",
          },
          totalRooms,
        );
      })
      .catch((error) => {
        console.error("Dashboard summary load failed:", error);
        renderRoomStatusMessage("Unable to load room status.");
      });
  }

  function loadCompanyCarKPIs() {
    fetchJSON("api/company-car/index.php?stats=1").then((stats) => {
      if (!stats) return;
      setKpiValue(
        "kpi-companycar-total",
        stats.active_company_car_requests ??
          stats.total_company_car_requests ??
          stats.todays_requests,
        0,
      );
      setKpiValue("kpi-companycar-available", stats.available_vehicles, 0);
    });
  }

  //   End of Rooms Status Panel

//   RECENT EMPLOYEES PANEL

  function loadRecentEmployees() {
    fetchJSON("api/employees.php").then((data) => {
      const tbody = document.getElementById("dashboard-employees");
      if (!Array.isArray(data) || data.length === 0) {
        tbody.innerHTML =
          '<tr><td colspan="3" class="dashboard-loading-state">No employees found.</td></tr>';
        return;
      }

      tbody.innerHTML = data
        .slice(0, 5)
        .map((emp) => {
          const initials = (emp.english_name || "")
            .split(" ")
            .map((w) => w[0])
            .slice(0, 2)
            .join("")
            .toUpperCase();
          const colors = [
            "#dbeafe:#1d4ed8",
            "#ede9fe:#6d28d9",
            "#fce7f3:#be185d",
            "#ffedd5:#c2410c",
            "#d1fae5:#065f46",
          ];
          const [bg, fg] =
            colors[Math.abs(emp.id || 0) % colors.length].split(":");
          const badge =
            emp.status === "Active" ? "badge-active" : "badge-inactive";
          return `
                <tr>
                    <td>
                        <div class="dashboard-activity-name-wrap">
                            <div class="dashboard-employee-avatar" style="background:${bg}; color:${fg};">${initials}</div>
                            <span class="dashboard-employee-name">${emp.english_name ?? "-"}</span>
                        </div>
                    </td>
                    <td class="dashboard-status-label">${emp.department_name ?? "-"}</td>
                    <td><span class="${badge}">${emp.status ?? "-"}</span></td>
                </tr>`;
        })
        .join("");
    });
  }

//   END RECENT EMPLOYEES PANEL 

  function navigateToEmployees(filters) {
    const params = new URLSearchParams();
    Object.entries(filters || {}).forEach(([key, value]) => {
      if (value !== null && value !== undefined && value !== "") {
        params.set(key, String(value));
      }
    });

    const query = params.toString();
    window.location.href = `employees.php${query ? `?${query}` : ""}`;
  }

  function showChartTooltip(event, content) {
    const tooltip = document.getElementById("dashboard-chart-tooltip");
    if (!tooltip) return;

    const container = tooltip.parentElement;
    tooltip.innerHTML = content;
    tooltip.style.display = "block";

    if (container) {
      const rect = container.getBoundingClientRect();
      const pointerX =
        typeof event.clientX === "number"
          ? event.clientX - rect.left
          : (event.offsetX ?? 0);
      const pointerY =
        typeof event.clientY === "number"
          ? event.clientY - rect.top
          : (event.offsetY ?? 0);
      const tooltipWidth = tooltip.offsetWidth || 110;
      const tooltipHeight = tooltip.offsetHeight || 44;
      const left = Math.min(
        Math.max(pointerX, tooltipWidth / 2 + 8),
        rect.width - tooltipWidth / 2 - 8,
      );
      const top = Math.min(
        Math.max(pointerY - 12, tooltipHeight + 8),
        rect.height - 8,
      );

      tooltip.style.left = left + "px";
      tooltip.style.top = top + "px";
    } else {
      tooltip.style.left = (event.offsetX ?? 0) + "px";
      tooltip.style.top = (event.offsetY ?? 0) + "px";
    }
  }

  function hideChartTooltip() {
    const tooltip = document.getElementById("dashboard-chart-tooltip");
    if (tooltip) {
      tooltip.style.display = "none";
    }
  }

  function showDepartmentChartEmptyState() {
    const emptyState = document.getElementById("department-chart-empty");
    const svg = document.getElementById("trend-chart-svg");
    const labelsEl = document.getElementById("trend-chart-labels");
    const legendEl = document.getElementById("trend-chart-legend");

    if (emptyState) emptyState.style.display = "flex";
    if (svg) svg.style.display = "none";
    if (labelsEl) labelsEl.style.display = "none";
    if (legendEl) legendEl.innerHTML = "";
  }

  function hideDepartmentChartEmptyState() {
    const emptyState = document.getElementById("department-chart-empty");
    const svg = document.getElementById("trend-chart-svg");
    const labelsEl = document.getElementById("trend-chart-labels");

    if (emptyState) emptyState.style.display = "none";
    if (svg) svg.style.display = "block";
    if (labelsEl) labelsEl.style.display = "flex";
  }

  function showGenderChartEmptyState() {
    const emptyState = document.getElementById("gender-chart-empty");
    const donutChart = document.querySelector(".dashboard-donut-chart");
    const center = document.querySelector(".dashboard-donut-center");
    const legend = document.querySelector(".dashboard-donut-legend");

    if (emptyState) emptyState.style.display = "flex";
    if (donutChart) donutChart.style.display = "none";
    if (center) center.style.display = "none";
    if (legend) legend.style.opacity = "0.5";
  }

  function hideGenderChartEmptyState() {
    const emptyState = document.getElementById("gender-chart-empty");
    const donutChart = document.querySelector(".dashboard-donut-chart");
    const center = document.querySelector(".dashboard-donut-center");
    const legend = document.querySelector(".dashboard-donut-legend");

    if (emptyState) emptyState.style.display = "none";
    if (donutChart) donutChart.style.display = "block";
    if (center) center.style.display = "flex";
    if (legend) legend.style.opacity = "1";
  }

  function renderTrendChart(trendData) {
    const svg = document.getElementById("trend-chart-svg");
    const labelsEl = document.getElementById("trend-chart-labels");
    const legendEl = document.getElementById("trend-chart-legend");
    if (!svg || !trendData || !Array.isArray(trendData.series)) return;

    const labels =
      Array.isArray(trendData.labels) && trendData.labels.length
        ? trendData.labels
        : ["No data"];
    const svgRect = svg.getBoundingClientRect();
    const svgHeight = Math.max(420, svgRect.height || 420);
    const chartHeight = svgHeight - 60;
    const chartTop = 20;
    const chartLeft = 24;
    const chartRight = 776;
    const chartWidth = chartRight - chartLeft;
    const pointMeta = Array.isArray(trendData.pointMeta)
      ? trendData.pointMeta
      : [];
    const rawValues = trendData.series.flatMap((s) =>
      s.values.map((value) => Number(value || 0)),
    );
    const hasData = rawValues.some((value) => value > 0);

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

    const gridLines = [
      chartTop + chartHeight,
      chartTop + chartHeight * 0.66,
      chartTop + chartHeight * 0.33,
      chartTop + 8,
    ];
    let svgContent = gridLines
      .map(
        (y) => `
            <line x1="${chartLeft}" y1="${y}" x2="${chartRight}" y2="${y}" stroke="#e2e8f0" stroke-width="1"></line>`,
      )
      .join("");

    function smoothPath(points) {
      if (points.length < 2) return "";
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
        const x =
          chartLeft +
          (labels.length > 1
            ? (index * chartWidth) / (labels.length - 1)
            : chartWidth / 2);
        const y =
          chartTop +
          Math.round(
            chartHeight - ((numericValue + baseline) / scaledMax) * chartHeight,
          );
        const meta = pointMeta[index] || {};
        return {
          x,
          y,
          value: numericValue,
          label: labels[index] || "",
          departmentId: meta.departmentId ?? null,
          departmentName: meta.departmentName || labels[index] || "",
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

    const lineElements = svg.querySelectorAll(".dashboard-chart-line");
    const pointElements = svg.querySelectorAll(".dashboard-chart-point");

    function setActivePoint(activePoint) {
      pointElements.forEach((point) => {
        point.classList.toggle("active", point === activePoint);
      });
      lineElements.forEach((line) => {
        line.classList.toggle("active", Boolean(activePoint));
      });
    }

    function showDepartmentTooltip(event, point) {
      if (!point) return;
      const label = point.label;
      const value = Number(point.value);
      const percentage =
        totalEmployees > 0 ? Math.round((value / totalEmployees) * 100) : 0;
      const html =
        `<div>Department: <strong>${label}</strong></div>` +
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
      line.addEventListener("mouseenter", function () {
        setActivePoint(null);
      });
      line.addEventListener("mousemove", function (event) {
        if (!points.length) return;
        const hoverX = event.offsetX ?? 0;
        const nearest = getNearestPoint(points, hoverX);
        const nearestIndex = points.indexOf(nearest);
        const activePoint = pointElements[nearestIndex] || null;
        setActivePoint(activePoint);
        showDepartmentTooltip(event, nearest);
      });
      line.addEventListener("mouseleave", function () {
        setActivePoint(null);
        hideChartTooltip();
      });
      line.addEventListener("click", function (event) {
        if (!points.length) return;
        const hoverX = event.offsetX ?? 0;
        const nearest = getNearestPoint(points, hoverX);
        if (nearest && nearest.departmentId) {
          navigateToEmployees({ department_id: nearest.departmentId });
        }
      });
    });

    pointElements.forEach((point) => {
      point.addEventListener("mouseenter", function (event) {
        const pointIndex = Array.from(pointElements).indexOf(point);
        setActivePoint(point);
        showDepartmentTooltip(event, points[pointIndex]);
      });
      point.addEventListener("mousemove", function (event) {
        const pointIndex = Array.from(pointElements).indexOf(point);
        setActivePoint(point);
        showDepartmentTooltip(event, points[pointIndex]);
      });
      point.addEventListener("mouseleave", function () {
        setActivePoint(null);
        hideChartTooltip();
      });
      point.addEventListener("click", function () {
        const pointIndex = Array.from(pointElements).indexOf(point);
        const clickedPoint = points[pointIndex];
        if (clickedPoint && clickedPoint.departmentId) {
          navigateToEmployees({ department_id: clickedPoint.departmentId });
        }
      });
    });

    labelsEl.innerHTML = labels
      .map((label) => `<span>${label}</span>`)
      .join("");

    legendEl.innerHTML = trendData.series
      .map(
        (series) => `
                <span class="dashboard-chart-legend-item">
                    <span class="dashboard-chart-legend-swatch" style="background:${series.color};"></span>
                    ${series.label}
                </span>`,
      )
      .join("");
  }

  // DEPARTMENT TREND CHART
  async function loadDepartmentChart() {
    const statusEl = document.getElementById("departmentChartStatus");
    const canvasEl = document.getElementById("departmentChart");
    const wrapper = document.querySelector(".dept-chart-canvas-wrap");

    if (statusEl) {
      statusEl.textContent = "Loading department data...";
      statusEl.style.display = "flex";
    }
    if (canvasEl) {
      canvasEl.style.display = "none";
    }

    try {
      const res = await fetch("api/departments_summary.php");
      if (!res.ok) {
        throw new Error(`HTTP ${res.status}`);
      }

      const json = await res.json();
      if (!Array.isArray(json)) {
        throw new Error("Invalid department summary response format");
      }

      const data = json.map((item) => ({
        department: String(item.department ?? "").trim(),
        employee_count: Number(item.employee_count),
      }));

      if (
        data.some(
          (item) => item.department === "" || Number.isNaN(item.employee_count),
        )
      ) {
        throw new Error("Invalid department summary data");
      }

      if (data.length === 0) {
        if (statusEl) {
          statusEl.textContent = "No department data available.";
          statusEl.style.display = "flex";
        }
        return;
      }

      const labels = data.map((item) => item.department);
      const counts = data.map((item) => item.employee_count);

      if (wrapper) {
        wrapper.style.overflowY = "hidden";
        wrapper.style.overflowX = labels.length > 10 ? "auto" : "hidden";
      }

      if (canvasEl) {
        const parentWidth = canvasEl.parentElement?.clientWidth || 800;
        const minCanvasWidth =
          labels.length > 10 ? labels.length * 80 : parentWidth;
        const canvasWidth = Math.max(parentWidth, minCanvasWidth);

        canvasEl.width = canvasWidth;
        canvasEl.height = 320;
        canvasEl.style.width = `${canvasWidth}px`;
        canvasEl.style.height = "320px";
        canvasEl.style.maxWidth = "none";
        canvasEl.style.display = "block";
      }

      if (statusEl) {
        statusEl.style.display = "none";
      }

      const ctx = canvasEl?.getContext("2d");
      if (!ctx) {
        throw new Error("Department chart canvas not found");
      }

      const chartConfig = {
        type: "bar",
        data: {
          labels,
          datasets: [
            {
              label: "Employees",
              data: counts,
              backgroundColor: "#003686",
              borderRadius: 4,
              maxBarThickness: 32,
            },
          ],
        },
        options: {
          responsive: false,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              display: false,
            },
            tooltip: {
              callbacks: {
                label: (item) =>
                  `${item.parsed.y} employee${item.parsed.y === 1 ? "" : "s"}`,
              },
            },
          },
          scales: {
            x: {
              ticks: {
                autoSkip: false,
                maxRotation: 45,
                minRotation: 45,
                color: "#737784",
                font: {
                  size: 11,
                },
              },
              grid: {
                display: false,
              },
            },
            y: {
              beginAtZero: true,
              ticks: {
                precision: 0,
                color: "#737784",
              },
              grid: {
                color: "#eef0f5",
              },
            },
          },
        },
      };

      if (departmentChartInstance) {
        departmentChartInstance.data.labels = labels;
        departmentChartInstance.data.datasets[0].data = counts;
        departmentChartInstance.options = chartConfig.options;
        departmentChartInstance.update();
      } else {
        departmentChartInstance = new Chart(ctx, chartConfig);
      }
    } catch (error) {
      console.error("Unable to load department data:", error);
      if (statusEl) {
        statusEl.textContent = "Unable to load department data.";
        statusEl.style.display = "flex";
      }
    }
  }

  // END DEPARTMENT TREND CHART

  function renderGenderDonut(maleCount, femaleCount, otherCount = 0) {
    const total = maleCount + femaleCount + otherCount;
    const malePct = total > 0 ? Math.round((maleCount / total) * 100) : 0;

    if (total <= 0) {
      showGenderChartEmptyState();
      document.getElementById("gender-donut-total").textContent = "0";
      document.getElementById("gender-pct-male").textContent = "0%";
      document.getElementById("gender-pct-female").textContent = "0%";
      document.getElementById("gender-pct-other").textContent = "0%";
      return;
    }

    hideGenderChartEmptyState();
    const femalePct = total > 0 ? Math.round((femaleCount / total) * 100) : 0;
    const otherPct = total > 0 ? Math.max(0, 100 - malePct - femalePct) : 0;

    const maleArc = document.getElementById("gender-donut-arc");
    const femaleArc = document.getElementById("gender-donut-arc-female");
    const otherArc = document.getElementById("gender-donut-arc-other");
    const tooltip = document.getElementById("gender-chart-tooltip");

    const setArcState = (activeArc) => {
      [maleArc, femaleArc, otherArc].filter(Boolean).forEach((arc) => {
        const isActive = arc === activeArc;
        arc.classList.toggle("is-active", isActive);
        arc.classList.toggle("is-dimmed", !isActive && Boolean(activeArc));
      });
    };

    const showTooltip = (event, label, count, pct) => {
      if (!tooltip) return;
      const safePct = Number.isFinite(pct) ? pct : 0;
      const color =
        label === "Female"
          ? "#e879a0"
          : label === "Other"
            ? "#f59e0b"
            : "#00639d";
      tooltip.innerHTML = `
                <span class="dashboard-gender-tooltip-indicator" style="background:${color}"></span>
                <div>
                    <div class="dashboard-gender-tooltip-title">${label}</div>
                    <div class="dashboard-gender-tooltip-value">${count} employees</div>
                    <div class="dashboard-gender-tooltip-meta">${safePct}% of total</div>
                </div>`;

      const wrap = tooltip.parentElement;
      if (!wrap) return;
      const rect = wrap.getBoundingClientRect();
      const x = (event.clientX || 0) - rect.left;
      const y = (event.clientY || 0) - rect.top;
      tooltip.style.left = `${Math.min(Math.max(x, 70), rect.width - 70)}px`;
      tooltip.style.top = `${Math.max(Math.min(y - 12, rect.height - 40), 36)}px`;
      tooltip.classList.add("is-visible");
    };

    const hideTooltip = () => {
      if (tooltip) {
        tooltip.classList.remove("is-visible");
      }
      setArcState(null);
    };

    if (maleArc) {
      maleArc.setAttribute("stroke-dasharray", `${malePct} 100`);
      maleArc.setAttribute("stroke-dashoffset", "0");
      maleArc.setAttribute("data-gender", "Male");
      maleArc.setAttribute("data-count", maleCount);
      maleArc.setAttribute("data-percent", malePct);
      maleArc.onclick = () => navigateToEmployees({ gender: "Male" });
      maleArc.onmouseenter = (event) => {
        setArcState(maleArc);
        showTooltip(event, "Male", maleCount, malePct);
      };
      maleArc.onmousemove = (event) => {
        setArcState(maleArc);
        showTooltip(event, "Male", maleCount, malePct);
      };
      maleArc.onmouseleave = hideTooltip;
    }

    if (femaleArc) {
      femaleArc.setAttribute("stroke-dasharray", `${femalePct} 100`);
      femaleArc.setAttribute("stroke-dashoffset", `${-malePct}`);
      femaleArc.setAttribute("data-gender", "Female");
      femaleArc.setAttribute("data-count", femaleCount);
      femaleArc.setAttribute("data-percent", femalePct);
      femaleArc.onclick = () => navigateToEmployees({ gender: "Female" });
      femaleArc.onmouseenter = (event) => {
        setArcState(femaleArc);
        showTooltip(event, "Female", femaleCount, femalePct);
      };
      femaleArc.onmousemove = (event) => {
        setArcState(femaleArc);
        showTooltip(event, "Female", femaleCount, femalePct);
      };
      femaleArc.onmouseleave = hideTooltip;
    }

    if (otherArc) {
      otherArc.setAttribute("stroke-dasharray", `${otherPct} 100`);
      otherArc.setAttribute("stroke-dashoffset", `${-(malePct + femalePct)}`);
      otherArc.setAttribute("data-gender", "Other");
      otherArc.setAttribute("data-count", otherCount);
      otherArc.setAttribute("data-percent", otherPct);
      otherArc.onclick = () => navigateToEmployees({ gender: "Other" });
      otherArc.onmouseenter = (event) => {
        setArcState(otherArc);
        showTooltip(event, "Other", otherCount, otherPct);
      };
      otherArc.onmousemove = (event) => {
        setArcState(otherArc);
        showTooltip(event, "Other", otherCount, otherPct);
      };
      otherArc.onmouseleave = hideTooltip;
    }

    document
      .querySelectorAll(".dashboard-donut-legend-item")
      .forEach((item) => {
        item.onmouseenter = () => {
          const label = item
            .querySelector(".dashboard-donut-legend-label")
            ?.textContent?.trim();
          const arc =
            label === "Female"
              ? femaleArc
              : label === "Other"
                ? otherArc
                : maleArc;
          setArcState(arc);
          if (arc && tooltip) {
            showTooltip(
              { clientX: 0, clientY: 0 },
              label,
              Number(arc.getAttribute("data-count") || 0),
              Number(arc.getAttribute("data-percent") || 0),
            );
          }
        };
        item.onmouseleave = hideTooltip;
      });

    document.getElementById("gender-donut-total").textContent = total;
    document.getElementById("gender-pct-male").textContent = malePct + "%";
    document.getElementById("gender-pct-female").textContent = femalePct + "%";
    document.getElementById("gender-pct-other").textContent = otherPct + "%";
  }

  function loadCharts() {
    loadDepartmentChart();

    fetchJSON("api/employees.php").then((data) => {
      const employeeList = Array.isArray(data) ? data : [];
      const maleCount = employeeList.filter(
        (emp) => String(emp.gender || "").toLowerCase() === "male",
      ).length;
      const femaleCount = employeeList.filter(
        (emp) => String(emp.gender || "").toLowerCase() === "female",
      ).length;
      const otherCount = employeeList.filter((emp) => {
        const g = String(emp.gender || "").toLowerCase();
        return g !== "male" && g !== "female";
      }).length;
      renderGenderDonut(maleCount, femaleCount, otherCount);
    });
  }

  const loadTransactionCards = (type, elementId, badgeClass, emptyText) => {
    fetchJSON(
      "api/transactions/index.php/type/" +
        type +
        "?date_from=" +
        today +
        "&date_to=" +
        today,
    ).then((data) => {
      const el = document.getElementById(elementId);
      if (!Array.isArray(data) || data.length === 0) {
        el.innerHTML = `<div class="dashboard-loading-state">${emptyText}</div>`;
        return;
      }
      el.innerHTML = data
        .slice(0, 4)
        .map(
          (tx) => `
            <div class="tx-row">
                <div>
                    <p class="dashboard-activity-name">${tx.english_name ?? "-"}</p>
                    <p class="dashboard-activity-meta">${tx.employee_code ? tx.employee_code : "Employee"}</p>
                </div>
                <span class="dashboard-activity-pill ${badgeClass}">${type === "arrival" ? "Arriving" : "Departing"}</span>
            </div>`,
        )
        .join("");
    });
  };

  const loadDashboard = () => {
    loadDashboardSummary();
    loadRecentEmployees();
    loadCharts();
    loadTransactionCards(
      "arrival",
      "dashboard-arrivals",
      "badge-status-arriving",
      "No arrivals scheduled today.",
    );
    loadTransactionCards(
      "departure",
      "dashboard-departures",
      "badge-status-departing",
      "No departures scheduled today.",
    );
  };

  const refreshButton = document.getElementById("dashboardRefreshBtn");
  if (refreshButton) {
    refreshButton.addEventListener("click", loadDashboard);
  }

  loadDashboard();
  window.dashboardRefreshInterval = setInterval(loadDashboard, 30000);
}

document.addEventListener("DOMContentLoaded", initDashboard);

// Pie chart for gender

(function () {
  function parsePct(text) {
    const m = String(text || "").match(/([\d.]+)/);
    return m ? parseFloat(m[1]) : null;
  }

  function updateBar() {
    const maleEl = document.getElementById("gender-pct-male");
    const femaleEl = document.getElementById("gender-pct-female");
    const otherEl = document.getElementById("gender-pct-other");
    const barM = document.getElementById("gender-bar-male");
    const barF = document.getElementById("gender-bar-female");
    const barO = document.getElementById("gender-bar-other");
    const lblM = document.getElementById("gender-bar-label-male");
    const lblF = document.getElementById("gender-bar-label-female");
    const lblO = document.getElementById("gender-bar-label-other");

    if (!maleEl || !barM) return;

    const malePct = parsePct(maleEl.textContent);
    const femalePct = parsePct(femaleEl ? femaleEl.textContent : null);
    const otherPct = parsePct(otherEl ? otherEl.textContent : null);

    if (malePct === null) return;

    const fp = femalePct !== null ? femalePct : 0;
    const op = otherPct !== null ? otherPct : Math.max(0, 100 - malePct - fp);

    barM.style.width = malePct + "%";
    if (barF) barF.style.width = fp + "%";
    if (barO) barO.style.width = op + "%";
    if (lblM) lblM.textContent = "Male " + malePct + "%";
    if (lblF) lblF.textContent = "Female " + fp + "%";
    if (lblO) lblO.textContent = "Other " + op + "%";

    const femArc = document.getElementById("gender-donut-arc-female");
    if (femArc) {
      const offset = malePct;
      femArc.setAttribute("stroke-dasharray", fp + " " + (100 - fp));
      femArc.setAttribute("stroke-dashoffset", -offset);
    }

    const otherArc = document.getElementById("gender-donut-arc-other");
    if (otherArc) {
      const offset = malePct + fp;
      otherArc.setAttribute("stroke-dasharray", op + " " + (100 - op));
      otherArc.setAttribute("stroke-dashoffset", -offset);
    }
  }

  /* Run once on load in case dashboard.js already ran */
  document.addEventListener("DOMContentLoaded", function () {
    updateBar();

    /* Watch for dashboard.js updating the text nodes */
    const targets = [
      document.getElementById("gender-pct-male"),
      document.getElementById("gender-pct-female"),
      document.getElementById("gender-pct-other"),
      document.getElementById("gender-donut-total"),
    ].filter(Boolean);

    if (!targets.length) return;

    const obs = new MutationObserver(updateBar);
    targets.forEach(function (el) {
      obs.observe(el, { childList: true, characterData: true, subtree: true });
    });
  });
})();
