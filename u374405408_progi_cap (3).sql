-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Oct 23, 2025 at 06:16 AM
-- Server version: 11.8.3-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u374405408_progi_cap`
--

-- --------------------------------------------------------

--
-- Table structure for table `academic_paths`
--

CREATE TABLE `academic_paths` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `student_pending_student_id` bigint(20) UNSIGNED NOT NULL,
  `academic_year_id` bigint(20) UNSIGNED NOT NULL,
  `study_level` varchar(255) NOT NULL,
  `year_decision` varchar(255) NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `financial_status` varchar(255) NOT NULL DEFAULT 'full',
  `cohort` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `academic_paths`
--

INSERT INTO `academic_paths` (`id`, `student_pending_student_id`, `academic_year_id`, `study_level`, `year_decision`, `role_id`, `financial_status`, `cohort`, `created_at`, `updated_at`) VALUES
(1, 1, 3, '1ère Année (Ings1s2/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(2, 2, 3, '1ère Année (Ings1s2/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(3, 3, 3, '1ère Année (Ings1s2/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(4, 4, 3, '1ère Année (Ings1s2/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(5, 5, 3, '1ère Année (Ings1s2/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(6, 6, 3, '1ère Année (Ings1s2/GC)', 'Réorienté', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(7, 7, 3, '1ère Année (Ings1s2/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(8, 8, 3, '1ère Année (Ings1s2/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(9, 9, 3, '1ère Année (Ings1s2/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(10, 10, 3, '1ère Année (Ings1s2/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(11, 11, 3, '1ère Année (Ings1s2/GC)', 'Redouble', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(12, 12, 3, '1ère Année (Ings1s2/GC)', 'Redouble', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(13, 13, 3, '1ère Année (Ings1s2/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(14, 14, 3, '1ère Année (Ings1s2/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(15, 15, 3, '1ère Année (Ings1s2/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(16, 16, 3, '1ère Année (Ings1s2/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(17, 17, 3, '1ère Année (Ings1s2/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(18, 18, 3, '1ère Année (Ings1s2/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(19, 19, 3, '1ère Année (Ings1s2/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(20, 20, 3, '1ère Année (Ings1s2/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(21, 21, 3, '1ère Année (Ings1s2/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(22, 22, 3, '1ère Année (Ings1s2/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(23, 23, 3, '1ère Année (Ings1s2/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(24, 24, 3, '1ère Année (Ings1s2/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(25, 25, 3, '1ère Année (Ings1s2/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(26, 26, 3, '1ère Année (Ings1s2/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(27, 27, 3, '1ère Année (Ings1s2/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(28, 28, 3, '1ère Année (Ings1s2/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(29, 29, 3, '1ère Année (Ings1s2/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(30, 30, 3, '1ère Année (Ings1s2/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(31, 31, 3, '1ère Année (Ings1s2/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(32, 32, 3, '1ère Année (Ings1s2/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(33, 33, 3, '1ère Année (Ings1s2/GC)', 'Redouble', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(34, 34, 3, '1ère Année (Ings1s2/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(35, 35, 3, '1ère Année (Ings1s2/GC)', 'Redouble', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(36, 36, 3, '1ère Année (Ings1s2/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(37, 37, 3, '1ère Année (Ings1s2/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(38, 38, 3, '1ère Année (Ings1s2/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(39, 39, 3, '1ère Année (Ings1s2/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(40, 40, 3, '1ère Année (Ings1s2/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(41, 41, 3, '1ère Année (Ings1s2/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(42, 42, 3, '1ère Année (Ings1s2/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(43, 43, 3, '1ère Année (Ings1s2/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(44, 44, 3, '1ère Année (Ings1s2/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(45, 45, 3, '1ère Année (Ings1s2/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(46, 46, 3, '1ère Année (Ings1s2/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(47, 47, 3, '1ère Année (Ings1s2/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(48, 48, 3, '1ère Année (Ings1s2/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(49, 49, 3, '1ère Année (Ings1s2/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(50, 50, 3, '1ère Année (Ings1s2/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(51, 51, 3, '1ère Année (Ings1s2/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(52, 52, 3, '1ère Année (Ings1s2/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(53, 53, 3, '1ère Année (Ings1s2/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(54, 54, 3, '1ère Année (Ings1s2/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(55, 55, 3, '1ère Année (Ings1s2/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(56, 56, 3, '1ère Année (Ings1s2/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(57, 57, 3, '1ère Année (Ings1s2/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(58, 58, 3, '2ème Année (Ings3s4/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(59, 59, 3, '2ème Année (Ings3s4/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(60, 60, 3, '2ème Année (Ings3s4/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(61, 61, 3, '2ème Année (Ings3s4/GC)', 'Redouble', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(62, 62, 3, '2ème Année (Ings3s4/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(63, 63, 3, '2ème Année (Ings3s4/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(64, 64, 3, '2ème Année (Ings3s4/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(65, 65, 3, '2ème Année (Ings3s4/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(66, 66, 3, '2ème Année (Ings3s4/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(67, 67, 3, '2ème Année (Ings3s4/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(68, 68, 3, '2ème Année (Ings3s4/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(69, 69, 3, '2ème Année (Ings3s4/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(70, 70, 3, '2ème Année (Ings3s4/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(71, 71, 3, '2ème Année (Ings3s4/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(72, 72, 3, '2ème Année (Ings3s4/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(73, 73, 3, '2ème Année (Ings3s4/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(74, 74, 3, '2ème Année (Ings3s4/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(75, 75, 3, '2ème Année (Ings3s4/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(76, 76, 3, '2ème Année (Ings3s4/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(77, 77, 3, '2ème Année (Ings3s4/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(78, 78, 3, '2ème Année (Ings3s4/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(79, 79, 3, '2ème Année (Ings3s4/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(80, 80, 3, '2ème Année (Ings3s4/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(81, 81, 3, '2ème Année (Ings3s4/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(82, 82, 3, '2ème Année (Ings3s4/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(83, 83, 3, '2ème Année (Ings3s4/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(84, 84, 3, '2ème Année (Ings3s4/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(85, 85, 3, '2ème Année (Ings3s4/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(86, 86, 3, '2ème Année (Ings3s4/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(87, 87, 3, '2ème Année (Ings3s4/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(88, 88, 3, '2ème Année (Ings3s4/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(89, 89, 3, '2ème Année (Ings3s4/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(90, 90, 3, '2ème Année (Ings3s4/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(91, 91, 3, '2ème Année (Ings3s4/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(92, 92, 3, '2ème Année (Ings3s4/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(93, 93, 3, '2ème Année (Ings3s4/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(94, 94, 3, '2ème Année (Ings3s4/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(95, 95, 3, '2ème Année (Ings3s4/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(96, 96, 3, '2ème Année (Ings3s4/GC)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(97, 97, 3, '1ère Année (Ings1s2/GE)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(98, 98, 3, '1ère Année (Ings1s2/GE)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(99, 99, 3, '1ère Année (Ings1s2/GE)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(100, 100, 3, '1ère Année (Ings1s2/GE)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(101, 101, 3, '1ère Année (Ings1s2/GE)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(102, 102, 3, '1ère Année (Ings1s2/GE)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(103, 103, 3, '1ère Année (Ings1s2/GE)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(104, 104, 3, '1ère Année (Ings1s2/GE)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(105, 105, 3, '1ère Année (Ings1s2/GE)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(106, 106, 3, '1ère Année (Ings1s2/GE)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(107, 107, 3, '1ère Année (Ings1s2/GE)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(108, 108, 3, '1ère Année (Ings1s2/GE)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(109, 109, 3, '1ère Année (Ings1s2/GE)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(110, 110, 3, '1ère Année (Ings1s2/GE)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(111, 111, 3, '1ère Année (Ings1s2/GE)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(112, 112, 3, '2ème Année (Ings3s4/GE)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(113, 113, 3, '2ème Année (Ings3s4/GE)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(114, 114, 3, '2ème Année (Ings3s4/GE)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(115, 115, 3, '2ème Année (Ings3s4/GE)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(116, 116, 3, '2ème Année (Ings3s4/GE)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(117, 117, 3, '2ème Année (Ings3s4/GE)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(118, 118, 3, '2ème Année (Ings3s4/GE)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(119, 119, 3, '2ème Année (Ings3s4/GE)', 'Redouble', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(120, 120, 3, '2ème Année (Ings3s4/GE)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(121, 121, 3, '2ème Année (Ings3s4/GE)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(122, 122, 3, '2ème Année (Ings3s4/GE)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(123, 123, 3, '2ème Année (Ings3s4/GE)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(124, 124, 3, '2ème Année (Ings3s4/GE)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(125, 125, 3, '2ème Année (Ings3s4/GE)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(126, 126, 3, '1ère Année (Ings1s2/TOPO)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(127, 127, 3, '1ère Année (Ings1s2/TOPO)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(128, 128, 3, '1ère Année (Ings1s2/TOPO)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(129, 129, 3, '1ère Année (Ings1s2/TOPO)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(130, 130, 3, '1ère Année (Ings1s2/TOPO)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(131, 131, 3, '1ère Année (Ings1s2/TOPO)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(132, 132, 3, '1ère Année (Ings1s2/TOPO)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(133, 133, 3, '1ère Année (Ings1s2/TOPO)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(134, 134, 3, '1ère Année (Ings1s2/TOPO)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(135, 135, 3, '1ère Année (Ings1s2/TOPO)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(136, 136, 3, '1ère Année (Ings1s2/TOPO)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(137, 137, 3, '1ère Année (Ings1s2/TOPO)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(138, 138, 3, '1ère Année (Ings1s2/TOPO)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(139, 139, 3, '1ère Année (Ings1s2/TOPO)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(140, 140, 3, '1ère Année (Ings1s2/TOPO)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(141, 141, 3, '1ère Année (Ings1s2/TOPO)', 'Redouble', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(142, 142, 3, '1ère Année (Ings1s2/TOPO)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(143, 143, 3, '1ère Année (Ings1s2/TOPO)', 'Admis', 7, 'full', 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09');

-- --------------------------------------------------------

--
-- Table structure for table `academic_years`
--

CREATE TABLE `academic_years` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `submission_start` date NOT NULL,
  `submission_end` date NOT NULL,
  `academic_year` varchar(255) NOT NULL,
  `year_start` date NOT NULL,
  `year_end` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `academic_years`
--

INSERT INTO `academic_years` (`id`, `submission_start`, `submission_end`, `academic_year`, `year_start`, `year_end`, `created_at`, `updated_at`) VALUES
(2, '2025-06-20', '2025-12-31', '2025-2026', '2025-05-01', '2026-05-30', '2025-06-21 16:48:48', '2025-06-21 16:48:48'),
(3, '2023-06-20', '2023-08-21', '2023-2024', '2023-05-01', '2024-05-30', '2025-07-10 14:57:08', '2025-07-10 14:57:08'),
(4, '2025-07-01', '2025-07-31', '2024-2025', '2024-12-24', '2025-12-31', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `administrations`
--

CREATE TABLE `administrations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `rib_number` varchar(255) NOT NULL,
  `rib` varchar(255) NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `ifu_number` varchar(255) NOT NULL,
  `ifu` varchar(255) NOT NULL,
  `bank` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `administrations`
--

INSERT INTO `administrations` (`id`, `last_name`, `first_name`, `email`, `phone`, `password`, `role_id`, `rib_number`, `rib`, `photo`, `ifu_number`, `ifu`, `bank`, `created_at`, `updated_at`) VALUES
(1, 'SANYA', 'Max', 'owomax@gmail.com', '61332652', '$2y$12$QVGKdNVX7dA7QV07iaPcx.pylYW5wqS59KOHntf3ExJekgyEaLi3W', 2, '', '', NULL, '', '', '', '2024-08-10 15:28:05', '2024-08-10 15:28:05'),
(2, 'DOSSOU', 'Florence A.', 'fldossou@gmail.com', '96855759', '$2y$12$QVGKdNVX7dA7QV07iaPcx.pylYW5wqS59KOHntf3ExJekgyEaLi3W', 3, '', '', NULL, '', '', '', '2024-08-05 11:12:32', '2024-08-05 11:12:32'),
(3, 'TCHOBO', 'Fidèle Paul', 'fideletchobo@gmail.com', '97686201', '$2y$12$QVGKdNVX7dA7QV07iaPcx.pylYW5wqS59KOHntf3ExJekgyEaLi3W', 1, '', '', NULL, '', '', '', '2024-08-16 17:17:05', '2024-08-16 17:17:05'),
(4, 'AHOUNOU', 'Serge', 'ahounouserge@gmail.com', '97011862', '$2y$12$QVGKdNVX7dA7QV07iaPcx.pylYW5wqS59KOHntf3ExJekgyEaLi3W', 2, '', '', NULL, '', '', '', '2024-08-16 17:27:36', '2024-08-16 17:27:36'),
(5, 'ZANNOU', 'Julienne', 'zahoju22@gmail.com', '97589187', '$2y$12$QVGKdNVX7dA7QV07iaPcx.pylYW5wqS59KOHntf3ExJekgyEaLi3W', 4, '', '', NULL, '', '', '', '2024-08-16 17:27:36', '2024-08-16 17:27:36');

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

CREATE TABLE `classes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `department_id` bigint(20) UNSIGNED NOT NULL,
  `level` varchar(255) NOT NULL,
  `semester1_credits` int(11) NOT NULL,
  `semester2_credits` int(11) NOT NULL,
  `academic_year_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `hourly_mass` int(11) NOT NULL,
  `ue_id` bigint(20) UNSIGNED DEFAULT NULL,
  `credits` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `course_professors`
--

CREATE TABLE `course_professors` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `course_id` bigint(20) UNSIGNED NOT NULL,
  `professor_id` bigint(20) UNSIGNED NOT NULL,
  `principal` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `course_resources`
--

CREATE TABLE `course_resources` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `course_id` bigint(20) UNSIGNED NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cycles`
--

CREATE TABLE `cycles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `abbreviation` varchar(255) NOT NULL,
  `years_count` int(11) NOT NULL,
  `is_lmd` tinyint(1) NOT NULL,
  `type` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cycles`
--

INSERT INTO `cycles` (`id`, `name`, `abbreviation`, `years_count`, `is_lmd`, `type`, `created_at`, `updated_at`) VALUES
(1, 'Licence Professionnelle', 'DLP', 3, 1, 'undergraduate', '2025-06-20 12:09:26', '2025-06-20 12:09:26'),
(2, 'Master Professionnel', 'DMP', 2, 1, 'graduate', '2025-06-20 12:09:26', '2025-06-20 12:09:26'),
(3, 'Ingénierie', 'DIC', 5, 0, 'professional', '2025-06-20 12:09:26', '2025-06-20 12:09:26');

-- --------------------------------------------------------

--
-- Table structure for table `defense_jury_members`
--

CREATE TABLE `defense_jury_members` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `defense_submission_id` bigint(20) UNSIGNED NOT NULL,
  `professor_id` bigint(20) UNSIGNED DEFAULT NULL,
  `grade_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `role` enum('Président du Jury','Maitre de mémoire','Examinateur','Membre du Jury') DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `defense_submissions`
--

CREATE TABLE `defense_submissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `last_name` varchar(255) DEFAULT NULL,
  `first_names` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `contacts` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`contacts`)),
  `student_id_number` varchar(255) DEFAULT NULL,
  `defense_submission_period_id` bigint(20) UNSIGNED NOT NULL,
  `thesis_title` varchar(255) NOT NULL,
  `department_id` bigint(20) UNSIGNED NOT NULL,
  `professor_id` bigint(20) UNSIGNED DEFAULT NULL,
  `files` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`files`)),
  `status` enum('pending','accepted','rejected') NOT NULL DEFAULT 'pending',
  `defense_type` enum('licence','master','engineering') NOT NULL,
  `rejection_reason` text DEFAULT NULL,
  `room_id` bigint(20) UNSIGNED DEFAULT NULL,
  `defense_date` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `defense_submission_periods`
--

CREATE TABLE `defense_submission_periods` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `academic_year_id` bigint(20) UNSIGNED NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `defense_submission_periods`
--

INSERT INTO `defense_submission_periods` (`id`, `uuid`, `academic_year_id`, `start_date`, `end_date`, `created_at`, `updated_at`) VALUES
(2, '1beee673-58c3-11f0-bd07-128873a8f0b9', 2, '2025-11-01 00:00:00', '2025-12-10 00:00:00', '2025-07-04 10:39:05', '2025-07-04 10:39:05');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `abbreviation` varchar(255) NOT NULL,
  `cycle_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`, `abbreviation`, `cycle_id`, `created_at`, `updated_at`) VALUES
(1, 'Génie Civil', 'GC', 1, '2024-08-03 15:26:13', '2024-08-03 15:26:13'),
(2, 'Génie Electrique', 'GE', 1, '2024-08-03 15:26:39', '2024-08-03 15:26:39'),
(3, 'Géomètre Topographe', 'GT', 1, '2024-08-03 15:27:03', '2024-08-03 15:27:03'),
(4, 'Production Animale', 'PA', 1, '2024-08-03 15:27:21', '2024-08-03 15:27:21'),
(5, 'Production Végétale', 'PV', 1, '2024-08-03 15:27:35', '2024-08-03 15:27:35'),
(6, 'Génie de l\'Environnement', 'Gen', 1, '2024-08-03 15:27:59', '2024-08-03 15:27:59'),
(7, 'Hygiène et Contrôle de Qualité', 'HCQ', 1, '2024-08-03 15:28:21', '2024-08-03 15:28:21'),
(8, 'Biohygiène et Sécurité Sanitaire', 'BSS', 1, '2024-08-03 15:28:37', '2024-08-03 15:28:37'),
(9, 'Analyses Biomédicales', 'ABM', 1, '2024-08-03 15:28:58', '2024-08-03 15:28:58'),
(10, 'Nutrition, Diététique et Technologie Alimentaire', 'NDTA', 1, '2024-08-03 15:29:20', '2024-08-03 15:29:20'),
(11, 'Génie Rural', 'GR', 1, '2024-08-03 15:29:41', '2024-08-03 15:29:41'),
(12, 'Maintenance Industrielle', 'MI', 1, '2024-08-03 15:30:04', '2024-08-03 15:30:04'),
(13, 'Mécanique Automobile', 'MA', 1, '2024-08-03 15:30:21', '2024-08-03 15:30:21'),
(14, 'Hydraulique', 'HYD', 1, '2024-08-03 15:30:38', '2024-08-03 15:30:38'),
(15, 'Fabrication Mécanique', 'FM', 1, '2024-08-03 15:30:56', '2024-08-03 15:30:56'),
(16, 'Froid et Climatisation', 'FC', 1, '2024-08-03 15:31:12', '2024-08-03 15:31:12'),
(17, 'Production Végétale et Post-Récolte', 'PVPR', 2, '2024-08-03 15:32:07', '2024-08-03 15:32:07'),
(18, 'Génie Civil', 'GC', 3, '2024-08-03 15:35:04', '2024-08-03 15:35:04'),
(19, 'Géomètre Topographe', 'GT', 3, '2024-08-03 15:35:27', '2024-08-03 15:35:27'),
(20, 'Génie Electrique', 'GE', 3, '2024-08-03 15:35:43', '2024-08-03 15:35:43'),
(21, 'Génie Mécanique et Energétique', 'GME', 1, '2024-08-19 16:18:20', '2024-08-19 16:18:20'),
(22, 'Génie Mécanique et Productique', 'GMP', 1, '2024-08-19 16:18:38', '2024-08-19 16:18:38'),
(23, 'Génie Mécanique et Energétique', 'GME', 3, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `entry_diplomas`
--

CREATE TABLE `entry_diplomas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `entry_diplomas`
--

INSERT INTO `entry_diplomas` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'Baccalaureat Scientifique', NULL, NULL),
(2, 'Licence Professionnelle', NULL, NULL),
(3, 'DUT', NULL, NULL),
(4, 'BTS', NULL, NULL),
(5, 'DEAT', NULL, NULL),
(6, 'DTI', NULL, NULL),
(7, 'Certificat de Classes Préparatoires', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grades`
--

CREATE TABLE `grades` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `name` varchar(255) NOT NULL,
  `abbreviation` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `grades`
--

INSERT INTO `grades` (`id`, `uuid`, `name`, `abbreviation`, `created_at`, `updated_at`) VALUES
(1, 'd6f77a88-7792-4fba-a5a0-6fa609fdc607', 'Professeur', 'Pr', NULL, NULL),
(2, '9cdcd42c-4f28-4717-9c0d-cf0c9a609a55', 'Maître de Conférences', 'MC', NULL, NULL),
(3, 'e193d472-8eb1-446f-aadf-68f2f862f7b0', 'Docteur', 'Dr', NULL, NULL),
(4, '42604b72-dee1-47f2-a3ee-aae56c745136', 'Maître Assistant', 'Dr(MA)', NULL, NULL),
(5, '18b5f010-305a-4990-9ae6-2e31779738a2', 'Monsieur', 'M.', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `jury_fees`
--

CREATE TABLE `jury_fees` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `degree_type` varchar(255) NOT NULL,
  `role` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `jury_fees`
--

INSERT INTO `jury_fees` (`id`, `degree_type`, `role`, `amount`, `created_at`, `updated_at`) VALUES
(1, 'engineering', 'Président du Jury', 10000.00, '2025-07-30 13:17:13', '2025-07-30 13:17:13'),
(2, 'engineering', 'Maitre de mémoire', 7500.00, '2025-07-30 13:19:58', '2025-07-30 13:19:58'),
(3, 'engineering', 'Examinateur', 7500.00, '2025-07-30 13:21:15', '2025-07-30 13:21:15'),
(4, 'engineering', 'Membre du Jury', 7500.00, '2025-07-30 13:22:46', '2025-07-30 13:22:46');

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2014_10_12_000000_create_users_table', 1),
(2, '2014_10_12_100000_create_password_reset_tokens_table', 1),
(3, '2019_08_19_000000_create_failed_jobs_table', 1),
(4, '2019_12_14_000001_create_personal_access_tokens_table', 1),
(5, '2025_06_05_064644_create_roles_table', 1),
(6, '2025_06_05_064645_create_administrations_table', 1),
(7, '2025_06_05_064704_create_academic_years_table', 1),
(8, '2025_06_05_064705_create_cycles_table', 1),
(9, '2025_06_05_064706_create_departments_table', 1),
(10, '2025_06_05_064712_create_classes_table', 1),
(11, '2025_06_05_064734_create_entry_diploma_table', 1),
(12, '2025_06_05_064753_create_students_table', 1),
(13, '2025_06_05_064754_create_personal_informations_table', 1),
(14, '2025_06_05_064801_create_pending_students_table', 1),
(15, '2025_06_05_064813_create_student_pending_students_table', 1),
(16, '2025_06_05_064814_create_academic_path_table', 1),
(17, '2025_06_05_064909_create_submission_periods_table', 1),
(18, '2025_06_05_064919_create_reclamation_periods_table', 1),
(19, '2025_06_05_064930_create_professors_table', 1),
(20, '2025_06_07_065944_create_sessions_table', 1),
(21, '2025_06_05_064929_create_grades_table', 2),
(22, '2025_06_20_114000_create_rooms_table', 3),
(23, '2025_06_20_222827_create_defense_submission_periods_table', 4),
(24, '2025_06_20_224829_create_defense_jury_members_table', 5),
(25, '2025_06_30_071808_create_ues_table', 6),
(26, '2025_06_30_071809_create_courses_table', 7),
(27, '2025_06_30_071854_create_course_professors_table', 8),
(28, '2025_06_30_071855_create_programs_table', 9),
(29, '2025_06_30_165602_create_course_resources_table', 10),
(30, '2025_07_29_170536_create_jury_fees_table', 11);

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pending_students`
--

CREATE TABLE `pending_students` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `personal_information_id` bigint(20) UNSIGNED NOT NULL,
  `tracking_code` varchar(255) DEFAULT NULL,
  `cuca_opinion` varchar(255) DEFAULT NULL,
  `cuca_comment` text DEFAULT NULL,
  `cuo_opinion` varchar(255) DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `sent_mail_cuca` tinyint(1) NOT NULL DEFAULT 0,
  `mailing_number` int(11) NOT NULL DEFAULT 0,
  `documents` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `level` varchar(255) NOT NULL,
  `entry_diploma_id` bigint(20) UNSIGNED NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `academic_year_id` bigint(20) UNSIGNED NOT NULL,
  `department_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pending_students`
--

INSERT INTO `pending_students` (`id`, `personal_information_id`, `tracking_code`, `cuca_opinion`, `cuca_comment`, `cuo_opinion`, `rejection_reason`, `sent_mail_cuca`, `mailing_number`, `documents`, `level`, `entry_diploma_id`, `photo`, `academic_year_id`, `department_id`, `created_at`, `updated_at`) VALUES
(8, 8, '11261012', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(9, 9, '21381024', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(10, 10, '11471415', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(11, 11, '1110705', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(12, 12, '10652712', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(13, 13, '1372304', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(14, 14, '21380924', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(15, 15, '11417515', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(16, 16, '14512324', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(17, 17, '1808803', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(18, 18, '19254713', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(19, 19, '11324815', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(20, 20, '10984919', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(21, 21, '11111412', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(22, 22, '21381424', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(23, 23, '1212203', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(24, 24, '21381124', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(25, 25, '11159013', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(26, 26, '11356910', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(27, 27, '21381224', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(28, 28, '1743005', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(29, 29, '11084607', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(30, 30, '1483505', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(31, 31, '10220208', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(32, 32, '11154209', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(33, 33, '19835114', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(34, 34, '21380324', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(35, 35, '21380824', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(36, 36, '21380724', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(37, 37, '11261409', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(38, 38, '11144406', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(39, 39, '11159614', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(40, 40, '10748108', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(41, 41, '1519004', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(42, 42, '97133991', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(43, 43, '20538618', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(44, 44, '21380024', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(45, 45, '13605216', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(46, 46, '21381324', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(47, 47, '10543607', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(48, 48, '11376412', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(49, 49, '21380624', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(50, 50, '21380224', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(51, 51, '10292107', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(52, 52, '21380524', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(53, 53, '21380124', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(54, 54, '18627218', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(55, 55, '66967618', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(56, 56, '10598408', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(57, 57, '10219908', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(58, 58, '17654214', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(59, 59, '12172816', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(60, 60, '10351208', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(61, 61, '12424313', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(62, 62, '10512608', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(63, 63, '11223515', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(64, 64, '21380424', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(65, 65, '10029109', NULL, NULL, NULL, NULL, 0, 0, NULL, '2ème Année (Ings3s4/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(66, 66, '13634216', NULL, NULL, NULL, NULL, 0, 0, NULL, '2ème Année (Ings3s4/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(67, 67, '10301012', NULL, NULL, NULL, NULL, 0, 0, NULL, '2ème Année (Ings3s4/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(68, 68, '21294723', NULL, NULL, NULL, NULL, 0, 0, NULL, '2ème Année (Ings3s4/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(69, 69, '21295323', NULL, NULL, NULL, NULL, 0, 0, NULL, '2ème Année (Ings3s4/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(70, 70, '10328509', NULL, NULL, NULL, NULL, 0, 0, NULL, '2ème Année (Ings3s4/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(71, 71, '10436109', NULL, NULL, NULL, NULL, 0, 0, NULL, '2ème Année (Ings3s4/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(72, 72, '227301', NULL, NULL, NULL, NULL, 0, 0, NULL, '2ème Année (Ings3s4/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(73, 73, '21294923', NULL, NULL, NULL, NULL, 0, 0, NULL, '2ème Année (Ings3s4/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(74, 74, '19216618', NULL, NULL, NULL, NULL, 0, 0, NULL, '2ème Année (Ings3s4/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(75, 75, '20042515', NULL, NULL, NULL, NULL, 0, 0, NULL, '2ème Année (Ings3s4/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(76, 76, 'ABGC1323', NULL, NULL, NULL, NULL, 0, 0, NULL, '2ème Année (Ings3s4/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(77, 77, '309716708', NULL, NULL, NULL, NULL, 0, 0, NULL, '2ème Année (Ings3s4/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(78, 78, '21295223', NULL, NULL, NULL, NULL, 0, 0, NULL, '2ème Année (Ings3s4/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(79, 79, '21295523', NULL, NULL, NULL, NULL, 0, 0, NULL, '2ème Année (Ings3s4/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(80, 80, '21295623', NULL, NULL, NULL, NULL, 0, 0, NULL, '2ème Année (Ings3s4/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(81, 81, '10721412', NULL, NULL, NULL, NULL, 0, 0, NULL, '2ème Année (Ings3s4/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(82, 82, '1429504', NULL, NULL, NULL, NULL, 0, 0, NULL, '2ème Année (Ings3s4/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(83, 83, '11013606', NULL, NULL, NULL, NULL, 0, 0, NULL, '2ème Année (Ings3s4/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(84, 84, '2039805', NULL, NULL, NULL, NULL, 0, 0, NULL, '2ème Année (Ings3s4/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(85, 85, '11140912', NULL, NULL, NULL, NULL, 0, 0, NULL, '2ème Année (Ings3s4/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(86, 86, '10395010', NULL, NULL, NULL, NULL, 0, 0, NULL, '2ème Année (Ings3s4/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(87, 87, '11087111', NULL, NULL, NULL, NULL, 0, 0, NULL, '2ème Année (Ings3s4/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(88, 88, '10889010', NULL, NULL, NULL, NULL, 0, 0, NULL, '2ème Année (Ings3s4/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(89, 89, '11238106', NULL, NULL, NULL, NULL, 0, 0, NULL, '2ème Année (Ings3s4/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(90, 90, '21299723', NULL, NULL, NULL, NULL, 0, 0, NULL, '2ème Année (Ings3s4/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(91, 91, '12176009', NULL, NULL, NULL, NULL, 0, 0, NULL, '2ème Année (Ings3s4/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(92, 92, '12265109', NULL, NULL, NULL, NULL, 0, 0, NULL, '2ème Année (Ings3s4/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(93, 93, '21295723', NULL, NULL, NULL, NULL, 0, 0, NULL, '2ème Année (Ings3s4/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(94, 94, '21294823', NULL, NULL, NULL, NULL, 0, 0, NULL, '2ème Année (Ings3s4/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(95, 95, '17791914', NULL, NULL, NULL, NULL, 0, 0, NULL, '2ème Année (Ings3s4/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(96, 96, '21295423', NULL, NULL, NULL, NULL, 0, 0, NULL, '2ème Année (Ings3s4/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(97, 97, '10848811', NULL, NULL, NULL, NULL, 0, 0, NULL, '2ème Année (Ings3s4/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(98, 98, '89999', NULL, NULL, NULL, NULL, 0, 0, NULL, '2ème Année (Ings3s4/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(99, 99, '10888112', NULL, NULL, NULL, NULL, 0, 0, NULL, '2ème Année (Ings3s4/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(100, 100, '10147112', NULL, NULL, NULL, NULL, 0, 0, NULL, '2ème Année (Ings3s4/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(101, 101, '10017410', NULL, NULL, NULL, NULL, 0, 0, NULL, '2ème Année (Ings3s4/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(102, 102, '19484414', NULL, NULL, NULL, NULL, 0, 0, NULL, '2ème Année (Ings3s4/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(103, 103, '11203312', NULL, NULL, NULL, NULL, 0, 0, NULL, '2ème Année (Ings3s4/GC)', 7, NULL, 3, 1, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(104, 104, '67300053', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/GE)', 7, NULL, 3, 2, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(105, 105, '10522310', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/GE)', 7, NULL, 3, 2, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(106, 106, '11746815', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/GE)', 7, NULL, 3, 2, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(107, 107, '10033808', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/GE)', 7, NULL, 3, 2, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(108, 108, '21273723', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/GE)', 7, NULL, 3, 2, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(109, 109, '12349913', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/GE)', 7, NULL, 3, 2, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(110, 110, '1879603', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/GE)', 7, NULL, 3, 2, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(111, 111, '21379824', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/GE)', 7, NULL, 3, 2, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(112, 112, '96311584', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/GE)', 7, NULL, 3, 2, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(113, 113, '11368419', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/GE)', 7, NULL, 3, 2, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(114, 114, '11544810', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/GE)', 7, NULL, 3, 2, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(115, 115, '11503710', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/GE)', 7, NULL, 3, 2, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(116, 116, '1734505', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/GE)', 7, NULL, 3, 2, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(117, 117, '11746915', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/GE)', 7, NULL, 3, 2, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(118, 118, '21379924', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/GE)', 7, NULL, 3, 2, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(119, 119, '10911410', NULL, NULL, NULL, NULL, 0, 0, NULL, '2ème Année (Ings3s4/GE)', 7, NULL, 3, 2, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(120, 120, '30278718', NULL, NULL, NULL, NULL, 0, 0, NULL, '2ème Année (Ings3s4/GE)', 7, NULL, 3, 2, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(121, 121, '11009306', NULL, NULL, NULL, NULL, 0, 0, NULL, '2ème Année (Ings3s4/GE)', 7, NULL, 3, 2, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(122, 122, '1630104', NULL, NULL, NULL, NULL, 0, 0, NULL, '2ème Année (Ings3s4/GE)', 7, NULL, 3, 2, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(123, 123, '21297123', NULL, NULL, NULL, NULL, 0, 0, NULL, '2ème Année (Ings3s4/GE)', 7, NULL, 3, 2, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(124, 124, '10615912', NULL, NULL, NULL, NULL, 0, 0, NULL, '2ème Année (Ings3s4/GE)', 7, NULL, 3, 2, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(125, 125, '21297023', NULL, NULL, NULL, NULL, 0, 0, NULL, '2ème Année (Ings3s4/GE)', 7, NULL, 3, 2, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(126, 126, '12163109', NULL, NULL, NULL, NULL, 0, 0, NULL, '2ème Année (Ings3s4/GE)', 7, NULL, 3, 2, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(127, 127, '10846111', NULL, NULL, NULL, NULL, 0, 0, NULL, '2ème Année (Ings3s4/GE)', 7, NULL, 3, 2, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(128, 128, '14185014', NULL, NULL, NULL, NULL, 0, 0, NULL, '2ème Année (Ings3s4/GE)', 7, NULL, 3, 2, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(129, 129, '21297223', NULL, NULL, NULL, NULL, 0, 0, NULL, '2ème Année (Ings3s4/GE)', 7, NULL, 3, 2, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(130, 130, '21296923', NULL, NULL, NULL, NULL, 0, 0, NULL, '2ème Année (Ings3s4/GE)', 7, NULL, 3, 2, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(131, 131, '10136508', NULL, NULL, NULL, NULL, 0, 0, NULL, '2ème Année (Ings3s4/GE)', 7, NULL, 3, 2, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(132, 132, '21159522', NULL, NULL, NULL, NULL, 0, 0, NULL, '2ème Année (Ings3s4/GE)', 7, NULL, 3, 2, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(133, 133, '15968117', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/TOPO)', 7, NULL, 3, 3, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(134, 134, '21381724', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/TOPO)', 7, NULL, 3, 3, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(135, 135, '21381924', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/TOPO)', 7, NULL, 3, 3, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(136, 136, '19247418', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/TOPO)', 7, NULL, 3, 3, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(137, 137, '12171016', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/TOPO)', 7, NULL, 3, 3, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(138, 138, '21381524', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/TOPO)', 7, NULL, 3, 3, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(139, 139, '10513012', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/TOPO)', 7, NULL, 3, 3, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(140, 140, '1248305', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/TOPO)', 7, NULL, 3, 3, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(141, 141, '16399716', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/TOPO)', 7, NULL, 3, 3, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(142, 142, '10180411', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/TOPO)', 7, NULL, 3, 3, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(143, 143, '11282419', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/TOPO)', 7, NULL, 3, 3, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(144, 144, '21381824', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/TOPO)', 7, NULL, 3, 3, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(145, 145, '21297723', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/TOPO)', 7, NULL, 3, 3, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(146, 146, '10276608', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/TOPO)', 7, NULL, 3, 3, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(147, 147, '1553305', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/TOPO)', 7, NULL, 3, 3, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(148, 148, '21297623', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/TOPO)', 7, NULL, 3, 3, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(149, 149, '11086110', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/TOPO)', 7, NULL, 3, 3, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(150, 150, '21381624', NULL, NULL, NULL, NULL, 0, 0, NULL, '1ère Année (Ings1s2/TOPO)', 7, NULL, 3, 3, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(152, 152, 'CAP-kcsVhZCuYN', 'Favorable', 'Favorable : sous-réserve de la licence Professionnelle (CAP) avec le dossier physique.', 'Favorable', NULL, 1, 2, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/demande_da\\/xhxfMp00xvApy0AZZy3BHJuzBE1GE99ocBvN3k18.jpg\",\"Curriculum Vitae\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/cv\\/bZECyet6PpHDI5fjEbSf6ghzu1JDOVhGUDrpa1kJ.jpg\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/acte_naissance\\/DYrZ1fxiL1tcn9YqMTnKQZetWaw4CFmvqHddBDVA.jpg\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_bac\\/YiFntZp0DLsM2hoZRYGZDmXcOLkstlNCOqGFYL34.jpg\",\"Attestation de travail\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_travail\\/LV1BJgmkduWu7q9fkNzpA2sdqqdWwXeTCtWudBlB.jpg\",\"Quittance Rectorat de 2.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_rectorat\\/c2KCvLMNqRLdZZJMTIlwM0TZjmivaqQNVHXHzq2g.jpg\",\"Quittance de 15.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_cap\\/RLXXBQkNjojNu3LBkM7GLrY0jRTNSfBBMsigD0tO.jpg\",\"Dipl\\u00f4me ou Attestation de licence professionnelle(Compl\\u00e9ment)\":\"dossiers\\/2025\\/complement\\/Dipl\\u00f4me ou Attestation de licence professionnelle\\/oTb5qLre0egj8KOGgvzrPDMbIXnC7EGcp0HSADNO.pdf\",\"Quittance Cap(Compl\\u00e9ment)\":\"dossiers\\/2025\\/complement\\/Quittance Cap\\/wIIFML2m8A2a1OdLr8CkamrAe09PMvgUVfEqTRmP.jpg\"}', '1', 6, 'dossiers/2025/photos/1qxS5Mx0TO7VFeZE8J3cgtNpYmu3Byg2mbtyt831.jpg', 4, 19, '2025-07-11 16:28:21', '2025-10-22 17:18:55'),
(153, 153, 'CAP-Tg4pM9lfli', 'Favorable', 'Favorable : sous-réserve de la licence Professionnelle (CAP) avec le dossier physique', 'Favorable', NULL, 1, 2, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/demande_da\\/x97fBpLrptFxnF2A46aDRq98OvsNwPnIJgtUJmPe.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/cv\\/xXTDIWx7RMPqoOGyf2iF9DWpkjzbVuOYSsvgIWLi.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/acte_naissance\\/h8C7sL7ZxqMabZL1BYHOJB6T74VPLQX6Jz387sAC.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_bac\\/VyA6xmzjSIxr2x4L4PIPoXWSBonYoUGfxaO96E3X.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_travail\\/8thiSziSKED4l156YgBXuKwqBtzMWl0eEG2k0QjE.pdf\",\"Quittance Rectorat de 2.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_rectorat\\/DwI9ymIV0ciyINTvDNdDouS2kP1O3jxfPNaewnQi.pdf\",\"Quittance de 15.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_cap\\/nRO1H6FvQwztsQqa5wg2HgW7nHP1qt6WpJlFG38a.pdf\"}', '1', 6, 'dossiers/2025/photos/9R5czXHM15HuLAM5tdoHQnNadMPynYL50LtDAWwA.jpg', 4, 19, '2025-07-14 10:55:54', '2025-08-30 19:25:36'),
(154, 154, 'CAP-Koq7OBr3Ie', 'Favorable', 'Favorable : sous-réserve de la licence Professionnelle (CAP) avec le dossier physique', NULL, NULL, 1, 2, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/demande_da\\/gungVQgpzwc9rxf9017F6b1XLpnqrkYtndLGd2Lg.jpg\",\"Curriculum Vitae\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/cv\\/tQDsQmBMq9GTsU5ARpHtmMKjgGCVyoOfjZ3uzwlo.jpg\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/acte_naissance\\/4uwg3szlmdPhwncpD8KGidElpOTozdzM6Df1SVsk.jpg\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_bac\\/CyI3KOZ9sOlQ5WRH710ArI5ffGTMUVaSUqyEFOcg.jpg\",\"Attestation de travail\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_travail\\/TqdANyCi4zeURboJyYKMJUWzW0zo9jf4x1uXzlB2.jpg\",\"Quittance Rectorat de 2.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_rectorat\\/Z8VeCLxALhyDi50RLEykf417wU7g1lCuRqJjLXpl.jpg\",\"Quittance de 15.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_cap\\/LupjzmYmAvCcLKXriNIkJxTgCG77sErcd1TuUU0U.jpg\",\"Dipl\\u00f4me ou Attestation de licence professionnelle(Compl\\u00e9ment)\":\"dossiers\\/2025\\/complement\\/Dipl\\u00f4me ou Attestation de licence professionnelle\\/4NtqZrmmmExon31216oMi1NuKdmUFmpMpVKeAVgz.pdf\"}', '1', 6, 'dossiers/2025/photos/An8ytKRMrD5HzRK2da8W4z8QAmDW0U5zeWUol1jw.jpg', 4, 19, '2025-07-14 11:04:20', '2025-10-13 16:50:26'),
(156, 156, 'CAP-L8rApcMbqy', 'Favorable', 'Favorable : sous-réserve de la licence Professionnelle (CAP) avec le dossier physique', NULL, NULL, 1, 2, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/demande_da\\/60Ja2pgDWtt0y3s7ek6HFiJDBExP1AQj2Vpl3y8b.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/cv\\/MnfobVlYxNQpNzY95Sjspfy9rgr2eIYQZAnGRTbc.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/acte_naissance\\/jHrM13iNIP30uDokQagbnIHEa9iTbnU2zMmUuvQa.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_bac\\/dbbqYAF8jSKNuZ6wYH5M9C9MYCGngjYRmZxYsxEG.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_travail\\/UHTjfkeMCzBPA4vyrc94thuFiBJRxTIQzcj6nuzr.pdf\",\"Quittance Rectorat de 2.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_rectorat\\/N1Zf1lfOen0yLVl69AOq3KGOOAwCp9c08r4PcpIr.pdf\",\"Quittance de 15.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_cap\\/GcmcMfTqygfZM0hYwTZwYayTbTqfr4D0HqmkYv3q.pdf\"}', '1', 6, 'dossiers/2025/photos/i7zA1JHQpuR4UsnqLjBUQUroriUQXuIGLkqk2Ht3.jpg', 4, 19, '2025-07-15 15:27:41', '2025-08-30 18:59:42'),
(158, 158, 'CAP-hDILeh4uZM', 'Favorable', 'Favorable : sous-réserve de la licence Professionnelle (CAP) avec le dossier physique', NULL, NULL, 1, 2, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/demande_da\\/wF9QgSmf9YBZyhNAnwE3Me2HWeIP4kr5vWwN4Vdo.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/cv\\/aJH84RbVK2m0VQQhAenBq1hvDf9ZAjLPVMYtMsHC.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/acte_naissance\\/925ZQdgBnSPiKErE7aVntvVjn4J9qCnhrWmNeAb5.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_bac\\/DmdKJSnh0pIzJtx7u0hxlCcwyuF0ebeqolIpeW3p.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_travail\\/nqdf6Q1ejhlTlTxUOLoA6lDNI5ULjJ8eWMirRvJ5.pdf\",\"Quittance Rectorat de 2.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_rectorat\\/r8eWlYSHF3tL76EcbTzkEsjh9OYkxlyseQ1o2IdG.pdf\",\"Quittance de 15.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_cap\\/PTFfHjdhmC9iUNpYTtv7X0lGvfHAQVL8Bj94Dd5d.pdf\",\"Dipl\\u00f4me ou Attestation de licence professionnelle(Compl\\u00e9ment)\":\"dossiers\\/2025\\/complement\\/Dipl\\u00f4me ou Attestation de licence professionnelle\\/GYL1PqtQoi28o9jx1pvMWqArX1b8EbFPA5hS3VBc.pdf\",\"Quittance Cap(Compl\\u00e9ment)\":\"dossiers\\/2025\\/complement\\/Quittance Cap\\/wYWVcn6e0VdrmR8CyOkFOez7lWYaOeqxypzB7aTy.jpg\"}', '1', 1, 'dossiers/2025/photos/7dGLPczxvijQED4UbakCla1sELZ6aNVfld5oP3oH.jpg', 4, 19, '2025-07-18 07:57:01', '2025-10-22 08:51:50'),
(160, 160, 'CAP-PufBrdoAGk', 'Favorable', 'Favorable', NULL, NULL, 1, 2, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/demande_da\\/EJiQMObuzwj21ETE9P4SnWA5IbdOQjkpuiq974o8.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/cv\\/Mrd0YXMueOC3kM8e35q5ddsP3FuLrkfy7D5WVmdT.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/acte_naissance\\/os2Q3ultSuetmkLkDoiphhSli1oMpwGKg3iJKaN0.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_bac\\/DngX1apl0ascbYwhLFrJjFBDahHv39FnWJ44kXnk.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_travail\\/CfyXQIint3ClWWrboS3oHt8w3cHUQgkrOeozq0nn.pdf\",\"Quittance de 20.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_cap\\/HLoXmK8Twt8I6WvylDfRfGDMdIVChRk958YYVkRz.pdf\"}', '1', 1, 'dossiers/2025/photos/9i4UAVq5LhSBxSiLHLf0Bc0m678nzjTGYkr7O1WI.png', 4, 18, '2025-07-20 20:21:44', '2025-09-01 11:54:29'),
(161, 161, 'CAP-WZwGsGpCwC', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/licence professionnelle\\/demande_da\\/S7ijGrgES7KqyZwwao7YwtNqTLM86gWNQidbgBg3.jpg\",\"Curriculum Vitae\":\"dossiers\\/2025\\/licence professionnelle\\/cv\\/ZAh6CSOK5OOQJIvIZxGKUkSRGoX7lhWzqnntWKbz.jpg\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/licence professionnelle\\/acte_naissance\\/K8Ybq0p9vdFqag2inTMr4J75MuX7sO66SMIMHKDm.jpg\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC ou \\u00e9quivalent\":\"dossiers\\/2025\\/licence professionnelle\\/diplome_bac\\/SgQHljB3ld6eyzH4vS6cohN5nfslodx7ES4wKXmi.jpg\",\"Attestation de travail\":\"dossiers\\/2025\\/licence professionnelle\\/attestation_travail\\/cqvPV3lbiXcbXKuZLwfkGNPwS8zA6o8x5DAhDGmm.jpg\",\"Quittance Rectorat de 2.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_rectorat\\/Ou8iw0CSwhQvEmooW501iELq7a0Ottlg0m6isMH7.jpg\",\"Quittance de 10.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_cap\\/T4gmOMZpZT7lxlTifkFP7JFkzMoRzeBiSCEATqnc.jpg\"}', '1', 1, 'dossiers/2025/photos/qOltPkC8CBNaoA3Crdp5gYVimpza9HsikPGcyFLM.jpg', 2, 1, '2025-08-05 13:14:29', '2025-08-05 13:14:29'),
(162, 162, 'CAP-qnzBVhAssK', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/licence professionnelle\\/demande_da\\/oDH8uINYPloXBvyphML73TA7Jn4Q2s6hdbF9HAeg.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/licence professionnelle\\/cv\\/G0O93qL3ySQw819NaVryoYiAls6MLVBmv8veFvVm.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/licence professionnelle\\/acte_naissance\\/jENNdhGXEiobBhIF0h9LUwlcrdfyedd43JONLlng.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC ou \\u00e9quivalent\":\"dossiers\\/2025\\/licence professionnelle\\/diplome_bac\\/Fn4rPuNVloUr5KtZMNrVvH87m8rymof8fn8bQh9u.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/licence professionnelle\\/attestation_travail\\/aDwrXZN9MwAMyHLWVxbtP0QMjsPtufhSVTyfDp22.pdf\",\"Quittance Rectorat de 2.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_rectorat\\/fx4TVWQKIspByhKBWrDP9L4RWpuhzRmUyRkEKSAE.pdf\",\"Quittance de 10.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_cap\\/CZriynxKLiVuoxYxxHU34LlrgicQOtnE07736ac3.pdf\"}', '1', 6, 'dossiers/2025/photos/AXfNXxlUHlENY839Jdc7RykqNHnmEqZvlJvxDUbR.png', 2, 3, '2025-08-06 11:30:44', '2025-08-06 11:30:44'),
(163, 163, 'CAP-XRVMqw4RFD', 'Favorable', 'Favorable : dossier bien complet', 'Favorable', NULL, 1, 2, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/demande_da\\/aIdnFYsqNPSLVaoPWPfr2cSJkgMNh09DRMSGJrdT.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/cv\\/lWxt4zLnrb9b0NNzteVeddOa7O5rcwVnLYM0Kihr.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/acte_naissance\\/7XwvIJr0BehGMB4uKcwWEa91PoDuSrlAjOAItQpz.jpg\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_bac\\/UrofPVOtcIOd43nDDgb9gp51YwsTS7IINOGLAnPG.jpg\",\"Attestation de travail\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_travail\\/aab50Rz3Ldf1UyxYEZL5Odu4m8CfK6uOekeduwJa.jpg\",\"Quittance de 20.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_cap\\/k2MtPHFMwqlvKEgGEwiFukeMjDmgNUZYh89WJc1K.jpg\"}', '1', 2, 'dossiers/2025/photos/kB3diU3L4c7r6M9kK0MJ2mH9IFOpFIFOLrSRAzLF.jpg', 4, 18, '2025-08-06 11:58:35', '2025-08-30 19:27:31'),
(164, 164, 'CAP-F9t34IAlyF', 'Favorable', 'Favorable : dossier bien complet', 'Favorable', NULL, 1, 2, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/demande_da\\/0AdQWn1lMAQVTiQ0lexcs2MkY3r0GfIpdyua5Jjm.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/cv\\/sfZT2Nxi9o4Jcqf2BVddLMrzvtJSHBmbIxqtUDZz.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/acte_naissance\\/yeB0CQfZOXelxEs8kWqhqBBomQjUTcwv3HjM22td.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_bac\\/d0GTs60oTwhmoiYGbCgwTVFx81J15mwnaSjzo1Mo.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_travail\\/06JChkPGrhBc1z37iLIlzj7j47KJVMunvNgOJK71.pdf\",\"Quittance de 20.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_cap\\/6RBYAklHkSC9ewKq6kR5silefcY96zzlPPRhJdB3.pdf\"}', '1', 2, 'dossiers/2025/photos/nH8kJyAWoEpX5hXri8fBXk0fi0K34QAelsFNBm2v.jpg', 4, 18, '2025-08-06 12:32:52', '2025-08-30 19:27:48'),
(165, 165, 'CAP-NLB34eWKRt', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/licence professionnelle\\/demande_da\\/eX7L8mKC5qMoA4rBjaSfWvSLKGlhpzEHUGKOYAbo.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/licence professionnelle\\/cv\\/scKPUi04aiBCh26QyYLOFJDOI4nqTgmTk02Wkamo.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/licence professionnelle\\/acte_naissance\\/auzWskh4gQPLOwsZJTRrEvqkSfpWFaqkq2CEZLr1.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC ou \\u00e9quivalent\":\"dossiers\\/2025\\/licence professionnelle\\/diplome_bac\\/pLnhoekwV58Wjdtzy6rxuhg0nxhNtVd5142tFQjX.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/licence professionnelle\\/attestation_travail\\/ROgPQDsMnc5ncmP2DIsI4h2g54MB49DYx7eCImsb.pdf\",\"Quittance Rectorat de 2.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_rectorat\\/5ZOsa6kCbshxMKOgLShylVR4KVmRTU6D64P5H1qU.pdf\",\"Quittance de 10.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_cap\\/d3ziZVv7dzjQm4rHCvLoXEsk7246p7MfLRRJznFe.pdf\"}', '1', 1, 'dossiers/2025/photos/r6VYAm1LmkgkrxvHkPXLPKibvrTe2IH3xsZRXYqB.jpg', 2, 1, '2025-08-07 08:58:56', '2025-08-07 08:58:56'),
(166, 166, 'CAP-JOvod4mWid', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/licence professionnelle\\/demande_da\\/oKuxot25eKTFzwfWz4e5oRTv8pj2Ljb65x1GxG5n.jpg\",\"Curriculum Vitae\":\"dossiers\\/2025\\/licence professionnelle\\/cv\\/ogKnvhyU6gdtOwk1Q4MLR5nM020RyZTlqFbN3us4.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/licence professionnelle\\/acte_naissance\\/CoGa8hNhSAK0KqmaPVTEpbznEmcD8uMHi6On3SPg.jpg\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC ou \\u00e9quivalent\":\"dossiers\\/2025\\/licence professionnelle\\/diplome_bac\\/SJZHQvWvZeW0cwZEMQBZyTBNbifAxEHgwXRmbf7T.jpg\",\"Attestation de travail\":\"dossiers\\/2025\\/licence professionnelle\\/attestation_travail\\/gzSfrAFzae7R7Hvw8pmoJ3KA2oEOy3b0EdUus5ZT.jpg\",\"Quittance Rectorat de 2.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_rectorat\\/46inNdgkNfK5O6ysJhlwDonuVtxdtnrlGvHrZqUQ.jpg\",\"Quittance de 10.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_cap\\/rBDfywwwaSpxVRiTiXafnffPl4cQsMboJA3xFDWI.jpg\"}', '1', 6, 'dossiers/2025/photos/tnAGjm6JYhsMqWUt4VPTSLs5FaxT2dpyEIVpV9cQ.jpg', 2, 2, '2025-08-07 10:46:16', '2025-08-07 10:46:16'),
(167, 167, 'CAP-ViC5FwURfE', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/licence professionnelle\\/demande_da\\/vKhRm6zA8JLyxUn09JRMnLP11mLdTPkYwfxuhIRC.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/licence professionnelle\\/cv\\/m9rnLSXXPJrRKozGG1njpWKmlR4Ofz2zLKR34z1D.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/licence professionnelle\\/acte_naissance\\/BkYopGKKp2IzK6vSgUIQ6XhDTmOWGMIdIdMKkxp5.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC ou \\u00e9quivalent\":\"dossiers\\/2025\\/licence professionnelle\\/diplome_bac\\/OnbVKJvKIB4p3GtKQvN6KBLy3x6wWLPejbO5cwT1.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/licence professionnelle\\/attestation_travail\\/1oOBzToXjLNwYN9ZmZykYal0bkNWTPYnfmKOHD4v.pdf\",\"Quittance Rectorat de 2.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_rectorat\\/jba14hRCJBLK15osqiN1X0UEDAKsmXmduxeEB9Qw.pdf\",\"Quittance de 10.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_cap\\/8PFPmOSCDt1cvGbE9qYsuJtU9KFxOQVU1jCExjne.pdf\"}', '1', 1, 'dossiers/2025/photos/kbGTNNSg0QXKXahT9b4K0FMOrrOYA5RPLBFmbcT4.jpg', 2, 2, '2025-08-07 11:03:23', '2025-08-07 11:03:23'),
(168, 168, 'CAP-EFbQn0Icda', 'Favorable', 'Favorable : dossier bien complet', 'Favorable', NULL, 1, 2, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/demande_da\\/7JQJ6hgHLW66NrwtAZi4xEL1SbSFQTwzPkdCwE3r.jpg\",\"Curriculum Vitae\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/cv\\/ubgW1cqYwulGVZlXIhMBsLw9T9eSAxDu5TX9RavF.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/acte_naissance\\/rhsL5R9pWddAJ9SnBvNgDT27pH6b0IWZFjAKblBJ.jpg\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_bac\\/BqZvqxIWeGkETVsXawfAhNZu7ycSpVT1cvoJyoZS.jpg\",\"Attestation de travail\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_travail\\/NBtHVaa5mar14M2J8qSeOc48b5RUlqbkuhk7CX73.jpg\",\"Quittance de 20.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_cap\\/wPCvuhgNMP7wEzr6XSNFieU4V8SOip8ty2my044B.jpg\",\"Attestation de d\\u00e9p\\u00f4t de dossier pour dipl\\u00f4mes \\u00e9trangers\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_depot_dossier\\/CisvdPgNcqkjj3saQZ1PL2BqTbJIKwDUIGfH3QuK.jpg\"}', '1', 2, 'dossiers/2025/photos/DgviHXl0A5bT4MGtZWCr576HUAHvikfVFOKW0tFr.jpg', 4, 18, '2025-08-07 12:21:43', '2025-08-30 19:27:51'),
(169, 169, 'CAP-pzEvQJYMUn', 'Favorable', 'Favorable : dossier bien complet', 'Favorable', NULL, 1, 3, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/demande_da\\/3ZCWzoNNjYh01WAgbiTzTQeeVUD4ICcVK3eFGimj.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/cv\\/NBdC2PsnLNl1RCb54xJsllVf4OjoB3EpfU7vDeME.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/acte_naissance\\/MgOVs4B3VCWXdsA23PiSQZxA466PGETjTzK7UTXV.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_bac\\/PBYB2rSVkBbExH5spR2OW7hfvjryKosX2ksHiMAH.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_travail\\/XTVO3xrM3ujDcOw1VwJN1sRciG8q3RiRXhXf4D63.pdf\",\"Quittance de 20.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_cap\\/PHgmAUKZCHJidw0NWrim9FBFa8URRSDF07j5fg8e.pdf\"}', '1', 2, 'dossiers/2025/photos/lF6uI486YAtjSoseJEO6gYzCjzz03fnMVTLFs7Y5.jpg', 4, 19, '2025-08-07 15:59:39', '2025-08-30 19:27:56'),
(170, 170, 'CAP-kVpeCpXy19', 'Favorable', 'Favorable : sous-réserve de la licence nationale Professionnelle', NULL, NULL, 1, 2, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/demande_da\\/4M5ce9a2xFh8eg6d9PaS9v7xfn7Oxd0092s3kvbM.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/cv\\/gce9Y7enHzZoiUgIEWUKHJOUSzdyorqypSxnEvOm.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/acte_naissance\\/2R5TarlxeV9H2j2uXWQnEuUbXbbdRfwhylfth8PS.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_bac\\/1oV28vUtrD2BenxiZigUPQxaKXMYD0Ss5oUHpi7A.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_travail\\/ZjUph9hEcqq2pm0VD4kUrXRKysrAeWANHKV1rxTO.pdf\",\"Quittance de 20.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_cap\\/rLFkGFymVsu2JaekkE65if7hfhGE03LJurWhdbIg.pdf\"}', '1', 2, 'dossiers/2025/photos/y86vhMikZuvd0AsBpcXG2aozSPJxUueGm9zjhF3x.jpg', 4, 19, '2025-08-08 15:02:08', '2025-08-30 19:05:01'),
(171, 171, 'CAP-g0g62DnjWA', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/licence professionnelle\\/demande_da\\/Lsb7Qz1cdC1d9NBsve42fg8AQApa9z8ykBiiS1zd.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/licence professionnelle\\/cv\\/cYxhi66RYDB368mjRz4HBa5T4s5QzrnGBw6EIigq.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/licence professionnelle\\/acte_naissance\\/fHKDbO8014fdYC3N3irQtwpCtxO9KiOvzdF6gf4v.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC ou \\u00e9quivalent\":\"dossiers\\/2025\\/licence professionnelle\\/diplome_bac\\/mbScJ7PwSpImtCGw55iwusOysNm1WJdKhiW8SjTc.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/licence professionnelle\\/attestation_travail\\/u8P1dOqYIeqnHfJG8k6KY4XbUXfMpTg5csWGDRZ0.pdf\",\"Quittance Rectorat de 2.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_rectorat\\/8TydIfwW3V7mysgW3I7hWUIDrYEu0k7Abdl6Q2Bm.pdf\",\"Quittance de 10.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_cap\\/lnpKb9TuETR15lemBZrEvhqcLqd2L3Sx9b9zQC6C.pdf\"}', '1', 1, 'dossiers/2025/photos/8itQB9UUVjpjtnvw1FXbm9KJVLALVVg4GuU1fSMI.jpg', 2, 1, '2025-08-08 22:57:19', '2025-08-08 22:57:19'),
(172, 172, 'CAP-CuiprTQ9Az', 'Favorable', 'Favorable : dossier bien complet', 'Favorable', NULL, 1, 2, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/demande_da\\/HGl06HjndImkhRDZ5UpMmZOdJhyGuUh3j49gOcFf.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/cv\\/BysAqYo1W7zb2NcZcW0GiT82qv1VyPMsoIm3jtpt.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/acte_naissance\\/diIiKl9SZcaO6LS94hi8YGJRr9qJYu3f2Mhe4DtF.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_bac\\/Mw0AWNbPAU79oL07feIEXGgVJpUtcG7xDL73Q38U.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_travail\\/CGHRYGOo3bWBQfdwBg7lFZwhHdkrgQbY7kHU0f7z.pdf\",\"Quittance de 20.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_cap\\/hDXVT29hRD03p11ONwxgfYsQf7dp8FGnPX8BiJmR.pdf\"}', '1', 2, 'dossiers/2025/photos/pbE9OBmZlDmia27hPVhfAsFintWWzVRnfMcaepM3.jpg', 4, 19, '2025-08-11 10:28:24', '2025-08-30 19:28:21'),
(173, 173, 'CAP-MTdMu11jub', 'Favorable', 'Favorable : Sous-Réserve de fournir une Attestation de\ntravail valide de 06 mois au moins.', NULL, NULL, 1, 3, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/demande_da\\/TOGpO6NDx6BZTPTSJGZd5ZBG5WLBK3G04Mwt2OfG.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/cv\\/Et2LKnZjFbmQV5IkfBZwhT2YkY91PnUQEiiLGiBE.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/acte_naissance\\/DKnQ2YseGsBw7p6IUBqaJXcZvAOhwb3eyOUL9Vkx.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_bac\\/3FHmkXlB7hjw2Ki0q9roVlIdtyu2g1ouevbULbTG.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_travail\\/XRFC7XfBZyU6AmcSIzEOFP1luNulDlAIXmnoFxwt.pdf\",\"Quittance de 20.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_cap\\/hBYjS0vAaHiyj2G8INyrz3UD10KMeZio0K5Ok9Gx.pdf\",\"Attestation de travail(Compl\\u00e9ment)\":\"dossiers\\/2025\\/complement\\/Attestation de travail\\/7lm9bkaGwYy0W2QhuJgjlS7qe0BxJp7YjTGMAL1O.pdf\"}', '1', 2, 'dossiers/2025/photos/sbyMkJ0tOMy8YJJvo8y9s7yXx5x4nJZDexaxdwV2.png', 4, 18, '2025-08-11 13:11:51', '2025-09-26 18:39:27'),
(174, 174, 'CAP-WaAbCMdXy2', 'Favorable', 'Favorable : dossier bien complet', 'Favorable', NULL, 1, 2, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/demande_da\\/21O2oIA3eMO8K4uEA6cMrIJA9z2S6NozSsYfYLIi.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/cv\\/zJfvQACrln7NVC5USVHSCFj4yl8MgUrJoJnwqNLg.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/acte_naissance\\/J3n2C5jpyy50zGmBxjB6FfvLhldAah3j7DbBA4Uf.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_bac\\/00AiGC5KmDCHhx5JbcciIVfNqnlB5yL25SakGrYA.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_travail\\/apLoWdWlFUjeDhR1L9IRNAJNOrYmxZKpsHMn1X8F.pdf\",\"Quittance de 20.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_cap\\/0zwU6iggsAn2Zanh545354OYlGnRJOIf8njYL2JK.pdf\"}', '1', 2, 'dossiers/2025/photos/mAfGDX724h0JfMLbtpRh8YFAvFmHnZkodGqrnTeJ.png', 4, 19, '2025-08-12 11:38:42', '2025-08-30 19:28:17'),
(175, 175, 'CAP-FTAXGuhsUM', 'Favorable', 'Favorable', NULL, NULL, 1, 3, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/demande_da\\/1FqQUMT03DpX7ofJhFnopAxHq2E28INmKzJW1Ig6.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/cv\\/mMOsxrV2ghnibFMM7xtpOps8ZRukTYofUYKhdKvB.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/acte_naissance\\/G1EW38kRDBVpViSE8iswVntM9IQ2eqjRCmsvwSdA.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_bac\\/67B04rMWUqOh5MkcQA7TXNHtzbiNRtW8bNye6d1u.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_travail\\/tmRJcoYJSWHjZLQFQNpYPMaxLmJmJ4OnIoZsibSZ.pdf\",\"Quittance de 20.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_cap\\/x3KKs3IJb74Qc3JpRV7W9FO9MW8X5AuyTMtoDYAa.pdf\"}', '1', 2, 'dossiers/2025/photos/qaUmzSdxVeQpiciVZ9PbjPErM1pO6w8QdSZ1Leid.png', 4, 19, '2025-08-12 12:26:07', '2025-09-17 08:50:24'),
(176, 176, 'CAP-h3vbCTsvKN', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/licence professionnelle\\/demande_da\\/7lmExox3MNKxx32mBD6hFOxtlT4CrTWfWNKUENQu.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/licence professionnelle\\/cv\\/ZtSIYBF7bUuWoP63p8mkugAgVbjaUn8NdAiG1KBc.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/licence professionnelle\\/acte_naissance\\/lytkc4OLGuoGKWbEW5bIW5yD36NlZ3Qf4LrZKzRa.png\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC ou \\u00e9quivalent\":\"dossiers\\/2025\\/licence professionnelle\\/diplome_bac\\/cY0PPqD3OMxj386Ojd0thMGLktNuQ4DPsKNDfqHB.png\",\"Attestation de travail\":\"dossiers\\/2025\\/licence professionnelle\\/attestation_travail\\/IJK92K4v1FlszwB4KXpwQKWdyAieUB66XTfX7q3h.png\",\"Quittance Rectorat de 2.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_rectorat\\/csdJoMsxtzICzlfftdRZ3NRfhgZP7Zz9uA9G82EC.png\",\"Quittance de 10.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_cap\\/vgmTXuHhI1Bj6bkAaPatgjfVA0syuqD0BA4VmfKu.png\"}', '1', 1, 'dossiers/2025/photos/IwU3ViQiYkak5MgteRHI7xsOp5o0ElPGaRG1agOu.jpg', 2, 8, '2025-08-12 13:45:51', '2025-08-12 13:45:51');
INSERT INTO `pending_students` (`id`, `personal_information_id`, `tracking_code`, `cuca_opinion`, `cuca_comment`, `cuo_opinion`, `rejection_reason`, `sent_mail_cuca`, `mailing_number`, `documents`, `level`, `entry_diploma_id`, `photo`, `academic_year_id`, `department_id`, `created_at`, `updated_at`) VALUES
(178, 178, 'CAP-qWJHTpcjlo', 'Favorable', 'Favorable : dossier bien complet', 'Favorable', NULL, 1, 2, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/demande_da\\/p4vPgtNh6evLOxiEezeDUeUP0CZ241fWZHCbeYgQ.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/cv\\/cV3kJgwV22i4O2QFkAFM4GyG1tKv4SWiqWZimsIa.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/acte_naissance\\/GFVeqlCN8TlnIcDR2vHV1HCbpBswI5MAK276UnJp.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_bac\\/IU5iZyEXYGdheCM8CnwXbnsM1ZGMwej1IwFs0A6Y.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_travail\\/4zzVYiYN0eSCrHhPfIZL1vLLNChIrjAG4JQcgfue.pdf\",\"Quittance de 20.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_cap\\/jQELF1qULys89ZTVmnUtKAnBiQzrE4lnYqaM001R.pdf\"}', '1', 2, 'dossiers/2025/photos/0Qm5TKqAWw3G9w2B1LPCcEn4MpKpNuFfPF8toMea.jpg', 4, 19, '2025-08-12 23:02:41', '2025-08-30 19:28:13'),
(179, 179, 'CAP-WjBKUNsR3p', 'Favorable', 'Favorable : Sous-réserve de fournir l\'Attestation de Licence Professionnelle', NULL, NULL, 1, 2, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/demande_da\\/Sr4OlNHuAUzUAM2pOua2u8B4xEWJZh7t5l8jGjNd.jpg\",\"Curriculum Vitae\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/cv\\/srC5W0HrVCK8NwWjccZp0Dt89L7ln05TbEeV3VfH.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/acte_naissance\\/5kq8Gc4BXx2fEYwA2OLI2dEhm7S1ogh28WLvBOIB.jpg\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_bac\\/CNd59uAHOqBkpG2avfiNWv0FGZAQpA8KBZZ9dnxL.jpg\",\"Attestation de travail\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_travail\\/gVmKPWIbhkl31q3Q8weQ7on7W0sTB8OJYkzIkPTA.pdf\",\"Quittance de 20.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_cap\\/6mjnZbPkvpgjFrNBqQzqiZHCA2dIloO6htl9vIN3.jpg\"}', '1', 2, 'dossiers/2025/photos/nhtU29W0to7BQMVv0jrdhQiStNC8J6y4pBfK83ZR.jpg', 4, 18, '2025-08-13 17:40:24', '2025-08-30 19:06:37'),
(181, 181, 'CAP-nyIuQ1Yxgp', 'Favorable', 'Favorable : dossier bien complet', 'Favorable', NULL, 1, 2, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/demande_da\\/w9PvSn1S8anQajYGCbMM2qUldczlDKJwAheBtitR.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/cv\\/GVY6pgUgTW4QtC8qWwmUFlGzmdpntKZj67Eq3reu.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/acte_naissance\\/YO4gOPjB29VGQ9Heooa7kgf72M2TiBZaTiHrZRNV.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_bac\\/xaqW7BoIYjMnqd986xgFt4TCwpAXKTLXLAs8xCnr.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me de la licence\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_licence\\/EaSxFR0yFyw8oohi45rEtIltj8JGM96UZgFuhPMi.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_travail\\/7wpgUT6J7rlfqjHKhK3vIppoMJXMjZ7RQ9NhDyKC.pdf\",\"Quittance de 20.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_cap\\/LIs5tyPw1knAtAGzP3ZE9TU7zyCrn0jusavilNDC.pdf\"}', '1', 2, 'dossiers/2025/photos/UEIY9Fu8fmrUyinuMzyluOsuU8637TfLA3jqTHTt.png', 4, 19, '2025-08-14 10:16:55', '2025-08-30 19:28:08'),
(182, 182, 'CAP-K9c9wPCVvF', 'Favorable', 'Favorable : dossier bien complet', 'Favorable', NULL, 1, 2, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/demande_da\\/dCeb7DKEJW62RgvwwxwBX5wX2f2RS00o50QWLQNR.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/cv\\/OSF07vlkBbKQ0mxZvVp3vXgKtAVE9Fm690WfoDp7.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/acte_naissance\\/vfAXzzzMWI8jQWi4Jzvfhq2jAWsRIeZqYlTz4ukv.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_bac\\/gPHKgvQbv6isvzcHyh9GwKy8BbXJCh8DAxx19F8E.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me de la licence\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_licence\\/m2sAfnrfYQORnRlelwbo8uy76J3OmN2FZQtXpmFj.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_travail\\/KMzm3u6w342Nxlumi01kXj7OG01wRxTijnXFNxcr.jpg\",\"Quittance de 20.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_cap\\/aU3kdB42P5MLoEiGG4PwQEeUicKIkyg4lETxQaYH.pdf\"}', '1', 2, 'dossiers/2025/photos/rXHtCH2CH07T9llMNZxOBiHUoWRalU3QyAjy1dzB.jpg', 4, 18, '2025-08-14 10:56:15', '2025-08-30 19:28:05'),
(183, 183, 'CAP-20e1LiCwXv', 'Favorable', 'Favorable : dossier bien complet. \nMais peut pas ouvrir la seconde cohorte pour raison d\'effectif insuffisant. Donc démarrage des cours - Première cohorte (Janvier à Juin : année académique 2025-2026).', 'Favorable', NULL, 1, 2, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/demande_da\\/SDzsPfhGNIihJRzEyZ9pOngIeaAFmdvJPnjkdRBI.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/cv\\/ItCvvSEtcROD3HoSvk84OLMfHCK596OiMnH15Khx.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/acte_naissance\\/DDws0AONQvFVSTsGoHxt2Wty82Xv0AalnoHHx28i.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_bac\\/LCEE9hBMbEMTOknoWYPqWxeLS14AxSltrWuZwKOs.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me de la licence\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_licence\\/07KEPTFeqXLgkD0QaAy8a7AEN28yJYhHpVUCIBjE.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_travail\\/6JV3H3GuBSaYsKa4R1cPySGAP00gdX0T4pgoOxWE.pdf\",\"Quittance de 20.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_cap\\/oJarM6gHGJZULJLDnPku1Yq1AzbSnPjZAH1C4Wtv.pdf\"}', '1', 2, 'dossiers/2025/photos/gRjQvjKSR8guyjZvfcb5OvD1PNiXGphlrgOUqE25.jpg', 4, 20, '2025-08-18 08:37:07', '2025-08-30 19:28:35'),
(184, 184, 'CAP-TNeZ11cRJF', 'Favorable', 'Favorable : dossier bien complet. \nMais peut pas ouvrir la seconde cohorte pour raison d\'effectif insuffisant. Donc démarrage des cours - Première cohorte (Janvier à Juin : année académique 2025-2026).', 'Favorable', NULL, 1, 1, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/demande_da\\/wMJc7bWvwuhDsYgygfWabhhCpDVOL9SuRnGgUxo5.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/cv\\/7H0wyT0mk9wE0AHuYNfuIdBd4VTQGbrfPeFoK27g.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/acte_naissance\\/RyojFQR2SPwgQqtIbmPcpwgb8vFNo43XnuYCM3CC.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_bac\\/ijwKrpR40GWvgdHevUnu0LSbIiNdBvV9v8QjFAVc.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me de la licence\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_licence\\/hsadbdxw7vTUzczD5vyWDvfliYEfYxIRwTajNAax.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_travail\\/5XZo3uvlRXtcsyLVT4H4jfCFQcGZeaCe4HHvtPd1.pdf\",\"Quittance de 20.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_cap\\/qyUvSnuGvXwIirikFT11W3Th20EWp8NQLLSsfdXo.pdf\"}', '1', 2, 'dossiers/2025/photos/pgTx6pwRuIlxzTdqgPSNrF3Mfq5q2Wv7DRzflTir.jpg', 4, 20, '2025-08-18 11:07:26', '2025-08-30 19:30:36'),
(185, 185, 'CAP-7DlhvxwAQb', 'Favorable', 'Favorable : dossier bien complet. \nMais peut pas ouvrir la seconde cohorte pour raison d\'effectif insuffisant. Donc démarrage des cours - Première cohorte (Janvier à Juin : année académique 2025-2026).', 'Favorable', NULL, 1, 1, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/demande_da\\/CBMQ4bf3gyh2ZE2QlTMpsEvXat8U5WQU8zNxkNAU.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/cv\\/etOCqzmPT0a7hYhmTyZDvKtmehK80BcADlrqN6IZ.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/acte_naissance\\/vvavrrX5mcpe9U6DMDIwnsSND5u4VAuBBcqspIqT.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_bac\\/NSWsEVlcqE6kKAzzvkonzKPHL50Bld4BrSIbTuRh.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me de la licence\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_licence\\/jXnjNsMoeg6D7kS4PDfcevOFmEE0qnVoRfNqqG9J.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_travail\\/qMWMZ2w8ieMJVTIMzTPXmTdSq2139fbzdEDMdXHc.pdf\",\"Quittance de 20.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_cap\\/mxMae83fwyreNDnvfo6SnrT5krhRG4JMZkYkEZcV.pdf\"}', '1', 2, 'dossiers/2025/photos/skSCfe9X0C4W4dlfi1aE2R3kHPzQk3McvjKF75th.jpg', 4, 20, '2025-08-18 12:02:27', '2025-08-30 19:30:33'),
(186, 186, 'CAP-lAFJitKvs7', 'Favorable', 'Favorable : dossier bien complet', 'Favorable', NULL, 1, 2, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/demande_da\\/nUzyAFbQnf7n0TtElpoKBygG0EkfwVifdV6459Qy.jpg\",\"Curriculum Vitae\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/cv\\/tF6acWlJdgZYMqZYKo8CwJrEbXv2JDddW0BOGvDT.jpg\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/acte_naissance\\/Mu23VnTbTCgqLDYsFKGPssfPwVzNAcfE1OpsYEEo.jpg\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_bac\\/KWn6wtNeEwCTRhe4jvzfzj5TBnklH0PWcaxHvq71.jpg\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me de la licence\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_licence\\/Vx809TRF3fOMLMGmWD7dN4ktXhzXghNMK3RauM77.jpg\",\"Attestation de travail\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_travail\\/WaON5M3MNis1VyFGHE4wR9GEcDCgImJ7Ra2VRhPv.jpg\",\"Quittance de 20.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_cap\\/F78smPonBNcIxhxlCn2pO9zXHIAqkh2fbSn7YIlT.jpg\"}', '1', 2, 'dossiers/2025/photos/mXDfiq9DMFs2c1hA7TXPOiwHO5iU9q4AdXyK510f.jpg', 4, 18, '2025-08-18 17:16:56', '2025-08-30 19:30:29'),
(187, 187, 'CAP-pjZDTZCCDa', 'Favorable', 'Favorable : dossier bien complet. \nMais peut pas ouvrir la seconde cohorte pour raison d\'effectif insuffisant. Donc démarrage des cours - Première cohorte (Janvier à Juin : année académique 2025-2026).', 'Favorable', NULL, 1, 1, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/demande_da\\/GSXX1WIZv9z9J4EF7aqEvvI9rYf1hQqYrBuGp0UQ.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/cv\\/zEboEJ91OTePrr6BVCXV1OSj5jWqquXhzW6D0jIM.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/acte_naissance\\/3JQTYQ6pCEykkcR5fWgHXMD5G2me9fZv1iYoqXgk.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_bac\\/2MWV23E0Opq994AJIQq8SFIVui5dINvBAqwkrBNu.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me de la licence\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_licence\\/bBKJFnucT7ZqN6A7oAjAwbJw2NYaBxpJ3syoTHR2.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_travail\\/v4rdtetkb4krGnMD9J5oWrppGlKyvrUxIuiZOEhS.pdf\",\"Quittance de 20.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_cap\\/ThEpTCaQO1yb8hhXGRTFcYGOpiaTmfSWD4DT4X8W.pdf\"}', '1', 2, 'dossiers/2025/photos/ByIXgBRJIDjNjOKsM5iTuiTFfUWZycEqxg97HaQB.jpg', 4, 20, '2025-08-18 18:32:47', '2025-08-30 19:30:24'),
(188, 188, 'CAP-ALLVgBlmVt', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/licence professionnelle\\/demande_da\\/iLP1IojSCnF6KXjNCeNLrkngsdvuQw5nYKIlXgtj.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/licence professionnelle\\/cv\\/PQmzzyNcX0uOW8gSeqNPEGOR8imWouE4EEpCkYMF.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/licence professionnelle\\/acte_naissance\\/GNdnRs5Vp05a07dOOsnz4a1UOvBrsulSDZchXtbL.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC ou \\u00e9quivalent\":\"dossiers\\/2025\\/licence professionnelle\\/diplome_bac\\/4JUqDw7Bg6OUTUSMYwr8alOxLBIXrpxfAH452yEJ.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/licence professionnelle\\/attestation_travail\\/tibNZvhSiQHxbDGv6GvlE13INWhJCObTtmlK3fRH.pdf\",\"Quittance Rectorat de 2.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_rectorat\\/x2FlUdVxuhK2CD5qXXPqU4cWwQmhQSgCMRNywQz6.pdf\",\"Quittance de 10.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_cap\\/Z5ScZDIXQItO7Atmj5Pso643wBVAIcPE6yGIRdnv.pdf\"}', '1', 6, 'dossiers/2025/photos/KcyzpES8h2lVTYBwrWyrCH6EtVdp1OG9ZOV9nNDW.jpg', 2, 3, '2025-08-19 09:00:28', '2025-08-19 09:00:28'),
(189, 189, 'CAP-MXWLTwR0rz', 'Favorable', 'Favorable : dossier bien complet', 'Favorable', NULL, 1, 2, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/demande_da\\/jX46AmcWSxiX2l0Gp1eb5hj6CERzdEd3cOdGMWDh.jpg\",\"Curriculum Vitae\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/cv\\/eU40PsxYrbGJDAMkcs4sTo3abSVJGQ2ZeHqOuyJu.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/acte_naissance\\/4SHgAhFjPDjtTq9NHebl5lvhJPKd47RoC4i18TQc.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_bac\\/yVjJI8QsoZD66eSJio6xCBUZTiZUaQmrst2cIkO3.jpg\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me de la licence\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_licence\\/tw7HBQAmZ1IuYrK1siHbFgoz0f060u5bFM6ShVU6.jpg\",\"Attestation de travail\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_travail\\/Hck6o8Yi0iCejgmdpUI9lL97bMELqsuNho5dU4Oh.jpg\",\"Quittance de 20.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_cap\\/WgNxiFuT9gr6eJQNLG8crHJPzAcgJrR6TQrE5WmT.jpg\"}', '1', 2, 'dossiers/2025/photos/Tk8mFo2yTy60IMWdKdL4CMyCN6UELFH3A1xcs82U.jpg', 4, 18, '2025-08-19 12:15:42', '2025-08-30 19:30:18'),
(190, 190, 'CAP-asTjShjSCt', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/licence professionnelle\\/demande_da\\/u2IqtDl1nzRPvmVmRJ2kq99CpMiBIitkQMgCjn04.jpg\",\"Curriculum Vitae\":\"dossiers\\/2025\\/licence professionnelle\\/cv\\/QnErMTPeSJBhCDmgrV1M7JYuvqLx2Yl7giRhnKLW.jpg\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/licence professionnelle\\/acte_naissance\\/eJnJpmWWiRNSYb8Lqw9TrfsPnANXWyttLQEYZOmS.jpg\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC ou \\u00e9quivalent\":\"dossiers\\/2025\\/licence professionnelle\\/diplome_bac\\/OYAp2raDGBEN6RKFyigKrPHAN8tt9QyoilsqIbpe.jpg\",\"Attestation de travail\":\"dossiers\\/2025\\/licence professionnelle\\/attestation_travail\\/fuWFOwWY2pt3sHuy6GZ7C9GcXjh16dvrhvHtzftl.jpg\",\"Quittance Rectorat de 2.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_rectorat\\/B67tHJqdMM0tYtbQnEABYFxoTOG4006LYMVr3w3B.jpg\",\"Quittance de 10.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_cap\\/BmDeJRpJMgsBayoeesRrJnRmyhVSOaJVLjh6Ji9w.jpg\"}', '1', 5, 'dossiers/2025/photos/JCcPRtGjfdz5qdEz2yUVKGt1vq0afRnrshnc7f7t.jpg', 2, 5, '2025-08-19 15:56:05', '2025-08-19 15:56:05'),
(191, 191, 'CAP-vXbm9ln6px', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/licence professionnelle\\/demande_da\\/cCuSZIe4353HhmvlvMlrmaMHlNQBD9amFI0q8P81.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/licence professionnelle\\/cv\\/wI7jq3dfBCg1Vpxht3Uw9MDk7dJA3jXjsUhDiESZ.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/licence professionnelle\\/acte_naissance\\/gspsMkOCjksI17k6UYWaQ8CGydDrYkSFAPN74Jpi.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC ou \\u00e9quivalent\":\"dossiers\\/2025\\/licence professionnelle\\/diplome_bac\\/E1Lo8VN96HVmIsiYg5xSleB2kfk9nzGKI15EzgL2.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/licence professionnelle\\/attestation_travail\\/P9UNSaKZlu7ePEqPcXZsKkkyAz6w5GH2zpNpkcVv.pdf\",\"Quittance Rectorat de 2.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_rectorat\\/8U9t6X3jwtPIp9g1N3e533uZWpkABsIHUUvVe52V.pdf\",\"Quittance de 10.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_cap\\/0bki8aNnE4fCXQhpbptYMIWDnYzi2rBUUYXTe7jE.pdf\"}', '2', 6, 'dossiers/2025/photos/fsM4lEkawLhkARpNOdduCicBGY1moSmAwDe0rGYh.jpg', 2, 1, '2025-08-19 21:42:48', '2025-08-19 21:42:48'),
(192, 192, 'CAP-38Yedw0aZR', 'Favorable', 'Favorable : Sous-Réserve de la Licence nationale et de l\'Attestation de travail avec mention de la période de travail (date de début et fin). Pour raison d\'effectif insuffisant, Démarrage des cours - Première cohorte (Janvier à Juin : année académique 2025-2026).', NULL, NULL, 1, 3, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/demande_da\\/wBp9N88vXuHGmtw5am1YYcMz7JO0VVMWt5EhjPsJ.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/cv\\/3maObRMyE06oygYqXrOCMPlFSDNvUQSx1s3n5OGJ.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/acte_naissance\\/T1dmPVlkhqOS8uMOSfyEJKqS0PBiTC232xIxlKHn.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_bac\\/gQDmSoueEieCE5fDs7s97H2UJsE86zO7pL3abmLC.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me de la licence\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_licence\\/pTzzjEYsgs5sISY6za5XQX7ICPFcuK275TfhDMh2.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_travail\\/BslHqzffOgBeNArNyzkLgMTPdK5PFOP3yf14REHP.pdf\",\"Quittance de 20.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_cap\\/QjIGQeUfnfnXMIHragOLVO0HFmWELn7jvPPERh1w.pdf\"}', '1', 2, 'dossiers/2025/photos/DjaK9BOYvSfWs2OXrNYYDYUS9yqHQYxmLVD0rHtM.jpg', 4, 20, '2025-08-20 09:30:28', '2025-08-28 17:06:25'),
(193, 193, 'CAP-OvSzwosVMU', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/licence professionnelle\\/demande_da\\/aaI6IkyIxvMnEk8bLqVKOuKZwEz8Qxf6VQ6By7zh.jpg\",\"Curriculum Vitae\":\"dossiers\\/2025\\/licence professionnelle\\/cv\\/RjjgWWIb8h1yQtQLwq1FGkk4gDHOi6ox5QUJaXCo.jpg\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/licence professionnelle\\/acte_naissance\\/2ubZqMoiGSLAfimIEIOwTGcSECbn9MDba7qZiPwE.jpg\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC ou \\u00e9quivalent\":\"dossiers\\/2025\\/licence professionnelle\\/diplome_bac\\/ZYEcVz7SJqZdBjfuUI5fmzlGjSZaDRUI44wycmrz.jpg\",\"Attestation de travail\":\"dossiers\\/2025\\/licence professionnelle\\/attestation_travail\\/JNcWElEGR0SizyDoDHNa8t9LuvdWxK75mRBDlLPI.jpg\",\"Quittance Rectorat de 2.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_rectorat\\/VaGlLvPOekHtgntcO8kepWK5QKieeYeESIQECoLd.jpg\",\"Quittance de 10.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_cap\\/plF9UW8yqjVdrpnPfRjBGUHZncGmQ7v1ltIj1qjF.jpg\"}', '1', 1, 'dossiers/2025/photos/S0o83wx1hZPsC9ZkT3YRKFyJv6F603inHnq7CEf2.jpg', 2, 1, '2025-08-20 22:27:27', '2025-08-20 22:27:27'),
(194, 194, 'CAP-uF1GXECZ2l', 'Favorable', 'Favorable : dossier bien complet', 'Favorable', NULL, 1, 2, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/demande_da\\/bdIz5iLQdVPWix79KhtSlynpcNpgOnyFURE5nS14.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/cv\\/yQ6tWFDypf9hg51zqO3hKxliHpC4DRH1JBUOlz64.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/acte_naissance\\/rUj9Px0WPQWoN3wBwYSxuiFhW1O07fI7bOFXbR1m.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_bac\\/KZuJLrpMHUlAfCUmOZpDec8zAsKeXbEObFp4gT3O.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me de la licence\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_licence\\/dPRloBUjk77nUaggB4YJkWhNyAp5vLDffHcqW97R.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_travail\\/aoxlZpFs0f1zKrkOhmd1c3sGv3OOir0MXACjp2H3.pdf\",\"Quittance de 20.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_cap\\/UL8W1gPwZ4U472MYPPYGWsKMdNB7DrBSZormhfwJ.pdf\"}', '1', 2, 'dossiers/2025/photos/MbS8bZeQwILTAHb0UILxtAt1sqK63PSKnxjmHvbL.jpg', 4, 18, '2025-08-21 08:25:32', '2025-08-30 19:30:11'),
(195, 195, 'CAP-I9QbM2oOmO', 'Favorable', 'Favorable : dossier bien complet', 'Favorable', NULL, 1, 2, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/demande_da\\/DKyeaFlP47CN9PgKZvxgUoSbhznSpmXiiYIR13Wg.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/cv\\/4xaRSNqGduXAfqD42uGt3Iujya1G45FtftCvPiJS.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/acte_naissance\\/ViH5NYQKbZw50gojH3j8fufdGbfiYSOtBRVsEcXr.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_bac\\/p1nfWr8vXIgoz9Un3iwkxOYdGntu77PqcuPtlphk.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me de la licence\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_licence\\/BJ6PdHh3G6iJ66KGy77rw5bsbFInztGVM8z1WMfU.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_travail\\/k5orkOqQYhSfYsKYVn1GW3j2muB4xPMU6BAImNnl.pdf\",\"Quittance de 20.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_cap\\/8X5uCc4sMBdl680hBGpzHnXm7rrenHzTMhB5iXkx.pdf\"}', '1', 6, 'dossiers/2025/photos/Nhvb2qgI3C8IdXpl2cwO9NFHe5SAyyewukzCrukV.jpg', 4, 18, '2025-08-21 13:58:23', '2025-08-30 19:30:06'),
(196, 196, 'CAP-pcpgI34VCs', 'Favorable', 'Favorable : dossier bien complet', 'Favorable', NULL, 1, 2, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/demande_da\\/EJMiyjHARQ5TYkvVwNTg6nz6iUM4Oi7BDoZOvlE1.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/cv\\/gm1bVGslUJE2IAP7g2CMvVB36o3a7HrMRKRM3ZNH.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/acte_naissance\\/0dvGZAc4ylLX1NPhX4Rb7vwSvuK8P27YFLbrptjF.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_bac\\/wbtrP8gdsfjSOW9CiPvzOx1wMiGtVEu09fO5Duvu.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me de la licence\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_licence\\/4kMcpN3SI7wBx9GKPDXk7LBLGw2QpA3REXcqrKZy.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_travail\\/L4kjgK7WfpRWqcc0Xy4HhZSAo8su3s73sGWxqspz.pdf\",\"Quittance de 20.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_cap\\/f1DSYnczPqRwVReWV750idc7liiyQJiRunk5X59z.pdf\"}', '1', 2, 'dossiers/2025/photos/MNSzrfrCaEoID2cATEAuV1VNUUXMxK2u5H4kWFR5.png', 4, 18, '2025-08-21 14:32:14', '2025-08-30 19:29:56'),
(197, 198, 'CAP-cwjR0MdgCZ', 'Favorable', 'Favorable : dossier bien complet', 'Favorable', NULL, 1, 2, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/demande_da\\/JQa0iUp4RRzy7bRQRkWoQ9JbvPRJhJge9EL9wGnM.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/cv\\/EK1bo8QGELoaVqNDQsD5KMfIkhrUMhKocFPuaGuq.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/acte_naissance\\/s61BkF3I8vxAnf2HIdpDT8HFZyY4zmOk13QvoyJf.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_bac\\/LdabI37hmNjaZa3cIxTPy6mMr9S3SFpaRtfFw0nW.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me de la licence\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_licence\\/8w7NXldinR1aLw7yHoaQO4tyanqYWbo5PbMow5Q7.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_travail\\/Q4XT5ll8CIz1cQA1t8mu1UN2Fu1cA9HbRwVcxZJD.pdf\",\"Quittance de 20.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_cap\\/ixMYfIJKrRoQPFUTyA1v5D1JF4gl6dpdE1aXWddg.pdf\"}', '1', 2, 'dossiers/2025/photos/Nsi09rAiB1lyLfr9uwjN97Yd03Z4fnh3ttZe3oor.jpg', 4, 18, '2025-08-22 09:42:47', '2025-08-30 19:29:53'),
(198, 199, 'CAP-demt6QIh6s', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/licence professionnelle\\/demande_da\\/al6zNv9krMwxSJD03WUWXE21l0f1ZavGxJPL2q91.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/licence professionnelle\\/cv\\/TFLa5ln5aDfIiljjSyFmaSIgGhbF6lHkY7wv4T41.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/licence professionnelle\\/acte_naissance\\/UDueoC7Eegz8eieYmpiPiK03RD4Dgv7XjdIBsDB7.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC ou \\u00e9quivalent\":\"dossiers\\/2025\\/licence professionnelle\\/diplome_bac\\/6mb9eDI43mfJtfxh4sMNeFiKceZaAAhmn6QIPoNk.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/licence professionnelle\\/attestation_travail\\/g0rcITHmFI4N9gjpApaGISfvTWYqkqRsC6jE2jwM.pdf\",\"Quittance Rectorat de 2.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_rectorat\\/qT5wS9Jzwifws6AeQfojIc3KESSFEOPQZeQcT8iZ.pdf\",\"Quittance de 10.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_cap\\/USvEZhWGSPUXJIxen6VmqTwJT3it497EW2Zn6zaj.pdf\"}', '1', 5, 'dossiers/2025/photos/KIhosBBB6cDizbMIO3tdqoAu9CzaWzV8bB2b6jIY.jpg', 2, 4, '2025-08-22 12:58:20', '2025-08-22 12:58:20'),
(199, 200, 'CAP-N7fIrxcMwu', 'Défavorable', 'Défavorable pour défaut de licence nationale ou co-signée.', NULL, NULL, 1, 1, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/demande_da\\/zWUx1daU6HdK6BhbzRfVJbVO5bRR66vILP0aej3n.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/cv\\/EQfteYN1WmBq3VckHrxib4i72AI11UlV7hwF1Hj3.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/acte_naissance\\/EExdHcFtCU3IuiWc14QAVt5Mnh2CzYUrpmxGc4tR.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_bac\\/ZYEHYXwzPPjVjR6fdE0bWxOMLW93HfA8f5g7THwO.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me de la licence\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_licence\\/8C59Dz3G0vI73y3i3tISDsvjdNcsOVszIb5Pf2Rf.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_travail\\/qstIPTdGwOwnriYWs9ic2t9et5cdLffnKRYwmxhb.pdf\",\"Quittance de 20.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_cap\\/Vq9npnC616yfurbCp0D1WuTd7FeWzUiEFjXBuVhC.pdf\"}', '1', 2, 'dossiers/2025/photos/rGjYypFHT3OUAo1fNRT3tCfU0LWk8alubIN6NKOb.jpg', 4, 18, '2025-08-22 13:59:35', '2025-08-28 16:30:31'),
(200, 201, 'CAP-lkVOeOIdc0', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/licence professionnelle\\/demande_da\\/OMmoRbOdnPMQRkBy1TysUsFSxuK6H0CeoCtx8SH3.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/licence professionnelle\\/cv\\/gOBwOKD6xWzCn7iuau2QmVcW6O2fEFUqH3A3nTYx.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/licence professionnelle\\/acte_naissance\\/rYxst6DIYesd3tc07zprtz3q4hFniOF6fYQEMxli.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC ou \\u00e9quivalent\":\"dossiers\\/2025\\/licence professionnelle\\/diplome_bac\\/a3b4WNajC9L0SLoAIOGpwA0OI2IirwBT64o991qe.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/licence professionnelle\\/attestation_travail\\/W2F1R5iG878WGtnBxJ0GfwpBoYdb8QDGDjXPwlIJ.pdf\",\"Quittance Rectorat de 2.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_rectorat\\/MMHN1iDHp5vQgAF7TJaDGUEJH1Hly62YqM1YV0ZY.pdf\",\"Quittance de 10.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_cap\\/IZC4Z2OpEA4RW7njsA8OV0hYwJpe0XioPw2FKF1f.pdf\"}', '3', 4, 'dossiers/2025/photos/uAN6Oj8R8KAgsXUhitxI40QZftkaeJD1KcZKWwlV.jpg', 2, 1, '2025-08-22 16:17:06', '2025-08-22 16:17:06'),
(201, 202, 'CAP-AZJpv64YNI', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/licence professionnelle\\/demande_da\\/xMhdAlPimfXuEdh0phRyC8feWJJmKnjavS5iabB3.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/licence professionnelle\\/cv\\/1Rytmbmf7dpwJWDmqgxtHZA5RWFBVlOjOCsoantW.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/licence professionnelle\\/acte_naissance\\/yPpizh6NMxbxoX3S9jCev6kWkkuYNuwKEbOhOgd2.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC ou \\u00e9quivalent\":\"dossiers\\/2025\\/licence professionnelle\\/diplome_bac\\/9J8M9FcKN14Kxqo9MSSlq7tHQBmxhOEbStVz4vCm.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/licence professionnelle\\/attestation_travail\\/Q82awThoxRTZSAp6VCN5ZuQri10rC2xt6nNcj6to.pdf\",\"Quittance Rectorat de 2.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_rectorat\\/QlVbMYu3h5KP9IFX9KvIXvoZKDEccp67vBV0Ayru.pdf\",\"Quittance de 10.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_cap\\/S9edNYd6ybjOPppYrVFKwbGo7wdZ857q3zrU621P.pdf\"}', '3', 4, 'dossiers/2025/photos/Zmuu3H6H9VL1uZlJ3vhj0YYa4vmW4LP313fZx2h3.jpg', 2, 1, '2025-08-22 17:15:28', '2025-08-22 17:15:28'),
(202, 203, 'CAP-tEXTaeGzCR', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/licence professionnelle\\/demande_da\\/t9xdpgHyZ8XODjRZc291Yo9kxaSftWcBeHphlfqC.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/licence professionnelle\\/cv\\/tbBkHA6SLgCDomFbnsH5hzNxJxbUHFv70kXW3DEX.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/licence professionnelle\\/acte_naissance\\/96z6y0XCyfEAHUrQlMbp22Bx6014hM9g4R9uehSP.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC ou \\u00e9quivalent\":\"dossiers\\/2025\\/licence professionnelle\\/diplome_bac\\/EhcpJMGVfWHKOIJJcDuSY0KAPDG6x4t31Qtdgouh.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/licence professionnelle\\/attestation_travail\\/Yd8rzY93bo2SgIbJJxmwfNQz14YWDVXby5y76ki4.pdf\",\"Quittance Rectorat de 2.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_rectorat\\/PUCdSXfUpG2dJlZiQBci6YZWzWY8etFUHoOD95nc.pdf\",\"Quittance de 10.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_cap\\/PaHZRayo7OVi5cC4efEkYIxdqAgwDUBx17orJ6L4.pdf\"}', '3', 4, 'dossiers/2025/photos/AwQdv2GR37dZUaAPFqAoZ6LnOqotH19pDcH223li.jpg', 2, 1, '2025-08-22 18:29:33', '2025-08-22 18:29:33'),
(203, 204, 'CAP-QzJvSeUcT3', 'Favorable', 'Favorable : dossier bien complet', 'Favorable', NULL, 1, 2, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/demande_da\\/rGzodyflBy2oonakSmTJ6pBIYh0k1EwTJhYgYoaC.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/cv\\/qbA2CzTRnbcIvZa2p569100anLp086mb2uQNiFrW.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/acte_naissance\\/wa2IQ4wLFyZrKsAjVUwUELlEemTGoxn5a2qtboPP.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_bac\\/5UL3P142qMSFVLFJJbvRWPup9osxZm3W4zrJj2vl.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me de la licence\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_licence\\/fXHm4YkeniNIFzN9OHDPLIUA7Agrezvvw1bsJl4j.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_travail\\/fGL3JcsIkcTXI2fSeLMBoKZS8FvNT0x1I71LoiXZ.pdf\",\"Quittance de 20.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_cap\\/Ujbx3osDrUC9haygQ7sZ0rVeteiw3MNHuBd57hqg.pdf\"}', '1', 2, 'dossiers/2025/photos/8KvKQBc4oKEyN9E4bV2nCsHTZfwvqGIbXnvnifU9.png', 4, 19, '2025-08-22 19:53:10', '2025-08-30 19:31:01'),
(204, 205, 'CAP-vTvg6n8XDA', 'Favorable', 'Favorable', NULL, NULL, 1, 2, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/demande_da\\/ObJw5Omj0Ijwu0vDSsAgjrV7qCp5xo1J2yrRWl5a.jpg\",\"Curriculum Vitae\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/cv\\/ESw2wTARG15YJTYiKVJjGhtbH3TmgeUzT7rJ7t4w.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/acte_naissance\\/WTfrRm5PrcaTpPfic23A8m5znHhjgi0fSJKjnyMx.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_bac\\/6jPrhD5QR6HMv1MdFRoTfXsuz08xGtelI1GRQTdd.jpg\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me de la licence\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_licence\\/ADG4Is76WnfKqDyXoQ1QMlJN8Oy3AZqAWv1Vg0Io.jpg\",\"Attestation de travail\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_travail\\/CvxavDdhIRNKIwK735J0JP4sr1c4Y3QjKt9rfHmy.jpg\",\"Quittance de 20.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_cap\\/c1wm7FOlh6RpyNr8n4LoVbvTdDBktzdcZyGMj7Ku.jpg\"}', '1', 2, 'dossiers/2025/photos/VmG2aHjr3mCL6Y6b5PVJUcRHShG7EoePxYmvafeu.jpg', 4, 19, '2025-08-26 08:38:07', '2025-09-01 11:17:40'),
(205, 206, 'CAP-dT8snyWrBT', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/licence professionnelle\\/demande_da\\/uenW3K4zfKABR1emt0IgZz3QrUo67ykNwCxLI2zi.jpg\",\"Curriculum Vitae\":\"dossiers\\/2025\\/licence professionnelle\\/cv\\/I6diHhc34FNIbASbpeshqCYujSDH0FNhZj4HFsIK.jpg\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/licence professionnelle\\/acte_naissance\\/W7pXWw4JLr96k98C9SZHwfLi42ZX8BXLZ41gJDwX.jpg\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC ou \\u00e9quivalent\":\"dossiers\\/2025\\/licence professionnelle\\/diplome_bac\\/4vViKmm4a7J48v1DftNKZspc79TpN1Y37ISfjczH.jpg\",\"Attestation de travail\":\"dossiers\\/2025\\/licence professionnelle\\/attestation_travail\\/kRuCJIdDMhHhjCEQaq5TfPTPdnh19BXjAArSnRry.jpg\",\"Quittance Rectorat de 2.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_rectorat\\/9sQkhNk3Xq0RITladBrc6ugC3MCqiOQNH5YcQDzt.jpg\",\"Quittance de 10.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_cap\\/VW7LqRFDxrom7NRHINQAAtPmAO9MGUYzNU4MfQau.jpg\",\"Attestation de d\\u00e9p\\u00f4t de dossier pour dipl\\u00f4mes \\u00e9trangers\":\"dossiers\\/2025\\/licence professionnelle\\/attestation_depot_dossier\\/VBgSveUYJxjvLYUOL3L30xym5nu1jfblONTjo33c.jpg\"}', '1', 1, 'dossiers/2025/photos/BQUY2kfxFZeMuGPTvyRuhYBVtJMDsvti87SlUqu1.jpg', 2, 2, '2025-08-26 10:06:29', '2025-08-26 10:06:29'),
(206, 207, 'CAP-LkShWFoSAh', 'Favorable', 'Favorable : dossier bien complet. \nMais peut pas ouvrir la seconde cohorte pour raison d\'effectif insuffisant. Donc démarrage des cours - Première cohorte (Janvier à Juin : année académique 2025-2026).', 'Favorable', NULL, 1, 1, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/demande_da\\/gh5vD4i0hYvWtDVrzaWF1jgRiKhNjTQgCGLvKkM8.jpg\",\"Curriculum Vitae\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/cv\\/p9o56fCdJ25OTQatUUun99rP80MTy1zPYOimGjFa.jpg\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/acte_naissance\\/jSvGTn8sDlX32HNNi5tKPfi8aeHN1tD0kVqsLKQA.jpg\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_bac\\/qicrFY3MhCKRDmdCwJoqhf5rKJMROmJlPFm8L7Gf.jpg\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me de la licence\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_licence\\/l3EyiISjup67QUGQ9r3e7ixzpqrugd9xyJhJ7NnA.jpg\",\"Attestation de travail\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_travail\\/k5OptBZFvOU2UGD0SmZ8KkMQ9iwpYk9NfJuQ6Nzk.jpg\",\"Quittance de 20.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_cap\\/6UHhUYR3KotT0gmcgFlbqyfJ3l9vTvxOwZGBVi8j.jpg\"}', '1', 2, 'dossiers/2025/photos/cHGyalrz8aewtidVqNoSP4KUTjYM61tgMcF9Hqnm.jpg', 4, 20, '2025-08-27 08:55:25', '2025-08-30 19:30:56'),
(207, 208, 'CAP-bwbWHfkbwz', 'Favorable', 'Favorable : dossier bien complet', 'Favorable', NULL, 1, 2, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/demande_da\\/lmaxwspz2l9t0nGrwIc9rhx0efwOUsjQmfPPemFm.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/cv\\/AM0hmLvDIYVXzK6cDyTU1R1T4INunlRyCczbSfZU.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/acte_naissance\\/r3jdnTfjPCS8p8toi7mxkb10dlFbzYKmEDdQemSl.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_bac\\/MqrXq4QxXkWyOusNkY7BXIHHfzEKIo56CY9QOUOJ.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me de la licence\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_licence\\/I5et2c8ZYLa2p8jQzgZF3f4uUQK0GJore0mc7FoQ.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_travail\\/LlH9pXp4F9ApVBGKXlXa8ZBCpA3qnpXjYCVuTv5w.pdf\",\"Quittance de 20.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_cap\\/QTPKrbU2QdDjcsntRGpPDOkm2q3iqWUx4JSWyWH6.pdf\"}', '1', 2, 'dossiers/2025/photos/UfRflNllrKm4LEo2m61iTO9yXI3qnKZQNqaNrN6V.jpg', 4, 18, '2025-08-27 15:16:52', '2025-08-30 19:30:49'),
(208, 209, 'CAP-wDrpus1iJu', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/licence professionnelle\\/demande_da\\/6lTT7u8YQ8XKmqGLuwX8Zlh1UxhaJ2FYmKQf7KfM.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/licence professionnelle\\/cv\\/UYVh7bD677XThiTWhrRelsVHdILs8zpQQuYU9hDj.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/licence professionnelle\\/acte_naissance\\/BiZGmcQZzkQSqzWrvPddKUzV4heXSgp32XJtGfxt.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC ou \\u00e9quivalent\":\"dossiers\\/2025\\/licence professionnelle\\/diplome_bac\\/9vMY155IUUb9VamUJvuh2qpMFgUV8sKG4T914dDs.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/licence professionnelle\\/attestation_travail\\/An3Tv9FAu2F8fG0Xl1U3FytCEwVZbiE52fhx8Zan.pdf\",\"Quittance Rectorat de 2.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_rectorat\\/nbDGSLLhwiUBhRH800W0ArDF4wg9vfPxAVCSq5MA.pdf\",\"Quittance de 10.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_cap\\/BIv8kYZQiiuwqcdAvuzN5P3z8rKI318wuU17N0SG.pdf\"}', '1', 5, 'dossiers/2025/photos/apZYdp4ZxUm8T3puP31IbJIqoqCuoTDQQGLb6Dzv.jpg', 2, 4, '2025-08-28 16:23:30', '2025-08-28 16:23:30'),
(209, 210, 'CAP-6e67TjljNI', 'Favorable', 'Favorable : dossier bien complet', 'Favorable', NULL, 1, 2, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/demande_da\\/gx5KYaJK3GlqaU97ytyFta6oiOZaV1INx7Cpnsc4.jpg\",\"Curriculum Vitae\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/cv\\/JR7h2fPKnVzX9JXckjMNHLJibjNB66srCD8RllGt.jpg\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/acte_naissance\\/WkVRwu264MX3tdqrldqbkrwjBxq64j5vS0ZKIuQe.jpg\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_bac\\/sS32pgs9UpIU1WB5TuNP8DeI2L0Z0XZFAAW5RGsI.jpg\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me de la licence\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_licence\\/cZpEHpJmD9z677z7XgDsVJferDnMC6K3urhlkibX.jpg\",\"Attestation de travail\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_travail\\/hpYdOef0aWoCWsp55f5edqBDnq82DurJQMsn9x58.jpg\",\"Quittance de 20.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_cap\\/47kFou86pbtFMmcEqbVgqTjeD0fGzx0wUswG5MDg.jpg\"}', '1', 2, 'dossiers/2025/photos/BA4vDgepl9E3GLjekmTZsesitoG93LqogY7FDW9z.jpg', 4, 18, '2025-08-29 16:35:32', '2025-08-30 19:30:46'),
(210, 211, 'CAP-x5NmAOKeqi', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/demande_da\\/E3h5xy3LCEAHDCrSGgUmol2h6PkDT2VzrkAOceky.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/cv\\/NfxGxVE9gUPfaJNtYSwEGZk1VnddSHctgwTy2Mr7.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/acte_naissance\\/affDBLkYrhPxLc2MEmf0pLSD28qaDnI3OdQ8CuTo.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_bac\\/muHrZJkVJtHyFV9wwcW2RmZ5k4MqOg7NYCIE7h3A.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me de la licence\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_licence\\/DoHjsOI33hft6vDSE9aJR2K6GUWkIopoorhHelcH.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_travail\\/tUgwjFcJDBwNhFFmOhSURsbVm0gVdjxEWXGvZh0L.pdf\",\"Quittance de 20.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_cap\\/471KUFqTX1P68AD2K84rbhiixLfWKZn7EpxKU9r2.pdf\",\"Attestation de travail(Compl\\u00e9ment)\":\"dossiers\\/2025\\/complement\\/Attestation de travail\\/1AWhnz9gydIUbedrVRxMZJgGOmyly83EudC9x6VT.pdf\"}', '1', 2, 'dossiers/2025/photos/c4aiLjfhzOEr7WYaPgtu1LIadHgHpMOob6fCcQjU.jpg', 2, 18, '2025-09-01 10:30:54', '2025-09-17 14:39:35'),
(211, 212, 'CAP-vJ3PLxT6V8', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/demande_da\\/LPPyyjDXK6IVocDmGLP5vTHfQIYnGl5ponMCbyfI.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/cv\\/uFuO2mfhqcoYyWf0BIuj38985Tzb5nSXdnTB1Cy2.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/acte_naissance\\/uvOsbAR8qbQl3PboVzhwzJ7JPHfxldoHy2EBsxKQ.jpg\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_bac\\/qVkZH421PUEVFjkHIjOdR4MJAv4I1oHiWyVAdbNx.jpg\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me de la licence\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_licence\\/I5jgedjtMHTFaO3pICJVDveSCDzWhV8TqkTzKgxM.jpg\",\"Attestation de travail\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_travail\\/BSkBd9xNLAH5CHdYkawTWwjnENNfYOcGIivrA2Eu.jpg\",\"Quittance de 20.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_cap\\/IPX98lc9xUIqwnoKnFGKmzp9qh2OIIuqePA0GqYh.jpg\"}', '1', 2, 'dossiers/2025/photos/gXCMvZUt1wnFVvFFf6VNLmNg8HO72UZNgUz2o4pX.jpg', 2, 18, '2025-09-02 08:31:21', '2025-09-02 08:31:21'),
(212, 213, 'CAP-q4BzKUYcae', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/demande_da\\/TTbTbEetN9OWwl2dRv5NmSsiyE0vwzeWVLMkyz4E.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/cv\\/ovHX4WB3KzsRGvwUpuhJWVyQu9LHlwRD6jEzSl7U.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/acte_naissance\\/17S3P7SV4fDwNl33cIraPfYznVaAVHwSFEHFKr6n.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_bac\\/9CyxjVdvcNpS89FLC6OxhfPU0L2zMoKFEZbdVyFY.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me de la licence\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_licence\\/6NksxSIflGMOegrlOv63bjTnzpEK3JDysyjA5LwX.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_travail\\/fc7BUDJ3rnXL8n9x3TfHYIobHbiKI9nHazEE8AN5.pdf\",\"Quittance de 20.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_cap\\/ReOb8gnjxDLgzKvrwJjpSxR53pNs2rZqObILawiN.pdf\"}', '1', 2, 'dossiers/2025/photos/syITQBB3RRTPAM9epOLSTaKZpwxSYs1EwUC7wjdY.jpg', 2, 18, '2025-09-02 09:36:05', '2025-09-02 09:36:05'),
(213, 214, 'CAP-Q8tEdi9wa3', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/licence professionnelle\\/demande_da\\/IPeXOJJqdEQnAOQK5Fxicllgoz9cUX9jf4tOdpwv.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/licence professionnelle\\/cv\\/CygkzTZG5KyHMbIs2ahgkfxHZzEEcqnE61C6TPUS.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/licence professionnelle\\/acte_naissance\\/yupD8lyimRcxV86XoOekM71apOILJzLjO8DJu6xb.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC ou \\u00e9quivalent\":\"dossiers\\/2025\\/licence professionnelle\\/diplome_bac\\/F4h4nSkYtLTU3yIa5JpLl839bLmPgDZjr21UQx2u.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/licence professionnelle\\/attestation_travail\\/cSWnKiWQn65zBd5Ub8zfLIyJj5tk0ua9ZKuykTUQ.pdf\",\"Quittance Rectorat de 2.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_rectorat\\/R6qEOjhJZhKznZeqC7q1Y3wRCGH2taHSUy2NQO0g.pdf\",\"Quittance de 10.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_cap\\/JAoFbzQPpx7EkTOB5cczBOgmkDnd0kkGiFsDt3oO.pdf\"}', '1', 1, 'dossiers/2025/photos/I2qfmjdM4VM1XJXe6uteVt9si5q8YMUrID3zoY4R.jpg', 2, 1, '2025-09-03 09:02:58', '2025-09-03 09:02:58'),
(214, 215, 'CAP-2EbxOsmFul', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/licence professionnelle\\/demande_da\\/TzWwXwhf9PkJPlwbeXjRQHuJaGSse8TnYsB9HGB8.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/licence professionnelle\\/cv\\/mA9OGTOlgoVmu0sb6Y8Ml0Dkp48iaTRtiqoPoAJV.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/licence professionnelle\\/acte_naissance\\/Tygww9HasZuFAZXUWvnpWkmMnYLAwNvQ0cYCurMk.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC ou \\u00e9quivalent\":\"dossiers\\/2025\\/licence professionnelle\\/diplome_bac\\/xwFihiPqB0Mj0LLoGdGef7hVzU6EL4XN4YBKsOUv.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/licence professionnelle\\/attestation_travail\\/zUulokgZEIDUNbH6T7tb29c5ghQ1NnDlHfy5UW63.pdf\",\"Quittance Rectorat de 2.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_rectorat\\/mMUTpIX3iGnmJNZ4gWOAHeL1UUFRDR2y9o7jio8C.pdf\",\"Quittance de 10.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_cap\\/ZcM92SWSjYyCCOj63gmqVIzJR2vuZI1XPETAhYfU.pdf\"}', '1', 1, 'dossiers/2025/photos/wKyKxDeu9zINVN2LDeMXrcL0FcvY0LpllcjUMSNY.jpg', 2, 1, '2025-09-03 09:57:44', '2025-09-03 09:57:44'),
(215, 216, 'CAP-kfySAkHF0J', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/licence professionnelle\\/demande_da\\/ZazT4P4saMBAfgHWW3zjGogtEs7grPfykiYpoYRH.jpg\",\"Curriculum Vitae\":\"dossiers\\/2025\\/licence professionnelle\\/cv\\/y8rmfqOgAfxV1uLmBzrWcr6lkqJAWcqgt0o3YYqH.jpg\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/licence professionnelle\\/acte_naissance\\/bC9sOd4jOPwVPpPBANVqtR5i2gGkR7vzcDh1c6lC.jpg\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC ou \\u00e9quivalent\":\"dossiers\\/2025\\/licence professionnelle\\/diplome_bac\\/B0r7LvvYQTGIAG2fT6HB84ZsIi2KrKAXghA6hHHL.jpg\",\"Attestation de travail\":\"dossiers\\/2025\\/licence professionnelle\\/attestation_travail\\/8rgB1fTczI9VxEhTjVRpEWMGIqmMkrZ1BI57IPC5.jpg\",\"Quittance Rectorat de 2.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_rectorat\\/xOf0gDzYnnDB03fzZtdLYCq3tt533HMv3KgxKmnz.jpg\",\"Quittance de 10.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_cap\\/zQpeDwbgZC0CwmlNFyimSrsKxWfldcd2viqQyl7b.jpg\"}', '1', 6, 'dossiers/2025/photos/KjIYOZkNmh9K8wiHcE0Tifvp50nC7VbHHylmhkG1.jpg', 2, 3, '2025-09-04 11:32:48', '2025-09-04 11:32:48');
INSERT INTO `pending_students` (`id`, `personal_information_id`, `tracking_code`, `cuca_opinion`, `cuca_comment`, `cuo_opinion`, `rejection_reason`, `sent_mail_cuca`, `mailing_number`, `documents`, `level`, `entry_diploma_id`, `photo`, `academic_year_id`, `department_id`, `created_at`, `updated_at`) VALUES
(216, 217, 'CAP-706UJIByOL', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/demande_da\\/kdJGAQ1jvBVKxqAHIhPoHvACTuQUvgufJL3Jwp7x.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/cv\\/I6nkIvBP0TlTeBKPphHYKMIQQfXs1X5KfUfFYWql.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/acte_naissance\\/3fgxPt1ntO04pi2ts2qAVItP1UXNdU90PBaf0i4l.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_bac\\/U6TZMr0gw6X71kpuwgqlA8qLd1iDkFNxc8pgim7h.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me de la licence\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_licence\\/MxrKxF6bPOBxvnBHojsfjY4nUVdpCFdcqKyLBpKs.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_travail\\/MsSAKWd2G7wt871xMRKNnVJqWJ01WhzgbAsj2JKQ.pdf\",\"Quittance de 20.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_cap\\/t49TsD2PmwrPLI6nX79W9A46MdPsCqKaEBsRTqvr.pdf\"}', '1', 2, 'dossiers/2025/photos/ybXcqPa7SEqkDZAEeat6WecYW7JgAeksfLM4tKXN.jpg', 2, 19, '2025-09-05 09:29:14', '2025-09-05 09:29:14'),
(217, 218, 'CAP-d4oXz5t0Jf', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/demande_da\\/CMaYOIjehWTwClSqyT4cHBHdnxwHXSN6EK4erag0.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/cv\\/SzOxQ3BdXquFVPXbMVflxeRNYCzKpmClXUiVOj5R.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/acte_naissance\\/SuDmk8hRvAzzGmAFZFzv9lEGR44RIoCEM7vbhm8m.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_bac\\/eLiy2MFIr3dpWvJXNmZl1V1mb1mAR0fPyq64ofsF.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me de la licence\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_licence\\/mE5mgjfJiZzeWbNxZhy6KrlaPa47omLIe40LTtsH.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_travail\\/ss7wNAEWAEqdi2CvWi8tYUH5YjFOe204Jb0Jg9s5.pdf\",\"Quittance de 20.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_cap\\/43fDf2pRkcQMLJDV7NwGQ0po6SHmvdslvNVO2PAV.pdf\"}', '1', 2, 'dossiers/2025/photos/wV314rtPUp1gr47Jait8wTbqxfkb3AWLNBktlCgI.jpg', 2, 20, '2025-09-05 10:46:28', '2025-09-05 10:46:28'),
(218, 219, 'CAP-sdMIfOBWOM', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/licence professionnelle\\/demande_da\\/BapT7Zp58nGax5Rztb3O3BrItrALmKYNRRbtGXwl.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/licence professionnelle\\/cv\\/QyPqpjTWbuIjXQ9mxYaWJVSvF5VtfS4lLEONxZtC.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/licence professionnelle\\/acte_naissance\\/SMi5uBWr0U6HsROfTNu3tGw36AxWtE4kb2sFLYl9.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC ou \\u00e9quivalent\":\"dossiers\\/2025\\/licence professionnelle\\/diplome_bac\\/Kw1ZdkQqsxSfaFhgcqMu9or4Xb9OUaWpHlsJh3gz.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/licence professionnelle\\/attestation_travail\\/NfLWCrKS0zWqjY5aDEYbkReiPrcp1Ey5aP4n7MR9.pdf\",\"Quittance Rectorat de 2.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_rectorat\\/1eNQTNbDveuMK3iBEj3x76rpwzTdI4VGUNtYNXZt.pdf\",\"Quittance de 10.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_cap\\/lGC8JPnyvcgqeaJjWxxCYfk3cCo3JohkG9CBq9Ow.pdf\"}', '1', 6, 'dossiers/2025/photos/z0O6NSrDJkFmKwq5nXc7y1WIm2QPOnvleVwXfdnI.jpg', 2, 3, '2025-09-05 21:04:15', '2025-09-05 21:04:15'),
(219, 220, 'CAP-3xg6WQy0FG', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/licence professionnelle\\/demande_da\\/PMXrKOldq92qha7xCNeUHDqXVVKC1QTLioO0lzy4.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/licence professionnelle\\/cv\\/cdPp0CIr06drKkKhMPrMOi9VSHBJA5nLfUZn3zL7.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/licence professionnelle\\/acte_naissance\\/Bu5oz2AXVl6QumIoRPtoV5rLtyC735GqGFaSaJ3F.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC ou \\u00e9quivalent\":\"dossiers\\/2025\\/licence professionnelle\\/diplome_bac\\/u2tzIlXj3zhkDJuoeUO9Kttz4kZImK3DiDlu1eGm.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/licence professionnelle\\/attestation_travail\\/qt91BLBkY2uxXm6gGvZnZ4U9XAI8rHANycmIuOV5.pdf\",\"Quittance Rectorat de 2.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_rectorat\\/coH3swKgatsniXBVZ5DU3RiDxKQoFw9dvXbXe39p.pdf\",\"Quittance de 10.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_cap\\/d4xHei4aVFkinNzdeYQruHXdturyPuAIs5LzcNw1.pdf\"}', '1', 1, 'dossiers/2025/photos/RpZjaozDQ2tIL3WRoKOWRG1y2aGOfIUzfplAesGS.jpg', 2, 8, '2025-09-07 09:23:23', '2025-09-07 09:23:23'),
(220, 221, 'CAP-Ac3BjCQlqC', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/licence professionnelle\\/demande_da\\/zSBAtNRQDinNFKqunlEQxD4dYvGzsXtSFy4wO9W4.jpg\",\"Curriculum Vitae\":\"dossiers\\/2025\\/licence professionnelle\\/cv\\/8KiPXJV6Shi6Yag3zE898ADTrV4NDNDkR7NMivDD.jpg\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/licence professionnelle\\/acte_naissance\\/PYucz13nGqRBil4XtLbVpVLDoWySWSKO4nltw4WT.jpg\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC ou \\u00e9quivalent\":\"dossiers\\/2025\\/licence professionnelle\\/diplome_bac\\/vBinb0yX6nzNoNI4NkOabY2uuCj8k6UHDtT21tJd.jpg\",\"Attestation de travail\":\"dossiers\\/2025\\/licence professionnelle\\/attestation_travail\\/W256qUe6UgpTo8zwwxKGIiDY5UXx0HJFhA6TcS9J.jpg\",\"Quittance Rectorat de 2.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_rectorat\\/15rDb8qfcGUIBS87LQydfLbKf8aVbIHT5kSsd47k.jpg\",\"Quittance de 10.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_cap\\/ferDxe5kiHXyCrtFUYMMxaArGt23Dp6IoUkRI8ON.jpg\"}', '1', 1, 'dossiers/2025/photos/boZUNxcgNsOUUitM1yofvuQKoRU7jKhUNyrhTclQ.jpg', 2, 8, '2025-09-08 10:57:10', '2025-09-08 10:57:10'),
(221, 222, 'CAP-jyv8IAbaHD', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/demande_da\\/ZHViSjOkxATzvDmzNqM3e2YcABJfpoiPCZryizha.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/cv\\/DSt9VkYKJilEQQTvKdoE97dFp4JPo7ERpax3n1Sa.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/acte_naissance\\/g42negpJn3IXAw3zOZd762RmOOb9HhSkYNncNgBn.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_bac\\/AXe8tXaAHjP6rXJqcs0Lmfr1fbsPLLsxqI0PqDY1.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me de la licence\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_licence\\/ryQKPEilrAThW2tfI9njaJo4mj0oiiwsYIOmlurw.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_travail\\/Y7XM6HAsvCEZylZoWnPrUZ72Cf9PK9Z9Va0fHqz9.pdf\",\"Quittance de 20.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_cap\\/1WeCwXdjNpQNpgUR7U0QPsgxYlM6ER83quMu38re.pdf\"}', '1', 2, 'dossiers/2025/photos/WtkJ4UJlks8sshINnRWRRRgMyr1bt9BhByhboGl5.jpg', 2, 18, '2025-09-09 14:08:42', '2025-09-09 14:08:42'),
(222, 223, 'CAP-rsFZpT53GR', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/demande_da\\/QVlUJorR3bI1Cu1bDcioY4o41tzidIjA2skMKmDF.jpg\",\"Curriculum Vitae\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/cv\\/aFYS8ETm08VzmNmnPwC40Hi3QRIoWR3AUAcFDdQ5.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/acte_naissance\\/BwJ6vNio7TrCm2f40jyIzX0gDRGXhDVB8elaxabr.jpg\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_bac\\/EopMa5jYtgGkAxmU3iKlPrIViHZpH16x9yxNAsNu.jpg\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me de la licence\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_licence\\/b7iaLqU98EGrOXC5AepSU7qNy1Cd2sBcF9SPYrR4.jpg\",\"Attestation de travail\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_travail\\/fowhYJYHCpcP13HqKiYXCSAGg5xQlEskmS3EZjzJ.jpg\",\"Quittance de 20.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_cap\\/9tzv1OcYmzgwzuHW2GpQuAX2m5AM4k4erTZB4eO0.jpg\"}', '1', 2, 'dossiers/2025/photos/gdx1qUV1B3Igeub9Y6L8qrnfv42x1YSoMzLPQnDt.jpg', 2, 20, '2025-09-10 10:20:03', '2025-09-10 10:20:03'),
(223, 224, 'CAP-xQVOkIEmIe', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/demande_da\\/ZkuK3n7w1U8V1I3uV4AAnIZcyMZfods1mdo0jLpY.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/cv\\/tzVYPkWHZ9jza8jhhq7GPkKdreiPMNqCvKCfNdNC.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/acte_naissance\\/Do1yc8lHSFeVNenGf1LPZiRtECAJDv90Ib7f3QVy.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_bac\\/wMG27gNRsgYXxGAZs4PnfbLWoG0AqKQHRDdcWJFB.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me de la licence\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_licence\\/pXmgTkVqT8Pvcr27fEFMnOkhb6BDgEsMa5t6ZXot.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_travail\\/UrkliSO4JJenoLKamAh3WAjJ5BYNOENHC3OH5GkD.pdf\",\"Quittance de 20.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_cap\\/VQ33ZJJwY68uXykpFkRtldTAYukNgxeyAqxA2StX.pdf\"}', '1', 2, 'dossiers/2025/photos/tguwd7lP13SN3A1Rl6FUdGUUAwVvPeY7025yds8W.png', 2, 20, '2025-09-10 15:28:23', '2025-09-10 15:28:23'),
(224, 225, 'CAP-3RkQoQ76Bh', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/licence professionnelle\\/demande_da\\/plxn7tB2axNDcONwtxb5O3XIY9aFKcqCbxN77aOH.jpg\",\"Curriculum Vitae\":\"dossiers\\/2025\\/licence professionnelle\\/cv\\/1kV0lcZLDgDFvHddKgSCOLldfDFxeqLtkpsWE7hh.jpg\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/licence professionnelle\\/acte_naissance\\/Z3pJLX8FnbzcxCAKiL3XZtWBWeKbpckWizjgYO1B.jpg\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC ou \\u00e9quivalent\":\"dossiers\\/2025\\/licence professionnelle\\/diplome_bac\\/87ZCoRl4tdh4P6uRcSBXWs1sj2lAQLtqXol902Fh.jpg\",\"Attestation de travail\":\"dossiers\\/2025\\/licence professionnelle\\/attestation_travail\\/WGAVAyfXX8RXosIUA6iTDllsOMaD7yL02YRBg70W.jpg\",\"Quittance Rectorat de 2.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_rectorat\\/3gVPKv6VHlJvcq9tSitXQYK2FsbkfVi3ku0CmhSm.jpg\",\"Quittance de 10.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_cap\\/SHLKduZPHkR8z9ZuwtCeKyvqoyJwZcX6anIhnVZY.jpg\"}', '1', 6, 'dossiers/2025/photos/sWEvnuvbMvXSUVoSv0TFJbzpiZNrFQQ45PTuur2i.jpg', 2, 3, '2025-09-12 10:56:31', '2025-09-12 10:56:31'),
(225, 226, 'CAP-urbKUFjVdv', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/licence professionnelle\\/demande_da\\/4loB75EPFcfRqV7RIhVbHS17xgwgVpDBDqpMTRiy.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/licence professionnelle\\/cv\\/bHTTTK5lJD5MIsNsfzvmNplTrNOT63nDXhBlVN0b.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/licence professionnelle\\/acte_naissance\\/KzSYPC7tm5clRBI59m4ljWTGLMdtScj60PUbmjS6.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC ou \\u00e9quivalent\":\"dossiers\\/2025\\/licence professionnelle\\/diplome_bac\\/fdwHQEey9tZM2SgurG95XPRK4ujtCDQoxiPbriAr.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/licence professionnelle\\/attestation_travail\\/OP3ovVDcWFV19H6PZo09NNlDPZvZz8GCcHs4eZQa.pdf\",\"Quittance Rectorat de 2.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_rectorat\\/Q5At4I9M1HPLvp9VayfeXSrHYDJpkYzCcblvqpPy.pdf\",\"Quittance de 10.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_cap\\/yCwe4sNybw6EhYVWh8QPJtj7SpwZGnylBxTfXG3l.pdf\"}', '1', 1, 'dossiers/2025/photos/UJNQObSQGwBuEvmhOf7Owh0KTN4eLUXaDsLSIeR0.jpg', 2, 2, '2025-09-12 14:27:29', '2025-09-12 14:27:29'),
(226, 227, 'CAP-a61cfj91Xa', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/demande_da\\/16LNpc3Jxk0PF4I22vxiDSOf1TFOI9Kz4KLF5uGA.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/cv\\/oo57pkXteVjom6gt9dC8a9X1loeKFo6jqH2bf9su.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/acte_naissance\\/IGIXqdwjKzmsb2TW2sKbNGimOAL0bIkXbsh0r6tE.jpg\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_bac\\/HP1xVmWQWhC9H8ndaOjlu4Oez9dC4SO1xYFKCECF.jpg\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me de la licence\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_licence\\/tMVAJyvmUUZd3yA08Bo0zuOLqWiXXrsGjmVe9T8l.jpg\",\"Attestation de travail\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_travail\\/6Qb1OyLvRZgXq21Z88pMW1UWoNhHBhkPzJOYz5Wq.pdf\",\"Quittance de 20.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_cap\\/KtytA6ciVgc7CIqaonVzJ3DWUdzF8kkrWUIshysq.pdf\"}', '1', 2, 'dossiers/2025/photos/4wMJ5AkPU0XGLsAaxwYOKVH4PmJwpbqp3ZCO7fKc.jpg', 2, 19, '2025-09-15 09:46:35', '2025-09-15 09:46:35'),
(227, 228, 'CAP-JvPxn7qTbH', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/licence professionnelle\\/demande_da\\/UShwGGucvI0Qub1TEXWsv20ITlfKveSsBcbjHTGP.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/licence professionnelle\\/cv\\/sAR8IfcmRs6XOTXJ5G3QOFt6JWimXez6GkKItE55.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/licence professionnelle\\/acte_naissance\\/pobnfo9xKBmBWdd01ndFeOAKERZ23awJUAzsWUJA.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC ou \\u00e9quivalent\":\"dossiers\\/2025\\/licence professionnelle\\/diplome_bac\\/zLl0aHsMVpH1VkZiAVA101bAKXYDWA031qSTbRaS.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/licence professionnelle\\/attestation_travail\\/eMeYXZgeANaKLBkkgPcVE6b7dM9u51OccxlZwTx4.pdf\",\"Quittance Rectorat de 2.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_rectorat\\/7cMLazqzPzb1zMH7KNYRVFJKuO9QxfDtWxGr4t6A.pdf\",\"Quittance de 10.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_cap\\/4X0qoxQJ4ztGtaxiKW6d71meCsOtegzl0cX79qCz.pdf\"}', '1', 1, 'dossiers/2025/photos/xHCH4UC5mQ0zKWLjbxUmAijrsY0OQbgsBqmCJH8c.jpg', 2, 6, '2025-09-15 11:23:43', '2025-09-15 11:23:43'),
(228, 229, 'CAP-Pkv5VIO89l', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/licence professionnelle\\/demande_da\\/Xu2Bs1XE3BNZFKTG21Y94WG8EPW00QhYhjRMlsW5.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/licence professionnelle\\/cv\\/30ueMrlVMvkjheR3VAeJ5LfQ5UFxxZLkXwAe4kaZ.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/licence professionnelle\\/acte_naissance\\/cvJXrgZfTEf9eGS7VHTfkP1N9aEux8N3xQLnMox8.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC ou \\u00e9quivalent\":\"dossiers\\/2025\\/licence professionnelle\\/diplome_bac\\/B8JCKNzFUxMhcwde27Ityv1V3rttBVUHhS8yb8Sz.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/licence professionnelle\\/attestation_travail\\/G9447X73FlSWVCg0MhFiZHEjd6d0SnTgRHr5TfSb.pdf\",\"Quittance Rectorat de 2.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_rectorat\\/OxdvGMMptOYj4IlBT4mMfeXqDwiMiKgoTXWB8qj0.pdf\",\"Quittance de 10.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_cap\\/h1Zqp8gSf2sXcCaINXrmqHsjBIvmUcSMlb6SV9ts.pdf\"}', '1', 1, 'dossiers/2025/photos/3QgxUupgfUyGfXiydDKrDp0ooJtDohborvgTFpyU.jpg', 2, 2, '2025-09-15 14:07:54', '2025-09-15 14:07:54'),
(229, 230, 'CAP-4FfSRd8phK', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/licence professionnelle\\/demande_da\\/6jG2rPgTXSlyWcPOqQam1UYDbOKiVaNVZwB7GIC8.png\",\"Curriculum Vitae\":\"dossiers\\/2025\\/licence professionnelle\\/cv\\/THmLXlGj1Nf4A1JMJuIYaKj2nP08xfHhknRhP5Zy.png\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/licence professionnelle\\/acte_naissance\\/7sIDVvTNwUzV5CZVdsknyIR9NeE94vDQcjZnk5GH.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC ou \\u00e9quivalent\":\"dossiers\\/2025\\/licence professionnelle\\/diplome_bac\\/aS31r6jgLH4k2waQHgLaF4H6ULpuFuquwpfsnBjE.png\",\"Attestation de travail\":\"dossiers\\/2025\\/licence professionnelle\\/attestation_travail\\/1gqMSFSpTt6iPPD5NMQwpBDwrTENg6pObylJApYU.pdf\",\"Quittance Rectorat de 2.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_rectorat\\/Ijz3z9VgU041X3wqWhAJpfepx1gF9mi9Wmb1Qql7.jpg\",\"Quittance de 10.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_cap\\/Ik4YrX4XKDiX6TtjeDM4YLmjPYmJJM2Rk9G4Gmky.jpg\"}', '1', 1, 'dossiers/2025/photos/7ZCin2SM4miEjoyK63mFDtuhLVs7bwUOmmhRMxh7.jpg', 2, 1, '2025-09-15 15:09:57', '2025-09-15 15:09:57'),
(230, 231, 'CAP-HZjKkf5Tau', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/demande_da\\/eO2Fafuy6TxPgTcKfayDWMVg2Ir4K0crvM1R8MYJ.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/cv\\/EBJ2BeooF0QFHMJ8yFveGNpBUWetKkZyNLIpdUnk.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/acte_naissance\\/xlu9wFFPJ8nLfbnHcIr4flbE4KZKI2Or8yYD5muF.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_bac\\/DPln9rcPKfiJqrulUQh90QvOcpeZVuFSNlTUYqK4.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me de la licence\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_licence\\/Ue89wg9SGUS93BcCkUJpELbC8i0CjYdCekKBkGIl.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_travail\\/eXAwmQsPPQ0cjD37MEcQMlH53h4xQ9XQEAOxYuXs.pdf\",\"Quittance de 20.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_cap\\/qBe0TOBRaKuqxZtyojhPVpo19bNbg4yR1LTme617.pdf\"}', '1', 2, 'dossiers/2025/photos/giMEru9kSrh3pgRdVF5h5fYmHfwLjx4uvafK1VeH.jpg', 2, 20, '2025-09-16 12:32:51', '2025-09-16 12:32:51'),
(231, 232, 'CAP-ufWVMCdEdm', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/licence professionnelle\\/demande_da\\/TTbEnIfwBlMn2h752ToqxQPuI73M2GloZZrlfWZ5.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/licence professionnelle\\/cv\\/0VHfX7xZsZVhzTYUvlsncjA6H2US2RFD3bwz6lKX.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/licence professionnelle\\/acte_naissance\\/Oogh7ubj63vrQLHMSqebAP4tGNvJumsSqnV4T1a9.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC ou \\u00e9quivalent\":\"dossiers\\/2025\\/licence professionnelle\\/diplome_bac\\/TpYbUgKDiYiv2mi9Cl7DVzvb8s0lne2lx1BvohhN.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/licence professionnelle\\/attestation_travail\\/09xu0vJ6wzipnbDCE4QT07rhFm1ZZh0MnifRsj9a.pdf\",\"Quittance Rectorat de 2.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_rectorat\\/ZdT2YV4r5wfJhu34jjNddNWkzdyWKb61X6OeuBK5.pdf\",\"Quittance de 10.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_cap\\/akfQX5mqLqIPuHpzNkaauGN8SXSjIg5K2Fu7Napz.pdf\"}', '2', 6, 'dossiers/2025/photos/dqVAyigK4pLzaYY24OKPZKm0LEb7q5cgn5ID1tZ8.jpg', 2, 3, '2025-09-16 15:04:45', '2025-09-16 15:04:45'),
(232, 233, 'CAP-0HF5V0FG7p', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/demande_da\\/8td1Ufngs08z8RsZFfhFok2bnVwnd0wlxqNqnEcd.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/cv\\/VF7oBj7niWSrj4XLw3c6ba1GFgrXKlU6C3AYvida.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/acte_naissance\\/dfZZjOkkIpNThLWHdkVW487ukLZJKtcu13LMvZbS.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_bac\\/2nWJNCAwASNC15QpJ4TwHDVvZH47NjHl9OXG83YN.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me de la licence\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_licence\\/0oeKm7xI5qzniZ1LS7DM4OmJAK2sTEYVzcBIRX90.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_travail\\/rgBfoJSCIzJ0UzxSIELcv1vM42mjxttkKHStsyuE.pdf\",\"Quittance de 20.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_cap\\/2GI2198TsX5X5gnGcUv65nYeixsdScwCwwIWRcdA.pdf\"}', '1', 6, 'dossiers/2025/photos/hQ1N4FUzl7E3BGwJBQ0T3LH98nEZWZtS44PdCjH2.jpg', 2, 18, '2025-09-17 09:57:27', '2025-09-17 09:57:27'),
(233, 234, 'CAP-IdH5Go0EXp', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/demande_da\\/FRLwHY4PJUOsSNWjS2wAFpS9dV26o1w3RAJBtYSy.jpg\",\"Curriculum Vitae\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/cv\\/JTqCHquEOogkG0VfFKchaMrzvPtTviHoQRKbQlXj.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/acte_naissance\\/mhRXJt2W7hy50LI4g5DfznHDQPNWMQq1DfEWlrtk.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_bac\\/2IK0weaszEKwUTtA5ozTaH2XftxRqQI3UQrc0Shu.jpg\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me de la licence\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_licence\\/ahji5DbxXSB3bUPqOVqA2FjhJdITmpx7k5ytXvBK.jpg\",\"Attestation de travail\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_travail\\/K6Ij2SzqojllRBjCtumtzbJOYXY88E50ouZgrsD8.jpg\",\"Quittance de 20.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_cap\\/hlgX1fWGSpmLFHNmYCVpC6sMdYmU10SX7ejnVkNS.jpg\",\"Attestation de d\\u00e9p\\u00f4t de dossier pour dipl\\u00f4mes \\u00e9trangers\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_depot_dossier\\/cn462SbSUowcyI4aOhHCUdCzNNasL6ocWIiMXjTv.pdf\"}', '1', 2, 'dossiers/2025/photos/WOr6hXJkFS0fuP0M7GfNKwAPA7yllP9Z8TenfIH7.jpg', 2, 19, '2025-09-17 22:30:14', '2025-09-17 22:30:14'),
(234, 235, 'CAP-A9A9J4MZTs', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/licence professionnelle\\/demande_da\\/BZGvsNERmhihVSOInVIWRXLeZI3AbXEXvIGc7htE.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/licence professionnelle\\/cv\\/XPHyxNEISmHiSoOH6JLCh80X1jsNPsMjmycE0uUu.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/licence professionnelle\\/acte_naissance\\/1niWnog71VheT4rg3XuNwh4bFlEE3DEgjJ4K2ZVp.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC ou \\u00e9quivalent\":\"dossiers\\/2025\\/licence professionnelle\\/diplome_bac\\/LyG9qbhumCbiwA8SQZYGo1k1uZCERc6lRQge9a1b.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/licence professionnelle\\/attestation_travail\\/sh9o9nsbsnQWR2e8cEhNeMyRNJqIqwdU2c2zUSv1.pdf\",\"Quittance Rectorat de 2.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_rectorat\\/L6mGp4Hq5Gos1rqzjn15Gt7a0uFUMeQqbdGDGBXE.pdf\",\"Quittance de 10.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_cap\\/fWeryiU5nVCaiF0NsX2bNfvhYR53ZW8Mogjd1EXq.pdf\"}', '1', 1, 'dossiers/2025/photos/LVbH5q5tAXCskkOhxzjAiczk0dZlGkkA3NR32l6i.jpg', 2, 2, '2025-09-18 11:59:31', '2025-09-18 11:59:31'),
(235, 236, 'CAP-VTQRNMHktk', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/demande_da\\/4ybFgBzROEGNAQ72TbwF1ylqmz3nLv3zcDBUf95h.jpg\",\"Curriculum Vitae\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/cv\\/JOfXGDgoLaqFtuLxDiEgmxe5UFJl68LnEHj7XXTR.jpg\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/acte_naissance\\/8eUJMkHySTLZUePu6BlreNCFXmFGv9JD6rvWFe0I.jpg\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_bac\\/miT5S59hoitiArgyuIorGZZiWEtDTpBKy6DzPKGS.jpg\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me de la licence\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_licence\\/LwlaG2zwFikomD9UTfXHQIG6WiNcUuRka29W7eT7.jpg\",\"Attestation de travail\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_travail\\/ouNMqQq1NHjv8fZzQJx5258UaPEbRuAEFOP9e4C1.jpg\",\"Quittance de 20.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_cap\\/erphHfxTbelapOyRUYoY20hn6jCJD4WY8wkCw8gy.jpg\"}', '1', 2, 'dossiers/2025/photos/iZX9K7Wn2DLFrTFAKF4pX4wRFvLnj69AEz90iJpx.jpg', 2, 18, '2025-09-19 08:09:09', '2025-09-19 08:09:09'),
(236, 237, 'CAP-UkJtHluSwn', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/demande_da\\/dfrUeQqcbPohm4VBkOOVNtR2LPjXKGYkBhi5cMxD.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/cv\\/UjRDq98VxtFIw3ysz075ROiJzw6Bs5aVwGfLdi2w.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/acte_naissance\\/gonuEVbG7dOFIQLK32xWq0kz5cIPmBQMR6210Kfw.jpg\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_bac\\/D6nNCe5ojA9mVTMxiJR8LEELVQIB3DwQRL9BDnWP.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me de la licence\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_licence\\/9HfPUNKKWiQBsEVG02jnRgJNKmsNgMpfVRMoJKBW.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_travail\\/PSFhvte9P8Y605SaZOd6mnV7wrnmUlqQQvmLC1re.pdf\",\"Quittance de 20.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_cap\\/64W5Qjznj1Dq2V8u6WPrJOJY5WwQ0vwykKU3yeyJ.pdf\"}', '1', 2, 'dossiers/2025/photos/MaY0bixtlGzYRyz3zTtoC4iPzYpak5u2kahAQIf2.jpg', 2, 19, '2025-09-19 08:28:33', '2025-09-19 08:28:33'),
(237, 238, 'CAP-ecjKglogGf', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/licence professionnelle\\/demande_da\\/tAVYCiXYH8TYt5AMjgLMdX2ILdgMtaUUVSjd6qsq.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/licence professionnelle\\/cv\\/ijkyJAPBp5tng9bHfUEV1xASB6FtT3ZlYovNSTNO.jpg\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/licence professionnelle\\/acte_naissance\\/o05Y4wotn7TpoWnQ49YfVy1z99g2GdiOai3UqGY6.jpg\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC ou \\u00e9quivalent\":\"dossiers\\/2025\\/licence professionnelle\\/diplome_bac\\/ja1cTeRD5Y9GqQNoe6BQcLV9kK35vul4Len6WrZV.jpg\",\"Attestation de travail\":\"dossiers\\/2025\\/licence professionnelle\\/attestation_travail\\/dVn9Zb3sMuAFC5u2S0HStVtKG2ZmuldNOdYDXd8h.jpg\",\"Quittance Rectorat de 2.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_rectorat\\/UJUPc63sIMY9N6sHizr9CTbeX2rfNVPaGsqbZbAA.jpg\",\"Quittance de 10.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_cap\\/4vU1eKmge2g8Xk8ILcIR8z7DTXDFczXeSFZltR2S.jpg\"}', '1', 1, 'dossiers/2025/photos/bSAYMDb7TOEEHMrdXCirjkIgXAod4Gd3vqPflKDJ.jpg', 2, 1, '2025-09-19 09:32:39', '2025-09-19 09:32:39'),
(238, 239, 'CAP-0gfr1ldHjT', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/demande_da\\/4nMa7ETBY4AVna5DYZQ1BYdu8XvHT0ZYqrc4wWH2.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/cv\\/Iq9wc6wTHhXSmnTuL4VXIkdAMNjK9SWbSncRKbfT.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/acte_naissance\\/TEkr0A3ufXRouZSkilNgZhg15HctH9ZWHRbRbeAp.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_bac\\/WMuC0vf27sut0kWkELLdCBNVKNBDLIzxfcBRYarK.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me de la licence\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_licence\\/XhoILGm7bsvlqhudIjvIgJ0IkhMwpgJ8GgaBGQjY.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_travail\\/M1BDbTksAuSsQ13NeqjhHB47fHExusNeZXp4co9F.pdf\",\"Quittance de 20.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_cap\\/xF47SQ0pFuUBv1i7lm3Py21pcrtsNUVjnUuS33oa.pdf\",\"Attestation de d\\u00e9p\\u00f4t de dossier pour dipl\\u00f4mes \\u00e9trangers\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_depot_dossier\\/WEpSnxFOCe9r9U6dZcBY3aYQM504A0suW2Ol1qz6.pdf\"}', '1', 2, 'dossiers/2025/photos/5N1Q8EPiD2Yzlw2q6F0Lx6scAI2oYHROJh3zxy8V.jpg', 2, 19, '2025-09-19 18:59:47', '2025-09-19 18:59:47'),
(239, 240, 'CAP-hxAGjcbyyr', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/demande_da\\/BDlIPZgTgtp0VUUwvERNh5RXdcd1RugKOGuUTJi0.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/cv\\/uQaBVgzI7TI2Ff7PssOOHl407VHblOuaSXL8vWOf.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/acte_naissance\\/3zrsuaWAzephaDBILbLbyhzAVkUBX8FuhyAtpKUE.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_bac\\/JdbJUbvK6cDKjCCaI44QuSoQrI3QWN1REUMzCGVW.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me de la licence\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_licence\\/9miWKHI9jzKUvQT3Cd4d4Z1R5LrqH3yUzZR3kFNe.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_travail\\/OKGnUYgsfcNunRL1KlnLj9frmWHvFUMjeJtPNKe3.pdf\",\"Quittance de 20.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_cap\\/OsnZ6F8CJoWhSdC7bzVr3b8RZGTwDqoBLyuDMr1p.pdf\",\"Attestation de d\\u00e9p\\u00f4t de dossier pour dipl\\u00f4mes \\u00e9trangers\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_depot_dossier\\/N4erwqG8LKA9VxVeGKCeblQb56UCtjuEhtE2gAPT.pdf\"}', '1', 2, 'dossiers/2025/photos/UxZJzcmHEWHZrsm1X8ZfuCyk0TNZHa7jvP7xGdrL.jpg', 2, 18, '2025-09-20 13:56:19', '2025-09-20 13:56:19'),
(240, 241, 'CAP-b4jdK1gC9q', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/licence professionnelle\\/demande_da\\/F2jtG0q0wFegt2ROMdlPiYObK1pe7hSvd7xx8QlH.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/licence professionnelle\\/cv\\/7jqQ6KvzBMAyWcDJ78mtfUpaukHHyhJ7UjTYb77s.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/licence professionnelle\\/acte_naissance\\/ZwZCvEjj5AsnhWSaJR5owLTdZqSzTCMRbkvp44aQ.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC ou \\u00e9quivalent\":\"dossiers\\/2025\\/licence professionnelle\\/diplome_bac\\/NHtX1x65dBfs4y0MrIasTFH2VwlVkEgkmqB0ifRj.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/licence professionnelle\\/attestation_travail\\/MJ0GLniP6FcVWayNZpUWuDINZD9LtEX7344GPg4n.pdf\",\"Quittance Rectorat de 2.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_rectorat\\/3cQmQl9G0DzuJKqYhiMMexeluGIKv7SNJU2LG2tn.pdf\",\"Quittance de 10.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_cap\\/wHavj0ogpOhFWqAADCARMghq2j0IlQKuBc27FxOs.pdf\"}', '1', 1, 'dossiers/2025/photos/j341BfQRBBcWNLkNGMXPGtwJJigWJ3GwS3MBvCp2.jpg', 2, 3, '2025-09-20 15:18:33', '2025-09-20 15:18:33'),
(241, 242, 'CAP-qEDRLoyniA', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/demande_da\\/quA67j8Hok3UTaG0PDglMTKCuyQNbTtoPiTDJv5l.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/cv\\/KOjq2amvmaoyqmsMkZmG4lbimDKGtGPkL3gwLhw5.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/acte_naissance\\/euRj1RpBioxiaNkqeCy8ZbjJpBSMxgMtyCYwsDrT.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_bac\\/nYy7nEPX2eHMvCLFIz49hBDAgWdFo2Uij0SREQjH.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me de la licence\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_licence\\/Ydhb28jbSzCvWaDH2wjl4PnMRNnwnlU5QaelgOUH.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_travail\\/bec1uUH0Do5j3aywX3vbDPUD1MP3Vn3ex0llcJa2.pdf\",\"Quittance de 20.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_cap\\/aGsGUTZkuWik1uz9sbyi4IDKNDZiCeltIO367701.pdf\"}', '1', 2, 'dossiers/2025/photos/IOGJvnXwcw8l0dA0dfYcS9ce06X57jGZB7crrnWb.jpg', 2, 18, '2025-09-22 11:51:05', '2025-09-22 11:51:05'),
(242, 243, 'CAP-NsyhtcNh7Q', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/demande_da\\/FMllESsXviCEE7zIoycsKf2ps9tMH0zMWqkVlDaK.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/cv\\/bmqq0ATP8XuY4JUbE8GRvZGLXvctiKCqPxTXYKDR.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/acte_naissance\\/97e6YhcQfxLFTlpgRKhYf116jqHMqj1zTSVS3PMA.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_bac\\/QMQy6FulzZirUri3tZ76xpgJVBsaz71zOpKJL5Mu.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me de la licence\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_licence\\/dbQzWhnJDgNLnuSLqznqXnzmi7zMF18qnaeVrpY8.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_travail\\/9svGOKp24RewMTgbmVnILCAp0SVeqrHs7agmTkzY.pdf\",\"Quittance de 20.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_cap\\/JM6TM74LgaL6zENCHO7tHm1v2ywtwnY8n36Iejpk.pdf\"}', '1', 2, 'dossiers/2025/photos/dBGlqJo7asS5qiHjYsHlaJUgIxYbM301XOuDLpva.jpg', 2, 18, '2025-09-22 15:25:26', '2025-09-22 15:25:26'),
(243, 244, 'CAP-Keb5b2AQSW', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/licence professionnelle\\/demande_da\\/pWrx5TsEHsATaZwApubudcTOCQzY6hZkgpuPuhgu.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/licence professionnelle\\/cv\\/tezLZKmqPbxZ7EZZ2XRZafItUXz3aL5lFkq52ZLH.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/licence professionnelle\\/acte_naissance\\/CZyCEGPeYch1Dqsl7sx0FODwy8iGZAszKhdkXidH.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC ou \\u00e9quivalent\":\"dossiers\\/2025\\/licence professionnelle\\/diplome_bac\\/KoFy83e48y32KOI1xIkl27t4Q5y643cfH4TA4jT2.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/licence professionnelle\\/attestation_travail\\/AXjWyZhBB4R7ZoNfP15TsUldqs74iVKA7Oc2gg2f.pdf\",\"Quittance Rectorat de 2.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_rectorat\\/2Q2R4aXgkgWNGV1Mj5H73YLhcZLmKh2WcrO8mPhv.pdf\",\"Quittance de 10.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_cap\\/SF6iOAOX2Aj5mJczuXrEItv5eCsuvsytT3rJjUxN.pdf\"}', '1', 1, 'dossiers/2025/photos/MXxhDVT3f2hMU2RF7XuujD1Jqv5FM4heWE4IW6Mo.jpg', 2, 2, '2025-09-22 18:50:48', '2025-09-22 18:50:48'),
(244, 245, 'CAP-yJVxyyW6Qc', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/licence professionnelle\\/demande_da\\/jKycCa4Y20GoXIXFHx3T4zBY63ksacIAzxG1WIsq.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/licence professionnelle\\/cv\\/tUxS7wUTqDmEbncU8YecmTuiJ3ubOMNwjnRvUxUl.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/licence professionnelle\\/acte_naissance\\/eXEnz2vdWoswSUfs9aQydOf07Y6oodu2i6jgIwus.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC ou \\u00e9quivalent\":\"dossiers\\/2025\\/licence professionnelle\\/diplome_bac\\/bbFBUGwBVsf1dqGMqgcMKlFlYgghROgAMJSDgNKz.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/licence professionnelle\\/attestation_travail\\/smjpVwMzPWpTJhcM0cQamFJhONAsmMPEFNogpwYO.pdf\",\"Quittance Rectorat de 2.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_rectorat\\/6Z2jXJqsBBDwYFEWkbuZjNysPQ4jvTy3MVAervNn.pdf\",\"Quittance de 10.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_cap\\/uzw5lM6CnuU7zyYywIBWFbHH7eLBNf84K33keFvG.pdf\"}', '1', 1, 'dossiers/2025/photos/paZ90nUaaoRmuV5OC0T2pSE6XpYKXpi4Ynvt2BH6.jpg', 2, 1, '2025-09-23 17:28:38', '2025-09-23 17:28:38'),
(245, 246, 'CAP-sc33zaAiPu', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/demande_da\\/DjVaij9XpQoom5ACPVsPWvo49MHevdzjjC8Q9z4b.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/cv\\/JeSBk2K2subbjaCi6qdqb6gX7RNJtaxQUcQVtweW.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/acte_naissance\\/Oy9e4VPnkfcZIgtRgKN82yLZvT77EGOYAwlZJZpu.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_bac\\/1ld78K3isPUsL6s86e9thYPlQIc4aAUFU5jaLvaF.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me de la licence\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_licence\\/xQnlxFAC1ufghexCLr1xeFrqGuYVJ8c37y5wJ43w.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_travail\\/vSvogtGrRPdnWBnBDoBuAE9vVNTEJcw9yra02QwG.pdf\",\"Quittance de 20.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_cap\\/Qb9VZdImOs6ptmV7zjCArQNmXZYDBCwUR21PrgUY.pdf\"}', '1', 2, 'dossiers/2025/photos/WG2uwRW4FoEqE9v4GZP4ynJoXAj4mzjhsZlHeo05.jpg', 2, 20, '2025-09-26 11:06:48', '2025-09-26 11:06:48'),
(246, 247, 'CAP-eOz2IEX73d', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/demande_da\\/6Imu2wkViDiET2QELyE90sSijH2QvoxkOfEQM0eK.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/cv\\/GWuIVqh1WKDPkem3hvWZGsBnFW9SBrJv7Y53GbGg.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/acte_naissance\\/bvxRlWe0plXSDphiK3n7nvRWPfsP1ifOjefHMtYX.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_bac\\/UrjFk5k6LRauKSOo7tYLqPgP9NbbQ5K61pRiAJPI.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me de la licence\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_licence\\/WGBiMGRRU8jcCfoiwURokdGNfKGARlbLncJcMGuG.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_travail\\/HyUBjqZf3ynNMXorKudAIwxaZxsZXE3N3DChRinu.pdf\",\"Quittance de 20.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_cap\\/MVGy7dMWb9OGgJ1X2n59uuIWRz85qNXFz2aFTdH1.pdf\"}', '1', 2, 'dossiers/2025/photos/HWm5sDgLERG7niSC7ZjtTNeC2Ovg55ICTnP2YvLd.jpg', 2, 20, '2025-09-26 11:31:33', '2025-09-26 11:31:33'),
(247, 248, 'CAP-TDpIyv5uH8', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/licence professionnelle\\/demande_da\\/o572GDwkHWiHWUh19GnWlp8XPXWKePzfh3ZMjF5L.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/licence professionnelle\\/cv\\/9UsGUDDvPszvKw1mGXlxqR9GNoPIKpXMZaL25xAJ.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/licence professionnelle\\/acte_naissance\\/ToEyWwSgm542PCqGi9xmzzxJ0njkprzTWIQHQIfS.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC ou \\u00e9quivalent\":\"dossiers\\/2025\\/licence professionnelle\\/diplome_bac\\/vxgnWGPJnbkxZyJl1xSBjLLETiWFaVYz9Mn6zPfR.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/licence professionnelle\\/attestation_travail\\/ngotXhJOB1PKioS30er1CgtHcBJH7FnMUi2fmALz.pdf\",\"Quittance Rectorat de 2.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_rectorat\\/5Wb5OgOkrGELJU7rZQS1GMlbx8NQoegudcbv0i3H.pdf\",\"Quittance de 10.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_cap\\/RDYrCjV1SrG94Zu6jt1ai3pwwrk1q8zzIjb0mjoF.pdf\"}', '1', 6, 'dossiers/2025/photos/0fn24REVxMZK552AD5yYbiByXk7Kb0e5XzkNXpmo.jpg', 2, 1, '2025-09-29 10:44:20', '2025-09-29 10:44:20'),
(248, 249, 'CAP-G3IICwvhks', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/demande_da\\/z8j71mThWwmdCZctozXRf24oVhM3oO5eGqDLKih9.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/cv\\/52k1TnlHlYgEKbs5zTlKBiqY3RWsZMntX0737SX2.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/acte_naissance\\/1q4L6Iigj28ql49e3BEYbExKRKLQCpefx1OoAdrU.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_bac\\/3lbfZtulids6fRatRxPQEmXox6772Yryb4C3g8pc.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me de la licence\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_licence\\/MJVZ85eOWS2NyVHgzNLKyMpWClEWkI4sTXWF5EfG.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_travail\\/KjFLSWjHExRt9y078HdbCPmLI7Nu49qg3igvfnlb.pdf\",\"Quittance de 20.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_cap\\/Vr0JP1NTIjmip2HIaY1jgPLnQZViKuylkoMWIz3Y.pdf\"}', '1', 2, 'dossiers/2025/photos/6pDL0MH54djlph2HIEWeZJk3k8AqKkwWSRadGtn5.jpg', 2, 23, '2025-10-02 12:48:25', '2025-10-02 12:48:25'),
(249, 250, 'CAP-mhwPQv1KwT', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/demande_da\\/Fdah0td6mZ4QFTZSwtfo2ZJBiElFjsvqATp2KM2I.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/cv\\/9NFGFpqooXYH1JyRsc8gqEXiHftLRslTfmDndJtn.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/acte_naissance\\/m5cGGXT238ZKJqGGqV1JDckqnEmjgr7adUpMxD9Q.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_bac\\/efLQupIjBVfEFRv0zgFeWnGPBgRSnzPOsHllIqve.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me de la licence\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_licence\\/vcIGQyBDqxoFbhPESBDvquAlPtBIyHLou4C2EACV.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_travail\\/9fKE3LMXlkqzmxRwa5rFlAC1aAdyJQsqpwZ9LZWj.pdf\",\"Quittance de 20.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_cap\\/GqwlI3rnIuvcldm8HNpMZeAiEVjya0zuiGmKkAhf.pdf\"}', '1', 2, 'dossiers/2025/photos/OpiBDXuhOOutRyYMbJN8tl7EsokCXnBspqAAgtxu.png', 2, 20, '2025-10-02 14:38:50', '2025-10-02 14:38:50'),
(250, 251, 'CAP-yEBKXalwfj', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/demande_da\\/sFjhwyy4LIwjGbIQmGKcOo8IbfhNn83IT8WvfxKQ.jpg\",\"Curriculum Vitae\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/cv\\/BNSf5tN5Lrg8VqX2NX3mZx2itsVBXCxt0gJeemtQ.jpg\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/acte_naissance\\/jT7FswDi7vIXrT67nu8A68qyeRsAL2PvxIPQx0J2.jpg\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_bac\\/3T7j1ALD5nv1HihTqYqzXe4tmArCbwSfOdyMEGBp.jpg\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me de la licence\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_licence\\/JZrpZyKxhXoOPLoGnOFAu1AWKIiNegY5VK7hgABJ.jpg\",\"Attestation de travail\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_travail\\/YTwz89FCDlhnhnWtN7xxWp4fdjpgDRG2TLEQfArl.jpg\",\"Quittance de 20.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_cap\\/zOxTG2sa7fIiMQ14HF0zEZdIFxb5FtXMLDH50Gd8.jpg\"}', '1', 2, 'dossiers/2025/photos/x4rlWT30FSBypuBfiM8D92pIaTguM1Gv5pnFTPlS.jpg', 2, 18, '2025-10-02 20:50:19', '2025-10-02 20:50:19'),
(251, 252, 'CAP-8f300JlWVJ', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/licence professionnelle\\/demande_da\\/a4kBYdtQduAdqfuQVvUl8475MVsYSSEKsnBUGXfs.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/licence professionnelle\\/cv\\/OCgGOGvch3KGs0JyVH2jReKcFJ2VMSEdY2oUlbRt.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/licence professionnelle\\/acte_naissance\\/ZbgfmqWv4mU1IsTQJY0n2IJHtzfgeB9bRzXxpim7.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC ou \\u00e9quivalent\":\"dossiers\\/2025\\/licence professionnelle\\/diplome_bac\\/8M6TOaTQPJFM2GkCDjgOqjrh0asnZgCXJazzLtHm.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/licence professionnelle\\/attestation_travail\\/I0Kc9mOUbpUXgV1moX2bHGJwFLJx7IvZ5kgm0aY0.pdf\",\"Quittance Rectorat de 2.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_rectorat\\/vOwDwDMkGCiLfhL9RusEDwMeoXkNyZvTg0YBb2Ce.pdf\",\"Quittance de 10.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_cap\\/YMmOzkT6C2zwnKVV0naKOKhpigzQrPTxuVlBi8qK.pdf\"}', '1', 1, 'dossiers/2025/photos/aOpGNjqg0Gi8GFPvpxGUNTcJfFBz5AYhCT5A1PGZ.jpg', 2, 2, '2025-10-03 07:55:42', '2025-10-03 07:55:42'),
(252, 253, 'CAP-ybn8CbgTWZ', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/licence professionnelle\\/demande_da\\/0OzvfvB26Kf6FkYklFUqLL2aONlsKMt0Ugsq8Mq2.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/licence professionnelle\\/cv\\/QaGhEOjEZ8v0dI8aYLsFSgZY9Qz6DkvBYpp16Wgn.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/licence professionnelle\\/acte_naissance\\/x59ZOrgRaCgZEUiJcP2AQVcQWmdWXKUog85ACRje.jpg\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC ou \\u00e9quivalent\":\"dossiers\\/2025\\/licence professionnelle\\/diplome_bac\\/DSAG7xUI1ic8wjNoyIK4vFQ6kPS2nIcWqXzmfnlO.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/licence professionnelle\\/attestation_travail\\/JPa3aHeow6rEiUpL2Jjip4iXKrHuHORksFnN9QeU.jpg\",\"Quittance Rectorat de 2.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_rectorat\\/7KQTdK09l1KIx2s3nh79hbuvGF7Wh5AqPMUeOcRf.jpg\",\"Quittance de 10.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_cap\\/W15tV1owHQKXrfOgjVsdvios63nfff4urSFVP1tm.jpg\"}', '1', 6, 'dossiers/2025/photos/9GIakFsLJafvKRvwOIIr86jjQll3kGvlJ4a84aDp.jpg', 2, 2, '2025-10-03 10:16:47', '2025-10-03 10:16:47'),
(253, 254, 'CAP-YHUqbjKFUN', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/demande_da\\/VnRLBifN7x6T9kqTenkAuQU68plTugNHFajXPsc9.jpg\",\"Curriculum Vitae\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/cv\\/sqjcybtGYoUb1HZOIBoLwyyODtDnkaZPbr37bJBk.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/acte_naissance\\/e29zOk1hVcFn92cthLKd5Kl4stAxMvNQ3GSN25G1.jpg\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_bac\\/oBtRhT7SgjYcEW9Zj85MbTmsGV3wW3P9HX4H4RVI.jpg\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me de la licence\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_licence\\/tTx7MPDaB27PQOnkOPIlbpaMyXl5KnbNQghNGZDB.jpg\",\"Attestation de travail\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_travail\\/FFbgMo6uS6T179MUgCCnyP62OWhg8dG0oijzqSoa.pdf\",\"Quittance de 20.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_cap\\/m1NntwI1J7VD24kLtavkBsYXIem4lJn2EzCjP8A3.jpg\"}', '1', 2, 'dossiers/2025/photos/9z3osFw0QytDpdOQHEBClQ1dojdKv8kYO9ZjHxtm.jpg', 2, 18, '2025-10-03 15:16:47', '2025-10-03 15:16:47');
INSERT INTO `pending_students` (`id`, `personal_information_id`, `tracking_code`, `cuca_opinion`, `cuca_comment`, `cuo_opinion`, `rejection_reason`, `sent_mail_cuca`, `mailing_number`, `documents`, `level`, `entry_diploma_id`, `photo`, `academic_year_id`, `department_id`, `created_at`, `updated_at`) VALUES
(254, 255, 'CAP-cd6RH6pScg', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/demande_da\\/2Zpxl0NYUIrkedbGBxXRTJOZ17EjlUESBfmi2j7B.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/cv\\/Ytkw0db4d2efxhSWhaYj37mYRSaaEE73bVPSXDMv.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/acte_naissance\\/Ngwyw3VCkkV9ercPqzqpwPHqzOfj1kbHNy97lKjG.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_bac\\/dKUx3AC46eivwgTYWdKhJVYVcUeU3LPAPwUJ4p0A.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me de la licence\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_licence\\/KZwfzU6bKHLJC9ImK7KXnAeml6YFOIkcaoMOt6iV.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_travail\\/bkIdfh9TduouhHb71vfGfaErOUPjkw1k9tFchDeB.pdf\",\"Quittance de 20.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_cap\\/TYZnCKijtr3Hz50aX2lAdGqjX0qlVs36BCQLJsz3.pdf\"}', '1', 2, 'dossiers/2025/photos/nPKZpz6xZM9VChhfTJZjQGuRG6ufTVCLKcDYPlH3.jpg', 2, 23, '2025-10-03 22:32:59', '2025-10-03 22:32:59'),
(255, 256, 'CAP-8cOnIHbbOW', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/licence professionnelle\\/demande_da\\/CfgtP91EnKeV22dzb8xaal3iPwngZyfQxtCisxyq.jpg\",\"Curriculum Vitae\":\"dossiers\\/2025\\/licence professionnelle\\/cv\\/rPP1tRxgx5DV79OWdlLT4QMbqRGYQvhzUbNT0dSv.jpg\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/licence professionnelle\\/acte_naissance\\/lu2hbMkV2LsuzcOIIUFe521pT6BPVAtvhtlAOCN5.jpg\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC ou \\u00e9quivalent\":\"dossiers\\/2025\\/licence professionnelle\\/diplome_bac\\/TblDnTFOT3PPYcRiyB0KAY8nLYeQYh4bLy7hsFRw.jpg\",\"Attestation de travail\":\"dossiers\\/2025\\/licence professionnelle\\/attestation_travail\\/dGC6YVy69dtFhSoJ1AI9hFRHlACXkLM8beu1qlir.jpg\",\"Quittance Rectorat de 2.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_rectorat\\/X4jWHcJH0Emw91mSgSqHepBxYuVw4gVBU7tORURM.jpg\",\"Quittance de 10.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_cap\\/CZbcppo7PQ6Y5xEIdqRrvrWpyKWp7bR3MEzv9UjX.jpg\"}', '1', 1, 'dossiers/2025/photos/zkWzwa554yJPfbWm7elrz7nRejm2H8NTo9mtNU1f.jpg', 2, 7, '2025-10-06 15:07:26', '2025-10-06 15:07:26'),
(256, 257, 'CAP-JwAlYtMLgQ', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/licence professionnelle\\/demande_da\\/yUco32sfmuxtFZzO9NJ24Y8ooZ86S9DGVb8BRp0c.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/licence professionnelle\\/cv\\/Xk6W07q7hLw1iTzbqfLlJRg4Gvqg1UKkWi7bUKOz.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/licence professionnelle\\/acte_naissance\\/T42h2ejDDgNqa2YoN3Md6Wrd8ZFIRMZj84qcRE18.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC ou \\u00e9quivalent\":\"dossiers\\/2025\\/licence professionnelle\\/diplome_bac\\/4MRSFzQHvfJZJ3hXQrpIiud1kiVRx8q1GkytF7RX.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/licence professionnelle\\/attestation_travail\\/MSm1dntPMsW8NzkNuMHlYleosqklseJYyXfUYNlX.pdf\",\"Quittance Rectorat de 2.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_rectorat\\/8R0offeT2XanqKJiXV4U9S7yPtsjOjrpOApNGWu5.pdf\",\"Quittance de 10.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_cap\\/TPtoQTBC81dknZyxuWIc5UPJhN7i1lniTzLXr1dM.pdf\"}', '1', 1, 'dossiers/2025/photos/Svhd8WI5UoK8LyWx3PmQPPIyzPwTk8TYlaERDkWa.jpg', 2, 3, '2025-10-07 08:22:40', '2025-10-07 08:22:40'),
(257, 258, 'CAP-ayIM7bC17C', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/licence professionnelle\\/demande_da\\/Q52Qs10ggDvehfkXW8QWePsu83wBpNETUTJ6EXhr.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/licence professionnelle\\/cv\\/QLYiTOrQ7mRX2tSsHarSReXdNx8GcfUKsGSCQmJY.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/licence professionnelle\\/acte_naissance\\/jnj4aw4MCJA9BwD0G3rwtvPgqDov9kpC6cX8M1nq.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC ou \\u00e9quivalent\":\"dossiers\\/2025\\/licence professionnelle\\/diplome_bac\\/lY3WRAPvzUliS4y5J0FSbGmmi3hjkPXyHne2F02p.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/licence professionnelle\\/attestation_travail\\/bHvWVpBAe5K1oTJb8i2q5WlIhF0blLl0WuF4SJRh.pdf\",\"Quittance Rectorat de 2.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_rectorat\\/jgh0gTaDDdlEB7GtjVlRUkdN5Y4dzCAtiwLR64xE.pdf\",\"Quittance de 10.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_cap\\/sOtATUAWZ9BEW879xgMJvQs2W7vYQZcIxvXjdzI4.pdf\"}', '1', 5, 'dossiers/2025/photos/yFL4lDuWJNS7R3aCCoNLjMIycmRfiLzca2AG5lJy.jpg', 2, 4, '2025-10-07 19:01:00', '2025-10-07 19:01:00'),
(258, 259, 'CAP-hRu4rWX90O', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/licence professionnelle\\/demande_da\\/OegmzEEO03dNsnyCQeBW8QfpA8h8l9IetbdRvmPE.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/licence professionnelle\\/cv\\/M4hZEsGojSGF8UYzxju0s0HhtBIo2jSNzDNsJ8sY.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/licence professionnelle\\/acte_naissance\\/aP70gbeUoRHwHdvlaqM6a7rMfd0aAkneTl97yfli.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC ou \\u00e9quivalent\":\"dossiers\\/2025\\/licence professionnelle\\/diplome_bac\\/bFse1qho9ChTIUsXCmqxm4jho8aB9YgqfJQL7PAw.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/licence professionnelle\\/attestation_travail\\/zOHINEqvRfbH3A5UCBgy47U6g2kv1aE8Zn3ZYHh9.pdf\",\"Quittance Rectorat de 2.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_rectorat\\/dYvZKlxBFwARTSjHCIHlcDz9X79aK3ah9lsQo1Pv.pdf\",\"Quittance de 10.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_cap\\/bByehvTz9v2evrbD8XakvMuaaCHGo7XHUe5TjsIB.pdf\"}', '1', 6, 'dossiers/2025/photos/3vp6SWBjhvlbX07WwXkaY8fB3Q7Qfln0JtlBJMGH.jpg', 2, 1, '2025-10-08 16:55:40', '2025-10-08 16:55:40'),
(259, 260, 'CAP-5b5b9jjRnF', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/licence professionnelle\\/demande_da\\/cpUEhQ88nNGwE4FuAK97g5l8Ab2AicFuBUhbdPHt.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/licence professionnelle\\/cv\\/IUU4ehwCxdTnSGq0yYBnTSzBLsRzCk4lsBVYv4Zc.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/licence professionnelle\\/acte_naissance\\/uLtUjqhHium4diMwP7VOUYKJaGVLINUumb7DG4aq.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC ou \\u00e9quivalent\":\"dossiers\\/2025\\/licence professionnelle\\/diplome_bac\\/Uxn0kbqBvndNKw1cLSSlblYnZbhsEv0t1LxAUoIt.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/licence professionnelle\\/attestation_travail\\/XNvl5sRh4KRzd1UGWcFD7WnVmv5SggsoAqvXis2J.pdf\",\"Quittance Rectorat de 2.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_rectorat\\/R3kuP8MWiE60ijFbqByXmISmaUE3oeD9HAchLuuk.pdf\",\"Quittance de 10.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_cap\\/jafYxl8hcfhRBnVCo1gadTYMv1sMwmLi0JDt81UH.pdf\"}', '1', 6, 'dossiers/2025/photos/pq6X5cxDNZByiol5fmfh9v7risXg6OYXypt7fG6J.jpg', 2, 3, '2025-10-09 11:05:47', '2025-10-09 11:05:47'),
(260, 261, 'CAP-8mUQyCqKIX', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/demande_da\\/1OTf8vOMZgrJJhwVJrhdnpmkMzx9Y9cFfG3kW8wY.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/cv\\/vKvrpX5cldwhJ1pRq8eKJhWkWPQ4b4UEdHLbAzS2.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/acte_naissance\\/eYXEo7nUCNaAbDyMTdVQ6ohbPSjJOF4Mor6xtUJ6.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_bac\\/s4M79Bzzjv3V4UJ1idGWerCfdEku14YVVlRCltCb.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me de la licence\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_licence\\/qZPrgm4gaOXvI54mqs8ojUuryITcqpdGPatTe8bK.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_travail\\/FMiuHbichyp0iRfcXIXvGCPF5K8IlDG6tbVA2LCq.pdf\",\"Quittance de 20.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_cap\\/bD0cp7LiPhxZSZ6DUwfCCwNFKYjihU4phgdOb3xG.pdf\"}', '1', 2, 'dossiers/2025/photos/ls4qOCE37eb2m51xlkPbAH6BJWykAohU9TE0BzKP.jpg', 2, 20, '2025-10-09 11:35:49', '2025-10-09 11:35:49'),
(261, 262, 'CAP-x4YxrEa3ar', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/licence professionnelle\\/demande_da\\/z8SdKFgSP6ldMgA3YcPAK7BONaKh2jzMuy5MRTAo.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/licence professionnelle\\/cv\\/zpDFz12AM0vSDGSC5FkewfrsmhNdwUiSWCCvmif0.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/licence professionnelle\\/acte_naissance\\/1CkX9Kfuyye9H8WtZJh2nkHQpYxlPyx1mG8yYvk8.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC ou \\u00e9quivalent\":\"dossiers\\/2025\\/licence professionnelle\\/diplome_bac\\/rArYbOPpOzndOUj5abPHpJfO3CddQ6KwmU0HjwTa.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/licence professionnelle\\/attestation_travail\\/8APOrybWy2r05gM8haAYbgvViWnLxS8bKH7j8ZXT.pdf\",\"Quittance Rectorat de 2.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_rectorat\\/sLY6C9w82ZLYNSKn388quug4jwQNF2ujIDOvUGFx.pdf\",\"Quittance de 10.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_cap\\/cx2EVV3Cv4zYQWNaqPZC2hI6Oea9I5sJzXvqagLk.pdf\"}', '1', 1, 'dossiers/2025/photos/sXuVmKuxAmppa060SFaU9CCpS2QppVBdVaB61JaV.jpg', 2, 2, '2025-10-09 14:42:28', '2025-10-09 14:42:28'),
(262, 263, 'CAP-T5jZmTg5hd', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/licence professionnelle\\/demande_da\\/HIcYUKppw10MER1oVXqJUOOCBDv8uwH2qbd0YeWO.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/licence professionnelle\\/cv\\/HeVmMeMNpwPcGUTQmpkvhkUdY5MATxQstSMmdQtW.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/licence professionnelle\\/acte_naissance\\/PkmvE7sUtUVhiOUkvzFBV8eFqrvUuQ50O4CEv5Hg.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC ou \\u00e9quivalent\":\"dossiers\\/2025\\/licence professionnelle\\/diplome_bac\\/HmkMIOr9IWkSypl2dksWcCEJRwgtn9YSurvWyua8.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/licence professionnelle\\/attestation_travail\\/T3voAzk0QbJWGWwZkcBWUlisQ60GXy4ea5BQyj4b.pdf\",\"Quittance Rectorat de 2.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_rectorat\\/TINgBSkCKmcSQcZb1yoHepZxtaZERYpCpkgIFFSi.pdf\",\"Quittance de 10.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_cap\\/7RSlkRSGrJQIqXbjCzvJxqpkoVtf1hK1389UjiQe.pdf\"}', '1', 1, 'dossiers/2025/photos/3Y2rdXChdEz2VWrvrepNYaT7plbYFrZ8jsi1K0vo.jpg', 2, 2, '2025-10-09 14:52:29', '2025-10-09 14:52:29'),
(263, 264, 'CAP-YACpPoMP0b', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/licence professionnelle\\/demande_da\\/EBNijxNc4yIUyde8U18SE1wCHYozrGTlwFSZa9n9.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/licence professionnelle\\/cv\\/exCLdfwqSCgwtFls5bI9vUHsp7tbBXrN4P3zXquv.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/licence professionnelle\\/acte_naissance\\/eLNogcsP2IB2mkdKOHsXIKvGpLdBN74u83KhySBc.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC ou \\u00e9quivalent\":\"dossiers\\/2025\\/licence professionnelle\\/diplome_bac\\/yHsA5uW2gyzpR1p0p3KOV5kKPwHoCfxiGLRpkXr6.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/licence professionnelle\\/attestation_travail\\/WLsCf9GPvN9sMWApqKGhsZ8WN8AKloYfBVBaGaA5.pdf\",\"Quittance Rectorat de 2.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_rectorat\\/IljeaRRMY4PU0AIo4jTRFShSeg8iBfNED6uInhjo.pdf\",\"Quittance de 10.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_cap\\/BgrozCJLUqDsPcDFvuGXOQL3tv3QmOcJoewaIer6.pdf\"}', '1', 6, 'dossiers/2025/photos/2LZJJm4YhIRx9AVyo62yAh4ZCsYvZNzWbcDJltWu.jpg', 2, 12, '2025-10-11 18:33:49', '2025-10-11 18:33:49'),
(264, 265, 'CAP-z0wF60wvtR', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/licence professionnelle\\/demande_da\\/gqryGpVd03dwglKw2WmCrTZz0mxGk7RMCLOreA1X.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/licence professionnelle\\/cv\\/Adpv45J8dR7LIBiuakJ9Dmll2c7D05U6ZYDGB5Nx.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/licence professionnelle\\/acte_naissance\\/iJuPyeSn5qZPjWaK0r8WwVsLdN0LPCodse4CkO7k.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC ou \\u00e9quivalent\":\"dossiers\\/2025\\/licence professionnelle\\/diplome_bac\\/r6xz51BJ1O0TVyVZkkjm7pDmqpk9de01wl74OYLQ.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/licence professionnelle\\/attestation_travail\\/jJFLwdqmL51HFtr03spS1m9BKqtKpipTmHg4hamm.pdf\",\"Quittance Rectorat de 2.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_rectorat\\/xFcMIJ7mvy4HZHuOF1LsJAm0Xuo07vyhtjNFfzq6.pdf\",\"Quittance de 10.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_cap\\/He56KxuAusKicgYCYeONSQsNGKq9tWEafDCaBzUx.pdf\"}', '1', 1, 'dossiers/2025/photos/9bi0lC65ZpiFeiphgdY6oCOlvqqPyTYPbfrjhwJ1.jpg', 2, 2, '2025-10-12 13:08:17', '2025-10-12 13:08:17'),
(265, 266, 'CAP-979cnHByqQ', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/demande_da\\/2LvHH2cqqntbwxR1zEbX7pDXGDpXhjMyqGmzRw53.jpg\",\"Curriculum Vitae\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/cv\\/pcfFV7yxCzF6ORhRMM9MX9f7iocPlKMyM2c01hYO.jpg\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/acte_naissance\\/C42gl5Ebjhk4z5pcRCp9GaYOzqXNNXXUER2tUGiH.jpg\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_bac\\/zTlgTlb23SkI8Qb3WFYBCDNmuNK6suYVYHIQ4P51.jpg\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me de la licence\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_licence\\/ZIJOx4kJQYM4MTeMpMtoWufimAsxTfojjeZR26Ay.jpg\",\"Attestation de travail\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_travail\\/7dKtS39GA2ojYyDAB1kACEjkoJJiIGQStV5r3Bwo.jpg\",\"Quittance de 20.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_cap\\/pSVYV8DdS5jsLvKr1rtbsZI2AdVLrGMa0R3UFKYW.jpg\",\"Attestation de d\\u00e9p\\u00f4t de dossier pour dipl\\u00f4mes \\u00e9trangers\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_depot_dossier\\/cYUJRygZr6huhmcIx695s3ezneiulb9atGy2JZxH.jpg\"}', '1', 2, 'dossiers/2025/photos/AKDiWXXJysLUQuqOQETWJ5l3OUpr2KSTVxlr6nrG.jpg', 2, 18, '2025-10-12 14:24:10', '2025-10-12 14:24:10'),
(266, 267, 'CAP-lzc98ggRli', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/licence professionnelle\\/demande_da\\/AR8K6xuko2HGlaT5j2rG1Nkz3jfGenugKJHLltuy.jpg\",\"Curriculum Vitae\":\"dossiers\\/2025\\/licence professionnelle\\/cv\\/pgA1iH5CfN9llgx8Txkljqom5N02SjAkHVSp6vFD.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/licence professionnelle\\/acte_naissance\\/ohSukaVF2NBuwk5C4f6p0AEbkxN1qJyGeTG4amfy.jpg\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC ou \\u00e9quivalent\":\"dossiers\\/2025\\/licence professionnelle\\/diplome_bac\\/2p61mhIv2gVcPO4LTmlb6LqHUEwE2JYJRVaRizjC.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/licence professionnelle\\/attestation_travail\\/EC2jIDQnRGFN0lzETH8nWi6a0GKY92VZweCfAheq.pdf\",\"Quittance Rectorat de 2.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_rectorat\\/4nDiAPgCvAkcD2IdVhZZyNOQNy060LIat0Pl3ohN.pdf\",\"Quittance de 10.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_cap\\/vaDapvOT8Ie78dDkanPwORDXFQ5NdOV0nLkqb6ii.pdf\"}', '1', 1, 'dossiers/2025/photos/kmRqf5BvZo6XgUBLA9nEyb7bHS4VeFMm3f83iClU.jpg', 2, 2, '2025-10-12 20:25:28', '2025-10-12 20:25:28'),
(267, 268, 'CAP-AROxip1SXj', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/licence professionnelle\\/demande_da\\/UF7r2gPMRt8SsB1gRnEkFKTYw0qXuRVrp7x1MEES.jpg\",\"Curriculum Vitae\":\"dossiers\\/2025\\/licence professionnelle\\/cv\\/HyL3dnWB5jjKI7ERC6MaHQEgSecEp1EeBzMrsZhW.jpg\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/licence professionnelle\\/acte_naissance\\/XOmcACh0RSpyoRbH0G4fpiUkRYhEIunMZ6AfLJan.jpg\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC ou \\u00e9quivalent\":\"dossiers\\/2025\\/licence professionnelle\\/diplome_bac\\/KrY2djXeOQbUQEcJzxxDZevBO6zgyZMfBxnkYm9T.jpg\",\"Attestation de travail\":\"dossiers\\/2025\\/licence professionnelle\\/attestation_travail\\/tVxChFzU0Zm9HCi3mhgx5etHT2x7upgUmrxLtjMP.jpg\",\"Quittance Rectorat de 2.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_rectorat\\/pyEOQVmLdHEO9zZKL7Sbx1fqMqB1foDb04VKRlJ0.jpg\",\"Quittance de 10.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_cap\\/3GvTNkwwu4dFZyAqQtGYj2ZIshRPo8AtfzxPVx69.jpg\"}', '1', 1, 'dossiers/2025/photos/uMTk107bZmW1cKpKYItwMpm777nPD6gReirjsxir.jpg', 2, 1, '2025-10-13 16:26:23', '2025-10-13 16:26:23'),
(268, 269, 'CAP-2DmIk8HkTL', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/demande_da\\/8VxYtml5wiYwHwm8YDOFV7ibDUSwHWEEgn9u5gV8.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/cv\\/RcRbqGeIBbijwWqKSDh5GBnirWHqyM2ExdedUUIj.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/acte_naissance\\/0b9pHJM38QJui0eEFOkSqUdwTgpO9kxz9ByzDbou.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_bac\\/588Sz1Yap3CfnuqO46iTbqrg6Vpgc0BSLXGjJDru.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me de la licence\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_licence\\/aGxACjfnsq0clwvFwc1DGDNtC5s0Xp0f4mfbkleD.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_travail\\/WW78lu2OenJsnCLWYeNB0XOS5RILApA1OrIlifWJ.pdf\",\"Quittance de 20.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_cap\\/3MsWXAP9l1AsdgusYiVbZFfgg2lt1rfELAaXPV54.pdf\"}', '1', 2, 'dossiers/2025/photos/jc1tAoAMz5s0fcpymSQGAJuQsmyu7GtVcLCeUoJa.jpg', 2, 19, '2025-10-14 14:46:59', '2025-10-14 14:46:59'),
(269, 270, 'CAP-sj1maOw1iA', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/licence professionnelle\\/demande_da\\/2EUopdJtpcNu3zZ4Mt9KWsXBFpMvubvudpsAw6Fm.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/licence professionnelle\\/cv\\/xgw0iqhNeZXlzSnm4pJPiusbzPB3rr3qnkLrmkCy.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/licence professionnelle\\/acte_naissance\\/7XFKkqVkMwCda61YmV0wGPIh5Bk7skvFINnmnpn0.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC ou \\u00e9quivalent\":\"dossiers\\/2025\\/licence professionnelle\\/diplome_bac\\/DY9in2EB6UIG6GPITMD9aBqSyGL2bdBBzQV7T5jP.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/licence professionnelle\\/attestation_travail\\/GDTtp4e2ePzymKOwCPNFc3JjFuVD41h1kBtt9g3C.pdf\",\"Quittance Rectorat de 2.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_rectorat\\/nAHdzeCiJHiIQiFfYmbOGYPOCNGxEmzMspNA5Lz9.pdf\",\"Quittance de 10.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_cap\\/Qv9QkLDEcbDQy5ST0780KNxf4JHalJtEPtCphBSI.jpg\"}', '3', 4, 'dossiers/2025/photos/BCVR9clLNJhgNXE7PL6oYJCnbhy66JnL0NDkCNrn.jpg', 2, 2, '2025-10-14 14:50:14', '2025-10-14 14:50:14'),
(270, 271, 'CAP-ZVLLbMGkQa', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/demande_da\\/QCC2dhUDsCuyg1VLwCc4BZcMqy6nXZIDsyeePNY8.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/cv\\/BamFGphLSbM87wsKZnj5vB3kP8qQFOLl3QCvzZJr.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/acte_naissance\\/HWbRT0Bkaq0ut76E9kXTyQxoAPNmDw0eeLO8e6k2.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_bac\\/gDulQmzzFNj1YK7OD3cEBvyFAthouYOODsWt21Qh.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me de la licence\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_licence\\/O68XtGpDBC1kToUHvnU4fpAshCaaEcMeQalNwAu3.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_travail\\/muIYEamApPmnAMFFFF93K5ycR5Pl8holDyHAYCUg.pdf\",\"Quittance de 20.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_cap\\/EGYOdcmcSejjRKJbY350Fh9Vqn2BxbKQTwwTV4KG.pdf\"}', '1', 2, 'dossiers/2025/photos/KekOoqu3wJEDrerIEMrNEAzG7SRthgd9mhy2H0oj.jpg', 2, 19, '2025-10-15 08:48:00', '2025-10-15 08:48:00'),
(271, 272, 'CAP-ZYG6d4fWnw', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/demande_da\\/Y7aafbNYyu38wmbaak0oXkvhqepqPLz29hk8Q3vm.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/cv\\/e9eM1nWEtk1Os99pyL7iabAT8lh4nbsmwwTdALEl.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/acte_naissance\\/ArowKKb6hAc77SStjeRlhcS3IMbxosXfhfnXYTQ7.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_bac\\/Lnlj2Ch3P4u4wvrdoDdnH0tFR8m3zU8rD8cULp9e.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me de la licence\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_licence\\/GmCv1d2nwDJVRoOtKIrrZKMe9uqQbhMB0Rmp4l7z.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_travail\\/vqdllwLgQrmFj90P3aMTHMS5GOOCp7j7PqHKFVF0.pdf\",\"Quittance de 20.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_cap\\/1793YhIbF3tpy0WJw4Qfb5xt81DGHn91nFbphvVe.pdf\"}', '1', 2, 'dossiers/2025/photos/4xkpqrn70mNXSuXfqGTvs9AjF0Tv5Ng1fUSTzyQG.jpg', 2, 18, '2025-10-15 09:07:27', '2025-10-15 09:07:27'),
(272, 273, 'CAP-TDMG88fjrf', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/demande_da\\/A6hIj4AfcKCBt6EalK08TjbRfNQhuPkRdbh44Np6.jpg\",\"Curriculum Vitae\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/cv\\/DAGLiqgghiStkFgEtHovoKqrGCa45ddHzlU9Q8Ss.jpg\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/acte_naissance\\/3oT3gDPzBDd7AzUd4DO8YOjv3X0oYL2J8kfEbCEP.jpg\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_bac\\/xYNzjpavE2lRGHmnimdlb63jTnAiM9gr5WLhSTLF.jpg\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me de la licence\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_licence\\/eraVk0DjjtWeRzbwaXm4rBg9DMfWqqK1nQl47VFH.jpg\",\"Attestation de travail\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_travail\\/TUx2G6EylOqr73byXrk1CXsSl4O63Nsn0Yiiz7ka.jpg\",\"Quittance de 20.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_cap\\/1SyW5AWgOj8sBQGBK8zWky4OS66n1WNKrF8E6AMr.jpg\"}', '1', 6, 'dossiers/2025/photos/oI9xY3Al2il7z6UeYIrdIEWldLK7KF8qhglroxDF.jpg', 2, 18, '2025-10-15 12:34:20', '2025-10-15 12:34:20'),
(273, 274, 'CAP-VeSVG975Ip', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/licence professionnelle\\/demande_da\\/57PTxdxeIpHOac5pcCksm5qmD0itAXZrTXEqwDGG.jpg\",\"Curriculum Vitae\":\"dossiers\\/2025\\/licence professionnelle\\/cv\\/bEqGrrwizQB9RxiewqnsVspoF15QvjN7N856lMhV.jpg\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/licence professionnelle\\/acte_naissance\\/UIDL1VehQZAE2YUtwwzTyKaMEPPemcWifHvILE2f.jpg\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC ou \\u00e9quivalent\":\"dossiers\\/2025\\/licence professionnelle\\/diplome_bac\\/BgCa92sTXfsm1vEc7afexZjOWiQRijdJxffbLvrs.jpg\",\"Attestation de travail\":\"dossiers\\/2025\\/licence professionnelle\\/attestation_travail\\/j9xxWix0RYHbiu6DGRwoPsmdg2KRgPepjUCNfUi7.jpg\",\"Quittance Rectorat de 2.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_rectorat\\/i8Xhd4oBNsW0Qgzwhgs3gRenkN0zthFkAIFu1IXU.jpg\",\"Quittance de 10.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_cap\\/NuvIr6ESlf8LvWMFGVQ8PEY2t7TcnqwrdF3tTEU8.jpg\"}', '1', 6, 'dossiers/2025/photos/8wBtzDip85lyf0oYHQYHcoiYMYoVNkmCr8XeZHuZ.jpg', 2, 6, '2025-10-16 10:29:50', '2025-10-16 10:29:50'),
(274, 275, 'CAP-v2qnYLWI5P', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/demande_da\\/KMeD32yACMm8SxFefpZ23EUgHgan2ei49pMjWD8x.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/cv\\/bNdfdpbzHiyn00I9ibxmLhP1kgd8i6jKrCreow26.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/acte_naissance\\/abqfH4smkNd2UctOArug5xFXizLiS1o4BAEeAMbc.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_bac\\/zPveLEkx8J7TjkMLj54koxNXBlcllGBbsdr7NCZv.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me de la licence\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_licence\\/3telvDBqUflGVxoPgTKQNoprQX6XECayJSM53RxW.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_travail\\/gIdteRTkmNX9CJ3d3XYpstI1daXGPpJDXmZzvwSC.pdf\",\"Quittance de 20.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_cap\\/IzlWGS59tB4YPBuCWtxu1w0MpYdjl6HGbOvrnvoM.pdf\"}', '1', 2, 'dossiers/2025/photos/v749xd2cfaEw0KDBArp9caoi1DUzH0os6muTeHw5.jpg', 2, 18, '2025-10-17 07:53:28', '2025-10-17 07:53:28'),
(275, 276, 'CAP-yS4dKubYbP', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/demande_da\\/0rD01SAvbnOzwTAG4UBS8o02LzkNENvWrXCYX52j.jpg\",\"Curriculum Vitae\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/cv\\/l6rdEOH4bapWdICMRjJimsKvIWYfr3wVDrjUIpAP.jpg\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/acte_naissance\\/KK1w1D4lY1YRmslKCZpWpnxUgfSLlMywGH5PCA3n.jpg\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_bac\\/7gNsJb65DjSYsR5ExRwCE98VvZXvuKR6ngQGs7Z7.jpg\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me de la licence\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_licence\\/RPZiSuE8NVdAyJyaTK1dcpzcZZ9C5X88PNaUZqmU.jpg\",\"Attestation de travail\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_travail\\/v1ojAQVtnrrTPHdFPYznPojhZ6rq5duuDXwrDR0x.jpg\",\"Quittance de 20.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_cap\\/ER6mln95KWUbc0elwiX872bHeodMpJhSjnXGFpB1.jpg\"}', '1', 2, 'dossiers/2025/photos/HEbBUyTi1HE9FrrWfwggSFQZ7Moqn4M9guoXModu.jpg', 2, 19, '2025-10-17 08:49:22', '2025-10-17 08:49:22'),
(276, 277, 'CAP-2Q19HJuecQ', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/demande_da\\/2IEP097cCxQlasVMFslJylrZqgXECcxnLRyC4DvI.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/cv\\/quzIT7N5sVhh9TZxm9MD3s5bs0dbTurOKbMlWwqf.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/acte_naissance\\/65EPxaDFLD06XD4JX3UiT1vMKwVBQjOnxmZMDCJM.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_bac\\/MYVDZ8VL4Dkpp7kDnujNEDtlOc9FIOrBDFRdezd7.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me de la licence\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_licence\\/GRCgHMg8T2CphOZvCiqnL76caFbgeZ4bvj852VwJ.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_travail\\/yQP2RQ2sLykXzEp7SXGNiuwGkeJoljeB0baBJD4d.pdf\",\"Quittance de 20.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_cap\\/tGIHxrsskZLEY3Sn2xcwIxJxRZEFGtyjTFOjXBTU.pdf\"}', '1', 2, 'dossiers/2025/photos/c7oOlZLMGmeeUUICzobnF64Wlm1MJPSPDb0MzvFY.jpg', 2, 23, '2025-10-17 11:17:38', '2025-10-17 11:17:38'),
(277, 278, 'CAP-OcZ17uKLOA', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/demande_da\\/IBRBqcMMypqAR8Fc9xflU0a0Kedu8bRVCuYxHZjd.jpg\",\"Curriculum Vitae\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/cv\\/JVanIh5ljXu9yHiW2FaHr6IvQlFDdbAS8xfOcLih.jpg\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/acte_naissance\\/nyqSDKA7CfI27Dxkcfs42LfhOHVP91QDJzAJch7g.jpg\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_bac\\/5tSp4vbtwy298uRtMzbLTXJ3TtsxOCxcPbUrgojO.jpg\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me de la licence\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_licence\\/EUjtnlQ7FLXKHCmTcouCRGkjwxOr3P1bHA7zXSeL.jpg\",\"Attestation de travail\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_travail\\/erln8iZWGGnUdZroxK1JGwdHoRmiZKQXKbu8KVzk.jpg\",\"Quittance de 20.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_cap\\/x29UihByOsHVjDG0WAjKhnRCzSz95u5ln6fxSAlV.jpg\"}', '1', 2, 'dossiers/2025/photos/8xsa2eZqM4eQFQ38H6wxdMaFD76xRMXKohw6Rn8I.jpg', 2, 18, '2025-10-17 17:11:16', '2025-10-17 17:11:16'),
(278, 279, 'CAP-vMUBma9xLo', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/demande_da\\/0BD7WGlBEIumeHn6iTx21teiacPsUlXOvrE7kL20.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/cv\\/e8rxIaHLfSprv6MFqpjrMUlyUdyPuvhODtKZT1c5.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/acte_naissance\\/BNdO85hqU0gns1YX1jwVDnqbqU53Gutk8l67ohTa.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_bac\\/HGoERvcM7NyjSSmTDsCjxJpMIdkpt6i7FIu31cU5.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me de la licence\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_licence\\/SBW7GMLqBII7oeH8AfLSv1uBKyEhZYeeFiTc2nX6.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_travail\\/BF9W3Ez3xSi4QxCRp9jgfG2zVlgvVV7o4xM2Z5y0.pdf\",\"Quittance de 20.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_cap\\/7CjFogzZ3hBlejoTJyThSiDJ8z638L1gA6dAmewv.pdf\"}', '1', 2, 'dossiers/2025/photos/PDxmIq6PiDHhPv3ebW4LdhDNDXagDjaDW4NYpxO9.jpg', 2, 20, '2025-10-18 15:20:39', '2025-10-18 15:20:39'),
(279, 280, 'CAP-hjlyB1jeMP', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/licence professionnelle\\/demande_da\\/VmvFlr7inaVXxKImWQ5A7ZaGSm21YMKcGZ1jrg4U.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/licence professionnelle\\/cv\\/BFUlfQ7JkH9zP97NnHFX0Bw7ph1A8s2pwfbYnLRW.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/licence professionnelle\\/acte_naissance\\/6ndipP3eIxi9GlzN1OyYQY6p559JY3G3JEqcSJH7.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC ou \\u00e9quivalent\":\"dossiers\\/2025\\/licence professionnelle\\/diplome_bac\\/lZ63rklHuNpXeJtpVxtsFd3WTP3Oo6tyys0t1AFQ.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/licence professionnelle\\/attestation_travail\\/DXyrLw5HBmTWG4KBoC1TPBBqAQGG3jLeCfrZ3E9T.pdf\",\"Quittance Rectorat de 2.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_rectorat\\/AKvZCUZuSHKMihHhnewpE6gBXasyYCGKrFfB3KVx.pdf\",\"Quittance de 10.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_cap\\/haGBF0L6QcUzDaLhPWMclKM9XGOpiejTBPPbtzc4.pdf\"}', '1', 1, 'dossiers/2025/photos/QFVIqS4iPHHKi7lkRRhglxsMgkvM4758AEEkOM0X.jpg', 2, 6, '2025-10-20 09:42:59', '2025-10-20 09:42:59'),
(280, 281, 'CAP-2RXWV4JBN1', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/licence professionnelle\\/demande_da\\/Ca8al2hBRcVo2nDVQAQYDIxsGdTaPjumJncpGpYR.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/licence professionnelle\\/cv\\/gKsblQ7Ipg3QHCGDIaF1yi3GzHqibgTHdtsF8USB.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/licence professionnelle\\/acte_naissance\\/4SAd7fPrr2ARYN4xA816pYh9zowhr9F59B3ImeKH.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC ou \\u00e9quivalent\":\"dossiers\\/2025\\/licence professionnelle\\/diplome_bac\\/GCaWesj1VVUfC74L0UZpUhvaqtAK9ceY56d5iNmy.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/licence professionnelle\\/attestation_travail\\/qpWedt6EzhxEwpKm5s8uXhkgSDyFyr1QKUoWLZ0y.pdf\",\"Quittance Rectorat de 2.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_rectorat\\/653fPc752aWX0WfttK7NYMr11yr3HWH3twXaqvSq.pdf\",\"Quittance de 10.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_cap\\/wZ9HYz2GLBwdyazVyBDojCvStASccFkEnXyTc9Un.pdf\"}', '1', 1, 'dossiers/2025/photos/3N9nUvuDAxaHnW9OJMhhz6lXIciyrGvopbB0DJE8.jpg', 2, 1, '2025-10-20 09:59:59', '2025-10-20 09:59:59'),
(281, 282, 'CAP-DERc2YbTOU', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/licence professionnelle\\/demande_da\\/KwCXNdS5nDkMsGvH6Ia9HKuLiE2qy9tCU7uoqKdO.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/licence professionnelle\\/cv\\/2AocytZNBD65l4SInovsiVrIlrBKotKFhbYYO3JY.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/licence professionnelle\\/acte_naissance\\/vIycNPnjGWCmYiNIP2yL326W5tCknKrn9L6VNx5P.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC ou \\u00e9quivalent\":\"dossiers\\/2025\\/licence professionnelle\\/diplome_bac\\/3RkgxJam8o8nKzJjx2gYSIcEWKY42vlErMrN7k6w.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/licence professionnelle\\/attestation_travail\\/k6BJ4rliWJTlR8vubGL2fBu6nJLsHdc9xEPN6zjN.pdf\",\"Quittance Rectorat de 2.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_rectorat\\/Gct5pUXr1WHVvOoEmYH73Xmd35NfAg9JvWeW1gfH.pdf\",\"Quittance de 10.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_cap\\/sKeOVmqfid1AjnbQlxMqg1ED6DK3dgVJ8OVGyAgs.pdf\"}', '1', 1, 'dossiers/2025/photos/P3Stg3wyetF34uN4EZ6vcBBYcmUVY9IZpmZ6PDjN.jpg', 2, 1, '2025-10-20 22:01:55', '2025-10-20 22:01:55'),
(282, 283, 'CAP-jARnCRQMJn', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/demande_da\\/eCHNSebcAfID4UXg1LtiB7Gd6HexF8q92nVJMIeU.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/cv\\/VAMCROfBQ4GEO96WpmgooqkZvk41dEhRx8pFvC78.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/acte_naissance\\/SbQDxl0IoZdyVfmg1Wf8ojg6diUt1xZTS6UbYeBP.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_bac\\/8m5CxjmM3hhF8QGavO3ZMJbB3H82S064fkpkKhTt.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me de la licence\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_licence\\/B6oMOSBCJKNzwagtKmCoyzflVwKGfbx5340q644x.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_travail\\/VcVFZ3mwJKjylMzU22ElCN1OqlhjaLBTXXlpmGtR.pdf\",\"Quittance de 20.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_cap\\/Mpy5lsnzwmd7mT1Cf2daWFB3kq35FBqdilhcbJDk.pdf\"}', '1', 2, 'dossiers/2025/photos/OyEup5Nrqcve1tDDDvvzHqHnqfi4WTLWF8NENSkd.jpg', 2, 18, '2025-10-21 15:27:22', '2025-10-21 15:27:22'),
(283, 284, 'CAP-k4yoCx8hM5', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/licence professionnelle\\/demande_da\\/01sDI8OhE2bajwlZfMzyNLe4hoNDtUnnttGTvyEo.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/licence professionnelle\\/cv\\/4LAJk2aAlzcIxnHgstvPaGbytpqrBjzTccZ4Rh0I.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/licence professionnelle\\/acte_naissance\\/91U0Jz8cO7on3m1ThVCdIgcz7TAZcIftax72tr1j.pdf\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC ou \\u00e9quivalent\":\"dossiers\\/2025\\/licence professionnelle\\/diplome_bac\\/BsCubE3F4QrtO3l3Y4PXmotjDrI2QLj3YHCZggrb.pdf\",\"Attestation de travail\":\"dossiers\\/2025\\/licence professionnelle\\/attestation_travail\\/mFDOGroeVSrLDlb4knP2iN87tyvNSfMziLwU2PhG.pdf\",\"Quittance Rectorat de 2.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_rectorat\\/aVGKvDxYQ1SyDtopEKlLRDTvWxXVk6GfCuuqeoqM.pdf\",\"Quittance de 10.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_cap\\/gDD40K3M86zy2vychNjDslEwkauUYdfsgogkAuWe.pdf\"}', '1', 5, 'dossiers/2025/photos/Ge7XI9snktHDup9sOYdJLBrdE3Lg3hdA21aycVwq.jpg', 2, 4, '2025-10-22 08:44:15', '2025-10-22 08:44:15'),
(284, 285, 'CAP-ChOoxb8lia', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/licence professionnelle\\/demande_da\\/9hYL3RRfK9pvePJwASdozcMwBkT6ekSPSG4gtJCF.pdf\",\"Curriculum Vitae\":\"dossiers\\/2025\\/licence professionnelle\\/cv\\/h0uVOYwJjvEZaPRMluKhhEiygTUMdLAOntYS2Miw.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/licence professionnelle\\/acte_naissance\\/oiZecdtgvLUKCduLqdr2dPlor0qL94brapGZ07N2.jpg\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC ou \\u00e9quivalent\":\"dossiers\\/2025\\/licence professionnelle\\/diplome_bac\\/fk7HBGsEVv5mhpTRKhxx3YbJlUJgHqnHPLQ6l2s0.jpg\",\"Attestation de travail\":\"dossiers\\/2025\\/licence professionnelle\\/attestation_travail\\/8WYATiHD1lhdDTQIUn6bK1RqBMhPqkBabHC3YkNz.jpg\",\"Quittance Rectorat de 2.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_rectorat\\/JnRY3QRsYrLaOVTpJyN88QDkn4hwLfntYqqgMq4W.jpg\",\"Quittance de 10.000F\":\"dossiers\\/2025\\/licence professionnelle\\/quittance_cap\\/KJoxCQx4Gev3R3PSyqqynQ1L8LzmOcmBw2iIxadM.jpg\"}', '1', 1, 'dossiers/2025/photos/c05fjEkfRGSPYRJWXqRTyhfHitSNG6sDkQGdUc3h.jpg', 2, 12, '2025-10-22 08:58:53', '2025-10-22 08:58:53'),
(285, 286, 'CAP-vr50Y8tdml', 'pending', NULL, NULL, NULL, 0, 0, '{\"Demande manuscrite adress\\u00e9e au D\\/EPAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/demande_da\\/JFkAa0PlhQRyPptP4mK9Zp64qloZu2MLLBxWAdeT.jpg\",\"Curriculum Vitae\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/cv\\/BNXXAdIogKwR64wtQADuJmiYABNhBcnsxzKMsVPZ.pdf\",\"Photocopie de l\\u2019extrait d\\u2019acte de naissance l\\u00e9galis\\u00e9 ou s\\u00e9curis\\u00e9\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/acte_naissance\\/jPpdtnftm387vPxzNOUF7NdSwTUHBgdVhbQT5sMQ.jpg\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me BAC\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_bac\\/qxpUaO6xcSNtyDvBqyNPowNue1By0FiRiRZHS6Mt.jpg\",\"Photocopie l\\u00e9galis\\u00e9e du dipl\\u00f4me de la licence\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/diplome_licence\\/VnK8A0rn83uJ4Q8ssCrakepsHJcEzdDOtk2uaaBf.jpg\",\"Attestation de travail\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/attestation_travail\\/PRVUFuUBpPAeTjikMsSql69LHeXaijY8JC0FHhuB.jpg\",\"Quittance de 20.000F\":\"dossiers\\/2025\\/ing\\u00e9nierie\\/quittance_cap\\/E3Zlc0noGAqhNX3TS57R5ZhVfKYWwZRa6WCxkkJW.jpg\"}', '1', 2, 'dossiers/2025/photos/YbGzF3s2KiyzMgsiiZItDqxQFnzQLYtyUllwByWZ.jpg', 2, 20, '2025-10-22 11:13:34', '2025-10-22 11:13:34');

-- --------------------------------------------------------

--
-- Table structure for table `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `personal_access_tokens`
--

INSERT INTO `personal_access_tokens` (`id`, `tokenable_type`, `tokenable_id`, `name`, `token`, `abilities`, `last_used_at`, `expires_at`, `created_at`, `updated_at`) VALUES
(1, 'App\\Models\\Administration', 1, 'portail-access', 'be2935eb219980b0e644377b569cfb73e3386cb1f63ef5515b87a53544ab360d', '[\"*\"]', NULL, '2025-06-22 13:34:32', '2025-06-20 13:34:32', '2025-06-20 13:34:32'),
(2, 'App\\Models\\Administration', 1, 'portail-access', 'fa1cb5678111696124d3fc820db8867043b9021b054de005cb14459b8d562204', '[\"*\"]', NULL, '2025-06-22 15:47:57', '2025-06-20 15:47:57', '2025-06-20 15:47:57'),
(3, 'App\\Models\\Administration', 1, 'portail-access', '89c35cb09f86fbed2912a1ee75ffa7561612b65afc294adba6247c7f9bb6e648', '[\"*\"]', NULL, '2025-06-23 14:35:14', '2025-06-21 14:35:14', '2025-06-21 14:35:14'),
(4, 'App\\Models\\Administration', 1, 'portail-access', 'd52009bc0dbcd8ee10f747612cedf6b1a4f816960519cada68f63c0df045e06f', '[\"*\"]', NULL, '2025-06-23 16:45:02', '2025-06-21 16:45:03', '2025-06-21 16:45:03'),
(5, 'App\\Models\\Administration', 1, 'portail-access', 'bd05ad4d5c2958bfc4d998996a0550d75d1baf65918df57db98eb3e27f841ac1', '[\"*\"]', NULL, '2025-06-23 16:51:42', '2025-06-21 16:51:42', '2025-06-21 16:51:42'),
(6, 'App\\Models\\Administration', 1, 'portail-access', '2074dbbf31c55cca4a8a00e1e7e770475082fcca329c692ea50527d8f76705ea', '[\"*\"]', NULL, '2025-06-25 09:38:29', '2025-06-23 09:38:29', '2025-06-23 09:38:29'),
(7, 'App\\Models\\Administration', 1, 'portail-access', '136fc8018f4ac1354f639c1dd15e009c4da9d9427d0693882608a53f03cfcaaa', '[\"*\"]', NULL, '2025-06-25 19:25:20', '2025-06-23 19:25:20', '2025-06-23 19:25:20'),
(8, 'App\\Models\\Administration', 1, 'portail-access', 'a52e83456323667590caa50d9fa673da8fb736e83501f4d4b5200135c34213b6', '[\"*\"]', NULL, '2025-06-25 21:23:35', '2025-06-23 21:23:35', '2025-06-23 21:23:35'),
(9, 'App\\Models\\Administration', 1, 'portail-access', 'b1297e243a8f76dde3c48157e0e8efdac18fe5439bae8dd3ea2fbd01d735bc62', '[\"*\"]', NULL, '2025-06-25 21:33:22', '2025-06-23 21:33:22', '2025-06-23 21:33:22'),
(10, 'App\\Models\\Administration', 1, 'portail-access', '3b97cf30dde9b93a8b99fc07228a0a4e182c1c62fa33aedeed64cd19fc62730d', '[\"*\"]', NULL, '2025-06-26 06:55:56', '2025-06-24 06:55:56', '2025-06-24 06:55:56'),
(11, 'App\\Models\\Administration', 1, 'portail-access', 'c3ba88a9bc6587ff425a138f6828bd71beaca3a56c59212b59cec31c9be2c8c7', '[\"*\"]', NULL, '2025-06-27 08:48:16', '2025-06-25 08:48:16', '2025-06-25 08:48:16'),
(12, 'App\\Models\\Administration', 4, 'portail-access', 'c3c9bad09c29ab66333174d8e1a53c288e65b778336f1579f8b299239f82cf4d', '[\"*\"]', NULL, '2025-06-27 20:55:41', '2025-06-25 20:55:41', '2025-06-25 20:55:41'),
(13, 'App\\Models\\Administration', 4, 'portail-access', 'ef007e039a2af4e8ba1161537b5d872815271bf8f1c00c3eee05db9046852006', '[\"*\"]', NULL, '2025-06-28 15:38:13', '2025-06-26 15:38:13', '2025-06-26 15:38:13'),
(14, 'App\\Models\\Administration', 4, 'portail-access', '34fdfd5f45e53570f50cf8b4a89f749df8676213c1c02a8d7a35383d9ba9dc0c', '[\"*\"]', NULL, '2025-06-29 09:33:12', '2025-06-27 09:33:12', '2025-06-27 09:33:12'),
(15, 'App\\Models\\Administration', 1, 'portail-access', '236f8a393c8cceeb4206f49734310fecb91da17c3652653efa9023ad554d5171', '[\"*\"]', NULL, '2025-07-03 10:48:56', '2025-07-01 10:48:56', '2025-07-01 10:48:56'),
(16, 'App\\Models\\Administration', 1, 'portail-access', '68791eccdbb2af3a938a15dca8e17418f22b04e8b15bfddab1e5b34bd428d324', '[\"*\"]', NULL, '2025-07-03 15:25:53', '2025-07-01 15:25:53', '2025-07-01 15:25:53'),
(17, 'App\\Models\\Administration', 1, 'portail-access', '9981824497f7be22761cb4a9e29c7cbea4e848e5e12e18544c30858e3e1bbf21', '[\"*\"]', NULL, '2025-07-03 16:33:45', '2025-07-01 16:33:45', '2025-07-01 16:33:45'),
(18, 'App\\Models\\Administration', 1, 'portail-access', 'e13e31d9443e47addab647d74f3832b5e42188fd3c2e39220757398150fdd8f1', '[\"*\"]', NULL, '2025-07-03 18:55:01', '2025-07-01 18:55:02', '2025-07-01 18:55:02'),
(19, 'App\\Models\\Administration', 1, 'portail-access', '17caf07df7769e76b0e7e638fc156997f3b8d0522a627ff8c68e162f404e297e', '[\"*\"]', NULL, '2025-07-07 16:51:00', '2025-07-05 16:51:00', '2025-07-05 16:51:00'),
(20, 'App\\Models\\Administration', 4, 'portail-access', 'c6a1c62f05b84d69f30a3c73847af7e1301d8030729afc1936673ce821018c4e', '[\"*\"]', NULL, '2025-07-10 16:59:04', '2025-07-08 16:59:04', '2025-07-08 16:59:04'),
(21, 'App\\Models\\Administration', 1, 'portail-access', '7f2d07963525b7724284e8d22fe26c68fd9f5373f8b126b43142eedb58db0c9e', '[\"*\"]', NULL, '2025-07-13 14:44:37', '2025-07-11 14:44:37', '2025-07-11 14:44:37'),
(22, 'App\\Models\\Administration', 1, 'portail-access', '116c294413add27b1272158097ea155ed29c014ca788c6c587f0af5e59ba0499', '[\"*\"]', NULL, '2025-07-17 15:38:13', '2025-07-15 15:38:13', '2025-07-15 15:38:13'),
(23, 'App\\Models\\Administration', 1, 'portail-access', 'a52d54295d83b48d773dd4a588a4fb9336375ae09ee86343d6cc9782d08d892d', '[\"*\"]', NULL, '2025-07-18 14:19:40', '2025-07-16 14:19:40', '2025-07-16 14:19:40'),
(24, 'App\\Models\\Administration', 1, 'portail-access', 'fca20afd62805733aea66520bb7f66d17f00144f501f0c6a5aaa61f5ce59c4c7', '[\"*\"]', NULL, '2025-07-18 17:43:25', '2025-07-16 17:43:25', '2025-07-16 17:43:25'),
(25, 'App\\Models\\Administration', 1, 'portail-access', '891ce6b8b5716c631f4b4dde554036d01fc6987acdbf7ef81af797aac3068d30', '[\"*\"]', NULL, '2025-07-19 14:15:48', '2025-07-17 14:15:48', '2025-07-17 14:15:48'),
(26, 'App\\Models\\Administration', 1, 'portail-access', '416fcd06c4ad2d7185d3c7f2c07627bbb9e7e5cc8aebb4b19c7fe2f5a63d8561', '[\"*\"]', NULL, '2025-07-19 14:15:50', '2025-07-17 14:15:50', '2025-07-17 14:15:50'),
(27, 'App\\Models\\Administration', 1, 'portail-access', 'c7c10b36da27382b688b4af4d3dbb29a5533097c8f79275144e540a3098d1b58', '[\"*\"]', NULL, '2025-07-19 16:22:31', '2025-07-17 16:22:31', '2025-07-17 16:22:31'),
(28, 'App\\Models\\Administration', 1, 'portail-access', '444222ab1faede447dd94a4b2c912660974590f82f9fd22918dede961bb308e8', '[\"*\"]', NULL, '2025-07-20 15:14:36', '2025-07-18 15:14:36', '2025-07-18 15:14:36'),
(29, 'App\\Models\\Administration', 1, 'portail-access', '372ce9719d1bc089f0f8cbe1b21aaf47e89dfce776073328a97fe5276785169f', '[\"*\"]', NULL, '2025-07-21 13:33:46', '2025-07-19 13:33:46', '2025-07-19 13:33:46'),
(30, 'App\\Models\\Administration', 1, 'portail-access', '61d1e046ded9813af3b1626488972ba11b30671e82bfccbfdc644d86c2305bb5', '[\"*\"]', NULL, '2025-07-23 11:48:22', '2025-07-21 11:48:22', '2025-07-21 11:48:22'),
(31, 'App\\Models\\Administration', 1, 'portail-access', '88837e40bd1eb0bfda24969968f5c9a938cbc306edd223f7b5b5c9aa637bb894', '[\"*\"]', NULL, '2025-07-23 17:10:41', '2025-07-21 17:10:41', '2025-07-21 17:10:41'),
(32, 'App\\Models\\Administration', 4, 'portail-access', '918b5153ef524c3802b10dbeba9b6ac9aef9e5e5e1c332fa697336712258b3b1', '[\"*\"]', NULL, '2025-07-24 07:50:51', '2025-07-22 07:50:51', '2025-07-22 07:50:51'),
(33, 'App\\Models\\Administration', 1, 'portail-access', 'd885bed871445fc346e4c4f070bc072f4373323a511bef8cccb4974f6aaf9f2b', '[\"*\"]', NULL, '2025-07-24 16:56:25', '2025-07-22 16:56:25', '2025-07-22 16:56:25'),
(34, 'App\\Models\\Administration', 1, 'portail-access', 'e9fe8baeeef43ab37e158aaf8a98416db9db888f137c0ca6629f9b01d169d16e', '[\"*\"]', NULL, '2025-07-30 12:31:20', '2025-07-28 12:31:20', '2025-07-28 12:31:20'),
(35, 'App\\Models\\Administration', 1, 'portail-access', 'ca79680816f67cb917734496f7efd70f0f3468ab65ced472f7704f38767fa121', '[\"*\"]', NULL, '2025-07-31 12:46:03', '2025-07-29 12:46:03', '2025-07-29 12:46:03'),
(36, 'App\\Models\\Administration', 1, 'portail-access', 'd35f10ffdca0ce8edb43f04912988eeafe8837f0cb8150e8a489a26ab3249b33', '[\"*\"]', NULL, '2025-07-31 13:57:37', '2025-07-29 13:57:37', '2025-07-29 13:57:37'),
(37, 'App\\Models\\Administration', 1, 'portail-access', '2b286e6cf6349fc71b135871c90560e4937a27b45d970fa860efbf8452e833c1', '[\"*\"]', NULL, '2025-08-01 12:48:10', '2025-07-30 12:48:10', '2025-07-30 12:48:10'),
(38, 'App\\Models\\Administration', 1, 'portail-access', '3f5266c53acd647b3779bf8c81c81edd306f56e4c5297a9da25f02d67caeacd1', '[\"*\"]', NULL, '2025-08-02 18:01:53', '2025-07-31 18:01:53', '2025-07-31 18:01:53'),
(39, 'App\\Models\\Administration', 1, 'portail-access', '563c7ee8931489ec1c0d7582d478d07a25fe821e2498e80dba5f2cc8835d9c86', '[\"*\"]', NULL, '2025-08-02 18:08:18', '2025-07-31 18:08:18', '2025-07-31 18:08:18'),
(40, 'App\\Models\\Administration', 1, 'portail-access', '3949e61e6cbe4bdb4278bb4e23abf030abe4c0b4b3c1f83cd93e510f894df5c8', '[\"*\"]', NULL, '2025-08-03 16:45:50', '2025-08-01 16:45:50', '2025-08-01 16:45:50'),
(41, 'App\\Models\\Administration', 1, 'portail-access', 'ca71d2c4138edd7490f01ad4219b67384b584bec881e34846fc78834668e15a7', '[\"*\"]', NULL, '2025-08-05 19:02:57', '2025-08-03 19:02:57', '2025-08-03 19:02:57'),
(42, 'App\\Models\\Administration', 1, 'portail-access', '4dea966e8d83b65e6779ba2a66683a4460ed0bfae3b16aa37e459692bb7a2577', '[\"*\"]', NULL, '2025-08-06 19:50:10', '2025-08-04 19:50:10', '2025-08-04 19:50:10'),
(43, 'App\\Models\\Administration', 1, 'portail-access', '29d71b6bf78e5b4f94d744735dd0d34b16bd85df8649852d9af0eb51265477ec', '[\"*\"]', NULL, '2025-08-07 05:46:06', '2025-08-05 05:46:06', '2025-08-05 05:46:06'),
(44, 'App\\Models\\Administration', 1, 'portail-access', 'da18551c7f45ea2a30850ecf131833f362c5b0af673a9684ffba60d9cc7b450b', '[\"*\"]', NULL, '2025-08-07 13:00:39', '2025-08-05 13:00:39', '2025-08-05 13:00:39'),
(45, 'App\\Models\\Administration', 1, 'portail-access', 'a77f749e33c3e9166cc2a068dfc929b83e9ec48d77ccea9587f077a7c7d72b81', '[\"*\"]', NULL, '2025-08-08 10:28:05', '2025-08-06 10:28:05', '2025-08-06 10:28:05'),
(46, 'App\\Models\\Administration', 1, 'portail-access', '7ea9d16844821cd9efbb00757a122dcc966629e1ed5f73b537a5fe8224540a29', '[\"*\"]', NULL, '2025-08-09 10:56:01', '2025-08-07 10:56:01', '2025-08-07 10:56:01'),
(47, 'App\\Models\\Administration', 1, 'portail-access', 'a25e9abb4037dd62a314b0bb53b2f59e03c2cacb294514d4131b1b09725a56f1', '[\"*\"]', NULL, '2025-08-13 05:27:17', '2025-08-11 05:27:17', '2025-08-11 05:27:17'),
(48, 'App\\Models\\Administration', 1, 'portail-access', 'a74801fbb1e745b3bbaa4fe6421b9bba30b6c83616aa2f1c87ec16b393adafa9', '[\"*\"]', NULL, '2025-08-13 12:56:14', '2025-08-11 12:56:14', '2025-08-11 12:56:14'),
(49, 'App\\Models\\Administration', 1, 'portail-access', 'a107423893b4f20df71332fa572b530338f72f344ff23ae9590670219f7c6a0e', '[\"*\"]', NULL, '2025-08-14 16:40:43', '2025-08-12 16:40:43', '2025-08-12 16:40:43'),
(50, 'App\\Models\\Administration', 1, 'portail-access', '7d20cbb187dcb184e8cf484faed138381d2b4f1a05887a5e986116a46e3ee0c3', '[\"*\"]', NULL, '2025-08-14 16:43:52', '2025-08-12 16:43:52', '2025-08-12 16:43:52'),
(51, 'App\\Models\\Administration', 1, 'portail-access', 'd4640fdd3c039ca6136a94a6e520c77d1b96158768c6b586950bec91b9e4f30e', '[\"*\"]', NULL, '2025-08-15 10:44:58', '2025-08-13 10:44:58', '2025-08-13 10:44:58'),
(52, 'App\\Models\\Administration', 1, 'portail-access', 'f13e2a2b68f83e0f931e6565ac4846ee9899ec5f1d98a1d8dbac1872120d6595', '[\"*\"]', NULL, '2025-08-15 15:58:24', '2025-08-13 15:58:24', '2025-08-13 15:58:24'),
(53, 'App\\Models\\Administration', 1, 'portail-access', '86045df72ff0ba68293299aa35c258e9e65f1b033bb38e78dd49ca44496a1606', '[\"*\"]', NULL, '2025-08-15 18:52:19', '2025-08-13 18:52:19', '2025-08-13 18:52:19'),
(54, 'App\\Models\\Administration', 1, 'portail-access', '80cf6bea536a178c31c6257a1758d11b711134bd9a4514e8bd931c7449b8e73a', '[\"*\"]', NULL, '2025-08-16 17:18:23', '2025-08-14 17:18:23', '2025-08-14 17:18:23'),
(55, 'App\\Models\\Administration', 1, 'portail-access', '49ad69c76a557b042dbc7b2a987faeecb861347e9ff4ef3a9bfa085d9b939838', '[\"*\"]', NULL, '2025-08-20 06:48:21', '2025-08-18 06:48:21', '2025-08-18 06:48:21'),
(56, 'App\\Models\\Administration', 1, 'portail-access', '7f03c29ba9b4f254063fee18065a12ec2985505326e193b8c82ecc809826dbe4', '[\"*\"]', NULL, '2025-08-20 17:54:37', '2025-08-18 17:54:37', '2025-08-18 17:54:37'),
(57, 'App\\Models\\Administration', 1, 'portail-access', 'b09ad58dfe06a321c387402b9fb6c5dd543cfe8efdea15c33af334db856f6433', '[\"*\"]', NULL, '2025-08-21 15:48:56', '2025-08-19 15:48:56', '2025-08-19 15:48:56'),
(58, 'App\\Models\\Administration', 1, 'portail-access', '96e5271af0873cf0f0617fe9d65a3828670708bf87cf878c860342110c13047d', '[\"*\"]', NULL, '2025-08-22 13:15:59', '2025-08-20 13:15:59', '2025-08-20 13:15:59'),
(59, 'App\\Models\\Administration', 1, 'portail-access', '01ceecc90a5180fc1708b945e4158dfa8139fe68cc47ee88144fa0d9c69f8a5d', '[\"*\"]', NULL, '2025-08-22 14:08:49', '2025-08-20 14:08:49', '2025-08-20 14:08:49'),
(60, 'App\\Models\\Administration', 1, 'portail-access', '6c8346ad759e660a44987c0ec80bf109fb4da52c88b0cf149e4aaebe39ef7e0d', '[\"*\"]', NULL, '2025-08-23 15:14:13', '2025-08-21 15:14:13', '2025-08-21 15:14:13'),
(61, 'App\\Models\\Administration', 1, 'portail-access', 'c4aeef2ff3d6b3dd6927b0d0eb9b34a414a5d8bdc1a21fe088437366a4888c0e', '[\"*\"]', NULL, '2025-08-23 16:24:00', '2025-08-21 16:24:00', '2025-08-21 16:24:00'),
(62, 'App\\Models\\Administration', 1, 'portail-access', 'f3ac6271b1234c38cd294223cab8319fe173ff1f1ddf2eb563b18038a411a321', '[\"*\"]', NULL, '2025-08-24 10:27:42', '2025-08-22 10:27:42', '2025-08-22 10:27:42'),
(63, 'App\\Models\\Administration', 1, 'portail-access', 'c61c5aaf3a8a21ba368fe2bb2024307b3764791e7ebdee96358a9baa332ee765', '[\"*\"]', NULL, '2025-08-24 10:34:55', '2025-08-22 10:34:55', '2025-08-22 10:34:55'),
(64, 'App\\Models\\Administration', 1, 'portail-access', 'd1cc7654bcf9c6e1f94ce9260f721c407f9a3a8d4cd8122dfa1f47f0d21094ba', '[\"*\"]', NULL, '2025-08-24 10:40:57', '2025-08-22 10:40:57', '2025-08-22 10:40:57'),
(65, 'App\\Models\\Administration', 1, 'portail-access', '34559b2d1230f08727c62b0f65be1c60d771b40095c3d6665dece795adac4925', '[\"*\"]', NULL, '2025-08-24 12:34:32', '2025-08-22 12:34:32', '2025-08-22 12:34:32'),
(66, 'App\\Models\\Administration', 1, 'portail-access', '0aea715adce2448f8c414cb19f2aaf0109929df1ef83435c8af89eee5aa85514', '[\"*\"]', NULL, '2025-08-24 13:15:35', '2025-08-22 13:15:35', '2025-08-22 13:15:35'),
(67, 'App\\Models\\Administration', 1, 'portail-access', 'dcb8b1730a0845ebe79fa9ee5215b64b9ac93c85553589537cfd86731bd8a090', '[\"*\"]', NULL, '2025-08-25 08:55:09', '2025-08-23 08:55:09', '2025-08-23 08:55:09'),
(68, 'App\\Models\\Administration', 1, 'portail-access', '90f10c93dc20f6d1cc7a74be1382d38c27bb29080c158f836de5cb710b73403c', '[\"*\"]', NULL, '2025-08-27 06:00:38', '2025-08-25 06:00:38', '2025-08-25 06:00:38'),
(69, 'App\\Models\\Administration', 1, 'portail-access', 'fd9926090ebcf7426246199bd15764a368b6041e7ec8da275ea8359d575ca816', '[\"*\"]', NULL, '2025-08-27 08:04:39', '2025-08-25 08:04:39', '2025-08-25 08:04:39'),
(70, 'App\\Models\\Administration', 1, 'portail-access', 'd0298533c4bdcda83817b5491261de074e24538e2c46f6f27ebe26ae75f4b0d6', '[\"*\"]', NULL, '2025-08-27 08:10:08', '2025-08-25 08:10:08', '2025-08-25 08:10:08'),
(71, 'App\\Models\\Administration', 4, 'portail-access', 'c9b2b2ef62a0da5087460713366a7aa2b12a521cabf785c9c85ce6be43d13a98', '[\"*\"]', NULL, '2025-08-27 09:00:54', '2025-08-25 09:00:54', '2025-08-25 09:00:54'),
(72, 'App\\Models\\Administration', 1, 'portail-access', '301f1f15ac27f5270fdf13d9c79ec4ff7121b2b13ef441d9438cfeeda8368901', '[\"*\"]', NULL, '2025-08-27 09:49:18', '2025-08-25 09:49:18', '2025-08-25 09:49:18'),
(73, 'App\\Models\\Administration', 1, 'portail-access', '5f78960fcfbda9f70a4858e96fc04c0410d294166238cfcc0911c080b5fcd8ad', '[\"*\"]', NULL, '2025-08-27 12:22:09', '2025-08-25 12:22:09', '2025-08-25 12:22:09'),
(74, 'App\\Models\\Administration', 1, 'portail-access', '3ddd0dbb53ce569efac7dc0da8a635376dec31f8d045dcee297050e4532390c3', '[\"*\"]', NULL, '2025-08-28 07:57:21', '2025-08-26 07:57:21', '2025-08-26 07:57:21'),
(75, 'App\\Models\\Administration', 1, 'portail-access', '6510f1d1824a559683cbc418c41d38e43d0815d7e7e658ba7761af5000c13cc6', '[\"*\"]', NULL, '2025-08-29 11:00:43', '2025-08-27 11:00:43', '2025-08-27 11:00:43'),
(76, 'App\\Models\\Administration', 1, 'portail-access', '6dd66dd1a0bca3d370f943008a0b5a28b122f5e069a9514fae12ba1fd658f28b', '[\"*\"]', NULL, '2025-08-29 12:11:08', '2025-08-27 12:11:08', '2025-08-27 12:11:08'),
(77, 'App\\Models\\Administration', 1, 'portail-access', 'b4e5ba005f5209ff9b666ef9aadb735320a6297d3ad48c5aa69c4df95942ab91', '[\"*\"]', NULL, '2025-08-29 17:39:41', '2025-08-27 17:39:41', '2025-08-27 17:39:41'),
(78, 'App\\Models\\Administration', 1, 'portail-access', '92a3496da62f02d476fb5eade268a94e980c9b48eb689d1890ef3e21f67e0b52', '[\"*\"]', NULL, '2025-08-29 17:39:42', '2025-08-27 17:39:42', '2025-08-27 17:39:42'),
(79, 'App\\Models\\Administration', 1, 'portail-access', '26ba725b901a7c910f977ff1e9e6ce326ca9928b721a0f275a987516b8137eb7', '[\"*\"]', NULL, '2025-08-29 19:25:37', '2025-08-27 19:25:37', '2025-08-27 19:25:37'),
(80, 'App\\Models\\Administration', 1, 'portail-access', '560b6beb3491f40734d299f11c381cf9cc30fb2e3980e36212d741830103c5e9', '[\"*\"]', NULL, '2025-08-29 22:09:58', '2025-08-27 22:09:58', '2025-08-27 22:09:58'),
(81, 'App\\Models\\Administration', 1, 'portail-access', '17114efeb9efafe1d9815b170533fa55cdc613373cc8bb782400d80e86ba63f6', '[\"*\"]', NULL, '2025-08-30 08:20:12', '2025-08-28 08:20:12', '2025-08-28 08:20:12'),
(82, 'App\\Models\\Administration', 1, 'portail-access', 'e800758cf128898e9662e230adfe1740fa7babf9d0c8d75a545ebaf71b2ba7f3', '[\"*\"]', NULL, '2025-08-30 08:24:26', '2025-08-28 08:24:26', '2025-08-28 08:24:26'),
(83, 'App\\Models\\Administration', 1, 'portail-access', '8c235b09d5f41563bbd4ae4f7b12896335969b85e0953804772cb12f7982a7e3', '[\"*\"]', NULL, '2025-08-30 09:19:07', '2025-08-28 09:19:07', '2025-08-28 09:19:07'),
(84, 'App\\Models\\Administration', 1, 'portail-access', '98afdde5ddad5e32c5d33486890632083d0d529e19957a17dc5b266f446a48fb', '[\"*\"]', NULL, '2025-08-30 09:20:06', '2025-08-28 09:20:06', '2025-08-28 09:20:06'),
(85, 'App\\Models\\Administration', 1, 'portail-access', '5a0fc868f1a6961e99bdc68d9586bcfcd0c46a597f6352e32431ef9e723d7fe5', '[\"*\"]', NULL, '2025-08-30 10:05:45', '2025-08-28 10:05:45', '2025-08-28 10:05:45'),
(86, 'App\\Models\\Administration', 1, 'portail-access', 'cb0f07373050c9a9d8b99c976ddaeb752a9e01e78cfbc22fd633b51981da2593', '[\"*\"]', NULL, '2025-08-30 11:00:05', '2025-08-28 11:00:05', '2025-08-28 11:00:05'),
(87, 'App\\Models\\Administration', 1, 'portail-access', '5868fdb8850086e80e1fdfa681f95ad970e27b36555aec518bf82fe748b024da', '[\"*\"]', NULL, '2025-08-30 15:27:26', '2025-08-28 15:27:26', '2025-08-28 15:27:26'),
(88, 'App\\Models\\Administration', 1, 'portail-access', '11373bc75a464f9fbd16adffb1f2a73c20f036b4538c49433e533c510a4017b2', '[\"*\"]', NULL, '2025-08-30 15:48:29', '2025-08-28 15:48:29', '2025-08-28 15:48:29'),
(89, 'App\\Models\\Administration', 1, 'portail-access', 'dae4b755decc9cf5907690ce2e50988a0d1615ef38e56f0af03d04d8aa0b907d', '[\"*\"]', NULL, '2025-08-30 16:21:07', '2025-08-28 16:21:07', '2025-08-28 16:21:07'),
(90, 'App\\Models\\Administration', 1, 'portail-access', '735b38a953a09ae4c2656b8ba8276b9b4d6fe30dccb74766dc28fef47ef702bc', '[\"*\"]', NULL, '2025-08-30 18:41:21', '2025-08-28 18:41:21', '2025-08-28 18:41:21'),
(91, 'App\\Models\\Administration', 1, 'portail-access', '1ab97d3619c6e76337e968f4288411b1f2cc25a2906c67f66d619f603e7a55df', '[\"*\"]', NULL, '2025-08-31 08:30:26', '2025-08-29 08:30:26', '2025-08-29 08:30:26'),
(92, 'App\\Models\\Administration', 1, 'portail-access', '11eaff5b6b27f30e21e912ab7116207e5634c31b0377831e02be66c4f65b45d9', '[\"*\"]', NULL, '2025-08-31 11:37:02', '2025-08-29 11:37:02', '2025-08-29 11:37:02'),
(93, 'App\\Models\\Administration', 1, 'portail-access', '4fd994560c6c26eac5450292a4a9f6d9b6e4d23ba88d13104c58ebc11f59e980', '[\"*\"]', NULL, '2025-08-31 13:57:57', '2025-08-29 13:57:57', '2025-08-29 13:57:57'),
(94, 'App\\Models\\Administration', 1, 'portail-access', '7e77997be4b46f1ea2de82d6f60d6da566eeb09f8b1998ec453a705f8ed789a2', '[\"*\"]', NULL, '2025-08-31 14:48:18', '2025-08-29 14:48:18', '2025-08-29 14:48:18'),
(95, 'App\\Models\\Administration', 1, 'portail-access', 'f69c25652f8723c1036f905cee6194e4894ee93752aef7093c9c75099ce7b48a', '[\"*\"]', NULL, '2025-09-01 14:57:30', '2025-08-30 14:57:30', '2025-08-30 14:57:30'),
(96, 'App\\Models\\Administration', 1, 'portail-access', '4ab97dffc8cf86592902dd774ab8538c9e085b13f404711342685b8e01b44839', '[\"*\"]', NULL, '2025-09-01 18:53:51', '2025-08-30 18:53:51', '2025-08-30 18:53:51'),
(97, 'App\\Models\\Administration', 1, 'portail-access', 'baa6049ca11e524a1a96e58de2e31aaf30823fa813b406eae9208bf8a6459324', '[\"*\"]', NULL, '2025-09-02 18:49:56', '2025-08-31 18:49:56', '2025-08-31 18:49:56'),
(98, 'App\\Models\\Administration', 1, 'portail-access', 'd895a295ec4670edd82e2154c2f16fb3ac3beaea570a8fba20c4e7b21e91c5c1', '[\"*\"]', NULL, '2025-09-03 08:46:18', '2025-09-01 08:46:18', '2025-09-01 08:46:18'),
(99, 'App\\Models\\Administration', 1, 'portail-access', '704fe2a3c10a60b41ad88e5a7a70b8b33e572eb94349a1709e51891b70ed157c', '[\"*\"]', NULL, '2025-09-03 08:56:26', '2025-09-01 08:56:26', '2025-09-01 08:56:26'),
(100, 'App\\Models\\Administration', 1, 'portail-access', '4bac66866cccf1d7cc6d8bdba3da82c283b6adf64f26e7697712c4276039b361', '[\"*\"]', NULL, '2025-09-03 09:24:08', '2025-09-01 09:24:08', '2025-09-01 09:24:08'),
(101, 'App\\Models\\Administration', 1, 'portail-access', '2736df07c643cae10e3200efbf258ca0867dd6c5c880c7bbb179592ad2eec0c4', '[\"*\"]', NULL, '2025-09-03 09:31:52', '2025-09-01 09:31:52', '2025-09-01 09:31:52'),
(102, 'App\\Models\\Administration', 1, 'portail-access', '67c685b6b6ec19bf78013e1bf651ec896bb4e5fe46840aee2869a822c5cc6a80', '[\"*\"]', NULL, '2025-09-03 11:35:32', '2025-09-01 11:35:32', '2025-09-01 11:35:32'),
(103, 'App\\Models\\Administration', 1, 'portail-access', '14b7a1c2b11e6e6c201e4549ea7a0bb60b9fbde362160ca4ca42b7b664247ff4', '[\"*\"]', NULL, '2025-09-03 11:44:39', '2025-09-01 11:44:39', '2025-09-01 11:44:39'),
(104, 'App\\Models\\Administration', 1, 'portail-access', '8bef8d9a5450d25492c1bcbf7c70c31e5f8e8f41d324f44df6314b53bdb16727', '[\"*\"]', NULL, '2025-09-03 13:47:06', '2025-09-01 13:47:06', '2025-09-01 13:47:06'),
(105, 'App\\Models\\Administration', 1, 'portail-access', 'bebc08788cf5087a2a6df05e18e53a475816ef9583703b726dfe0c32e82b26bc', '[\"*\"]', NULL, '2025-09-03 13:47:07', '2025-09-01 13:47:07', '2025-09-01 13:47:07'),
(106, 'App\\Models\\Administration', 1, 'portail-access', 'a0909269257553a0877d38e432b3ece5a8477dddcc0ce145111bfd7630c24766', '[\"*\"]', NULL, '2025-09-03 15:48:30', '2025-09-01 15:48:30', '2025-09-01 15:48:30'),
(107, 'App\\Models\\Administration', 1, 'portail-access', 'e9318f8c7d213015f642164f4afcfc9ae266a36e36969bd2a86cbe678d8c5bae', '[\"*\"]', NULL, '2025-09-04 13:42:22', '2025-09-02 13:42:22', '2025-09-02 13:42:22'),
(108, 'App\\Models\\Administration', 1, 'portail-access', '69c2aed3c452692c312ae6717a0aacdfbdb04a4558ef635efb7c173c5939327a', '[\"*\"]', NULL, '2025-09-05 14:37:36', '2025-09-03 14:37:36', '2025-09-03 14:37:36'),
(109, 'App\\Models\\Administration', 1, 'portail-access', '8f9bedb680664dd3ebe709870bec6e8185645fdb7d0555c1cee17897b1b07695', '[\"*\"]', NULL, '2025-09-05 18:00:45', '2025-09-03 18:00:45', '2025-09-03 18:00:45'),
(110, 'App\\Models\\Administration', 1, 'portail-access', 'eaf0a0935ec86617e97e46abf8978d2118c8a535de7047f97b2f29d2867f4623', '[\"*\"]', NULL, '2025-09-05 18:23:56', '2025-09-03 18:23:56', '2025-09-03 18:23:56'),
(111, 'App\\Models\\Administration', 1, 'portail-access', 'bcf8279f35b330bfa1afffe74c60dc16a106aa51b0ec6151be871399a1add6ed', '[\"*\"]', NULL, '2025-09-06 13:56:02', '2025-09-04 13:56:02', '2025-09-04 13:56:02'),
(112, 'App\\Models\\Administration', 1, 'portail-access', 'c9bb45c377ba3b4f1a038c9d4105a8c7cbdc5f32e3881f931b983ea261ca3b93', '[\"*\"]', NULL, '2025-09-07 09:13:10', '2025-09-05 09:13:10', '2025-09-05 09:13:10'),
(113, 'App\\Models\\Administration', 1, 'portail-access', '9837e95284664c00ab295f9f86b22f5a0412880ba71dd7ee13129ea5f9a999e1', '[\"*\"]', NULL, '2025-09-07 12:39:41', '2025-09-05 12:39:41', '2025-09-05 12:39:41'),
(114, 'App\\Models\\Administration', 1, 'portail-access', '0e38d531404da195ef18903bb3cb7e11677e44f98420a467991ed5b2e4590176', '[\"*\"]', NULL, '2025-09-10 09:05:45', '2025-09-08 09:05:45', '2025-09-08 09:05:45'),
(115, 'App\\Models\\Administration', 1, 'portail-access', '77857fd2412eeda8165de446c3a28a10555d8d75ad07ceadc11e68a23a4358c7', '[\"*\"]', NULL, '2025-09-10 15:01:52', '2025-09-08 15:01:52', '2025-09-08 15:01:52'),
(116, 'App\\Models\\Administration', 1, 'portail-access', 'e5dd28bed477d022f211ffe8e51134eeba6d85083314496a26a02c37f9be1aa8', '[\"*\"]', NULL, '2025-09-11 10:25:52', '2025-09-09 10:25:52', '2025-09-09 10:25:52'),
(117, 'App\\Models\\Administration', 1, 'portail-access', '66827bf947836d8a5da80c2ccc01f44ea942ac6162c665145e3cae492794ddd1', '[\"*\"]', NULL, '2025-09-11 11:40:45', '2025-09-09 11:40:45', '2025-09-09 11:40:45'),
(118, 'App\\Models\\Administration', 1, 'portail-access', 'f8d1448adc62d53dc31cacacc47bdeb6ca82195a7ade8e192803d2ef66268968', '[\"*\"]', NULL, '2025-09-12 08:35:46', '2025-09-10 08:35:46', '2025-09-10 08:35:46'),
(119, 'App\\Models\\Administration', 1, 'portail-access', 'aed99268d1ba82abc0e1ce87656833b27de7aa586191c3d8d22a6e7be176df3a', '[\"*\"]', NULL, '2025-09-12 12:17:16', '2025-09-10 12:17:16', '2025-09-10 12:17:16'),
(120, 'App\\Models\\Administration', 2, 'portail-access', '9ffd66bc7ec571de879ff88626213e0fd00638256c75aa1c90a4baf3e1aca62c', '[\"*\"]', NULL, '2025-09-12 13:43:13', '2025-09-10 13:43:13', '2025-09-10 13:43:13'),
(121, 'App\\Models\\Administration', 1, 'portail-access', '2d08ab9ffbaba2f65ddfec51d9921e3648b0c831e9fe0d601b211f81b0ced713', '[\"*\"]', NULL, '2025-09-12 14:31:42', '2025-09-10 14:31:42', '2025-09-10 14:31:42'),
(122, 'App\\Models\\Administration', 1, 'portail-access', 'ef9b24218391e149e51e1259cd3cb90743e7667541bbf1606cd1ee436692ffb0', '[\"*\"]', NULL, '2025-09-13 15:39:04', '2025-09-11 15:39:04', '2025-09-11 15:39:04'),
(123, 'App\\Models\\Administration', 1, 'portail-access', '615205283cebf553abcbfe76aa8af5dfdbd431b2de38309d4558934d4b2a520e', '[\"*\"]', NULL, '2025-09-14 08:37:55', '2025-09-12 08:37:55', '2025-09-12 08:37:55'),
(124, 'App\\Models\\Administration', 1, 'portail-access', 'e2058e42817f004f66b17c895048312420fcc17a56b21221ba53fc953ef6d108', '[\"*\"]', NULL, '2025-09-14 10:22:28', '2025-09-12 10:22:28', '2025-09-12 10:22:28'),
(125, 'App\\Models\\Administration', 1, 'portail-access', 'fe49e3b6087339c7cad4503ee1d0ce8ea1acee8435e8fc612c2f6af6f8737777', '[\"*\"]', NULL, '2025-09-14 16:49:04', '2025-09-12 16:49:04', '2025-09-12 16:49:04'),
(126, 'App\\Models\\Administration', 1, 'portail-access', 'b176735a4eab9aeb3b713765172b2418bd00d3319eeb2339f4f4df0d929b98da', '[\"*\"]', NULL, '2025-09-15 16:46:52', '2025-09-13 16:46:52', '2025-09-13 16:46:52'),
(127, 'App\\Models\\Administration', 1, 'portail-access', '584fa33920cf68aa4a8e58b2a5ca7f76eff85e65e3aa951948354f2efc857938', '[\"*\"]', NULL, '2025-09-17 09:12:18', '2025-09-15 09:12:18', '2025-09-15 09:12:18'),
(128, 'App\\Models\\Administration', 1, 'portail-access', '0b64ed25960f2042bea3261fbb63b4551be922132f3e4814a76755d6cef0ced0', '[\"*\"]', NULL, '2025-09-17 12:40:10', '2025-09-15 12:40:10', '2025-09-15 12:40:10'),
(129, 'App\\Models\\Administration', 1, 'portail-access', '7afb3b3e27f61da04be796e6044fe84dd068178c169888279b6d985401aade6b', '[\"*\"]', NULL, '2025-09-18 08:14:14', '2025-09-16 08:14:14', '2025-09-16 08:14:14'),
(130, 'App\\Models\\Administration', 1, 'portail-access', 'f04a471de0a9a72dea8048f442fb97068c4f00cb72a831e81af28eb106f43a25', '[\"*\"]', NULL, '2025-09-18 18:05:23', '2025-09-16 18:05:23', '2025-09-16 18:05:23'),
(131, 'App\\Models\\Administration', 1, 'portail-access', '240aaf9d453571eede953859def2aacceae6badb3bc8ec51adc16f402e3295f2', '[\"*\"]', NULL, '2025-09-19 08:03:07', '2025-09-17 08:03:07', '2025-09-17 08:03:07'),
(132, 'App\\Models\\Administration', 1, 'portail-access', 'e55d92ae8abd19afab09d1c0488d894ccc751c0a82e55e3db53d91f7bcc12c27', '[\"*\"]', NULL, '2025-09-19 08:48:04', '2025-09-17 08:48:04', '2025-09-17 08:48:04'),
(133, 'App\\Models\\Administration', 1, 'portail-access', '775f4df566107d130c925ba44729f743aa17585280e7e9a685152745de4fa99e', '[\"*\"]', NULL, '2025-09-19 12:03:36', '2025-09-17 12:03:36', '2025-09-17 12:03:36'),
(134, 'App\\Models\\Administration', 2, 'portail-access', '92516570ee0f0fd33cf1b0992e9d134fa6eadac0e2c259f47ce2d25c2ca59ee8', '[\"*\"]', NULL, '2025-09-20 11:02:00', '2025-09-18 11:02:00', '2025-09-18 11:02:00'),
(135, 'App\\Models\\Administration', 1, 'portail-access', '36d64b6e62b7afe93e52068ad9384fbebf6a99ebd5d7594a85fce59eaf7ce355', '[\"*\"]', NULL, '2025-09-20 15:37:08', '2025-09-18 15:37:08', '2025-09-18 15:37:08'),
(136, 'App\\Models\\Administration', 1, 'portail-access', '75697b8a737bbeaa9ae21293aaf7e3ff7c0eaae02105d6478be138ba54a99f11', '[\"*\"]', NULL, '2025-09-21 18:36:54', '2025-09-19 18:36:54', '2025-09-19 18:36:54'),
(137, 'App\\Models\\Administration', 1, 'portail-access', '1bdb6c74a2bf03fd117e418535260f107b370103157e3780155e7bcf1ea3cd4d', '[\"*\"]', NULL, '2025-09-24 11:52:40', '2025-09-22 11:52:40', '2025-09-22 11:52:40'),
(138, 'App\\Models\\Administration', 2, 'portail-access', 'a37caa9e9caf4d2c5e8267408fbcb0762478ff80306a872081a285709f0055ac', '[\"*\"]', NULL, '2025-09-24 14:27:14', '2025-09-22 14:27:14', '2025-09-22 14:27:14'),
(139, 'App\\Models\\Administration', 2, 'portail-access', 'abcd32b199a99af527fd70d7accfaf3c0cb3689f12a6c2e69929cdcf22dcb68d', '[\"*\"]', NULL, '2025-09-24 17:44:59', '2025-09-22 17:44:59', '2025-09-22 17:44:59'),
(140, 'App\\Models\\Administration', 2, 'portail-access', '3b2c55d76a23544e6d7100b4525660cc0c392ab6feb6bcf3352cae4de30d44e3', '[\"*\"]', NULL, '2025-09-25 18:03:41', '2025-09-23 18:03:41', '2025-09-23 18:03:41'),
(141, 'App\\Models\\Administration', 1, 'portail-access', 'b9560024a05acede871645ace01a94b80ffcc38f862b2e300731b26eabcf3e81', '[\"*\"]', NULL, '2025-09-25 18:03:57', '2025-09-23 18:03:57', '2025-09-23 18:03:57'),
(142, 'App\\Models\\Administration', 2, 'portail-access', '7e277d598233325d7cd42595ec736167f920c708013448b165e68afe13e34db5', '[\"*\"]', NULL, '2025-10-02 12:52:08', '2025-09-30 12:52:08', '2025-09-30 12:52:08'),
(143, 'App\\Models\\Administration', 1, 'portail-access', '72dff3211b600e2e828723a2afd59d1d282106f8992ce5cb78d9776df9afbb53', '[\"*\"]', NULL, '2025-10-10 11:26:34', '2025-10-08 11:26:34', '2025-10-08 11:26:34'),
(144, 'App\\Models\\Administration', 1, 'portail-access', 'ef47f9cbf7487575c8d3c7655b27947db8ef9663b02b59cc7480926dbfdf29f6', '[\"*\"]', NULL, '2025-10-12 08:13:16', '2025-10-10 08:13:16', '2025-10-10 08:13:16'),
(145, 'App\\Models\\Administration', 1, 'portail-access', '259260e35535033bf4916eacbc511fa5c2591f5bfe08fe331e1a6756108831a5', '[\"*\"]', NULL, '2025-10-12 14:31:55', '2025-10-10 14:31:55', '2025-10-10 14:31:55'),
(146, 'App\\Models\\Administration', 4, 'portail-access', '0c4264c54037a5bff633b2c029e555f87732707d3c59d293d0acde14e112f077', '[\"*\"]', NULL, '2025-10-15 02:13:44', '2025-10-13 02:13:44', '2025-10-13 02:13:44'),
(147, 'App\\Models\\Administration', 1, 'portail-access', 'e56dc03ee7b06a261a5868fa87bc7c22d7b2366d911392095907a19ed121a2de', '[\"*\"]', NULL, '2025-10-18 23:32:18', '2025-10-16 23:32:18', '2025-10-16 23:32:18'),
(148, 'App\\Models\\Administration', 1, 'portail-access', '3804c5060f56e2c5e575813f99c2c5b23703e9221548d0dfd47efd41fe045e40', '[\"*\"]', NULL, '2025-10-21 08:25:48', '2025-10-19 08:25:48', '2025-10-19 08:25:48');

-- --------------------------------------------------------

--
-- Table structure for table `personal_information`
--

CREATE TABLE `personal_information` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `first_names` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `birth_date` date NOT NULL,
  `birth_place` varchar(255) NOT NULL,
  `birth_country` varchar(255) NOT NULL,
  `gender` varchar(255) NOT NULL,
  `estimated_birth` tinyint(1) NOT NULL DEFAULT 0,
  `contacts` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`contacts`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `personal_information`
--

INSERT INTO `personal_information` (`id`, `last_name`, `first_names`, `email`, `birth_date`, `birth_place`, `birth_country`, `gender`, `estimated_birth`, `contacts`, `created_at`, `updated_at`) VALUES
(8, 'ADJAKIDJE', 'Tokannou Benoît Destin', 'adjakidje.11261012@uac.bj', '1990-11-07', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(9, 'ADJASSA', 'Abel Kocou', 'adjassa.21381024@uac.bj', '1977-03-08', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(10, 'ADJINONKOU', 'Ibitoyé Loïc Eléar', 'adjinonkou.11471415@uac.bj', '1996-02-07', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(11, 'AGBESSI', 'Finagnon Serge', 'agbessi.1110705@uac.bj', '1984-01-10', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(12, 'AHISSOU', 'Djidjoho Fortune', 'ahissou.10652712@uac.bj', '1993-04-16', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(13, 'AINADOU', 'Desmos', 'ainadou.1372304@uac.bj', '1990-01-01', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(14, 'AKPATA', 'Segun Morel Ildevert', 'akpata.21380924@uac.bj', '1997-07-09', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(15, 'AMOUZOUN', 'Kouessi Kalaibe', 'amouzoun.11417515@uac.bj', '1996-07-12', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(16, 'ASSOGBA', 'Cosme Yves', 'assogba.14512324@uac.bj', '2001-05-19', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(17, 'AVOCE', 'Jean Pierre', 'avoce.1808803@uac.bj', '1982-12-17', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(18, 'BENSANH', 'Donald Gontran', 'bensanh.19254713@uac.bj', '1993-03-28', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(19, 'BOURAIMA', 'Olatoundji Abidogoun', 'bouraima.11324815@uac.bj', '1996-09-10', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(20, 'BOVIS', 'Giscar Adoré', 'bovis.10984919@uac.bj', '2001-07-05', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(21, 'DAMON', 'Bani Toukouré Lawal', 'damon.11111412@uac.bj', '1992-01-07', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(22, 'DJEDOCANSI', 'Marcel Mahougnon', 'djedocansi.21381424@uac.bj', '1998-08-03', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(23, 'DJIBRIL', 'Mohamed Abdel Nasser Adéchinan', 'djibril.1212203@uac.bj', '1982-04-23', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(24, 'DJIKPESSE', 'Horace', 'djikpesse.21381124@uac.bj', '2001-07-09', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(25, 'DJOHOSSOU', 'Hognon Jean-Baptiste', 'djohossou.11159013@uac.bj', '1994-07-04', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(26, 'DOSSOU', 'Serge Kévin Gbéètogo', 'dossou.11356910@uac.bj', '1989-03-08', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(27, 'FANNY', 'Codjo Nestor', 'fanny.21381224@uac.bj', '1990-01-01', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(28, 'GBEDANDE', 'Mahouton Toussaint', 'gbedande.1743005@uac.bj', '1984-05-09', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(29, 'GNAHOUI', 'Sagbo Romulus', 'gnahoui.11084607@uac.bj', '1986-08-23', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(30, 'GOUNDJO', 'Sèminvo Elrick', 'goundjo.1483505@uac.bj', '1986-06-29', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(31, 'HOUEGBEME', 'Sènan Romuald', 'houegbeme.10220208@uac.bj', '1986-03-07', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(32, 'HOUENONGBE', 'Hospice', 'houenongbe.11154209@uac.bj', '1988-10-25', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(33, 'HOUNAHO', 'Essè Edmond', 'hounaho.19835114@uac.bj', '1995-11-19', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(34, 'HOUNGBEDJI', 'Hénoc', 'houngbedji.21380324@uac.bj', '1997-07-02', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(35, 'HOUNGBO', 'Boris Ahouignandji', 'houngbo.21380824@uac.bj', '1999-11-05', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(36, 'HOUNKPATIN', 'Yao Carmel', 'hounkpatin.21380724@uac.bj', '1999-04-07', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(37, 'HOUNTONDJI', 'Yélian Raïsse Orphée', 'hountondji.11261409@uac.bj', '1991-05-09', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(38, 'HOUSSOU', 'Vignon Agonman Christian Hervé', 'houssou.11144406@uac.bj', '1983-12-22', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(39, 'KEITA', 'Richard Koutidonta', 'keita.11159614@uac.bj', '1994-01-10', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(40, 'KOHONOU', 'Bignon Jacques Josimar', 'kohonou.10748108@uac.bj', '1990-07-25', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(41, 'KONA', 'Cocou Charles Hubert', 'kona.1519004@uac.bj', '1980-05-11', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(42, 'KOSSOU', 'Comlan Pacôme', 'kossou.97133991@uac.bj', '1990-01-01', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(43, 'KOTTIN', 'Jules', 'kottin.20538618@uac.bj', '1975-12-04', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(44, 'KOULESSI', 'Mahoutin Wilfried', 'koulessi.21380024@uac.bj', '1989-04-12', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(45, 'LALEYE', 'François Bayo Adéfemi Horfé', 'laleye.13605216@uac.bj', '1998-09-03', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(46, 'LESSE', 'Gloglo Hounangnon Sylvain', 'lesse.21381324@uac.bj', '1991-04-05', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(47, 'MESSE', 'Mahougbé Raoul', 'messe.10543607@uac.bj', '1983-07-07', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(48, 'NOUGBOGNONHOU', 'Kouassi Ulrich', 'nougbognonhou.11376412@uac.bj', '1991-06-22', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(49, 'OFFOUMON', 'Yaï Franck Josée', 'offoumon.21380624@uac.bj', '1979-09-04', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(50, 'OGOUSSAN', 'Abdoul-Malick Prince Abiola', 'ogoussan.21380224@uac.bj', '1999-08-11', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(51, 'OLIKIMON', 'Aldo-Unnel Evariste', 'olikimon.10292107@uac.bj', '1987-07-24', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(52, 'OLOUKA', 'Oyeniyi Habib', 'olouka.21380524@uac.bj', '1998-07-01', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(53, 'OROU SEKO', 'Orou Gara Rodrigue', 'orou seko.21380124@uac.bj', '2001-02-02', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(54, 'SALOU', 'Marzouk Dawdu', 'salou.18627218@uac.bj', '2000-08-23', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(55, 'SATCHOUA', 'DOVONOU Mahougbé Luc Jeconias', 'satchoua.66967618@uac.bj', '1990-01-01', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(56, 'SEHOUNON', 'Carolle Noudéhouénou', 'sehounon.10598408@uac.bj', '1986-06-04', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(57, 'SENOU', 'Sèwagnon Sosthène', 'senou.10219908@uac.bj', '1986-05-23', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(58, 'SESSON', 'Sylvain Midokpè', 'sesson.17654214@uac.bj', '1993-05-11', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(59, 'SOGBEDJI', 'Mario', 'sogbedji.12172816@uac.bj', '1990-01-01', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(60, 'SOURADJOU', 'Hillal', 'souradjou.10351208@uac.bj', '1982-03-29', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(61, 'TACOLODJOU', 'Romaric Dona Sèdjro', 'tacolodjou.12424313@uac.bj', '1990-01-01', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(62, 'YEMADJRO SATONDJI', 'Fiacre Aristide', 'yemadjro satondji.10512608@uac.bj', '1989-08-30', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(63, 'YESSOUFOU', 'Faozath', 'yessoufou.11223515@uac.bj', '1990-01-01', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(64, 'ZEHE', 'Evariste Lisca', 'zehe.21380424@uac.bj', '1982-04-12', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(65, 'ABOUBACAR MAMAN', 'Taofic', 'aboubacar maman.10029109@uac.bj', '1989-07-01', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(66, 'ADOUNDJO', 'Memanvo Tundé Pierre', 'adoundjo.13634216@uac.bj', '1997-12-21', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(67, 'AGBESSI', 'Fernand', 'agbessi.10301012@uac.bj', '1994-11-30', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(68, 'AGBOGNIHOUE', 'Parfait', 'agbognihoue.21294723@uac.bj', '1990-01-01', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(69, 'AHOHOUENDO', 'Monsede Florentin', 'ahohouendo.21295323@uac.bj', '1990-01-01', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(70, 'ALAMOU', 'Rachidi', 'alamou.10328509@uac.bj', '1988-03-11', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(71, 'ASSOGBA', 'Dègninou Carlos', 'assogba.10436109@uac.bj', '1989-11-13', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(72, 'ASSOGBA', 'Koffi Edgard', 'assogba.227301@uac.bj', '1979-12-28', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(73, 'ATAKPA', 'Alain Coffi', 'atakpa.21294923@uac.bj', '1990-01-01', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(74, 'AZALOU', 'Mondoukpè Moïse Martial', 'azalou.19216618@uac.bj', '2000-05-10', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(75, 'AZON', 'Sèdéhou Judicaël Comlan', 'azon.20042515@uac.bj', '1975-12-16', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(76, 'AZONWADE', 'Batché Irené', 'azonwade.ABGC1323@uac.bj', '1990-01-01', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(77, 'BALOGOUN', 'Kolawolé Brice Alain', 'balogoun.309716708@uac.bj', '1978-11-30', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(78, 'BOURAIMA', 'Farid Olaoyé Olouwassègoun', 'bouraima.21295223@uac.bj', '1990-01-01', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(79, 'DAGOUE', 'Eliézer Aimé Tobi', 'dagoue.21295523@uac.bj', '1990-01-01', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(80, 'DAHOUE', 'Cocouvi Désiré', 'dahoue.21295623@uac.bj', '1990-01-01', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(81, 'DANHIN', 'Lééman Aimé', 'danhin.10721412@uac.bj', '1990-11-02', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(82, 'DJOUMONLO', 'Sètondji Prudence', 'djoumonlo.1429504@uac.bj', '1981-06-05', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(83, 'GBECONNOU', 'Sèdjrogan Brice', 'gbeconnou.11013606@uac.bj', '1983-05-12', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(84, 'GBESSO', 'Réné', 'gbesso.2039805@uac.bj', '1980-10-21', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(85, 'GOVOEYI', 'Sènou Hervé', 'govoeyi.11140912@uac.bj', '1989-10-16', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(86, 'HOTEKPO', 'Godfroy Elie', 'hotekpo.10395010@uac.bj', '1988-07-12', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(87, 'HOUNDAGBA', 'Yélian Hyppolite', 'houndagba.11087111@uac.bj', '1988-08-13', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(88, 'HOUNLIDJI', 'Aïnahin Rubens Abdias', 'hounlidji.10889010@uac.bj', '1992-01-01', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(89, 'HOUNMASSE', 'Kossi Lester Marion', 'hounmasse.11238106@uac.bj', '1985-02-06', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(90, 'IDRISSOU', 'Bounouyaminou', 'idrissou.21299723@uac.bj', '1979-08-23', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(91, 'KPADONOU', 'Sonagnon Wilfrid', 'kpadonou.12176009@uac.bj', '1986-11-08', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(92, 'LOKONON', 'Evènon Vignonce', 'lokonon.12265109@uac.bj', '1990-01-01', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(93, 'MADADANNIN', 'Fidèle', 'madadannin.21295723@uac.bj', '1990-01-01', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(94, 'NOUHOTO', 'Finangnon Brontheid', 'nouhoto.21294823@uac.bj', '1990-01-01', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(95, 'ODJO', 'Bolawa Prudencia', 'odjo.17791914@uac.bj', '1995-06-05', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(96, 'QUENUM', 'Doris Alicia', 'quenum.21295423@uac.bj', '1990-01-01', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(97, 'SAVI', 'Anihouvi Donald', 'savi.10848811@uac.bj', '1990-06-24', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(98, 'SEKPE', 'Camille Bertrand', 'sekpe.89999@uac.bj', '1980-10-16', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(99, 'SOSSA', 'Benoit Raoul', 'sossa.10888112@uac.bj', '1992-04-16', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(100, 'TEKO KUESSAN', 'Julien Josias', 'teko kuessan.10147112@uac.bj', '1992-12-02', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(101, 'TOGNISSO', 'Lionel Ariss Cyd Junior', 'tognisso.10017410@uac.bj', '1985-01-28', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(102, 'ZIME MOUSSA', 'Koro Germain', 'zime moussa.19484414@uac.bj', '1993-06-15', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(103, 'ZOCLANCOUNON', 'Bbènoukpo Sylvain', 'zoclancounon.11203312@uac.bj', '1990-03-05', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(104, 'ADIKPETO', 'Marcellin', 'adikpeto.67300053@uac.bj', '1990-01-01', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(105, 'ATCHEGUI', 'Emile', 'atchegui.10522310@uac.bj', '1989-09-30', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(106, 'BATONON', 'Anicet Bénoit', 'batonon.11746815@uac.bj', '1993-04-17', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(107, 'COFFI', 'Léonce', 'coffi.10033808@uac.bj', '1984-06-18', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(108, 'DASSOU', 'Geoffroy', 'dassou.21273723@uac.bj', '2001-08-12', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(109, 'DJABOUTOUBOUTOU', 'Mansourou', 'djaboutouboutou.12349913@uac.bj', '1985-08-28', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(110, 'DOSSOU', 'Boussi Barthélemy', 'dossou.1879603@uac.bj', '1981-08-23', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(111, 'EHOUMI', 'Olohounto David', 'ehoumi.21379824@uac.bj', '1990-01-01', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(112, 'FASSINOU', 'Yéhomè Daryl', 'fassinou.96311584@uac.bj', '1990-01-01', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(113, 'GOUGLA', 'Dèlonou', 'gougla.11368419@uac.bj', '1983-10-11', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(114, 'KOGUI', 'Bah-Bio Serge', 'kogui.11544810@uac.bj', '1988-01-23', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(115, 'MOUSSE', 'Imad Deen', 'mousse.11503710@uac.bj', '1992-07-07', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(116, 'SOUROU', 'O Smail', 'sourou.1734505@uac.bj', '1982-06-02', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(117, 'TOGAN', 'Xavier', 'togan.11746915@uac.bj', '1994-02-12', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(118, 'ZOUNON', 'Yannick Charbel Vignon', 'zounon.21379924@uac.bj', '2002-08-02', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(119, 'AHOUANDJINOU', 'B Pierre Canisius', 'ahouandjinou.10911410@uac.bj', '1990-01-01', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(120, 'ASSANI', 'Osséni 2ème Jumeau', 'assani.30278718@uac.bj', '1990-01-01', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(121, 'BAH', 'Moussiliou Alain', 'bah.11009306@uac.bj', '1990-01-01', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(122, 'BODJRENOU', 'Donatien Houessou', 'bodjrenou.1630104@uac.bj', '1990-01-01', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(123, 'CHOGOLOU', 'D Yvonne', 'chogolou.21297123@uac.bj', '1990-01-01', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(124, 'COSSOU-GBETO', 'Guedalia Eliphaz', 'cossou-gbeto.10615912@uac.bj', '1990-01-01', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(125, 'DEGLA', 'Harold Sourou', 'degla.21297023@uac.bj', '1990-01-01', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(126, 'DO REGO', 'Moussilim', 'do rego.12163109@uac.bj', '1990-01-01', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(127, 'HOUNKPATIN', 'Richard Toussain Sènadé', 'hounkpatin.10846111@uac.bj', '2025-04-23', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(128, 'HOUNLEYI', 'Sènami Auriol Froebel', 'hounleyi.14185014@uac.bj', '1990-01-01', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(129, 'KITIHOUN', 'Michel', 'kitihoun.21297223@uac.bj', '1990-01-01', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(130, 'MIGAN', 'Agoman Kpognon Rudolf', 'migan.21296923@uac.bj', '1990-01-01', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(131, 'SINDAGBO', 'Marius', 'sindagbo.10136508@uac.bj', '1990-01-01', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(132, 'TOHINLO', 'Yves', 'tohinlo.21159522@uac.bj', '1990-01-01', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(133, 'ADELEKE', 'Hanan Atanda Adéromou', 'adeleke.15968117@uac.bj', '1999-02-28', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(134, 'AKINOMI', 'Adékounlé Boris', 'akinomi.21381724@uac.bj', '1992-01-05', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(135, 'AVOCEFOHOUN', 'Djidjoho Rodolphe', 'avocefohoun.21381924@uac.bj', '1995-09-06', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(136, 'AWAGBOYESSI', 'Ulrich', 'awagboyessi.19247418@uac.bj', '1998-08-02', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(137, 'GOUDOU', 'Daspa Rhés-Atif Chrissougnon', 'goudou.12171016@uac.bj', '1998-05-28', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(138, 'HOUNKPE', 'Loïc Féjos', 'hounkpe.21381524@uac.bj', '2001-07-03', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(139, 'KOUTON', 'Géo-Prudence', 'kouton.10513012@uac.bj', '1993-03-11', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(140, 'LANI-YONOU', 'Roland', 'lani-yonou.1248305@uac.bj', '1982-09-29', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(141, 'LATIFOU', 'Awal Adio', 'latifou.16399716@uac.bj', '1998-07-01', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(142, 'LOKO', 'Coovi Marianos', 'loko.10180411@uac.bj', '1991-11-21', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(143, 'MEHOU', 'LOKO Céphas Carthynel', 'mehou.11282419@uac.bj', '2025-04-23', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(144, 'OUIN-OURO', 'Yintidema Evans Vainqueur', 'ouin-ouro.21381824@uac.bj', '1990-11-06', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(145, 'PADONOU', 'Serge Amagbégnon', 'padonou.21297723@uac.bj', '1998-05-10', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(146, 'TCHIGLI KOFFI', 'Kocou Aymar', 'tchigli koffi.10276608@uac.bj', '1985-05-29', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(147, 'VIVENAGBO', 'Landry Cocou', 'vivenagbo.1553305@uac.bj', '1981-10-06', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(148, 'YEDE', 'Lénaïque Houéfa Dê-dédji', 'yede.21297623@uac.bj', '2003-01-15', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(149, 'YEHOUENOU', 'Romuald Coffi', 'yehouenou.11086110@uac.bj', '1986-06-13', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(150, 'ZINSALO', 'Wilson Elise', 'zinsalo.21381624@uac.bj', '2001-02-06', 'Inconnu', 'Bénin', 'M', 0, '[\"+22900000000\"]', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(152, 'NOUHOUMON', 'Michel', 'n_michou@yahoo.fr', '1976-08-20', 'Cotonou', 'Béninoise', 'male', 0, '[\"0197608971\"]', '2025-07-11 16:28:21', '2025-07-11 16:28:21'),
(153, 'LEMON', 'Riyade Dine Emelin', 'l.riyade@outlook.fr', '1997-10-27', 'Porto-Novo', 'Béninoise', 'male', 0, '[\"0196562601\"]', '2025-07-14 10:55:54', '2025-07-14 10:55:54'),
(154, 'GANDO', 'Mahouna Symplice', 'gandosymplice2@gmail.com', '2001-02-03', 'Akpassi', 'Béninoise', 'male', 0, '[\"0169037080\"]', '2025-07-14 11:04:20', '2025-07-14 11:04:20'),
(156, 'ASSOGBA', 'Victorin Wanignon', 'espwinner7@gmail.com', '1981-03-26', 'Adjarra 1', 'Béninoise', 'male', 0, '[\"0197629889\"]', '2025-07-15 15:27:41', '2025-07-15 15:27:41'),
(158, 'BABARIMISSA', 'Fadel Abd-El Rachidi', 'babarimissarachid@gmail.com', '1986-10-19', 'Porto-Novo', 'Béninoise', 'male', 0, '[\"0197161736\",\"0195253736\"]', '2025-07-18 07:57:01', '2025-07-18 07:57:01'),
(160, 'AGBALE', 'Comlan Armand', 'agbalecarmand@gmail.com', '1996-12-20', 'Bohicon', 'Beninoise', 'male', 0, '[\"0153183635\"]', '2025-07-20 20:21:44', '2025-07-20 20:21:44'),
(161, 'ABAI', 'Prince', 'princeabai666@gmail.com', '2002-10-14', 'Cotonou', 'Béninoise', 'male', 0, '[\"0197351391\"]', '2025-08-05 13:14:29', '2025-08-05 13:14:29'),
(162, 'GNANCADJA', 'Josée Espérancia Gracia', 'joseegnancadja89@gmail.com', '2004-04-16', 'Cotonou', 'Béninoise', 'female', 0, '[\"0157152388\",\"0145531286\"]', '2025-08-06 11:30:44', '2025-08-06 11:30:44'),
(163, 'TCHOBO', 'Armand Oladélé', 'aolt1994@gmail.com', '1994-10-25', 'Cotonou', 'Béninoise', 'male', 0, '[\"0196763052\",\"0147555527\"]', '2025-08-06 11:58:35', '2025-08-06 11:58:35'),
(164, 'AYENA', 'Grâce Manuel', 'ayenagrace9@gmail.com', '2002-05-19', 'Cotonou', 'Béninoise', 'female', 0, '[\"0169063600\"]', '2025-08-06 12:32:52', '2025-08-06 12:32:52'),
(165, 'AHOSSOUBOKO', 'Mihenou Dossa Rodrigue', 'rahossouboko12@gmail.com', '1998-03-28', 'Bohicon', 'Béninoise', 'male', 0, '[\"0140100587\",\"0141001818\"]', '2025-08-07 08:58:56', '2025-08-07 08:58:56'),
(166, 'OLATOUNDE', 'Koukoyi Bruno', 'olatoundebruno068@gmail.com', '1986-10-06', 'Parakou', 'Béninoise', 'male', 0, '[\"0196178355\",\"0140061782\"]', '2025-08-07 10:46:16', '2025-08-07 10:46:16'),
(167, 'GNONHOUEVI', 'S. Raguël Gildas', 'raguegil@gmail.com', '1986-02-15', 'Cotonou', 'Beninoise', 'male', 0, '[\"01 97 93 89 75\",\"01 47 81 38 35\"]', '2025-08-07 11:03:23', '2025-08-07 11:03:23'),
(168, 'GBETCHI', 'Armel', 'armel3887@gmail.com', '2000-08-16', 'HOUEYOGBE', 'Béninois', 'male', 0, '[\"90996147\",\"63240414\"]', '2025-08-07 12:21:43', '2025-08-07 12:21:43'),
(169, 'ADANTINNON', 'Arsène', 'arseneadantinnon@gmail.com', '2000-11-27', 'AKPRO-MISSERETE(Katagon)', 'Bénin', 'male', 0, '[\"0162329063\",\"0149490714\"]', '2025-08-07 15:59:39', '2025-08-07 15:59:39'),
(170, 'AVOCEGAMOU', 'Mahugnon Jérémie Pascal', 'jeremieavocegamou1@gmail.com', '2003-05-17', 'Porto-Novo,Tokpota', 'Bénin', 'male', 0, '[\"0169454643\"]', '2025-08-08 15:02:08', '2025-08-08 15:02:08'),
(171, 'KAKPO', 'Noudéhouénou Appolinaire', 'appolinairekakpo2020@gmail.com', '1997-02-09', 'Adjohoun', 'Béninoise', 'male', 0, '[\"0191104876\"]', '2025-08-08 22:57:19', '2025-08-08 22:57:19'),
(172, 'DJIKPESSE', 'Aurelien Crépin', 'crepindjikpesse793@gmail.com', '2003-09-07', 'Cotonou', 'Béninoise', 'male', 0, '[\"0163042701\"]', '2025-08-11 10:28:24', '2025-08-11 10:28:24'),
(173, 'NOUMON', 'Yaovi Arsène Lionel', 'lionelnoumon@gmail.com', '1997-05-08', 'Cotonou', 'Béninoise', 'male', 0, '[\"0166026621\"]', '2025-08-11 13:11:51', '2025-08-11 13:11:51'),
(174, 'HINNILO QUENUM', 'Geoffroy', 'geoffroyhinnilo286@gmail.com', '2002-11-16', 'Godomey', 'Béninoise', 'male', 0, '[\"0199264672\"]', '2025-08-12 11:38:42', '2025-08-12 11:38:42'),
(175, 'KAGBOA', 'Adjimon Jéchonias Ezéchias', 'ezechiaskagboa@gmail.com', '1997-10-10', 'Cotonou', 'Béninoise', 'male', 0, '[\"0169596789\"]', '2025-08-12 12:26:07', '2025-08-12 12:26:07'),
(176, 'LIMOAN', 'Victorine', 'limoanvictorine@gmail.com', '1978-11-28', 'Savalou', 'Béninoise', 'female', 0, '[\"0197479514\"]', '2025-08-12 13:45:51', '2025-08-12 13:45:51'),
(178, 'AGLOSSI', 'Corinne Mawugnon', 'corinneaglossi0000@gmail.com', '2002-08-06', 'Cotonou', 'Bénin', 'female', 0, '[\"0165310354\",\"0153426685\"]', '2025-08-12 23:02:41', '2025-08-12 23:02:41'),
(179, 'EDAH', 'HOUEDEKA  ESTHER', 'houedeka@gmail.com', '1990-04-03', 'Abomey', 'Béninoise', 'female', 0, '[\"0166933334\"]', '2025-08-13 17:40:24', '2025-08-13 17:40:24'),
(181, 'TENONKPONTO GBOSSEDE', 'Akomagnon Urbain', 'utenonkponto@gmail.com', '2003-02-28', 'Cotonou', 'Bénin', 'male', 0, '[\"0161665518\"]', '2025-08-14 10:16:55', '2025-08-14 10:16:55'),
(182, 'BAGBONON', 'Senam Gracio Farel', 'graciobagbonon@gmail.com', '2001-09-11', 'Cotonou', 'Béninoise', 'male', 0, '[\"0166686888\",\"0143069190\"]', '2025-08-14 10:56:15', '2025-08-14 10:56:15'),
(183, 'WINSOU', 'Fréjus Bénide', 'frejusbenidewinsou@gmail.com', '1998-03-31', 'Cotonou', 'Béninoise', 'male', 0, '[\"0166219114\",\"0160454990\",\"0144954234\"]', '2025-08-18 08:37:07', '2025-08-18 08:37:07'),
(184, 'AGOUDANHADE', 'Eric', 'eagoudanhade@gmail.com', '1992-05-17', 'Cotonou', 'Béninoise', 'male', 0, '[\"0196357966\"]', '2025-08-18 11:07:26', '2025-08-18 11:07:26'),
(185, 'ZAKPE', 'Parfait Jocelyn', 'parfaitjocelynzakpe@gmail.com', '1998-01-01', 'AGAME', 'Béninoise', 'male', 0, '[\"0160703601\",\"0147825083\"]', '2025-08-18 12:02:26', '2025-08-18 12:02:26'),
(186, 'ALOGNON', 'Innocent', 'alognoninnocent@gmail.com', '1993-12-28', 'Kandi', 'Béninois', 'male', 0, '[\"0194186424\"]', '2025-08-18 17:16:56', '2025-08-18 17:16:56'),
(187, 'GNANSOUNOU', 'Henagnon Charbel', 'gnansounouchar@gmail.com', '1999-09-14', 'Tankpè/Godomey', 'Bénin', 'male', 0, '[\"0197220474\"]', '2025-08-18 18:32:47', '2025-08-18 18:32:47'),
(188, 'AGO', 'DEGBEMABOU MAXIME', 'agomax01@gmail.com', '1997-12-03', 'SO-AVA', 'COTONOU', 'male', 0, '[\"0168477172\"]', '2025-08-19 09:00:27', '2025-08-19 09:00:27'),
(189, 'MEHOBA', 'Houéfa Irvine Ocsana', 'mehobahouefairvineocsana@gmail.com', '2002-10-19', 'Cotonou', 'Béninoise', 'female', 0, '[\"0153006038\"]', '2025-08-19 12:15:42', '2025-08-19 12:15:42'),
(190, 'GOGAN', 'Gilbert', 'gilbertgogan06@gmail.com', '1991-01-01', 'Koto', 'Béninoise', 'male', 0, '[\"01 44 67 70 04\\/ 01 66 46 70 57\"]', '2025-08-19 15:56:05', '2025-08-19 15:56:05'),
(191, 'AHONONGA', 'Franck Tiburce', 'frncky@yahoo.com', '1982-01-18', 'Cotonou', 'Bénin', 'male', 0, '[\"0195270412\"]', '2025-08-19 21:42:48', '2025-08-19 21:42:48'),
(192, 'GOMANDA', 'Ruben', 'gomandadieudonneruben@gmail.com', '2002-12-23', 'Zoungoudo', 'Béninoise', 'male', 0, '[\"0147374318\"]', '2025-08-20 09:30:28', '2025-08-20 09:30:28'),
(193, 'TCHOUKELE', 'Nougbognon Amédé', 'atchoukele@gmail.com', '1977-03-30', 'PORTO - NOVO', 'Béninoise', 'male', 0, '[\"0196888886\"]', '2025-08-20 22:27:27', '2025-08-20 22:27:27'),
(194, 'HOUEKPON', 'Sètondé Agossou Zinsou Parménion', 'parmenionhouekpon@gmail.com', '2002-03-15', 'Godomey', 'Béninoise', 'male', 0, '[\"0191426432\"]', '2025-08-21 08:25:32', '2025-08-21 08:25:32'),
(195, 'DIDAVI', 'Djidjoho Romaric Armel', 'didarmel93@gmail.com', '1993-07-16', 'Cotonou', 'Béninoise', 'male', 0, '[\"0196860183\"]', '2025-08-21 13:58:23', '2025-08-21 13:58:23'),
(196, 'CHABI', 'DIMON AIME', 'aimedchabi@gmail.com', '1984-10-28', 'TOKPA-DOME', 'BENINOISE', 'male', 0, '[\"(00229) 0197126204\",\"0163552763\"]', '2025-08-21 14:32:14', '2025-08-21 14:32:14'),
(198, 'MONTCHO', 'Godsend Abdias Mauluce', 'abdoabdias15@gmail.com', '1999-08-16', 'Cotonou', 'Béninoise', 'male', 0, '[\"0191421440\"]', '2025-08-22 09:42:47', '2025-08-22 09:42:47'),
(199, 'YETOME', 'Amour', 'amouryetome6@gmail.com', '1996-01-01', 'Bohicon', 'Béninoise', 'female', 0, '[\"0167282137\",\"0141679251\"]', '2025-08-22 12:58:20', '2025-08-22 12:58:20'),
(200, 'BEHANZIN', 'Precieux', 'behanzinprecieux@gmail.com', '2002-02-14', 'Aplahoue', 'Bénin', 'male', 0, '[\"0140100448\"]', '2025-08-22 13:59:35', '2025-08-22 13:59:35'),
(201, 'LOKONON', 'Jean Pierre', 'lokononjeanpierre@gmail.com', '1982-09-14', 'Adjohoun', 'Béninoise', 'male', 0, '[\"+229 0196152960\",\"+229 0195792050\"]', '2025-08-22 16:17:06', '2025-08-22 16:17:06'),
(202, 'HOUNNOU', 'Coovi Hermès Cacharel Pino Alvy', 'hermeshounnou@gmail.com', '1982-06-10', 'Cotonou', 'Cotonou', 'male', 0, '[\"+2290166055711\"]', '2025-08-22 17:15:28', '2025-08-22 17:15:28'),
(203, 'ZANMENOU', 'Mèdessè Bertrand', 'bertuno2@yahoo.fr', '1978-08-04', 'Cotonou', 'Béninoise', 'male', 0, '[\"0195286374\",\"0197912426\"]', '2025-08-22 18:29:33', '2025-08-22 18:29:33'),
(204, 'MISSEKPE', 'Basilas', 'basilasbasilas007@gmail.com', '1993-03-12', 'Agamè (Lokossa)', 'Béninoise', 'male', 0, '[\"0167289509\"]', '2025-08-22 19:53:10', '2025-08-22 19:53:10'),
(205, 'BODONON', 'Josué', 'bodononjosue@gmail.com', '2000-07-28', 'Kinta', 'Bénin', 'male', 0, '[\"0190597890\"]', '2025-08-26 08:38:07', '2025-08-26 08:38:07'),
(206, 'HOUNGUE', 'Daniel G. Cédric', 'hounguecedricdaniel7@gmail.com', '1994-11-08', 'Possotomé', 'Beninoise', 'male', 0, '[\"0167317596\",\"0154327692\"]', '2025-08-26 10:06:29', '2025-08-26 10:06:29'),
(207, 'HOUNSOU', 'Finagnon Eric', 'ehounsou@sbee.bj', '1990-01-01', 'Gbecon - Hounli', 'Beninoise', 'male', 0, '[\"0196772735 \\/ 0158551050\"]', '2025-08-27 08:55:25', '2025-08-27 08:55:25'),
(208, 'OTEGBEYE', 'Mouhamed Olasiubo', 'mouhamedolasiubo@gmail.com', '1991-08-13', 'Cotonou', 'Beninoise', 'male', 0, '[\"0197763896\",\"0143461453\"]', '2025-08-27 15:16:51', '2025-08-27 15:16:51'),
(209, 'HABOU', 'Moussa', 'moussahabou839@gmail.com', '1999-12-16', 'Founougo', 'Béninoise', 'male', 0, '[\"0194946766\"]', '2025-08-28 16:23:30', '2025-08-28 16:23:30'),
(210, 'AKPOVIKE', 'Sègla Innocent Roger', 'rogerakpovike@gmail.com', '2000-12-28', 'Savalou', 'Béninoise', 'male', 0, '[\"0160210769\"]', '2025-08-29 16:35:32', '2025-08-29 16:35:32'),
(211, 'MAGNONFINON', 'Sillas Renaldo', 'magnonfinonr@yahoo.fr', '1981-07-19', 'Cotonou', 'Béninois', 'male', 0, '[\"0197938080\",\"0195962388\"]', '2025-09-01 10:30:54', '2025-09-01 10:30:54'),
(212, 'ANATO', 'Pierre Hugues', 'anato.p.hugues@gmail.com', '1997-01-18', 'Houeyogbé', 'Béninoise', 'male', 0, '[\"0166677337\",\"0151507369\"]', '2025-09-02 08:31:21', '2025-09-02 08:31:21'),
(213, 'YESSOUFOU', 'Aéman Bola-Nlé Yannick', 'aemanyessoufou@gmail.com', '1986-06-23', 'Cotonou', 'Béninoise', 'male', 0, '[\"0197487950\"]', '2025-09-02 09:36:05', '2025-09-02 09:36:05'),
(214, 'DAVITO', 'TOHOUÉDÉ Charlemagne', 'charlesdav229@gmail.com', '1988-12-22', 'Azoveè', 'Béninoise', 'male', 0, '[\"0196775941\",\"0195702225\"]', '2025-09-03 09:02:58', '2025-09-03 09:02:58'),
(215, 'AGASSIN', 'Christel Josué', 'agassinchristeljosue@gmail.com', '2003-11-24', 'Cotonou', 'Béninois', 'male', 0, '[\"0191104803\"]', '2025-09-03 09:57:44', '2025-09-03 09:57:44'),
(216, 'SOHOU', 'Romaric', 'romaricsohou80@gmail.com', '2000-07-15', 'Abomey', 'Bénin', 'male', 0, '[\"0165552338\"]', '2025-09-04 11:32:48', '2025-09-04 11:32:48'),
(217, 'KOUSSEDOH', 'Sèwlan Aimée Ornelia', 'akoussedoh@gmail.com', '2002-02-19', 'TOTA', 'Béninoise', 'female', 0, '[\"0140040261\"]', '2025-09-05 09:29:14', '2025-09-05 09:29:14'),
(218, 'AGBAMAHOU', 'Carmel', 'cagbamahou@gmail.com', '1984-01-01', 'SINWE', 'Béninoise', 'male', 0, '[\"0195887621\",\"0197189883\"]', '2025-09-05 10:46:28', '2025-09-05 10:46:28'),
(219, 'ASSIHLENON', 'Allihonou Fabrice', 'assihlenon@gmail.com', '1999-12-19', 'ASSANTE', 'Béninois', 'male', 0, '[\"0162249352\",\"0165356158\"]', '2025-09-05 21:04:15', '2025-09-05 21:04:15'),
(220, 'ALLAGBE', 'Marguerite Idi-Abinin', 'idiabinin@gmail.com', '1985-11-10', 'TCHAOUROU', 'Béninoise', 'female', 0, '[\"0197422492\"]', '2025-09-07 09:23:23', '2025-09-07 09:23:23'),
(221, 'MONGUEDE', 'BIOVA', 'monguedetovi@gmail.com', '1977-03-02', 'Dogbo', 'Béninoise', 'female', 0, '[\"0195567608\"]', '2025-09-08 10:57:10', '2025-09-08 10:57:10'),
(222, 'ZINSOU', 'Ingrid Jennifer', 'zinsoujennifer@gmail.com', '2003-07-30', 'Parakou', 'Béninoise', 'female', 0, '[\"0167717109\"]', '2025-09-09 14:08:42', '2025-09-09 14:08:42'),
(223, 'ESSOU', 'Kafui emmanuella floronique', 'essoufloronick@gmail.com', '2004-12-01', 'Abomey', 'Béninoise', 'female', 0, '[\"0194006270\"]', '2025-09-10 10:20:03', '2025-09-10 10:20:03'),
(224, 'DOHOU', 'Djidé Serge', 'oladjide2019@gmail.com', '1990-10-02', 'Hêtin', 'Béninois', 'male', 0, '[\"0196253864\",\"0191916028\"]', '2025-09-10 15:28:23', '2025-09-10 15:28:23'),
(225, 'SEDEDJAN', 'Ferdinand', 'romaricsohou2@gmail.com', '1995-07-14', 'DENOU-LISSEZIN', 'Béninois', 'male', 0, '[\"0167832632\"]', '2025-09-12 10:56:31', '2025-09-12 10:56:31'),
(226, 'DJIWAN', 'Vihitogbe Alida', 'alidadjiwan@gmail.com', '2002-02-08', 'DJEREGBE', 'Béninoise', 'female', 0, '[\"0146746888\",\"0147546640\"]', '2025-09-12 14:27:29', '2025-09-12 14:27:29'),
(227, 'AMOUSSOU', 'Chérif-Dine', 'amoussoucherifdine@gmail.com', '2003-06-05', 'Bassila', 'Bénin', 'male', 0, '[\"0166330820\"]', '2025-09-15 09:46:35', '2025-09-15 09:46:35'),
(228, 'Gbongboui', 'Géoffroy Godwish', 'ggbongboui@gmail.com', '2002-02-16', '+22951323836', 'Bénin', 'male', 0, '[\"+229 0151323836\"]', '2025-09-15 11:23:43', '2025-09-15 11:23:43'),
(229, 'AMOUSSOU', 'David Emmanuel Mawuna', 'superemmanueldavid@gmail.com', '1994-05-09', 'Cotonou', 'Béninoise', 'male', 0, '[\"0166823297\",\"0147680097\"]', '2025-09-15 14:07:54', '2025-09-15 14:07:54'),
(230, 'TOBOSSI', 'Biwêgnon Christian Raphaël Nislet', 'tobossichristiann@gmail.com', '1998-10-20', 'Agonlin houegbo', 'Béninoise', 'male', 0, '[\"0166355087\"]', '2025-09-15 15:09:57', '2025-09-15 15:09:57'),
(231, 'GNONLONFOUN', 'Prince', 'gnonlonfoun65@gmail.com', '1994-11-20', 'Ekpè', 'Béninoise', 'male', 0, '[\"0196123014\",\"0140374808\"]', '2025-09-16 12:32:51', '2025-09-16 12:32:51'),
(232, 'ODJO', 'Frimas Faras', 'odjofrimas3@gmail.com', '1997-09-07', 'Abomey calavi', 'Béninoise', 'male', 0, '[\"0161185340\"]', '2025-09-16 15:04:45', '2025-09-16 15:04:45'),
(233, 'Doussoh', 'Dan josias', 'danjosias36@gmail.com', '2002-01-22', 'Lomé', 'Béninoise', 'male', 0, '[\"01\"]', '2025-09-17 09:57:27', '2025-09-17 09:57:27'),
(234, 'SESSOU', 'Koffi Florentin Campbell', 'campbellsessou@gmail.com', '2003-10-24', 'Cotonou', 'Béninoise', 'male', 0, '[\"0194472061\"]', '2025-09-17 22:30:14', '2025-09-17 22:30:14'),
(235, 'Dossou', 'Tchognon Sunday Judicaël', 'sundaydossou6@gmail.com', '2000-11-12', 'Aklampa', 'Béninoise', 'male', 0, '[\"2290152114360\"]', '2025-09-18 11:59:31', '2025-09-18 11:59:31'),
(236, 'ATCHABI', 'OLOUWAKEMI Diane', 'atchabidiane@gmail.com', '2001-06-08', 'Ketou', 'Béninoise', 'female', 0, '[\"0166139555\"]', '2025-09-19 08:09:09', '2025-09-19 08:09:09'),
(237, 'SESSOU', 'Sadeck', 'geofidsdk@gmail.com', '2000-01-24', 'Cotonou', 'Béninoise', 'male', 0, '[\"0167469342\"]', '2025-09-19 08:28:33', '2025-09-19 08:28:33'),
(238, 'GNAHOUI', 'Sèdami Cadnel', 'gnahouisedamicadnel@gmail.com', '1990-11-03', 'Parakou', 'Béninoise', 'male', 0, '[\"+229 0167689761\",\"+229 0152269444\"]', '2025-09-19 09:32:38', '2025-09-19 09:32:38'),
(239, 'ABLET', 'Coovi Jules', 'abletjules@gmail.com', '1981-10-13', 'TOVIGOME', 'BENINOISE', 'male', 0, '[\"002290195840382\\u00e0  - 0029290196728233\"]', '2025-09-19 18:59:47', '2025-09-19 18:59:47'),
(240, 'LALEYE', 'Marcellus Luc-Oberon Mouléro', 'laleyemarcellus@gmail.com', '2002-01-31', 'Cotonou', 'Bénin', 'male', 0, '[\"0166433912\",\"0164307887\"]', '2025-09-20 13:56:19', '2025-09-20 13:56:19'),
(241, 'KPATCHIA', 'Pawes\'Pana Abdoul\'Hafiz', 'pawespana@yahoo.com', '2008-04-21', 'TORI-BOSSITO', 'BENINOISE', 'male', 0, '[\"0191757572\",\"0194073136\"]', '2025-09-20 15:18:33', '2025-09-20 15:18:33'),
(242, 'IMOROU', 'Anassy', 'imorouanassy@gmail.com', '1997-12-01', 'Banikoara', 'Béninoise', 'male', 0, '[\"0153191312\"]', '2025-09-22 11:51:05', '2025-09-22 11:51:05'),
(243, 'NOBIME', 'Désiré Patrick Gbènoukpo', 'big.gpatrick@gmail.com', '1990-03-06', 'Cotonou', 'Bénin', 'male', 0, '[\"0197167726\"]', '2025-09-22 15:25:26', '2025-09-22 15:25:26'),
(244, 'DJOSSOU', 'Dagbégnon Bernard', 'djossoub69@gmail.com', '1995-11-13', 'Akpro missérété', 'Beninoise', 'male', 0, '[\"0197308082\"]', '2025-09-22 18:50:48', '2025-09-22 18:50:48'),
(245, 'ISSIOLOU', 'LABISSI YEKINI', 'yekiniissiolou@gmail.com', '1992-10-31', 'Pobè', 'Béninois', 'male', 0, '[\"0167822446\"]', '2025-09-23 17:28:38', '2025-09-23 17:28:38'),
(246, 'YENOU', 'Sègbégnon Bertran', 'bertranyenou66@gmail.com', '1998-09-06', 'Porto-Novo', 'Béninoise', 'male', 0, '[\"0166151996\"]', '2025-09-26 11:06:48', '2025-09-26 11:06:48'),
(247, 'HOUEKPODIGNI', 'MAWENAN ARNAUD', 'arnaudma.houekpodigni@gmail.com', '1995-04-14', 'Pobè', 'Béninois', 'male', 0, '[\"0167921840\"]', '2025-09-26 11:31:33', '2025-09-26 11:31:33'),
(248, 'DOHOU', 'Adolphe', 'dohouadolphe4@gmail.com', '1989-12-28', 'SEME-PODJI', 'Béninoise', 'male', 0, '[\"0196424921\",\"0195823147\"]', '2025-09-29 10:44:20', '2025-09-29 10:44:20'),
(249, 'GOGOHUNGA', 'Marc', 'marcgogohunga2@gmail.com', '2000-04-11', 'Gouka', 'Béninoise', 'male', 0, '[\"0161071227\",\"0141479304\"]', '2025-10-02 12:48:25', '2025-10-02 12:48:25'),
(250, 'ADJOVI', 'Edmond', 'adjoviedmonde9@gmail.com', '1992-11-21', 'AKASSATO', 'Béninois', 'male', 0, '[\"0167520571\"]', '2025-10-02 14:38:50', '2025-10-02 14:38:50'),
(251, 'DANTODJI', 'Nestor', 'dantodjinestor@gmail.com', '1995-12-04', 'Dévé', 'Bénin', 'male', 0, '[\"0196503152\"]', '2025-10-02 20:50:19', '2025-10-02 20:50:19'),
(252, 'MINANNON', 'Rodrigue komagbagbe', 'rminannonsoneb@gmail.com', '1997-07-31', 'Lalo', 'Béninoise', 'male', 0, '[\"01 61 92 37 50\",\"01 65 84 06 29\"]', '2025-10-03 07:55:42', '2025-10-03 07:55:42'),
(253, 'DJEMEKO', 'Marius', 'djemekomarius96@gmail.com', '1996-10-06', 'ALLADA', 'Béninois', 'male', 0, '[\"0166611016\"]', '2025-10-03 10:16:47', '2025-10-03 10:16:47'),
(254, 'KPAOU', 'Zakiatou', 'zaki181104@gmail.com', '2004-11-18', 'Kouandé', 'Beninoise', 'female', 0, '[\"0197560574\",\"0161407400\"]', '2025-10-03 15:16:46', '2025-10-03 15:16:46'),
(255, 'ADJINAN', 'Sèyivè Franck', 'seyivfranckadjinan@gmail.com', '1999-11-25', 'Cotonou', 'Béninoise', 'male', 0, '[\"0196758936\",\"0147033717\"]', '2025-10-03 22:32:59', '2025-10-03 22:32:59'),
(256, 'AGBAHOUNGBA', 'Roseline Reine Sede', 'rosereiagbahoungba@gmail.com', '1989-09-03', 'Porto-Novo', 'Béninoise', 'female', 0, '[\"0161675354\"]', '2025-10-06 15:07:26', '2025-10-06 15:07:26'),
(257, 'Brun', 'Emeth Esdras', 'emethbrun29@gmail.com', '1996-04-29', 'Cotonou', 'Bénin', 'male', 0, '[\"0166523629\"]', '2025-10-07 08:22:40', '2025-10-07 08:22:40'),
(258, 'BACHABI', 'Abdoul zoul Djalal walhikirane', 'walhikiran@gmail.com', '2000-08-08', 'Angaradebou', 'Béninois', 'male', 0, '[\"+229 0194759793\",\"+229 0196353677\",\"+229 0149811924\"]', '2025-10-07 19:01:00', '2025-10-07 19:01:00'),
(259, 'ODOUNLAMI', 'Finagnon Nataniel', 'natodounlami@gmail.com', '1978-08-26', 'Porto Novo', 'Béninoise', 'male', 0, '[\"+229\\/  01 97 64 61 22\"]', '2025-10-08 16:55:40', '2025-10-08 16:55:40'),
(260, 'SEMEVO', 'Sèdami Irisda Josoué', 'josouesemevo96@gmail.com', '2003-10-25', 'Bohicon', 'Béninoise', 'male', 0, '[\"0149452078\\/0152130616\"]', '2025-10-09 11:05:47', '2025-10-09 11:05:47'),
(261, 'AMOUZOUN', 'Géoffroy', 'amouzoungeo@yahoo.com', '1994-10-27', 'DOGBO', 'Béninoise', 'male', 0, '[\"0161211163\"]', '2025-10-09 11:35:49', '2025-10-09 11:35:49'),
(262, 'AMEHOUNKE', 'TANGUY', 'tanguyamehounke@gmail.com', '1993-10-23', 'COME', 'BENINOISE', 'male', 0, '[\"0162595826\"]', '2025-10-09 14:42:28', '2025-10-09 14:42:28'),
(263, 'HOUNYE', 'SESSINOU HERMANN', 'hounyehermann56@gmail.com', '1993-01-01', 'ABOMEY-CALAVI', 'BENINOISE', 'male', 0, '[\"0161140361\"]', '2025-10-09 14:52:29', '2025-10-09 14:52:29'),
(264, 'QUENUM', 'Martial', 'qmartial173@gmail.com', '1989-11-01', 'Cotonou', 'Béninoise', 'male', 0, '[\"0196956037\",\"0194849192\"]', '2025-10-11 18:33:49', '2025-10-11 18:33:49'),
(265, 'YORO FIKO', 'Baroka Boris', 'yorofikoboris@gmail.com', '1989-04-30', 'Kotopounga', 'Béninoise', 'male', 0, '[\"0197750156\"]', '2025-10-12 13:08:17', '2025-10-12 13:08:17'),
(266, 'MEGNANHOU', 'Fiacre', 'megnanhou@gmail.com', '1989-08-30', 'Lokossa', 'Béninoise', 'male', 0, '[\"0196813193 \\/ 0195737257\"]', '2025-10-12 14:24:10', '2025-10-12 14:24:10'),
(267, 'ACCROMBESSI', 'Alfred Fernel Junior', 'accrombessialfred2@gmail.com', '2006-02-26', 'Cotonou', 'Béninoise', 'male', 0, '[\"0156027215\"]', '2025-10-12 20:25:28', '2025-10-12 20:25:28'),
(268, 'ATCHOUA', 'Fabrice', 'fatchoua3@gmail.com', '1988-09-16', 'Bembereke', 'Bénin', 'male', 0, '[\"0196881800\"]', '2025-10-13 16:26:23', '2025-10-13 16:26:23'),
(269, 'GANDE', 'Nounagnon Aristide', 'aristgand@gmail.com', '1994-08-31', 'Porto Novo', 'Béninois', 'male', 0, '[\"0166265456\",\"0143487635\"]', '2025-10-14 14:46:59', '2025-10-14 14:46:59'),
(270, 'KOUADIO', 'KOUADIO SERGE LUCIEN', 'sergelucienkouadio2012@gmail.com', '1985-10-01', 'Kabehoa C/GUIBEROUA', 'Ivoirienne', 'male', 0, '[\"01 56 02 00 06\"]', '2025-10-14 14:50:14', '2025-10-14 14:50:14'),
(271, 'MITONDE', 'Bidossèssi Déo-Gracias', 'mitondedeogracias@gmail.com', '2003-01-24', 'Sèhouè', 'Béninoise', 'male', 0, '[\"01 60 93 86 27\"]', '2025-10-15 08:48:00', '2025-10-15 08:48:00'),
(272, 'HOUNDONOUGBO', 'Kpessou Marie Lourdes', 'houndonougbokpessou@gmail.com', '2003-02-11', 'Cotonou', 'Béninoise', 'female', 0, '[\"01 96 61 11 51\"]', '2025-10-15 09:07:27', '2025-10-15 09:07:27'),
(273, 'BOVIS', 'Léos Farel-Rey', 'bovisleos@gmail.com', '2001-07-28', 'Cotonou', 'Béninoise', 'male', 0, '[\"0143756785\",\"0151896081\"]', '2025-10-15 12:34:20', '2025-10-15 12:34:20'),
(274, 'GBONDJE', 'Houélété Emmanuel', 'gbondjeemmanuel@gmail.com', '1984-12-25', 'Lokossa', 'Beninoise', 'male', 0, '[\"0197617331\"]', '2025-10-16 10:29:50', '2025-10-16 10:29:50'),
(275, 'VIAHOUNDE', 'Dieu-donné Adolphe', 'adovhd56@gmail.com', '2005-02-13', 'Porto-Novo', 'Bénin', 'male', 0, '[\"0156173146\"]', '2025-10-17 07:53:28', '2025-10-17 07:53:28'),
(276, 'KANLIDOGBE', 'Innocent Satingo Samuel', 'Kanlidogbesamuel1@gmail.com', '1999-03-17', 'Cotonou', 'Béninoise', 'male', 0, '[\"0161961996\"]', '2025-10-17 08:49:22', '2025-10-17 08:49:22'),
(277, 'AMOUSSOUGBO', 'IFEDELE JEAN EBENEZER', 'ifeamoussougbo@gmail.com', '2001-06-27', 'Cotonou', 'Bénin', 'male', 0, '[\"0151226967\"]', '2025-10-17 11:17:38', '2025-10-17 11:17:38'),
(278, 'AKODEA', 'Sèlonwan Christ-Pain Emmanuel', 'akodeaemmanuel@gmail.com', '2001-03-12', 'Ouèssè Savalou', 'Béninoise', 'male', 0, '[\"0191832838\",\"0143672484\"]', '2025-10-17 17:11:16', '2025-10-17 17:11:16'),
(279, 'KOUDJINOU MALE', 'Bienvenu Abel', 'bienvenuabel@gmail.com', '1995-10-30', 'SAHOU DJAKOTOMEY', 'Béninoise', 'male', 0, '[\"0167249754\",\"0140108734\"]', '2025-10-18 15:20:39', '2025-10-18 15:20:39'),
(280, 'ANANI KOBELE', 'Mathieu', 'mathieuanani57@gmail.com', '1996-12-16', 'Ouidah', 'Béninoise', 'male', 0, '[\"0166387601\"]', '2025-10-20 09:42:59', '2025-10-20 09:42:59'),
(281, 'ODADE', 'Essegbede Modeste', 'odademodeste@gmail.com', '1985-07-12', 'Glazoue', 'Beninoise', 'male', 0, '[\"0167054163\"]', '2025-10-20 09:59:59', '2025-10-20 09:59:59'),
(282, 'YESSOUFOU', 'Mohamed Saïd Olawolé', 'olawoleyessoufou@gmail.com', '1994-05-16', 'Cotonou', 'Béninoise', 'male', 0, '[\"0196726578\"]', '2025-10-20 22:01:54', '2025-10-20 22:01:54'),
(283, 'DANSOU', 'Marc Joël', 'marcjoeldansou@gmail.com', '2004-01-01', 'GODOMEY', 'Béninoise', 'male', 0, '[\"0169743133\"]', '2025-10-21 15:27:22', '2025-10-21 15:27:22'),
(284, 'DOHOUNGBO', 'Merveille de Dieu', 'Merveillededieudohoungbo@gmail.com', '2004-01-13', 'Kpomassè', 'Béninoise', 'female', 0, '[\"0141575680\"]', '2025-10-22 08:44:15', '2025-10-22 08:44:15'),
(285, 'AIHOUNDA', 'Mahugnon Sylvestre', 'saihounda@gmail.com', '2000-06-18', 'Parakou', 'Béninoise', 'male', 0, '[\"0160901807 \\/ 0157765862\"]', '2025-10-22 08:58:53', '2025-10-22 08:58:53'),
(286, 'OSSAH', 'SOTORIA JESUGNON BENEDICTE', 'sotoriaossah@gmail.com', '2003-03-07', 'PORTO-NOVO', 'Bénin', 'female', 0, '[\"0197994293\"]', '2025-10-22 11:13:34', '2025-10-22 11:13:34');

-- --------------------------------------------------------

--
-- Table structure for table `professors`
--

CREATE TABLE `professors` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `rib_number` varchar(255) NOT NULL,
  `rib` varchar(255) NOT NULL,
  `ifu_number` varchar(255) NOT NULL,
  `ifu` varchar(255) NOT NULL,
  `bank` varchar(255) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `grade` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `programs`
--

CREATE TABLE `programs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `classe_id` bigint(20) UNSIGNED NOT NULL,
  `course_professor_id` bigint(20) UNSIGNED NOT NULL,
  `semester` varchar(255) NOT NULL,
  `credit` int(11) NOT NULL,
  `pond_session_normale` double NOT NULL,
  `pond_session_rattrapage` double NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reclamation_periods`
--

CREATE TABLE `reclamation_periods` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `academic_year_id` bigint(20) UNSIGNED NOT NULL,
  `department_id` bigint(20) UNSIGNED NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`permissions`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `permissions`, `created_at`, `updated_at`) VALUES
(1, 'chef_cap', NULL, '2025-06-20 12:14:06', '2025-06-20 12:14:06'),
(2, 'chef_division', NULL, '2025-06-20 12:14:06', '2025-06-20 12:14:06'),
(3, 'comptable', NULL, '2025-06-20 12:14:06', '2025-06-20 12:14:06'),
(4, 'secretaire', NULL, '2025-06-20 12:14:06', '2025-06-20 12:14:06'),
(7, 'etudiant', NULL, NULL, NULL),
(8, 'responsable', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `name` varchar(255) NOT NULL,
  `is_defense_room` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('0aliPuYE4X88tqhYLUcZOVzaBPvdjCJfUDY0ncmR', NULL, '156.0.213.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiNm1qZDZMUktYOHFISkgwejlrNGM4RmZqRjhWR1BUNDhpRzcxMFdwaCI7czo1OiJlcnJvciI7czozODoiVmV1aWxsZXogdm91cyBjb25uZWN0ZXIgdmlhIGxlIHBvcnRhaWwiO3M6NjoiX2ZsYXNoIjthOjI6e3M6MzoibmV3IjthOjA6e31zOjM6Im9sZCI7YToxOntpOjA7czo1OiJlcnJvciI7fX1zOjk6Il9wcmV2aW91cyI7YToxOntzOjM6InVybCI7czo0OToiaHR0cHM6Ly9pbnNjcmlwdGlvbi5jYXAtZXBhYy5vbmxpbmUvc3R1ZGVudHMtbGlzdCI7fX0=', 1760447173),
('1J4EWNIWDVzZfrTUm8LaooUbbBQWh7MbDy5ACI4S', NULL, '41.85.162.23', 'Mozilla/5.0 (Linux; Android 11; Infinix X6511B Build/RP1A.201005.001; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/141.0.7390.97 Mobile Safari/537.36 Vinebre', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiM2NpS3ozRjl5clBxSGVVRFN4SnY4M3p0NTNtYnVEWUZsMkxxNEt3MSI7czo1OiJlcnJvciI7czozODoiVmV1aWxsZXogdm91cyBjb25uZWN0ZXIgdmlhIGxlIHBvcnRhaWwiO3M6NjoiX2ZsYXNoIjthOjI6e3M6MzoibmV3IjthOjA6e31zOjM6Im9sZCI7YToxOntpOjA7czo1OiJlcnJvciI7fX1zOjk6Il9wcmV2aW91cyI7YToxOntzOjM6InVybCI7czo0OToiaHR0cHM6Ly9pbnNjcmlwdGlvbi5jYXAtZXBhYy5vbmxpbmUvc3R1ZGVudHMtbGlzdCI7fX0=', 1761073946),
('2UaRCdadFeLejubym7fLr6ayknFMVLAQchqoAIv9', NULL, '2a02:4780:27:c0de::3cc', 'Go-http-client/2.0', 'ZXlKcGRpSTZJbTQzTmxkMWMxQkdVMlZMTTNNdk5GcDJOMmgxVmxFOVBTSXNJblpoYkhWbElqb2lNbXN5Y1ZSM1pFNHlXVXR2UVdGaU1HRlpWR3ByZDFWQldsVlBMelkxTm1Sb1VXbEpTQ3ROWlUxUlFsWm9jR2xuVlRoMVlreEJWekJ0V2pOdGRXRllWMWROY0ZoblFsQlZabEo2Y21GNU1WbDBaMEZLWlhCTVpVVkZOak5CWTI5eFdWQkZlVlJOWnpZeFVFb3ZSblp4WVhSemRHZ3JWR1JQV0c5VFVUWkRSbTlNUzFWQ2FIRm5kWEpyUmpaRk5rZExhbFZ5VVdaUVVUZHRhMGxXTkVnMU9EUnBNMFJMTm1rNFQwSnFjMmN5S3pWUEwwNVBXRTF6Wkhvd1VGaEJTekUyVHpWVE5EZ3JkVGxQUWtGUE1VcEhURGRMYW5CdVpWVnVja0pzUW5Vd2JGVTFXbFZMV1hwbk9YaHJSREJZVFdONlpuWkJjMWx6YTA5bWEwdG1ZV2hvV1NJc0ltMWhZeUk2SWpJNU5EbGpNbUptWVROaE56azNObVU1TXpZM04yUm1ZMlUwTWpsbFpHSTVZamxrTURKbU1qRTFaVFJtTnpVd01qWXhabU5qWkdZMU16UmhZbVptTkRRaUxDSjBZV2NpT2lJaWZRPT0=', 1760529269),
('38MqjrBWse6Mqe086LbhXUrgV2eVbKnAPZfJ9ROr', NULL, '2a02:4780:27:c0de::3cc', 'Go-http-client/2.0', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiR3hMbDlPaGRuRXA4Ym9IbUJYM3ExazU1eE9IS2wzREZuWlh4YkI2dyI7czo1OiJlcnJvciI7czozODoiVmV1aWxsZXogdm91cyBjb25uZWN0ZXIgdmlhIGxlIHBvcnRhaWwiO3M6NjoiX2ZsYXNoIjthOjI6e3M6MzoibmV3IjthOjA6e31zOjM6Im9sZCI7YToxOntpOjA7czo1OiJlcnJvciI7fX19', 1760438861),
('4JTVIBaJcIMYMSfXbbsoWwOPdgyFD9Rx1JqH3Tu9', NULL, '66.249.66.197', 'Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/99.0.4844.84 Mobile Safari/537.36 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiakxGNXRMclRHb01FdUJKeFVGQkRDRDhwVTdaVGdkalhBcDVpN2tNbSI7czo1OiJlcnJvciI7czozODoiVmV1aWxsZXogdm91cyBjb25uZWN0ZXIgdmlhIGxlIHBvcnRhaWwiO3M6NjoiX2ZsYXNoIjthOjI6e3M6MzoibmV3IjthOjA6e31zOjM6Im9sZCI7YToxOntpOjA7czo1OiJlcnJvciI7fX1zOjk6Il9wcmV2aW91cyI7YToxOntzOjM6InVybCI7czozNToiaHR0cHM6Ly9pbnNjcmlwdGlvbi5jYXAtZXBhYy5vbmxpbmUiO319', 1760940670),
('5Xudud4a2ONaXcbXeHP3K5YL6xtZZmkggiG2jpSz', NULL, '2a02:4780:27:c0de::3cc', 'Go-http-client/2.0', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiSG5rNVF0TjhUR3pHOXFUeE9VWFRzWm8zNHFwaFNnbXhHWEdpelpDWCI7czo1OiJlcnJvciI7czozODoiVmV1aWxsZXogdm91cyBjb25uZWN0ZXIgdmlhIGxlIHBvcnRhaWwiO3M6NjoiX2ZsYXNoIjthOjI6e3M6MzoibmV3IjthOjA6e31zOjM6Im9sZCI7YToxOntpOjA7czo1OiJlcnJvciI7fX19', 1760921336),
('6Rkjl3ZfdHsGvISLlHgntUpAex5lXoIPyLgZBlmh', NULL, '34.1.28.215', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', 'ZXlKcGRpSTZJakJXY0U5MVMweHhSWFZoTkc5Vk1WVTBSSGRJTTBFOVBTSXNJblpoYkhWbElqb2lhMGhRY0dsVlVHTTBaM2dyY0c5TldVaGlPRzk2THpCc2JGVXlaMjFFUlVsbVRVUmtVbko2U1RkMk1sTXlObWxUV1RJeVRrZEpaazQ0ZG5nM1NqRm9jVmxLSzFRNWNURnliVWgyZVZCU1VVcHVlVFpxT0RGeU9GWlhibnBsWlhOV1ZGQnJhRTE2VFdRd1NFWmxXbXBzVlZKemVWUm1SR3hXTm5kRGRVdElNVnAxYjFNNWFHWjFVVloxYTB0eFpHWlNjbE51YlZCdGRFZFdOakZ1Ym0xMk1FRXdTVUZ3TW1KbGQxUmpkM0p3YUdSUFlrSnZkMGxOZEZOd1dHVlNVbXRxWkRSalRETTJURU5DY0Vrd2FYWk1aMGhZVUV3d05XdFlLMWc1U2xWQ09XOUpNVzVvVVZWUFZHa3dUbWhQZEhGaFUzZzRSV2hMWlc0dk5IbEJXR05QV0RWSmRsTTNhMmRaZGxnNWJuZExhV05QVm5kTFZFcFVXVTFPZDFaRVRYcEpSbWxGWTBOWk4yUjVOWFJyVjNsVVZ6VmplWFkzUzI4d1MwRXZURzF0WTFaMGRpOXJNRlpxUW1FdlUxaGlNSE50VkZoaGIwMURWVVowT1VScVl6QktUMEYwYWxrMU1VTTVhR3haUFNJc0ltMWhZeUk2SW1NMllXVTBZemhsWm1RMU56Z3pPR1kzTXpWaE5UVXhPRFl4T0dSak5ESTBPVFEyTjJSbFpqUmpOamMzWVRSak5UUTVORGM0TlRReE9XVTNabVUyWlRraUxDSjBZV2NpT2lJaWZRPT0=', 1761135153),
('7G6lAiJKEnnUO7tMPg1eHI4OCNAAunaRbIzmy1iM', NULL, '185.72.144.171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.37 (KHTML, like Gecko) Chrome/118.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiZGZ1Z3lYc0g3M09YMGg5TmFEb0ZETXlzZEx2RnQzcnBGSmtPVDJwYyI7czo1OiJlcnJvciI7czozODoiVmV1aWxsZXogdm91cyBjb25uZWN0ZXIgdmlhIGxlIHBvcnRhaWwiO3M6NjoiX2ZsYXNoIjthOjI6e3M6MzoibmV3IjthOjA6e31zOjM6Im9sZCI7YToxOntpOjA7czo1OiJlcnJvciI7fX1zOjk6Il9wcmV2aW91cyI7YToxOntzOjM6InVybCI7czozNToiaHR0cHM6Ly9pbnNjcmlwdGlvbi5jYXAtZXBhYy5vbmxpbmUiO319', 1760882157),
('7uI7cpD4DkwJxAKSk6ZvYYx5BDfNdxNFEGQSxH0A', NULL, '41.216.53.162', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiSUdLT3hrR3VJYUtXWkcxMEFCSkN6ZGR1eUg2VzhQQTFVVzRNSTRGRiI7czo1OiJlcnJvciI7czozODoiVmV1aWxsZXogdm91cyBjb25uZWN0ZXIgdmlhIGxlIHBvcnRhaWwiO3M6NjoiX2ZsYXNoIjthOjI6e3M6MzoibmV3IjthOjA6e31zOjM6Im9sZCI7YToxOntpOjA7czo1OiJlcnJvciI7fX1zOjk6Il9wcmV2aW91cyI7YToxOntzOjM6InVybCI7czo0OToiaHR0cHM6Ly9pbnNjcmlwdGlvbi5jYXAtZXBhYy5vbmxpbmUvc3R1ZGVudHMtbGlzdCI7fX0=', 1760686218),
('7xoUexD9zCHGqBz2DwVO5m6bag6cAvIGTLhpR8fi', NULL, '2a02:4780:27:c0de::3cc', 'Go-http-client/2.0', 'ZXlKcGRpSTZJbWs0UVVoQmNpOU9UMDluUlVreFFtNWFka3RPYzFFOVBTSXNJblpoYkhWbElqb2lNR0ZJUWxKMU0wWlBWRlJpWVRoNU1FdGtOR1V5YXpGMk5EbFBSV2xZZEVFeVpuRnFjVEEwTTIxeksyeE5NM0JIZURkWE9FUnJkemd3ZGxOMVRXUnZhbGhXTUM5Wk1XOURkVEU0Y21KbGNteExUR1J0UjFSb1NYUlRUVFpzZFVkTmRXMHJSRVJGYkN0bE1VcHFPWFkzZDJOcVlUZDVibmdyUTNsUVJHSmhjazVYTkU5Q1lrMTRkMXBPU1ZwR2FFZHdZak5hZDBWbU1uSXJaR053Y0V4UGNXOHliR2RIVUhGeE5rOVVNMHh6TVZGUlNrTjNkMjRyYTA5Wk1YWmFVVVFyS3psNlNEaGtZV1V6VjNWT2EyWmpUbFpPY2xwd2RXVklUR05LZVdaelRrRlRiMnMzUVVKd09HeFVaRlZYVUcxb1psUnBjbVpwVlV4ek5GTlVRMGhJUkVzd1drcExjRGRwUkZCTlNFTlhlVkpYU2pKT05VRTlQU0lzSW0xaFl5STZJak5rWXpKbE9UZGxZakl3WkRZd05HUXhObU00TldFd016VXpaREl3TnpkaE5tWXlaVEppTUdVMVkyRTRNV0V5TURFek1tRmhNRFJsTVdJNE5EQmpZelVpTENKMFlXY2lPaUlpZlE9PQ==', 1761180030),
('8M7vR7EnyL1LhggcD55vdEPtOtPfkSPy1HAsIElu', NULL, '205.169.39.25', 'Mozilla/5.0 (Windows NT 10.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/106.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiZ21TM2htTks1WmoyTVYxRXBtazNWWklVMW9qakpSTGNGdnV0SktVRyI7czo1OiJlcnJvciI7czozODoiVmV1aWxsZXogdm91cyBjb25uZWN0ZXIgdmlhIGxlIHBvcnRhaWwiO3M6NjoiX2ZsYXNoIjthOjI6e3M6MzoibmV3IjthOjA6e31zOjM6Im9sZCI7YToxOntpOjA7czo1OiJlcnJvciI7fX1zOjk6Il9wcmV2aW91cyI7YToxOntzOjM6InVybCI7czozNToiaHR0cHM6Ly9pbnNjcmlwdGlvbi5jYXAtZXBhYy5vbmxpbmUiO319', 1760945308),
('9L4ziTP91wJt44y3nwXzKTwZim45Nmbqxugfb5eQ', NULL, '34.79.244.253', 'Mozilla/5.0 (compatible; CMS-Checker/1.0; +https://example.com)', 'ZXlKcGRpSTZJa3hoVFU1UmVWVnJXSEJ2YjBsUmJrbEVVWGM1WkhjOVBTSXNJblpoYkhWbElqb2lSRFp4WWtaalFYTk9kMWRJTDFWWmJHeG5kMmh2Vm5SNFJEaFphMHhRT1dWRVMwOUxWVFZLYkc5ak0yMVpLMjUwYmtkSkwyeENabFpZUTBkcFRHSnpMeloxWVVGVWJYcEJTQ3Q1WjFSSGMxWlFNRk0zTkZadFZIWm1RVTkxWnpaU1dtMXBibk0zVWxoalNqaG1ZelkwZGxocmEwVkdlU3RFU1d4cGNVODNPVUZuWmxnMFVtczVRbUpMUjNKMU1YQXhNMVJIU1VSeGJGUkdjV1ZSVlZsRlRUazJWSFpaUm5aTGJEZEhiMEp5TUVOWWNtSlVla3cyVjJkWU9EaGxSRWhUV2s5Nk4wSm1LMHM0TDBKdVVFbDFhM0JVT0ZsemJUQmpNbU5yUm5kT05TODBUMnQ2TDBobVoxRXhhRGxOYjJGc0sxSXhlamx0UjJSRmQwTnROVll3ZDB4TWVXdElUakoxWkVZNGFuUndXSEppUVVocmFsbFlWWGR4UlhGblJ6Vk5MMVJZT1haTGREbHZjM2hPUkZKeGFrNXVhVk13Y1hCU1VXVkdaa0pzVlZjMVFtNDNRVVV3VVVWWlNFbHFOMGR1WmxoME4wdFRlVGRLY1ROWlMwbzVPRzU1ZDBjd2JFTmhXV1ZyZEhWRk5YaG9kVEp3ZG5SWWN6YzRabnBISzJJMElpd2liV0ZqSWpvaVl6STFOVEk1TjJNMk1tWTVNbU0yWVdJNE5tSmlPRGM0WWpnek1UTXhaREE1TmpobVltWmtNakJtWXpBeU56TmpNVEZsTldabFpEVTFZV00wTVRJellTSXNJblJoWnlJNklpSjk=', 1760718915),
('anrpNjiZtDNKFPaB1eKTbh2gFZYmTjUHkrtmZz1d', NULL, '2a02:4780:27:c0de::3cc', 'Go-http-client/2.0', 'ZXlKcGRpSTZJbk5XVm5WNFkwZHROSEoxVjJkMGNXeFVLMlp1TjFFOVBTSXNJblpoYkhWbElqb2lXVWd4VFhGaFUxY3llWEpQWXpoM0swRjRWbVZ6Y0dVd2QycDZSamx2YURsSVVuUnRkMGw1UTNOa01uSnJPR1V3YzBoclRrWklRbk5xVTFsWU9UZGxkbWh3Y21sNGJqRnBTWEJsTVZaVlluWnlVMHRzVFVkd01GVlBTMkZPVm5aa1JtVTRkR1JMU205NWF6aEVWSEoxU0VadFdTdERlbUoyYTFJMlJrbFVNVmR5WlU1NlNXeHVORGxyZEdFelNEVTFSR0pOU1U1NVltaHlURmcxUzNCS2JHWm5UVFpSYlVKcE9YaDFaVEZ4VGtjd1ZVdzFMM0ZKUTFSR1luZElhRWRVVFRGTlVXSmpZa2xvYmpGNVV6RnpkUzlZVm5Zd0t6bHRialoyUlVsSFFsaFJjVnBEWXpFNGMwWjJUeXRNSzFKVkx6Rk9XRUZUWjJsUlFVRkdkRGhLTjFkT1ZXRm5jbUZCTkdRNVZsY3lhRVZPYkZGelFrRTlQU0lzSW0xaFl5STZJakU1WVdRMVpXUTFaamhoWVRrNVkySmlPVEZrTm1ZMk16Z3dZV0l5WTJKbU5XVTFaVGhrTURVeVpUQXpZV1EyT0dRMllqVmpZemRrWmpsaVlqa3lOemNpTENKMFlXY2lPaUlpZlE9PQ==', 1760588455),
('ATuQqVAHhUWohIGXA78kmIlM7gmF1o2sFv2e6IRQ', NULL, '35.185.63.144', 'Mozilla/5.0 (compatible; CMS-Checker/1.0; +https://example.com)', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiVWl5NUd5YWhkRDdOU2xwTWp6dlZwU29jWDBPbkhMcnpFam1WNzl5ZiI7czo1OiJlcnJvciI7czozODoiVmV1aWxsZXogdm91cyBjb25uZWN0ZXIgdmlhIGxlIHBvcnRhaWwiO3M6NjoiX2ZsYXNoIjthOjI6e3M6MzoibmV3IjthOjA6e31zOjM6Im9sZCI7YToxOntpOjA7czo1OiJlcnJvciI7fX1zOjk6Il9wcmV2aW91cyI7YToxOntzOjM6InVybCI7czozNToiaHR0cHM6Ly9pbnNjcmlwdGlvbi5jYXAtZXBhYy5vbmxpbmUiO319', 1760535242),
('B4cdWdzZKDmUz5wtFTie7B45Xq87eCsD7CzR0GSl', NULL, '41.85.163.6', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoialFjd2pCeUlHbkhxaGwzOHZlYXhySUpBTWRhaFpUNUU1TWRmZklCWCI7czo1OiJlcnJvciI7czozODoiVmV1aWxsZXogdm91cyBjb25uZWN0ZXIgdmlhIGxlIHBvcnRhaWwiO3M6NjoiX2ZsYXNoIjthOjI6e3M6MzoibmV3IjthOjA6e31zOjM6Im9sZCI7YToxOntpOjA7czo1OiJlcnJvciI7fX1zOjk6Il9wcmV2aW91cyI7YToxOntzOjM6InVybCI7czo0OToiaHR0cHM6Ly9pbnNjcmlwdGlvbi5jYXAtZXBhYy5vbmxpbmUvc3R1ZGVudHMtbGlzdCI7fX0=', 1761067450),
('btNGtZbPT3fM5BtYWQtNLAzISAAJcUalOujzq4Jr', NULL, '2a02:4780:27:c0de::3cc', 'Go-http-client/2.0', 'ZXlKcGRpSTZJamR3TjIxQ2NEaG1PRlZPYW1vdlFYUXdhbUkyT1djOVBTSXNJblpoYkhWbElqb2lPV1ZCWVVSWlV6Y3ZWV1J4VW5Sb1RIVnlObTFIVFRoTmJEbDRXbU55U1hkdFRIUTJiRkZuY0VSWE5VeGtVbEZZZFZoVFNWRjFRek5HTmpCRk1uVnlXbmRLVTFkNFNWVjNiVVIxTkVFdmVGUjNUQzg1T1RGQ2NHdzBZa3RPWkVaWE9XZEhRbTg0YWpBMmJXZHJlSGwzTDI1M1R6WjBTRVZUV1dGbFUwOHdNV1ZIVWk4eU4yVTJWQ3RVYWxWelpGTTNRa042Wm00dlFTODJTbVJUZVVnMGJXNURiU3N4TVVwTlp5dHlUbVJHWW5wUGVIazJTMnBpVFVObFp6TnRLM0pxZDB4NmR6TmthVmhsVlhKcVUwaDJTa3N2UlhsaWN6WlFXQzlPVG5sRVdHWlpMM1J0TTFaR2VEaGpMM1kxT0Nzek9XdzFiVk4zVVc1NVlsWjVkVEU0U0hSTFMwaE1kMEk1UW5jM2FGVTJSRUppYWl0blFtYzlQU0lzSW0xaFl5STZJalEzTW1Vd1pqTXlZbU13TkdWalptUXdZV000TjJSaVpHUTBZbU16TVRNMVpXUm1aVEE0T0dFM1pqZzRNakJqT0RZNFpUWmhNVE14TnpNME5HTXdNVElpTENKMFlXY2lPaUlpZlE9PQ==', 1761129013),
('BW8ofrOedlb4d2N1U0RlXO2glWJAmJmeoQjhv1An', NULL, '2a02:4780:27:c0de::3cc', 'Go-http-client/2.0', 'ZXlKcGRpSTZJazExVGxwQk5sVTRTVVJqTm1oQ2NsQktlSEZ1VkdjOVBTSXNJblpoYkhWbElqb2lUWFp5T0ROMlduSkxZak5oY0V4MFIzZDBTbGxGYmtKcVduSnVVekJrWlU1WVJGcDRhMjEyWlRSMksxWlFSMkZTTlM5WldWRm5ZVVl3YW0xNlZFNXhXbTl4WW5odmVtZHZWRmQ2VDNsTFpYcEpiVVVyTTFGTlRESTNNbVY0YzI1bFJFeG5jbXBXTkRSRlIwb3pRMDVTUjFoRldtSnZMM1pqU2poRGF5dFFhbWhaWkZCb1FVWndXbE5SU0ZOTlQzZEpTakZhTVRkUGMxUm1jR0pFTVZKblMyZHJjazFFTWtKTllYQjFVa3RJZVRaek5rTk9PV1JqWjI5YVdrRjRSakpXUVZwVlptdFBibkJVWTI5UE1ERm1aM05DYUZCVVdWUTJhRTFsWkVJd1JHSnRlblZTTlRsWU9XaDBka3hZTWtkU1QzcHJlVEZLV2tSQ1EyVlNOR0ZXWnlJc0ltMWhZeUk2SWpJek16TmpaRFV3Wm1ObU1XSXpNell6TmpReU1UWXdaVEU1TURGbU1EQTFNREV3Tm1KallXSTBORGxpTUdWak5ETTJaRE5sWVRWaE1qWmhOR1JoWTJZaUxDSjBZV2NpT2lJaWZRPT0=', 1761104329),
('Ccv4D7qbVPWD0k7CwgJCKnbuxdCI13ms8ZoYxuUx', NULL, '212.127.73.178', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiMGJHdUhleENLVkUxb1NUcko5TmZoNml0bWt0RGVOZm5yc0tPMFY3QSI7czo1OiJlcnJvciI7czozODoiVmV1aWxsZXogdm91cyBjb25uZWN0ZXIgdmlhIGxlIHBvcnRhaWwiO3M6NjoiX2ZsYXNoIjthOjI6e3M6MzoibmV3IjthOjA6e31zOjM6Im9sZCI7YToxOntpOjA7czo1OiJlcnJvciI7fX1zOjk6Il9wcmV2aW91cyI7YToxOntzOjM6InVybCI7czozNToiaHR0cHM6Ly9pbnNjcmlwdGlvbi5jYXAtZXBhYy5vbmxpbmUiO319', 1760878030),
('Cw9d9MhKaQM7eSl1vr9VVv0dmL6JXesjyLGGbIDS', NULL, '2a02:4780:27:c0de::3cc', 'Go-http-client/2.0', 'ZXlKcGRpSTZJbkJtUVc1Wk4zVklSWFpZY201eE5rYzNVamRuUzBFOVBTSXNJblpoYkhWbElqb2libHA2V1ZKb2R6VllaRUYzTlRkVGFIWnBVV05XT1daMU9YSnBjMUZuYUZOc1VFaFBNMWhVYlRGMlFVaHlOMFZUTTJ3MWJXZGhhVzQ1VEZsMlRFNHJWVkJzWlVKdGRrcG5hME5qV1hsVVRtSkZNbkpaVVVoQlpqRkNkRXMxVjA0eldUQXdTRGROVlZoQmVVWlJLMlp2YlN0VWNqSmlWWFZLUVVKWmJtNHhjRlpXZFZCdmNsVjNWbTVNT0VaVE1tUm5XSHAzTWxoUFlXZ3hhWEY0TkRocU9VVmFkV3hFUVZwa2FXMUlNamhuZG05d2QwWXdWVFJJTjJwb2VqQnVhSFZKTVV0V2RpdElZM0JsV1RaclRucEVSazlDWW5CclFrODNkU3Q2V1VaU1ZXSm5SeXQ2Vm1wSVJVOWhTSEJhVnpseVdYTlphbWxaUm5WWk4wbEZTa2R0S3pCeEwwdzRVMFkwUkRseVJrRm5TVkZXVDNGU01HYzlQU0lzSW0xaFl5STZJakUwTm1KbVlXUXlNR0ZsWWpFMFlUYzBNREEwTjJRME4yTmlaV0kxWldGak5XWTJZVEl6WVdWbVltUmpOVGsyTnpJek16aG1PRFE0TXpRek1UWXhZV01pTENKMFlXY2lPaUlpZlE9PQ==', 1760765032),
('DFnVIk0wE2eJCZDlYaJoATEakej3rN8oJ32t1gCj', NULL, '34.91.104.234', 'Mozilla/5.0 (compatible; CMS-Checker/1.0; +https://example.com)', 'ZXlKcGRpSTZJbm94UkZGdEsyOUpZMEkwY2tVdlJqTk5NV1ZJU2xFOVBTSXNJblpoYkhWbElqb2ljVkZCZEV0Rlp6bHpVbnA0U2xWUWRubGtURTFRWVU5a1dubGhZVEJYVGtKb2IxaHNUWGh4V2pWWkwxSlNhRnBXWW0xc2JWQTNRMVkyYUZOR2RpOVhMM05pVEV4VVRYRkRSM2RvU0VoUllUVTFLMXBMZVhCVVNWTmFlR2h5YW5GbWQxTnpiVXg1VjBrcldHcFJia1JHYUVkaVNGTnRVRkV6ZEVSYVdWQmhTelZKWVVOSlMwUmhORk54VG5semNqaEtSbWx4TmxkQk1IbEdXRGg1UTNBemNtaEdNWGhZTjJoVU1FUTVkVVZrZUUweWRFVjRiUzlzTVhjMk9WbE9RbkZwTUVGdWRHVTRSWE53TnpkclNtTkhjU3N2YmxFeFlVTXpRVFJtT1hGeFRsZE5ZWGxyVUV4SVdXRlBkMnhSSzA4dk5YUkhiREI2U0ZOdE9HcGlaRkpKYkRkUlNYSjZOVU42WlVaVU9EZ3hiRzE0UjNKSFoyaFdVVTluYTI0d2JrTkNlbTF0VmtGaGVFMVlSa2hTVW1GNGVtMUVjRWhPV1doTmRYZGlTR051UzFaaFdsbzJPRFZ5T0dreVFqZDNiVXRaUlVwWlowUXdNRGxWUTA1T2MzQlpZbVZLVTBST2JGcEZhVWcxVlU1Mk9HbHpWRkZDVGtWbVl5OUpTbWMyVlhsUklpd2liV0ZqSWpvaVl6aGxNR0V6T1RFMVpUSXpNREV6TVdVd1pXTTVaakl3WmpGak5EaGxaR016WW1Rd1lUZ3dOR0kzWW1aak1qQXhaRGhtTm1NeE1tTmpPV0V3WVdNME15SXNJblJoWnlJNklpSjk=', 1760624051),
('Dh99UJS9Z5GXfnMmIDEUyBQ8QLde9QKxeqktYI96', NULL, '2a02:4780:27:c0de::3cc', 'Go-http-client/2.0', 'ZXlKcGRpSTZJbXMzVGxvM01IRldPRE0xVGpWQ2F6ZE5TU3R3TVVFOVBTSXNJblpoYkhWbElqb2lSWEZtVVVsM1ZYUk5ZMFZQTlhSbVprSjBiVTlGWkVSQmVGSXplRVF6VGtGek5uRkxNQ3RuTTFZdlFrMWFVRFZ5VjBSTVJYUXdZamxYV1M5M1pVUnNVMFUyVUd0WFUwbGpVVXBXVFhocGVrSk1ZalpLTW5sQllqQXpWRXRaUldOQllYTkhia0Z0Vm1WbGRHc3hXV2xGYUhOaVVHNVdaQzlCWlRGVFQweHlRVE5hY20xdFQwNVZSRE5JTHpVNGNYWjRkRlI0WVZRMWRsQjJZekZJUmpOcFNrOVZSMk16ZDI1dlJtSm5aemRXVlZwaU1ISlRjbGczVG1kclRISnVVSEZLWjJ4a2MwWkhPR1pWTnpOYWVGVjVZM0pwYzBSVGRXVTFjamx4VmxOUFdXUlhVRGR5YjFkdU1uWjViVTlEZVhFeFJuVldVMkZETmxZNGFWY3ZOMnQwYWxOWFp6RnRkRVJNYmtsSVVDOWhaQzlXYW5FM1ptYzlQU0lzSW0xaFl5STZJbVE1WmpZNFpXRmhPRFppTXpZd04yRmlNR0kyTVRRMU5HSmtZVEJoWldReE1XTXdNelU0TW1OaU0ySXpaakkzWTJKbVl6Z3pOV1kyTURjNE9XWTFNREVpTENKMFlXY2lPaUlpZlE9PQ==', 1760439731),
('dHzloVVEwYEP67TV4lz2f8eEA5i3THPwicbV5NVL', NULL, '41.216.53.178', 'Mozilla/5.0 (Linux; Android 6.0; VIE-L29) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/106.0.0.0 Mobile Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiRmlMdHRCVWR1NE9PWXQ1SWZUd2lpQXgyYjFvY1ZmaEVHOEV4T21jZCI7czo1OiJlcnJvciI7czozODoiVmV1aWxsZXogdm91cyBjb25uZWN0ZXIgdmlhIGxlIHBvcnRhaWwiO3M6NjoiX2ZsYXNoIjthOjI6e3M6MzoibmV3IjthOjA6e31zOjM6Im9sZCI7YToxOntpOjA7czo1OiJlcnJvciI7fX1zOjk6Il9wcmV2aW91cyI7YToxOntzOjM6InVybCI7czo0OToiaHR0cHM6Ly9pbnNjcmlwdGlvbi5jYXAtZXBhYy5vbmxpbmUvc3R1ZGVudHMtbGlzdCI7fX0=', 1760636005),
('E7hnTZayQfUqeCDwJ0enrm2i08rWBpfeKHpWpyLP', NULL, '64.23.225.86', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'ZXlKcGRpSTZJa1I2TTJFM1NHVlNiRTlQUVVOTU1EQlpiRzVQU0VFOVBTSXNJblpoYkhWbElqb2lNalZNUTBzMEt6aGFVRUZ0ZDJkb01GWldlV3RIV1VnellsVjNVVFEwU0ZsT05rSlNRV3hFVmtaSFNYZDJPSFpwTmt4cVJYaExPVEoxWTNGblVHZEdjek5xTkVVNVRVNVFPR2xRVkhOeWMzbE5lVE15WjJsNVNUZDNUSE56VDFaWmEycFFhVWt5Wkc5MmNFbFdkMUJwYldab2Ixa3hXVXRqWVRoNWR6Y3JSSEpWUzAxV1Fub3hTR3QyV0ZWekx6VXdUMDFRZG1neGNXNTRiMmxOVldGbllrdGtPWHBvZWpoWWVraHZOVnBFZUV0dWNuSkVObGRMY1U5Mk1WSTVWM055VFU5Q1pVazBaVE5LZEhVMWJsY3hjMnRrYkVKaFVUSkxWMGh0YkZKYVZIaDBRVk5aY0ZBeEsyVnFkVUo0Y1VNMVdGZDJTRU01VFhoU2N6RjZOMDE2WjNwUVIzZG9kMVZxY0ZZMVFXZ3lSemR2TjA5TFppdE5VMVJuY1RFellsSlRNa2RTZVVWd2NFdDZRME5WTDA1bmRHSjZWemRIYW1obVZVOVpZMkZNTjNoU2JrRmlSMEpEVEVVcmEzTXJVellyYzJsSkszRjRabFZTY1ZkeUwzRnRjV0ZQY1RKbk5FRTRaSGRKVVVadFFXdGljRklyWVRKaVJsTXpkMDV0TUdVNUlpd2liV0ZqSWpvaVptSTBOV1V3WXpFM1lXRTNPREUwWVdFMVpUSmpZelEwTjJVMU5qQmhaVFE0WWpWa05UbGpNMlF6Wm1RNFlqQTJOVEV5T1dVd01HSTFNR00xTmpCa05DSXNJblJoWnlJNklpSjk=', 1760754358),
('Ecj5sZk7DwBKPDyNLX2WT6xJqxA6aoQCzbnzrwBr', NULL, '13.218.111.30', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/104.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiVnNlcGtITE5tM2xRcTBaR2tNN21PY2E2Z1p4TWkwS1E3cG95R01ZSiI7czo1OiJlcnJvciI7czozODoiVmV1aWxsZXogdm91cyBjb25uZWN0ZXIgdmlhIGxlIHBvcnRhaWwiO3M6NjoiX2ZsYXNoIjthOjI6e3M6MzoibmV3IjthOjA6e31zOjM6Im9sZCI7YToxOntpOjA7czo1OiJlcnJvciI7fX1zOjk6Il9wcmV2aW91cyI7YToxOntzOjM6InVybCI7czozNToiaHR0cHM6Ly9pbnNjcmlwdGlvbi5jYXAtZXBhYy5vbmxpbmUiO319', 1761084227),
('FHN5OdrQEOLhcGAFBzgwh0do0ffQKZcJ7hV0WZmz', NULL, '2a02:4780:27:c0de::3cc', 'Go-http-client/2.0', 'ZXlKcGRpSTZJblpHY2xWS1VtVm5WMlZNY1U5VVJuaFNZM00xYTBFOVBTSXNJblpoYkhWbElqb2liRGcxWTBwUWJUSkpiMmd4Tm5ZNVdtTjViRTl2YkZCeWR6SnhVV0Y2ZG5KUU5HMVJSbEJLV1VaTWEzRldLeXN3U2xoNFpXaEVTVmxLUjJzM1JtWnFiSEl2UzNRMlFWTnZZbU5hWXpOclpVcDBSRWROU2paQ2JrUnFNVEEyUnpSRmQyTTBhbWx6Y0ZCdU1VeEZOa2xUZW1sa2VTdHhWRzFFV0Vwa1JXMVRhMUl6Y2tKV05HOWtUMGRFYjJ4aU1GRkhkMEpWY1doaFdVbENNR1J2Y0VVMFJVWm9VVWd3UTBKUVIyRmhaRlI0UzFwMVZsTkdTbkpyV205VlRFTjRXaTg1YzBOSFVYaEdlbUZSYnl0VFVHTndjbllySzFsd2VVNXFObTV1UzBOdE5WWXJhRVJYZGs5RlJISXZjVmcxTjJkTGVESjFkazlrUjFKaWJUUjFSVTlXVkU1RmFqa3JZV000VTJWWVkzRm9WVGhMV1ZKNk1rRTlQU0lzSW0xaFl5STZJamt5WmpVeE1HSTRaVEEwTVdRMFl6bGxOV0l5TVdRek9HWmpPRFEwTW1NeU1qSm1ORGt6TTJFd00yTTNPV1l4TkRreE5HVTBOVE0zWmpNelpEWTJNak1pTENKMFlXY2lPaUlpZlE9PQ==', 1760695567),
('FvId2ESmIjSZzdYdMKgXcTpQbz0x0DU5iZGtpg6w', NULL, '2a02:4780:27:c0de::3cc', 'Go-http-client/2.0', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoibzNyQTVZQUphZE45RWtTVWFMZWF3QTMxZDVWck01eFRZN1I5U2g3SiI7czo1OiJlcnJvciI7czozODoiVmV1aWxsZXogdm91cyBjb25uZWN0ZXIgdmlhIGxlIHBvcnRhaWwiO3M6NjoiX2ZsYXNoIjthOjI6e3M6MzoibmV3IjthOjA6e31zOjM6Im9sZCI7YToxOntpOjA7czo1OiJlcnJvciI7fX19', 1760858559),
('fwNZwIt8Gjgxo8hpbdnHGUYaEJxUEev1b5IDScqV', NULL, '41.85.163.11', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiM1NZeW5YOEpCeGN2R1hEMEE2Ujc4bmpXNTJibUhnd2hmNDRIaHQ5RSI7czo1OiJlcnJvciI7czozODoiVmV1aWxsZXogdm91cyBjb25uZWN0ZXIgdmlhIGxlIHBvcnRhaWwiO3M6NjoiX2ZsYXNoIjthOjI6e3M6MzoibmV3IjthOjA6e31zOjM6Im9sZCI7YToxOntpOjA7czo1OiJlcnJvciI7fX1zOjk6Il9wcmV2aW91cyI7YToxOntzOjM6InVybCI7czo0OToiaHR0cHM6Ly9pbnNjcmlwdGlvbi5jYXAtZXBhYy5vbmxpbmUvc3R1ZGVudHMtbGlzdCI7fX0=', 1760453500),
('fzNsg7mu3Lu13itv1jvwGf4jaiQqgKMue3APVCZ0', NULL, '2a02:4780:27:c0de::3cc', 'Go-http-client/2.0', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiQ0tVa1VBM1hQVnlpUXVMVkRES1kxNVRTZDVTZWFpdDJJR2I3akt4TyI7czo1OiJlcnJvciI7czozODoiVmV1aWxsZXogdm91cyBjb25uZWN0ZXIgdmlhIGxlIHBvcnRhaWwiO3M6NjoiX2ZsYXNoIjthOjI6e3M6MzoibmV3IjthOjA6e31zOjM6Im9sZCI7YToxOntpOjA7czo1OiJlcnJvciI7fX19', 1761044831),
('GpQ1Dmc9vnvvKKja9RF0B4fRtAaB2SVEzuQ0CuuM', NULL, '197.234.221.204', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiVWtkYlVncXZnME5YaEh1Uzh6eGxubWdaTnVkeGFOZm1waVJ6T01jbSI7czo1OiJlcnJvciI7czozODoiVmV1aWxsZXogdm91cyBjb25uZWN0ZXIgdmlhIGxlIHBvcnRhaWwiO3M6NjoiX2ZsYXNoIjthOjI6e3M6MzoibmV3IjthOjA6e31zOjM6Im9sZCI7YToxOntpOjA7czo1OiJlcnJvciI7fX1zOjk6Il9wcmV2aW91cyI7YToxOntzOjM6InVybCI7czo0OToiaHR0cHM6Ly9pbnNjcmlwdGlvbi5jYXAtZXBhYy5vbmxpbmUvc3R1ZGVudHMtbGlzdCI7fX0=', 1760994459),
('gVlvK1Icwe9gzyrR98iaRASs6V4tL73p7PveJwBT', NULL, '2a02:4780:27:c0de::3cc', 'Go-http-client/2.0', 'ZXlKcGRpSTZJak5VZDNadFZVNVhaM0JaTWtWNVJHZFpTbHByY1ZFOVBTSXNJblpoYkhWbElqb2lhRXMxV1VaS09ITTRORXh1Y3pSMGVqaHBjR3hEU1RkbVNYUTVSWEFyUW0wd2EzVlZRM2N3U1RKT1pETjRZemx0UTFsSVIwOTRRV2xqVkZkTksxSlJPRkIwWmtKUWMyWTVWV3NyWVhsNEt5OXlUV2RuYTBWQ1drbEpVM2xtYm5saFNrbE9Uek5vVVdWUk9EaHRLMnB1WTA1blpIYzRVVFUwTUhCa1FqVlBNMUExYWxCUmVXWlhVakIwT1cwM1kwNU9XRTF6WW05Q01HZG9hMVZZWVZvMGFGaGhka2R2YWtwVWQwaExhemxoY21OcVRHUnJSVE4zTWxWVlJFdFNVazFJTkdSSVExYzNOVVJ0ZUU5SVRHTnRSRFE0ZVZkcVZYUnhiUzlhYzFObldHZzBiM1k0U2l0d1ZIazRPVmwxUmt3eU0ydDJUM1o0ZEV0WFVFVlVVa1ZhVkdFeVEzcFFNVVpSTkcxQ2JYUndjMUVyT1VNM1ZsRTlQU0lzSW0xaFl5STZJalJpWWpVNVpEQXdZVFptT0RObU1ERXpZalU0TmpkaU5EaGxZV1F4TW1RM05qQXdaREl6TWpnd05XWXhaamhtT0RneVlUUmhNbUV3WVdKa05qTmtOR1VpTENKMFlXY2lPaUlpZlE9PQ==', 1760504866),
('GYHVb1wp3v1GuIfV0xwDmYnuCiIYXyDEDivD9NQx', NULL, '2a02:4780:27:c0de::3cc', 'Go-http-client/2.0', 'ZXlKcGRpSTZJbWw2UTBwRWNUSjZUMnR1UWxONVMzSmxkVUZCV21jOVBTSXNJblpoYkhWbElqb2ljamh4UVhwd1dEQjRVVGN5YW5sdWJGRldTWFZzUm0xMGVrOWhkVFpRTW1zMlZUaFZNVGdyV21WeVZHdHdkRzVUWW1SUksyNDJOemhKV1cxS04zQlpaelpPUlN0c0wyaHpLMHhQUTFveFpXNXhXWEpYWVU5VmRFcHRabTlYYzA1RWVUUTNjMVZLYTJKQ2JFSlpSRWx0YmpGMVVreGFhVXBXUzBkQ1IxRjVPRWcyYWpZM1RHNUlMMEpaTXpWTWRFTkxNbWhwZFZrMlVsVlFTakpQWld4NVlubHFTR0YyVEZwdmNFbGxiMlpKYW1jMllqbEljamxWWTFWa1owRkJOV294VmtGRE0zcEpkREpRV2pWdGEzVXphVkozVDNKeWJtMU9WazVQWnpaTWJrczNkamMyUWtSbmRIZ3hjMWcxYkhwbGNIcE9USEV4Wm5sTGJHSm9lVmRVYlNJc0ltMWhZeUk2SWpBeVpURmlPR1l4TXpNeVpHSmhZekE0TURGaU16bGlZekUwTWpGbE5tUTFOREEwWTJZMFlqZGpOekkwTWpRd1pqZGxNbUl4TkRka016ZzROMkl6WTJNaUxDSjBZV2NpT2lJaWZRPT0=', 1760843991),
('H80n05NU1Y27O0FJqAUHY9FSTMwWBhjw91qos2Po', NULL, '2a02:4780:27:c0de::3cc', 'Go-http-client/2.0', 'ZXlKcGRpSTZJbHAyTmtGcmNXdEJOMDlXYzNsaVJWUnZUalJFUVdjOVBTSXNJblpoYkhWbElqb2lVVlZIUnpCQldqWnpOa2N3WnpOU2VqSmphbFZpU21oRFUxWkNiM3B6V1V4dVZqVklUQzlFVFdOR1ZGUm5la2hCUW1wRlZqZExNVWQ2UTJWUlEyeFRNVzFtY2twVGFHdDVNWE5zVTFGamFtdERaSGxhYzJzNFkxaEdNall3YVhkdlVWcDRNVlo2UmpRMGNtVndZVEpNYm5CRFpIWjZNMVZtWm5CNGIwTk1UVmgxZVZRNFZVTnRXbEJCYjNkdFRrSkRjMVppYUZoaWNWbHJWbk50Y1hSdVVtOTJjSEZqUVRaQlZHUlRVWFZRT1ZGV2RuTkxjVkl5VVZWSmRXTTBRbWd3UXpoQ1dqVlhlR2RHVDFZclpWSXdSbHBzSzNaMVdrVkhXRFV6Tms0eWNWVTJWR1p4Tm10WFYzaDNla1J2WldOV1NWaFlNVEEwYzA5SE9GcENZbmhXT1NJc0ltMWhZeUk2SW1NM09HWmlNamMxTldRM1pUUmhObUUxWlRSaE9USmtOMlF4TVRCa056RmlOMkk1TjJRMU4ySTFNVGRsWmpVMU5tSmtObVEyTjJNek1UbGxObU5oTmpVaUxDSjBZV2NpT2lJaWZRPT0=', 1761011837),
('HTY9R2y2II6673vUQFFk3SzpiFHcDdZr9QQp18ly', NULL, '2a02:4780:27:c0de::3cc', 'Go-http-client/2.0', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoieXNRU2ppdzJLd0pzcFp3MXNTdURGZnBDcnRhRkxqQlhqblZKTzZucCI7czo1OiJlcnJvciI7czozODoiVmV1aWxsZXogdm91cyBjb25uZWN0ZXIgdmlhIGxlIHBvcnRhaWwiO3M6NjoiX2ZsYXNoIjthOjI6e3M6MzoibmV3IjthOjA6e31zOjM6Im9sZCI7YToxOntpOjA7czo1OiJlcnJvciI7fX19', 1760770216),
('jAaKAXhsT1aKlmOKTXJqZLTwkTDLl5H4RAu29VtS', NULL, '34.79.244.253', 'Mozilla/5.0 (compatible; CMS-Checker/1.0; +https://example.com)', 'ZXlKcGRpSTZJakJEYWxsaGFTOHlZVXBDUm5kSEsxTk9Wa2xtZW5jOVBTSXNJblpoYkhWbElqb2lURlF3TDFoaWFtMHlVMlUzZDJoc1ZIVlBVR3RsZW1aWFZ5ODNiR3BMZGpkT1JqTTBSVU42UWtOMVdsTkdlRzFJZHpKak1qQlJTbWh1TlU5VVNFaGtOWFpMZUV4UlkzZFhRMnN3UXpoTVZpOHdVemd3VVhOTmVqZHhXR1V3YkhJelRFMUtabVpHUW5NMVJXRlRVQzlSYTIxd01VUnZWamRYVjFoMVltSnNORTlIY21sVk9HNUtlRE40T1RNcmVWbGhiR3BrUW5Cb1ltRndPVTlwV214UlpuWm1UbWc1WkRKUFVHMW9WM2MyUm5WaGRraFhjblZaUzFCVU0yOXlaSFZ2VUdkc09GRmlNVXR0WTB4M01rUkxUMjl0ZWs1TlRFRnFValpLVlZkR0syODVWWHB4Y0ROclduRjRhVmx3TWxkdmJXOHdVbVpYU2paV1UybDNZVEZ0Ym5oaFpYSlZZMWhMTm1wUU1Ha3lUbUZaYTNrNFdtWXpNMXB4WjJsTllsVlFTSFJqY1U1WU9TdERZMDU1TTFWaFUyRnJTWGsyWnpSbWJXdzNSeXRvTm5CdlNXTlljRlZhUlZKaFQwWXhSakZtVFZOd1kweERaRUYwUzJKdldFd3pObGRMU1hCbU9TczFWRmh0WlZvd2JsWm1Xbk5RTmxkYU0wRTJUWE01WnpsNElpd2liV0ZqSWpvaVpXWXhZMk01T1RJeE5HRXlNekZqTW1FeU56UmhNalpqWW1JeU1XSmpNRFJoT0dFek0yRXhaakV5WW1ReVltRTJZVGRtWlRCaE9XUTFabUl6WkRSak5DSXNJblJoWnlJNklpSjk=', 1760720280),
('LZ4c6Cjx27BGfpZS5VwhpZc3G4VGBYvYffytg9YU', NULL, '137.255.115.76', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/28.0 Chrome/130.0.0.0 Mobile Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiR2hZQmoxemwxREFYNm5iZjRpYnpOU1lDYnJqbm5MYmNxeTFoM3dweSI7czo1OiJlcnJvciI7czozODoiVmV1aWxsZXogdm91cyBjb25uZWN0ZXIgdmlhIGxlIHBvcnRhaWwiO3M6NjoiX2ZsYXNoIjthOjI6e3M6MzoibmV3IjthOjA6e31zOjM6Im9sZCI7YToxOntpOjA7czo1OiJlcnJvciI7fX1zOjk6Il9wcmV2aW91cyI7YToxOntzOjM6InVybCI7czo0OToiaHR0cHM6Ly9pbnNjcmlwdGlvbi5jYXAtZXBhYy5vbmxpbmUvc3R1ZGVudHMtbGlzdCI7fX0=', 1760519484),
('MJrApL1irlbUTP3LAf1zmvBKFhtGUPHCdQ5EUR4k', NULL, '44.242.136.200', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_5) AppleWebKit/603.3.8 (KHTML, like Gecko) Version/10.1.2 Safari/603.3.8', 'ZXlKcGRpSTZJa1JwZFU4d1VXZHlNVE15T0V4QlFtMXROMlV5V2xFOVBTSXNJblpoYkhWbElqb2ljWFl2SzI5aU5EaDBkVXhZZFV4MGFFeDJNRTl3VVVOeGJUQlBlVzFCV0VoTVdEQnJlVFpZUWpseGFEUmlOR3hKSzBKUk0wSlpNRGRCZUd0V2JVbzRPVmRDV1ZoaVFVbzVlVkZ4VkU4eVRUTkVNRVpWTUVkUGJtTmFWazlaZURseGFtcEtSR3BGVG04emJFdzBUblZ3ZGxjcldHSXJVa3hzUlM5MWJESkpNamhCZGxnMldYWmxTalpGZVd0dU0zRndlbFZOWlRGdU9YSlpibFZhSzJac1dHaDFTM2xpUlhsNGRHSlpPVFpTVDIxclprNUJhVWRRTlhkb2IwVlZSeXQ0Y2tad1oxcFVhVEkzWmtaNlkwZDJOSFJUVFc1cVVGcE9RV3BwTURKWGRVWXdjRmRuVldrNFQxbEhhemhRZEdWV1J6UlpaSGhyTmt0bGJWRnBWblZsTUhSdFdEaEZRemw0WlZSaVNHOXVVMjl0Vm5FNU5UUllZMFJvYkc0MldUbHdObXNyYkdOTmN6QTBZbTlXYjNGWFUzVnpUMU54WjNaRGMyaHBWMUZwUld4RVltSXdaRVZxSzNoelFYWmhXak1yY1RBdk1EWjRSVE4wV0RkV01VeFpiRlJVYTBwUmMyaENZMWs0TkZkc1MxVXdURFpGVTJWT2NESjRVbVpNWkVJM0lpd2liV0ZqSWpvaU9EWTFaV1EzWkRBMU56WmtOVGt3Tmprd05HVmpOek5qTnpneE1UQTRZakl3TURWbE5EWm1ZelppWVRRd056SXpZVFV6T1RNMU9HWm1Oak0wT1RabFppSXNJblJoWnlJNklpSjk=', 1760614498),
('MlaDfg31gwAIPdu66XRlBVEuyp43ln8ksAa8y5tG', NULL, '66.249.66.195', 'Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.7339.207 Mobile Safari/537.36 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiMzY1ZUlRVzFnOXdKbVZQc3RCdWFvaGxLejFIUUI3ZlZiWHBFUGx4dCI7czo1OiJlcnJvciI7czozODoiVmV1aWxsZXogdm91cyBjb25uZWN0ZXIgdmlhIGxlIHBvcnRhaWwiO3M6NjoiX2ZsYXNoIjthOjI6e3M6MzoibmV3IjthOjA6e31zOjM6Im9sZCI7YToxOntpOjA7czo1OiJlcnJvciI7fX1zOjk6Il9wcmV2aW91cyI7YToxOntzOjM6InVybCI7czozNToiaHR0cHM6Ly9pbnNjcmlwdGlvbi5jYXAtZXBhYy5vbmxpbmUiO319', 1760940657),
('mS4bLKPdJOcbNy0Dmsf2Yo276O9AKbIZfJdLSjO7', NULL, '41.79.219.99', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiVDROWDU5N2pocU82S2NjYlBzSDczN3hGTVZqR0owOUJxTmZlT1IyYyI7czo1OiJlcnJvciI7czozODoiVmV1aWxsZXogdm91cyBjb25uZWN0ZXIgdmlhIGxlIHBvcnRhaWwiO3M6NjoiX2ZsYXNoIjthOjI6e3M6MzoibmV3IjthOjA6e31zOjM6Im9sZCI7YToxOntpOjA7czo1OiJlcnJvciI7fX1zOjk6Il9wcmV2aW91cyI7YToxOntzOjM6InVybCI7czo0OToiaHR0cHM6Ly9pbnNjcmlwdGlvbi5jYXAtZXBhYy5vbmxpbmUvc3R1ZGVudHMtbGlzdCI7fX0=', 1760944976),
('N4vvb93DjVnX37dR4SD6cjfvYELNuqNhRXDaKTg9', NULL, '34.14.17.140', 'Mozilla/5.0 (compatible; CMS-Checker/1.0; +https://example.com)', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoidEd5UUJOYVk4cjhzQk01SnNyaThHWVp4bm5ZVGc1bFdCaXZNTFVzZCI7czo1OiJlcnJvciI7czozODoiVmV1aWxsZXogdm91cyBjb25uZWN0ZXIgdmlhIGxlIHBvcnRhaWwiO3M6NjoiX2ZsYXNoIjthOjI6e3M6MzoibmV3IjthOjA6e31zOjM6Im9sZCI7YToxOntpOjA7czo1OiJlcnJvciI7fX1zOjk6Il9wcmV2aW91cyI7YToxOntzOjM6InVybCI7czozNToiaHR0cHM6Ly9pbnNjcmlwdGlvbi5jYXAtZXBhYy5vbmxpbmUiO319', 1760548008),
('NCv4G20o19T4alVwQrZS6MYXxh5WtwWApJIpxDRw', NULL, '66.249.66.196', 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoibTEzZ1ZxWWZrc1FIakl0UXlvSWNXQ3NPSW9TSXlkUlVEdXdvcWNPMyI7czo1OiJlcnJvciI7czozODoiVmV1aWxsZXogdm91cyBjb25uZWN0ZXIgdmlhIGxlIHBvcnRhaWwiO3M6NjoiX2ZsYXNoIjthOjI6e3M6MzoibmV3IjthOjA6e31zOjM6Im9sZCI7YToxOntpOjA7czo1OiJlcnJvciI7fX1zOjk6Il9wcmV2aW91cyI7YToxOntzOjM6InVybCI7czozNToiaHR0cHM6Ly9pbnNjcmlwdGlvbi5jYXAtZXBhYy5vbmxpbmUiO319', 1760940670),
('NOUikFNJ09WAlX9fGRWp9tFsDZ27eprKx8QyfbA3', NULL, '128.199.16.213', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', 'ZXlKcGRpSTZJbFpEZUdKVWRrRXlRMk5LUjJGQlZsVlFTMEZvZDJjOVBTSXNJblpoYkhWbElqb2lZV2xJUzNSUmVuWm5UaTlMUjB0R2JFUmxlV2RtTkZjeGNWRnNkM1pVYlV4cldYTm1NaXRKTVZoVGRqaGpNRlJIWkhkc2FWSlRRbTloZERVcmRqZDVLMHgxV1VaMGMwdENWWFp3VlU1TlJFMUZUM0VyY1haSlZqbHdNV1kyUW5KT1NUY3hXbFl2VDB0S2VWZ3lSRWhFVkdGQlNIQnlWV1JrWTBsMU1YWkljSGRUYUdKWFFrNXhXSFI1V0Rrd2ExaEZabUpUYlZkcVoxUXdUSGczYjNoNWNtTXpUMlJDYTJ0T1JEVmlUakJ1V1V4dVVFRk5kblIzZUVkNVpDdDBOMUZKTVdsclN6WldUekpNVWtKSU1FeExiMjU1ZGpsSVMwd3hja1JHTjBRMlZrSm1PVXBwSzNKR1prSlhiaTk0V2paTlRqazJjVUZGUVdoQlFWSm5SRGcyV1RRNFNVZFhWMFJwVG1OWmNrODFhMVl6VFNzNGFsZFlaVzFXWVU5VmJVUndWRTQwVkc4MldUTmhSalpEWVZZdlowbDFaa3czU1dnNE1qQkpVRUpPUVdab09WaENjbVJNZFcxS1VGRldibVkxVVhOTVRtcEhVbVp6UkVFMFRFMTFlaTlsWVRoeE0xUlhhbWhSUFNJc0ltMWhZeUk2SWpVeFlXVTFOR1ZpWXpObU4yRm1NelZsTnpjd1lUUTBaVEJqTUdFek1HVTVPV0psTXpReE5XSmlPV0V5TldVMFpHRm1aRFUwTTJJek1UWmpZVGd4TXpZaUxDSjBZV2NpT2lJaWZRPT0=', 1760427452),
('nS4Rjrq5Qnw0sML0XlShSSoeiDaxw9ghwu5trVpZ', NULL, '208.84.101.119', 'python-requests/2.27.1', 'ZXlKcGRpSTZJblJhUmtaVFRWVllVemh5TUU1eU16VjZNMGg0ZVVFOVBTSXNJblpoYkhWbElqb2lkMVJ1WjBKS1dVNXhRVGRhUjNOemVsUnRiRXhFVGxOdVFYbE1lakJ2UVM5cFJFWnplRGgxVUdsNVdYQnVXbVJPV25oNmRFTlZja2MzZDFaak5IbHRha3gzYjA5MWMxVndiVzlXU0daV00wSk5VR28xV0RjME5IUjRhR0VyUmtac01FczBhbGc1Wm5Wdk5tVnFjbUpDVXpCYVdrRm1hbEZUY0ROU1FXSlVWRTlGVm1OQlR6SllNR3RVVGxCSlVtMU1ZbXhzWW5SRWVYWjZXakZzZUVFeWRWaERUbmhzUkhrd2JFNXdTV0ZvVmxoUGQxTmpVRFpYVmpsc09YaExRMkl4VW14bk5pOWFaRkZVWld3dmNUTkpja2wxTHpOMVJGSlFkMHcwUXl0akwyazBjM0ZMYjNvemJsaHNkMGQ1ZUdJMmVWRlliR0ZOUTBWRk5tSkRUVWxGUWxSak4yWm1aV05wYUZwVlRXMHJPR0pYY0ZkaU0yVkNNWEpKVmxwYVprZEZSMlpyTVRjd1NYTlJNRVUyTVRKU1FYZzFWa3hNV0RBdlIyVkJZelUwUnpkS1RGb3hSVlZqVUVwa1FrRk9kMVEySzBwM1ZtTmphWFJYVFZwek5IVlpVallyT1U5V2JGRnRaa2RCVW0xaGNFeHJhRmhpY1doYWFXSnRRMmRRYmtSRElpd2liV0ZqSWpvaVlXUTNNRGN5WXpSbVltUTRPREE1TjJFMlkyWXpNR1V3WmpGalpEYzRNbUl4TVRabE5XVXpaRFkwWkRBMlpUZGhaamt4TldRME56ZGxOV0k1WmpFeVl5SXNJblJoWnlJNklpSjk=', 1760744335),
('Nwg73fht4BVsiEweh567UdJPsOjEsKvowNXrsvy2', NULL, '34.1.28.215', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', 'ZXlKcGRpSTZJbEJEYW5KUVdVZEJjSHB1YldKWU9UZFlVbmhYYUdjOVBTSXNJblpoYkhWbElqb2liVXg0TjJ3M2VHMUROVFkxVnk5M2JrZDFiREZpYWtGMFJIVkpUVlFyUjNWeFowRlFjM1ZZVm0welFXSkdOVU5MVTI5TVdVMUhaa1JOU0RCWGMyeGxjMmhXVEZsNlkwRlBXQ3RyUVZsdlQxZGhZazFJTUc1V1dHWXJZVVZQUW5GUFZFODJNa2RDT0U5VlN6ZENURFVyVkRSb2Qza3JUR2h6UW1aRE1EQkZaR3B3VXpKR1EycDBURmM1YkU1NGQycFRRbWN6VTFvclUxZFVUa1pwYTJGcWIxRlBZVVVyVmxoYVFrRlJiRFJWUW1GRk5GaDBhbTlIYVc1RU9FdExhVVJWTkhWTU9UVmtaQzlvVDBaNkwzaHdXa1ZYTjBwMk5qTlhWblZLU0hKM2NpdGlPWEJrVGpoU1NDdE5hMmR2WlVFek9IWnNWMmhQTURBMWNXTnFjRXhUVDJRclptNXNRbFZyZWpKdlYzUnlkVXBwVGpJd1RIWXhNR3ROSzJvdldVUnhjRkV4V1d4Q2FqZFJVbmhLV1VWWlkxcHlTMUpWV2pNeldYWlJZVFozYzNCbmNEZEVRV1kyUzJSa2FXOVZNazA1YTFCRVJrZEpRbVl2TWxsUFUyWTNOMHg2WmxOSlNWVm1XbWhyWjBKSFdrSTJkR1l4VjNWV05YUnVTM0I1T1daNklpd2liV0ZqSWpvaU16ZGtOemhoWlRWa09EZGxNbVZtTVRoak9EQTBaalEzWXpsbFlURmhabU5rTkdFME5qTXpPVEk0TUdJMFlUZGtOV1ZoWWpRM016VXlOR05qWTJKa1ppSXNJblJoWnlJNklpSjk=', 1761180023),
('o7sHozpnW2UfMQmgyuXseKRoCP8f3vk16UjpOFWl', NULL, '2a02:4780:27:c0de::3cc', 'Go-http-client/2.0', 'ZXlKcGRpSTZJbVUwTUd0TkszcEZORGR5UVRKT1YzRjJTMmd3YUhjOVBTSXNJblpoYkhWbElqb2lSMFF2ZDBRdlpGb3pRakprVVROSWMyVXlUbE55ZGpOUWJYSmpjV1I0WjNWU2JIcDVXbkZQZWtKRlptZFNla3BTVW1WVmEwMHdkbXc0UzNGWGNGaE1NbEZ5ZWl0WEsxaFNRbk51UjJjelVYVnhiRlJaTjNWSVkzYzBibnBsYzI5MU5uQjNlSEJZTnpBNU4wOTNiRmQwT1cweEt6ZGhkM05RY0RWTE1GTktValpDVlhoV2FIQmlRMmxKUjBOcmRsWmhTR3hPZDJ4NWFXZHhTV05aV1dVd1ZGSk1kbmxSWlVkQ1UxaE5lbGM1WkVWNk1FRmpVSGR3YUVSdVN6UmFkalpGTDBoM1ZWaFNSR3hGUkNzNFRUTlVaSFpHTkM5ek1uQXlOMnRGU0dsa1pUTlpjVFpaU2xFMGJHa3ZhRE13TVRNdlUxRkhSSFpNZG1aSlYyNVZhVEIyYXlJc0ltMWhZeUk2SW1Zek1qZzRNR1l6TWpCaE5tWmpNMlUzTnpsaFkyTXhPRE01WVdWbU9EUXdOalEwTVRBMlptVXhNakl4TXpobE9EY3hZekl5T0RJell6RmhOVFkyWlRraUxDSjBZV2NpT2lJaWZRPT0=', 1760427257),
('oTZ0odBwCSd5zgkQdQlGYagHcnVRAWYTbyVN2Tpq', NULL, '2a02:4780:27:c0de::3cc', 'Go-http-client/2.0', 'ZXlKcGRpSTZJbFk1WkU5TmNHTndaVzVGTVcxWlZVSnVVbGxRTVVFOVBTSXNJblpoYkhWbElqb2lZMVZ1UVdacGJrdzFOVXBaVDJ0bVJEQTNlSEoxTUhWMGVXeE5kbWRKTjA1R1NWcEVPR1oxUmtoQ01rMXpkVkV4U0Vsa1QyVXZTMFIxY2tZcllVOTVTVE5GYmpGWlRVdE5la3hvV21aU05VcEtkamcxWW01UE5rTmlaM0pXY1VGMWRtYzJWa3h6VTNGUmVWUXJSVmd6TTFkRGQzUXpUa2RsTmtOR1UwZ3dZM2xHZG5KREwxSmtXV3RRZFVGclVtSm9OekZEY0haNmExUnBSM3A0WlRkbllXaEpSbkF4VFUxS1UwcHZWVmhIWWxGeE0wbGxVMEZsTDFKWFJGWnlZVEpwZGtGcU4zSXZLMFY2T1RoM056aFVjWEZDSzJOS2EzSlhLMHQzZVU1dFVqUjNRalp5V2xaNWRUaERhSFoxYTIxaGNDOXhkMkp5TTFCMFNrbFpWRTVrY1d0UVZGWm5OalZXT1hBelEya3ZlV2wxYW5SMFNHYzlQU0lzSW0xaFl5STZJakV4WXpNNFlqZ3lPVE5qTlRrNU1EazRPVFZtWldVMFlqTTNPR1ZrTm1NNU9EZzFabUl3Tm1Oa1lqazFNV1E1WWpFNFlXRTRObVExTVRVNFpXWXlOMkVpTENKMFlXY2lPaUlpZlE9PQ==', 1761048064),
('Qh7SYxBIEUfFrJqgbm5wlRSAtsFqsOHzyp4uRuwv', NULL, '2a02:4780:27:c0de::3cc', 'Go-http-client/2.0', 'ZXlKcGRpSTZJa3c1TldaVk4wWlRWa0ZtZG5aeVZHWXdiR00wWmtFOVBTSXNJblpoYkhWbElqb2lTVFpqTVhBd1VYZEpiVEJEVFRBeVExVjRUVFo1Ym1rNWRuWldXSEZFT1VoellubHZRWE0zZFU1NFIyVjZUR1ZJTUhKaU9Xb3hSRUkwT0RkQlRGbEZhRXg2ZUdKb2VGaG5ObGw2YWxCa1ZuVmFhVmRMU0d4SmFUQkZjSFpxZVhSSUwwNTZjMlo1Um5WUFYxVnpXWHBpVkZaM1RtMXdhRXhZZDA5MlQwUXZiM0J2TkhvMFpITkxWV1pSYXpOV1dXNU1jME5pU1c0eWQzWkZXRFJFUkVOc1pHNTZSSEkyZDJwVWFDOUxVbFIzTTBFNFJGUm5MMmN3VlV0WlZtMVNWRk41YnpobFZGWkNTVTlIT1dOMVJWSk1kbFpGZVhwVWFIazVVRXhDTmxSb09XVXZSalpQVjIwd2NuUndkR2hyVlM5aFRVZ3ZiM2Q2TVVreFVHWTRSR0l6TXlJc0ltMWhZeUk2SWpNeE5EVTVOalZoWVRNMk9HRXlNbVZtTVRjMk5UUXpObUUxWlRGaFlqYzBaamszTkdVMVkyWm1NRGhqTWpoalpUUmtNak5pWlRsa1lUQmtOVFk1WVdZaUxDSjBZV2NpT2lJaWZRPT0=', 1760667392),
('qHHOD5QzjauwnzH8fzx2xyvew0vxXj0hLwa5Ahs9', NULL, '197.234.221.204', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoibFBkUUdBdUhFQkx6eUtIQ2ZtNDVXZGxqbGZRRXZna3BtR2dJd2Z0ciI7czo1OiJlcnJvciI7czozODoiVmV1aWxsZXogdm91cyBjb25uZWN0ZXIgdmlhIGxlIHBvcnRhaWwiO3M6NjoiX2ZsYXNoIjthOjI6e3M6MzoibmV3IjthOjA6e31zOjM6Im9sZCI7YToxOntpOjA7czo1OiJlcnJvciI7fX1zOjk6Il9wcmV2aW91cyI7YToxOntzOjM6InVybCI7czo0OToiaHR0cHM6Ly9pbnNjcmlwdGlvbi5jYXAtZXBhYy5vbmxpbmUvc3R1ZGVudHMtbGlzdCI7fX0=', 1760990074),
('qJZ9iIAJQarKVEO1R5rGAU1Z6MUYCM0Lsh3XjjRl', NULL, '44.229.34.211', 'Mozilla/5.0 (compatible; wpbot/1.3; +https://forms.gle/ajBaxygz9jSR8p8G9)', 'ZXlKcGRpSTZJbmQyZVc1M1luWnpNbkZTZFZrNFowYzRURTkyUzJjOVBTSXNJblpoYkhWbElqb2lUbTh6TjBOVFR6bGFSM2MzYnpsbFVVOHJOMk5HVWxORVNIUkdaMUEwYVVweVVIaG9jV2g0ZFU5d1IxVTRPVXQyV201SE9IQTVaMVp5YkhwdFlrOTZiR0pYTkc1WlNUTm9SM1pxY2xSNU5tZFFZM0ZRVkdWelNGWTVNRXd6Y1RWMGJFRjJRMGxFYmtOSFV6ZzVOVzlPWms5dGFFVlFUbUpNUVdSNGNtRjJTVFJpTkVsMVNXRnViVTVRWkZVME9WUnNTbXQxTmxGMVFqVlZiV3R5WmpoSGNrMWthSFJDZVZobVdUVXhZbWs0VFVGcVUwMVJPVmhLYmtoMmRWcExkMjVOUjJaS016Vk9WVEV5TVV4b2JqWlVTRWQ1TlZGNWJrcDZiMmxqTDFGclVFdG9RVk41YW5STkwwaDNha2QyVFc1VGRrcHFWamxEWjBOS09HNTJRMHBDY21WRVoxVlBSakpyUm5SamRHRnpjMGRvU0d0b1VXd3dUM1V4T0dZNU1XWnJVRlZwTm1Vek5GSjVXWEE1TUdnd1VFSXJhM1JDWTI5dVRFbFJPWGhpZEdWbWJrTlhOU3M0SzFGVVJFWmlOV0pUTm1kUmFIUkpibWhvWVdNeVNGRnFWSFJrYm5CU1JWSXdVMUJ2ZG1kRk1uTjJNMGRUV2pGM2MzRlJNRFUyUkdkTElpd2liV0ZqSWpvaU5qZzVNelkyWkRneE9URTVOMlV5TW1ZMU56ZGxOak5qTmpNMVpqZ3pOVGsxWkdJeU1XSmhPVFZqT0dRd05ESTBNamMyTm1KaU9ESXhaVFV4Tm1FMk1pSXNJblJoWnlJNklpSjk=', 1760612014),
('QLXIshPnGLB5lkfgWISnESJKVsSPK1NtpdNoxDvH', NULL, '102.215.136.241', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoidzJJOHlPdnZUTjF0ekRBbWxMMnh0Z0dVUHd1V0FhMUIzSmVIQkloOCI7czo1OiJlcnJvciI7czozODoiVmV1aWxsZXogdm91cyBjb25uZWN0ZXIgdmlhIGxlIHBvcnRhaWwiO3M6NjoiX2ZsYXNoIjthOjI6e3M6MzoibmV3IjthOjA6e31zOjM6Im9sZCI7YToxOntpOjA7czo1OiJlcnJvciI7fX1zOjk6Il9wcmV2aW91cyI7YToxOntzOjM6InVybCI7czozNToiaHR0cHM6Ly9pbnNjcmlwdGlvbi5jYXAtZXBhYy5vbmxpbmUiO319', 1760431947),
('QZlT56gFtwbrPOvdYEBQ0ZIZBojUIUvcUurXsUTf', NULL, '156.0.214.54', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoialdPZE9EcHlrY2JWRG5taHZLbzQ1Z1BDdFdHb1h2TGNXRnZlZlNMNiI7czo1OiJlcnJvciI7czozODoiVmV1aWxsZXogdm91cyBjb25uZWN0ZXIgdmlhIGxlIHBvcnRhaWwiO3M6NjoiX2ZsYXNoIjthOjI6e3M6MzoibmV3IjthOjA6e31zOjM6Im9sZCI7YToxOntpOjA7czo1OiJlcnJvciI7fX1zOjk6Il9wcmV2aW91cyI7YToxOntzOjM6InVybCI7czo0OToiaHR0cHM6Ly9pbnNjcmlwdGlvbi5jYXAtZXBhYy5vbmxpbmUvc3R1ZGVudHMtbGlzdCI7fX0=', 1760611132),
('R0dMn2PQny4wKR2XsptPttc63sWQQmn0L8E5be38', NULL, '2a02:4780:27:c0de::3cc', 'Go-http-client/2.0', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiSFJVZmdoQUpqd0hqZUc4Z2NTZ2JmdkFKZzFGREY3T29XMHlXeTJucSI7czo1OiJlcnJvciI7czozODoiVmV1aWxsZXogdm91cyBjb25uZWN0ZXIgdmlhIGxlIHBvcnRhaWwiO3M6NjoiX2ZsYXNoIjthOjI6e3M6MzoibmV3IjthOjA6e31zOjM6Im9sZCI7YToxOntpOjA7czo1OiJlcnJvciI7fX19', 1761121795),
('rQxYQ6FTBzpHOx5oF3X1WTbi3W7VP53F9geh2p9c', NULL, '41.85.162.141', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiQXk2VjBOZWhQSmw5NW53MUoyVEk0V0YwQzVrNlNzZzdPTXdUZEVrTyI7czo1OiJlcnJvciI7czozODoiVmV1aWxsZXogdm91cyBjb25uZWN0ZXIgdmlhIGxlIHBvcnRhaWwiO3M6NjoiX2ZsYXNoIjthOjI6e3M6MzoibmV3IjthOjA6e31zOjM6Im9sZCI7YToxOntpOjA7czo1OiJlcnJvciI7fX1zOjk6Il9wcmV2aW91cyI7YToxOntzOjM6InVybCI7czo0OToiaHR0cHM6Ly9pbnNjcmlwdGlvbi5jYXAtZXBhYy5vbmxpbmUvc3R1ZGVudHMtbGlzdCI7fX0=', 1761141475),
('ru8S7OTFfD6Us3kcfkDEEyCItPOkKH928DD0S1lj', NULL, '34.1.22.121', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiOW5BdVlMUTFvQ2trb2xrRHdMb0dmdlFNdEdVelZlREZwMlJJb1Y0RiI7czo1OiJlcnJvciI7czozODoiVmV1aWxsZXogdm91cyBjb25uZWN0ZXIgdmlhIGxlIHBvcnRhaWwiO3M6NjoiX2ZsYXNoIjthOjI6e3M6MzoibmV3IjthOjA6e31zOjM6Im9sZCI7YToxOntpOjA7czo1OiJlcnJvciI7fX1zOjk6Il9wcmV2aW91cyI7YToxOntzOjM6InVybCI7czozNToiaHR0cHM6Ly9pbnNjcmlwdGlvbi5jYXAtZXBhYy5vbmxpbmUiO319', 1761129575),
('sQF9VpQZaFZtWL0gG5zKQ6vifHggT0nGdg5Sk6FA', NULL, '2a02:4780:27:c0de::3cc', 'Go-http-client/2.0', 'ZXlKcGRpSTZJbW80SzFsV1QyOVhXVzFzY0dSWU9VNVhSMDF1VEVFOVBTSXNJblpoYkhWbElqb2lLMjFYWkVRdmFISjZSVXB2ZHpSc1oxSnZVWEo0TkdkQ1NEQkxkbnBHV25SWU1YSldiM2xHTjJ4aUt6QXZUMDlFTW5RMVEwNUhaME5OYjB4WE5FMW5LMVkzTVVkeFJVMHpRblpWVXpoRloyeFFXV3BWVFRNclVYRkxWMmhJYjA0MGNGQXpXVFp5UVdSMlYzbDFVbFJFTm1KcFJ6SllMMEVyTlZSMVpFRkZWbUp0TVdzM1FtZEVlbkZhVWprclRWQXJlRmxQTjNkRFVYUXZPR3h1Tm5WWGMyZElUWE5qWWpOSldHcDBTMDVNVmxkUVV6TjJja05GYTFOR1JFbHJlVUYzWTJwdk9HdEdUekJMTlM5UlZtdFdkR0k0Um1KRUsxWjFkbFpEWWtKVWIwSk5kVXRxVFZCclowMVNiVmxaU0ZKR1IxVTRkRVV4VjBVeFEweGxaR05LZFNJc0ltMWhZeUk2SWpNME9HVTVOMkUxTWpnMVptRmtaalprWVdOaE1qWXdPV0ZtWW1Wa1pXWXhOVFUxTVdWak9UWmlNVFZtTlRBMllqWTFNelE0T0RreE9EQmpaVE15WmpFaUxDSjBZV2NpT2lJaWZRPT0=', 1760597923),
('SvNtqPv4z4g4eo1SakzfbwXYSJpTJuHswurXIZhm', NULL, '137.255.142.32', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiYkRqVDhzQ3hDM1pqYzBYdXVxV0xlTklmcGMzSk9YQmxQSGtMNkgxOCI7czo1OiJlcnJvciI7czozODoiVmV1aWxsZXogdm91cyBjb25uZWN0ZXIgdmlhIGxlIHBvcnRhaWwiO3M6NjoiX2ZsYXNoIjthOjI6e3M6MzoibmV3IjthOjA6e31zOjM6Im9sZCI7YToxOntpOjA7czo1OiJlcnJvciI7fX1zOjk6Il9wcmV2aW91cyI7YToxOntzOjM6InVybCI7czo0OToiaHR0cHM6Ly9pbnNjcmlwdGlvbi5jYXAtZXBhYy5vbmxpbmUvc3R1ZGVudHMtbGlzdCI7fX0=', 1760456952),
('TntRzjuZ5wfOZWT27bwkJuef0v5JxZhLItLTvAyI', NULL, '2a02:4780:27:c0de::3cc', 'Go-http-client/2.0', 'ZXlKcGRpSTZJalpIUVdWdk1FZHlWbUYwZW5WSmNqaFpTMm96VjNjOVBTSXNJblpoYkhWbElqb2lRa2hTVjNkRU5FeDZUVTB2WTBwemJtOVRiRXBRUWl0bVZVNHZjMmRPYlZKNEx6QTJhVkEyVG0xck1UUmpVRXhVVUdveEwyMXhOMkZNUzNkR2FrbG1SMkV4ZVVOek1tVk5SRlZhUVVaT1RuZzNhMnRZUkUwdmMzZFhXSHBrVEU5UlpGZFJlVkIzVlRGRlNDdFpOMlp6WlRGT1FtdHBaekpqTlZaM2MyMUtRbmxPWmtwSk15dG5SVEpRZWtOUVdGaGFZbHB1U1ZVeVNYRTRhMmhvU2xwRUsybGxWMFZaWXpaRVEyUnVWMHBPTUV4aVRXcHJWSGd4UW5vMU4yaEJSMDlPY2pkemJHcFBZVWhTVlRacmVpczRMMjQ0VDB3d1luZFlVSGw2VFdwUlZqQXlka3R0ZUM5SFowbDBXVXBZZGxKQ1NITlJXVTFhZWxaelFqVXhNMWsxTHlJc0ltMWhZeUk2SWpSaFlUbGhZamxsTVdGa016ZGtNRGhtTXpoaE5HWTJObUl3WWpZNU0yRmtaR1UzTkRVeU4yTmlNRE01TVdOak9HRXlPVFl3WmpOalptRTRZamhtTkRFaUxDSjBZV2NpT2lJaWZRPT0=', 1760770360),
('TrBOJxJ1jHifNLZMalPjPatO2QWQ0SOWxmEzp2ag', NULL, '2a02:4780:27:c0de::3cc', 'Go-http-client/2.0', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiaEdKc2dqb3lxeGRZWXVYTFRrRVhrcEdhUmY3SDQ0YmYyTVRNdjNFbiI7czo1OiJlcnJvciI7czozODoiVmV1aWxsZXogdm91cyBjb25uZWN0ZXIgdmlhIGxlIHBvcnRhaWwiO3M6NjoiX2ZsYXNoIjthOjI6e3M6MzoibmV3IjthOjA6e31zOjM6Im9sZCI7YToxOntpOjA7czo1OiJlcnJvciI7fX19', 1760688775),
('TsqQYvNioAQicOXvIYXEQVKbp8wzPd3OcVr2SQhv', NULL, '35.179.178.153', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/91.0.4472.124', 'ZXlKcGRpSTZJbFZDYlM5Sk4yaG9LMmxVYzI5V1pWZ3dTa3AxT0djOVBTSXNJblpoYkhWbElqb2lTbGhuYTA5bVNqa3labXh0VldjMGVWWmpiMWxZVTFsUmREVmFXSFYyT0M5VlJUbHFhMnRUSzFKR2FGRTBObEp1ZGpsWlJHTTBORlJpVkdGMll5czVVRTlpUW1welNEZHJXa05KZFRsNUwxSTBkM05CZDI1NFpXeFBPVGRNUzNoYVZscDZkRFpvVmtzd1ZXOHJZVWRPWjJJNFZISjZSblF4V0dJMlRVbDFNaXRrTVVSdk56Uk9Za2RHTlZsU1VGQmtUVWhHYm13dlduVkNNVGtyU0hGUE5VMU9WM3BSYUUxQ1QxRkxaVXRyYjFaRk1sWjFVWEZqWWtGb2JHOXdaWFZaZGpCMGFXOXBkSGxWWWtwaGRWbGpiV3REWW5oa1RYRTJUVFpoVkcxTVJFWTBWVlJMVlhsV2VXTnFPWG8ySzNWWFFXdE1NRkZqV2xsQ2FqaG5VVFZCYTNsalkycGhValIwZWtoRU9VbFJTRTVDWTB4Sk1FTlJlSGRCUTJjNFpVWlNjalpVYTJVMlFUbE1kRXQxWlhkNFVHeHJhemh3VWpSVlp6WlJUWGQ0YjNWMmJ6QjZWR1phTjNneWFHOTBNSFZPVFdWak1FRkNOVlJCY0dZMVVTOTNVVE5STm1JdlIxQkhXbXAwZWs1WGNuaFJaVlJJWjJKcFZIcFdWbXMxWlZaTElpd2liV0ZqSWpvaU9EZG1aak13TldZd01tVm1NalJoTnpsa05tUTRZVEJpT0dVeU16WmxNRFZrTkRCa1lqbGtOVEUzWkRWa1pEbGlZakkzTlRRMVpEZGxOR1JqWTJNd015SXNJblJoWnlJNklpSjk=', 1760740033),
('TttIdk7CU3EUfK8RE57nn4OxbNvXLh6V9QXYlHY7', NULL, '2a02:4780:27:c0de::3cc', 'Go-http-client/2.0', 'ZXlKcGRpSTZJbEJrVEd4dk4wUkJSVFJsVEcxTllVSnNiemhaZFZFOVBTSXNJblpoYkhWbElqb2ljaXRYWVhFMGVHOUdNV0Y2ZUdsdlp6WkhOM2xGVFRSdlpFbzNSVW8yTUVsMWFDdHlOV013VWxKRlFsRkVlR05aYURsdmJrTllibEF6YW1SSVVrMVJMMDVqTDA1akwwaEJVbXc1SzFsS01FVjZkV3ROVkZwMVRqaDVlRzQyVlVOMVptNDVNbUppYkhsV1dURkxTV2h4ZFRCU1NqTkphamh6YmtGR1dWSjBPWGxtVVV4dldIRlZZVVowWTBKNmRpOU1kamRLU0U0eWNIQm1lblJEVUdaYVRsQldUVkZLTmtGekswMXFSbTV4UzNWVFVUbG1ZalZKSzFVNWIwTlZjVEYwV25oeGRrTkpSRTVCVUc5aVZYaFlXbUZuWmtOMGRuaGFjMmhaVGs1WE0zVkJhVzFJYm1veWNWUXlUMWxWUWtKak5tZGtibVowYkRONU1GZGljVTlxTVNJc0ltMWhZeUk2SWpVM05EbGpaamt4TVRWa09ERmhNV1ExTVRGa01qaGlZVFkyWldReU16WTJNVE00TWpNMFlUazRaR1UxWmpFNE5UTmxaREUxWlRGa00yVTBZVGs1WTJFaUxDSjBZV2NpT2lJaWZRPT0=', 1760933690),
('UN2TebLhuTV3yHltweRSWpqYLvaASJTDa3TRBUrQ', NULL, '44.250.151.146', 'Mozilla/5.0 (compatible; wpbot/1.3; +https://forms.gle/ajBaxygz9jSR8p8G9)', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiOVUwTXVLQzV3azVqcG54UDRvNjU0Qk03MGVvRkUyS1dsMDVENnlXaiI7czo1OiJlcnJvciI7czozODoiVmV1aWxsZXogdm91cyBjb25uZWN0ZXIgdmlhIGxlIHBvcnRhaWwiO3M6NjoiX2ZsYXNoIjthOjI6e3M6MzoibmV3IjthOjA6e31zOjM6Im9sZCI7YToxOntpOjA7czo1OiJlcnJvciI7fX1zOjk6Il9wcmV2aW91cyI7YToxOntzOjM6InVybCI7czozNToiaHR0cHM6Ly9pbnNjcmlwdGlvbi5jYXAtZXBhYy5vbmxpbmUiO319', 1760476538),
('UVd9lBrXFHasQ07Jl9goTdJ9w18CcJjALl5Zvz0C', NULL, '192.36.109.104', 'Mozilla/5.0 (X11; CrOS x86_64 14541.0.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.3', 'ZXlKcGRpSTZJbmgxVUc5c1ZtaHNkRW81V0hodGFUQkZVRkpIT1djOVBTSXNJblpoYkhWbElqb2lWbkJCTDBwRGQyOXViV1ZVV25jNWRraHVaRGRtYVZZMmQxTklha2xLWWtSME1WbDRZVll3TVZsU2JXeEVUM1EyTW5KWWJXVnpWRFYwZVZKbGNIQnRjbU5KTVhWUFV6WnhNRXh1VTBJMmRGUmFlbUoxUWk5Q2VqaEZSWFZ6VnpGME5rdEpaV3R4WXpablVGWTFMMVpuZVhOQ1IzVnBka2xTTm5saU1sVjVVR0pPWVdReVdqSmxjM05pTVVveGQySkRaR0pZTUdOMFVEZE5lVU5TVTNsUVQzRkdaalZsYTFkcVUyMURlVkJRTjFwNlVHbHBhQ3RzT0RaaWRGY3plVFZRVjJoUmF5dFRRbkJVTkVWUWRXWTJUeTkzVTNaR1NEaEVjMk5IYzBnMVoyRTRZM0p0TDFkUU9YZG1OekJsVWxneVdGcDVVMWhRVlZWMVJtdEZjRTFPU1Rod01FaHhNbUV4ZFVsWGVVTXhaekl2TVRSdGFISjVSek0zVDA1bk0zQnZTbU5uVGt4TWJIZDFOVTlrYjBSMFZtbFFSVUo0VFd4QlpIaFlieXRZWTJsYVlWUTFlalZ4TjIxQ1JqSTBiMEZhYnpaM0wyVXZlR2wxT0d4NmRHeFpaR1JhYld3MllWaHdSMGw0U1RoblZtaEJOaXRsVG1WeE5WWjRjMjVQUWpCTElpd2liV0ZqSWpvaVptRmtPV1V6WWpOak1qSXhOek0yTnpneE56TTVZV1JtTjJKak5tSmpZMlEzWVdVM01XSm1ZMk5rWlRsaU1qUXpPV1F5TlRreE4yWTJNMlpsWm1Nd1ppSXNJblJoWnlJNklpSjk=', 1760475894),
('vLq4WCXdnwyRUOPGp2wg0iw7vnaFeI0TG1tRK3Id', NULL, '2a02:4780:27:c0de::3cc', 'Go-http-client/2.0', 'ZXlKcGRpSTZJbVZSWkhsdFVWUlJPVzUxUzA1dU9GcHFjV2w0WWtFOVBTSXNJblpoYkhWbElqb2lhWFE1UlM5R05ubE5TM0psZHpaNFpHaHJaa05UY0RjMVJYaHZjM2t4VG1ZdmQxQkZTMWhyU0cxRWJYZzNkMEZJTlVKUU9IZEVWRFJuUlZjMFdsUkJXbUZCU0VoVE9USXhkaXN2Ym5ab1ZreFpWMEZ6Y0hnd2RISndlR3BEYUVKVFZWTnZlazF3VFZsbWVHNXZka3B0Um1WTWNFOVpZbGxOYkRscFlTOUhTRmNySzNSd09VRmxiMVZoTkVScmIyNTNWbFpLU0dGWVZXeGhlRlZ2VGtZelNtZ3ZURXBFWm1NeVZ6WnBaQzlXV1ZOMUswVk1PRk4yU0ZSYVFVbHNSekpaWkdWbFExQlBhelJGWjNoc2FTOVZXR1pwV2k5bWFHbG5Xa3hTTkVWNU4xWnFaazFUVjBWS1NGZE9jbXMxVmxSb1JHMDRlRGhHU2xCeEsxcEZTRzVHSzBFNGIyNTFORGhMYkU5SlJtRnJhMlZVU21wc1JFRTlQU0lzSW0xaFl5STZJakkyWTJVMVpXUTNNamt4TURNeVlXVmxORGMzWWpka04yTXpPV1F4WWpBMVptVmlaV000WkdGaU5XRTNORGxrTjJSalpEVTVaakZpTmpGaE5qSmpPVFFpTENKMFlXY2lPaUlpZlE9PQ==', 1760838032),
('vnLyf1XlTsNzEJiv7hVp4g67Xo01yuJsuUOMcDCS', NULL, '41.216.53.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiT05teUhJZXlPUkFROTJ6d3FRcDB3M1FzU3UybzNUeEI4MGtIUFhLZiI7czo1OiJlcnJvciI7czozODoiVmV1aWxsZXogdm91cyBjb25uZWN0ZXIgdmlhIGxlIHBvcnRhaWwiO3M6NjoiX2ZsYXNoIjthOjI6e3M6MzoibmV3IjthOjA6e31zOjM6Im9sZCI7YToxOntpOjA7czo1OiJlcnJvciI7fX1zOjk6Il9wcmV2aW91cyI7YToxOntzOjM6InVybCI7czozNToiaHR0cHM6Ly9pbnNjcmlwdGlvbi5jYXAtZXBhYy5vbmxpbmUiO319', 1760512637),
('vsxJfEWFQp0dRnD0av9Z9w9q0Bq2jM6aTfFGJgBA', NULL, '137.255.52.151', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiT1d4dms0ekluS21CNDdYV3k1VG1TZXFIbkJETEt2Zkc2UkdPWUZxZSI7czo1OiJlcnJvciI7czozODoiVmV1aWxsZXogdm91cyBjb25uZWN0ZXIgdmlhIGxlIHBvcnRhaWwiO3M6NjoiX2ZsYXNoIjthOjI6e3M6MzoibmV3IjthOjA6e31zOjM6Im9sZCI7YToxOntpOjA7czo1OiJlcnJvciI7fX1zOjk6Il9wcmV2aW91cyI7YToxOntzOjM6InVybCI7czo0OToiaHR0cHM6Ly9pbnNjcmlwdGlvbi5jYXAtZXBhYy5vbmxpbmUvc3R1ZGVudHMtbGlzdCI7fX0=', 1760997940),
('wb1CNpdHjm6GxfCrB9wexIuyfkJz8TgaiI10CshB', NULL, '54.79.197.235', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Gecko/20101207 Firefox/23.0', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiVlQ0U2lkb0JBdk1qVUJSWXFvYjU5bVRjV3pvT0M3aUozM1NGdnZJVCI7czo1OiJlcnJvciI7czozODoiVmV1aWxsZXogdm91cyBjb25uZWN0ZXIgdmlhIGxlIHBvcnRhaWwiO3M6NjoiX2ZsYXNoIjthOjI6e3M6MzoibmV3IjthOjA6e31zOjM6Im9sZCI7YToxOntpOjA7czo1OiJlcnJvciI7fX1zOjk6Il9wcmV2aW91cyI7YToxOntzOjM6InVybCI7czozNToiaHR0cHM6Ly9pbnNjcmlwdGlvbi5jYXAtZXBhYy5vbmxpbmUiO319', 1760526852),
('wqgSdMwpYwt7kbRm9dKAev4ifV5ft6CcfdVpedxl', NULL, '23.146.184.190', 'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/44.0.2403.157 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoickVQcGEzOGJiUFlBTnl5T1I1V2hXTXlIR3V6TGhGdnJxYmt0a1BVMiI7czo1OiJlcnJvciI7czozODoiVmV1aWxsZXogdm91cyBjb25uZWN0ZXIgdmlhIGxlIHBvcnRhaWwiO3M6NjoiX2ZsYXNoIjthOjI6e3M6MzoibmV3IjthOjA6e31zOjM6Im9sZCI7YToxOntpOjA7czo1OiJlcnJvciI7fX1zOjk6Il9wcmV2aW91cyI7YToxOntzOjM6InVybCI7czozNToiaHR0cHM6Ly9pbnNjcmlwdGlvbi5jYXAtZXBhYy5vbmxpbmUiO319', 1760629899),
('wVmG0HVAH27qRctnEiPKfsOlDZEsokvF56lsuEmI', NULL, '41.85.162.141', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiSVRlSTFzTFlCUUF0NHBZcDlCeU1lYWkzazBXcmh6VkJNc3ViVlVGbSI7czo1OiJlcnJvciI7czozODoiVmV1aWxsZXogdm91cyBjb25uZWN0ZXIgdmlhIGxlIHBvcnRhaWwiO3M6NjoiX2ZsYXNoIjthOjI6e3M6MzoibmV3IjthOjA6e31zOjM6Im9sZCI7YToxOntpOjA7czo1OiJlcnJvciI7fX1zOjk6Il9wcmV2aW91cyI7YToxOntzOjM6InVybCI7czo0OToiaHR0cHM6Ly9pbnNjcmlwdGlvbi5jYXAtZXBhYy5vbmxpbmUvc3R1ZGVudHMtbGlzdCI7fX0=', 1761133212),
('WWaFjN5rl1F2NHVOr1gCX9HameN03ZCndMeBgv9g', NULL, '2a02:4780:27:c0de::3cc', 'Go-http-client/2.0', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiNEdUOGJlOWhkcEJpSHRNcDV0TWZPdHNHdkVzNTlzekhkUkY3cjFHQSI7czo1OiJlcnJvciI7czozODoiVmV1aWxsZXogdm91cyBjb25uZWN0ZXIgdmlhIGxlIHBvcnRhaWwiO3M6NjoiX2ZsYXNoIjthOjI6e3M6MzoibmV3IjthOjA6e31zOjM6Im9sZCI7YToxOntpOjA7czo1OiJlcnJvciI7fX19', 1760507375),
('XLCcn1YgmdM5ELDsEBuMz6d616jGTydglt17IrKU', NULL, '18.141.225.240', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.3', 'ZXlKcGRpSTZJbHBUV1V0SGJGSXpVVFZLWkZoRk1EWmhVbVZwT0hjOVBTSXNJblpoYkhWbElqb2lRMU5QVEcxbFkwRlZkMWh5T0ZSV1pqQnlRMmhNT1hWYVYzWkpZVE4yZEVsTFdtcDFNMEpXVXpOak0yOVBVVVZSWXpSbFRHVjJTWFEzUmpGbE5UVkJSbGQxWkVWUE4wbHliR3RsVURKalptdHNWR2s0SzJKSFVHTndWMUpTVVZOVmFtTnBTRk56WmsxQlYwMURXVUpEV2tWWWIzY3dNemRTTkZWWVMzaHdlVzVCVDAxaWREaFVkV1pVUVhCdlRFbDNiVWhZTjJSaGRUWlJjalpPWTBnclRYWlpWbGt2VURSWmNUQmFjRnBzUkZsbWQwNURiakozUzJsQk5VNVZLMFJXYlhSUWJWTnFkVTVGU0daU1UzUnZibFpUWmtoVlNVVnVZMUZSYW1WblJuaDNiSEJYUkVZeU1VdFlSMDl0T0doNGFDdEViVmRzWTNkMFJucEJjVXB2TTFBNUt6azFUaTh3WVZkWWFEZEdRVUp5Ym1jM05TOVJWRmhrUWxWSlVHdERWMFowZGk5TmFrTjNiRUkwVTBGWGRsbEVjRzR3VlhGTk5WbEJUWElyT0hCT1VVSkVlbEpwWld3eFUzUjViblIyTDJOSkwweFZlVlp6YUdkR2JFSm1VV3RtWW1aUVNGUTRkRzFVVHpWV1FVNWtaRk5TZWtORlVpdFdjRTR4UVVVMklpd2liV0ZqSWpvaU56STVNek5sTmpBeU0ySXhOMlJoWWpRM1pUVXlNelJqTUdKbU1tUXlNelV5TWpjeFpEVmhNR0k1Tm1NMll6ZGlaRGMzWkRGbVlXSTBNMlJpTm1JNU1TSXNJblJoWnlJNklpSjk=', 1760968146),
('xOvmfbAUJYg51OJrP0IVulb8a40QQJDb1Qe6k1zG', NULL, '2a02:4780:27:c0de::3cc', 'Go-http-client/2.0', 'ZXlKcGRpSTZJbU5RZVVaamJYcGlNalJEVFhnck1EWTFSemxEYm5jOVBTSXNJblpoYkhWbElqb2lTSEZ1UW5aMlZUZEJXbk55ZWxOeVJtRmlOakpDTDJKaE9HdEVXazFHYjJSWFNWVnZlRGRyU1hKYVNYcHpZVk5tYnpOc1NqY3phSGRQT1dSSGJtb3hTakJ3TWxwdmIzQkdaSFphV1dFNGNqbEVXREpyZWxjM05FRTRkR2xKVlZOaFQyMVVSa2hJUTFWMFdsSk5VelJrV1RWRU5VVkpjR1YyUkhwSllrcDJRV3MyWW1sRFVTOHpZVFUyUVVkWU1FOWlaRlozUmpGcmREWktVMmxrTXpjMlEwSmlZa2RPTHpSTVIzRnZWR2xsVURoMk5tNVplbnAwYURGcFdWYzRjVzkyU1hoMmVVSm9lakowVW5ONFptNVlkV1poU1hseFpUSXZSMVY0V1dOdk5rTTRXRnB2YUZST1JGRm1MemhNVW5obFFrVnVabVpGYTNZclJFdDRXV1pzYmxoR2FrZGpUSFp0ZVdGd09VbFZRMGRGV0dKTGFYYzlQU0lzSW0xaFl5STZJbUZoTnpCa1pHWXhZVEU1TmpOaFpqRXdOVFF6TlRNMk1HVTRORGs1T0dSbFlUQmhZemRoWmpNMU9XVXdaRGszWXpaa1lqSmhZbVJtWWpBMVpXTXlZVFFpTENKMFlXY2lPaUlpZlE9PQ==', 1760920262);
INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('xZh3NVIYEX0IzmYjyY7d3WgqIE1dIYg3dfv0yZsW', NULL, '137.255.78.166', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoibGFVVDZBclhSaVcwV0Z1SU45WGpxOTR5ekZiM01zV3NtcXVVMkZLbyI7czo1OiJlcnJvciI7czozODoiVmV1aWxsZXogdm91cyBjb25uZWN0ZXIgdmlhIGxlIHBvcnRhaWwiO3M6NjoiX2ZsYXNoIjthOjI6e3M6MzoibmV3IjthOjA6e31zOjM6Im9sZCI7YToxOntpOjA7czo1OiJlcnJvciI7fX1zOjk6Il9wcmV2aW91cyI7YToxOntzOjM6InVybCI7czo0OToiaHR0cHM6Ly9pbnNjcmlwdGlvbi5jYXAtZXBhYy5vbmxpbmUvc3R1ZGVudHMtbGlzdCI7fX0=', 1761060542),
('ZC8a5FC8AxWbYTz07HLXNkW4CDxN6ga1rjnrWEuO', NULL, '2c0f:53c0:603:9f00:91bc:2ab5:b9fd:a99f', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'YTo3OntzOjY6Il90b2tlbiI7czo0MDoiY0RMVGxHdkZncFl4eDNWUjNLV25valB6YlJaTzVKd3RxeUVlbXREcyI7czo2MToibG9naW5fYWRtaW5pc3RyYXRpb25fNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToxO3M6MTI6InBvcnRhbF90b2tlbiI7czo1MjoiMTQ3fHNvOVBtNXc4TVZJWjQ1ZGlINDB4UVNkUHRjblJha082bkJja3hwaWIwOTA2ZmEyMCI7czoyMzoibGFzdF90b2tlbl92ZXJpZmljYXRpb24iO086MjU6IklsbHVtaW5hdGVcU3VwcG9ydFxDYXJib24iOjQ6e3M6NDoiZGF0ZSI7czoyNjoiMjAyNS0xMC0xNyAwODowOToxNi41NjUyNjgiO3M6MTM6InRpbWV6b25lX3R5cGUiO2k6MztzOjg6InRpbWV6b25lIjtzOjM6IlVUQyI7czoxODoiZHVtcERhdGVQcm9wZXJ0aWVzIjthOjI6e3M6NDoiZGF0ZSI7czoyNjoiMjAyNS0xMC0xNyAwODowOToxNi41NjUyNjgiO3M6ODoidGltZXpvbmUiO3M6MzoiVVRDIjt9fXM6MTA6ImF1dGhfZ3VhcmQiO3M6MTQ6ImFkbWluaXN0cmF0aW9uIjtzOjk6Il9wcmV2aW91cyI7YToxOntzOjM6InVybCI7czoxMTQ6Imh0dHBzOi8vaW5zY3JpcHRpb24uY2FwLWVwYWMub25saW5lL3BlbmRpbmctc3R1ZGVudHM/YWNjZXNzPTE0NyU3Q3NvOVBtNXc4TVZJWjQ1ZGlINDB4UVNkUHRjblJha082bkJja3hwaWIwOTA2ZmEyMCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1760688625),
('ZYGmNaBARmum2Tg2vFb0DW7PWMijnGIXqaBudDAZ', NULL, '34.158.50.168', 'Mozilla/5.0 (Linux; Android 5.1.1; SM-J111F)', 'ZXlKcGRpSTZJbHAzZVZkTVpqWlZSWE54ZWpKc1ZVbFBkRXBOY0VFOVBTSXNJblpoYkhWbElqb2lhbmRzVW5Ca05tdzJiRmxvUkVobVV5dHRWM2RoZEc1UVdqWldTSEl4ZG1kaVZqa3pUeTk0ZVRRNGNXVTRVREUyUm01U01GTnNlRlZwZEZGdFdVcHpNRzlXYkVGd2NsTlhOVGRxY2tJM1RHUlZaVTlyWkhOaFMwcFNWRzVLYTNKMkt6UldXRlZ5VnpsS2NVZHpNekpMV2xKTk1YSlJXbUpNYjFoTVFraDBWRGhvVkhoaWQwRmthMGc0TkVrMVdUVTRZWGQ2VEVKNEwwZFJlRU5ZYkV0dFp6VlRlV2RpVmxZNFdtNW5aMFpqWVcxamRVWjRaVEpZU1UxQ2JWWkhTV3g2Y1RWbGIwSnZjR0p3UmxwdlkwcEZiblJWYVdselJHaFdVMGRUZUhkRmNtTlRVSEpqUkhKWE1EZERkRlZKVlZGSGRuRkpOVkl6VkZrNE9FZG1NMGxKTUVWSlYycDNWVkZLZVVaV1MzZFdibTFuVWsxR1NXNVBRVlJtTldkRlZ6QkpjVFpoWVdKNFFreGxZVTlUV25oNFltaFZTME16ZEZoWFlWQkxVVTQyYW1Gd1FVSkxaQzlvYVVSRVRrZEVOVmczTUd3dlVqWkpTMXBSY21aRGFTdEJWRVpRTmtWVVRuVXdNVUpIWXprMGJURlpiR0pDVFVGMVZXSlVSMnBXVnpSS0lpd2liV0ZqSWpvaU16VTRZalU0TVRWak5tVmxPV1kyWVRjM05UYzFOREppWm1NMFpHVTVZamhoWVdaa05EVmlORE0yWVdNeE1XWXdNemxrWW1SbE5tUmhPVEU0TmpJMVlTSXNJblJoWnlJNklpSjk=', 1760587113),
('ZZ5xDsMdUKzZuBBHebO4rrnfiJpbXeH713G71lTY', NULL, '2a02:4780:27:c0de::3cc', 'Go-http-client/2.0', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiVWgwaU80U2VWdkE4bWtnWlAxZFpUdVJPMU9VaFRUcmJxRzZicng5cyI7czo1OiJlcnJvciI7czozODoiVmV1aWxsZXogdm91cyBjb25uZWN0ZXIgdmlhIGxlIHBvcnRhaWwiO3M6NjoiX2ZsYXNoIjthOjI6e3M6MzoibmV3IjthOjA6e31zOjM6Im9sZCI7YToxOntpOjA7czo1OiJlcnJvciI7fX19', 1760606605);

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `student_id_number` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `student_id_number`, `password`, `created_at`, `updated_at`) VALUES
(1, '11261012', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(2, '21381024', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(3, '11471415', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(4, '1110705', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(5, '10652712', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(6, '1372304', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(7, '21380924', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(8, '11417515', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(9, '14512324', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(10, '1808803', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(11, '19254713', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(12, '11324815', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(13, '10984919', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(14, '11111412', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(15, '21381424', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(16, '1212203', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(17, '21381124', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(18, '11159013', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(19, '11356910', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(20, '21381224', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(21, '1743005', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(22, '11084607', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(23, '1483505', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(24, '10220208', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(25, '11154209', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(26, '19835114', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(27, '21380324', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(28, '21380824', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(29, '21380724', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(30, '11261409', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(31, '11144406', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(32, '11159614', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(33, '10748108', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(34, '1519004', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(35, '97133991', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(36, '20538618', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(37, '21380024', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(38, '13605216', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(39, '21381324', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(40, '10543607', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(41, '11376412', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(42, '21380624', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(43, '21380224', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(44, '10292107', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(45, '21380524', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(46, '21380124', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(47, '18627218', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(48, '66967618', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(49, '10598408', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(50, '10219908', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(51, '17654214', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(52, '12172816', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(53, '10351208', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(54, '12424313', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(55, '10512608', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(56, '11223515', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(57, '21380424', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(58, '10029109', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(59, '13634216', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(60, '10301012', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(61, '21294723', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(62, '21295323', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(63, '10328509', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(64, '10436109', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(65, '227301', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(66, '21294923', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(67, '19216618', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(68, '20042515', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(69, 'ABGC1323', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(70, '309716708', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(71, '21295223', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(72, '21295523', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(73, '21295623', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(74, '10721412', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(75, '1429504', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(76, '11013606', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(77, '2039805', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(78, '11140912', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(79, '10395010', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(80, '11087111', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(81, '10889010', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(82, '11238106', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(83, '21299723', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(84, '12176009', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(85, '12265109', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(86, '21295723', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(87, '21294823', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(88, '17791914', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(89, '21295423', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(90, '10848811', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(91, '89999', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(92, '10888112', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(93, '10147112', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(94, '10017410', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(95, '19484414', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(96, '11203312', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(97, '67300053', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(98, '10522310', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(99, '11746815', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(100, '10033808', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(101, '21273723', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(102, '12349913', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(103, '1879603', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(104, '21379824', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(105, '96311584', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(106, '11368419', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(107, '11544810', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(108, '11503710', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(109, '1734505', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(110, '11746915', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(111, '21379924', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(112, '10911410', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(113, '30278718', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(114, '11009306', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(115, '1630104', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(116, '21297123', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(117, '10615912', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(118, '21297023', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(119, '12163109', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(120, '10846111', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(121, '14185014', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(122, '21297223', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(123, '21296923', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(124, '10136508', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(125, '21159522', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(126, '15968117', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(127, '21381724', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(128, '21381924', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(129, '19247418', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(130, '12171016', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(131, '21381524', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(132, '10513012', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(133, '1248305', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(134, '16399716', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(135, '10180411', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(136, '11282419', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(137, '21381824', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(138, '21297723', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(139, '10276608', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(140, '1553305', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(141, '21297623', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(142, '11086110', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(143, '21381624', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(144, 'nan', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-10 15:05:09', '2025-07-10 15:05:09');

-- --------------------------------------------------------

--
-- Table structure for table `student_pending_students`
--

CREATE TABLE `student_pending_students` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `pending_student_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student_pending_students`
--

INSERT INTO `student_pending_students` (`id`, `student_id`, `pending_student_id`, `created_at`, `updated_at`) VALUES
(1, 1, 8, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(2, 2, 9, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(3, 3, 10, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(4, 4, 11, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(5, 5, 12, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(6, 6, 13, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(7, 7, 14, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(8, 8, 15, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(9, 9, 16, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(10, 10, 17, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(11, 11, 18, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(12, 12, 19, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(13, 13, 20, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(14, 14, 21, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(15, 15, 22, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(16, 16, 23, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(17, 17, 24, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(18, 18, 25, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(19, 19, 26, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(20, 20, 27, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(21, 21, 28, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(22, 22, 29, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(23, 23, 30, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(24, 24, 31, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(25, 25, 32, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(26, 26, 33, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(27, 27, 34, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(28, 28, 35, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(29, 29, 36, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(30, 30, 37, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(31, 31, 38, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(32, 32, 39, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(33, 33, 40, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(34, 34, 41, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(35, 35, 42, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(36, 36, 43, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(37, 37, 44, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(38, 38, 45, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(39, 39, 46, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(40, 40, 47, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(41, 41, 48, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(42, 42, 49, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(43, 43, 50, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(44, 44, 51, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(45, 45, 52, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(46, 46, 53, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(47, 47, 54, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(48, 48, 55, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(49, 49, 56, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(50, 50, 57, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(51, 51, 58, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(52, 52, 59, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(53, 53, 60, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(54, 54, 61, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(55, 55, 62, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(56, 56, 63, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(57, 57, 64, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(58, 58, 65, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(59, 59, 66, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(60, 60, 67, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(61, 61, 68, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(62, 62, 69, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(63, 63, 70, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(64, 64, 71, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(65, 65, 72, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(66, 66, 73, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(67, 67, 74, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(68, 68, 75, '2025-07-10 15:05:08', '2025-07-10 15:05:08'),
(69, 69, 76, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(70, 70, 77, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(71, 71, 78, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(72, 72, 79, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(73, 73, 80, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(74, 74, 81, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(75, 75, 82, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(76, 76, 83, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(77, 77, 84, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(78, 78, 85, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(79, 79, 86, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(80, 80, 87, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(81, 81, 88, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(82, 82, 89, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(83, 83, 90, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(84, 84, 91, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(85, 85, 92, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(86, 86, 93, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(87, 87, 94, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(88, 88, 95, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(89, 89, 96, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(90, 90, 97, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(91, 91, 98, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(92, 92, 99, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(93, 93, 100, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(94, 94, 101, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(95, 95, 102, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(96, 96, 103, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(97, 97, 104, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(98, 98, 105, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(99, 99, 106, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(100, 100, 107, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(101, 101, 108, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(102, 102, 109, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(103, 103, 110, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(104, 104, 111, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(105, 105, 112, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(106, 106, 113, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(107, 107, 114, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(108, 108, 115, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(109, 109, 116, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(110, 110, 117, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(111, 111, 118, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(112, 112, 119, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(113, 113, 120, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(114, 114, 121, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(115, 115, 122, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(116, 116, 123, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(117, 117, 124, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(118, 118, 125, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(119, 119, 126, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(120, 120, 127, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(121, 121, 128, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(122, 122, 129, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(123, 123, 130, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(124, 124, 131, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(125, 125, 132, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(126, 126, 133, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(127, 127, 134, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(128, 128, 135, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(129, 129, 136, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(130, 130, 137, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(131, 131, 138, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(132, 132, 139, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(133, 133, 140, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(134, 134, 141, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(135, 135, 142, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(136, 136, 143, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(137, 137, 144, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(138, 138, 145, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(139, 139, 146, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(140, 140, 147, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(141, 141, 148, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(142, 142, 149, '2025-07-10 15:05:09', '2025-07-10 15:05:09'),
(143, 143, 150, '2025-07-10 15:05:09', '2025-07-10 15:05:09');

-- --------------------------------------------------------

--
-- Table structure for table `submission_periods`
--

CREATE TABLE `submission_periods` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `academic_year_id` bigint(20) UNSIGNED NOT NULL,
  `department_id` bigint(20) UNSIGNED NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `submission_periods`
--

INSERT INTO `submission_periods` (`id`, `academic_year_id`, `department_id`, `start_date`, `end_date`, `created_at`, `updated_at`) VALUES
(15, 2, 1, '2025-06-20', '2025-12-31', '2025-06-21 16:48:48', '2025-06-21 16:48:48'),
(16, 2, 2, '2025-06-20', '2025-12-31', '2025-06-21 16:48:48', '2025-06-21 16:48:48'),
(17, 2, 3, '2025-06-20', '2025-12-31', '2025-06-21 16:48:48', '2025-06-21 16:48:48'),
(18, 2, 4, '2025-06-20', '2025-12-31', '2025-06-21 16:48:48', '2025-06-21 16:48:48'),
(19, 2, 5, '2025-06-20', '2025-12-31', '2025-06-21 16:48:48', '2025-06-21 16:48:48'),
(20, 2, 6, '2025-06-20', '2025-12-31', '2025-06-21 16:48:48', '2025-06-21 16:48:48'),
(21, 2, 7, '2025-06-20', '2025-12-31', '2025-06-21 16:48:48', '2025-06-21 16:48:48'),
(22, 2, 8, '2025-06-20', '2025-12-31', '2025-06-21 16:48:48', '2025-06-21 16:48:48'),
(23, 2, 9, '2025-06-20', '2025-12-31', '2025-06-21 16:48:48', '2025-06-21 16:48:48'),
(24, 2, 10, '2025-06-20', '2025-12-31', '2025-06-21 16:48:48', '2025-06-21 16:48:48'),
(25, 2, 11, '2025-06-20', '2025-12-31', '2025-06-21 16:48:48', '2025-06-21 16:48:48'),
(26, 2, 12, '2025-06-20', '2025-12-31', '2025-06-21 16:48:48', '2025-06-21 16:48:48'),
(27, 2, 13, '2025-06-20', '2025-12-31', '2025-06-21 16:48:48', '2025-06-21 16:48:48'),
(28, 2, 14, '2025-06-20', '2025-12-31', '2025-06-21 16:48:48', '2025-06-21 16:48:48'),
(29, 2, 15, '2025-06-20', '2025-12-31', '2025-06-21 16:48:48', '2025-06-21 16:48:48'),
(30, 2, 16, '2025-06-20', '2025-12-31', '2025-06-21 16:48:48', '2025-06-21 16:48:48'),
(31, 2, 21, '2025-06-20', '2025-12-31', '2025-06-21 16:48:48', '2025-06-21 16:48:48'),
(32, 2, 22, '2025-06-20', '2025-12-31', '2025-06-21 16:48:48', '2025-06-21 16:48:48'),
(34, 4, 18, '2025-07-11', '2025-08-28', '2025-07-11 14:48:35', '2025-08-06 10:44:11'),
(35, 4, 19, '2025-07-11', '2025-08-28', '2025-07-11 14:48:35', '2025-08-06 10:44:11'),
(36, 4, 20, '2025-07-11', '2025-08-28', '2025-07-11 14:48:35', '2025-08-06 10:44:11'),
(37, 2, 18, '2025-08-28', '2025-12-31', '2025-08-28 16:35:44', '2025-08-28 16:35:44'),
(38, 2, 19, '2025-08-28', '2025-12-31', '2025-08-28 16:35:44', '2025-08-28 16:35:44'),
(39, 2, 20, '2025-08-28', '2025-12-31', '2025-08-28 16:35:44', '2025-08-28 16:35:44'),
(40, 2, 17, '2025-09-22', '2025-12-31', '2025-09-22 12:14:21', '2025-09-22 12:14:21'),
(41, 2, 23, '2025-09-22', '2025-12-31', '2025-09-22 12:14:21', '2025-09-22 12:14:21');

-- --------------------------------------------------------

--
-- Table structure for table `ues`
--

CREATE TABLE `ues` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `credits` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `academic_paths`
--
ALTER TABLE `academic_paths`
  ADD PRIMARY KEY (`id`),
  ADD KEY `academic_paths_student_pending_student_id_foreign` (`student_pending_student_id`),
  ADD KEY `academic_paths_academic_year_id_foreign` (`academic_year_id`),
  ADD KEY `academic_paths_role_id_foreign` (`role_id`);

--
-- Indexes for table `academic_years`
--
ALTER TABLE `academic_years`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `administrations`
--
ALTER TABLE `administrations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `administrations_email_unique` (`email`),
  ADD KEY `administrations_role_id_foreign` (`role_id`);

--
-- Indexes for table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `classes_department_id_foreign` (`department_id`),
  ADD KEY `classes_academic_year_id_foreign` (`academic_year_id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `courses_uuid_unique` (`uuid`),
  ADD UNIQUE KEY `courses_code_unique` (`code`),
  ADD KEY `courses_ue_id_foreign` (`ue_id`);

--
-- Indexes for table `course_professors`
--
ALTER TABLE `course_professors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `course_professors_course_id_principal_unique` (`course_id`,`principal`),
  ADD UNIQUE KEY `course_professors_uuid_unique` (`uuid`),
  ADD KEY `course_professors_professor_id_foreign` (`professor_id`);

--
-- Indexes for table `course_resources`
--
ALTER TABLE `course_resources`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_resources_course_id_foreign` (`course_id`);

--
-- Indexes for table `cycles`
--
ALTER TABLE `cycles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `defense_jury_members`
--
ALTER TABLE `defense_jury_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `defense_jury_members_uuid_unique` (`uuid`),
  ADD KEY `defense_jury_members_defense_submission_id_foreign` (`defense_submission_id`),
  ADD KEY `defense_jury_members_professor_id_foreign` (`professor_id`),
  ADD KEY `defense_jury_members_grade_id_foreign` (`grade_id`);

--
-- Indexes for table `defense_submissions`
--
ALTER TABLE `defense_submissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `defense_submissions_defense_submission_period_id_foreign` (`defense_submission_period_id`),
  ADD KEY `defense_submissions_professor_id_foreign` (`professor_id`),
  ADD KEY `defense_submissions_room_id_foreign` (`room_id`);

--
-- Indexes for table `defense_submission_periods`
--
ALTER TABLE `defense_submission_periods`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `defense_submission_periods_uuid_unique` (`uuid`),
  ADD KEY `defense_submission_periods_academic_year_id_foreign` (`academic_year_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `departments_cycle_id_foreign` (`cycle_id`);

--
-- Indexes for table `entry_diplomas`
--
ALTER TABLE `entry_diplomas`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `grades`
--
ALTER TABLE `grades`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `grades_uuid_unique` (`uuid`);

--
-- Indexes for table `jury_fees`
--
ALTER TABLE `jury_fees`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `pending_students`
--
ALTER TABLE `pending_students`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pending_students_personal_information_id_foreign` (`personal_information_id`),
  ADD KEY `pending_students_entry_diploma_id_foreign` (`entry_diploma_id`),
  ADD KEY `pending_students_academic_year_id_foreign` (`academic_year_id`),
  ADD KEY `pending_students_department_id_foreign` (`department_id`);

--
-- Indexes for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`);

--
-- Indexes for table `personal_information`
--
ALTER TABLE `personal_information`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_information_email_unique` (`email`);

--
-- Indexes for table `professors`
--
ALTER TABLE `professors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `professors_email_unique` (`email`),
  ADD KEY `professors_role_id_foreign` (`role_id`);

--
-- Indexes for table `programs`
--
ALTER TABLE `programs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `programs_uuid_unique` (`uuid`),
  ADD KEY `programs_classe_id_foreign` (`classe_id`),
  ADD KEY `programs_course_professor_id_foreign` (`course_professor_id`);

--
-- Indexes for table `reclamation_periods`
--
ALTER TABLE `reclamation_periods`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reclamation_periods_academic_year_id_foreign` (`academic_year_id`),
  ADD KEY `reclamation_periods_department_id_foreign` (`department_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `rooms_name_unique` (`name`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `student_pending_students`
--
ALTER TABLE `student_pending_students`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_pending_students_student_id_foreign` (`student_id`),
  ADD KEY `student_pending_students_pending_student_id_foreign` (`pending_student_id`);

--
-- Indexes for table `submission_periods`
--
ALTER TABLE `submission_periods`
  ADD PRIMARY KEY (`id`),
  ADD KEY `submission_periods_academic_year_id_foreign` (`academic_year_id`),
  ADD KEY `submission_periods_department_id_foreign` (`department_id`);

--
-- Indexes for table `ues`
--
ALTER TABLE `ues`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ues_uuid_unique` (`uuid`),
  ADD UNIQUE KEY `ues_code_unique` (`code`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `academic_paths`
--
ALTER TABLE `academic_paths`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=145;

--
-- AUTO_INCREMENT for table `academic_years`
--
ALTER TABLE `academic_years`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `administrations`
--
ALTER TABLE `administrations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `course_professors`
--
ALTER TABLE `course_professors`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `course_resources`
--
ALTER TABLE `course_resources`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cycles`
--
ALTER TABLE `cycles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `defense_jury_members`
--
ALTER TABLE `defense_jury_members`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `defense_submissions`
--
ALTER TABLE `defense_submissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `defense_submission_periods`
--
ALTER TABLE `defense_submission_periods`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `entry_diplomas`
--
ALTER TABLE `entry_diplomas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `grades`
--
ALTER TABLE `grades`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `jury_fees`
--
ALTER TABLE `jury_fees`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `pending_students`
--
ALTER TABLE `pending_students`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=286;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=149;

--
-- AUTO_INCREMENT for table `personal_information`
--
ALTER TABLE `personal_information`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=287;

--
-- AUTO_INCREMENT for table `professors`
--
ALTER TABLE `professors`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `programs`
--
ALTER TABLE `programs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reclamation_periods`
--
ALTER TABLE `reclamation_periods`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=145;

--
-- AUTO_INCREMENT for table `student_pending_students`
--
ALTER TABLE `student_pending_students`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=145;

--
-- AUTO_INCREMENT for table `submission_periods`
--
ALTER TABLE `submission_periods`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `ues`
--
ALTER TABLE `ues`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `academic_paths`
--
ALTER TABLE `academic_paths`
  ADD CONSTRAINT `academic_paths_academic_year_id_foreign` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `academic_paths_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `academic_paths_student_pending_student_id_foreign` FOREIGN KEY (`student_pending_student_id`) REFERENCES `student_pending_students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `administrations`
--
ALTER TABLE `administrations`
  ADD CONSTRAINT `administrations_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `classes`
--
ALTER TABLE `classes`
  ADD CONSTRAINT `classes_academic_year_id_foreign` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `classes_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `courses_ue_id_foreign` FOREIGN KEY (`ue_id`) REFERENCES `ues` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `course_professors`
--
ALTER TABLE `course_professors`
  ADD CONSTRAINT `course_professors_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `course_professors_professor_id_foreign` FOREIGN KEY (`professor_id`) REFERENCES `professors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `course_resources`
--
ALTER TABLE `course_resources`
  ADD CONSTRAINT `course_resources_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `defense_jury_members`
--
ALTER TABLE `defense_jury_members`
  ADD CONSTRAINT `defense_jury_members_defense_submission_id_foreign` FOREIGN KEY (`defense_submission_id`) REFERENCES `defense_submissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `defense_jury_members_grade_id_foreign` FOREIGN KEY (`grade_id`) REFERENCES `grades` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `defense_jury_members_professor_id_foreign` FOREIGN KEY (`professor_id`) REFERENCES `professors` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `defense_submissions`
--
ALTER TABLE `defense_submissions`
  ADD CONSTRAINT `defense_submissions_defense_submission_period_id_foreign` FOREIGN KEY (`defense_submission_period_id`) REFERENCES `defense_submission_periods` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `defense_submissions_professor_id_foreign` FOREIGN KEY (`professor_id`) REFERENCES `professors` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `defense_submissions_room_id_foreign` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `defense_submission_periods`
--
ALTER TABLE `defense_submission_periods`
  ADD CONSTRAINT `defense_submission_periods_academic_year_id_foreign` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `departments`
--
ALTER TABLE `departments`
  ADD CONSTRAINT `departments_cycle_id_foreign` FOREIGN KEY (`cycle_id`) REFERENCES `cycles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pending_students`
--
ALTER TABLE `pending_students`
  ADD CONSTRAINT `pending_students_academic_year_id_foreign` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pending_students_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pending_students_entry_diploma_id_foreign` FOREIGN KEY (`entry_diploma_id`) REFERENCES `entry_diplomas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pending_students_personal_information_id_foreign` FOREIGN KEY (`personal_information_id`) REFERENCES `personal_information` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `professors`
--
ALTER TABLE `professors`
  ADD CONSTRAINT `professors_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `programs`
--
ALTER TABLE `programs`
  ADD CONSTRAINT `programs_classe_id_foreign` FOREIGN KEY (`classe_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `programs_course_professor_id_foreign` FOREIGN KEY (`course_professor_id`) REFERENCES `course_professors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reclamation_periods`
--
ALTER TABLE `reclamation_periods`
  ADD CONSTRAINT `reclamation_periods_academic_year_id_foreign` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reclamation_periods_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_pending_students`
--
ALTER TABLE `student_pending_students`
  ADD CONSTRAINT `student_pending_students_pending_student_id_foreign` FOREIGN KEY (`pending_student_id`) REFERENCES `pending_students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_pending_students_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `submission_periods`
--
ALTER TABLE `submission_periods`
  ADD CONSTRAINT `submission_periods_academic_year_id_foreign` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `submission_periods_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
