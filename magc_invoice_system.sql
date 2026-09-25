-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Sep 21, 2026 at 06:37 AM
-- Server version: 10.11.9-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `magc_invoice_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

CREATE TABLE `clients` (
  `id` int(11) NOT NULL,
  `client_name` varchar(150) DEFAULT NULL,
  `company_name` varchar(150) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `gstin` varchar(20) DEFAULT NULL,
  `ved_no` varchar(200) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `clients`
--

INSERT INTO `clients` (`id`, `client_name`, `company_name`, `email`, `phone`, `address`, `state`, `country`, `gstin`, `ved_no`, `created_at`) VALUES
(8, 'Philip Krause', 'RKP', 'info@rkpstorage.com', '', 'RKP International Hauptsitz,\r\n20 Austin Avenue, Tsim Sha Tsui KL, Hongkong, Kontaktieren Sie uns', '', 'Hong Kong S.A.R.', '', '', '2026-06-29 07:31:17'),
(10, 'Sweta', 'Demo Pvt LTD', 'demo@gmail.com', '753689052', 'om nagar', 'Madhya Pradesh', 'India', 'uytytg', '', '2026-08-31 06:52:36'),
(11, 'GST test', 'MAG CLOUD SOLUTIONS PRIVATE LIMITED', 'demo@gmail.com', '75360890520', '31/173-B, SHAMSHABAD ROAD, Agra, Uttar Pradesh, 282001', 'Uttar Pradesh', 'India', '09AAQCM2454D1ZC', '', '2026-08-31 09:48:34'),
(12, 'RENU JAIN', 'KSS Shoe Industry', 'kss_shoeind@yahoo.co.in', '9837346168', '3 FLOOR, SHREEDEVI SHOE AMRKET, HING KI MANDI, Uttar Pradesh, 282002', 'Uttar Pradesh', 'India', '09AGMPJ7749P1ZB', '', '2026-08-31 15:27:43');

-- --------------------------------------------------------

--
-- Table structure for table `company_settings`
--

CREATE TABLE `company_settings` (
  `id` int(11) NOT NULL,
  `company_name` varchar(200) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `gstin` varchar(50) DEFAULT NULL,
  `pan` varchar(50) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `website` varchar(200) NOT NULL,
  `bank_name` varchar(200) DEFAULT NULL,
  `account_number` varchar(100) DEFAULT NULL,
  `ifsc` varchar(50) DEFAULT NULL,
  `swift_code` varchar(50) DEFAULT NULL,
  `logo` varchar(200) DEFAULT NULL,
  `signature` varchar(200) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `company_settings`
--

INSERT INTO `company_settings` (`id`, `company_name`, `address`, `state`, `gstin`, `pan`, `phone`, `email`, `website`, `bank_name`, `account_number`, `ifsc`, `swift_code`, `logo`, `signature`, `created_at`) VALUES
(1, 'Mag Cloud Solutions Pvt Ltd', '31/173 B Hari Nagar Rajpur Chungi Shamshabad Road Agra U.P India', 'Uttar Pradesh', '09AAQCM2454D1ZC', 'AAQCM2454D', '+91 9536899899', 'mag44953@gmail.com', 'www.magcloudsolutions.com', 'HDFC Bank Ltd', '50200070454652', 'HDFC0003962', 'HDFCINBB', '1773393945_mag-logo (2).png', '1782719530_1782718640_Divya_signature.png', '2026-03-13 09:22:22');

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` int(11) NOT NULL,
  `invoice_number` varchar(50) DEFAULT NULL,
  `invoice_type` enum('gst','export') DEFAULT NULL,
  `client_id` int(11) DEFAULT NULL,
  `invoice_date` date DEFAULT NULL,
  `financial_year` varchar(20) DEFAULT NULL,
  `subtotal` decimal(10,2) DEFAULT NULL,
  `tax_amount` decimal(10,2) DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `currency` varchar(10) DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `place_of_supply` varchar(100) DEFAULT NULL,
  `payment_terms` varchar(100) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `cgst` decimal(10,2) DEFAULT NULL,
  `sgst` decimal(10,2) DEFAULT NULL,
  `igst` decimal(10,2) DEFAULT 0.00,
  `grand_total` decimal(10,2) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`id`, `invoice_number`, `invoice_type`, `client_id`, `invoice_date`, `financial_year`, `subtotal`, `tax_amount`, `total_amount`, `currency`, `status`, `created_at`, `place_of_supply`, `payment_terms`, `due_date`, `cgst`, `sgst`, `igst`, `grand_total`, `deleted_at`) VALUES
(50, 'EXP/FY26-27/001', 'export', 8, '2026-07-07', '26-27', 400.00, NULL, NULL, 'USD', 'unpaid', '2026-07-07 14:15:18', '', '', NULL, 0.00, 0.00, 0.00, 400.00, NULL),
(57, 'INV/FY26-27/001', 'gst', 12, '2026-09-01', '26-27', 25000.00, NULL, NULL, 'INR', 'paid', '2026-09-01 09:03:08', 'Agra', '', '2026-09-01', 2250.00, 2250.00, 0.00, 29500.00, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `invoice_items`
--

CREATE TABLE `invoice_items` (
  `id` int(11) NOT NULL,
  `invoice_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `qty` int(11) DEFAULT NULL,
  `unit_price` decimal(10,2) DEFAULT NULL,
  `hsn_code` varchar(50) DEFAULT NULL,
  `tax_rate` decimal(5,2) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `invoice_items`
--

INSERT INTO `invoice_items` (`id`, `invoice_id`, `description`, `qty`, `unit_price`, `hsn_code`, `tax_rate`, `amount`) VALUES
(5, 0, 'sadf', 1, 45.00, 'dsf', NULL, 45.00),
(6, 0, 'asd', 1, 1.00, 'das', NULL, 1.00),
(7, 0, 'sadf', 2, 2.00, 'sdf', NULL, 4.00),
(8, 0, 'Web', 2, 4896.00, '1', NULL, 9792.00),
(9, 0, 'v', 3, 3.00, '', NULL, 9.00),
(10, 0, 'v', 3, 1.00, '', NULL, 3.00),
(70, 43, 'Website Development', 1, 1499.00, 'WEB12', NULL, 1499.00),
(80, 50, 'RKP Power Site Migration', 1, 400.00, '', NULL, 400.00),
(83, 51, 'Ecommerce Website development  (WordPress Technology)', 1, 25000.00, '', NULL, 25000.00),
(87, 55, 'r', 1, 45.00, '', NULL, 45.00),
(89, 56, 'Ecommerce Website development Services', 1, 25000.00, '998314', NULL, 25000.00),
(91, 57, 'ecommerce website development services', 1, 25000.00, '998314', NULL, 25000.00);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` varchar(20) DEFAULT 'admin',
  `admin_pin` varchar(50) DEFAULT '''123456''',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `admin_pin`, `created_at`) VALUES
(1, 'Admin', 'admin@magcloudsolutions.com', 'e10adc3949ba59abbe56e057f20f883e', 'admin', '123456', '2026-03-13 08:36:12');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `company_settings`
--
ALTER TABLE `company_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `company_settings`
--
ALTER TABLE `company_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `invoice_items`
--
ALTER TABLE `invoice_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=92;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
