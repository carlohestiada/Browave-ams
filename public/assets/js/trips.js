function tripApiUrl(path) {
  const base = window.location.pathname.replace(/\/[^\/]+$/, "");
  return `${base}/${path.replace(/^\/+/, "")}`;
}

const tripStatuses = ["PLANNED", "ACTIVE", "COMPLETED", "CANCELLED"];
let tripEmployees = [];
let tripRooms = [];
let tripRoomHistory = [];
let tripRows = [];
let tripModal;
let detailsModal;
let tripLoadRequestId = 0;
let currentTripView = "active";
let activeTripStatusFilter = "";
const tripSortColumns = [
  { index: 0, key: (trip) => trip.employee_code || trip.employee_id },
  { index: 1, key: (trip) => trip.employee_name || "" },
  { index: 2, key: (trip) => trip.department_name || "" },
  {
    index: 3,
    key: (trip) =>
      trip.legs?.find((leg) => leg.leg_type === "ARRIVAL")?.leg_date || "",
  },
  {
    index: 4,
    key: (trip) =>
      trip.legs?.find((leg) => leg.leg_type === "DEPARTURE")?.leg_date || "",
  },
  { index: 9, key: (trip) => trip.status || "" },
];

function escapeTripHtml(value) {
  return $("<div>")
    .text(value == null ? "" : String(value))
    .html();
}

function tripResponse(data) {
  return typeof data === "string" ? JSON.parse(data) : data;
}

function employeeLabel(employee) {
  return `${displayEmployeeCode(employee.employee_code)} - ${employee.english_name || employee.full_name || employee.chinese_name || "Unnamed employee"}`;
}

function statusBadge(status) {
  const normalized = String(status || "").toLowerCase();
  return `<span class="badge status-badge status-${escapeTripHtml(normalized)}">${escapeTripHtml(status || "Unknown")}</span>`;
}

function formatTripDate(date) {
  if (!date) return "—";
  const parsed = new Date(`${date}T00:00:00`);
  return Number.isNaN(parsed.getTime())
    ? escapeTripHtml(date)
    : parsed.toLocaleDateString(undefined, {
        year: "numeric",
        month: "2-digit",
        day: "2-digit",
      });
}

function roomForEmployee(employeeId) {
  const assignment = tripRooms.find(
    (room) =>
      String(room.employee_id) === String(employeeId) &&
      ["Active", "Transferred"].includes(room.status),
  );
  if (assignment) {
    return {
      accommodation: assignment.accommodation_name || "—",
      room: assignment.room_no || "—",
      checkedOut: false,
    };
  }

  const checkedOutAssignment = tripRoomHistory.find(
    (room) => String(room.employee_id) === String(employeeId),
  );
  if (!checkedOutAssignment) {
    return { accommodation: "—", room: "—", checkedOut: false };
  }

  return {
    accommodation: checkedOutAssignment.accommodation_name || "—",
    room: `${checkedOutAssignment.room_no || "—"} (checked out)`,
    checkedOut: true,
  };
}

function renderEmployeeOptions(selector, includeAll = false, allowedIds = null) {
  const options = includeAll
    ? '<option value="">All employees</option>'
    : '<option value="">Select employee</option>';
  const employees = allowedIds
    ? tripEmployees.filter((employee) => allowedIds.has(String(employee.id)))
    : tripEmployees;
  $(selector).html(
    options +
      employees
        .map(
          (employee) => {
            const searchText = [
              employee.employee_code,
              employee.english_name,
              employee.full_name,
              employee.chinese_name,
              employee.department_name,
            ]
              .filter(Boolean)
              .join(" ");
            return `<option value="${escapeTripHtml(employee.id)}" data-search="${escapeTripHtml(searchText).replace(/"/g, "&quot;")}">${escapeTripHtml(employeeLabel(employee))}</option>`;
          },
        )
        .join(""),
  );
}

function initTripEmployeeSelect() {
  if (!$.fn.select2) {
    return;
  }

  const employeeSelect = $("#tripEmployee");
  if (
    employeeSelect.hasClass("select2-hidden-accessible") ||
    employeeSelect.data("select2")
  ) {
    employeeSelect.select2("destroy");
  }

  employeeSelect.select2({
    theme: "bootstrap-5",
    dropdownParent: $("#tripFormModal"),
    width: "100%",
    allowClear: false,
    placeholder: "Search by employee ID, name or department",
    matcher: function (params, data) {
      const term = $.trim(params.term || "").toLowerCase();
      if (!term) {
        return data;
      }

      const searchText = data.element
        ? $(data.element).attr("data-search") || ""
        : "";
      const text = `${searchText} ${data.text || ""}`.toLowerCase();
      return text.indexOf(term) > -1 ? data : null;
    },
  });
}

function renderDepartmentOptions() {
  const departments = new Map();
  tripEmployees.forEach((employee) => {
    if (employee.department_id && employee.department_name)
      departments.set(String(employee.department_id), employee.department_name);
  });
  $("#tripFilterDepartment").append(
    Array.from(departments.entries())
      .sort((a, b) => a[1].localeCompare(b[1]))
      .map(
        ([id, name]) =>
          `<option value="${escapeTripHtml(id)}">${escapeTripHtml(name)}</option>`,
      )
      .join(""),
  );
}

function renderTripRow(trip) {
  const legs = trip.legs || [];
  const arrival = legs.find((leg) => leg.leg_type === "ARRIVAL");
  const departure = legs.find((leg) => leg.leg_type === "DEPARTURE");
  const roomData = roomForEmployee(trip.employee_id);
  return `<tr>
            <td>${escapeTripHtml(trip.employee_code ? displayEmployeeCode(trip.employee_code) : trip.employee_id)}</td>
            <td>${escapeTripHtml(trip.employee_name || "—")}</td>
            <td>${escapeTripHtml(trip.department_name || "—")}</td>
            <td>${formatTripDate(arrival?.leg_date)}</td>
            <td>${formatTripDate(departure?.leg_date)}</td>
            <td>${escapeTripHtml(arrival?.arrival_airport || "—")}</td>
            <td>${escapeTripHtml(departure?.departure_airport || "—")}</td>
            <td><div style="white-space: normal; line-height: 1.4;">${escapeTripHtml(roomData.accommodation)}<br>${escapeTripHtml(roomData.room)}</div></td>
            <td>${escapeTripHtml(trip.trip_type || "—")}</td>
            <td>${statusBadge(trip.status)}</td>
            <td><button type="button" class="btn btn-primary view-trip" data-id="${escapeTripHtml(trip.id)}">${String(trip.status).toUpperCase() === "COMPLETED" ? "View Details" : "View"}</button></td>
        </tr>`;
}

function renderNoTripRow(employee) {
  const roomData = roomForEmployee(employee.employee_id);
  return `<tr>
            <td>${escapeTripHtml(displayEmployeeCode(employee.employee_code))}</td>
            <td>${escapeTripHtml(employee.employee_name || "—")}</td>
            <td>${escapeTripHtml(employee.department_name || "—")}</td>
            <td>—</td>
            <td>—</td>
            <td>—</td>
            <td>—</td>
            <td><div style="white-space: normal; line-height: 1.4;">${escapeTripHtml(roomData.accommodation)}<br>${escapeTripHtml(roomData.room)}</div></td>
            <td>—</td>
            <td><span class="badge bg-secondary">No trip</span></td>
            <td><button type="button" class="btn btn-primary create-trip-for-employee" data-employee-id="${escapeTripHtml(employee.employee_id)}">Create Trip</button></td>
        </tr>`;
}

function renderTrips(rows) {
  const noTripMode = $("#tripFilterAssignment").val() === "none";
  tripRows = Array.isArray(rows) ? rows : [];
  $("#tripCount").text(noTripMode
    ? `${tripRows.length} employees without a trip`
    : currentTripView === "completed"
      ? `${tripRows.length} completed trips found`
      : `${tripRows.length} trips found`);
  updateTripViewSummary();

  renderPaginatedTable({
    data: tripRows,
    tableSelector: "#tripsTableBody",
    currentPage: 1,
    perPage: 10,
    renderRow: noTripMode ? renderNoTripRow : renderTripRow,
    sortColumns: noTripMode ? tripSortColumns.slice(0, 3) : tripSortColumns,
  });

  if (tripRows.length === 0) {
    $("#tripsTableBody").html(
      `<tr><td colspan="11" class="text-center text-muted py-4">${noTripMode ? "All employees already have a trip." : currentTripView === "completed" ? "No completed trips found." : "No trips found."}</td></tr>`,
    );
  }
}

function updateTripViewSummary() {
  const noTripMode =
    currentTripView === "active" && $("#tripFilterAssignment").val() === "none";
  $("#tripViewSummary").text(
    noTripMode
      ? "Showing employees without a trip"
      : currentTripView === "completed"
        ? "Showing completed trips"
        : "Showing active trips",
  );
}

function updateTripAssignmentFilters() {
  const noTripMode = currentTripView === "active" && $("#tripFilterAssignment").val() === "none";
  $("#tripFilterAssignment option[value='none']").prop("disabled", currentTripView === "completed");
  $("#completedTripsTab").prop("disabled", noTripMode);
  $("#tripFilterType, #tripFilterStatus, #tripFilterFrom, #tripFilterTo")
    .prop("disabled", noTripMode);
  $("#tripFilterStatus option[value='COMPLETED'], #tripFilterStatus option[value='CANCELLED']")
    .prop("hidden", currentTripView === "active");
  if (currentTripView === "completed") {
    $("#tripFilterStatus").val("COMPLETED").prop("disabled", true);
  } else {
    if ($("#tripFilterStatus").val() === "COMPLETED" || $("#tripFilterStatus").val() === "CANCELLED") {
      $("#tripFilterStatus").val("");
    }
    $("#tripFilterStatus").prop("disabled", noTripMode);
  }
  updateTripViewSummary();
}

function setTripView(view) {
  const nextView = view === "completed" ? "completed" : "active";
  if (nextView === "completed" && $("#tripFilterAssignment").val() === "none") {
    $("#tripFilterAssignment").val("");
  }
  if (currentTripView === "active") {
    activeTripStatusFilter = $("#tripFilterStatus").val() || activeTripStatusFilter;
  }
  currentTripView = nextView;
  if (currentTripView === "completed") {
    $("#tripFilterStatus").val("COMPLETED");
  } else {
    $("#tripFilterStatus").val(activeTripStatusFilter);
  }

  $(".trip-view-tab").each(function () {
    const active = $(this).data("view") === currentTripView;
    $(this).toggleClass("active", active).attr("aria-selected", active ? "true" : "false");
  });
  $("#tripSchedulePane").attr(
    "aria-labelledby",
    currentTripView === "completed" ? "completedTripsTab" : "activeTripsTab",
  );
  updateTripAssignmentFilters();
  loadTrips();
}

function loadTrips() {
  updateTripAssignmentFilters();
  $("#tripsTableBody").html(
    '<tr><td colspan="11" class="text-center text-muted py-4">Loading trips...</td></tr>',
  );
  const params = new URLSearchParams();
  const employeeId = $("#tripFilterEmployee").val();
  const assignment = currentTripView === "active" ? $("#tripFilterAssignment").val() : "";
  const type = $("#tripFilterType").val();
  const status = currentTripView === "completed"
    ? "COMPLETED"
    : $("#tripFilterStatus").val();
  const departmentId = $("#tripFilterDepartment").val();
  if (employeeId) params.set("employee_id", employeeId);
  if (departmentId) params.set("department_id", departmentId);
  if (assignment === "none") {
    params.set("assignment", "none");
  } else {
    if (type) params.set("trip_type", type);
    if (status) params.set("status", status);
    if ($("#tripFilterFrom").val())
      params.set("date_from", $("#tripFilterFrom").val());
    if ($("#tripFilterTo").val())
      params.set("date_to", $("#tripFilterTo").val());
  }

  const requestId = ++tripLoadRequestId;
  $.ajax({
    url: tripApiUrl(
      `api/trips/index.php${params.toString() ? `?${params}` : ""}`,
    ),
    method: "GET",
    cache: false,
    dataType: "json",
  })
    .done((data) => {
      if (requestId !== tripLoadRequestId) return;
      const rows = tripResponse(data);
      const filteredRows = assignment === "none"
        ? rows
        : (Array.isArray(rows) ? rows : []).filter((trip) =>
            currentTripView === "completed"
              ? String(trip.status).toUpperCase() === "COMPLETED"
              : ["PLANNED", "ACTIVE"].includes(String(trip.status).toUpperCase()),
          );
      renderTrips(filteredRows);
    })
    .fail(() => {
      if (requestId !== tripLoadRequestId) return;
      $("#tripsTableBody").html(
        '<tr><td colspan="11" class="text-center text-danger py-4">Unable to load trips. Please try again.</td></tr>',
      );
    });
}

function loadTripEmployees() {
  return $.get(tripApiUrl("api/employees.php")).done((data) => {
    tripEmployees = tripResponse(data) || [];
    renderEmployeeOptions("#tripEmployee");
    renderEmployeeOptions("#tripFilterEmployee", true);
    renderDepartmentOptions();
  });
}

function loadTripRooms() {
  const activeRequest = $.get(tripApiUrl("api/room_assignments/index.php")).done((data) => {
    tripRooms = tripResponse(data) || [];
  });
  const historyRequest = $.get(
    tripApiUrl("api/room_assignments/index.php/checkout"),
  ).then(
    (data) => {
      tripRoomHistory = tripResponse(data) || [];
    },
    () => {
      tripRoomHistory = [];
      return [];
    },
  );
  return $.when(activeRequest, historyRequest);
}

function populateEmployeeInfo() {
  const employee = tripEmployees.find(
    (row) => String(row.id) === String($("#tripEmployee").val()),
  );
  if (!employee) {
    $("#employeePreview").addClass("d-none").empty();
    return;
  }
  const roomData = roomForEmployee(employee.id);
  $("#employeePreview").removeClass("d-none").html(`<div class="row g-2 small">
        <div class="col-md-3"><strong>Employee</strong><br>${escapeTripHtml(employee.english_name || employee.full_name || employee.chinese_name || "—")}</div>
        <div class="col-md-3"><strong>Employee ID</strong><br>${escapeTripHtml(displayEmployeeCode(employee.employee_code))}</div>
        <div class="col-md-3"><strong>Department</strong><br>${escapeTripHtml(employee.department_name || "—")}</div>
        <div class="col-md-3"><strong>Accommodation</strong><br>${escapeTripHtml(roomData.accommodation)}<br><small>${escapeTripHtml(roomData.room)}</small></div>
    </div>`);
}

function legSection(legType, leg = {}, index = 0) {
  const label = legType === "ARRIVAL" ? "Arrival" : "Departure";
  const airport =
    legType === "ARRIVAL" ? "arrival_airport" : "departure_airport";
  return `<div class="col-lg-6"><div class="border rounded p-3 h-100"><div class="d-flex justify-content-between align-items-center mb-3"><h6 class="mb-0">${label} (optional)</h6><span class="badge bg-light text-dark">${legType}</span></div>
        <input type="hidden" name="leg_id_${index}" value="${escapeTripHtml(leg.id || "")}">
        <input type="hidden" name="leg_type_${index}" value="${legType}">
        <div class="mb-3"><label class="form-label" for="leg_date_${index}">${label} Date (optional)</label><input type="date" class="form-control trip-leg-date" id="leg_date_${index}" name="leg_date_${index}" value="${escapeTripHtml(leg.leg_date || "")}"></div>
        <div class="mb-3"><label class="form-label" for="${airport}_${index}">${label} Airport</label><input type="text" class="form-control" id="${airport}_${index}" name="${airport}_${index}" value="${escapeTripHtml(leg[airport] || "")}"></div>
        <div class="row g-2"><div class="col-md-6"><label class="form-label" for="origin_${index}">Origin (optional)</label><input type="text" class="form-control" id="origin_${index}" name="origin_${index}" value="${escapeTripHtml(leg.origin || "")}"></div><div class="col-md-6"><label class="form-label" for="destination_${index}">Destination (optional)</label><input type="text" class="form-control" id="destination_${index}" name="destination_${index}" value="${escapeTripHtml(leg.destination || "")}"></div></div>
    </div></div>`;
}

function handleTripTypeChange(legs = []) {
  const type = $("#tripType").val();
  const order =
    type === "ROUND_TRIP" ? ["DEPARTURE", "ARRIVAL"] : ["ARRIVAL", "DEPARTURE"];
  const byType = Object.fromEntries(
    (legs || []).map((leg) => [leg.leg_type, leg]),
  );
  $("#tripLegsForm").html(
    order
      .map((legType, index) =>
        legSection(legType, byType[legType] || {}, index),
      )
      .join(""),
  );
}

function showTripForm(trip = null, preselectEmployeeId = null) {
  $("#tripForm")[0].reset();
  $("#tripFormError").addClass("d-none").empty();
  $("#tripEditId").val(trip?.id || "");
  $("#tripFormModalLabel").text(trip ? "Edit Trip" : "CreateTrip");
  $("#tripType").val(trip?.trip_type || "NORMAL_TRIP");
  $("#tripStatus").val(trip?.status || "PLANNED");
  $("#tripRemarks").val(trip?.remarks || "");
  handleTripTypeChange(trip?.legs || []);

  if (trip) {
    renderEmployeeOptions("#tripEmployee");
    $("#tripEmployee").val(trip.employee_id).prop("disabled", true);
    populateEmployeeInfo();
    tripModal.show();
    return;
  }

  $("#tripEmployee")
    .html('<option value="">Loading employees...</option>')
    .prop("disabled", true);
  $.ajax({
    url: tripApiUrl("api/trips/index.php?assignment=none"),
    method: "GET",
    cache: false,
    dataType: "json",
  })
    .done((employeesWithoutTrip) => {
      const allowedIds = new Set(
        (Array.isArray(employeesWithoutTrip) ? employeesWithoutTrip : [])
          .map((employee) => String(employee.employee_id)),
      );
      renderEmployeeOptions("#tripEmployee", false, allowedIds);
      const preselectedId = String(preselectEmployeeId || "");
      $("#tripEmployee")
        .val(allowedIds.has(preselectedId) ? preselectedId : "")
        .prop("disabled", false);
      populateEmployeeInfo();
      tripModal.show();
    })
    .fail(() => {
      renderEmployeeOptions("#tripEmployee");
      const preselectedId = String(preselectEmployeeId || "");
      $("#tripEmployee")
        .val(
          $("#tripEmployee option").filter(function () {
            return String(this.value) === preselectedId;
          }).length
            ? preselectedId
            : "",
        )
        .prop("disabled", false);
      populateEmployeeInfo();
      tripModal.show();
    });
}

function legHasData(leg) {
  return [
    "leg_date",
    "origin",
    "destination",
    "arrival_airport",
    "departure_airport",
  ].some((field) => String(leg?.[field] ?? "").trim() !== "");
}

function validateTripForm(legs = collectTripData().legs) {
  for (const leg of legs) {
    if (legHasData(leg) && !leg.leg_date) {
      const label = leg.leg_type === "ARRIVAL" ? "Arrival" : "Departure";
      return `${label} date is required when other ${label.toLowerCase()} details are filled in.`;
    }
  }

  const arrivalDate = legs.find((leg) => leg.leg_type === "ARRIVAL" && leg.leg_date)?.leg_date;
  const departureDate = legs.find((leg) => leg.leg_type === "DEPARTURE" && leg.leg_date)?.leg_date;

  if (arrivalDate && departureDate) {
    if ($("#tripType").val() === "NORMAL_TRIP" && arrivalDate > departureDate) {
      return "Arrival date must be on or before departure date.";
    }
    if ($("#tripType").val() === "ROUND_TRIP" && departureDate > arrivalDate) {
      return "Departure date must be on or before arrival date.";
    }
  }

  return null;
}

function collectTripData() {
  const legs = [0, 1].map((index) => {
    const type = $(`[name="leg_type_${index}"]`).val();
    const airport =
      type === "ARRIVAL"
        ? $(`#arrival_airport_${index}`).val()
        : $(`#departure_airport_${index}`).val();
    return {
      id: $(`[name="leg_id_${index}"]`).val(),
      leg_type: type,
      leg_date: $(`#leg_date_${index}`).val(),
      origin: $(`#origin_${index}`).val().trim(),
      destination: $(`#destination_${index}`).val().trim(),
      arrival_airport: type === "ARRIVAL" ? airport : "",
      departure_airport: type === "DEPARTURE" ? airport : "",
      remarks: "",
    };
  });
  return {
    employee_id: $("#tripEmployee").val(),
    trip_type: $("#tripType").val(),
    status: $("#tripStatus").val(),
    remarks: $("#tripRemarks").val().trim(),
    legs,
  };
}

function saveTrip() {
  $("#tripFormError").addClass("d-none").empty();
  const data = collectTripData();
  const editId = $("#tripEditId").val();
  const hasLegDate = data.legs.some((leg) => Boolean(leg.leg_date));
  const hasUndatedDetails = data.legs.some((leg) => legHasData(leg) && !leg.leg_date);
  if (!hasLegDate && (editId || !hasUndatedDetails)) {
    const message = editId
      ? "A trip must keep at least one arrival or departure."
      : "Enter at least an arrival or a departure date.";
    swalError(message).then(() => $("#leg_date_0").trigger("focus"));
    return;
  }

  const error = validateTripForm(data.legs);
  if (error) {
    $("#tripFormError").removeClass("d-none").text(error);
    return;
  }
  const button = $("#saveTripButton")
    .prop("disabled", true)
    .html(
      '<span class="spinner-border spinner-border-sm me-1"></span> Saving...',
    );
  let saveRequest;
  if (editId) {
    const deletions = [];
    const updates = [];
    const creations = [];
    data.legs.forEach((leg) => {
      const legId = String(leg.id || "").trim();
      const payload = {
        leg_type: leg.leg_type,
        leg_date: leg.leg_date,
        origin: leg.origin,
        destination: leg.destination,
        arrival_airport: leg.arrival_airport,
        departure_airport: leg.departure_airport,
        remarks: "",
      };

      if (leg.leg_date && legId) {
        updates.push(() => $.ajax({
          url: tripApiUrl(`api/trip-legs/index.php/${encodeURIComponent(legId)}`),
          method: "PUT",
          data: payload,
        }));
      } else if (leg.leg_date) {
        creations.push(() => $.ajax({
          url: tripApiUrl(`api/trips/index.php/${encodeURIComponent(editId)}/legs`),
          method: "POST",
          data: payload,
        }));
      } else if (legId) {
        deletions.push(() => $.ajax({
          url: tripApiUrl(`api/trip-legs/index.php/${encodeURIComponent(legId)}`),
          method: "DELETE",
        }));
      }
    });

    const legOperations = [...deletions, ...updates, ...creations];
    saveRequest = $.ajax({
        url: tripApiUrl(`api/trips/index.php/${editId}`),
        method: "PUT",
        data: {
          trip_type: data.trip_type,
          remarks: data.remarks,
        },
      }).then(() => legOperations.reduce(
        (operation, saveLeg) => operation.then(() => saveLeg()),
        $.Deferred().resolve().promise(),
      ));
  } else {
    saveRequest = $.post(tripApiUrl("api/trips/index.php"), {
        employee_id: data.employee_id,
        trip_type: data.trip_type,
        remarks: data.remarks,
        legs: JSON.stringify(data.legs.filter((leg) => leg.leg_date)),
      });
  }

  saveRequest
    .done(() => {
      swalSuccess(editId ? "Trip updated successfully." : "Trip created successfully.");
      tripModal.hide();
      loadTrips();
    })
    .fail((xhr) => {
      const message = xhr.responseJSON?.error || xhr.statusText ||
        "Unable to save trip. Please check the entered information.";
      $("#tripFormError").removeClass("d-none").text(message);
    })
    .always(() => button.prop("disabled", false).html("Save Trip"));
}

function calculateTripAssignmentCheckoutDate(checkinDate) {
  if (!checkinDate) return "";
  const [year, month, day] = checkinDate.split("-").map(Number);
  if (![year, month, day].every((value) => Number.isFinite(value))) {
    return "";
  }

  const checkout = new Date(year, month - 1, day);
  checkout.setDate(checkout.getDate() + 49);
  const yyyy = checkout.getFullYear();
  const mm = String(checkout.getMonth() + 1).padStart(2, "0");
  const dd = String(checkout.getDate()).padStart(2, "0");
  return `${yyyy}-${mm}-${dd}`;
}

function buildAccommodationEditor(trip, assignment = null) {
  const today = new Date().toISOString().slice(0, 10);
  const checkinValue = assignment?.checkin_date || today;
  const checkoutValue = assignment?.expected_checkout_date || calculateTripAssignmentCheckoutDate(checkinValue) || today;

  return `
    <div class="border rounded p-3 mt-3 trip-accommodation-editor">
      <div class="fw-semibold mb-3">Accommodation Details</div>
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label" for="tripAccommodationSelect">Accommodation</label>
          <select id="tripAccommodationSelect" class="form-select"></select>
        </div>
        <div class="col-md-6">
          <label class="form-label" for="tripAccommodationRoomSelect">Room No.</label>
          <select id="tripAccommodationRoomSelect" class="form-select"></select>
        </div>
        <div class="col-md-6">
          <label class="form-label" for="tripAccommodationCheckin">Check-in</label>
          <input id="tripAccommodationCheckin" type="date" class="form-control" value="${escapeTripHtml(checkinValue)}" required>
        </div>
        <div class="col-md-6">
          <label class="form-label" for="tripAccommodationCheckout">Check-out</label>
          <input id="tripAccommodationCheckout" type="date" class="form-control" value="${escapeTripHtml(checkoutValue)}" required>
        </div>
        <div class="col-12">
          <label class="form-label" for="tripAccommodationRemarks">Remarks</label>
          <textarea id="tripAccommodationRemarks" class="form-control" rows="2" placeholder="Optional remarks"></textarea>
        </div>
      </div>
      <div class="d-flex justify-content-end gap-2 mt-3">
        <button type="button" class="btn btn-outline-secondary trip-accommodation-cancel">Cancel</button>
        <button type="button" class="btn btn-primary trip-accommodation-save" data-trip-id="${escapeTripHtml(trip.id)}">Save</button>
      </div>
    </div>
  `;
}

function getTripAccommodationAssignment(employeeId) {
  return (tripRooms || []).find(
    (room) =>
      String(room.employee_id) === String(employeeId) &&
      ["Active", "Transferred"].includes(room.status),
  );
}

function tripAccommodationRoomOptions(rooms, accommodationName, selectedRoomId = "") {
  const chosenAccommodation = String(accommodationName || "");
  const roomMatches = (rooms || []).filter((room) => {
    const matchesAccommodation = String(room.accommodation_name || "") === chosenAccommodation;
    const isSelected = String(room.id) === String(selectedRoomId);
    if (!matchesAccommodation) {
      return false;
    }
    if (isSelected) {
      return true;
    }
    return !["Reserved", "Maintenance"].includes(room.status);
  });

  const options = roomMatches.map((room) => {
    const isSelected = String(room.id) === String(selectedRoomId);
    const label = `${room.room_no || "Room"}${room.status === "Occupied" ? " - Occupied" : ""}`;
    return `<option value="${escapeTripHtml(room.id)}" ${isSelected ? "selected" : ""}>${escapeTripHtml(label)}</option>`;
  });

  return options.length
    ? options.join("")
    : '<option value="">No rooms available for this accommodation</option>';
}

function openTripAccommodationEditor(trip) {
  const currentAssignment = getTripAccommodationAssignment(trip.employee_id);
  const tripDetailBody = $("#tripDetailsBody");

  $.when(
    $.get(tripApiUrl("api/accommodations/index.php")),
    $.get(tripApiUrl("api/rooms.php")),
  )
    .done((accommodationResponse, roomsResponse) => {
      const accommodations = tripResponse(accommodationResponse[0]) || [];
      const rooms = tripResponse(roomsResponse[0]) || [];
      const selectedAccommodation = currentAssignment?.accommodation_name || accommodations[0]?.accommodation_name || "";
      const selectedRoomId = currentAssignment?.room_id ? String(currentAssignment.room_id) : "";

      tripDetailBody.find(".trip-accommodation-editor").remove();
      tripDetailBody.append(buildAccommodationEditor(trip, currentAssignment));

      const accommodationSelect = $("#tripAccommodationSelect");
      accommodationSelect.html(
        '<option value="">Select accommodation</option>' +
          accommodations
            .map(
              (item) =>
                `<option value="${escapeTripHtml(item.accommodation_name || "")}" ${String(item.accommodation_name || "") === String(selectedAccommodation) ? "selected" : ""}>${escapeTripHtml(item.accommodation_name || "Unnamed accommodation")}</option>`,
            )
            .join(""),
      );

      const roomSelect = $("#tripAccommodationRoomSelect");
      roomSelect.html(
        tripAccommodationRoomOptions(rooms, selectedAccommodation, selectedRoomId),
      );

      const initialCheckin = currentAssignment?.checkin_date || (trip.legs?.find((leg) => leg.leg_type === "ARRIVAL")?.leg_date || new Date().toISOString().slice(0, 10));
      const initialCheckout = currentAssignment?.expected_checkout_date || calculateTripAssignmentCheckoutDate(initialCheckin) || initialCheckin;
      $("#tripAccommodationCheckin").val(initialCheckin);
      $("#tripAccommodationCheckout").val(initialCheckout);

      accommodationSelect.on("change", function () {
        const accommodationName = $(this).val();
        roomSelect.html(tripAccommodationRoomOptions(rooms, accommodationName, ""));
      });

      $("#tripAccommodationCheckin").on("change", function () {
        const nextCheckin = $(this).val();
        if (!nextCheckin) {
          $("#tripAccommodationCheckout").val("");
          return;
        }
        const calculated = calculateTripAssignmentCheckoutDate(nextCheckin);
        $("#tripAccommodationCheckout").val(calculated || nextCheckin);
      });

      $(".trip-accommodation-cancel").on("click", () => {
        tripDetailBody.find(".trip-accommodation-editor").remove();
      });

      $(".trip-accommodation-save").on("click", function () {
        const accommodationName = accommodationSelect.val();
        const roomId = roomSelect.val();
        const checkin = $("#tripAccommodationCheckin").val();
        const checkout = $("#tripAccommodationCheckout").val();

        if (!accommodationName) {
          swalError("Please select an accommodation.");
          return;
        }
        if (!roomId) {
          swalError("Please select a room.");
          return;
        }
        if (!checkin) {
          swalError("Please select a check-in date.");
          return;
        }
        if (!checkout) {
          swalError("Please select a check-out date.");
          return;
        }
        if (checkout < checkin) {
          swalError("Check-out date cannot be earlier than Check-in date.");
          return;
        }

        const roomRecord = rooms.find((room) => String(room.id) === String(roomId));
        const isCurrentRoom = currentAssignment && String(roomRecord?.id || "") === String(currentAssignment.room_id || "");
        if (!roomRecord || String(roomRecord.accommodation_name || "") !== String(accommodationName)) {
          swalError("The selected room does not belong to the selected accommodation.");
          return;
        }
        if (!isCurrentRoom && ["Reserved", "Maintenance"].includes(roomRecord.status)) {
          swalError("This room is unavailable. Please select another room.");
          return;
        }

        const overlappingAssignment = (tripRooms || []).find((assignmentRow) => {
          const sameRoom = String(assignmentRow.room_id) === String(roomId);
          const sameEmployee = String(assignmentRow.employee_id) === String(trip.employee_id);
          if (!sameRoom || sameEmployee) {
            return false;
          }
          if (!assignmentRow.checkin_date || !assignmentRow.expected_checkout_date) {
            return false;
          }
          return (
            checkin <= assignmentRow.expected_checkout_date &&
            assignmentRow.checkin_date <= (checkout || checkin)
          );
        });

        if (overlappingAssignment) {
          swalError("This room is already assigned during the selected dates. Please select another room.");
          return;
        }

        const payload = {
          employee_id: trip.employee_id,
          room_id: roomId,
          checkin_date: checkin,
          expected_checkout_date: checkout || checkin,
          remarks: $("#tripAccommodationRemarks").val().trim(),
        };

        const request = currentAssignment
          ? $.ajax({
              url: tripApiUrl(`api/room_assignments/index.php/${currentAssignment.id}`),
              type: "PUT",
              data: {
                ...payload,
                room_id: roomId,
                new_room_id: roomId,
              },
            })
          : $.post(tripApiUrl("api/room_assignments/index.php"), payload);

        request
          .done((response) => {
            const result = tripResponse(response);
            if (!result || result.success === false) {
              swalError(result?.error || "Unable to save accommodation.");
              return;
            }
            tripDetailBody.find(".trip-accommodation-editor").remove();
            $.when(loadTripRooms(), loadTrips()).done(() => {
              openTripDetails(trip.id);
            });
            swalSuccess("Accommodation updated successfully.");
          })
          .fail((xhr) => {
            swalError(xhr.responseJSON?.error || xhr.responseText || "Unable to save accommodation.");
          });
      });
    })
    .fail(() => {
      swalError("Unable to load accommodation options.");
    });
}

function renderTripDetails(trip) {
  const room = roomForEmployee(trip.employee_id);
  const legs = Array.isArray(trip.legs) ? trip.legs : [];
  const isCompleted = String(trip.status || "").toUpperCase() === "COMPLETED";
  const canManageTrip =
    $("#tripDetailsModal").attr("data-can-manage-trips") === "true" &&
    !isCompleted;
  const tripDetailBody = $("#tripDetailsBody");
  tripDetailBody.data("trip-id", trip.id);

  const renderDetails = (transportationLoadError = null) => {
      const transportationAssigned = legs.filter((leg) => leg.transportation).length;
      const transportationPending = legs.length - transportationAssigned;
      const hasAccommodation = room.accommodation !== "—" && room.room !== "—";
      const activeRoomAssignment = tripRooms.find(
        (row) =>
          String(row.employee_id) === String(trip.employee_id) &&
          ["Active", "Transferred"].includes(row.status),
      );

      const legsHtml = legs.length ? legs.map((leg) => `
        <tr>
          <td>${escapeTripHtml(leg.leg_type)}</td>
          <td>${formatTripDate(leg.leg_date)}</td>
          <td>${escapeTripHtml(leg.origin)}</td>
          <td>${escapeTripHtml(leg.destination)}</td>
          <td>${escapeTripHtml(leg.arrival_airport || leg.departure_airport || "—")}</td>
          <td>
            ${transportationLoadError ? `
              <div class="text-danger">
                <strong>Unable to load transportation details.</strong><br>
                ${transportationLoadError.status ? `HTTP ${escapeTripHtml(transportationLoadError.status)}: ` : ""}${escapeTripHtml(transportationLoadError.message || "Request failed")}
              </div>
            ` : leg.transportation ? `
              <div class="text-sm">
                <strong>${escapeTripHtml(leg.transportation.transportation_type)}</strong><br>
                ${leg.transportation.driver_name ? `<span class="text-muted">Driver: ${escapeTripHtml(leg.transportation.driver_name)}</span><br>` : ''}
                ${leg.transportation.vehicle_name ? `<span class="text-muted">Vehicle: ${escapeTripHtml(leg.transportation.vehicle_name)}</span><br>` : ''}
                <span class="text-muted">Pickup: ${formatTripDate(leg.transportation.pickup_date)} ${escapeTripHtml(leg.transportation.pickup_time || "—")}</span><br>
                <span class="text-muted">Location: ${escapeTripHtml(leg.transportation.pickup_location || "—")}</span><br>
                <span class="badge bg-${getStatusColor(leg.transportation.status)}">${escapeTripHtml(leg.transportation.status)}</span>
                ${canManageTrip ? `<div class="mt-2">
                  <a class="btn btn-sm btn-outline-primary" href="company-car.php?edit=${encodeURIComponent(leg.transportation.id)}">Edit</a>
                  <button type="button" class="btn btn-sm btn-outline-danger delete-transportation" data-id="${leg.transportation.id}">Delete</button>
                </div>` : ""}
              </div>
            ` : `
              <div class="text-muted">
                <em>No transportation assigned</em><br>
                ${canManageTrip ? `<a class="btn btn-sm btn-outline-primary mt-2" href="company-car.php?trip_leg_id=${encodeURIComponent(leg.id)}&employee_id=${encodeURIComponent(trip.employee_id)}&pickup_date=${encodeURIComponent(leg.leg_date)}">
                  + Add Transportation
                </a>` : ""}
              </div>
            `}
          </td>
        </tr>
      `).join("") : '<tr><td colspan="6" class="text-center text-muted">No arrival or departure details provided.</td></tr>';

      tripDetailBody.html(
        `<div class="row g-3 mb-4">
          <div class="col-md-3"><strong>Employee</strong><br>${escapeTripHtml(trip.employee_name || "—")}</div>
          <div class="col-md-3"><strong>Employee ID</strong><br>${escapeTripHtml(trip.employee_code ? displayEmployeeCode(trip.employee_code) : trip.employee_id)}</div>
          <div class="col-md-3"><strong>Department</strong><br>${escapeTripHtml(trip.department_name || "—")}</div>
          <div class="col-md-3">
            <div class="d-flex justify-content-between align-items-center gap-2">
              <strong>Accommodation Room</strong>
              ${hasAccommodation && !room.checkedOut && canManageTrip ? '<button type="button" class="btn btn-outline-secondary btn-sm trip-accommodation-edit"><i class="bi bi-pencil-square me-1" aria-hidden="true"></i>Edit</button>' : ''}
            </div>
            ${hasAccommodation ? `${escapeTripHtml(room.accommodation || "—")}<br>${escapeTripHtml(room.room || "—")}${room.checkedOut && canManageTrip ? '<div class="mt-2"><button type="button" class="btn btn-sm btn-outline-primary trip-accommodation-add">+ Add Accommodation</button></div>' : ''}` : `<div class="text-muted mt-2">No accommodation assigned</div>${canManageTrip ? '<div class="mt-2"><button type="button" class="btn btn-sm btn-outline-primary trip-accommodation-add">+ Add Accommodation</button></div>' : ""}`}
            ${hasAccommodation && activeRoomAssignment ? `
              <div class="mt-3 small">
                <div><strong>Check-in:</strong> ${escapeTripHtml(activeRoomAssignment.checkin_date || "—")}</div>
                <div><strong>Check-out:</strong> ${escapeTripHtml(activeRoomAssignment.expected_checkout_date || "—")}</div>
              </div>
            ` : ""}
          </div>
          <div class="col-md-3"><strong>Trip Type</strong><br>${escapeTripHtml(trip.trip_type || "—")}</div>
          <div class="col-md-3"><strong>Status</strong><br>${statusBadge(trip.status)}</div>
          <div class="col-12"><strong>Remarks</strong><br>${escapeTripHtml(trip.remarks || "—")}</div>
        </div>
        
        ${transportationLoadError ? `
        <div class="alert alert-danger" role="alert" style="margin-bottom:1rem;">
          <strong>Transportation data could not be loaded.</strong>
          ${transportationLoadError.status ? `HTTP ${escapeTripHtml(transportationLoadError.status)}: ` : ""}${escapeTripHtml(transportationLoadError.message || "Request failed")}
        </div>
        ` : `
        <div class="alert alert-info" style="margin-bottom:1rem;">
          <strong>Transportation Summary</strong>
          <div class="mt-2">
            <small>
              <strong>Total Trip Legs:</strong> ${legs.length}<br>
              <strong>Transportation Assigned:</strong> <span style="color:green;">${transportationAssigned}</span><br>
              <strong>Transportation Pending:</strong> <span style="color:orange;">${transportationPending}</span>
            </small>
          </div>
        </div>
        `}
        
        <h6>Trip Legs & Transportation</h6>
        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead><tr><th>Type</th><th>Date</th><th>Origin</th><th>Destination</th><th>Airport</th><th>Transportation</th></tr></thead>
            <tbody>${legsHtml}</tbody>
          </table>
        </div>`,
      );

      tripDetailBody
        .off("click", ".trip-accommodation-edit, .trip-accommodation-add, .delete-transportation")
        .on("click", ".trip-accommodation-edit", () => openTripAccommodationEditor(trip))
        .on("click", ".trip-accommodation-add", () => openTripAccommodationEditor(trip))
        .on("click", ".delete-transportation", function() {
          deleteTransportation($(this).data("id"));
        });

      const lifecycleActions = trip.status === "ACTIVE"
        ? `<button type="button" class="btn btn-success" id="completeTripButton">Mark as Completed</button><button type="button" class="btn btn-outline-danger" id="cancelTripButton">Cancel Trip</button>`
        : trip.status === "PLANNED"
          ? `<button type="button" class="btn btn-outline-danger" id="cancelTripButton">Cancel Trip</button>`
          : "";
      const footerActions = canManageTrip
        ? `${lifecycleActions}<button type="button" class="btn btn-outline-primary" id="editTripButton">Edit Trip</button><button type="button" class="btn btn-outline-danger" id="deleteTripButton">Delete Trip</button>`
        : "";

      $("#tripDetailsFooter")
        .html(
          `${footerActions}<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>`,
        )
        .off("click")
        .on("click", "#editTripButton", () => {
          detailsModal.hide();
          showTripForm(trip);
        })
        .on("click", "#deleteTripButton", () => deleteTrip(trip.id))
        .on("click", "#completeTripButton", () => completeTrip(trip.id))
        .on("click", "#cancelTripButton", () => cancelTrip(trip.id));
  };

  $.ajax({
    url: tripApiUrl(`api/company-car/index.php/trip/${encodeURIComponent(trip.id)}`),
    method: "GET",
    cache: false,
    dataType: "json",
  })
    .done((response) => {
      if (String(tripDetailBody.data("trip-id")) !== String(trip.id)) return;

      const result = tripResponse(response);
      const detailLegs = result?.data?.legs;
      if (!result?.success || !Array.isArray(detailLegs)) {
        const body = JSON.stringify(response);
        console.error("Unable to load trip transportation details", {
          status: 200,
          body,
        });
        renderDetails({
          status: 200,
          message: result?.error || "Invalid transportation response",
        });
        return;
      }

      const transportationByLegId = new Map(
        detailLegs
          .filter((leg) => leg.transportation_id != null)
          .map((leg) => [String(leg.trip_leg_id), {
            id: leg.transportation_id,
            transportation_type: leg.transportation_type,
            driver_name: leg.driver_name,
            vehicle_name: leg.vehicle_name,
            pickup_date: leg.pickup_date,
            pickup_time: leg.pickup_time,
            pickup_location: leg.pickup_location,
            status: leg.status,
          }]),
      );

      legs.forEach((leg) => {
        leg.transportation = transportationByLegId.get(String(leg.id)) || null;
      });
      renderDetails();
    })
    .fail((xhr, textStatus, errorThrown) => {
      if (String(tripDetailBody.data("trip-id")) !== String(trip.id)) return;

      const message = xhr.statusText || errorThrown || textStatus || "Request failed";
      console.error("Unable to load trip transportation details", {
        status: xhr.status,
        body: xhr.responseText,
      });
      renderDetails({ status: xhr.status, message });
    });
}

function completeTrip(id) {
  swalConfirm("Are you sure this trip has been completed?", () => {
    $.ajax({ url: tripApiUrl(`api/trips/index.php/${id}/complete`), method: "POST" })
      .done(() => {
        swalSuccess("Trip marked as completed.");
        detailsModal.hide();
        setTripView("completed");
      })
      .fail((xhr) => swalError(xhr.responseJSON?.error || "Unable to complete trip."));
  });
}

function cancelTrip(id) {
  swalConfirm("Cancel this trip?", () => {
    $.ajax({ url: tripApiUrl(`api/trips/index.php/${id}/cancel`), method: "POST" })
      .done(() => {
        swalSuccess("Trip cancelled.");
        detailsModal.hide();
        loadTrips();
      })
      .fail((xhr) => swalError(xhr.responseJSON?.error || "Unable to cancel trip."));
  });
}

function getStatusColor(status) {
  switch(String(status || "").toLowerCase()) {
    case 'pending': return 'warning';
    case 'scheduled': return 'info';
    case 'picked up': return 'primary';
    case 'completed': return 'success';
    case 'cancelled': return 'danger';
    default: return 'secondary';
  }
}

function deleteTransportation(id) {
  swalConfirm("Delete this transportation request?", () => {
    $.ajax({
      url: tripApiUrl(`api/company-car/index.php/${id}`),
      method: "DELETE"
    })
      .done(() => {
        swalSuccess("Transportation deleted successfully.");
        const tripId = $("#tripDetailsBody").data("trip-id");
        if (tripId) openTripDetails(tripId);
      })
      .fail((xhr) =>
        swalError(xhr.responseJSON?.error || "Unable to delete transportation.")
      );
  });
}

function openTripDetails(id) {
  const detailBody = $("#tripDetailsBody");
  detailBody.data("trip-id", id);
  detailBody.html(
    '<div class="text-center text-muted py-4">Loading trip details...</div>',
  );
  $("#tripDetailsFooter").empty();
  detailsModal.show();
  $.get(tripApiUrl(`api/trips/index.php/${id}`))
    .done((data) => renderTripDetails(tripResponse(data)))
    .fail(() => {
      detailBody.html(
        '<div class="alert alert-danger">Unable to load trip details.<br><button type="button" class="btn btn-sm btn-outline-danger mt-2" onclick="openTripDetails(' + id + ')">Retry</button></div>',
      );
    });
}

function deleteTrip(id) {
  swalConfirm("Delete this trip and its associated legs?", () => {
    $("#deleteTripButton").prop("disabled", true);
    $.ajax({ url: tripApiUrl(`api/trips/index.php/${id}`), method: "DELETE" })
      .done(() => {
        detailsModal.hide();
        swalSuccess("Trip deleted successfully.");
        loadTrips();
      })
      .fail((xhr) =>
        swalError(xhr.responseJSON?.error || "Unable to delete trip."),
      )
      .always(() => $("#deleteTripButton").prop("disabled", false));
  });
}

$(function () {
  tripModal = new bootstrap.Modal("#tripFormModal");
  detailsModal = new bootstrap.Modal("#tripDetailsModal");
  $.when(loadTripEmployees(), loadTripRooms()).always(loadTrips);
  $("#tripFilterForm").on("submit", (event) => {
    event.preventDefault();
    loadTrips();
  });
  $(".trip-view-tab").on("click", function () {
    if (!$(this).prop("disabled")) {
      setTripView($(this).data("view"));
    }
  });
  $("#tripFilterAssignment").on("change", function () {
    if (currentTripView === "completed" && $(this).val() === "none") {
      $(this).val("");
    }
    updateTripAssignmentFilters();
  });
  $("#tripFilterStatus").on("change", function () {
    if (currentTripView === "active") {
      activeTripStatusFilter = $(this).val() || "";
    }
  });
  $("#resetTripFilters").on("click", () => {
    $("#tripFilterForm")[0].reset();
    if (currentTripView === "active") {
      activeTripStatusFilter = "";
    }
    if (currentTripView === "completed") {
      $("#tripFilterAssignment").val("");
    }
    updateTripAssignmentFilters();
    loadTrips();
  });
  $("#createTripButton").on("click", () => showTripForm());
  $("#tripEmployee").on("change", populateEmployeeInfo);
  $("#tripFormModal").on("shown.bs.modal", function () {
    initTripEmployeeSelect();
    const employeeSelect = $("#tripEmployee");
    if (
      $.fn.select2 &&
      !employeeSelect.prop("disabled") &&
      !employeeSelect.val()
    ) {
      employeeSelect.select2("open");
    }
  });
  $("#tripFormModal").on("hidden.bs.modal", function () {
    const employeeSelect = $("#tripEmployee");
    if (
      $.fn.select2 &&
      (employeeSelect.hasClass("select2-hidden-accessible") ||
        employeeSelect.data("select2"))
    ) {
      employeeSelect.select2("destroy");
    }
  });
  $("#tripType").on("change", () => {
    const cur = $("#tripLegsForm").children().length ? collectTripData().legs : [];
    handleTripTypeChange(cur);
  });
  $("#tripForm").on("submit", (event) => {
    event.preventDefault();
    saveTrip();
  });
  $("#tripsTableBody").on("click", ".view-trip", function () {
    openTripDetails($(this).data("id"));
  });
  $("#tripsTableBody").on("click", ".create-trip-for-employee", function () {
    const employeeId = $(this).data("employee-id");
    showTripForm(null, employeeId);
  });
});
