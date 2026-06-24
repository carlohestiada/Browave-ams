-- Testing SQL queries for accommodations.php
USE `browave_ams`;

-- 1) Insert sample accommodation record
INSERT INTO `accommodations` (`accommodation_name`, `accommodation_type`, `address`, `contact_person`, `contact_number`, `status`)
VALUES ('Sample Hotel', 'Hotel', '123 Sample Rd', 'Jane Doe', '09171234567', 'Active');

-- 2) Select accommodations for display in accommodations.php
SELECT
  a.id,
  a.accommodation_name,
  a.accommodation_type,
  a.address,
  a.contact_person,
  a.contact_number,
  a.status
FROM `accommodations` a
ORDER BY a.accommodation_name ASC;
