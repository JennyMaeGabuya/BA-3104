-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Dec 10, 2025 at 07:41 AM
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
-- Database: `booking_management`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_notifications`
--

CREATE TABLE `admin_notifications` (
  `id` int(11) NOT NULL,
  `appointment_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `message` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_notifications`
--

INSERT INTO `admin_notifications` (`id`, `appointment_id`, `type`, `message`, `created_at`) VALUES
(92, 258, 'cancelled', 'Appointment for Aron Khelvi Reyes on 2025-12-10 at 14:00:00 was cancelled.', '2025-12-09 23:34:44'),
(93, 263, 'cancelled', 'Appointment for Taguro Senso on 2025-12-10 at 08:00:00 was cancelled.', '2025-12-09 23:55:35'),
(100, 272, 'cancelled', 'Appointment for Aron Khelvi Reyes on 2025-12-12 at 10:00:00 was cancelled.', '2025-12-10 13:15:00'),
(102, 274, 'cancelled', 'Appointment for Aron Khelvi Reyes on 2025-12-27 at 13:00:00 was cancelled.', '2025-12-10 13:15:40'),
(104, 275, 'cancelled', 'Appointment for Sedrick John on 2025-12-11 at 10:00:00 was cancelled.', '2025-12-10 13:18:04'),
(108, 280, 'rescheduled', 'Appointment for Aron Khelvi Reyes was rescheduled to 2025-12-13 at 13:15:00.', '2025-12-10 13:42:34'),
(109, 280, 'cancelled', 'Appointment for Aron Khelvi Reyes on 2025-12-13 at 13:15:00 was cancelled.', '2025-12-10 13:43:05'),
(110, 281, 'rescheduled', 'Appointment for Ken Pitarde was rescheduled to 2025-12-11 at 10:30:00.', '2025-12-10 13:59:58'),
(111, 281, 'cancelled', 'Ken Pitarde\'s appointment was cancelled by admin.', '2025-12-10 14:24:47'),
(112, 282, 'rescheduled', 'Appointment for Ken Pitarde was rescheduled to 2025-12-11 at 11:30:00.', '2025-12-10 14:25:26'),
(113, 282, 'cancelled', 'Ken Pitarde\'s appointment was cancelled by admin.', '2025-12-10 14:25:38'),
(114, 284, 'rescheduled', 'Appointment for Ken Pitarde was rescheduled to 2025-12-11 at 11:15:00.', '2025-12-10 14:27:39'),
(115, 284, 'cancelled', 'Ken Pitarde\'s appointment was cancelled by admin.', '2025-12-10 14:35:08');

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `appointment_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `contact_no` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `age` int(11) NOT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `date_of_birth` date NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `reason` varchar(255) NOT NULL,
  `doctor_note` text DEFAULT NULL,
  `status` enum('pending','accepted','declined','completed','rescheduled','rescheduled_pending') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`appointment_id`, `user_id`, `name`, `contact_no`, `email`, `age`, `gender`, `address`, `date_of_birth`, `appointment_date`, `appointment_time`, `reason`, `doctor_note`, `status`, `created_at`) VALUES
(259, 20, 'Aron Khelvi Reyes', '+63 9086765125', 'reyeskhelvi2005@gmail.com', 20, 'Male', 'San Pioquinto, Malvar, Batangas', '2005-07-31', '2025-12-10', '14:00:00', 'Injury', NULL, 'completed', '2025-12-09 15:37:57'),
(260, 20, 'Aron Khelvi Reyes', '+63 9086765125', 'reyeskhelvi2005@gmail.com', 20, 'Male', 'San Pioquinto, Malvar, Batangas', '2005-07-31', '2025-12-11', '09:15:00', 'Heartbreak', NULL, 'completed', '2025-12-09 15:38:20'),
(261, 18, 'Ken Pitarde', '+639685912133', '23-60065@g.batstate-u.edu.ph', 22, 'Male', 'Rizal Avenue Extension', '2003-10-14', '2025-12-10', '11:00:00', 'Flu Symptoms', NULL, 'completed', '2025-12-09 15:49:49'),
(262, 18, 'Ken Pitarde', '+639685912133', '23-60065@g.batstate-u.edu.ph', 22, 'Male', 'Rizal Avenue Extension', '2003-10-14', '2025-12-10', '11:30:00', 'Injury', NULL, 'completed', '2025-12-09 15:50:01'),
(264, 20, 'Aron Khelvi Reyes', '+63 9086765125', 'reyeskhelvi2005@gmail.com', 20, 'Male', 'San Pioquinto, Malvar, Batangas', '2005-07-31', '2025-12-10', '09:00:00', 'Flu Symptoms', 'okay', 'completed', '2025-12-09 15:53:54'),
(265, 21, 'Taguro Senso', '639685912133', 'kenpitarde09685912133@gmail.com', 22, 'Male', 'San Antonio, Batangas', '2003-12-10', '2025-12-10', '11:45:00', 'Follow-up', NULL, 'completed', '2025-12-09 15:56:37'),
(266, 20, 'Aron Khelvi Reyes', '+63 9086765125', 'reyeskhelvi2005@gmail.com', 20, 'Male', 'San Pioquinto, Malvar, Batangas', '2005-07-31', '2025-12-20', '09:00:00', 'General Checkup', NULL, 'completed', '2025-12-09 15:57:16'),
(267, 20, 'Aron Khelvi Reyes', '+63 9086765125', 'reyeskhelvi2005@gmail.com', 20, 'Male', 'San Pioquinto, Malvar, Batangas', '2005-07-31', '2025-12-10', '09:00:00', 'Flu Symptoms', NULL, 'completed', '2025-12-09 15:58:23'),
(268, 21, 'Taguro Senso', '+639685912133', 'kenpitarde09685912133@gmail.com', 22, 'Male', 'San Antonio, Batangas', '2003-12-10', '2025-12-11', '09:30:00', 'Ayoko na', NULL, 'completed', '2025-12-09 16:02:37'),
(270, 21, 'Taguro Senso', '+639685912133', 'kenpitarde09685912133@gmail.com', 22, 'Male', 'San Antonio, Batangas', '2003-12-10', '2025-12-11', '11:00:00', 'General Checkup', NULL, 'completed', '2025-12-09 16:18:46'),
(271, 21, 'Taguro Senso', '639685912133', 'kenpitarde09685912133@gmail.com', 22, 'Male', 'San Antonio, Batangas', '2003-12-10', '2025-12-12', '11:15:00', 'General Checkup', NULL, 'completed', '2025-12-09 17:02:48'),
(273, 1, 'Aron Khelvi Reyes', '+63 9086765125', 'reyeskhelvi2005@gmail.com', 20, 'Male', 'San Pioquinto, Malvar, Batangas', '2005-07-31', '2025-12-22', '11:45:00', 'Dental', NULL, 'completed', '2025-12-10 05:12:15'),
(276, 22, 'Sedrick John', '9672543457', 'sedrickopulencia@gmail.com', 20, 'Male', 'Trapiche 1', '2005-01-23', '2025-12-12', '10:00:00', 'Masakit po ang tite', NULL, 'completed', '2025-12-10 05:18:36'),
(277, 23, 'Ronald Velasco', '+639276988300', '23-61445@g.batstate-u.edu.ph', 21, 'Male', 'Sto. Tomas Batangas', '2025-05-15', '2025-12-11', '15:00:00', 'Medical Certificate', 'ayoko', 'completed', '2025-12-10 05:27:44'),
(278, 18, 'Ken Pitarde', '+639685912133', '23-60065@g.batstate-u.edu.ph', 22, 'Male', 'Rizal Avenue Extension', '2003-10-14', '2025-12-11', '10:15:00', 'I can\'t drink water.', 'hello', 'completed', '2025-12-10 05:30:11'),
(283, 18, 'Ken Pitarde', '+639685912133', '23-60065@g.batstate-u.edu.ph', 22, 'Male', 'Rizal Avenue Extension', '2003-10-14', '2025-12-11', '10:30:00', 'Medical Certificate', NULL, 'completed', '2025-12-10 06:26:16');

-- --------------------------------------------------------

--
-- Table structure for table `cancelled_appointments`
--

CREATE TABLE `cancelled_appointments` (
  `id` int(11) NOT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `contact_no` varchar(50) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `appointment_date` date DEFAULT NULL,
  `appointment_time` time DEFAULT NULL,
  `status` varchar(50) DEFAULT 'cancelled',
  `cancelled_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `gender` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cancelled_appointments`
--

INSERT INTO `cancelled_appointments` (`id`, `appointment_id`, `user_id`, `name`, `email`, `contact_no`, `age`, `date_of_birth`, `reason`, `appointment_date`, `appointment_time`, `status`, `cancelled_at`, `gender`, `address`) VALUES
(154, 284, 18, 'Ken Pitarde', '23-60065@g.batstate-u.edu.ph', '639685912133', 22, '2003-10-14', 'Flu Symptoms', '2025-12-11', '11:15:00', 'cancelled', '2025-12-10 06:35:08', 'Male', 'Rizal Avenue Extension');

-- --------------------------------------------------------

--
-- Table structure for table `medical_records`
--

CREATE TABLE `medical_records` (
  `record_id` int(11) NOT NULL,
  `appointment_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `contact_no` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `date` date DEFAULT NULL,
  `time` varchar(20) DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `doctor_note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medical_records`
--

INSERT INTO `medical_records` (`record_id`, `appointment_id`, `patient_id`, `full_name`, `contact_no`, `email`, `age`, `gender`, `address`, `date_of_birth`, `date`, `time`, `reason`, `doctor_note`, `created_at`) VALUES
(45, 259, 20, 'Aron Khelvi Reyes', '+63 9086765125', 'reyeskhelvi2005@gmail.com', 20, 'Male', 'San Pioquinto, Malvar, Batangas', '2005-07-31', '2025-12-10', '14:00:00', 'Injury', NULL, '2025-12-09 15:38:36'),
(46, 260, 20, 'Aron Khelvi Reyes', '+63 9086765125', 'reyeskhelvi2005@gmail.com', 20, 'Male', 'San Pioquinto, Malvar, Batangas', '2005-07-31', '2025-12-11', '09:15:00', 'Heartbreak', NULL, '2025-12-09 15:38:40'),
(47, 264, 20, 'Aron Khelvi Reyes', '+63 9086765125', 'reyeskhelvi2005@gmail.com', 20, 'Male', 'San Pioquinto, Malvar, Batangas', '2005-07-31', '2025-12-10', '09:00:00', 'Flu Symptoms', 'okay', '2025-12-09 15:54:49'),
(48, 266, 20, 'Aron Khelvi Reyes', '+63 9086765125', 'reyeskhelvi2005@gmail.com', 20, 'Male', 'San Pioquinto, Malvar, Batangas', '2005-07-31', '2025-12-31', '10:45:00', 'General Checkup', NULL, '2025-12-09 15:57:30'),
(49, 266, 20, 'Aron Khelvi Reyes', '+63 9086765125', 'reyeskhelvi2005@gmail.com', 20, 'Male', 'San Pioquinto, Malvar, Batangas', '2005-07-31', '2025-12-20', '09:00:00', 'General Checkup', NULL, '2025-12-09 15:57:40'),
(50, 267, 20, 'Aron Khelvi Reyes', '+63 9086765125', 'reyeskhelvi2005@gmail.com', 20, 'Male', 'San Pioquinto, Malvar, Batangas', '2005-07-31', '2025-12-10', '09:00:00', 'Flu Symptoms', NULL, '2025-12-09 15:59:06'),
(51, 265, 21, 'Taguro Senso', '639685912133', 'kenpitarde09685912133@gmail.com', 22, 'Male', 'San Antonio, Batangas', '2003-12-10', '2025-12-10', '11:45:00', 'Follow-up', NULL, '2025-12-09 16:00:22'),
(52, 268, 21, 'Taguro Senso', '+639685912133', 'kenpitarde09685912133@gmail.com', 22, 'Male', 'San Antonio, Batangas', '2003-12-10', '2025-12-11', '09:30:00', 'Ayoko na', NULL, '2025-12-09 16:02:49'),
(53, 261, 18, 'Ken Pitarde', '+639685912133', '23-60065@g.batstate-u.edu.ph', 22, 'Male', 'Rizal Avenue Extension', '2003-10-14', '2025-12-10', '11:00:00', 'Flu Symptoms', NULL, '2025-12-09 16:19:23'),
(54, 262, 18, 'Ken Pitarde', '+639685912133', '23-60065@g.batstate-u.edu.ph', 22, 'Male', 'Rizal Avenue Extension', '2003-10-14', '2025-12-10', '11:30:00', 'Injury', NULL, '2025-12-09 17:02:25'),
(55, 270, 21, 'Taguro Senso', '+639685912133', 'kenpitarde09685912133@gmail.com', 22, 'Male', 'San Antonio, Batangas', '2003-12-10', '2025-12-11', '11:00:00', 'General Checkup', NULL, '2025-12-09 17:02:29'),
(56, 271, 21, 'Taguro Senso', '639685912133', 'kenpitarde09685912133@gmail.com', 22, 'Male', 'San Antonio, Batangas', '2003-12-10', '2025-12-12', '11:15:00', 'General Checkup', NULL, '2025-12-09 17:02:57'),
(57, 273, 1, 'Aron Khelvi Reyes', '+63 9086765125', 'reyeskhelvi2005@gmail.com', 20, 'Male', 'San Pioquinto, Malvar, Batangas', '2005-07-31', '2025-12-22', '11:45:00', 'Dental', NULL, '2025-12-10 05:13:26'),
(58, 277, 23, 'Ronald Velasco', '+639276988300', '23-61445@g.batstate-u.edu.ph', 21, 'Male', 'Sto. Tomas Batangas', '2025-05-15', '2025-12-11', '15:00:00', 'Medical Certificate', 'ayoko', '2025-12-10 05:28:36'),
(59, 276, 22, 'Sedrick John', '9672543457', 'sedrickopulencia@gmail.com', 20, 'Male', 'Trapiche 1', '2005-01-23', '2025-12-12', '10:00:00', 'Masakit po ang tite', NULL, '2025-12-10 05:28:44'),
(60, 278, 18, 'Ken Pitarde', '+639685912133', '23-60065@g.batstate-u.edu.ph', 22, 'Male', 'Rizal Avenue Extension', '2003-10-14', '2025-12-11', '08:00:00', 'I can\'t drink water.', 'hello', '2025-12-10 05:34:10'),
(61, 278, 18, 'Ken Pitarde', '+639685912133', '23-60065@g.batstate-u.edu.ph', 22, 'Male', 'Rizal Avenue Extension', '2003-10-14', '2025-12-11', '10:15:00', 'I can\'t drink water.', 'hello', '2025-12-10 05:37:48'),
(62, 283, 18, 'Ken Pitarde', '+639685912133', '23-60065@g.batstate-u.edu.ph', 22, 'Male', 'Rizal Avenue Extension', '2003-10-14', '2025-12-11', '10:30:00', 'Medical Certificate', NULL, '2025-12-10 06:26:31');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `message` varchar(255) NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `type`, `message`, `is_read`, `created_at`) VALUES
(33, 20, 'appointment_completed', 'Your appointment on Dec 10, 2025 at 2:00 PM has been completed.', 1, '2025-12-09 23:38:36'),
(34, 20, 'appointment_completed', 'Your appointment on Dec 11, 2025 at 9:15 AM has been completed.', 1, '2025-12-09 23:38:41'),
(35, 20, 'appointment_completed', 'Your appointment on Dec 10, 2025 at 9:00 AM has been completed.', 1, '2025-12-09 23:54:49'),
(36, 20, 'appointment_completed', 'Your appointment on Dec 31, 2025 at 10:45 AM has been completed.', 1, '2025-12-09 23:57:30'),
(37, 20, 'appointment_completed', 'Your appointment on Dec 20, 2025 at 9:00 AM has been completed.', 1, '2025-12-09 23:57:40'),
(38, 20, 'appointment_completed', 'Your appointment on Dec 10, 2025 at 9:00 AM has been completed.', 1, '2025-12-09 23:59:06'),
(39, 21, 'appointment_completed', 'Your appointment on Dec 10, 2025 at 11:45 AM has been completed.', 1, '2025-12-10 00:00:22'),
(40, 21, 'appointment_completed', 'Your appointment on Dec 11, 2025 at 9:30 AM has been completed.', 1, '2025-12-10 00:02:49'),
(41, 18, 'appointment_completed', 'Your appointment on Dec 10, 2025 at 11:00 AM has been completed.', 1, '2025-12-10 00:19:23'),
(42, 18, 'appointment_completed', 'Your appointment on Dec 10, 2025 at 11:30 AM has been completed.', 1, '2025-12-10 01:02:25'),
(43, 21, 'appointment_completed', 'Your appointment on Dec 11, 2025 at 11:00 AM has been completed.', 1, '2025-12-10 01:02:29'),
(44, 21, 'appointment_completed', 'Your appointment on Dec 12, 2025 at 11:15 AM has been completed.', 1, '2025-12-10 01:02:57'),
(45, 1, 'appointment_completed', 'Your appointment on Dec 22, 2025 at 11:45 AM has been completed.', 0, '2025-12-10 13:13:26'),
(46, 23, 'appointment_completed', 'Your appointment on Dec 11, 2025 at 3:00 PM has been completed.', 0, '2025-12-10 13:28:36'),
(47, 22, 'appointment_completed', 'Your appointment on Dec 12, 2025 at 10:00 AM has been completed.', 0, '2025-12-10 13:28:44'),
(48, 18, 'appointment_completed', 'Your appointment on Dec 11, 2025 at 8:00 AM has been completed.', 1, '2025-12-10 13:34:10'),
(49, 18, 'appointment_completed', 'Your appointment on Dec 11, 2025 at 10:15 AM has been completed.', 1, '2025-12-10 13:37:48'),
(50, 18, 'appointment_cancelled', 'Your appointment on 2025-12-11 at 10:30:00 was cancelled because staff is not available.', 1, '2025-12-10 14:24:47'),
(51, 18, 'appointment_cancelled', 'Your appointment on 2025-12-11 at 11:30:00 was cancelled because staff is not available.', 1, '2025-12-10 14:25:38'),
(52, 18, 'appointment_completed', 'Your appointment on Dec 11, 2025 at 10:30 AM has been completed.', 1, '2025-12-10 14:26:31'),
(53, 18, 'appointment_cancelled', 'Your appointment on 2025-12-11 at 11:15:00 was cancelled because staff is not available.', 0, '2025-12-10 14:35:08');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `role` varchar(20) NOT NULL DEFAULT 'patient',
  `otp` int(11) DEFAULT NULL,
  `otp_expire` datetime DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `name`, `email`, `phone`, `password`, `created_at`, `role`, `otp`, `otp_expire`, `gender`, `address`, `date_of_birth`) VALUES
(1, 'Admin Ken Pitarde', 'pitardeken2024@gmail.com', '+639685912133', '$2y$10$qEJRgpAP0UYNx6krkqPuJugTHnp.cTt6OcpNEJxUy2fj4zcNTq4Q6', '2025-11-19 02:37:53', 'admin', 599080, '2025-11-20 18:27:05', NULL, NULL, NULL),
(18, 'Ken Pitarde', '23-60065@g.batstate-u.edu.ph', '+639685912133', '$2y$10$LG8De9qeI.aB5OxFcooRjuXkRX0bEPt/GwrscTqjkYGx85XWh6nrC', '2025-12-05 15:03:33', 'patient', NULL, NULL, 'Male', 'Rizal Avenue Extension', '2003-10-14'),
(19, 'Apple Rose Pagal', 'applerosepagal@gmail.com', '09486634704', '$2y$10$p6lx.j2R3LjoM0/Ne4v3x.UpPB4Tuj9zvhmmMoFLNuTX3VpZBkDj.', '2025-12-06 23:55:26', 'patient', NULL, NULL, 'Female', 'sa puso mo', '2002-10-01'),
(20, 'Aron Khelvi Reyes', 'reyeskhelvi2005@gmail.com', '+63 9086765125', '$2y$10$VXV3K8AC8wKDwb1NLAOcce6g3aIoSa7GDCASbMkSpaQ9rzCr5H/4i', '2025-12-09 15:31:55', 'patient', NULL, NULL, 'Male', 'San Pioquinto, Malvar, Batangas', '2005-07-31'),
(21, 'Taguro Senso', 'kenpitarde09685912133@gmail.com', '+639685912133', '$2y$10$s9Co3acfU6PqXY2ct28qRObWwKX0SuaSv7h3EDZhdj9GtWAi8ac6y', '2025-12-09 15:52:20', 'patient', NULL, NULL, 'Male', 'San Antonio, Batangas', '2003-12-10'),
(22, 'Sedrick John', 'sedrickopulencia@gmail.com', '09672543457', '$2y$10$7Myvde5daKc7IgS6ZrAUge7BD8kcZjgjiKZYSMDR5thJKE1/0QRC.', '2025-12-10 05:12:47', 'patient', NULL, NULL, NULL, NULL, NULL),
(23, 'Ronald Velasco', '23-61445@g.batstate-u.edu.ph', '+639276988300', '$2y$10$zh0Zp5dQqwpzJyapfk3aee97lt7esQz4uqlHa72k23q.mGWD8bWUe', '2025-12-10 05:23:21', 'patient', NULL, NULL, NULL, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_notifications`
--
ALTER TABLE `admin_notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`appointment_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `cancelled_appointments`
--
ALTER TABLE `cancelled_appointments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `medical_records`
--
ALTER TABLE `medical_records`
  ADD PRIMARY KEY (`record_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_notifications_user` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_notifications`
--
ALTER TABLE `admin_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=116;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `appointment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=285;

--
-- AUTO_INCREMENT for table `cancelled_appointments`
--
ALTER TABLE `cancelled_appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=155;

--
-- AUTO_INCREMENT for table `medical_records`
--
ALTER TABLE `medical_records`
  MODIFY `record_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
