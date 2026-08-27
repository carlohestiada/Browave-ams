<?php

require_once __DIR__ . '/../models/Trip.php';
require_once __DIR__ . '/../models/TripLeg.php';

class TripController
{
    private $trip;
    private $tripLeg;
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
        $this->trip = new Trip($db);
        $this->tripLeg = new TripLeg($db);
    }

    public function index()
    {
        $filters = [
            'employee_id' => $_GET['employee_id'] ?? null,
            'department_id' => $_GET['department_id'] ?? null,
            'trip_type' => $_GET['trip_type'] ?? null,
            'status' => $_GET['status'] ?? null,
            'date_from' => $_GET['date_from'] ?? null,
            'date_to' => $_GET['date_to'] ?? null,
            'search' => $_GET['search'] ?? null,
        ];

        $result = $this->trip->getAll($filters);
        foreach ($result as &$trip) {
            $trip['legs'] = $this->tripLeg->getByTripId($trip['id']);
        }
        unset($trip);
        echo json_encode($result);
    }

    public function show($id)
    {
        $tripData = $this->trip->getById($id);
        if (!$tripData) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Trip not found.']);
            return;
        }

        // Get associated trip legs
        $legs = $this->tripLeg->getByTripId($id);

        $response = array_merge($tripData, ['legs' => $legs]);
        echo json_encode($response);
    }

    public function store()
    {
        $data = $_POST;

        // Validate trip data
        $tripResult = $this->validateTripData($data);
        if (!$tripResult['success']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $tripResult['error']]);
            return;
        }

        // Validate legs data
        $legsResult = $this->validateLegsData($data);
        if (!$legsResult['success']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $legsResult['error']]);
            return;
        }

        // Start transaction
        $this->db->beginTransaction();

        try {
            // Create trip
            $createTripResult = $this->trip->create([
                'employee_id' => $data['employee_id'],
                'trip_type' => $data['trip_type'],
                'status' => $data['status'] ?? 'PLANNED',
                'remarks' => $data['remarks'] ?? ''
            ]);

            if (!$createTripResult['success']) {
                throw new Exception($createTripResult['error']);
            }

            $tripId = $createTripResult['id'];

            // Parse legs JSON array
            $legs = json_decode($data['legs'] ?? '[]', true);
            if (!is_array($legs) || empty($legs)) {
                throw new Exception('No trip legs provided.');
            }

            // Create legs
            $createdLegIds = [];
            foreach ($legs as $legData) {
                $createLegResult = $this->tripLeg->create([
                    'trip_id' => $tripId,
                    'leg_type' => $legData['leg_type'],
                    'leg_date' => $legData['leg_date'],
                    'origin' => $legData['origin'],
                    'destination' => $legData['destination'],
                    'arrival_airport' => $legData['arrival_airport'] ?? null,
                    'departure_airport' => $legData['departure_airport'] ?? null,
                    'remarks' => $legData['remarks'] ?? ''
                ]);

                if (!$createLegResult['success']) {
                    throw new Exception('Leg creation failed: ' . $createLegResult['error']);
                }

                $createdLegIds[] = $createLegResult['id'];
            }

            $this->db->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Trip created successfully with ' . count($createdLegIds) . ' leg(s).',
                'trip_id' => $tripId,
                'leg_ids' => $createdLegIds
            ]);

        } catch (Exception $e) {
            $this->db->rollBack();
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function update($id)
    {
        parse_str(file_get_contents('php://input'), $data);

        // Only allow updating trip-level fields
        $updateData = [];
        if (isset($data['trip_type'])) {
            $updateData['trip_type'] = $data['trip_type'];
        }
        if (isset($data['status'])) {
            $updateData['status'] = $data['status'];
        }
        if (isset($data['remarks'])) {
            $updateData['remarks'] = $data['remarks'];
        }

        if (empty($updateData)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'No valid fields to update.']);
            return;
        }

        $result = $this->trip->update($id, $updateData);

        if (!$result['success']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $result['error']]);
            return;
        }

        echo json_encode(['success' => true, 'message' => 'Trip updated successfully.']);
    }

    public function destroy($id)
    {
        $result = $this->trip->delete($id);

        if (!$result['success']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $result['error']]);
            return;
        }

        echo json_encode(['success' => true, 'message' => 'Trip deleted successfully.']);
    }

    private function validateTripData(array $data)
    {
        if (empty($data['employee_id'])) {
            return ['success' => false, 'error' => 'Employee ID is required.'];
        }

        if (!is_numeric($data['employee_id'])) {
            return ['success' => false, 'error' => 'Invalid employee ID.'];
        }

        if (empty($data['trip_type'])) {
            return ['success' => false, 'error' => 'Trip type is required.'];
        }

        if (!in_array($data['trip_type'], ['NORMAL_TRIP', 'ROUND_TRIP'])) {
            return ['success' => false, 'error' => 'Invalid trip type. Allowed: NORMAL_TRIP, ROUND_TRIP'];
        }

        return ['success' => true];
    }

    private function validateLegsData(array $data)
    {
        $legs = json_decode($data['legs'] ?? '[]', true);

        if (!is_array($legs) || empty($legs)) {
            return ['success' => false, 'error' => 'Trip legs are required (send as JSON array).'];
        }

        if (count($legs) !== 2) {
            return ['success' => false, 'error' => 'Trip must have exactly 2 legs.'];
        }

        $tripType = $data['trip_type'];
        $legTypes = array_map(fn($leg) => $leg['leg_type'] ?? null, $legs);
        $legDates = array_map(fn($leg) => $leg['leg_date'] ?? null, $legs);

        // Validate leg types
        foreach ($legTypes as $type) {
            if (!in_array($type, ['ARRIVAL', 'DEPARTURE'])) {
                return ['success' => false, 'error' => 'Invalid leg type. Allowed: ARRIVAL, DEPARTURE'];
            }
        }

        // Validate leg dates
        foreach ($legDates as $date) {
            if (!$this->isValidDate($date)) {
                return ['success' => false, 'error' => 'Invalid leg date format. Use YYYY-MM-DD.'];
            }
        }

        if ($tripType === 'NORMAL_TRIP') {
            // Must have ARRIVAL first, then DEPARTURE
            if ($legTypes[0] !== 'ARRIVAL' || $legTypes[1] !== 'DEPARTURE') {
                return ['success' => false, 'error' => 'NORMAL_TRIP must have ARRIVAL leg followed by DEPARTURE leg.'];
            }

            // Arrival date must be <= departure date
            if ($legDates[0] > $legDates[1]) {
                return ['success' => false, 'error' => 'For NORMAL_TRIP, arrival date must be <= departure date.'];
            }
        } elseif ($tripType === 'ROUND_TRIP') {
            // Must have DEPARTURE first, then ARRIVAL
            if ($legTypes[0] !== 'DEPARTURE' || $legTypes[1] !== 'ARRIVAL') {
                return ['success' => false, 'error' => 'ROUND_TRIP must have DEPARTURE leg followed by ARRIVAL leg.'];
            }

            // Departure date must be <= arrival date
            if ($legDates[0] > $legDates[1]) {
                return ['success' => false, 'error' => 'For ROUND_TRIP, departure date must be <= arrival date.'];
            }
        }

        return ['success' => true];
    }

    private function isValidDate($date)
    {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
}
