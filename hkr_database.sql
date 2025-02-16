-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 14, 2025 at 07:39 PM
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
  `password` varchar(255) DEFAULT NULL,
  `token` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`admin_id`, `name`, `email`, `password`, `token`) VALUES
(3, 'admin', 'admin@gmail.com', '$2y$10$.7BbQ8AJAEd1bPUofpkm/ubrNSDYQBl61JZVDR3SdVu00/KNeYdKy', '9b543989658bf73af9b090e11e6a250b6e6858f81c3d511df45ebb482b1dc848'),
(4, 'admin2', 'admin2@gmail.com', '$2y$10$KI8e5Gm5jesnAs64bB5jPOXSoSbjiN/HEHCmeVnlfXDVs9VIH.kLm', '49071fe79de5cdabb91559fc021aa09069da28eb22641ac44d483cb218f166a9'),
(5, 'justin', 'blueblade906@gmail.com', '$2y$10$0trEY4nrT6DXXbKPlY/rLOPiYAqjp/VT.3pbEteuUvZlcHQTezzU2', '8ec2f314faa991a46dced530046118851adbc5fb86212faa9315959e00dcb486'),
(7, 'token', 'token@gmail.com', '$2y$10$VArn0TWX07WxiSX8aCjhCukD9k59MCS9CROP5YvkNjil69RVTFYpW', '5e1d1815d808d9781b725b356a69e13f10d87838726ab45762e69c5e1faaa48b'),
(8, 'token2', 'token2@gmail.com', '$2y$10$A9O6KJLIEt4G7Jd9v.aqfeu3sMEhubk.h3HRT48errzIu3dO8Z9Lu', '5524a414589258b13a395124db027186ce3a479e87bd035957667826cd30df97'),
(9, 'token3', 'token3@gmail.com', '$2y$10$Wpm5gKq/9MB9X9/xfO/EtO2EaK3plSbQDNftQx.eRTxi6vZ3TNJ9m', 'f604eaa7c227dbe498431f35d1e4b3fefe7f5ed202387844f846a7f8bec8a4a2'),
(11, 'ace philip', 'acephilipdenulan12@gmail.com', '$2y$10$DQTpjvIFCJ2zHl9vVlVrMezrwgUldv7HOb6iLLv7W6Cpd9bfdweD2', 'b093a7338f348f547d0e9655a0d82acf2d2de36622ba34db4039f98e06c13c9a');

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
(37, 11, '64d0ff4fca5350f09a0db7a237d0da17888fe69222daf0fd65e8e218834bfc9d', '2025-02-14 18:34:54');

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
('03-2324-031284', 'uploads/download.png', '2025-02-01 05:30:15'),
('2', 'uploads/Screenshot 2025-02-12 014412.png', '2025-02-13 07:00:40');

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
(2, 'ace', 'ace@gmail.com', '$2y$10$nuo1JyldbwZ/1eH4JIYZgeF.AwphGXimsAzVYc/Y7L/TQ/JLjA0LG', 'a683fa6418ddbf58acbc7aed8744aadad186dd8d417ad455dd9015467c7580e9'),
(3, 'admin1', 'student1@gmail.com', '$2y$10$HjxuaktQJKohF/mQxrlcouyFn.ylkeT3PkJUT8XbNAMTkbP6FGoAa', 'fc3c2aefa45d05ddd391840c17ebc129d1a0750b65ad2bb161479ae72f49339a'),
(4, 'ace philip', 'acephilipdenulan12@gmail.com', '$2y$10$fQSppxjDYnrbshuecKiYvOCljJFUy4/N0D0AqcnIwGaMG/uGdUM4O', 'd27e83bb5463b48c53aac80ac64499589ab65525bb0c4058a7378473c327f0d8');

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
(19, 4, 'c7ad4b9e26e88a41630e51b9c58632f296146c56d232bc445a0c8b36148d6fa4', '2025-02-13 06:35:17'),
(20, 4, 'd5e3e8428932ef0afe6099580be1332447eeeea13bf1b559e92b01d794b3ac5c', '2025-02-13 06:35:30');

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
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `admin_id` int(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `admin_tokens`
--
ALTER TABLE `admin_tokens`
  MODIFY `token_id` int(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `requirements`
--
ALTER TABLE `requirements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `student`
--
ALTER TABLE `student`
  MODIFY `student_id` int(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `student_tokens`
--
ALTER TABLE `student_tokens`
  MODIFY `token_id` int(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
