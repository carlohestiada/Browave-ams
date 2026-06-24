# BROWAVE AMS - Detailed Software Development Specification (SDS) v3

## Executive Summary

BROWAVE AMS is a web-based workforce, accommodation, and meal management system that automates employee arrivals, departures, room assignments, occupancy tracking, and meal headcount computation.

## Technology Stack

- Frontend: HTML5, CSS3, Bootstrap, JavaScript, AJAX
- Backend: PHP 8+
- Server: Apache (XAMPP)
- Database: MySQL/MariaDB

## Core Modules

- Employee Management
- Arrival & Departure
- Accommodation Management
- Room Management
- Meal Planning
- Department Management
- Dashboard
- Reports
- User Management
- Audit Logs

## Detailed Accommodation Features

### Accommodation Types

- Hotel: full-service accommodation with rooms, common spaces, and guest support.
- Dormitory: shared accommodation oriented for staff, with grouped units and communal facilities.

### Accommodation Hierarchy

- Accommodation: top-level property, such as a hotel or dormitory campus.
- Building: one or more buildings within an accommodation property.
- Floor: each building contains floors or zones.
- Room: individual room units assigned to employees.

### Accommodation Management Features

- Create, edit, and deactivate accommodations, buildings, floors, and rooms.
- Support multiple accommodation properties per account for multi-site operations.
- Maintain address, contact person, contact number, accommodation type, and status.
- Provide a hierarchical view and navigation to locate rooms by facility, building, and floor.
- Display accommodation occupancy summary with total rooms, occupied rooms, available rooms, and gender distribution.

### Room Properties and Metadata

- Room Number / Identifier
- Floor Location (1st Floor, 2nd Floor, etc.)
- Room Type (Single, Double, Triple, Quadruple, Suite)
- Capacity (maximum occupants)
- Current Occupancy (live count of active room assignments)
- Room Status (Available, Occupied, Reserved, Maintenance, Inactive)
- Gender Restriction (Male, Female, Mixed, None)
- Remarks / Notes for housekeeping or special conditions
- Room Category (Standard, Deluxe, Executive, Shared)
- Room Amenities tags (optional extension)

### Operational Rules and Validation

- Enforce capacity: assign only up to the defined room capacity.
- Auto update room occupancy on arrival, departure, room transfer, and accommodation transfer.
- Prevent assignment if a room is marked Maintenance or Inactive.
- Respect gender restriction rules when assigning employees to shared rooms.
- Support valid transfers:
  - Room transfer within the same accommodation/building/floor.
  - Accommodation transfer to a different accommodation property.
- Allow room reservation logic for planned arrivals with reserved status.
- Provide warnings for near-capacity rooms and over-capacity assignment attempts.

### Room Assignment and Transfer Workflow

- Assign employee to an available room during arrival or later.
- Record assignment details, including check-in date, expected checkout date, and status.
- Release room on departure and update occupancy counts immediately.
- Support transfer actions with audit trail for source and destination rooms.
- Preserve employee assignment history for room utilization reports.

### Availability and Reporting Features

- Real-time availability dashboard for each accommodation and building.
- Search and filter rooms by type, status, capacity, occupancy, floor, and gender restriction.
- Occupancy indicators: available, partially occupied, full, maintenance.
- Reports for room utilization, vacancy rates, gender mix, and accommodation capacity.
- Integrate room occupancy data with meal planning and headcount calculations.

### User Interface Requirements

- Accommodation screen with property list, building list, floor view, and room grid.
- Visual occupancy indicators and room status badges.
- Quick action controls for check-in, check-out, transfer, and room details.
- Filter panel with accommodation, building, floor, room type, status, and gender restriction.
- Modal or inline form for room creation and editing.

### Audit and Compliance

- Track changes to room assignments, transfers, and status updates.
- Log accommodation-level updates including building/floor changes and room metadata edits.
- Use audit logs for troubleshooting occupancy and assignment discrepancies.

## Detailed Database Schema

### users

- id
- username
- password_hash
- role
- status
- created_at

### employees

- id
- employee_code
- full_name
- gender
- department_id
- status
- created_at

### departments

- id
- department_name

### accommodations

- id
- accommodation_name
- accommodation_type
- address
- contact_person
- contact_number
- status

### buildings

- id
- accommodation_id
- building_name

### floors

- id
- building_id
- floor_name

### rooms

- id
- floor_id
- room_no
- room_type
- capacity
- current_occupancy
- status

### room_assignments

- id
- employee_id
- room_id
- checkin_date
- expected_checkout_date
- actual_checkout_date
- status

### transactions

- id
- employee_id
- transaction_type
- transaction_date
- remarks

### daily_headcount

- id
- date
- active_count
- meal_count

## Use Cases

- UC-01 Login
- UC-02 Manage Employees
- UC-03 Record Arrival
- UC-04 Record Departure
- UC-05 Assign Hotel/Dormitory
- UC-06 Transfer Room
- UC-07 Generate Reports
- UC-08 View Dashboard
- UC-09 Manage Departments
- UC-10 Manage Rooms

## API Contracts

- POST /api/auth/login
- GET /api/employees
- POST /api/employees
- PUT /api/employees/{id}
- POST /api/arrivals
- POST /api/departures
- GET /api/accommodations
- POST /api/accommodations
- GET /api/rooms
- POST /api/rooms
- POST /api/room-assignments
- POST /api/room-transfer
- GET /api/headcount
- GET /api/reports/headcount
- GET /api/reports/occupancy

## Sequence Diagram - Arrival

- HR/Admin -> System: Submit arrival record with employee details and planned stay
- System -> Employee Module: Validate employee identity and current status
- System -> Accommodation Module: Verify available rooms and gender restriction
- System -> Room Assignment Module: Assign an available room and reserve if needed
- System -> Room Module: Update room occupancy and status
- System -> Meal Planning Module: Recalculate meal headcount for the arrival date
- System -> Audit Log: Record arrival event, assignment, and user action
- System -> Dashboard: Refresh statistics for arrivals, occupied rooms, and meal count
- System -> Response -> HR/Admin: Confirm successful arrival registration or show room availability issues

## Sequence Diagram - Departure

- HR/Admin -> System
- System -> Save Departure
- System -> Release Room
- System -> Update Occupancy
- System -> Recalculate Headcount
- System -> Update Meal Count
- Dashboard Updated

## UI Specifications

### Dashboard

- Total Employees
- Active Employees
- Arrivals Today
- Departures Today
- Meal Headcount
- Occupied Rooms
- Available Rooms

### Accommodation Screen

- Hotel/Dormitory List
- Building List
- Floor View
- Room Grid
- Occupancy Indicators

### Employee Screen

- Employee Profile
- Assignment History
- Arrival/Departure History

## Performance Requirements

- Dashboard <= 3 seconds
- Login <= 2 seconds
- Search <= 2 seconds
- Reports <= 5 seconds
- Pagination required
- Indexed database queries

## Security Requirements

- Password Hashing
- RBAC Authorization
- Prepared Statements
- CSRF Protection
- XSS Protection
- Audit Logging
- HTTPS in Production

## Coding Standards

- PSR-12 Standard
- MVC Architecture
- Service Layer Pattern
- Repository Pattern (Optional)
- CamelCase Variables
- PascalCase Classes
- snake_case Database Tables

## Scalability Requirements

- Support 5,000+ Employees
- 100+ Concurrent Users
- Multi-Site Ready
- Cloud Deployment Ready
- REST API Ready

## Development Workflow

- Git Flow
- Feature Branches
- Pull Requests
- Code Reviews
- Testing Before Merge
- Definition of Done Required
