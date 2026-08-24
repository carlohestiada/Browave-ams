# BROWAVE AMS PostgreSQL Migration Notes

## Summary

The application configuration and core PHP models were updated to use PostgreSQL-compatible behavior without changing the business logic.

## What changed

- Standardized database configuration to PostgreSQL in the shared environment files.
- Updated the shared PDO connection layer to use the `pgsql` driver and set the timezone to `Asia/Manila` using PostgreSQL syntax.
- Replaced MariaDB-specific SQL patterns such as `NOW()`, `DATE_SUB`, and MySQL-style column checks with PostgreSQL-compatible equivalents.
- Updated migration SQL files to remove MySQL-only syntax such as `AUTO_INCREMENT`, `ENGINE=InnoDB`, `ON UPDATE CURRENT_TIMESTAMP`, and enum declarations where appropriate.

## Recommended migration path

1. Create the PostgreSQL database:
   ```sql
   CREATE DATABASE browave_ams;
   ```
2. Run the PostgreSQL-compatible migration scripts in the `migrations/` folder.
3. Load the existing data using pgloader:
   ```bash
   pgloader mysql://root@localhost/browave_ams \
     postgresql://postgres:postgres@localhost/browave_ams
   ```
4. Validate the app modules in the order requested:
   - Database connection
   - Authentication/login
   - Dashboard
   - Employees
   - Departments
   - Rooms
   - Room assignments
   - Arrivals
   - Departures
   - Meal planning
   - Reports
   - User management

## Notes

- Local development and production now use the same database engine.
- Only host, port, username, and password should differ between environments.
- The codebase was kept as close to the original structure as possible to reduce risk.
