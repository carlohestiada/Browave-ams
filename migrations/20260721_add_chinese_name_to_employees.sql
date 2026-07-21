-- Migration: add chinese_name to employees
-- Date: 2026-07-21

ALTER TABLE `employees`
  ADD COLUMN `chinese_name` varchar(150) DEFAULT NULL AFTER `full_name`;
