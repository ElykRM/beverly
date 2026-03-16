-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 16, 2026 at 02:53 AM
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
-- Database: `beverly`
--

-- --------------------------------------------------------

--
-- Table structure for table `households`
--

CREATE TABLE `households` (
  `id` int(11) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `home_status` enum('Owner','Renter','Member') NOT NULL DEFAULT 'Owner',
  `block` varchar(50) DEFAULT NULL,
  `lot` varchar(50) DEFAULT NULL,
  `street` varchar(150) DEFAULT NULL,
  `subdivision` varchar(200) DEFAULT 'Beverly Homes Subd. Brgy Hugo Perez Trece Martires Cavite',
  `birthday` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `contact_no` varchar(20) DEFAULT NULL,
  `occupation` varchar(100) DEFAULT NULL,
  `num_pets` tinyint(3) UNSIGNED DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `households`
--

INSERT INTO `households` (`id`, `last_name`, `first_name`, `middle_name`, `home_status`, `block`, `lot`, `street`, `subdivision`, `birthday`, `gender`, `contact_no`, `occupation`, `num_pets`, `created_at`, `updated_at`) VALUES
(2, 'Koyasu', 'Takehito', '', 'Owner', '2', '3', 'Ohio', 'Beverly Homes Subd. Brgy Hugo Perez Trece Martires Cavite', '1967-05-05', 'Male', '09945613289', 'Seiyuu', 1, '2026-03-12 07:55:31', '2026-03-12 07:55:31'),
(3, 'Ludenberg', 'Celestia', '', 'Owner', '1', '2', 'Chromu', 'Beverly Homes Subd. Brgy Hugo Perez Trece Martires Cavite', '2004-03-12', 'Female', '12323', '', 0, '2026-03-12 08:06:13', '2026-03-12 08:06:13'),
(4, 'Ina\'nis', 'Ninomae', '', 'Owner', '3', '6', 'Sameko', 'Beverly Homes Subd. Brgy Hugo Perez Trece Martires Cavite', '2004-12-03', 'Female', '09154170972', 'Artist', 0, '2026-03-12 08:33:11', '2026-03-12 08:33:11'),
(5, 'Majima', 'Goro', '', 'Owner', '4', '8', 'Shimano', 'Beverly Homes Subd. Brgy Hugo Perez Trece Martires Cavite', '1964-05-14', 'Male', '', '', 0, '2026-03-13 06:23:50', '2026-03-13 06:26:24'),
(6, 'Kiryu', 'Kazuma', '', 'Owner', '1', '7', 'Dojima', 'Beverly Homes Subd. Brgy Hugo Perez Trece Martires Cavite', '1968-06-17', 'Male', '', '', 0, '2026-03-13 06:24:51', '2026-03-13 06:24:51'),
(7, 'Nishikiyama', 'Akira', 'Yuko', 'Owner', '1', '9', 'Sunflower', 'Beverly Homes Subd. Brgy Hugo Perez Trece Martires Cavite', '1968-08-10', 'Male', '', '', 0, '2026-03-13 06:25:58', '2026-03-13 06:25:58'),
(8, 'Kasuga', 'Ichiban', '', 'Owner', '1', '8', 'Kamurocho', 'Beverly Homes Subd. Brgy Hugo Perez Trece Martires Cavite', '1967-01-01', '', '', '', 0, '2026-03-13 06:27:16', '2026-03-13 06:29:32'),
(9, 'Kris', 'Symboli', 'Shachi', 'Owner', '3', '5', 'Tracen', 'Beverly Homes Subd. Brgy Hugo Perez Trece Martires Cavite', '1998-11-21', 'Female', '', '', 0, '2026-03-13 06:33:16', '2026-03-13 06:33:16'),
(10, 'Algeria', 'Gran', '', 'Owner', '2', '4', 'Tracen', 'Beverly Homes Subd. Brgy Hugo Perez Trece Martires Cavite', '1999-01-24', 'Female', '', '', 0, '2026-03-13 06:38:47', '2026-03-13 06:38:47'),
(11, 'Light', 'Calstone', 'O', 'Owner', '3', '9', 'Neko', 'Beverly Homes Subd. Brgy Hugo Perez Trece Martires Cavite', '2004-05-03', 'Female', '', '', 0, '2026-03-13 06:46:35', '2026-03-13 06:46:35'),
(12, 'Kraftfahrzeug', 'Sonder', 'Kampfwagen ', 'Renter', '6', '6', 'Normandy', 'Beverly Homes Subd. Brgy Hugo Perez Trece Martires Cavite', '1945-06-06', 'Male', '661945', 'Engineer', 0, '2026-03-13 06:49:52', '2026-03-13 06:49:52');

-- --------------------------------------------------------

--
-- Table structure for table `household_members`
--

CREATE TABLE `household_members` (
  `id` int(11) NOT NULL,
  `household_id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `relation` varchar(50) NOT NULL COMMENT 'Spouse, Child, Parent, Sibling, Tenant, Other',
  `birthday` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `contact_no` varchar(20) DEFAULT NULL,
  `occupation` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `household_id` int(11) NOT NULL,
  `or_no` varchar(50) NOT NULL,
  `period_year` int(11) NOT NULL,
  `period_month` tinyint(3) UNSIGNED NOT NULL COMMENT '1-12',
  `period_to_year` int(11) DEFAULT NULL,
  `period_to_month` tinyint(3) UNSIGNED DEFAULT NULL COMMENT '1-12',
  `amount` decimal(10,2) NOT NULL,
  `remarks` text DEFAULT NULL,
  `is_promo` tinyint(1) DEFAULT 0 COMMENT '1 = yearly promo (e.g. 1000/year)',
  `paid_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT 'For soft delete (optional)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `household_id`, `or_no`, `period_year`, `period_month`, `period_to_year`, `period_to_month`, `amount`, `remarks`, `is_promo`, `paid_at`, `deleted_at`) VALUES
(1, 2, '3213213', 2026, 1, 2026, 12, 1000.00, NULL, 1, '2026-03-12 07:55:50', NULL),
(2, 3, '123123', 2026, 1, 2026, 2, 200.00, NULL, 0, '2026-03-12 08:15:24', NULL),
(3, 5, '23123', 2026, 1, 2026, 12, 1200.00, NULL, 0, '2026-03-13 06:44:14', NULL),
(4, 6, '16354', 2026, 1, 2027, 12, 2400.00, NULL, 0, '2026-03-13 06:45:03', NULL),
(6, 3, '87573', 2026, 3, NULL, NULL, 100.00, NULL, 0, '2026-03-13 07:00:54', NULL),
(7, 8, '1321313', 2026, 1, 2026, 2, 200.00, NULL, 0, '2026-03-13 08:03:40', NULL),
(8, 8, '1321313', 2026, 1, 2026, 2, 200.00, NULL, 0, '2026-03-13 08:03:41', NULL);

-- --------------------------------------------------------

--
-- Stand-in structure for view `payment_months`
-- (See below for the actual view)
--
CREATE TABLE `payment_months` (
`payment_id` int(11)
,`household_id` int(11)
,`or_no` varchar(50)
,`amount` decimal(10,2)
,`is_promo` tinyint(1)
,`paid_at` timestamp
,`remarks` text
,`period_year` int(11)
,`period_month` int(2)
);

-- --------------------------------------------------------

--
-- Table structure for table `vehicles`
--

CREATE TABLE `vehicles` (
  `id` int(11) NOT NULL,
  `household_id` int(11) NOT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `type_model` varchar(100) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `plate_no` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `vehicles`
--

INSERT INTO `vehicles` (`id`, `household_id`, `brand`, `type_model`, `color`, `plate_no`, `created_at`) VALUES
(1, 4, 'Mazda', 'Miata', 'Goldenrod', '69420', '2026-03-12 08:33:11');

-- --------------------------------------------------------

--
-- Structure for view `payment_months`
--
DROP TABLE IF EXISTS `payment_months`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `payment_months`  AS SELECT `p`.`id` AS `payment_id`, `p`.`household_id` AS `household_id`, `p`.`or_no` AS `or_no`, `p`.`amount` AS `amount`, `p`.`is_promo` AS `is_promo`, `p`.`paid_at` AS `paid_at`, `p`.`remarks` AS `remarks`, `y`.`year_num` AS `period_year`, `m`.`month_num` AS `period_month` FROM ((`payments` `p` join (select 1 AS `month_num` union select 2 AS `2` union select 3 AS `3` union select 4 AS `4` union select 5 AS `5` union select 6 AS `6` union select 7 AS `7` union select 8 AS `8` union select 9 AS `9` union select 10 AS `10` union select 11 AS `11` union select 12 AS `12`) `m`) join (select `payments`.`period_year` AS `year_num` from `payments` where `payments`.`period_to_year` is null union select `years`.`y` AS `y` from (select `payments`.`period_year` AS `y` from `payments` where `payments`.`period_to_year` is not null union select `payments`.`period_to_year` AS `period_to_year` from `payments` where `payments`.`period_to_year` is not null) `years`) `y`) WHERE `p`.`deleted_at` is null AND (`p`.`period_to_year` is null AND `m`.`month_num` = `p`.`period_month` OR `p`.`period_to_year` is not null AND `y`.`year_num` between `p`.`period_year` and `p`.`period_to_year` AND (`y`.`year_num` > `p`.`period_year` OR `m`.`month_num` >= `p`.`period_month`) AND (`y`.`year_num` < `p`.`period_to_year` OR `m`.`month_num` <= `p`.`period_to_month`)) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `households`
--
ALTER TABLE `households`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_full_name` (`last_name`,`first_name`),
  ADD KEY `idx_location` (`block`,`lot`),
  ADD KEY `idx_status` (`home_status`);

--
-- Indexes for table `household_members`
--
ALTER TABLE `household_members`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_household` (`household_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_household` (`household_id`),
  ADD KEY `idx_period` (`period_year`,`period_month`),
  ADD KEY `idx_range` (`period_year`,`period_to_year`),
  ADD KEY `idx_promo` (`is_promo`),
  ADD KEY `idx_deleted` (`deleted_at`);

--
-- Indexes for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_household` (`household_id`),
  ADD KEY `idx_plate` (`plate_no`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `households`
--
ALTER TABLE `households`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `household_members`
--
ALTER TABLE `household_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `vehicles`
--
ALTER TABLE `vehicles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `household_members`
--
ALTER TABLE `household_members`
  ADD CONSTRAINT `household_members_ibfk_1` FOREIGN KEY (`household_id`) REFERENCES `households` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`household_id`) REFERENCES `households` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD CONSTRAINT `vehicles_ibfk_1` FOREIGN KEY (`household_id`) REFERENCES `households` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
