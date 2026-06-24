-- Testing SQL queries for employees.php
USE `browave_ams`;

-- 1) Insert sample employee record into employees table
INSERT INTO `employees` (`employee_code`, `full_name`, `gender`, `department_id`, `status`)
VALUES ('EMP-001', 'John Doe', 'Male', 1, 'Active');

-- 2) Display employees with department name for employees.php
SELECT
  e.id,
  e.employee_code,
  e.full_name,
  e.gender,
  d.department_name,
  e.status,
  e.created_at
FROM `employees` e
LEFT JOIN `departments` d ON e.department_id = d.id
ORDER BY e.id DESC;
