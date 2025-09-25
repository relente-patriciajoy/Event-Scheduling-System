-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 25, 2025 at 06:51 AM
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
-- Database: `event_registration`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `attendance_id` int(11) NOT NULL,
  `registration_id` int(11) DEFAULT NULL,
  `check_in_time` datetime DEFAULT NULL,
  `check_out_time` datetime DEFAULT NULL,
  `status` varchar(10) DEFAULT 'absent'
) ;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`attendance_id`, `registration_id`, `check_in_time`, `check_out_time`, `status`) VALUES
(1, 1, '2025-06-12 10:15:37', '2025-06-12 10:15:39', 'present'),
(2, 2, '2025-06-20 12:24:50', '2025-06-20 12:24:51', 'present'),
(3, 3, '2025-07-01 15:06:43', '2025-07-01 15:06:45', 'present');

-- --------------------------------------------------------

--
-- Table structure for table `event`
--

CREATE TABLE `event` (
  `event_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `venue_id` int(11) DEFAULT NULL,
  `organizer_id` int(11) DEFAULT NULL,
  `capacity` int(11) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `category_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event`
--

INSERT INTO `event` (`event_id`, `title`, `description`, `start_time`, `end_time`, `venue_id`, `organizer_id`, `capacity`, `price`, `created_at`, `category_id`) VALUES
(2, 'Test edit event', 'fadfad', '2025-06-20 22:25:00', '2025-06-24 22:25:00', 4, 1, 32, 345.00, '2025-06-11 22:26:11', NULL),
(4, 'Basketball', 'sa gedli', '2025-06-25 04:30:00', '2025-06-30 23:33:00', 6, 2, 25, 350.00, '2025-06-25 11:31:22', NULL),
(7, 'Basketball', 'Basketball for a cause', '2025-07-02 13:00:00', '2025-07-02 14:00:00', 11, 1, 30, 0.00, '2025-07-02 13:13:55', 1),
(10, 'Volleyball', 'test', '2025-07-02 13:20:00', '2025-07-02 18:20:00', 14, 1, 20, 20.00, '2025-07-02 13:20:51', 1),
(11, 'Tennis', 'Gameplay', '2025-07-02 16:42:00', '2025-07-02 16:42:00', 15, 1, 10, 0.00, '2025-07-02 16:42:36', 1);

-- --------------------------------------------------------

--
-- Table structure for table `event_category`
--

CREATE TABLE `event_category` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event_category`
--

INSERT INTO `event_category` (`category_id`, `category_name`, `description`) VALUES
(1, 'Sports', 'Sports-related events'),
(2, 'Non-Sports', 'Non-sports related events');

-- --------------------------------------------------------

--
-- Table structure for table `organizer`
--

CREATE TABLE `organizer` (
  `organizer_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `contact_email` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `organizer`
--

INSERT INTO `organizer` (`organizer_id`, `name`, `contact_email`, `phone`) VALUES
(1, 'Jane Cruz Dee', 'janedee123@gmail.com', '0123456988857'),
(2, 'John Cruz Doe', 'john123@example.com', '123456988856');

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `payment_id` int(11) NOT NULL,
  `registration_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` datetime DEFAULT current_timestamp(),
  `payment_method` varchar(50) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'completed'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment`
--

INSERT INTO `payment` (`payment_id`, `registration_id`, `amount`, `payment_date`, `payment_method`, `status`) VALUES
(1, 2, 345.00, '2025-06-20 12:23:35', 'Gcash', 'completed'),
(2, 3, 350.00, '2025-06-25 11:32:04', 'Credit Card', 'completed'),
(3, 4, 0.00, '2025-07-02 13:36:58', 'Gcash', 'completed');

-- --------------------------------------------------------

--
-- Table structure for table `registration`
--

CREATE TABLE `registration` (
  `registration_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `event_id` int(11) DEFAULT NULL,
  `registration_date` datetime DEFAULT current_timestamp(),
  `status` varchar(50) DEFAULT 'confirmed',
  `table_number` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `registration`
--

INSERT INTO `registration` (`registration_id`, `user_id`, `event_id`, `registration_date`, `status`, `table_number`) VALUES
(1, 2, 2, '2025-06-11 22:26:23', 'confirmed', 0),
(2, 1, 2, '2025-06-20 12:23:30', 'confirmed', 0),
(3, 2, 4, '2025-06-25 11:31:51', 'confirmed', 0),
(4, 2, 7, '2025-07-02 13:36:55', 'confirmed', 13);

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `user_id` int(11) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `middle_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `role` varchar(20) DEFAULT 'attendee'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`user_id`, `first_name`, `middle_name`, `last_name`, `email`, `phone`, `password`, `created_at`, `role`) VALUES
(1, 'Jennie', 'Cruz', 'Doe', 'jennie123@example.com', '01234569056', '$2y$10$PoT.4U9KxSENTnfXFu6SxOsF2kElQQIbFkTc.BqvoIdcTlgVuvfUe', '2025-06-11 20:31:30', 'admin'),
(2, 'Jane', 'Cruz', 'Doe', 'janedee123@gmail.com', '0123456988856', '$2y$10$g/8Ho1n068.ulYCQwaDke.GNX6ejkrXkOeDtWfIPyk5ryg0ZXiSbW', '2025-06-11 22:04:48', 'event_head'),
(3, 'John', 'Cruz', 'Doe', 'john123@example.com', '123456988856', '$2y$10$BiWOPrk/AGYim4Sp6EoipeapdqfvJEn3VKNJ1gMxYvucJhtDtu82.', '2025-06-25 11:28:22', 'event_head');

-- --------------------------------------------------------

--
-- Table structure for table `venue`
--

CREATE TABLE `venue` (
  `venue_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `capacity` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `venue`
--

INSERT INTO `venue` (`venue_id`, `name`, `address`, `city`, `capacity`) VALUES
(1, 'Volleyball Court', '#4 Sample Address', 'CCF', 21),
(2, 'Volleyball Court', '#4 Sample Address', 'Sample City', NULL),
(3, 'asd', 'dasdasda', 'asdas', NULL),
(4, 'gsadgs', 'fsadfsdf', 'dsfsad', NULL),
(5, 'sdfa', 'sdf', 'sddfdg', NULL),
(6, 'Basketball Court', '#123 Sample Address', 'Sample City', NULL),
(7, 'CCF', ' 3F CCF Bldg., Prime St. Madrigal Business Park Ayala Alabang', 'Alabang', NULL),
(8, 'CCF Court Alabang', 'Alabang', 'Alabang', NULL),
(9, 'CCF Court Alabang', 'Alabang', 'Alabang', NULL),
(10, 'CCF Court Alabang', 'Alabang', 'Alabang', NULL),
(11, 'CCF Court Alabang', 'Alabang', 'Alabang', NULL),
(12, 'test', 'test', 'test', NULL),
(13, 'test', 'test', 'test', NULL),
(14, 'test', 'test', 'test', NULL),
(15, 'Tennis Court', '#4 Sample Address', 'Alabang', NULL),
(16, 'fafadf', 'adfa', 'adfa', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`attendance_id`),
  ADD UNIQUE KEY `registration_id` (`registration_id`);

--
-- Indexes for table `event`
--
ALTER TABLE `event`
  ADD PRIMARY KEY (`event_id`),
  ADD KEY `venue_id` (`venue_id`),
  ADD KEY `organizer_id` (`organizer_id`),
  ADD KEY `fk_event_category` (`category_id`);

--
-- Indexes for table `event_category`
--
ALTER TABLE `event_category`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `name` (`category_name`);

--
-- Indexes for table `organizer`
--
ALTER TABLE `organizer`
  ADD PRIMARY KEY (`organizer_id`);

--
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `registration_id` (`registration_id`);

--
-- Indexes for table `registration`
--
ALTER TABLE `registration`
  ADD PRIMARY KEY (`registration_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `fk_registration_event` (`event_id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `venue`
--
ALTER TABLE `venue`
  ADD PRIMARY KEY (`venue_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `event`
--
ALTER TABLE `event`
  MODIFY `event_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `event_category`
--
ALTER TABLE `event_category`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `organizer`
--
ALTER TABLE `organizer`
  MODIFY `organizer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `payment`
--
ALTER TABLE `payment`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `registration`
--
ALTER TABLE `registration`
  MODIFY `registration_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `venue`
--
ALTER TABLE `venue`
  MODIFY `venue_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `fk_attendance_registration` FOREIGN KEY (`registration_id`) REFERENCES `registration` (`registration_id`) ON DELETE CASCADE;

--
-- Constraints for table `event`
--
ALTER TABLE `event`
  ADD CONSTRAINT `event_ibfk_1` FOREIGN KEY (`venue_id`) REFERENCES `venue` (`venue_id`),
  ADD CONSTRAINT `event_ibfk_2` FOREIGN KEY (`organizer_id`) REFERENCES `organizer` (`organizer_id`),
  ADD CONSTRAINT `fk_event_category` FOREIGN KEY (`category_id`) REFERENCES `event_category` (`category_id`) ON DELETE SET NULL;

--
-- Constraints for table `payment`
--
ALTER TABLE `payment`
  ADD CONSTRAINT `payment_ibfk_1` FOREIGN KEY (`registration_id`) REFERENCES `registration` (`registration_id`);

--
-- Constraints for table `registration`
--
ALTER TABLE `registration`
  ADD CONSTRAINT `fk_registration_event` FOREIGN KEY (`event_id`) REFERENCES `event` (`event_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `registration_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
