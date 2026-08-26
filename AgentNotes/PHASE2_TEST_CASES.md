# Phase 2 — Backend Implementation Test Cases

## Setup

Before testing, ensure:
1. The Browave AMS application is running on Apache/XAMPP
2. PostgreSQL database is configured and migrations are applied
3. Authentication is working (session-based)
4. Database tables exist:
   - `trips`
   - `trip_legs`  
   - `transportation_requests` (with `trip_leg_id` column)

Base URL: `http://localhost/Browave-ams/public/api/`

## Test Case 1: Create Normal Trip

**Endpoint:** `POST /api/trips`

**Request:**
```json
{
  "employee_id": 1,
  "trip_type": "NORMAL_TRIP",
  "status": "PLANNED",
  "remarks": "Test normal trip",
  "legs": [
    {
      "leg_type": "ARRIVAL",
      "leg_date": "2026-08-20",
      "origin": "Taiwan/China",
      "destination": "Subic",
      "arrival_airport": "SIC",
      "remarks": "Arrival leg"
    },
    {
      "leg_type": "DEPARTURE",
      "leg_date": "2026-09-10",
      "origin": "Subic",
      "destination": "Taiwan/China",
      "departure_airport": "SIC",
      "remarks": "Departure leg"
    }
  ]
}
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Trip created successfully with 2 leg(s).",
  "trip_id": 1,
  "leg_ids": [1, 2]
}
```

**Verification:**
- 1 trip created with trip_id
- 2 trip legs created
- Trip status: PLANNED
- Leg 1: ARRIVAL (08/20/2026)
- Leg 2: DEPARTURE (09/10/2026)

---

## Test Case 2: Create Round Trip

**Endpoint:** `POST /api/trips`

**Request:**
```json
{
  "employee_id": 2,
  "trip_type": "ROUND_TRIP",
  "status": "PLANNED",
  "remarks": "Test round trip",
  "legs": [
    {
      "leg_type": "DEPARTURE",
      "leg_date": "2026-08-29",
      "origin": "Subic",
      "destination": "Taiwan",
      "departure_airport": "SIC",
      "remarks": "Departure leg"
    },
    {
      "leg_type": "ARRIVAL",
      "leg_date": "2026-09-07",
      "origin": "Taiwan",
      "destination": "Subic",
      "arrival_airport": "SIC",
      "remarks": "Arrival leg"
    }
  ]
}
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Trip created successfully with 2 leg(s).",
  "trip_id": 2,
  "leg_ids": [3, 4]
}
```

**Verification:**
- 1 trip created
- 2 legs created
- Leg 1: DEPARTURE (08/29/2026)
- Leg 2: ARRIVAL (09/07/2026)

---

## Test Case 3: Invalid Trip Type

**Endpoint:** `POST /api/trips`

**Request:**
```json
{
  "employee_id": 1,
  "trip_type": "INVALID_TYPE",
  "legs": [
    {"leg_type": "ARRIVAL", "leg_date": "2026-08-20", "origin": "A", "destination": "B"},
    {"leg_type": "DEPARTURE", "leg_date": "2026-09-10", "origin": "B", "destination": "A"}
  ]
}
```

**Expected Response:**
```json
{
  "success": false,
  "error": "Invalid trip type. Allowed: NORMAL_TRIP, ROUND_TRIP"
}
```

**HTTP Status:** 400 Bad Request

---

## Test Case 4: Invalid Employee

**Endpoint:** `POST /api/trips`

**Request:**
```json
{
  "employee_id": 99999,
  "trip_type": "NORMAL_TRIP",
  "legs": [
    {"leg_type": "ARRIVAL", "leg_date": "2026-08-20", "origin": "A", "destination": "B"},
    {"leg_type": "DEPARTURE", "leg_date": "2026-09-10", "origin": "B", "destination": "A"}
  ]
}
```

**Expected Response:**
```json
{
  "success": false,
  "error": "Employee does not exist."
}
```

**HTTP Status:** 400 Bad Request

---

## Test Case 5: Invalid Date Sequence for NORMAL_TRIP

**Endpoint:** `POST /api/trips`

**Request:**
```json
{
  "employee_id": 1,
  "trip_type": "NORMAL_TRIP",
  "legs": [
    {
      "leg_type": "ARRIVAL",
      "leg_date": "2026-09-10",
      "origin": "Taiwan",
      "destination": "Subic"
    },
    {
      "leg_type": "DEPARTURE",
      "leg_date": "2026-08-20",
      "origin": "Subic",
      "destination": "Taiwan"
    }
  ]
}
```

**Expected Response:**
```json
{
  "success": false,
  "error": "For NORMAL_TRIP, arrival date must be <= departure date."
}
```

**HTTP Status:** 400 Bad Request

---

## Test Case 6: Transaction Rollback

**Setup:** Create a trip and manually corrupt leg 2 data to cause insert failure.

**Expected Behavior:**
- If leg 2 creation fails, the entire transaction should rollback
- Trip should NOT be created
- No orphaned legs should exist

**Verification SQL:**
```sql
SELECT COUNT(*) FROM trips WHERE employee_id = X AND trip_type = 'NORMAL_TRIP' AND trip_id = Y;
SELECT COUNT(*) FROM trip_legs WHERE trip_id = Y;
```

Both should return 0.

---

## Test Case 7: Get Trip with Legs

**Endpoint:** `GET /api/trips/1`

**Expected Response:**
```json
{
  "id": 1,
  "employee_id": 1,
  "employee_code": "EMP001",
  "employee_name": "John Doe",
  "department_name": "Engineering",
  "trip_type": "NORMAL_TRIP",
  "status": "PLANNED",
  "remarks": "Test normal trip",
  "created_at": "2026-08-26 12:00:00",
  "updated_at": "2026-08-26 12:00:00",
  "legs": [
    {
      "id": 1,
      "trip_id": 1,
      "leg_type": "ARRIVAL",
      "leg_date": "2026-08-20",
      "origin": "Taiwan/China",
      "destination": "Subic",
      "arrival_airport": "SIC",
      "departure_airport": null,
      "remarks": "Arrival leg",
      "created_at": "2026-08-26 12:00:00",
      "updated_at": "2026-08-26 12:00:00"
    },
    {
      "id": 2,
      "trip_id": 1,
      "leg_type": "DEPARTURE",
      "leg_date": "2026-09-10",
      "origin": "Subic",
      "destination": "Taiwan/China",
      "arrival_airport": null,
      "departure_airport": "SIC",
      "remarks": "Departure leg",
      "created_at": "2026-08-26 12:00:00",
      "updated_at": "2026-08-26 12:00:00"
    }
  ]
}
```

---

## Test Case 8: List Legs for Trip

**Endpoint:** `GET /api/trips/1/legs`

**Expected Response:**
Array of trip legs with all fields populated.

---

## Test Case 9: Create Leg for Trip

**Endpoint:** `POST /api/trips/1/legs`

**Request:**
```json
{
  "leg_type": "ARRIVAL",
  "leg_date": "2026-10-15",
  "origin": "Singapore",
  "destination": "Subic",
  "arrival_airport": "SIN"
}
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Trip leg created successfully.",
  "id": 3
}
```

Note: This creates a 3rd leg; trip logic doesn't enforce 2-leg limit on individual adds.

---

## Test Case 10: Transportation Integration - Create with Trip Leg

**Endpoint:** `POST /api/accommodations`  
(Use existing transportation endpoint)

**Request with trip_leg_id:**
```json
{
  "employee_id": 1,
  "transportation_type": "Company Car",
  "driver_id": 1,
  "vehicle_id": 1,
  "pickup_date": "2026-08-20",
  "pickup_time": "08:00",
  "pickup_location": "Office",
  "status": "Pending",
  "trip_leg_id": 1
}
```

**Expected Behavior:**
- Transportation request created with trip_leg_id = 1
- Validates that trip leg 1 belongs to employee 1
- Stores relationship for future reference

---

## Test Case 11: Transportation Integration - Invalid Trip Leg Employee

**Request:**
```json
{
  "employee_id": 2,
  "transportation_type": "Company Car",
  "pickup_date": "2026-08-29",
  "pickup_time": "08:00",
  "pickup_location": "Office",
  "status": "Pending",
  "trip_leg_id": 1
}
```

**Expected Response:**
```json
{
  "success": false,
  "error": "Trip leg does not belong to the specified employee."
}
```

HTTP Status: 400 Bad Request

---

## Test Case 12: Update Trip Status

**Endpoint:** `PUT /api/trips/1`

**Request:**
```json
{
  "status": "ACTIVE"
}
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Trip updated successfully."
}
```

---

## Test Case 13: Update Trip Leg

**Endpoint:** `PUT /api/trip-legs/1`

**Request:**
```json
{
  "leg_date": "2026-08-21",
  "remarks": "Updated remarks"
}
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Trip leg updated successfully."
}
```

---

## Test Case 14: Delete Trip Leg

**Endpoint:** `DELETE /api/trip-legs/1`

**Expected Response:**
```json
{
  "success": true,
  "message": "Trip leg deleted successfully."
}
```

---

## Test Case 15: Delete Trip

**Endpoint:** `DELETE /api/trips/1`

**Expected Response:**
```json
{
  "success": true,
  "message": "Trip deleted successfully."
}
```

**Expected Behavior:**
- Trip deleted
- Associated legs should also be deleted (cascading delete via foreign keys)

---

## Test Case 16: Duplicate Active Trip Prevention

**Endpoint:** `POST /api/trips`

**Setup:** Create a trip with status PLANNED for employee 1.

**Request:** Try to create another trip for the same employee with status ACTIVE.

**Expected Response:**
```json
{
  "success": false,
  "error": "Employee already has an active or planned trip."
}
```

HTTP Status: 400 Bad Request

---

## Test Case 17: Missing Authentication

**Endpoint:** `GET /api/trips`

**Without session:** Access without logging in.

**Expected Response:**
```json
{
  "success": false,
  "error": "Authentication required."
}
```

HTTP Status: 401 Unauthorized

---

## Test Case 18: CSRF Token Validation

**Endpoint:** `POST /api/trips`

**Request:** POST without valid CSRF token in header.

**Expected Response:**
```json
{
  "success": false,
  "error": "Invalid CSRF token."
}
```

HTTP Status: 403 Forbidden

---

## Manual Testing Checklist

- [ ] Test all 18 test cases above
- [ ] Verify database transactions (rollback on error)
- [ ] Verify date validation for both trip types
- [ ] Verify foreign key relationships
- [ ] Verify RBAC (only authorized users can create/update/delete)
- [ ] Verify error messages are clear and not exposing sensitive data
- [ ] Test with both Firefox and Chrome dev tools network tab
- [ ] Verify response times are acceptable
- [ ] Check application logs for any SQL errors or exceptions
- [ ] Verify backward compatibility with existing transportation records (trip_leg_id = NULL)

---

## Files Modified/Created

### New Files
- `app/models/Trip.php`
- `app/models/TripLeg.php`
- `app/controllers/TripController.php`
- `app/controllers/TripLegController.php`
- `public/api/trips/index.php`
- `public/api/trip-legs/index.php`

### Modified Files
- `app/models/TransportationRequest.php` (added trip_leg_id support)

### No Database Changes Required
- Phase 1 database schema already includes trips, trip_legs, transportation_requests with trip_leg_id
