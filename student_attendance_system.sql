-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 13, 2025 at 10:50 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `student_attendance_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `attendance_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `attendance_date` date NOT NULL,
  `status` enum('present','absent','late','excused') NOT NULL,
  `marked_by` int(11) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `marked_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`attendance_id`, `student_id`, `course_id`, `attendance_date`, `status`, `marked_by`, `remarks`, `marked_at`) VALUES
(1, 1, 1, '2025-12-13', 'late', 2, '', '2025-12-13 21:44:53'),
(2, 1, 3, '2025-12-13', 'absent', 2, '1', '2025-12-13 21:20:44'),
(3, 1, 2, '2025-12-13', 'present', 2, '', '2025-12-13 21:12:13'),
(4, 3, 3, '2025-12-13', 'absent', 2, '3', '2025-12-13 21:20:44'),
(5, 2, 3, '2025-12-13', 'present', 2, '4', '2025-12-13 21:20:44'),
(16, 2, 1, '2025-12-12', 'absent', 2, NULL, '2025-12-13 21:42:57'),
(17, 3, 1, '2025-12-07', 'excused', 2, NULL, '2025-12-13 21:42:57'),
(19, 2, 2, '2025-12-07', 'present', 2, NULL, '2025-12-13 21:42:58'),
(20, 3, 2, '2025-12-11', 'present', 2, NULL, '2025-12-13 21:42:58'),
(22, 3, 1, '2025-12-13', 'present', 2, '', '2025-12-13 21:44:53'),
(23, 2, 1, '2025-12-13', 'present', 2, '', '2025-12-13 21:44:53');

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `course_id` int(11) NOT NULL,
  `course_code` varchar(20) NOT NULL,
  `course_name` varchar(200) NOT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `schedule_day` varchar(20) DEFAULT NULL,
  `schedule_time` time DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`course_id`, `course_code`, `course_name`, `teacher_id`, `schedule_day`, `schedule_time`, `created_at`) VALUES
(1, 'CS101', 'Introduction to Programming', 2, 'Monday', '09:00:00', '2025-12-13 19:48:05'),
(2, 'CS102', 'Database Systems', 2, 'Wednesday', '11:00:00', '2025-12-13 19:48:05'),
(3, 'CS105', 'Information Technology', 2, 'Monday', '21:09:00', '2025-12-13 20:54:14');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `student_code` varchar(20) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `year_of_study` int(11) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_id`, `user_id`, `student_code`, `department`, `year_of_study`, `phone`) VALUES
(1, 3, 'STU001', 'Computer Science', 2, '0781234567'),
(2, 8, 'STU0008', NULL, NULL, NULL),
(3, 9, 'STU0009', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','teacher','student') NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `full_name`, `created_at`) VALUES
(1, 'admin', 'admin@school.rw', '$2y$10$0KLxTsDyVGksdlJ/cgIXcOWsMd2bKU7F96jm6T6pqC1LUNYwqiwHm', 'admin', 'System Administrator', '2025-12-13 19:48:05'),
(2, 'teacher1', 'teacher@school.rw', '$2y$10$0KLxTsDyVGksdlJ/cgIXcOWsMd2bKU7F96jm6T6pqC1LUNYwqiwHm', 'teacher', 'John Teacher', '2025-12-13 19:48:05'),
(3, 'student1', 'student@school.rw', '$2y$10$0KLxTsDyVGksdlJ/cgIXcOWsMd2bKU7F96jm6T6pqC1LUNYwqiwHm', 'student', 'Regis Ndayishimiye', '2025-12-13 19:48:05'),
(4, 'ubalde', 'ubalde@gmail.copm', '$2y$10$0KLxTsDyVGksdlJ/cgIXcOWsMd2bKU7F96jm6T6pqC1LUNYwqiwHm', 'admin', 'Ubalde', '2025-12-13 19:57:53'),
(6, 'ubaldeofficial', 'ubaldeofficial@gmail.com', '$2y$10$0KLxTsDyVGksdlJ/cgIXcOWsMd2bKU7F96jm6T6pqC1LUNYwqiwHm', 'admin', 'Ubalde Official', '2025-12-13 20:28:54'),
(7, 'uba', 'uba@gmail.com', '$2y$10$0KLxTsDyVGksdlJ/cgIXcOWsMd2bKU7F96jm6T6pqC1LUNYwqiwHm', 'student', 'Ubalde Official', '2025-12-13 20:52:13'),
(8, 'regis', 'regis@gmail.com', '$2y$10$0KLxTsDyVGksdlJ/cgIXcOWsMd2bKU7F96jm6T6pqC1LUNYwqiwHm', 'student', 'regis igiraneza', '2025-12-13 21:17:41'),
(9, 'elise', 'elise@gmail.com', '$2y$10$0KLxTsDyVGksdlJ/cgIXcOWsMd2bKU7F96jm6T6pqC1LUNYwqiwHm', 'student', 'elise niyigena', '2025-12-13 21:18:09');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`attendance_id`),
  ADD UNIQUE KEY `unique_attendance` (`student_id`,`course_id`,`attendance_date`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `marked_by` (`marked_by`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`course_id`),
  ADD UNIQUE KEY `course_code` (`course_code`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `student_code` (`student_code`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `course_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=80;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`),
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`),
  ADD CONSTRAINT `attendance_ibfk_3` FOREIGN KEY (`marked_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `courses_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
