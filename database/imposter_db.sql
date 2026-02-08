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

--
-- Create a column for the 6 digit codes that users can enter

ALTER TABLE `rooms` 
ADD COLUMN `room_code` VARCHAR(6) UNIQUE NULL AFTER `name`,
ADD KEY `room_code` (`room_code`);

-- Update existing rooms with random codes only if they don't have a room_code yet
-- gets a random number 0-999999, LPAD pads numbers with zeroes to make them 6 digits

UPDATE `rooms` SET `room_code` = LPAD(FLOOR(RAND() * 1000000), 6, '0') WHERE `room_code` IS NULL;


/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

CREATE TABLE `categories` (
  `category_id` int(2) NOT NULL,
  `category_title` varchar(46) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

INSERT INTO `categories` (`category_id`, `category_title`) VALUES
(1, 'Animals'),
(2, 'Professions'),
(3, 'Cities'),
(4, 'Food'),
(5, 'Sports'),
(6, 'Celebrities'),
(7, 'Movies and TV Shows'),
(8, 'Hobbies'),
(9, 'Programming Languages'),
(10, 'Diseases');

-- Структура таблицы `words`
--

CREATE TABLE `words` (
  `word_id` int(6) NOT NULL,
  `word` varchar(25) DEFAULT NULL,
  `category_id` varchar(6) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Дамп данных таблицы `words`
--

INSERT INTO `words` (`word_id`, `word`, `category_id`) VALUES
(1, 'cat', 1),
(2, 'dog', 1),
(3, 'mouse', 1),
(4, 'horse', 1),
(5, 'chicken', 1),
(6, 'cow', 1),
(7, 'sheep', 1),
(8, 'goat', 1),
(9, 'eagle', 1),
(10, 'frog', 1),

(11, 'software engineer', 2),
(12, 'fire fighter', 2),
(13, 'doctor', 2),
(14, 'teacher', 2),
(15, 'archeologist', 2),
(16, 'palaeontologist', 2),
(17, 'forensic scientist', 2),
(18, 'detective', 2),
(19, 'police officer', 2),
(20, 'farmer', 2),

(21, 'London', 3),
(22, 'Paris', 3),
(23, 'Miami', 3),
(24, 'Sydney', 3),
(25, 'Cairo', 3),
(26, 'Amsterdam', 3),
(27, 'Kyiv', 3),
(28, 'Tokyo', 3),
(29, 'Toronto', 3),
(30, 'New York', 3),

(31, 'Pizza', 4),
(32, 'Carbonara', 4),
(33, 'Ramen', 4),
(34, 'Pad Thai', 4),
(35, 'Haggis', 4),
(36, 'Taco', 4),
(37, 'Cheeseburger', 4),
(38, 'Poutine', 4),
(39, 'Foie Gras', 4),
(40, 'Sushi', 4),

(41, 'Football', 5),
(42, 'Basketball', 5),
(43, 'Swimming', 5),
(44, 'Baseball', 5),
(45, 'Wrestling', 5),
(46, 'Golf', 5),
(47, 'Hockey', 5),
(48, 'Rugby', 5),
(49, 'Cricket', 5),
(50, 'Fencing', 5),

(51, 'Ariana Grande', 6),
(52, 'Michael Jackson', 6),
(53, 'Snoop Dog', 6),
(54, 'Obama', 6),
(55, 'Johny Depp', 6),
(56, 'Tom Cruise', 6),
(57, 'Jim Carrey', 6),
(58, 'Emma Watson', 6),
(59, 'Brad Pitt', 6),
(60, 'Morgam Freeman', 6),

(61, 'Home Alone', 7),
(62, 'Stranger Things', 7),
(63, 'Game of Thrones', 7),
(64, 'Breaking Bad', 7),
(65, 'Shrek', 7),
(66, 'The Sopranos', 7),
(67, 'Pulp Fiction', 7),
(68, 'Dexter', 7),
(69, 'IT', 7),
(70, 'Wednesday', 7),

(71, 'Drawing', 8),
(72, 'Reading', 8),
(73, 'Baking', 8),
(74, 'Gardening', 8),
(75, 'Gaming', 8),
(76, 'Knitting', 8),
(77, 'Pottery', 8),
(78, 'Music', 8),
(79, 'Birdwatching', 8),
(80, 'Fishing', 8),

(91, 'C', 9),
(92, 'C++', 9),
(93, 'C#', 9),
(94, 'Python', 9),
(95, 'Java', 9),
(96, 'Javascript', 9),
(97, 'Rust', 9),
(98, 'HTML', 9),
(99, 'CSS', 9),
(100, 'Spanish', 9),

(101, 'Covid-19', 10),
(102, 'AIDS', 10),
(103, 'Cold', 10),
(104, 'Chicken Pox', 10),
(105, 'Rabies', 10),
(106, 'Jaundice', 10),
(107, 'Cancer', 10),
(108, 'Diarrhea', 10),
(109, 'Dementia', 10),
(110, 'Asthma', 10);