<?php

require_once __DIR__ . '/../models/TripLeg.php';
require_once __DIR__ . '/../models/Trip.php';
require_once __DIR__ . '/../models/Employee.php';

class TripLegController
{
    private $tripLeg;
    private $trip;
    private $employee;
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
        $this->tripLeg = new TripLeg($db);
        $this->trip = new Trip($db);
        $this->employee = new Employee($db);
    }

    public function index($tripId = null)
    {
        if (!$tripId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Trip ID is required.']);
            return;
        }

        // Verify trip exists
        $trip = $this->trip->getById($tripId);
        if (!$trip) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Trip not found.']);
            return;
        }

        $filters = ['trip_id' => $tripId];
        $result = $this->tripLeg->getAll($filters);
        echo json_encode($result);
    }

    public function show($id)
    {
        $leg = $this->tripLeg->getById($id);
        if (!$leg) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Trip leg not found.']);
            return;
        }

        echo json_encode($leg);
    }

    public function store($tripId = null)
    {
        if (!$tripId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Trip ID is required.']);
            return;
        }

        // Verify trip exists
        $trip = $this->trip->getById($tripId);
        if (!$trip) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Trip not found.']);
            return;
        }

        $data = $_POST;
        $data['trip_id'] = $tripId;

        $legType = strtoupper(trim((string) ($data['leg_type'] ?? '')));
        if (!in_array($legType, ['ARRIVAL', 'DEPARTURE'], true)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid leg type. Allowed: ARRIVAL, DEPARTURE']);
            return;
        }

        $legDate = trim((string) ($data['leg_date'] ?? ''));
        if (!$this->isValidDate($legDate)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid leg date format. Use YYYY-MM-DD.']);
            return;
        }

        $data['leg_type'] = $legType;
        $data['leg_date'] = $legDate;
        $existingLegs = $this->tripLeg->getByTripId($tripId);
        foreach ($existingLegs as $existingLeg) {
            if ($existingLeg['leg_type'] === $legType) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Trip already has an ' . strtolower($legType) . ' leg.']);
                return;
            }
        }

        $dateOrderError = $this->validateDateOrder($trip['trip_type'], $legType, $legDate, $existingLegs);
        if ($dateOrderError !== null) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $dateOrderError]);
            return;
        }

        $result = $this->tripLeg->create($data);

        if (!$result['success']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $result['error']]);
            return;
        }

        $this->trip->recalculateStoredStatus($tripId);
        $this->employee->syncStatusesByTransactions(date('Y-m-d'), $trip['employee_id']);
        echo json_encode([
            'success' => true,
            'message' => 'Trip leg created successfully.',
            'id' => $result['id']
        ]);
    }

    public function update($id)
    {
        parse_str(file_get_contents('php://input'), $data);

        $leg = $this->tripLeg->getById($id);
        if (!$leg) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Trip leg not found.']);
            return;
        }

        $result = $this->tripLeg->update($id, $data);

        if (!$result['success']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $result['error']]);
            return;
        }

        $this->trip->recalculateStoredStatus($leg['trip_id']);
        $this->employee->syncStatusesByTransactions(date('Y-m-d'), $leg['employee_id']);
        echo json_encode(['success' => true, 'message' => 'Trip leg updated successfully.']);
    }

    public function destroy($id)
    {
        $leg = $this->tripLeg->getById($id);
        if (!$leg) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Trip leg not found.']);
            return;
        }

        if ($this->legHasTransportation($id)) {
            http_response_code(409);
            echo json_encode(['success' => false, 'error' => 'This leg has a transportation request. Delete the transportation first.']);
            return;
        }

        if (count($this->tripLeg->getByTripId($leg['trip_id'])) <= 1) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'A trip must keep at least one arrival or departure.']);
            return;
        }

        $result = $this->tripLeg->delete($id);

        if (!$result['success']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $result['error']]);
            return;
        }

        $this->trip->recalculateStoredStatus($leg['trip_id']);
        $this->employee->syncStatusesByTransactions(date('Y-m-d'), $leg['employee_id']);
        echo json_encode(['success' => true, 'message' => 'Trip leg deleted successfully.']);
    }

    private function legHasTransportation($legId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM transportation_requests WHERE trip_leg_id = ?'
        );
        $stmt->execute([$legId]);

        return (int) $stmt->fetchColumn() > 0;
    }

    private function validateDateOrder(string $tripType, string $legType, string $legDate, array $existingLegs): ?string
    {
        $otherType = $legType === 'ARRIVAL' ? 'DEPARTURE' : 'ARRIVAL';
        foreach ($existingLegs as $existingLeg) {
            if ($existingLeg['leg_type'] !== $otherType) {
                continue;
            }

            $arrivalDate = $legType === 'ARRIVAL' ? $legDate : $existingLeg['leg_date'];
            $departureDate = $legType === 'DEPARTURE' ? $legDate : $existingLeg['leg_date'];

            if ($tripType === 'NORMAL_TRIP' && $arrivalDate > $departureDate) {
                return 'For NORMAL_TRIP, arrival date must be <= departure date.';
            }
            if ($tripType === 'ROUND_TRIP' && $departureDate > $arrivalDate) {
                return 'For ROUND_TRIP, departure date must be <= arrival date.';
            }
        }

        return null;
    }

    private function isValidDate(string $date): bool
    {
        $parsed = DateTime::createFromFormat('!Y-m-d', $date);
        return $parsed !== false && $parsed->format('Y-m-d') === $date;
    }
}
