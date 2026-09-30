-- Migration 002: allow users to add their own transportation types
--
-- Problem: transportation_requests.transportation_type was a PostgreSQL ENUM
-- (transportation_requests_transportation_type) limited to 5 fixed values, so any
-- new type could not be saved without altering the database.
--
-- Fix:
--   1. Use the existing (empty) transportation_types table as the list of allowed types.
--   2. Seed it with the 5 original values so all existing rows stay valid.
--   3. Convert the column from ENUM to VARCHAR(100) and drop the ENUM type.
--
-- Run with the browave_ams schema on the search_path, e.g.:
--   psql -U postgres -d browave_ams -c "SET search_path TO browave_ams" -f migrations/002_transportation_type_user_defined.sql
-- or:  ALTER ROLE postgres IN DATABASE browave_ams SET search_path = browave_ams, public;
--
-- Safe to run more than once.

BEGIN;

-- Step 1: seed the default types (no-op if they already exist)
INSERT INTO transportation_types (transportation_name, is_active)
VALUES ('Company Car', TRUE),
       ('Airport Transfer', TRUE),
       ('Shuttle Service', TRUE),
       ('Private Hire', TRUE),
       ('Other', TRUE)
ON CONFLICT (transportation_name) DO NOTHING;

-- Step 2: names must be unique regardless of case ("shuttle" vs "Shuttle")
CREATE UNIQUE INDEX IF NOT EXISTS uq_transportation_types_name_ci
    ON transportation_types (LOWER(transportation_name));

-- Step 3: ENUM -> VARCHAR (only if it is still an ENUM)
DO $$
BEGIN
    IF EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = ANY (current_schemas(FALSE))
          AND table_name = 'transportation_requests'
          AND column_name = 'transportation_type'
          AND data_type = 'USER-DEFINED'
    ) THEN
        ALTER TABLE transportation_requests
            ALTER COLUMN transportation_type TYPE VARCHAR(100)
            USING transportation_type::TEXT;
    END IF;
END $$;

-- Step 4: the ENUM type is no longer used
DROP TYPE IF EXISTS transportation_requests_transportation_type;

COMMIT;
