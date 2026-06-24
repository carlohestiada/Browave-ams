-- Testing SQL queries for departments.php
USE `browave_ams`;

-- 1) Insert a sample department record
INSERT INTO `departments` (`department_name`) VALUES ('Sample Department');

-- 2) Select departments for display in departments.php
SELECT
  d.id,
  d.department_name
FROM `departments` d
ORDER BY d.department_name ASC;

-- 3) Update department name
UPDATE `departments` SET `department_name` = 'Updated Sample Department' WHERE `id` = 1;

-- 4) Delete department by id
DELETE FROM `departments` WHERE `id` = 1;
