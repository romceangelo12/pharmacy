-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 12, 2026 at 04:25 AM
-- Server version: 10.11.16-MariaDB
-- PHP Version: 8.3.36

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `pharmacy_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'Antibiotics', 'Antimicrobial medicines used in the treatment and prevention of bacterial infections.', '2026-11-12 03:10:57'),
(2, 'Analgesics & Antipyretics', 'Pain relief and fever reduction medications.', '2026-11-12 03:10:57'),
(3, 'Cardiovascular', 'Heart and blood pressure management drugs.', '2026-11-12 03:10:57'),
(4, 'Vitamins & Supplements', 'Essential dietary nutrients and health boosters.', '2026-11-12 03:10:57'),
(5, 'Respiratory & Antihistamines', 'Allergy relief, asthma and respiratory care.', '2026-11-12 03:10:57');

-- --------------------------------------------------------

--
-- Table structure for table `medicines`
--

CREATE TABLE `medicines` (
  `id` int(11) NOT NULL,
  `sku` varchar(50) NOT NULL,
  `name` varchar(150) NOT NULL,
  `generic_name` varchar(150) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `batch_number` varchar(50) DEFAULT NULL,
  `dosage_form` varchar(50) DEFAULT 'Tablet',
  `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `cost_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `min_alert_qty` int(11) NOT NULL DEFAULT 20,
  `expiry_date` date NOT NULL,
  `status` enum('in_stock','low_stock','out_of_stock','expired') DEFAULT 'in_stock',
  `report_data` longtext DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `medicines`
--

INSERT INTO `medicines` (`id`, `sku`, `name`, `generic_name`, `category_id`, `batch_number`, `dosage_form`, `unit_price`, `cost_price`, `quantity`, `min_alert_qty`, `expiry_date`, `status`, `created_at`) VALUES
(1, 'MED-001', 'Amoxicillin 500mg', 'Amoxicillin Trihydrate', 1, 'BATCH-2024A', 'Capsule', 12.50, 8.00, 350, 50, '2027-12-17', 'in_stock', '2026-11-12 03:10:57'),
(2, 'MED-002', 'Paracetamol 500mg', 'Acetaminophen', 2, 'BATCH-2024B', 'Tablet', 3.00, 1.20, 850, 100, '2028-07-04', 'in_stock', '2026-11-12 03:10:57'),
(3, 'MED-003', 'Ibuprofen 400mg', 'Ibuprofen', 2, 'BATCH-2024C', 'Tablet', 7.50, 4.00, 15, 30, '2027-03-12', 'low_stock', '2026-11-12 03:10:57'),
(4, 'MED-004', 'Atorvastatin 20mg', 'Atorvastatin Calcium', 3, 'BATCH-2023X', 'Tablet', 24.00, 16.50, 120, 40, '2026-12-07', 'in_stock', '2026-11-12 03:10:57'),
(5, 'MED-005', 'Cetirizine 10mg', 'Cetirizine Hydrochloride', 5, 'BATCH-2024D', 'Tablet', 5.00, 2.50, 450, 60, '2028-11-11', 'in_stock', '2026-11-12 03:10:57'),
(6, 'MED-006', 'Vitamin C 1000mg', 'Ascorbic Acid', 4, 'BATCH-2024E', 'Effervescent', 15.00, 9.00, 8, 25, '2027-05-11', 'low_stock', '2026-11-12 03:10:57');

-- --------------------------------------------------------

--
-- Table structure for table `stock_logs`
--

CREATE TABLE `stock_logs` (
  `id` int(11) NOT NULL,
  `medicine_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `type` enum('intake','dispense','adjustment','expired_return') NOT NULL,
  `quantity` int(11) NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `logged_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('admin','pharmacist','inventory_clerk') NOT NULL DEFAULT 'pharmacist',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `full_name`, `role`, `status`, `last_login`, `created_at`) VALUES
(1, 'admin', 'admin@pharmacare.local', '$2y$10$5kQ8xl4Dftl1rDzzjZFGke8X6qyN7C1bwxzudSAcC4spXz61VC4Xm', 'Dr. Sarah Mitchell (Administrator)', 'admin', 'active', NULL, '2026-11-12 03:10:57'),
(2, 'pharmacist', 'pharmacist@pharmacare.local', '$2y$10$DxuRJilouCnouUCzQ55jM.hzSP/Wh0fmrMw.vjVGztDDg88jPaCD.', 'Dr. James Wilson (Chief Pharmacist)', 'pharmacist', 'active', NULL, '2026-11-12 03:10:57'),
(3, 'clerk', 'clerk@pharmacare.local', '$2y$10$A51mhYgdzr256uK5Lc/7huiQMhsXM7TeGAj36RADOHA3H7eq1d5ia', 'Alex Turner (Inventory Clerk)', 'inventory_clerk', 'active', NULL, '2026-11-12 03:10:57'),
(4, 'jochelle', 'jochelle1@gmail.com', '$2y$10$gIZDUCaDOO5n9vQR8RZEluJr9UNagyeHIIS3j8XqS2JSzmAS5ZzSG', 'jochelle', 'admin', 'active', '2026-11-12 11:20:06', '2026-11-12 03:19:48');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `medicines`
--
ALTER TABLE `medicines`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `idx_sku` (`sku`),
  ADD KEY `idx_expiry` (`expiry_date`);

--
-- Indexes for table `stock_logs`
--
ALTER TABLE `stock_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `medicine_id` (`medicine_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `medicines`
--
ALTER TABLE `medicines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `stock_logs`
--
ALTER TABLE `stock_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `medicines`
--
ALTER TABLE `medicines`
  ADD CONSTRAINT `medicines_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `stock_logs`
--
ALTER TABLE `stock_logs`
  ADD CONSTRAINT `stock_logs_ibfk_1` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_logs_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
