-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 04, 2025 at 05:54 PM
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
-- Database: `hkr_database`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `admin_id` int(20) NOT NULL,
  `name` varchar(50) NOT NULL,
  `email` varchar(50) NOT NULL,
  `password` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`admin_id`, `name`, `email`, `password`) VALUES
(1, 'Justin', 'blueblade906@gmail.com', '$2y$12$TYKXMjM6SF4aLrrjuXvRZOtzxp0UH.wpcRPL5hpnq6n544jq/KGp6'),
(2, 'Ace', 'acephilipdenulan12@gmail.com', '$2y$10$ZRJoZydh7H6sKztSVwsnyOBC9wET6S7vMfZ2KBVCv6KPfUN.vO6AC');

-- --------------------------------------------------------

--
-- Table structure for table `admin_tokens`
--

CREATE TABLE `admin_tokens` (
  `token_id` int(20) NOT NULL,
  `admin_id` int(20) DEFAULT NULL,
  `token` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_tokens`
--

INSERT INTO `admin_tokens` (`token_id`, `admin_id`, `token`, `created_at`) VALUES
(1, 1, '126de8cb0dcfcff8a8085379ac37a96a09baef7a9d51fe681e6e7892337d52e6', '2025-02-23 16:11:02'),
(2, 2, 'dcb42052f3855f003f52a5b081a4147faa88eab824eb124943f45e0b4ef5b9cd', '2025-02-24 18:51:26');

-- --------------------------------------------------------

--
-- Table structure for table `document`
--

CREATE TABLE `document` (
  `student_id` varchar(255) NOT NULL,
  `document_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `requirement_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `document_type` enum('file','link') NOT NULL DEFAULT 'file',
  `link_url` varchar(2048) DEFAULT NULL,
  `upload_at` varchar(255) NOT NULL,
  `status` enum('draft','pending','approved','rejected','missing') DEFAULT 'draft',
  `is_submitted` tinyint(1) DEFAULT 0,
  `submitted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `document`
--

INSERT INTO `document` (`student_id`, `document_id`, `event_id`, `requirement_id`, `file_path`, `document_type`, `link_url`, `upload_at`, `status`, `is_submitted`, `submitted_at`) VALUES
('1', 21, 1, 2, 'uploads/126791505_p1.jpg', 'file', NULL, '2025-03-03 02:15:32', 'draft', 0, NULL),
('1', 44, 3, 4, 'uploads/67c72205e43f2_1741103621.png', 'file', NULL, '2025-03-04 23:53:41', 'draft', 0, NULL),
('1', 46, 3, 4, '', 'link', 'https://docs.google.com/document/d/1PlcQxBTmRV9x_ZO5IkeohykvQBYnkj-lcY9G88YalCw/edit?usp=drive_link', '2025-03-05 00:15:42', 'draft', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `event`
--

CREATE TABLE `event` (
  `event_id` int(11) NOT NULL,
  `event_name` varchar(255) NOT NULL,
  `date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event`
--

INSERT INTO `event` (`event_id`, `event_name`, `date`) VALUES
(1, 'FDC', '2025-02-22 00:00:00'),
(2, 'Sample Event', '2025-02-21 00:00:00'),
(3, 'Another Event', '2025-02-22 00:00:00'),
(4, 'The Event', '2025-02-28 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `requirement`
--

CREATE TABLE `requirement` (
  `requirement_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `requirement_name` varchar(255) NOT NULL,
  `due_date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `requirement`
--

INSERT INTO `requirement` (`requirement_id`, `event_id`, `requirement_name`, `due_date`) VALUES
(1, 1, 'picture', '2025-02-23 00:00:00'),
(2, 1, 'New Requirement', '2025-12-31 00:00:00'),
(4, 3, 'fgdsgfd', '2025-11-12 00:00:00'),
(5, 2, 'pwet ni justin', '2025-04-12 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `student`
--

CREATE TABLE `student` (
  `student_id` int(20) NOT NULL,
  `name` varchar(50) NOT NULL,
  `email` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student`
--

INSERT INTO `student` (`student_id`, `name`, `email`, `password`) VALUES
(1, 'Ace', 'acephilipdenulan12@gmail.com', '$2y$10$bpxiCciyUvA2tejHj2dvueFVP/gCuHUfLTVIk33pM4UoTBLSM2QXS'),
(2, 'Philip', 'acephilipdenulan11@gmail.com', '$2y$10$RH5rrDGecooB8KxiMdn/ourIk/J5EugJ64HLyX5KYJFGcsebAZT3C');

-- --------------------------------------------------------

--
-- Table structure for table `student_tokens`
--

CREATE TABLE `student_tokens` (
  `token_id` int(20) NOT NULL,
  `student_id` int(20) DEFAULT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_tokens`
--

INSERT INTO `student_tokens` (`token_id`, `student_id`, `token`, `created_at`) VALUES
(2, 2, '085426adef3790189a6099d5f0332d040d732a8968904e677e3620a316b239ec', '2025-02-25 09:36:39'),
(3, 1, '0e6b11ff79e0e966e858f1e5665261023482c279c9b5d5077f39f8ae8ac1b7c4', '2025-02-25 09:46:27'),
(8, 1, '12e57f35d02c1e0ddf7bd5039a60a8cecbc4a4b66a017ead615249dac66c822f', '2025-02-26 17:32:53');

-- --------------------------------------------------------

--
-- Table structure for table `submission`
--

CREATE TABLE `submission` (
  `student_id` varchar(255) NOT NULL,
  `submission_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `requirement_id` int(11) NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `document_type` enum('file','link') NOT NULL DEFAULT 'file',
  `link_url` varchar(2048) DEFAULT NULL,
  `submission_date` datetime NOT NULL,
  `status` varchar(255) NOT NULL,
  `approved_by` varchar(255) NOT NULL,
  `document_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `submission`
--

INSERT INTO `submission` (`student_id`, `submission_id`, `event_id`, `requirement_id`, `file_path`, `document_type`, `link_url`, `submission_date`, `status`, `approved_by`, `document_id`) VALUES
('03-2122-032648', 2, 1, 1, 'uploads/6789.jpg', 'file', NULL, '2025-02-24 00:30:53', 'approved', 'Justin', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`admin_id`);

--
-- Indexes for table `admin_tokens`
--
ALTER TABLE `admin_tokens`
  ADD PRIMARY KEY (`token_id`);

--
-- Indexes for table `document`
--
ALTER TABLE `document`
  ADD PRIMARY KEY (`document_id`);

--
-- Indexes for table `event`
--
ALTER TABLE `event`
  ADD PRIMARY KEY (`event_id`);

--
-- Indexes for table `requirement`
--
ALTER TABLE `requirement`
  ADD PRIMARY KEY (`requirement_id`);

--
-- Indexes for table `student`
--
ALTER TABLE `student`
  ADD PRIMARY KEY (`student_id`);

--
-- Indexes for table `student_tokens`
--
ALTER TABLE `student_tokens`
  ADD PRIMARY KEY (`token_id`);

--
-- Indexes for table `submission`
--
ALTER TABLE `submission`
  ADD PRIMARY KEY (`submission_id`),
  ADD KEY `document_id` (`document_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `admin_id` int(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `admin_tokens`
--
ALTER TABLE `admin_tokens`
  MODIFY `token_id` int(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `document`
--
ALTER TABLE `document`
  MODIFY `document_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `event`
--
ALTER TABLE `event`
  MODIFY `event_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `requirement`
--
ALTER TABLE `requirement`
  MODIFY `requirement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `student`
--
ALTER TABLE `student`
  MODIFY `student_id` int(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `student_tokens`
--
ALTER TABLE `student_tokens`
  MODIFY `token_id` int(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `submission`
--
ALTER TABLE `submission`
  MODIFY `submission_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `submission`
--
ALTER TABLE `submission`
  ADD CONSTRAINT `submission_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `document` (`document_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
