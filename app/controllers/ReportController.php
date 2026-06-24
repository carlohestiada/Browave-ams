<?php

require_once __DIR__ . '/../models/Report.php';
require_once __DIR__ . '/../models/Employee.php';

class ReportController
{
    private $report;
    private $employee;

    public function __construct($db)
    {
        $this->report = new Report($db);
        $this->employee = new Employee($db);
    }

    public function headcount()
    {
        $this->employee->syncStatusesByTransactions();

        $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $_GET['end_date'] ?? date('Y-m-d');

        echo json_encode($this->report->getHeadcountReport($startDate, $endDate));
    }

    public function occupancy()
    {
        echo json_encode($this->report->getOccupancyReport());
    }

    public function occupancyByAccommodation()
    {
        echo json_encode($this->report->getOccupancyByAccommodation());
    }

    public function arrivalDeparture()
    {
        $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $_GET['end_date'] ?? date('Y-m-d');

        echo json_encode($this->report->getEmployeeArrivalDeparture($startDate, $endDate));
    }

    public function summary()
    {
        $this->employee->syncStatusesByTransactions();

        $date = $_GET['date'] ?? date('Y-m-d');

        echo json_encode($this->report->getSummaryStats($date));
    }
}
