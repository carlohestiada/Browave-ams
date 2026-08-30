-- Phase 4 Migration: Add trip_leg_id to transportation_requests
-- This migration adds support for linking transportation requests to specific trip legs

-- Step 1: Add trip_leg_id column to transportation_requests (PostgreSQL syntax)
ALTER TABLE transportation_requests
ADD COLUMN IF NOT EXISTS trip_leg_id INT NULL;

-- Step 2: Add foreign key constraint to trip_legs table
ALTER TABLE transportation_requests
ADD CONSTRAINT IF NOT EXISTS fk_transportation_requests_trip_leg
FOREIGN KEY (trip_leg_id) REFERENCES trip_legs(id) ON DELETE SET NULL;

-- Step 3: Add index for efficient queries by trip_leg
CREATE INDEX IF NOT EXISTS idx_transportation_requests_trip_leg
ON transportation_requests(trip_leg_id);

-- Step 4: Add index for efficient queries by employee and trip_leg (for validation)
CREATE INDEX IF NOT EXISTS idx_transportation_requests_employee_trip
ON transportation_requests(employee_id, trip_leg_id);

-- Note: Existing transportation records will have trip_leg_id = NULL (legacy records)
-- These records continue to work as before but are not associated with any trip leg
