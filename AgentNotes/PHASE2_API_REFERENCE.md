# Phase 2 Trip Management API — Quick Reference

## Base URL
```
http://localhost/Browave-ams/public/api/
```

## Authentication
All endpoints require:
- Valid session (`$_SESSION['user_id']` set)
- Valid CSRF token in request (for POST/PUT/DELETE)

## Trip Endpoints

### List All Trips
**GET** `/trips`

Query Parameters:
- `employee_id`: Filter by employee
- `trip_type`: NORMAL_TRIP or ROUND_TRIP
- `status`: PLANNED, ACTIVE, COMPLETED, CANCELLED
- `search`: Search employee code or name

Response: Array of trips

```bash
curl http://localhost/Browave-ams/public/api/trips
curl "http://localhost/Browave-ams/public/api/trips?employee_id=1"
curl "http://localhost/Browave-ams/public/api/trips?status=PLANNED"
```

---

### Get Trip Details with Legs
**GET** `/trips/{id}`

Response: Trip object with embedded `legs` array

```bash
curl http://localhost/Browave-ams/public/api/trips/1
```

---

### Create Trip with Legs
**POST** `/trips`

Required Fields:
- `employee_id` (integer)
- `trip_type` (NORMAL_TRIP or ROUND_TRIP)
- `legs` (JSON string containing array of leg objects)

Optional Fields:
- `status` (default: PLANNED) - PLANNED, ACTIVE, COMPLETED, CANCELLED
- `remarks` (string)

Leg Object Structure:
```json
{
  "leg_type": "ARRIVAL or DEPARTURE",
  "leg_date": "YYYY-MM-DD",
  "origin": "string (required)",
  "destination": "string (required)",
  "arrival_airport": "string (optional)",
  "departure_airport": "string (optional)",
  "remarks": "string (optional)"
}
```

Example Request (using form data):
```bash
curl -X POST http://localhost/Browave-ams/public/api/trips \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "employee_id=1" \
  -d "trip_type=NORMAL_TRIP" \
  -d "status=PLANNED" \
  -d 'legs=[{"leg_type":"ARRIVAL","leg_date":"2026-08-20","origin":"Taiwan","destination":"Subic"},{"leg_type":"DEPARTURE","leg_date":"2026-09-10","origin":"Subic","destination":"Taiwan"}]'
```

Validation Rules:
- **NORMAL_TRIP**: Must have exactly 1 ARRIVAL and 1 DEPARTURE leg in order. ARRIVAL date ≤ DEPARTURE date.
- **ROUND_TRIP**: Must have exactly 1 DEPARTURE and 1 ARRIVAL leg in order. DEPARTURE date ≤ ARRIVAL date.

---

### Update Trip
**PUT** `/trips/{id}`

Updatable Fields:
- `trip_type`
- `status`
- `remarks`

Example:
```bash
curl -X PUT http://localhost/Browave-ams/public/api/trips/1 \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "status=ACTIVE"
```

---

### Delete Trip
**DELETE** `/trips/{id}`

Behavior: Deletes trip and associated trip legs (cascading delete).

```bash
curl -X DELETE http://localhost/Browave-ams/public/api/trips/1
```

---

## Trip Legs Endpoints

### List Legs for Trip
**GET** `/trips/{trip_id}/legs`

Response: Array of leg objects for the specified trip

```bash
curl http://localhost/Browave-ams/public/api/trips/1/legs
```

---

### Create Leg for Trip
**POST** `/trips/{trip_id}/legs`

Required Fields:
- `leg_type` (ARRIVAL or DEPARTURE)
- `leg_date` (YYYY-MM-DD)
- `origin`
- `destination`

Optional Fields:
- `arrival_airport`
- `departure_airport`
- `remarks`

Example:
```bash
curl -X POST http://localhost/Browave-ams/public/api/trips/1/legs \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "leg_type=ARRIVAL" \
  -d "leg_date=2026-08-20" \
  -d "origin=Taiwan" \
  -d "destination=Subic"
```

---

### Get Leg Details
**GET** `/trip-legs/{id}`

Response: Leg object with associated trip and employee info

```bash
curl http://localhost/Browave-ams/public/api/trip-legs/1
```

---

### Update Leg
**PUT** `/trip-legs/{id}`

Updatable Fields:
- `leg_type`
- `leg_date`
- `origin`
- `destination`
- `arrival_airport`
- `departure_airport`
- `remarks`

Example:
```bash
curl -X PUT http://localhost/Browave-ams/public/api/trip-legs/1 \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "leg_date=2026-08-21" \
  -d "remarks=Updated arrival leg"
```

---

### Delete Leg
**DELETE** `/trip-legs/{id}`

```bash
curl -X DELETE http://localhost/Browave-ams/public/api/trip-legs/1
```

---

## Transportation Integration

The `transportation_requests` API now supports `trip_leg_id`:

### Create Transportation Request with Trip Leg
**POST** `/accommodations` (existing endpoint)

New Optional Field:
- `trip_leg_id`: Link transportation request to a trip leg

Example:
```bash
curl -X POST http://localhost/Browave-ams/public/api/accommodations \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "employee_id=1" \
  -d "transportation_type=Company Car" \
  -d "pickup_date=2026-08-20" \
  -d "pickup_time=08:00" \
  -d "pickup_location=Office" \
  -d "status=Pending" \
  -d "trip_leg_id=1"
```

Validation:
- If `trip_leg_id` is provided, it must exist and belong to the specified employee
- Existing transportation records with `trip_leg_id = NULL` continue to work unchanged

---

## Response Format

### Success Response
```json
{
  "success": true,
  "message": "Operation completed successfully",
  "data": {}
}
```

### Error Response
```json
{
  "success": false,
  "error": "Descriptive error message"
}
```

---

## HTTP Status Codes

- `200 OK`: GET successful
- `400 Bad Request`: Validation error or malformed request
- `401 Unauthorized`: Not authenticated
- `403 Forbidden`: Invalid CSRF token
- `404 Not Found`: Resource not found
- `405 Method Not Allowed`: Unsupported HTTP method

---

## Common Validation Errors

| Error | Cause | Solution |
|-------|-------|----------|
| "Employee does not exist" | Invalid employee_id | Verify employee ID exists in system |
| "Invalid trip type" | trip_type not NORMAL_TRIP or ROUND_TRIP | Use correct trip type |
| "For NORMAL_TRIP, arrival date must be <= departure date" | Date sequence error | Ensure ARRIVAL leg date ≤ DEPARTURE leg date |
| "Trip must have exactly 2 legs" | Wrong number of legs | Provide exactly 2 legs in array |
| "NORMAL_TRIP must have ARRIVAL leg followed by DEPARTURE leg" | Leg order error | Ensure correct leg order and type |
| "Trip leg does not belong to the specified employee" | Employee mismatch in transportation request | Use correct employee_id matching the trip |
| "Employee already has an active or planned trip" | Duplicate trip for employee | Complete or cancel existing trip first |

---

## Notes

- All dates must be in `YYYY-MM-DD` format (ISO 8601)
- Timestamps in responses are in `YYYY-MM-DD HH:MM:SS` format
- Trip creation is atomic: if any leg fails, entire transaction rolls back
- Legs can only be added/updated individually after trip creation (not via trip update)
- To change leg sequence/dates, delete and recreate legs
- Transportation requests with `trip_leg_id` are linked but function independently
