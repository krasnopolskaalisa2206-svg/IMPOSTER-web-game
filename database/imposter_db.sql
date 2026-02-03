-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Feb 03, 2026 at 04:47 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `imposter_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `players`
--

CREATE TABLE `players` (
  `id` int(11) NOT NULL,
  `username` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `players`
--

INSERT INTO `players` (`id`, `username`, `password`) VALUES
(7, 'newStuff', '$2y$10$5WMDVXEeyMui1xkYvC/IzuTtjhJxUyroFxQ5tRbJ1LRavNDdFjnvW'),
(15, 'test_username', '$2y$10$Gr1JpFEEhypT7gVeGvI/xeK3ntCxgOTphSVm2c/Guhc8dVc9v5tIW'),
(16, 'test_username_2', '$2y$10$bw5g34HHXQB8vn9GJYeDAudkfSU.fw6ap7uhIkgYIp0kaymsO5DYK'),
(17, 'test_username_3', '$2y$10$AmXR7guyANTk7BDMD1YDNOK5dnipnQ8AWA/yHRuVa/n4jQLzuYAwe'),
(18, 'user1', '$2y$10$CUaI4.ZKFZYOaUhKyMUfTuPHelqLAxpFK1sBvyGtdUvt7GD4Ol9kW'),
(19, 'Shiven', '$2y$10$NhBjqQLWNJKoNAj6wU1bJeEYkU/C5sq72JMrmNHOm0BdroPFoW4NO');

-- --------------------------------------------------------

--
-- Table structure for table `player_session`
--

CREATE TABLE `player_session` (
  `id` int(11) NOT NULL,
  `player_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `player_session`
--

INSERT INTO `player_session` (`id`, `player_id`, `room_id`) VALUES
(1, 7, 7),
(2, 7, 8),
(3, 7, 8),
(4, 7, 9),
(5, 7, 9),
(6, 19, 10);

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`id`, `name`, `status`) VALUES
(1, 'testroom', 'lobby'),
(2, 'Player\'s Room', 'lobby'),
(3, 'Player\'s Room', 'lobby'),
(4, 'Player\'s Room', 'lobby'),
(5, 'Player\'s Room', 'lobby'),
(6, 'Player\'s Room', 'lobby'),
(7, 'Player\'s Room', 'lobby'),
(8, 'Player\'s Room', 'lobby'),
(9, 'Player\'s Room', 'lobby'),
(10, 'TestRoom01', 'lobby');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `players`
--
ALTER TABLE `players`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username_2` (`username`),
  ADD KEY `username` (`username`);

--
-- Indexes for table `player_session`
--
ALTER TABLE `player_session`
  ADD PRIMARY KEY (`id`),
  ADD KEY `player_id` (`player_id`),
  ADD KEY `room_id` (`room_id`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `players`
--
ALTER TABLE `players`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `player_session`
--
ALTER TABLE `player_session`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `player_session`
--
ALTER TABLE `player_session`
  ADD CONSTRAINT `player_session_ibfk_1` FOREIGN KEY (`player_id`) REFERENCES `players` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `player_session_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
