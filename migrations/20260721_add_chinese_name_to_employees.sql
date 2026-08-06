-- Migration: add chinese_name to employees
-- Date: 2026-07-21

ALTER TABLE employees
  ADD COLUMN IF NOT EXISTS chinese_name varchar(150) DEFAULT NULL;
