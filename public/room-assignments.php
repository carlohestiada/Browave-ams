<?php include 'layouts/header.php'; ?>
<?php include 'layouts/sidebar.php'; ?>

<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<div class="content-wrapper">
  <div class="d-flex justify-content-between mb-3">
    <div>
      <h2 style="font-size:26px; font-weight:700; color:#003686; margin:0; letter-spacing:-0.02em;">Room Assignments</h2>
      <p style="font-size:13px; color:#434653; margin:4px 0 0;">Manage and organize room assignments.</p>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#assignModal">Assign Room</button>
      <button class="btn btn-secondary" onclick="openTransfer()">Transfer</button>
    </div>
  </div>  

  <!-- Filters -->
  <div class="ams-card" style="padding:16px; margin-bottom:16px;">
    <div style="display:flex; flex-wrap:wrap; gap:16px; align-items:flex-end;">
      <div style="flex:1; min-width:220px;">
        <label class="ams-label">Search Assignment</label>
        <input
          id="assignmentSearchInput"
          type="text"
          class="ams-input"
          placeholder="Room, accommodation, employee, department">
      </div>
      <button type="button" onclick="resetAssignmentFilters()" class="btn-ams-ghost" style="height:40px;">
        Reset
      </button>
    </div>
  </div>

  <!-- Room Assignments Table -->
  <div class="ams-card" style="padding:0; overflow:hidden;">
    <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; padding:12px 16px; border-bottom:1px solid #e5e7eb;">
      <div id="selectedAssignmentsText" style="font-size:13px; color:#434653;">0 selected</div>
      <button
        type="button"
        class="btn btn-danger btn-sm"
        id="bulkDeleteAssignmentsBtn"
        onclick="deleteSelectedAssignments()"
        disabled>
        Delete Selected
      </button>
    </div>
    <div style="overflow-x:auto;">
      <table class="table table-striped" data-export-title="Room Assignment Data">
        <thead>
          <tr>
            <th style="width:44px; text-align:center;">
              <input type="checkbox" id="selectAllAssignments" aria-label="Select all assignments">
            </th>
            <th>Employee</th>
            <th>Department</th>
            <th>Gender</th>
            <th>Check In</th>
            <th>Check Out</th>
            <th>Accommodation</th>
            <th>Room No.</th>
            <th>Transferred To</th>
            <th style="width:180px; text-align:right;">Actions</th>
          </tr>
        </thead>
        <tbody id="assignmentTable"></tbody>
      </table>
    </div>
  </div>
</div>

<!-- Assign modal -->
<div class="modal fade" id="assignModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Assign Room</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="assignForm">
          <div class="mb-3">
            <label>Employee</label>
            <select id="assign_employee" name="employee_id" class="form-control"></select>
            <div class="form-text">Employees with an active room assignment cannot be assigned again here; use transfer instead.</div>
          </div>

          <div class="mb-3">
            <label>Room</label>
            <select id="assign_room" name="room_id" class="form-control"></select>
          </div>
          <div class="mb-3">
            <label>Check-in Date</label>
            <input id="assign_checkin_date" type="date" name="checkin_date" class="form-control" required>
          </div>
          <div class="mb-3">
            <label>Expected Checkout Date</label>
            <input id="assign_checkout_date" type="date" name="expected_checkout_date" class="form-control" required>
          </div>
          <button class="btn btn-primary">Save</button>
        </form>
      </div>
    </div>
  </div>
</div>
<!-- Transfer modal (moved out to avoid nested forms) -->
<div class="modal fade" id="transferModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Transfer Assignment</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="transferForm">
          <input type="hidden" id="transfer_assignment_id" name="assignment_id">
          <div class="mb-3">
            <label>Assignment</label>
            <select id="transfer_assignment" class="form-control"></select>
          </div>
          <div class="mb-3">
            <label>New Room</label>
            <select id="transfer_room" name="new_room_id" class="form-control"></select>
          </div>
          <div class="mb-3">
            <label>Transfer Date</label>
            <input type="date" id="transfer_date" name="transfer_date" class="form-control" required>
          </div>
          <div class="mb-3">
            <label>Preview</label>
            <div id="transfer_preview" class="small text-muted">--</div>
          </div>
          <button class="btn btn-primary">Transfer</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script src="assets/js/room_assignments.js"></script>
<?php include 'layouts/footer.php'; ?>
