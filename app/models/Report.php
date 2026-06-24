<?php

require_once __DIR__ . '/RoomAssignment.php';

class Report
{
    private $db;
    private $assignment;

    public function __construct($db)
    {
        $this->db = $db;
        $this->assignment = new RoomAssignment($db);
    }

    public function getHeadcountReport($startDate = null, $endDate = null)
    {
        $startDate = $startDate ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $endDate ?? date('Y-m-d');

        $stmt = $this->db->prepare(
            "SELECT * FROM daily_headcount WHERE date BETWEEN ? AND ? ORDER BY date ASC"
        );

        $stmt->execute([$startDate, $endDate]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getOccupancyReport()
    {
        $this->assignment->refreshRoomStatuses();

        $stmt = $this->db->query(
            "SELECT r.*, f.floor_name, b.building_name, a.accommodation_name
             FROM rooms r
             LEFT JOIN floors f ON r.floor_id = f.id
             LEFT JOIN buildings b ON f.building_id = b.id
             LEFT JOIN accommodations a ON b.accommodation_id = a.id
             ORDER BY a.accommodation_name, b.building_name, f.floor_name, r.room_no"
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getOccupancyByAccommodation()
    {
        $this->assignment->refreshRoomStatuses();

        $stmt = $this->db->query(
            "SELECT 
                a.id,
                a.accommodation_name,
                a.accommodation_type,
                COUNT(r.id) as total_rooms,
                SUM(r.capacity) as total_capacity,
                SUM(r.current_occupancy) as total_occupied,
                SUM(CASE WHEN r.status = 'Available' THEN 1 ELSE 0 END) as available_rooms,
                SUM(CASE WHEN r.status = 'Occupied' THEN 1 ELSE 0 END) as occupied_rooms
             FROM accommodations a
             LEFT JOIN buildings b ON a.id = b.accommodation_id
             LEFT JOIN floors f ON b.id = f.building_id
             LEFT JOIN rooms r ON f.id = r.floor_id
             GROUP BY a.id, a.accommodation_name, a.accommodation_type
             ORDER BY a.accommodation_name"
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getEmployeeArrivalDeparture($startDate = null, $endDate = null)
    {
        $startDate = $startDate ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $endDate ?? date('Y-m-d');

        $stmt = $this->db->prepare(
            "SELECT 
                DATE(t.transaction_date) AS transaction_date,
                t.transaction_type,
                COUNT(*) as count,
                GROUP_CONCAT(DISTINCT e.department_id) as departments
             FROM transactions t
             LEFT JOIN employees e ON t.employee_id = e.id
             WHERE DATE(t.transaction_date) BETWEEN ? AND ?
             GROUP BY DATE(t.transaction_date), t.transaction_type
             ORDER BY DATE(t.transaction_date) DESC"
        );

        $stmt->execute([$startDate, $endDate]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSummaryStats($date = null)
    {
        $this->assignment->refreshRoomStatuses();

        $date = $date ?? date('Y-m-d');

        $stats = [
            'total_employees' => 0,
            'active_employees' => 0,
            'arrivals_today' => 0,
            'departures_today' => 0,
            'meal_headcount' => 0,
            'total_rooms' => 0,
            'occupied_rooms' => 0,
            'available_rooms' => 0,
            'reserved_rooms' => 0,
            'maintenance_rooms' => 0
        ];

        // Total employees
        $stmt = $this->db->query("SELECT COUNT(*) as count FROM employees");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['total_employees'] = $result['count'];

        // Active employees
        $stmt = $this->db->query("SELECT COUNT(*) as count FROM employees WHERE status='Active'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['active_employees'] = $result['count'];

        // Arrivals today
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM transactions WHERE transaction_type='arrival' AND DATE(transaction_date)=?");
        $stmt->execute([$date]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['arrivals_today'] = $result['count'];

        // Departures today
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM transactions WHERE transaction_type='departure' AND DATE(transaction_date)=?");
        $stmt->execute([$date]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['departures_today'] = $result['count'];

        // Meal headcount
        $stmt = $this->db->prepare("SELECT meal_count FROM daily_headcount WHERE date=?");
        $stmt->execute([$date]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['meal_headcount'] = $result['meal_count'] ?? $stats['active_employees'];

        // Occupied rooms
        $stmt = $this->db->query("SELECT COUNT(*) as count FROM rooms WHERE status='Occupied'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['occupied_rooms'] = $result['count'];

        // Available rooms
        $stmt = $this->db->query("SELECT COUNT(*) as count FROM rooms WHERE status='Available'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['available_rooms'] = $result['count'];

        // Reserved rooms
        $stmt = $this->db->query("SELECT COUNT(*) as count FROM rooms WHERE status='Reserved'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['reserved_rooms'] = $result['count'];

        // Maintenance rooms
        $stmt = $this->db->query("SELECT COUNT(*) as count FROM rooms WHERE status='Maintenance'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['maintenance_rooms'] = $result['count'];

        // Total rooms
        $stmt = $this->db->query("SELECT COUNT(*) as count FROM rooms");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['total_rooms'] = $result['count'];

        return $stats;
    }
}
