function loadSummary()
{
    $.get('api/reports.php?action=summary', function(data) {
        const stats = data;
        let cards = '';

        cards += `
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h6>Total Employees</h6>
                        <h3 class="text-primary">${stats.total_employees}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h6>Active Employees</h6>
                        <h3 class="text-success">${stats.active_employees}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h6>Meal Headcount</h6>
                        <h3 class="text-info">${stats.meal_headcount}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h6>Arrivals Today</h6>
                        <h3 class="text-warning">${stats.arrivals_today}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h6>Departures Today</h6>
                        <h3 class="text-danger">${stats.departures_today}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h6>Occupied Rooms</h6>
                        <h3>${stats.occupied_rooms}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h6>Available Rooms</h6>
                        <h3 class="text-success">${stats.available_rooms}</h3>
                    </div>
                </div>
            </div>
        `;

        $('#summaryCards').html(cards);
    });
}

function loadHeadcountReport()
{
    const startDate = $('#headcountStartDate').val() || new Date(new Date().setDate(new Date().getDate()-30)).toISOString().split('T')[0];
    const endDate = $('#headcountEndDate').val() || new Date().toISOString().split('T')[0];

    $.ajax({
        url: 'api/reports.php?action=headcount',
        method: 'GET',
        dataType: 'json',
        data: {
            start_date: startDate,
            end_date: endDate
        }
    }).done(function(data) {
        const records = Array.isArray(data) ? data : [];

        renderPaginatedTable({
            data: records,
            tableSelector: '#headcountTable',
            currentPage: 1,
            perPage: 10,
            renderRow: function(record) {
                return `
                <tr>
                    <td>${record.date}</td>
                    <td>${record.active_count}</td>
                    <td>${record.meal_count}</td>
                </tr>
            `;
            },
            sortColumns: [
                { index: 0, key: 'date' },
                { index: 1, key: 'active_count' },
                { index: 2, key: 'meal_count' }
            ]
        });

        // Set default dates
        if (!$('#headcountStartDate').val()) {
            $('#headcountStartDate').val(startDate);
        }
        if (!$('#headcountEndDate').val()) {
            $('#headcountEndDate').val(endDate);
        }
    }).fail(function(xhr, status, error) {
        $('#headcountTable').html('<tr><td colspan="3" class="text-center">Unable to load headcount report.</td></tr>');
        console.error('Headcount report request failed:', status, error, xhr.responseText);
    });
}

function loadOccupancyReport()
{
    $.get('api/reports.php?action=occupancy-by-accommodation', function(data) {
        const records = data;

        renderPaginatedTable({
            data: records,
            tableSelector: '#occupancyTable',
            currentPage: 1,
            perPage: 10,
            renderRow: function(record) {
                const totalCapacity = record.total_capacity || 0;
                const totalOccupied = record.total_occupied || 0;
                const occupancyPercent = totalCapacity > 0 ? Math.round((totalOccupied / totalCapacity) * 100) : 0;

                return `
                    <tr>
                        <td>${record.accommodation_name}</td>
                        <td><span class="badge bg-primary">${record.accommodation_type}</span></td>
                        <td>${record.total_rooms}</td>
                        <td>${record.total_capacity}</td>
                        <td>${record.total_occupied}</td>
                        <td>${record.available_rooms}</td>
                        <td>
                            <div class="progress" style="height: 20px;">
                                <div class="progress-bar" role="progressbar" style="width: ${occupancyPercent}%" aria-valuenow="${occupancyPercent}" aria-valuemin="0" aria-valuemax="100">
                                    ${occupancyPercent}%
                                </div>
                            </div>
                        </td>
                    </tr>
                `;
            },
            sortColumns: [
                { index: 0, key: 'accommodation_name' },
                { index: 1, key: 'accommodation_type' },
                { index: 2, key: 'total_rooms' },
                { index: 3, key: 'total_capacity' },
                { index: 4, key: 'total_occupied' },
                { index: 5, key: 'available_rooms' },
                { index: 6, key: function(row) {
                    const totalCapacity = row.total_capacity || 0;
                    const totalOccupied = row.total_occupied || 0;
                    return totalCapacity > 0 ? Math.round((totalOccupied / totalCapacity) * 100) : 0;
                } }
            ]
        });
    });
}

function loadArrivalDepartureReport()
{
    const startDate = $('#arrivalStartDate').val() || new Date(new Date().setDate(new Date().getDate()-30)).toISOString().split('T')[0];
    const endDate = $('#arrivalEndDate').val() || new Date().toISOString().split('T')[0];

    $.get(`api/reports.php?action=arrival-departure&start_date=${startDate}&end_date=${endDate}`, function(data) {
        const records = data;

        renderPaginatedTable({
            data: records,
            tableSelector: '#arrivalTable',
            currentPage: 1,
            perPage: 10,
            renderRow: function(record) {
                return `
                    <tr>
                        <td>${record.transaction_date}</td>
                        <td><span class="badge ${record.transaction_type === 'arrival' ? 'bg-success' : 'bg-danger'}">${record.transaction_type.toUpperCase()}</span></td>
                        <td>${record.count}</td>
                    </tr>
                `;
            },
            sortColumns: [
                { index: 0, key: 'transaction_date' },
                { index: 1, key: 'transaction_type' },
                { index: 2, key: 'count' }
            ]
        });

        // Set default dates
        if (!$('#arrivalStartDate').val()) {
            $('#arrivalStartDate').val(startDate);
        }
        if (!$('#arrivalEndDate').val()) {
            $('#arrivalEndDate').val(endDate);
        }
    });
}

$(function() {
    loadSummary();
    loadHeadcountReport();
    loadOccupancyReport();
    loadArrivalDepartureReport();
});
