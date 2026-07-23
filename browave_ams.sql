-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 24, 2026 at 01:32 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `browave_ams`
--

-- --------------------------------------------------------

--
-- Table structure for table `accommodations`
--

CREATE TABLE `accommodations` (
  `id` int(11) NOT NULL,
  `accommodation_name` varchar(100) NOT NULL,
  `accommodation_type` enum('Hotel','Dormitory') NOT NULL,
  `address` text DEFAULT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `contact_number` varchar(30) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `accommodations`
--

INSERT INTO `accommodations` (`id`, `accommodation_name`, `accommodation_type`, `address`, `contact_person`, `contact_number`, `status`) VALUES
(11, 'Mango Valley 2', 'Dormitory', '', '', '', 'Active'),
(15, 'Test Padding Accommodation', 'Hotel', NULL, NULL, NULL, 'Active'),
(16, 'Test Padding Accommodation', 'Hotel', NULL, NULL, NULL, 'Active'),
(17, 'Test Padding Accommodation', 'Hotel', NULL, NULL, NULL, 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `buildings`
--

CREATE TABLE `buildings` (
  `id` int(11) NOT NULL,
  `accommodation_id` int(11) NOT NULL,
  `building_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `buildings`
--

INSERT INTO `buildings` (`id`, `accommodation_id`, `building_name`) VALUES
(13, 11, 'Building 1'),
(17, 15, 'Test Padding Building'),
(18, 16, 'Test Padding Building'),
(19, 17, 'Test Padding Building');

-- --------------------------------------------------------

--
-- Table structure for table `company_car_departures`
--

CREATE TABLE `company_car_departures` (
  `id` int(11) NOT NULL,
  `assignment_id` int(11) NOT NULL,
  `transportation_type_id` int(11) NOT NULL,
  `vehicle_id` int(11) DEFAULT NULL,
  `driver_id` int(11) DEFAULT NULL,
  `pickup_date` date NOT NULL,
  `pickup_time` time DEFAULT NULL,
  `pickup_location` varchar(150) DEFAULT NULL,
  `destination` varchar(150) DEFAULT NULL,
  `status` enum('Pending','Scheduled','Completed','Cancelled') DEFAULT 'Pending',
  `remarks` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `company_car_departure_logs`
--

CREATE TABLE `company_car_departure_logs` (
  `id` int(11) NOT NULL,
  `company_car_departure_id` int(11) NOT NULL,
  `status` varchar(30) NOT NULL,
  `remarks` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `daily_headcount`
--

CREATE TABLE `daily_headcount` (
  `id` int(11) NOT NULL,
  `date` date NOT NULL,
  `active_count` int(11) NOT NULL DEFAULT 0,
  `meal_count` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `daily_headcount`
--

INSERT INTO `daily_headcount` (`id`, `date`, `active_count`, `meal_count`) VALUES
(78, '2026-07-01', 0, 0),
(83, '2026-07-26', 2, 2);

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `department_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `department_name`, `created_at`, `updated_at`) VALUES
(33, 'PT', '2026-07-01 06:57:00', '2026-07-01 06:57:00'),
(34, 'QA', '2026-07-01 08:54:44', '2026-07-01 08:54:44'),
(35, 'MI', '2026-07-01 08:54:49', '2026-07-01 08:54:49'),
(36, 'ADM', '2026-07-01 08:55:03', '2026-07-02 00:51:51'),
(37, 'SMD', '2026-07-02 00:51:21', '2026-07-02 00:51:21'),
(38, 'MR', '2026-07-02 00:51:28', '2026-07-02 00:51:28'),
(39, 'GO', '2026-07-02 00:51:37', '2026-07-02 00:51:37'),
(40, 'FA', '2026-07-02 00:52:03', '2026-07-02 00:52:03'),
(41, 'PMC', '2026-07-02 00:52:17', '2026-07-02 00:52:17'),
(42, 'PD1', '2026-07-02 00:54:12', '2026-07-02 00:54:12'),
(43, 'ADF', '2026-07-02 03:14:00', '2026-07-02 03:14:00'),
(44, 'ALL PD', '2026-07-02 03:20:11', '2026-07-02 03:20:11');

-- --------------------------------------------------------

--
-- Table structure for table `drivers`
--

CREATE TABLE `drivers` (
  `id` int(11) NOT NULL,
  `driver_name` varchar(100) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'Active',
  `contact_number` varchar(30) DEFAULT NULL,
  `license_number` varchar(50) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `drivers`
--

INSERT INTO `drivers` (`id`, `driver_name`, `phone`, `status`, `contact_number`, `license_number`, `remarks`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Wang Li', '09123456789', 'Active', '09123456789', 'N/A', 'Sample driver', 1, '2026-07-23 01:05:12', '2026-07-23 01:05:12'),
(2, 'Test Driver', '09999999999', 'Active', NULL, NULL, NULL, 1, '2026-07-23 01:09:47', '2026-07-23 01:09:47'),
(3, 'Carlo', '091212121', 'Active', NULL, NULL, NULL, 1, '2026-07-23 01:12:03', '2026-07-23 01:12:03'),
(4, 'dasdasd', 'asdasdsa', 'Active', NULL, NULL, NULL, 1, '2026-07-23 07:18:34', '2026-07-23 07:18:34'),
(5, 'adas', '3212313', 'Active', NULL, NULL, NULL, 1, '2026-07-23 23:19:35', '2026-07-23 23:19:35');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `employee_code` varchar(30) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `chinese_name` varchar(255) DEFAULT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `department_id` int(11) NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `employee_code`, `full_name`, `chinese_name`, `gender`, `department_id`, `status`, `created_at`) VALUES
(176, 'EMP-001', 'ZHENG JIAYI', NULL, 'Male', 33, 'Active', '2026-07-21 05:18:18'),
(177, 'EMP-002', 'LIU LIHAO', NULL, 'Female', 33, 'Active', '2026-07-21 05:18:18'),
(178, 'EMP-003', 'LI HE', NULL, 'Male', 33, 'Active', '2026-07-21 05:18:18'),
(179, 'EMP-004', 'LU SHUMIN', NULL, 'Female', 33, 'Active', '2026-07-21 05:18:18'),
(180, 'EMP-005', 'HU MEIYING', NULL, 'Male', 33, 'Active', '2026-07-21 05:18:18'),
(181, 'EMP-006', 'HUANG YOUPING', NULL, 'Female', 33, 'Active', '2026-07-21 05:18:18'),
(182, 'EMP-007', 'LIU ZHIQING', NULL, 'Male', 33, 'Active', '2026-07-21 05:18:18'),
(183, 'EMP-008', 'ZHANG HAO', NULL, 'Female', 33, 'Active', '2026-07-21 05:18:18'),
(184, 'EMP-009', 'LIN JUNHAO', NULL, 'Male', 33, 'Active', '2026-07-21 05:18:18'),
(185, 'EMP-010', 'DAI ZHIHUI', NULL, 'Female', 33, 'Active', '2026-07-21 05:18:18'),
(186, 'EMP-011', 'CHEN XUANWEN', NULL, 'Male', 33, 'Active', '2026-07-21 05:18:18'),
(187, 'EMP-012', 'HUANG CUIPING', NULL, 'Female', 33, 'Active', '2026-07-21 05:18:18'),
(188, 'EMP-013', 'LUO HUILIAN', NULL, 'Male', 33, 'Active', '2026-07-21 05:18:18'),
(189, 'EMP-014', 'TANG ZHIHUA', NULL, 'Female', 33, 'Active', '2026-07-21 05:18:18'),
(190, 'EMP-015', 'LI CHUNLI', NULL, 'Male', 33, 'Active', '2026-07-21 05:18:18'),
(191, 'EMP-016', 'CAI WENJUN', NULL, 'Female', 33, 'Active', '2026-07-21 05:18:18'),
(192, 'EMP-017', 'ZHONG RUIXIANG', NULL, 'Male', 33, 'Active', '2026-07-21 05:18:18'),
(193, 'EMP-018', 'LIANG SHIXING', NULL, 'Female', 33, 'Active', '2026-07-21 05:18:18'),
(194, 'EMP-019', 'LYU/YINGXING', NULL, 'Male', 33, 'Active', '2026-07-21 05:18:18'),
(195, 'EMP-020', 'LIAO XILONG', NULL, 'Female', 33, 'Active', '2026-07-21 05:18:18'),
(196, 'EMP-021', 'CHEN ZIGAO', NULL, 'Male', 33, 'Active', '2026-07-21 05:18:18'),
(197, 'EMP-022', 'LIN LIENSHENG', NULL, 'Female', 33, 'Active', '2026-07-21 05:18:18'),
(198, 'EMP-023', 'SHI/YONG', NULL, 'Male', 33, 'Active', '2026-07-21 05:18:18'),
(199, 'EMP-024', 'YANG JENCHIEH', NULL, 'Female', 33, 'Active', '2026-07-21 05:18:18'),
(200, 'EMP-025', 'GAMBOL', NULL, 'Male', 33, 'Active', '2026-07-21 05:18:18'),
(201, 'EMP-026', 'JOHNNY PAN', NULL, 'Female', 33, 'Active', '2026-07-21 05:18:18'),
(202, 'EMP-027', 'ALECK LIU', NULL, 'Male', 33, 'Active', '2026-07-21 05:18:18'),
(203, 'EMP-028', 'PING PING HAN', NULL, 'Female', 33, 'Active', '2026-07-21 05:18:18'),
(204, 'EMP-029', 'SUN DENGKE', NULL, 'Male', 33, 'Active', '2026-07-21 05:18:18'),
(205, 'EMP-030', 'LOUIS LIU', NULL, 'Male', 33, 'Active', '2026-07-21 05:18:18'),
(206, 'PS26Y087', 'asd', NULL, 'Male', 38, 'Active', '2026-07-22 04:51:43');

-- --------------------------------------------------------

--
-- Table structure for table `floors`
--

CREATE TABLE `floors` (
  `id` int(11) NOT NULL,
  `building_id` int(11) NOT NULL,
  `floor_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `floors`
--

INSERT INTO `floors` (`id`, `building_id`, `floor_name`) VALUES
(14, 13, '1st Floor'),
(15, 13, '2nd Floor'),
(19, 17, 'Test Padding Floor'),
(20, 18, 'Test Padding Floor'),
(21, 19, 'Test Padding Floor');

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL,
  `floor_id` int(11) NOT NULL,
  `room_no` varchar(20) NOT NULL,
  `room_type` enum('Single','Double','Triple','Quadruple','Suite') NOT NULL,
  `capacity` int(11) NOT NULL,
  `current_occupancy` int(11) NOT NULL DEFAULT 0,
  `status` varchar(30) NOT NULL DEFAULT 'Available',
  `reserved_by_employee_id` int(11) DEFAULT NULL,
  `gender_restriction` enum('Male','Female','Any') NOT NULL DEFAULT 'Any',
  `remarks` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`id`, `floor_id`, `room_no`, `room_type`, `capacity`, `current_occupancy`, `status`, `reserved_by_employee_id`, `gender_restriction`, `remarks`) VALUES
(24, 14, 'A1', 'Single', 1, 0, 'Available', NULL, 'Any', ''),
(25, 14, 'A2', 'Single', 1, 0, 'Available', NULL, 'Any', ''),
(26, 14, 'A3', 'Single', 1, 0, 'Available', NULL, 'Any', ''),
(27, 14, 'A4', 'Single', 1, 1, 'Occupied', NULL, 'Any', ''),
(28, 14, 'A5', 'Single', 1, 1, 'Occupied', NULL, 'Any', ''),
(29, 14, 'A6', 'Single', 1, 0, 'Available', NULL, 'Any', ''),
(30, 14, 'A7', 'Single', 1, 0, 'Available', NULL, 'Any', ''),
(31, 14, 'A8', 'Single', 1, 0, 'Available', NULL, 'Any', ''),
(32, 14, 'C1', 'Single', 1, 0, 'Available', NULL, 'Any', ''),
(33, 14, 'C2', 'Single', 1, 0, 'Available', NULL, 'Any', ''),
(34, 14, 'C3', 'Single', 1, 0, 'Available', NULL, 'Any', ''),
(35, 14, 'C4', 'Single', 1, 0, 'Available', NULL, 'Any', ''),
(36, 14, 'C5', 'Single', 1, 0, 'Available', NULL, 'Any', ''),
(37, 14, 'C6', 'Single', 1, 0, 'Available', NULL, 'Any', ''),
(38, 14, 'C7', 'Single', 1, 0, 'Available', NULL, 'Any', ''),
(39, 14, 'C8', 'Single', 1, 0, 'Available', NULL, 'Any', ''),
(40, 14, 'B9', 'Single', 1, 0, 'Available', NULL, 'Any', ''),
(41, 14, 'B10', 'Single', 1, 0, 'Available', NULL, 'Any', ''),
(42, 15, 'B1', 'Double', 2, 0, 'Available', NULL, 'Any', ''),
(43, 15, 'B2', 'Double', 2, 0, 'Available', NULL, 'Any', ''),
(44, 15, 'B3', 'Double', 2, 0, 'Available', NULL, 'Any', ''),
(45, 15, 'B4', 'Double', 2, 0, 'Available', NULL, 'Any', ''),
(46, 15, 'B5', 'Double', 2, 0, 'Available', NULL, 'Any', ''),
(47, 15, 'B6', 'Double', 2, 0, 'Available', NULL, 'Any', ''),
(48, 15, 'B7', 'Double', 2, 0, 'Available', NULL, 'Any', ''),
(49, 15, 'B8', 'Double', 2, 0, 'Available', NULL, 'Any', ''),
(125, 15, 'C9', 'Single', 1, 0, 'Available', NULL, 'Any', ''),
(126, 15, 'C10', 'Single', 1, 0, 'Available', NULL, 'Any', ''),
(127, 15, 'C11', 'Single', 1, 0, 'Available', NULL, 'Any', ''),
(128, 15, 'C12', 'Single', 1, 0, 'Available', NULL, 'Any', ''),
(129, 15, 'C13', 'Single', 1, 0, 'Available', NULL, 'Any', ''),
(130, 15, 'C14', 'Single', 1, 0, 'Available', NULL, 'Any', ''),
(131, 15, 'C15', 'Single', 1, 0, 'Available', NULL, 'Any', ''),
(132, 15, 'B01', 'Single', 1, 0, 'Available', NULL, 'Any', ''),
(133, 15, 'B02', 'Single', 1, 0, 'Available', NULL, 'Any', ''),
(134, 15, 'B03', 'Single', 1, 0, 'Available', NULL, 'Any', ''),
(135, 15, 'B04', 'Single', 1, 0, 'Available', NULL, 'Any', ''),
(136, 15, 'B05', 'Single', 1, 0, 'Available', NULL, 'Any', ''),
(137, 15, 'B06', 'Single', 1, 0, 'Available', NULL, 'Any', ''),
(138, 15, 'B07', 'Single', 1, 0, 'Available', NULL, 'Any', ''),
(139, 15, 'B08', 'Single', 1, 0, 'Available', NULL, 'Any', ''),
(140, 15, 'B09', 'Single', 1, 0, 'Available', NULL, 'Any', ''),
(141, 15, 'B11', 'Single', 1, 0, 'Available', NULL, 'Any', ''),
(142, 15, 'B12', 'Single', 1, 0, 'Available', NULL, 'Any', ''),
(143, 15, 'B13', 'Single', 1, 0, 'Available', NULL, 'Any', ''),
(144, 15, 'B14', 'Single', 1, 0, 'Available', NULL, 'Any', ''),
(145, 15, 'B15', 'Single', 1, 0, 'Available', NULL, 'Any', '');

-- --------------------------------------------------------

--
-- Table structure for table `room_assignments`
--

CREATE TABLE `room_assignments` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `transferred_to_room_id` int(11) DEFAULT NULL,
  `checkin_date` date NOT NULL,
  `expected_checkout_date` date NOT NULL,
  `actual_checkout_date` date DEFAULT NULL,
  `status` enum('Active','Checked Out','Transferred') NOT NULL DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `room_assignments`
--

INSERT INTO `room_assignments` (`id`, `employee_id`, `room_id`, `transferred_to_room_id`, `checkin_date`, `expected_checkout_date`, `actual_checkout_date`, `status`) VALUES
(60, 202, 28, NULL, '2026-07-23', '2026-09-10', NULL, 'Active'),
(61, 203, 27, NULL, '2026-07-23', '2026-09-10', NULL, 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `transaction_type` varchar(50) NOT NULL,
  `transaction_date` datetime NOT NULL DEFAULT current_timestamp(),
  `remarks` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `employee_id`, `transaction_type`, `transaction_date`, `remarks`) VALUES
(122, 203, 'arrival', '2026-07-23 00:00:00', '');

-- --------------------------------------------------------

--
-- Table structure for table `transportation_requests`
--

CREATE TABLE `transportation_requests` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `transportation_type` enum('Company Car','Airport Transfer','Shuttle Service','Private Hire','Other') NOT NULL,
  `driver_id` int(11) DEFAULT NULL,
  `vehicle_id` int(11) DEFAULT NULL,
  `pickup_date` date NOT NULL,
  `pickup_time` time NOT NULL,
  `pickup_location` varchar(200) NOT NULL,
  `status` enum('Pending','Scheduled','Picked Up','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `transportation_requests`
--

INSERT INTO `transportation_requests` (`id`, `employee_id`, `transportation_type`, `driver_id`, `vehicle_id`, `pickup_date`, `pickup_time`, `pickup_location`, `status`, `remarks`, `created_at`, `updated_at`) VALUES
(1, 202, 'Company Car', 1, 1, '2026-07-23', '08:00:00', 'Dorm A', 'Scheduled', 'Sample dispatch request', '2026-07-23 01:05:12', '2026-07-23 01:08:31'),
(2, 206, 'Company Car', 3, 1, '2026-07-23', '10:17:00', 'Airport', 'Scheduled', '', '2026-07-23 01:17:37', '2026-07-23 01:17:37'),
(3, 176, 'Company Car', 3, 18, '2026-07-23', '08:00:00', 'Dorm A', 'Scheduled', 'Test', '2026-07-23 01:19:21', '2026-07-23 01:26:23'),
(4, 206, 'Company Car', NULL, NULL, '2026-07-24', '11:23:00', 'Mango Valley 2', 'Scheduled', '', '2026-07-23 01:24:10', '2026-07-23 01:24:10');

-- --------------------------------------------------------

--
-- Table structure for table `transportation_types`
--

CREATE TABLE `transportation_types` (
  `id` int(11) NOT NULL,
  `transportation_name` varchar(100) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('Admin','HR','Viewer') DEFAULT 'Viewer',
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password_hash`, `role`, `status`, `created_at`) VALUES
(10, 'hr', '$2y$12$gmYUgQNDFrFtAPrDoDimS.MD..3wImbRNuywQZopLrx9bl07n4VY.', 'HR', 'Active', '2026-06-20 05:29:18'),
(11, 'viewer', '$2y$12$NughlNmGcCjFOLJsPcHJwuqzLGwftnOCjE0kXS.t7drN3TqbyF8SS', 'Viewer', 'Active', '2026-06-20 05:29:19'),
(13, 'admin', '$2y$12$KzHNYwue.SR/hCLohElI1eSPJCsh6sFZDZAXUFi2vWWPNemtsY176', 'Admin', 'Active', '2026-06-21 10:16:22'),
(14, 'Carlo', '$2y$10$KazouG6.hImCNPFWcrXaCe5KHy3qmGf23ukglcQeos8k07EBJzBM.', 'Admin', 'Active', '2026-07-08 02:53:15');

-- --------------------------------------------------------

--
-- Table structure for table `vehicles`
--

CREATE TABLE `vehicles` (
  `id` int(11) NOT NULL,
  `plate_number` varchar(20) NOT NULL,
  `vehicle_name` varchar(100) NOT NULL,
  `license_plate` varchar(50) DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'Available',
  `vehicle_model` varchar(100) DEFAULT NULL,
  `vehicle_color` varchar(50) DEFAULT NULL,
  `seating_capacity` int(11) DEFAULT 4,
  `remarks` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vehicles`
--

INSERT INTO `vehicles` (`id`, `plate_number`, `vehicle_name`, `license_plate`, `status`, `vehicle_model`, `vehicle_color`, `seating_capacity`, `remarks`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'ABC-1234', 'Toyota Hiace', 'ABC-1234', 'Available', 'Hiace', 'White', 12, 'Sample vehicle', 1, '2026-07-23 01:05:12', '2026-07-23 01:05:12'),
(2, '', 'Test Van', 'XYZ-9999', 'Available', NULL, NULL, 4, NULL, 1, '2026-07-23 01:09:51', '2026-07-23 01:09:51'),
(18, 'VAL-1001', 'Validation Van', 'VAL-1001', 'Available', NULL, NULL, 4, NULL, 1, '2026-07-23 01:22:24', '2026-07-23 01:22:24'),
(19, 'ASD-12312', 'TOYOTA', 'ASD-12312', 'Available', NULL, NULL, 4, NULL, 1, '2026-07-23 01:23:31', '2026-07-23 01:23:31'),
(20, 'asdasd', 'sadasd', 'asdasd', 'Available', NULL, NULL, 4, NULL, 1, '2026-07-23 07:18:29', '2026-07-23 07:18:29'),
(21, 'adsadas', 'qwq123', 'adsadas', 'Available', NULL, NULL, 4, NULL, 1, '2026-07-23 23:19:44', '2026-07-23 23:19:44'),
(22, 'adasd', 'adsd', 'adasd', 'Available', NULL, NULL, 4, NULL, 1, '2026-07-23 23:29:13', '2026-07-23 23:29:13');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accommodations`
--
ALTER TABLE `accommodations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `buildings`
--
ALTER TABLE `buildings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `accommodation_id` (`accommodation_id`);

--
-- Indexes for table `company_car_departures`
--
ALTER TABLE `company_car_departures`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_car_assignment` (`assignment_id`),
  ADD KEY `fk_car_transportation` (`transportation_type_id`),
  ADD KEY `fk_car_vehicle` (`vehicle_id`),
  ADD KEY `fk_car_driver` (`driver_id`);

--
-- Indexes for table `company_car_departure_logs`
--
ALTER TABLE `company_car_departure_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_car_departure_id` (`company_car_departure_id`);

--
-- Indexes for table `daily_headcount`
--
ALTER TABLE `daily_headcount`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `date` (`date`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `drivers`
--
ALTER TABLE `drivers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_code` (`employee_code`),
  ADD KEY `department_id` (`department_id`),
  ADD KEY `idx_employees_search` (`employee_code`,`full_name`),
  ADD KEY `idx_employees_status` (`status`);

--
-- Indexes for table `floors`
--
ALTER TABLE `floors`
  ADD PRIMARY KEY (`id`),
  ADD KEY `building_id` (`building_id`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rooms_lookup` (`floor_id`,`status`);

--
-- Indexes for table `room_assignments`
--
ALTER TABLE `room_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `room_id` (`room_id`),
  ADD KEY `idx_room_assignments_status` (`status`,`checkin_date`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `idx_transactions_date` (`transaction_date`);

--
-- Indexes for table `transportation_requests`
--
ALTER TABLE `transportation_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_transportation_requests_employee` (`employee_id`),
  ADD KEY `idx_transportation_requests_pickup` (`pickup_date`,`pickup_time`),
  ADD KEY `idx_transportation_requests_status` (`status`),
  ADD KEY `fk_transportation_requests_driver` (`driver_id`),
  ADD KEY `fk_transportation_requests_vehicle` (`vehicle_id`);

--
-- Indexes for table `transportation_types`
--
ALTER TABLE `transportation_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transportation_name` (`transportation_name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `plate_number` (`plate_number`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accommodations`
--
ALTER TABLE `accommodations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `buildings`
--
ALTER TABLE `buildings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `company_car_departures`
--
ALTER TABLE `company_car_departures`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `company_car_departure_logs`
--
ALTER TABLE `company_car_departure_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `daily_headcount`
--
ALTER TABLE `daily_headcount`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=84;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `drivers`
--
ALTER TABLE `drivers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=207;

--
-- AUTO_INCREMENT for table `floors`
--
ALTER TABLE `floors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=146;

--
-- AUTO_INCREMENT for table `room_assignments`
--
ALTER TABLE `room_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=124;

--
-- AUTO_INCREMENT for table `transportation_requests`
--
ALTER TABLE `transportation_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `transportation_types`
--
ALTER TABLE `transportation_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `vehicles`
--
ALTER TABLE `vehicles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `buildings`
--
ALTER TABLE `buildings`
  ADD CONSTRAINT `buildings_ibfk_1` FOREIGN KEY (`accommodation_id`) REFERENCES `accommodations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `company_car_departures`
--
ALTER TABLE `company_car_departures`
  ADD CONSTRAINT `fk_car_assignment` FOREIGN KEY (`assignment_id`) REFERENCES `room_assignments` (`id`),
  ADD CONSTRAINT `fk_car_driver` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`),
  ADD CONSTRAINT `fk_car_transportation` FOREIGN KEY (`transportation_type_id`) REFERENCES `transportation_types` (`id`),
  ADD CONSTRAINT `fk_car_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`);

--
-- Constraints for table `company_car_departure_logs`
--
ALTER TABLE `company_car_departure_logs`
  ADD CONSTRAINT `company_car_departure_logs_ibfk_1` FOREIGN KEY (`company_car_departure_id`) REFERENCES `company_car_departures` (`id`);

--
-- Constraints for table `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `employees_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `floors`
--
ALTER TABLE `floors`
  ADD CONSTRAINT `floors_ibfk_1` FOREIGN KEY (`building_id`) REFERENCES `buildings` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `rooms`
--
ALTER TABLE `rooms`
  ADD CONSTRAINT `rooms_ibfk_1` FOREIGN KEY (`floor_id`) REFERENCES `floors` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `room_assignments`
--
ALTER TABLE `room_assignments`
  ADD CONSTRAINT `room_assignments_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `room_assignments_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `transportation_requests`
--
ALTER TABLE `transportation_requests`
  ADD CONSTRAINT `fk_transportation_requests_driver` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_transportation_requests_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_transportation_requests_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
