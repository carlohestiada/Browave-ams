CREATE UNIQUE INDEX IF NOT EXISTS uq_departments_name_location
    ON departments (LOWER(department_name), LOWER(location));
