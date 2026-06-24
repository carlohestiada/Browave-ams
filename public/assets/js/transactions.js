// Searchable Employee Dropdown
function apiUrl(path) {
    const base = window.location.pathname.replace(/\/[^\/]+$/, '');
    return base + '/' + path.replace(/^\/+/, '');
}

function loadTransactionOptions(date, target) {
    let url = apiUrl('api/employees/index.php');
    const showAll = target === 'arrival' && $('#arrival_show_all').is(':checked');

    if (target === 'arrival') {
        if (showAll && date) {
            url += '?mark_arrived_date=' + encodeURIComponent(date);
        } else if (date) {
            url += '?exclude_arrived_date=' + encodeURIComponent(date);
        }
    } else {
        url += '?status=Active';
    }

    $.get(url, function(data) {
        const employees = typeof data === 'string' ? JSON.parse(data) : data;

        if (target === 'arrival') {
            window.arrivalEmployees = employees;
            renderEmployeeList('', employees, {
                listSelector: '#arrival_employee_list',
                searchSelector: '#arrival_employee_search',
                hiddenIdSelector: '#arrival_employee_id',
                allowCreate: true,
                showAll: () => $('#arrival_show_all').is(':checked')
            });
        } else {
            window.departureEmployees = employees;
            renderEmployeeList('', employees, {
                listSelector: '#departure_employee_list',
                searchSelector: '#departure_employee_search',
                hiddenIdSelector: '#departure_employee_id',
                allowCreate: false,
                showAll: false
            });
        }
    });
}

function renderEmployeeList(searchTerm, employees, options) {
    const list = $(options.listSelector);
    const filtered = employees.filter(emp => {
        const empStr = (emp.employee_code + ' ' + emp.full_name).toLowerCase();
        return empStr.includes(searchTerm.toLowerCase());
    });

    let html = '';
    const showAll = typeof options.showAll === 'function' ? options.showAll() : options.showAll;

    filtered.forEach(emp => {
        const isArrived = showAll && emp.arrived_count && parseInt(emp.arrived_count) > 0;
        const disabled = isArrived ? ' disabled' : '';
        const disabledClass = isArrived ? ' disabled' : '';
        const label = isArrived ? `${emp.employee_code} - ${emp.full_name} (already arrived)` : `${emp.employee_code} - ${emp.full_name}`;

        html += `<div class="dropdown-item${disabledClass}" data-id="${emp.id}" data-label="${label}"${disabled ? ' style="cursor:not-allowed;"' : ''}>${label}</div>`;
    });

    if (options.allowCreate && searchTerm.trim() && !filtered.some(e => e.full_name.toLowerCase() === searchTerm.toLowerCase())) {
        html += `<div class="dropdown-item create-new" data-action="create" data-name="${searchTerm.trim()}">+ Create new: ${searchTerm.trim()}</div>`;
    }

    list.html(html || '<div class="dropdown-item" style="color:#999;">No employees found</div>');
    list.addClass('show');

    $(`${options.listSelector} .dropdown-item:not(.disabled)`).on('click', function() {
        const action = $(this).data('action');

        if (action === 'create') {
            const name = $(this).data('name');
            $('#new_employee_name').val(name);
            $('#new_employee_code').val('');
            $('#new_employee_gender').val('');
            $('#new_employee_department').val('');
            const modalElement = document.getElementById('createEmployeeModal');
            if (modalElement) {
                const modal = new bootstrap.Modal(modalElement);
                modal.show();
            }
            $(options.searchSelector).val('');
            list.removeClass('show');
        } else {
            const id = $(this).data('id');
            const label = $(this).data('label');
            $(options.hiddenIdSelector).val(id);
            $(options.searchSelector).val(label);
            list.removeClass('show');
        }
    });
}

let departureRows = [];
let selectedDepartureIds = new Set();
let departureSearchTimer = null;

function filterDepartureRows(rows) {
    const search = ($('#departureSearchInput').val() || '').trim().toLowerCase();

    if (!search) {
        return rows.slice();
    }

    return rows.filter(tx => {
        return [
            tx.transaction_date,
            tx.employee_code,
            tx.full_name,
            tx.department_name,
            tx.remarks
        ].some(value => String(value ?? '').toLowerCase().includes(search));
    });
}

function updateDepartureSelectionControls()
{
    const selectedCount = selectedDepartureIds.size;
    const rowCheckboxes = $('.departure-select-checkbox');
    const checkedCount = rowCheckboxes.filter(':checked').length;
    const selectAll = document.getElementById('selectAllDepartures');

    $('#selectedDeparturesText').text(`${selectedCount} selected`);
    $('#bulkDeleteDeparturesBtn').prop('disabled', selectedCount === 0);

    if (selectAll) {
        selectAll.checked = rowCheckboxes.length > 0 && checkedCount === rowCheckboxes.length;
        selectAll.indeterminate = checkedCount > 0 && checkedCount < rowCheckboxes.length;
    }
}

function toggleDepartureSelection(id, checked)
{
    if (checked) {
        selectedDepartureIds.add(String(id));
    } else {
        selectedDepartureIds.delete(String(id));
    }

    updateDepartureSelectionControls();
}

function toggleAllDepartures(checked)
{
    $('.departure-select-checkbox').each(function() {
        this.checked = checked;

        if (checked) {
            selectedDepartureIds.add(String(this.value));
        } else {
            selectedDepartureIds.delete(String(this.value));
        }
    });

    updateDepartureSelectionControls();
}

function resetDepartureFilters()
{
    $('#departureSearchInput').val('');
    selectedDepartureIds.clear();
    renderTransactionTable('departure', '#departureTable', departureRows);
}

function loadTransactions(type, tableSelector) {
    // build query params from filters (if present)
    const params = [];
    const dateFrom = $('#filterDateFrom').val();
    const dateTo = $('#filterDateTo').val();
    const department = $('#filterDepartment').val();
    const employee = $('#filterEmployee').val();

    if (dateFrom) params.push(`date_from=${encodeURIComponent(dateFrom)}`);
    if (dateTo) params.push(`date_to=${encodeURIComponent(dateTo)}`);
    if (department) params.push(`department_id=${encodeURIComponent(department)}`);
    if (employee) params.push(`employee_id=${encodeURIComponent(employee)}`);

    const url = params.length ? apiUrl(`api/transactions/index.php/type/${type}?${params.join('&')}`) : apiUrl(`api/transactions/index.php/type/${type}`);

    $.get(url, function(data) {
        const transactions = typeof data === 'string' ? JSON.parse(data) : data;
        const transactionType = tableSelector === '#arrivalTable' ? 'arrival' : 'departure';

        if (transactionType === 'departure') {
            departureRows = transactions;
            selectedDepartureIds.clear();
        }

        renderTransactionTable(transactionType, tableSelector, transactions);
    });
}

function renderTransactionTable(transactionType, tableSelector, transactions) {
    const isDeparture = transactionType === 'departure';
    const tableData = isDeparture ? filterDepartureRows(transactions) : transactions;

    renderPaginatedTable({
        data: tableData,
        tableSelector: tableSelector,
        currentPage: 1,
        perPage: 10,
        renderRow: function(tx) {
            const selected = selectedDepartureIds.has(String(tx.id)) ? 'checked' : '';
            const departureSelectCell = isDeparture ? `
                <td style="text-align:center;">
                    <input
                        type="checkbox"
                        class="departure-select-checkbox"
                        value="${tx.id}"
                        aria-label="Select departure"
                        onchange="toggleDepartureSelection(${tx.id}, this.checked)"
                        ${selected}>
                </td>
            ` : '';

            return `
                <tr>
                    ${departureSelectCell}
                    <td>${displayValue(tx.transaction_date)}</td>
                    <td>${displayValue(tx.employee_code)} - ${displayValue(tx.full_name)}</td>
                    <td>${displayValue(tx.department_name)}</td>
                    <td>${displayValue(tx.remarks)}</td>
                    <td style="white-space:nowrap;">
                        <button class="btn btn-sm btn-warning edit-transaction" data-id="${tx.id}" data-type="${transactionType}" title="Edit">
                            <i class="bi bi-pencil"></i> Edit
                        </button>
                        <button class="btn btn-sm btn-danger delete-transaction" data-id="${tx.id}" data-type="${transactionType}" title="Delete">
                            <i class="bi bi-trash"></i> Delete
                        </button>
                    </td>
                </tr>
            `;
        },
        sortColumns: isDeparture ? [
            { index: 1, key: 'transaction_date' },
            { index: 2, key: function(row) { return `${row.employee_code || ''} ${row.full_name || ''}`; } },
            { index: 3, key: 'department_name' },
            { index: 4, key: 'remarks' }
        ] : [
            { index: 0, key: 'transaction_date' },
            { index: 1, key: function(row) { return `${row.employee_code || ''} ${row.full_name || ''}`; } },
            { index: 2, key: 'department_name' },
            { index: 3, key: 'remarks' }
        ]
    });

    $(`${tableSelector} .edit-transaction`).on('click', function() {
        const transactionId = $(this).data('id');
        const transactionType = $(this).data('type');
        editTransaction(transactionId, transactionType);
    });

    $(`${tableSelector} .delete-transaction`).on('click', function() {
        const transactionId = $(this).data('id');
        const transactionType = $(this).data('type');
        deleteTransaction(transactionId, transactionType);
    });

    if (isDeparture) {
        updateDepartureSelectionControls();
    }
}

function editTransaction(transactionId, transactionType) {
    const url = apiUrl(`api/transactions/index.php/${transactionId}`);

    $.get(url, function(data) {
        const tx = typeof data === 'string' ? JSON.parse(data) : data;
        const formId = transactionType === 'arrival' ? '#arrivalForm' : '#departureForm';
        const modalId = transactionType === 'arrival' ? '#arrivalModal' : '#departureModal';
        const dateInputId = transactionType === 'arrival' ? '#arrival_transaction_date' : '#departure_transaction_date';
        const employeeSearchId = transactionType === 'arrival' ? '#arrival_employee_search' : '#departure_employee_search';
        const employeeIdInputId = transactionType === 'arrival' ? '#arrival_employee_id' : '#departure_employee_id';
        const transactionIdInputId = transactionType === 'arrival' ? '#arrival_transaction_id' : '#departure_transaction_id';
        const remarksInputId = transactionType === 'arrival' ? '#arrival_remarks' : '#departure_remarks';

        // Load the transaction data into form
        $(dateInputId).val(tx.transaction_date);
        $(employeeIdInputId).val(tx.employee_id);
        $(employeeSearchId).val(`${tx.employee_code} - ${tx.full_name}`);
        $(remarksInputId).val(tx.remarks || '');
        $(transactionIdInputId).val(tx.id);

        // Open modal
        const modal = new bootstrap.Modal(document.getElementById(modalId.replace('#', '')));
        modal.show();
    }).fail(function() {
        swalError('Failed to load transaction', 'Error');
    });
}

function submitTransaction(formSelector, url) {
    $(formSelector).on('submit', function(event) {
        event.preventDefault();

        const employeeIdField = $(this).find('input[name="employee_id"]');
        if (!employeeIdField.val()) {
            swalError('Please select or create an employee', 'Employee Required');
            return false;
        }

        const transactionId = $(this).find('input[name="transaction_id"]').val();
        const method = transactionId ? 'PUT' : 'POST';
        const requestUrl = transactionId ? apiUrl(`api/transactions/index.php/${transactionId}`) : url;

        $.ajax({
            url: requestUrl,
            type: method,
            data: $(this).serialize(),
            success: function(response) {
                const res = typeof response === 'string' ? JSON.parse(response) : response;
                if (res.success) {
                    loadTransactions('arrival', '#arrivalTable');
                    loadTransactions('departure', '#departureTable');
                    $('#arrivalModal, #departureModal').modal('hide');
                    $(formSelector)[0].reset();
                    $('#arrival_employee_search, #departure_employee_search').val('');
                    $('#arrival_employee_id, #departure_employee_id').val('');
                    $('#arrival_transaction_id, #departure_transaction_id').val('');
                    swalSuccess('Saved successfully');
                } else {
                    swalError(res.error || 'Unknown error', 'Save failed');
                }
            },
            error: function() {
                swalError('Request failed', 'Error');
            }
        });
    });
}

function deleteTransaction(transactionId, transactionType) {
    const label = transactionType === 'arrival' ? 'arrival' : 'departure';

    swalConfirm(`Delete this ${label} record?`, function() {
        $.ajax({
            url: apiUrl(`api/transactions/index.php/${transactionId}`),
            type: 'DELETE',
            success: function(response) {
                const res = typeof response === 'string' ? JSON.parse(response) : response;
                if (res.success) {
                    loadTransactions('arrival', '#arrivalTable');
                    loadTransactions('departure', '#departureTable');
                    loadTransactionOptions('', 'departure');

                    const arrivalDate = $('#arrival_transaction_date').val() || '';
                    loadTransactionOptions(arrivalDate, 'arrival');

                    swalSuccess('Deleted successfully');
                } else {
                    swalError(res.error || 'Unknown error', 'Delete failed');
                }
            },
            error: function() {
                swalError('Request failed', 'Error');
            }
        });
    });
}

$(function() {
    if ($('#arrivalForm').length) {
        const today = $('#arrival_transaction_date').val() || '';
        loadTransactionOptions(today, 'arrival');
        loadTransactions('arrival', '#arrivalTable');
        submitTransaction('#arrivalForm', apiUrl('api/transactions/arrival'));

        // Clear transaction_id when opening new record modal
        $('[data-bs-target="#arrivalModal"]').on('click', function() {
            $('#arrival_transaction_id').val('');
            $('#arrivalForm')[0].reset();
            $('#arrival_employee_search').val('');
            $('#arrival_employee_id').val('');
        });
    }

    if ($('#departureForm').length) {
        loadTransactionOptions('', 'departure');
        loadTransactions('departure', '#departureTable');
        submitTransaction('#departureForm', apiUrl('api/transactions/departure'));

        // Clear transaction_id when opening new record modal
        $('[data-bs-target="#departureModal"]').on('click', function() {
            $('#departure_transaction_id').val('');
            $('#departureForm')[0].reset();
            $('#departure_employee_search').val('');
            $('#departure_employee_id').val('');
        });
    }
});

// Handle search input
$(function() {
    function initializeSearch(fieldPrefix, options) {
        const searchSelector = `#${fieldPrefix}_employee_search`;
        const listSelector = `#${fieldPrefix}_employee_list`;

        $(searchSelector).on('input', function() {
            const searchTerm = $(this).val();
            const employees = window[`${fieldPrefix}Employees`];
            if (employees) {
                renderEmployeeList(searchTerm, employees, options);
            }
        });

        $(searchSelector).on('focus', function() {
            $(listSelector).addClass('show');
        });
    }

    if ($('#arrival_employee_search').length) {
        initializeSearch('arrival', {
            listSelector: '#arrival_employee_list',
            searchSelector: '#arrival_employee_search',
            hiddenIdSelector: '#arrival_employee_id',
            allowCreate: true,
            showAll: () => $('#arrival_show_all').is(':checked')
        });
    }

    if ($('#departure_employee_search').length) {
        initializeSearch('departure', {
            listSelector: '#departure_employee_list',
            searchSelector: '#departure_employee_search',
            hiddenIdSelector: '#departure_employee_id',
            allowCreate: false,
            showAll: false
        });
    }

    // Close dropdown when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.search-input-wrapper').length) {
            $('#arrival_employee_list').removeClass('show');
            $('#departure_employee_list').removeClass('show');
        }
    });
});

// Refresh arrival employee dropdown when date changes or when modal opens
$(function() {
    $('#arrivalModal').on('show.bs.modal', function() {
        const date = $('#arrival_transaction_date').val() || '';
        loadTransactionOptions(date, 'arrival');
    });

    $('#arrival_transaction_date').on('change', function() {
        const date = $(this).val();
        loadTransactionOptions(date, 'arrival');
    });
    
    $('#arrival_show_all').on('change', function() {
        const date = $('#arrival_transaction_date').val() || '';
        loadTransactionOptions(date, 'arrival');
    });

    $('#departureModal').on('show.bs.modal', function() {
        loadTransactionOptions('', 'departure');
    });
});

// Load departments for new employee modal
function loadDepartmentsForNewEmployee() {
    $.get(apiUrl('api/departments/index.php'), function(data) {
        const depts = typeof data === 'string' ? JSON.parse(data) : data;
        let opts = '<option value="">Select department</option>';
        depts.forEach(d => {
            opts += `<option value="${d.id}">${d.department_name}</option>`;
        });
        $('#new_employee_department').html(opts);
    });
}

// Handle create employee form submission
$(function() {
    loadDepartmentsForNewEmployee();
    
    $('#createEmployeeForm').on('submit', function(e) {
        e.preventDefault();
        
        $.post(apiUrl('api/employees/index.php'), {
            employee_code: $('#new_employee_code').val(),
            full_name: $('#new_employee_name').val(),
            gender: $('#new_employee_gender').val(),
            department_id: $('#new_employee_department').val(),
            status: 'Active'
        }, function(response) {
            const res = typeof response === 'string' ? JSON.parse(response) : response;
            if (res.success) {
                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('createEmployeeModal'));
                modal.hide();
                
                // Reload dropdown
                const date = $('#arrival_transaction_date').val() || '';
                loadTransactionOptions(date, 'arrival');
                
                swalSuccess('Employee created successfully!');
            } else {
                swalError('Error creating employee: ' + (res.error || 'Unknown error'));
            }
        });
    });
});

// Populate department filter
function loadDepartmentsForFilter() {
    $.get(apiUrl('api/departments/index.php'), function(data) {
        const depts = typeof data === 'string' ? JSON.parse(data) : data;
        let opts = '<option value="">All departments</option>';
        depts.forEach(d => {
            opts += `<option value="${d.id}">${d.department_name}</option>`;
        });
        $('#filterDepartment').html(opts);
    });
}

$(function() {
    if ($('#arrivalFilterForm').length) {
        loadDepartmentsForFilter();
        $('#applyArrivalFilters').on('click', function() {
            loadTransactions('arrival', '#arrivalTable');
        });
        $('#resetArrivalFilters').on('click', function() {
            $('#filterDateFrom').val('');
            $('#filterDateTo').val('');
            $('#filterDepartment').val('');
            loadTransactions('arrival', '#arrivalTable');
        });
    }
});
