function initDashboard() {
  function getLocalDateString(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, "0");
    const day = String(date.getDate()).padStart(2, "0");
    return `${year}-${month}-${day}`;
  }

  const today = getLocalDateString(new Date());

  let departmentChartInstance = null;
  let departmentChartData = [];
  let departmentEmployeeCache = [];
  let genderEmployeeCache = [];
  let lunchboxEmployeeCache = [];
  let trafficEmployeeCache = [];
  let lunchboxChartPoints = [];
  let currentDepartmentSelection = null;
  let currentGenderSelection = null;
  let currentLunchBoxSelection = null;
  let currentTrafficSelection = null;
  let currentTrafficType = null;
  let dashboardLoading = false;

  function fetchJSON(url, options = {}) {
    return fetch(url, options)
      .then((response) => {
        if (!response.ok) {
          console.warn(`API Error [${response.status}]: ${url}`);
          return Promise.reject(response);
        }
        return response.json();
      })
      .catch((error) => {
        console.error(`Fetch failed for ${url}:`, error);
        return null;
      });
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
    return String(value ?? "").replace(
      /[&<>'"]/g,
      (character) =>
        ({
          "&": "&amp;",
          "<": "&lt;",
          ">": "&gt;",
          "'": "&#039;",
          '"': "&quot;",
        })[character],
    );
  }

  function normalizeGenderDisplay(value) {
    if (value === null || value === undefined || value === "") {
      return "-";
    }

    const raw = String(value).trim();
    if (!raw) return "-";

    const normalized = raw.toLowerCase();
    if (normalized === "male") return "Male";
    if (normalized === "female") return "Female";
    if (normalized === "other" || normalized === "others") return "Other";

    return raw;
  }

  function getRoomFieldValue(room, keys, fallback = null) {
    if (!room || typeof room !== "object") return fallback;

    for (const key of keys) {
      if (
        Object.prototype.hasOwnProperty.call(room, key) &&
        room[key] !== null &&
        room[key] !== undefined &&
        String(room[key]).trim() !== ""
      ) {
        return room[key];
      }
    }

    return fallback;
  }

  function normalizeRoomStatus(rawStatus) {
    const status = String(rawStatus ?? "").trim();
    return status || "Unknown";
  }

  function normalizeRoomCapacity(rawCapacity) {
    const value = rawCapacity ?? "";
    if (value === null || value === undefined || value === "") return "N/A";
    return value;
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
      <div class="room-status-hbar-row${isZero ? " is-zero" : ""}" data-room-status="${label}" role="button" tabindex="0" title="Click to view ${label.toLowerCase()} details">
        <div class="room-status-hbar-label" style="--rs-color:${color}"><i class="bi ${icon}"></i>${label}</div>
        <div class="room-status-hbar-track">
          <div class="room-status-hbar-fill" style="width:${pct}%; background:${color};"></div>
        </div>
        <div class="room-status-hbar-value">${value}<span class="pct">${pct}%</span></div>
      </div>`;
      })
      .join("");

    const roomTypes = Object.entries(summary.room_types || {})
      .map(([name, count]) => ({
        name: name.trim(),
        count: Number(count) || 0,
      }))
      .filter((type) => type.name);

    const chips = roomTypes.length
      ? roomTypes
          .map(
            (t) =>
              `<span class="room-status-chip" data-room-type="${escapeHtml(t.name)}" role="button" tabindex="0" title="${escapeHtml(t.name)}\n${t.count} rooms\nClick to view room details">
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

  // Room Status Drawer Functions
  let roomStatusDrawerCache = {};
  let roomStatusDrawerCurrentType = null;
  let roomStatusDrawerCurrentStatus = null;

  function openRoomStatusDrawer(status) {
    const drawer = document.getElementById("roomStatusDrawer");
    if (!drawer) return;

    roomStatusDrawerCurrentStatus = status;
    const title = document.getElementById("roomStatusDrawerTitle");
    if (title) title.textContent = `${status} Rooms`;
    showRoomStatusDrawerLoading();

    fetchJSON(`api/rooms/by_status.php?status=${encodeURIComponent(status)}`)
      .then((data) => {
        if (!data || !data.success) {
          showRoomStatusDrawerError("Failed to load " + status.toLowerCase() + " details.");
          return;
        }

        roomStatusDrawerCache = data;
        roomStatusDrawerCurrentType = data.type;

        drawer.classList.add("drawer--open");
        document.body.style.overflow = "hidden";

        const countBadge = document.getElementById("roomStatusDrawerCount");
        if (countBadge) countBadge.textContent = data.count;

        const subtitle = document.getElementById("roomStatusDrawerSubtitle");
        if (subtitle) {
          if (data.type === "employees") {
            subtitle.textContent = `${data.count} ${data.count === 1 ? "Employee" : "Employees"}`;
          } else {
            subtitle.textContent = `${data.count} ${data.count === 1 ? "Room" : "Rooms"}`;
          }
        }

        renderRoomStatusDrawerTable(data.records, data.type, data.status, data.summary || null);

        const searchInput = document.getElementById("roomStatusDrawerSearch");
        if (searchInput) {
          searchInput.placeholder = data.type === "employees" ? "Search employees..." : "Search rooms...";
          searchInput.value = "";
        }
      })
      .catch((error) => {
        showRoomStatusDrawerError("Unable to load room details. Please try again.");
        console.error("Room status drawer error:", error);
      });
  }

  function openRoomTypeDrawer(roomType) {
    const drawer = document.getElementById("roomStatusDrawer");
    if (!drawer) return;

    roomStatusDrawerCurrentStatus = roomType;
    const title = document.getElementById("roomStatusDrawerTitle");
    if (title) title.textContent = `${roomType} Rooms`;
    showRoomStatusDrawerLoading();

    fetchJSON(`api/rooms/by_type.php?room_type=${encodeURIComponent(roomType)}`)
      .then((data) => {
        if (!data || !data.success) {
          showRoomStatusDrawerError("Unable to load room details. Please try again.");
          return;
        }

        roomStatusDrawerCache = data;
        roomStatusDrawerCurrentType = "room_type";

        drawer.classList.add("drawer--open");
        document.body.style.overflow = "hidden";

        const countBadge = document.getElementById("roomStatusDrawerCount");
        if (countBadge) countBadge.textContent = data.count;

        const subtitle = document.getElementById("roomStatusDrawerSubtitle");
        if (subtitle) {
          subtitle.textContent = `${data.count} ${data.count === 1 ? "Room" : "Rooms"}`;
        }

        renderRoomStatusDrawerTable(data.records, "rooms", roomType, data.summary || null);

        const searchInput = document.getElementById("roomStatusDrawerSearch");
        if (searchInput) {
          searchInput.placeholder = "Search rooms...";
          searchInput.value = "";
        }
      })
      .catch((error) => {
        showRoomStatusDrawerError("Unable to load room details. Please try again.");
        console.error("Room type drawer error:", error);
      });
  }

  function showRoomStatusDrawerLoading() {
    const drawer = document.getElementById("roomStatusDrawer");
    if (!drawer) return;

    const contentDiv = document.getElementById("roomStatusDrawerContent");
    const emptyDiv = document.getElementById("roomStatusDrawerEmpty");
    const errorDiv = document.getElementById("roomStatusDrawerError");

    contentDiv.innerHTML = `
      <div class="drawer-loading-state">
        <div class="drawer-spinner"></div>
        <p>Loading details...</p>
      </div>
    `;
    contentDiv.style.display = "block";
    emptyDiv.style.display = "none";
    errorDiv.style.display = "none";
  }

  function showRoomStatusDrawerError(message) {
    const contentDiv = document.getElementById("roomStatusDrawerContent");
    const emptyDiv = document.getElementById("roomStatusDrawerEmpty");
    const errorDiv = document.getElementById("roomStatusDrawerError");
    const errorText = document.getElementById("roomStatusDrawerErrorText");

    contentDiv.style.display = "none";
    emptyDiv.style.display = "none";
    errorDiv.style.display = "block";
    errorText.textContent = message;
  }

  function renderRoomStatusDrawerTable(records, type, label = null, summary = null) {
    const contentDiv = document.getElementById("roomStatusDrawerContent");
    const emptyDiv = document.getElementById("roomStatusDrawerEmpty");

    if (!records || records.length === 0) {
      const emptyText = document.getElementById("roomStatusDrawerEmptyText");
      if (emptyText) {
        emptyText.textContent = label
          ? `No rooms found for this room type.`
          : "No records found for this room status.";
      }
      contentDiv.style.display = "none";
      emptyDiv.style.display = "block";
      return;
    }

    contentDiv.style.display = "block";
    emptyDiv.style.display = "none";

    if (type === "employees") {
      renderRoomStatusEmployeeTable(records);
    } else {
      renderRoomStatusRoomTable(records, summary, label);
    }
  }

  function renderRoomStatusEmployeeTable(employees) {
    const contentDiv = document.getElementById("roomStatusDrawerContent");

    const table = document.createElement("table");
    table.className = "drawer-table";

    const thead = document.createElement("thead");
    const headerRow = document.createElement("tr");
    headerRow.innerHTML = `
      <th>Name</th>
      <th>Department</th>
      <th>Location</th>
      <th>Room</th>
    `;
    thead.appendChild(headerRow);
    table.appendChild(thead);

    const tbody = document.createElement("tbody");
    employees.forEach((emp) => {
      const row = document.createElement("tr");
      row.className = "drawer-table-row";
      row.innerHTML = `
        <td class="employee-name">${escapeHtml(emp.english_name || emp.chinese_name || "N/A")}</td>
        <td>${escapeHtml(emp.department_name || "N/A")}</td>
        <td>${escapeHtml(emp.location || "N/A")}</td>
        <td class="room-number">${escapeHtml(emp.room_no || "N/A")}</td>
      `;
      tbody.appendChild(row);
    });
    table.appendChild(tbody);

    contentDiv.innerHTML = "";
    contentDiv.appendChild(table);
  }

  function renderRoomStatusRoomTable(rooms, summary = null, label = null) {
    const contentDiv = document.getElementById("roomStatusDrawerContent");
    contentDiv.innerHTML = "";

    if (summary) {
      const summaryBox = document.createElement("div");
      summaryBox.className = "room-type-summary";
      summaryBox.innerHTML = `
        <div class="room-type-summary-row">
          <span>Total Rooms</span>
          <strong>${summary.total_rooms ?? 0}</strong>
        </div>
        <div class="room-type-summary-row">
          <span>Occupied</span>
          <strong>${summary.occupied ?? 0}</strong>
        </div>
        <div class="room-type-summary-row">
          <span>Available</span>
          <strong>${summary.available ?? 0}</strong>
        </div>
        <div class="room-type-summary-row">
          <span>Maintenance</span>
          <strong>${summary.maintenance ?? 0}</strong>
        </div>
      `;
      contentDiv.appendChild(summaryBox);
    }

    const table = document.createElement("table");
    table.className = "drawer-table";

    const thead = document.createElement("thead");
    const headerRow = document.createElement("tr");
    headerRow.innerHTML = `
      <th>Room</th>
      <th>Building</th>
      <th>Floor</th>
      <th>Status</th>
      <th>Capacity</th>
      <th>Occupied</th>
    `;
    thead.appendChild(headerRow);
    table.appendChild(thead);

    const tbody = document.createElement("tbody");
    rooms.forEach((room) => {
      const roomStatus = normalizeRoomStatus(
        getRoomFieldValue(room, ["status", "room_status", "roomStatus", "room_status_name"], "Unknown"),
      );
      const roomNumber = getRoomFieldValue(room, ["room_no", "room_no", "roomNumber", "room_number", "roomNo"], "N/A");
      const buildingName = getRoomFieldValue(room, ["building_name", "buildingName", "building", "building_name_text"], "N/A");
      const floorName = getRoomFieldValue(room, ["floor_name", "floorName", "floor", "floor_name_text"], "N/A");
      const capacity = normalizeRoomCapacity(
        getRoomFieldValue(room, ["capacity", "room_capacity", "roomCapacity", "room_capacity_value"], null),
      );
      const occupied = getRoomFieldValue(room, ["current_occupancy", "currentOccupancy", "occupied_count", "occupiedCount"], 0);
      const badgeClass = roomStatus.toLowerCase().replace(/\s+/g, "-");
      const row = document.createElement("tr");
      row.className = "drawer-table-row";
      row.innerHTML = `
        <td class="room-number">${escapeHtml(roomNumber)}</td>
        <td>${escapeHtml(buildingName)}</td>
        <td>${escapeHtml(floorName)}</td>
        <td><span class="status-badge status-badge--${badgeClass}">${escapeHtml(roomStatus)}</span></td>
        <td>${escapeHtml(capacity)}</td>
        <td>${escapeHtml(occupied)}</td>
      `;
      tbody.appendChild(row);
    });
    table.appendChild(tbody);

    contentDiv.appendChild(table);
  }

  function closeRoomStatusDrawer() {
    const drawer = document.getElementById("roomStatusDrawer");
    if (!drawer) return;

    drawer.classList.remove("drawer--open");
    document.body.style.overflow = "";

    roomStatusDrawerCache = {};
    roomStatusDrawerCurrentType = null;
    roomStatusDrawerCurrentStatus = null;
  }

  function filterRoomStatusDrawerTable(searchTerm) {
    const rows = document.querySelectorAll(".drawer-table-row");
    const term = searchTerm.toLowerCase().trim();

    rows.forEach((row) => {
      const text = row.textContent.toLowerCase();
      row.style.display = text.includes(term) ? "" : "none";
    });
  }

  function bindRoomStatusDrawerControls() {
    // Close button
    const closeBtn = document.getElementById("roomStatusDrawerClose");
    if (closeBtn) {
      closeBtn.addEventListener("click", closeRoomStatusDrawer);
    }

    // Backdrop click
    const backdrop = document.getElementById("roomStatusDrawerBackdrop");
    if (backdrop) {
      backdrop.addEventListener("click", closeRoomStatusDrawer);
    }

    // Search input
    const searchInput = document.getElementById("roomStatusDrawerSearch");
    const searchClear = document.getElementById("roomStatusDrawerSearchClear");
    if (searchInput) {
      searchInput.addEventListener("input", (e) => {
        const value = e.target.value;
        searchClear.style.display = value ? "block" : "none";
        filterRoomStatusDrawerTable(value);
      });
    }

    if (searchClear) {
      searchClear.addEventListener("click", () => {
        searchInput.value = "";
        searchClear.style.display = "none";
        filterRoomStatusDrawerTable("");
      });
    }

    // Retry button
    const retryBtn = document.getElementById("roomStatusDrawerRetry");
    if (retryBtn) {
      retryBtn.addEventListener("click", () => {
        if (roomStatusDrawerCurrentStatus) {
          openRoomStatusDrawer(roomStatusDrawerCurrentStatus);
        }
      });
    }

    // Room status rows click handlers
    document.addEventListener("click", (e) => {
      const roomTypeChip = e.target.closest("[data-room-type]");
      if (roomTypeChip) {
        const roomType = roomTypeChip.getAttribute("data-room-type");
        if (roomType) openRoomTypeDrawer(roomType);
        return;
      }

      const row = e.target.closest("[data-room-status]");
      if (row && !row.classList.contains("is-zero")) {
        const status = row.getAttribute("data-room-status");
        if (status) {
          openRoomStatusDrawer(status);
        }
      }
    });

    document.addEventListener("keydown", (e) => {
      const target = e.target.closest("[data-room-type]");
      if (target && (e.key === "Enter" || e.key === " ")) {
        e.preventDefault();
        const roomType = target.getAttribute("data-room-type");
        if (roomType) openRoomTypeDrawer(roomType);
        return;
      }

      if (e.key === "Escape") {
        closeRoomStatusDrawer();
      }
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

  function renderDepartmentEmployeeTable(rows) {
    const contentEl = document.getElementById("departmentEmployeeContent");
    if (!contentEl) return;

    const searchValue = (
      document.getElementById("departmentEmployeeSearch")?.value || ""
    )
      .trim()
      .toLowerCase();
    const filtered = Array.isArray(rows)
      ? rows.filter((employee) => {
          if (!searchValue) return true;
          const haystack = [
            employee.english_name,
            employee.chinese_name,
            employee.employee_code,
            employee.department_name,
            employee.gender,
            employee.status,
          ]
            .filter(Boolean)
            .join(" ")
            .toLowerCase();
          return haystack.includes(searchValue);
        })
      : [];

    if (filtered.length === 0) {
      contentEl.innerHTML =
        '<div class="department-employee-empty">No employees found</div>';
      return;
    }

    const rowsHtml = filtered
      .map((employee) => {
        const name =
          employee.english_name ||
          employee.chinese_name ||
          employee.employee_code ||
          "Unknown";
        const status = String(employee.status || "Inactive");
        const statusClass = status === "Active" ? "" : "inactive";
        const departmentName =
          employee.department_name ||
          currentDepartmentSelection?.department ||
          "-";
        const employeeCode = employee.employee_code || "-";
        const gender = normalizeGenderDisplay(employee.gender);

        return `
          <tr>
            <td class="department-employee-name">${escapeHtml(name)}</td>
            <td>${escapeHtml(employeeCode)}</td>
            <td>${escapeHtml(departmentName)}</td>
            <td>${escapeHtml(gender)}</td>
            <td><span class="department-employee-status ${statusClass}">${escapeHtml(status)}</span></td>
          </tr>
        `;
      })
      .join("");

    contentEl.innerHTML = `
      <div class="department-employee-table-wrap">
        <table class="department-employee-table">
          <thead>
            <tr>
              <th>Employee Name</th>
              <th>Employee ID</th>
              <th>Department</th>
              <th>Gender</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            ${rowsHtml}
          </tbody>
        </table>
      </div>
    `;
  }

  function renderGenderEmployeeTable(rows) {
    const contentEl = document.getElementById("departmentEmployeeContent");
    if (!contentEl) return;

    const searchValue = (
      document.getElementById("departmentEmployeeSearch")?.value || ""
    )
      .trim()
      .toLowerCase();
    const filtered = Array.isArray(rows)
      ? rows.filter((employee) => {
          if (!searchValue) return true;
          const haystack = [
            employee.english_name,
            employee.chinese_name,
            employee.employee_code,
            employee.department_name,
            employee.gender,
            employee.status,
          ]
            .filter(Boolean)
            .join(" ")
            .toLowerCase();
          return haystack.includes(searchValue);
        })
      : [];

    if (filtered.length === 0) {
      contentEl.innerHTML =
        '<div class="department-employee-empty">No employees found</div>';
      return;
    }

    const rowsHtml = filtered
      .map((employee) => {
        const name =
          employee.english_name ||
          employee.chinese_name ||
          employee.employee_code ||
          "Unknown";
        const status = String(employee.status || "Inactive");
        const statusClass = status === "Active" ? "" : "inactive";
        const departmentName = employee.department_name || "-";
        const employeeCode = employee.employee_code || "-";
        const gender = normalizeGenderDisplay(employee.gender || currentGenderSelection);

        return `
          <tr>
            <td class="department-employee-name">${escapeHtml(name)}</td>
            <td>${escapeHtml(employeeCode)}</td>
            <td>${escapeHtml(departmentName)}</td>
            <td>${escapeHtml(gender)}</td>
            <td><span class="department-employee-status ${statusClass}">${escapeHtml(status)}</span></td>
          </tr>
        `;
      })
      .join("");

    contentEl.innerHTML = `
      <div class="department-employee-table-wrap">
        <table class="department-employee-table">
          <thead>
            <tr>
              <th>Employee Name</th>
              <th>Employee ID</th>
              <th>Department</th>
              <th>Gender</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            ${rowsHtml}
          </tbody>
        </table>
      </div>
    `;
  }

  function renderDepartmentEmployeeError(message) {
    const contentEl = document.getElementById("departmentEmployeeContent");
    if (!contentEl) return;
    contentEl.innerHTML = `
      <div class="department-employee-error">
        <div>
          <div>${message}</div>
          <button type="button" class="department-employee-retry" data-retry-department="true">Retry</button>
        </div>
      </div>
    `;

    const retryButton = contentEl.querySelector("[data-retry-department]");
    if (retryButton && currentDepartmentSelection) {
      retryButton.addEventListener("click", () => {
        fetchDepartmentEmployees(currentDepartmentSelection.id);
      });
    }
  }

  function renderLunchboxEmployeeTable(rows) {
    const contentEl = document.getElementById("departmentEmployeeContent");
    if (!contentEl) return;

    const searchValue = (
      document.getElementById("departmentEmployeeSearch")?.value || ""
    )
      .trim()
      .toLowerCase();
    const filtered = Array.isArray(rows)
      ? rows.filter((employee) => {
          if (!searchValue) return true;
          const haystack = [
            employee.english_name,
            employee.chinese_name,
            employee.employee_code,
            employee.department_name,
            employee.gender,
            employee.status,
          ]
            .filter(Boolean)
            .join(" ")
            .toLowerCase();
          return haystack.includes(searchValue);
        })
      : [];

    if (filtered.length === 0) {
      contentEl.innerHTML =
        '<div class="department-employee-empty">No lunch box employees for this date.</div>';
      return;
    }

    const rowsHtml = filtered
      .map((employee) => {
        const name =
          employee.english_name ||
          employee.chinese_name ||
          employee.employee_code ||
          "Unknown";
        const status = String(employee.status || "Inactive");
        const statusClass = status === "Active" ? "" : "inactive";
        const departmentName = employee.department_name || "-";
        const employeeCode = employee.employee_code || "-";
        const gender = normalizeGenderDisplay(employee.gender);

        return `
          <tr>
            <td class="department-employee-name">${escapeHtml(name)}</td>
            <td>${escapeHtml(employeeCode)}</td>
            <td>${escapeHtml(departmentName)}</td>
            <td>${escapeHtml(gender)}</td>
            <td><span class="department-employee-status ${statusClass}">${escapeHtml(status)}</span></td>
          </tr>
        `;
      })
      .join("");

    contentEl.innerHTML = `
      <div class="department-employee-table-wrap">
        <table class="department-employee-table">
          <thead>
            <tr>
              <th>Employee Name</th>
              <th>Employee ID</th>
              <th>Department</th>
              <th>Gender</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            ${rowsHtml}
          </tbody>
        </table>
      </div>
    `;
  }

  function renderGenderEmployeeError(message) {
    const contentEl = document.getElementById("departmentEmployeeContent");
    if (!contentEl) return;
    contentEl.innerHTML = `
      <div class="department-employee-error">
        <div>
          <div>${message}</div>
          <button type="button" class="department-employee-retry" data-retry-gender="true">Retry</button>
        </div>
      </div>
    `;

    const retryButton = contentEl.querySelector("[data-retry-gender]");
    if (retryButton && currentGenderSelection) {
      retryButton.addEventListener("click", () => {
        fetchGenderEmployees(currentGenderSelection);
      });
    }
  }

  function renderLunchboxEmployeeError(message) {
    const contentEl = document.getElementById("departmentEmployeeContent");
    if (!contentEl) return;
    contentEl.innerHTML = `
      <div class="department-employee-error">
        <div>
          <div>${message}</div>
          <button type="button" class="department-employee-retry" data-retry-lunchbox="true">Retry</button>
        </div>
      </div>
    `;

    const retryButton = contentEl.querySelector("[data-retry-lunchbox]");
    if (retryButton && currentLunchBoxSelection) {
      retryButton.addEventListener("click", () => {
        fetchLunchboxEmployees(currentLunchBoxSelection);
      });
    }
  }

  function renderTrafficEmployeeTable(rows) {
    const contentEl = document.getElementById("departmentEmployeeContent");
    if (!contentEl) return;

    const searchValue = (
      document.getElementById("departmentEmployeeSearch")?.value || ""
    )
      .trim()
      .toLowerCase();

    const filtered = Array.isArray(rows)
      ? rows.filter((employee) => {
          if (!searchValue) return true;
          const haystack = [
            employee.english_name,
            employee.chinese_name,
            employee.employee_code,
            employee.department_name,
            employee.gender,
            employee.status,
          ]
            .filter(Boolean)
            .join(" ")
            .toLowerCase();
          return haystack.includes(searchValue);
        })
      : [];

    if (filtered.length === 0) {
      const emptyLabel = currentTrafficType === "arrival" ? "No arrivals for this date." : "No departures for this date.";
      contentEl.innerHTML = `<div class="department-employee-empty">${emptyLabel}</div>`;
      return;
    }

    const rowsHtml = filtered
      .map((employee) => {
        const name =
          employee.english_name ||
          employee.chinese_name ||
          employee.employee_code ||
          "Unknown";
        const status = String(employee.status || "Inactive");
        const statusClass = status === "Active" ? "" : "inactive";
        const departmentName = employee.department_name || "-";
        const employeeCode = employee.employee_code || "-";
        const gender = normalizeGenderDisplay(employee.gender);

        return `
          <tr>
            <td class="department-employee-name">${escapeHtml(name)}</td>
            <td>${escapeHtml(employeeCode)}</td>
            <td>${escapeHtml(departmentName)}</td>
            <td>${escapeHtml(gender)}</td>
            <td><span class="department-employee-status ${statusClass}">${escapeHtml(status)}</span></td>
          </tr>
        `;
      })
      .join("");

    contentEl.innerHTML = `
      <div class="department-employee-table-wrap">
        <table class="department-employee-table">
          <thead>
            <tr>
              <th>Employee Name</th>
              <th>Employee ID</th>
              <th>Department</th>
              <th>Gender</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            ${rowsHtml}
          </tbody>
        </table>
      </div>
    `;
  }

  function renderTrafficEmployeeError(message) {
    const contentEl = document.getElementById("departmentEmployeeContent");
    if (!contentEl) return;
    contentEl.innerHTML = `
      <div class="department-employee-error">
        <div>
          <div>${message}</div>
          <button type="button" class="department-employee-retry" data-retry-traffic="true">Retry</button>
        </div>
      </div>
    `;

    const retryButton = contentEl.querySelector("[data-retry-traffic]");
    if (retryButton && currentTrafficSelection && currentTrafficType) {
      retryButton.addEventListener("click", () => {
        fetchTrafficEmployees(currentTrafficSelection, currentTrafficType);
      });
    }
  }

  function fetchTrafficEmployees(dateString, type) {
    const contentEl = document.getElementById("departmentEmployeeContent");
    const metaEl = document.getElementById("departmentEmployeeMeta");
    if (!contentEl || !metaEl) return;

    const movementType = type === "departure" ? "departure" : "arrival";
    const queryDate = dateString || currentTrafficSelection;
    if (!queryDate || !movementType) {
      renderTrafficEmployeeError(
        `Unable to load ${movementType} employee details.\nPlease try again.`,
      );
      return;
    }

    contentEl.innerHTML =
      `<div class="department-employee-loading">Loading ${movementType} employees...</div>`;

    fetchJSON(
      `api/transactions/index.php/type/${encodeURIComponent(movementType)}?date_from=${encodeURIComponent(queryDate)}&date_to=${encodeURIComponent(queryDate)}`,
    )
      .then((data) => {
        if (!Array.isArray(data)) {
          throw new Error(`Invalid ${movementType} employee response`);
        }

        const rows = data
          .map((entry) => ({
            ...entry,
            id: entry.employee_id ?? entry.id ?? null,
            employee_code: entry.employee_code ?? "-",
            english_name: entry.english_name ?? "-",
            chinese_name: entry.chinese_name ?? "",
            gender: normalizeGenderDisplay(entry.gender),
            status: entry.status || "Active",
            department_name: entry.department_name || "-",
          }))
          .filter((entry) => {
            const transactionDate = entry.transaction_date || entry.date || entry.transactionDate;
            return transactionDate === queryDate;
          });

        trafficEmployeeCache = rows;
        const count = rows.length;
        metaEl.textContent = `${count} ${count === 1 ? "employee" : "employees"}`;
        renderTrafficEmployeeTable(rows);
      })
      .catch((error) => {
        console.error(`${movementType} employee fetch failed:`, error);
        renderTrafficEmployeeError(
          `Unable to load ${movementType} employee details.\nPlease try again.`,
        );
      });
  }

  function openTrafficEmployeeDrawer(dateString, type) {
    const drawer = document.getElementById("departmentEmployeeDrawer");
    const titleEl = document.getElementById("departmentEmployeeTitle");
    const metaEl = document.getElementById("departmentEmployeeMeta");
    const kickerEl = document.getElementById("departmentEmployeeKicker");
    const searchInput = document.getElementById("departmentEmployeeSearch");
    const contentEl = document.getElementById("departmentEmployeeContent");

    if (!drawer || !titleEl || !metaEl || !searchInput || !contentEl || !kickerEl) {
      return;
    }

    currentDepartmentSelection = null;
    currentGenderSelection = null;
    currentLunchBoxSelection = null;
    const normalizedType = type === "departure" ? "departure" : "arrival";
    currentTrafficSelection = dateString || currentTrafficSelection;
    currentTrafficType = normalizedType;
    const rawDate = currentTrafficSelection;
    if (!rawDate) {
      return;
    }

    const dateLabel = new Date(`${rawDate}T00:00:00`);
    const labels = [
      "Sunday",
      "Monday",
      "Tuesday",
      "Wednesday",
      "Thursday",
      "Friday",
      "Saturday",
    ];
    const dayName = labels[dateLabel.getDay()];
    const typeLabel = currentTrafficType === "departure" ? "DEPARTURES" : "ARRIVALS";

    kickerEl.textContent = typeLabel;
    titleEl.textContent = dayName;
    metaEl.textContent = "0 employees";
    searchInput.value = "";
    drawer.classList.add("is-open");
    drawer.setAttribute("aria-hidden", "false");
    contentEl.innerHTML =
      `<div class="department-employee-loading">Loading ${currentTrafficType} employees...</div>`;

    fetchTrafficEmployees(rawDate, currentTrafficType);
  }

  function fetchDepartmentEmployees(departmentId) {
    const contentEl = document.getElementById("departmentEmployeeContent");
    if (!contentEl) return;
    contentEl.innerHTML =
      '<div class="department-employee-loading">Loading employees...</div>';

    fetchJSON(
      `api/employees.php?department_id=${encodeURIComponent(departmentId)}`,
    )
      .then((data) => {
        if (!Array.isArray(data)) {
          throw new Error("Invalid employee response");
        }

        departmentEmployeeCache = data;
        renderDepartmentEmployeeTable(data);
      })
      .catch((error) => {
        console.error("Department employee fetch failed:", error);
        renderDepartmentEmployeeError(
          "Unable to load employee details.\nPlease try again.",
        );
      });
  }

  function fetchGenderEmployees(genderLabel) {
    const contentEl = document.getElementById("departmentEmployeeContent");
    const metaEl = document.getElementById("departmentEmployeeMeta");
    if (!contentEl || !metaEl) return;
    contentEl.innerHTML =
      '<div class="department-employee-loading">Loading employees...</div>';

    const queryGender = genderLabel === "Others" ? "Other" : genderLabel;
    fetchJSON(`api/employees.php?gender=${encodeURIComponent(queryGender)}`)
      .then((data) => {
        if (!Array.isArray(data)) {
          throw new Error("Invalid employee response");
        }

        genderEmployeeCache = data;
        const count = data.length;
        metaEl.textContent = `${count} ${count === 1 ? "employee" : "employees"}`;
        renderGenderEmployeeTable(data);
      })
      .catch((error) => {
        console.error("Gender employee fetch failed:", error);
        renderGenderEmployeeError(
          "Unable to load employee details.\nPlease try again.",
        );
      });
  }

  function fetchLunchboxEmployees(dateString) {
    const contentEl = document.getElementById("departmentEmployeeContent");
    const metaEl = document.getElementById("departmentEmployeeMeta");
    if (!contentEl || !metaEl) return;
    contentEl.innerHTML =
      '<div class="department-employee-loading">Loading lunch box employees...</div>';

    const queryDate = dateString || currentLunchBoxSelection;
    if (!queryDate) {
      renderLunchboxEmployeeError(
        "Unable to load lunch box employee details.\nPlease try again.",
      );
      return;
    }

    fetchJSON(
      `api/meals/index.php?mode=lunchbox_employees&date=${encodeURIComponent(queryDate)}`,
    )
      .then((data) => {
        if (!Array.isArray(data)) {
          throw new Error("Invalid lunch box employee response");
        }

        lunchboxEmployeeCache = data;
        const count = data.length;
        const expectedCount = Number(
          lunchboxChartPoints.find((point) => point.date === queryDate)?.count || 0,
        );

        if (expectedCount > 0 && count !== expectedCount) {
          console.warn(
            `Lunch box count mismatch for ${queryDate}: chart reported ${expectedCount}, drawer found ${count}.`,
          );
        }

        metaEl.textContent = `${count} ${count === 1 ? "employee" : "employees"}`;
        renderLunchboxEmployeeTable(data);
      })
      .catch((error) => {
        console.error("Lunch box employee fetch failed:", error);
        renderLunchboxEmployeeError(
          "Unable to load lunch box employees for this date.\nPlease try again.",
        );
      });
  }

  function openDepartmentEmployeeDrawer(department) {
    const drawer = document.getElementById("departmentEmployeeDrawer");
    const titleEl = document.getElementById("departmentEmployeeTitle");
    const metaEl = document.getElementById("departmentEmployeeMeta");
    const kickerEl = document.getElementById("departmentEmployeeKicker");
    const searchInput = document.getElementById("departmentEmployeeSearch");
    const contentEl = document.getElementById("departmentEmployeeContent");

    if (
      !drawer ||
      !titleEl ||
      !metaEl ||
      !searchInput ||
      !contentEl ||
      !kickerEl
    ) {
      return;
    }

    currentGenderSelection = null;
    currentLunchBoxSelection = null;
    currentDepartmentSelection = department || currentDepartmentSelection;
    const deptName = currentDepartmentSelection?.department || "Department";
    const total = Number(currentDepartmentSelection?.employee_count || 0);

    kickerEl.textContent = "Department";
    titleEl.textContent = deptName;
    metaEl.textContent = `${total} employees`;
    searchInput.value = "";
    drawer.classList.add("is-open");
    drawer.setAttribute("aria-hidden", "false");
    contentEl.innerHTML =
      '<div class="department-employee-loading">Loading employees...</div>';

    fetchDepartmentEmployees(currentDepartmentSelection?.id || department?.id);
  }

  function openLunchBoxEmployeeDrawer(dateString) {
    const drawer = document.getElementById("departmentEmployeeDrawer");
    const titleEl = document.getElementById("departmentEmployeeTitle");
    const metaEl = document.getElementById("departmentEmployeeMeta");
    const kickerEl = document.getElementById("departmentEmployeeKicker");
    const searchInput = document.getElementById("departmentEmployeeSearch");
    const contentEl = document.getElementById("departmentEmployeeContent");

    if (!drawer || !titleEl || !metaEl || !searchInput || !contentEl || !kickerEl) {
      return;
    }

    currentDepartmentSelection = null;
    currentGenderSelection = null;
    currentLunchBoxSelection = dateString || currentLunchBoxSelection;
    const rawDate = currentLunchBoxSelection;
    if (!rawDate) {
      return;
    }

    const dateLabel = new Date(`${rawDate}T00:00:00`);
    const labels = [
      "Sunday",
      "Monday",
      "Tuesday",
      "Wednesday",
      "Thursday",
      "Friday",
      "Saturday",
    ];
    const dayName = labels[dateLabel.getDay()];

    kickerEl.textContent = "Lunch Box";
    titleEl.textContent = `${dayName}`;
    metaEl.textContent = "0 employees";
    searchInput.value = "";
    drawer.classList.add("is-open");
    drawer.setAttribute("aria-hidden", "false");
    contentEl.innerHTML =
      '<div class="department-employee-loading">Loading lunch box employees...</div>';

    fetchLunchboxEmployees(rawDate);
  }

  function openGenderEmployeeDrawer(genderLabel) {
    const drawer = document.getElementById("departmentEmployeeDrawer");
    const titleEl = document.getElementById("departmentEmployeeTitle");
    const metaEl = document.getElementById("departmentEmployeeMeta");
    const kickerEl = document.getElementById("departmentEmployeeKicker");
    const searchInput = document.getElementById("departmentEmployeeSearch");
    const contentEl = document.getElementById("departmentEmployeeContent");

    if (
      !drawer ||
      !titleEl ||
      !metaEl ||
      !searchInput ||
      !contentEl ||
      !kickerEl
    ) {
      return;
    }

    currentDepartmentSelection = null;
    currentGenderSelection = genderLabel || currentGenderSelection || "Male";
    const safeGender =
      currentGenderSelection === "Others" ? "Others" : currentGenderSelection;
    const total = Array.isArray(genderEmployeeCache)
      ? genderEmployeeCache.length
      : 0;

    kickerEl.textContent = "Gender";
    titleEl.textContent = safeGender;
    metaEl.textContent = `${total} ${total === 1 ? "employee" : "employees"}`;
    searchInput.value = "";
    drawer.classList.add("is-open");
    drawer.setAttribute("aria-hidden", "false");
    contentEl.innerHTML =
      '<div class="department-employee-loading">Loading employees...</div>';

    fetchGenderEmployees(safeGender);
  }

  function closeDepartmentEmployeeDrawer() {
    const drawer = document.getElementById("departmentEmployeeDrawer");
    if (!drawer) return;
    drawer.classList.remove("is-open");
    drawer.setAttribute("aria-hidden", "true");
    const searchInput = document.getElementById("departmentEmployeeSearch");
    if (searchInput) searchInput.value = "";
    currentDepartmentSelection = null;
    currentGenderSelection = null;
    currentLunchBoxSelection = null;
    currentTrafficSelection = null;
    currentTrafficType = null;
    departmentEmployeeCache = [];
    genderEmployeeCache = [];
    lunchboxEmployeeCache = [];
    trafficEmployeeCache = [];
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
        id: Number(item.id ?? 0) || null,
        department: String(item.department ?? "").trim(),
        employee_count: Number(item.employee_count || 0),
      }));

      departmentChartData = data;

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
          animation: {
            duration: 250,
          },
          plugins: {
            legend: {
              display: false,
            },
            tooltip: {
              callbacks: {
                title: (items) => {
                  const idx = items[0]?.dataIndex ?? 0;
                  return (
                    departmentChartData[idx]?.department ||
                    items[0]?.label ||
                    "Department"
                  );
                },
                label: (item) =>
                  `${item.parsed.y} employee${item.parsed.y === 1 ? "" : "s"}`,
              },
            },
          },
          onHover: (event, elements) => {
            const canvas = event?.native?.target || event?.target;
            if (canvas) {
              canvas.style.cursor = elements.length ? "pointer" : "default";
            }
          },
          onClick: (event, elements) => {
            if (!elements.length) return;
            const index = elements[0].index;
            const selected = departmentChartData[index];
            if (selected && selected.id) {
              openDepartmentEmployeeDrawer(selected);
            }
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

  function bindDepartmentDrawerControls() {
    const drawer = document.getElementById("departmentEmployeeDrawer");
    if (!drawer) return;

    drawer.onclick = (event) => {
      const target = event.target;
      if (!(target instanceof HTMLElement)) return;

      const closeTrigger = target.closest("[data-close-drawer='true']");
      if (closeTrigger) {
        event.preventDefault();
        event.stopPropagation();
        closeDepartmentEmployeeDrawer();
      }
    };

    const searchInput = document.getElementById("departmentEmployeeSearch");
    searchInput?.addEventListener("input", () => {
      if (currentTrafficSelection) {
        renderTrafficEmployeeTable(trafficEmployeeCache);
      } else if (currentLunchBoxSelection) {
        renderLunchboxEmployeeTable(lunchboxEmployeeCache);
      } else if (currentGenderSelection) {
        renderGenderEmployeeTable(genderEmployeeCache);
      } else {
        renderDepartmentEmployeeTable(departmentEmployeeCache);
      }
    });

    const closeButton = drawer.querySelector(".department-employee-close");
    if (closeButton instanceof HTMLElement) {
      closeButton.onclick = (event) => {
        event.preventDefault();
        event.stopPropagation();
        closeDepartmentEmployeeDrawer();
      };
    }

    document.onkeydown = (event) => {
      if (event.key === "Escape" && drawer.classList.contains("is-open")) {
        closeDepartmentEmployeeDrawer();
      }
    };
  }

  // initDashboard already runs after DOMContentLoaded, so bind the drawer
  // controls now instead of waiting for an event that has already fired.
  bindDepartmentDrawerControls();
  bindRoomStatusDrawerControls();

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
      const normalizedLabel = label === "Others" ? "Others" : label;
      const color =
        normalizedLabel === "Female"
          ? "#e879a0"
          : normalizedLabel === "Others"
            ? "#f59e0b"
            : "#00639d";
      tooltip.innerHTML = `
                <span class="dashboard-gender-tooltip-indicator" style="background:${color}"></span>
                <div>
                    <div class="dashboard-gender-tooltip-title">${normalizedLabel}</div>
                    <div class="dashboard-gender-tooltip-value">${count} employees</div>
                    <div class="dashboard-gender-tooltip-meta">Click to view employees</div>
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
      maleArc.style.cursor = "pointer";
      maleArc.onclick = () => openGenderEmployeeDrawer("Male");
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
      femaleArc.style.cursor = "pointer";
      femaleArc.onclick = () => openGenderEmployeeDrawer("Female");
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
      otherArc.setAttribute("data-gender", "Others");
      otherArc.setAttribute("data-count", otherCount);
      otherArc.setAttribute("data-percent", otherPct);
      otherArc.style.cursor = "pointer";
      otherArc.onclick = () => openGenderEmployeeDrawer("Others");
      otherArc.onmouseenter = (event) => {
        setArcState(otherArc);
        showTooltip(event, "Others", otherCount, otherPct);
      };
      otherArc.onmousemove = (event) => {
        setArcState(otherArc);
        showTooltip(event, "Others", otherCount, otherPct);
      };
      otherArc.onmouseleave = hideTooltip;
    }

    document
      .querySelectorAll(".dashboard-donut-legend-item")
      .forEach((item) => {
        item.style.cursor = "pointer";
        item.onclick = () => {
          const label = item
            .querySelector(".dashboard-donut-legend-label")
            ?.textContent?.trim();
          if (label === "Male" || label === "Female" || label === "Others") {
            openGenderEmployeeDrawer(label);
          }
        };
        item.onmouseenter = () => {
          const label = item
            .querySelector(".dashboard-donut-legend-label")
            ?.textContent?.trim();
          const arc =
            label === "Female"
              ? femaleArc
              : label === "Others"
                ? otherArc
                : maleArc;
          setArcState(arc);
          if (arc && tooltip) {
            const displayLabel = label === "Others" ? "Others" : label;
            showTooltip(
              { clientX: 0, clientY: 0 },
              displayLabel,
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
      if (!el) return;

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
    if (dashboardLoading) {
      return Promise.resolve();
    }

    dashboardLoading = true;

    const refreshPromise = Promise.allSettled([
      loadDashboardSummary(),
      loadRecentEmployees(),
      loadCharts(),
      loadTrafficOverviewChart(),
      loadLunchboxChart(),
      loadTransactionCards(
        "arrival",
        "dashboard-arrivals",
        "badge-status-arriving",
        "No arrivals scheduled today.",
      ),
      loadTransactionCards(
        "departure",
        "dashboard-departures",
        "badge-status-departing",
        "No departures scheduled today.",
      ),
    ]).finally(() => {
      dashboardLoading = false;
    });

    return refreshPromise;
  };

  const refreshButton = document.getElementById("dashboardRefreshBtn");
  if (refreshButton) {
    refreshButton.addEventListener("click", () => {
      loadDashboard();
    });
  }

  if (window.dashboardRefreshInterval) {
    clearInterval(window.dashboardRefreshInterval);
    delete window.dashboardRefreshInterval;
  }

  loadDashboard();

  let trafficChartInstance = null;

  function getMonday(date) {
    const d = new Date(date);
    const day = d.getDay(); // 0 = Sunday ... 6 = Saturday
    // Calculate days to subtract to get to Monday
    // Monday is 1, so: if Monday, subtract 0; if Tuesday, subtract 1; etc.
    // If Sunday (0), subtract -6 (i.e., add 1 day... wait that's wrong)
    // Sunday should go back to previous Monday (6 days back)
    const diff = day === 0 ? -6 : 1 - day;
    d.setDate(d.getDate() + diff);

    console.debug("getMonday calculation:", {
      originalDate: new Date(date).toISOString().split("T")[0],
      dayOfWeek: day, // 0=Sun, 1=Mon, 2=Tue, 3=Wed, 4=Thu, 5=Fri, 6=Sat
      dayName: ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"][day],
      diff: diff,
      resultDate: d.toISOString().split("T")[0],
    });

    return d;
  }

  function setTrafficSummaryText(id, value) {
    const el = document.getElementById(id);
    if (el) {
      el.textContent = value;
    }
  }

  function formatTrafficSummary(arrivals, departures) {
    return `${arrivals} Arrivals · ${departures} Departures`;
  }

  function buildWeekTrafficData(arrivalRecords, departureRecords, weekDates) {
    const byDate = new Map();

    weekDates.forEach((entry) => {
      byDate.set(entry.date, { ...entry, arrivals: 0, departures: 0 });
    });

    (Array.isArray(arrivalRecords) ? arrivalRecords : []).forEach((record) => {
      const key =
        record.transaction_date || record.transactionDate || record.date;
      if (!key || !byDate.has(key)) return;
      byDate.get(key).arrivals += 1;
    });

    (Array.isArray(departureRecords) ? departureRecords : []).forEach(
      (record) => {
        const key =
          record.transaction_date || record.transactionDate || record.date;
        if (!key || !byDate.has(key)) return;
        byDate.get(key).departures += 1;
      },
    );

    return weekDates.map(
      (entry) =>
        byDate.get(entry.date) || { ...entry, arrivals: 0, departures: 0 },
    );
  }

  function formatShortDate(dateString) {
    if (!dateString) return "";

    const date = new Date(`${dateString}T00:00:00`);
    if (Number.isNaN(date.getTime())) return dateString;

    const month = String(date.getMonth() + 1).padStart(2, "0");
    const day = String(date.getDate()).padStart(2, "0");
    const year = String(date.getFullYear()).slice(-2);

    return `${month}/${day}/${year}`;
  }

  function formatTrafficXLabel(dateString, weekdayLabel) {
    const shortLabel = weekdayLabel
      ? weekdayLabel.slice(0, 3)
      : "";
    const shortDate = formatShortDate(dateString);

    if (!shortLabel || !shortDate) return weekdayLabel || "";
    return `${shortLabel} ${shortDate}`;
  }

  function formatTrafficFullDate(dateString) {
    if (!dateString) return "";

    const date = new Date(`${dateString}T00:00:00`);
    if (Number.isNaN(date.getTime())) return dateString;

    const weekday = new Intl.DateTimeFormat("en-US", { weekday: "long" }).format(date);
    const month = new Intl.DateTimeFormat("en-US", { month: "long" }).format(date);
    const day = date.getDate();
    const year = date.getFullYear();

    return `${weekday}, ${month} ${day}, ${year}`;
  }

  function showTrafficChartLoading() {
    const canvasWrap = document.querySelector(".traffic-chart-canvas-wrap");
    if (!canvasWrap) return;

    if (!canvasWrap.querySelector("#groupedTrafficChart")) {
      canvasWrap.innerHTML =
        '<canvas id="groupedTrafficChart" width="900" height="420" role="img" aria-label="Daily arrival and departure activity chart"></canvas>';
    }

    setTrafficSummaryText("traffic-week-summary", "Loading...");
    setTrafficSummaryText("traffic-today-summary", "Loading...");
  }

  function showTrafficChartError(message) {
    const canvasWrap = document.querySelector(".traffic-chart-canvas-wrap");
    if (!canvasWrap) return;

    if (!canvasWrap.querySelector("#groupedTrafficChart")) {
      canvasWrap.innerHTML =
        '<canvas id="groupedTrafficChart" width="900" height="420" role="img" aria-label="Daily arrival and departure activity chart"></canvas>';
    }

    setTrafficSummaryText("traffic-week-summary", "Unable to load data");
    setTrafficSummaryText("traffic-today-summary", "Unable to load data");
  }

  function loadTrafficOverviewChart() {
    showTrafficChartLoading();

    const monday = getMonday(new Date());
    const weekDates = Array.from({ length: 7 }, (_, index) => {
      const date = new Date(monday);
      date.setDate(monday.getDate() + index);
      return {
        date: getLocalDateString(date),
        label: [
          "Monday",
          "Tuesday",
          "Wednesday",
          "Thursday",
          "Friday",
          "Saturday",
          "Sunday",
        ][index],
      };
    });

    const weekStart = weekDates[0].date;
    const weekEnd = weekDates[6].date;
    const arrivalWeekUrl = `api/transactions/index.php/type/arrival?date_from=${weekStart}&date_to=${weekEnd}`;
    const departureWeekUrl = `api/transactions/index.php/type/departure?date_from=${weekStart}&date_to=${weekEnd}`;

    console.group("Traffic Chart - Weekly Daily Data");
    console.log(
      "📅 Week: %c" + weekStart + " → " + weekEnd,
      "color: #0284c7; font-weight: bold",
    );
    console.log("📅 Today: %c" + today, "color: #0284c7; font-weight: bold");
    console.log("Arrival URL:", arrivalWeekUrl);
    console.log("Departure URL:", departureWeekUrl);
    console.groupEnd();

    Promise.all([fetchJSON(arrivalWeekUrl), fetchJSON(departureWeekUrl)]).then(
      ([arrivalWeek, departureWeek]) => {
        if (arrivalWeek === null || departureWeek === null) {
          console.error("Traffic chart API request failed");
          showTrafficChartError("Unable to load arrival & departure data.");
          return;
        }

        const dailyData = buildWeekTrafficData(
          arrivalWeek,
          departureWeek,
          weekDates,
        );
        const totalArrivals = dailyData.reduce(
          (sum, entry) => sum + entry.arrivals,
          0,
        );
        const totalDepartures = dailyData.reduce(
          (sum, entry) => sum + entry.departures,
          0,
        );
        const todayEntry = dailyData.find((entry) => entry.date === today) || {
          arrivals: 0,
          departures: 0,
        };

        setTrafficSummaryText(
          "traffic-week-summary",
          formatTrafficSummary(totalArrivals, totalDepartures),
        );
        setTrafficSummaryText(
          "traffic-today-summary",
          formatTrafficSummary(todayEntry.arrivals, todayEntry.departures),
        );

        console.log("✅ Rendering daily traffic chart:", dailyData);
        renderTrafficChart(dailyData);
      },
    );
  }

  // ARRIVAL & DEPARTURE TRAFFIC CHART
  function renderTrafficChart(dailyData) {
    const canvasWrap = document.querySelector(".traffic-chart-canvas-wrap");

    if (!canvasWrap) {
      console.error("Traffic chart canvas wrapper not found");
      return;
    }

    const currentChartCanvas = canvasWrap.querySelector("#groupedTrafficChart");
    if (!currentChartCanvas) {
      canvasWrap.innerHTML =
        '<canvas id="groupedTrafficChart" width="900" height="420" role="img" aria-label="Daily arrival and departure activity chart"></canvas>';
    }

    const freshCanvas = document.getElementById("groupedTrafficChart");
    if (!freshCanvas) {
      console.error("Failed to get traffic chart canvas");
      return;
    }

    const highlightTodayPlugin = {
      id: "highlightTodayPlugin",
      afterDatasetsDraw(chart) {
        const todayIndex = chart.data.todayIndex ?? -1;
        if (todayIndex < 0) return;

        const metaA = chart.getDatasetMeta(0);
        const metaD = chart.getDatasetMeta(1);
        const arrivalBar = metaA?.data[todayIndex];
        const departureBar = metaD?.data[todayIndex];

        if (!arrivalBar || !departureBar) return;

        const left =
          Math.min(
            arrivalBar.x - arrivalBar.width / 2,
            departureBar.x - departureBar.width / 2,
          ) - 12;
        const right =
          Math.max(
            arrivalBar.x + arrivalBar.width / 2,
            departureBar.x + departureBar.width / 2,
          ) + 12;
        const top = chart.chartArea.top;
        const bottom = chart.chartArea.bottom;

        chart.ctx.save();
        chart.ctx.fillStyle = "rgba(59, 130, 246, 0.06)";
        chart.ctx.fillRect(left, top, right - left, bottom - top);
        chart.ctx.strokeStyle = "rgba(37, 99, 235, 0.14)";
        chart.ctx.strokeRect(left, top, right - left, bottom - top);
        chart.ctx.restore();
      },
    };

    const dataLabelsPlugin = {
      id: "trafficDataLabels",
      afterDatasetsDraw(chart) {
        const { ctx } = chart;
        ctx.save();
        ctx.font = "500 12px Inter";
        ctx.textAlign = "center";
        ctx.textBaseline = "bottom";
        ctx.fillStyle = "#414750";

        chart.data.datasets.forEach((dataset) => {
          const meta = chart.getDatasetMeta(
            chart.data.datasets.indexOf(dataset),
          );
          if (!meta.hidden) {
            meta.data.forEach((element, index) => {
              const value = dataset.data[index];
              if (value > 0) {
                ctx.fillText(String(value), element.x, element.y - 8);
              }
            });
          }
        });

        ctx.restore();
      },
    };

    const config = {
      type: "bar",
      data: {
        labels: dailyData.map((entry) =>
          formatTrafficXLabel(entry.date, entry.label),
        ),
        datasets: [
          {
            label: "Arrival",
            data: dailyData.map((entry) => entry.arrivals),
            backgroundColor: "#00639d",
            borderRadius: 6,
            borderSkipped: false,
            barPercentage: 0.7,
            categoryPercentage: 0.72,
            dataDates: dailyData.map((entry) => entry.date),
          },
          {
            label: "Departure",
            data: dailyData.map((entry) => entry.departures),
            backgroundColor: "#b45309",
            borderRadius: 6,
            borderSkipped: false,
            barPercentage: 0.7,
            categoryPercentage: 0.72,
            dataDates: dailyData.map((entry) => entry.date),
          },
        ],
        todayIndex: dailyData.findIndex((entry) => entry.date === today),
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
          mode: "index",
          intersect: false,
        },
        onHover: (event, elements) => {
          const canvas = event?.native?.target || event?.target;
          if (canvas) {
            canvas.style.cursor = elements.length ? "pointer" : "default";
          }
        },
        onClick: (event, elements) => {
          if (!elements.length) return;

          const chart = event?.chart;
          const exactHit = chart
            ? chart.getElementsAtEventForMode(
                event,
                "nearest",
                { intersect: true },
                true,
              )[0]
            : null;

          const element = exactHit || elements[0];
          const datasetIndex =
            typeof element?.datasetIndex === "number"
              ? element.datasetIndex
              : 0;
          const dateIndex =
            typeof element?.index === "number" ? element.index : 0;
          const selectedDate = dailyData[dateIndex]?.date;
          const selectedType = datasetIndex === 0 ? "arrival" : "departure";

          if (selectedDate && selectedType) {
            openTrafficEmployeeDrawer(selectedDate, selectedType);
          }
        },
        plugins: {
          legend: { display: false },
          tooltip: {
            backgroundColor: "#213145",
            titleColor: "#f8fafc",
            bodyColor: "#f8fafc",
            titleFont: { family: "Sora", size: 12 },
            bodyFont: { family: "Inter", size: 12 },
            padding: 12,
            cornerRadius: 8,
            callbacks: {
              title: (items) => {
                const date = dailyData[items[0]?.dataIndex]?.date;
                return formatTrafficFullDate(date);
              },
              label: (context) => {
                const date = dailyData[context.dataIndex]?.date;
                const movement = context.datasetIndex === 0 ? "Arrival" : "Departure";
                const count = Number(context.parsed.y || 0);
                const label = count === 1 ? "employee" : "employees";
                return `${movement}\n${count} ${label}`;
              },
            },
          },
        },
        scales: {
          x: {
            grid: { display: false },
            ticks: {
              font: { family: "Inter", size: 11, weight: "600" },
              color: "#414750",
              maxRotation: 0,
              autoSkip: false,
            },
            border: { display: false },
          },
          y: {
            beginAtZero: true,
            suggestedMax: Math.max(
              ...dailyData.flatMap((entry) => [
                entry.arrivals,
                entry.departures,
              ]),
              5,
            ),
            precision: 0,
            grid: { color: "#e2e8f0", drawTicks: false },
            ticks: {
              font: { family: "Inter", size: 11 },
              color: "#717881",
              padding: 10,
              precision: 0,
              callback: (value) => {
                if (!Number.isFinite(Number(value))) return "";
                const integerValue = Number(value);
                return Number.isInteger(integerValue) ? integerValue : "";
              },
            },
            border: { display: false },
          },
        },
        layout: { padding: { top: 18, right: 10, left: 10, bottom: 10 } },
      },
      plugins: [highlightTodayPlugin, dataLabelsPlugin],
    };

    if (trafficChartInstance) {
      trafficChartInstance.data = config.data;
      trafficChartInstance.options = config.options;
      trafficChartInstance.update();
    } else {
      try {
        const ctx = freshCanvas.getContext("2d");
        if (!ctx) {
          throw new Error("Failed to get canvas 2D context");
        }
        trafficChartInstance = new Chart(ctx, config);
      } catch (error) {
        console.error("Failed to render traffic chart:", error);
        showTrafficChartError(
          "Unable to render chart. Please refresh the page.",
        );
      }
    }
  }
  // END ARRIVAL & DEPARTURE TRAFFIC CHART

  // MEAL CHART
  let lunchboxChartInstance = null;

  function loadLunchboxChart() {
    const statusEl = document.getElementById("lunchboxChartStatus");
    const canvasEl = document.getElementById("lunchboxChart");

    if (statusEl) {
      statusEl.textContent = "Loading lunch box data...";
      statusEl.style.display = "flex";
    }
    if (canvasEl) canvasEl.style.display = "none";

    const monday = getMonday(new Date());
    const dayNames = ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"];

    const thisWeekDates = Array.from({ length: 7 }, (_, i) => {
      const d = new Date(monday);
      d.setDate(monday.getDate() + i);
      return getLocalDateString(d);
    });

    const nextMonday = new Date(monday);
    nextMonday.setDate(monday.getDate() + 7);
    const nextWeekDates = Array.from({ length: 7 }, (_, i) => {
      const d = new Date(nextMonday);
      d.setDate(nextMonday.getDate() + i);
      return getLocalDateString(d);
    });

    const startDate = thisWeekDates[0];
    const endDate = nextWeekDates[6];

    fetchJSON(
      `api/meals/lunchbox_summary.php?date_from=${startDate}&date_to=${endDate}`,
    ).then((data) => {
      if (!data || !data.daily_counts) {
        if (statusEl) {
          statusEl.textContent = "Unable to load lunch box data.";
          statusEl.style.display = "flex";
        }
        return;
      }

      const counts = data.daily_counts;

      const thisWeekValues = thisWeekDates.map((d) => Number(counts[d] || 0));
      const weekTotal = thisWeekValues.reduce((sum, v) => sum + v, 0);
      const nextWeekTotal = nextWeekDates.reduce(
        (sum, d) => sum + Number(counts[d] || 0),
        0,
      );

      setKpiValue("lunchbox-week-total", weekTotal, 0);
      setKpiValue("lunchbox-nextweek-total", nextWeekTotal, 0);

      if (weekTotal === 0 && nextWeekTotal === 0) {
        if (statusEl) {
          statusEl.textContent = "No lunch box data available.";
          statusEl.style.display = "flex";
        }
        return;
      }

      if (statusEl) statusEl.style.display = "none";
      if (canvasEl) canvasEl.style.display = "block";

      const ctx = canvasEl?.getContext("2d");
      if (!ctx) return;

      // Build labels with day name and date (e.g., "Mon 8/10/26")
      const chartLabels = thisWeekDates.map((dateStr, idx) => {
        const [year, month, day] = dateStr.split("-");
        const shortYear = year.slice(-2);
        return `${dayNames[idx]} ${month}/${day}/${shortYear}`;
      });

      const todayDate = getLocalDateString(new Date());
      lunchboxChartPoints = thisWeekDates.map((dateStr, idx) => ({
        label: chartLabels[idx],
        date: dateStr,
        count: Number(thisWeekValues[idx] || 0),
      }));

      const chartConfig = {
        type: "bar",
        data: {
          labels: chartLabels,
          datasets: [
            {
              label: "Lunch Boxes",
              data: lunchboxChartPoints.map((point) => point.count),
              backgroundColor: thisWeekDates.map((dateStr) =>
                dateStr === todayDate ? "#0f766e" : "#003686",
              ),
              borderRadius: 4,
              maxBarThickness: 48,
            },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          animation: { duration: 250 },
          onHover: (event, elements) => {
            const canvas = event?.native?.target || event?.target;
            if (canvas) {
              canvas.style.cursor = elements.length ? "pointer" : "default";
            }
          },
          onClick: (event, elements) => {
            if (!elements.length) return;
            const index = elements[0].index;
            const selectedDate = lunchboxChartPoints[index]?.date || thisWeekDates[index];
            if (selectedDate) {
              openLunchBoxEmployeeDrawer(selectedDate);
            }
          },
          plugins: {
            legend: { display: false },
            tooltip: {
              callbacks: {
                title: (items) => {
                  const index = items[0]?.dataIndex ?? 0;
                  return lunchboxChartPoints[index]?.label || chartLabels[index] || "Lunch Box";
                },
                label: (item) => `${item.parsed.y} Lunch Boxes — click to view employees`,
              },
            },
          },
          scales: {
            x: {
              grid: { display: false },
              ticks: { color: "#737784", font: { size: 12 } },
            },
            y: {
              beginAtZero: true,
              ticks: { precision: 0, color: "#737784" },
              grid: { color: "#eef0f5" },
            },
          },
        },
      };

      if (lunchboxChartInstance) {
        lunchboxChartInstance.data.labels = chartLabels;
        lunchboxChartInstance.data.datasets[0].data = chartConfig.data.datasets[0].data;
        lunchboxChartInstance.data.datasets[0].backgroundColor = chartConfig.data.datasets[0].backgroundColor;
        lunchboxChartInstance.options = chartConfig.options;
        lunchboxChartInstance.update();
      } else {
        lunchboxChartInstance = new Chart(ctx, chartConfig);
      }
    });
  }
  // END MEAL CHART
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
