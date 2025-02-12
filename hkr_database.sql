-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 12, 2025 at 08:57 AM
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
  `admin_id` varchar(50) NOT NULL,
  `email` varchar(50) NOT NULL,
  `password` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`admin_id`, `email`, `password`) VALUES
('Admin', 'escanojustin678@gmail.com', '$2y$12$VLQMYtLaXvV.rDYfIoaNCuUSFza6ThiWNGNjZN9Mk2D'),
('Admin1', 'Admin1@admin1.com', '$2y$12$ZJ16BGL0NGDq2ksEbd3eS.GuoqBA8158fSn4SMXYrrY'),
('Admin2', 'Admin2@admin2.com', '$2y$12$CejB4ReFw0MCtY3NzhUIK.TRDSjWCUVNzUOUUNW232WppM9Eot93O');

-- --------------------------------------------------------

--
-- Table structure for table `admin_tokens`
--

CREATE TABLE `admin_tokens` (
  `admin_id` varchar(255) NOT NULL,
  `token` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `document`
--

CREATE TABLE `document` (
  `student_id` varchar(50) NOT NULL,
  `file_path` varchar(50) NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `document`
--

INSERT INTO `document` (`student_id`, `file_path`, `created_at`) VALUES
('03-2122-030303', 'uploads/asus.jpg', '2025-01-27 05:49:05'),
('03-2324-031284', 'uploads/download.png', '2025-02-01 05:30:15');

-- --------------------------------------------------------

--
-- Table structure for table `requirements`
--

CREATE TABLE `requirements` (
  `id` int(11) NOT NULL,
  `student_id` varchar(255) NOT NULL,
  `event_name` varchar(255) NOT NULL,
  `due_date` date NOT NULL,
  `shared` tinyint(1) NOT NULL DEFAULT 0,
  `submission` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `requirements`
--

INSERT INTO `requirements` (`id`, `student_id`, `event_name`, `due_date`, `shared`, `submission`) VALUES
(15, '03-2122-030303', 'FDC', '2025-02-25', 1, '0000-00-00 00:00:00'),
(16, '03-2122-030303', 'FDC', '2025-02-25', 1, '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `student`
--

CREATE TABLE `student` (
  `student_id` int(20) NOT NULL,
  `name` varchar(50) NOT NULL,
  `email` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `token` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student`
--

INSERT INTO `student` (`student_id`, `name`, `email`, `password`, `token`) VALUES
(1, 'please', 'please@gmail.com', '$2y$10$YDY.8tuauagr7c9.jrdhLueeFrdW3iNmL1gDL08wtHVSNdqKtTtBu', '8249f60f025e3793182a0ea2c91b8b684d34e014c080d2abc2d2382ca21600e5'),
(2, 'ace', 'ace@gmail.com', '$2y$10$nuo1JyldbwZ/1eH4JIYZgeF.AwphGXimsAzVYc/Y7L/TQ/JLjA0LG', 'a683fa6418ddbf58acbc7aed8744aadad186dd8d417ad455dd9015467c7580e9');

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
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`admin_id`);

--
-- Indexes for table `document`
--
ALTER TABLE `document`
  ADD PRIMARY KEY (`student_id`);

--
-- Indexes for table `requirements`
--
ALTER TABLE `requirements`
  ADD PRIMARY KEY (`id`);

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
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `requirements`
--
ALTER TABLE `requirements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `student`
--
ALTER TABLE `student`
  MODIFY `student_id` int(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `student_tokens`
--
ALTER TABLE `student_tokens`
  MODIFY `token_id` int(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
