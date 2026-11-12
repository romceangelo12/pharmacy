-- ========================================================
-- JVGH Pharmacy - Database Schema & Real Inventory Seed
-- ========================================================

CREATE DATABASE IF NOT EXISTS `pharmacy_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `pharmacy_db`;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `stock_logs`;
DROP TABLE IF EXISTS `medicines`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- --------------------------------------------------------
-- Users Table
-- --------------------------------------------------------
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `role` ENUM('admin', 'pharmacist') NOT NULL DEFAULT 'pharmacist',
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `last_login` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Categories Table
-- --------------------------------------------------------
CREATE TABLE `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `description` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Medicines Inventory Table (with stock_type)
-- --------------------------------------------------------
CREATE TABLE `medicines` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `sku` VARCHAR(50) NOT NULL UNIQUE,
    `name` VARCHAR(180) NOT NULL,
    `generic_name` VARCHAR(180) NULL,
    `category_id` INT NULL,
    `stock_type` ENUM('regular', 'donation', 'mhmap') NOT NULL DEFAULT 'regular',
    `batch_number` VARCHAR(80) NULL,
    `dosage_form` VARCHAR(50) DEFAULT 'Tablet',
    `unit_price` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    `cost_price` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    `quantity` INT NOT NULL DEFAULT 0,
    `min_alert_qty` INT NOT NULL DEFAULT 20,
    `expiry_date` DATE NOT NULL,
    `status` ENUM('in_stock', 'critical_stock', 'out_of_stock', 'expired') DEFAULT 'in_stock',
    `report_data` LONGTEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL,
    INDEX `idx_stock_type` (`stock_type`),
    INDEX `idx_sku` (`sku`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- SEED DATA: Demo Users
-- --------------------------------------------------------
INSERT INTO `users` (`username`, `email`, `password`, `full_name`, `role`, `status`) VALUES
('admin', 'admin@jvghpharmacy.local', '$2y$10$5kQ8xl4Dftl1rDzzjZFGke8X6qyN7C1bwxzudSAcC4spXz61VC4Xm', 'Dr. Sarah Mitchell', 'admin', 'active'),
('pharmacist', 'pharmacist@jvghpharmacy.local', '$2y$10$DxuRJilouCnouUCzQ55jM.hzSP/Wh0fmrMw.vjVGztDDg88jPaCD.', 'Juliennevi J. Bautista, RPh', 'pharmacist', 'active');

-- --------------------------------------------------------
-- SEED DATA: Categories
-- --------------------------------------------------------
INSERT INTO `categories` (`id`, `name`, `description`) VALUES
(1, 'Anti-Biotics & Microbials', 'Infection treatment'),
(2, 'Cardiovascular & Anti-Hypertensives', 'Heart & blood pressure medications'),
(3, 'Cough Suppressants & Respiratory', 'Asthma, COPD and respiratory care'),
(4, 'Analgesics & Anti-Inflammatory', 'Pain relief and antipyretics'),
(5, 'Mental Health & Psychotropics', 'Psychiatric medications and neurologic support'),
(6, 'Vitamins & Minerals', 'Dietary nutrients and health boosters'),
(7, 'Intravenous Fluids & Electrolytes', 'IV solutions, vials and parenteral nutrition');

-- --------------------------------------------------------
-- SEED DATA: MEDICINES (Regular, Donation, MHMAP)
-- --------------------------------------------------------

-- 1. REGULAR STOCKS
INSERT INTO `medicines` (`sku`, `name`, `generic_name`, `category_id`, `stock_type`, `batch_number`, `dosage_form`, `unit_price`, `quantity`, `min_alert_qty`, `expiry_date`, `status`) VALUES
('REG-001', '0.9 Sodium Chloride 50ml vial', 'Sodium Chloride', 7, 'regular', '260418', 'Vial', 44.55, 784, 50, '2031-04-01', 'in_stock'),
('REG-002', '0.9 Sodium Chloride 100ml vial', 'Sodium Chloride', 7, 'regular', 'N/A', 'Vial', 49.00, 0, 20, '2028-01-01', 'out_of_stock'),
('REG-003', 'Acetazolamide 250mg tablet', 'Acetazolamide', 2, 'regular', 'N/A', 'Tablet', 18.50, 4, 15, '2027-12-01', 'critical_stock'),
('REG-004', 'Acetylcysteine 100mg/ml, 3ml solution', 'Acetylcysteine', 3, 'regular', '2142501', 'Nebule', 278.00, 14, 20, '2027-07-01', 'critical_stock'),
('REG-005', 'Acetylcysteine 200mg powder', 'Acetylcysteine', 3, 'regular', 'BV25001', 'Powder', 10.00, 695, 100, '2027-02-01', 'in_stock'),
('REG-006', 'Acetylcysteine 600mg tablet', 'Acetylcysteine', 3, 'regular', '2603085', 'Tablet', 23.00, 1661, 200, '2029-03-01', 'in_stock'),
('REG-007', 'Acyclovir 800mg tablet', 'Acyclovir', 1, 'regular', 'SVA 03', 'Tablet', 45.25, 37, 50, '2028-11-01', 'critical_stock'),
('REG-008', 'Adenosine 3mg/ml, 2ml vial', 'Adenosine', 2, 'regular', '2F25006', 'Vial', 260.00, 108, 30, '2028-07-01', 'in_stock'),
('REG-009', 'Albumin Human 20%, 50ml', 'Human Albumin', 7, 'regular', 'A02K008242', 'Bottle', 2650.00, 50, 30, '2028-02-01', 'in_stock'),
('REG-010', 'Allopurinol 300mg tablet', 'Allopurinol', 4, 'regular', '022AJN', 'Tablet', 7.00, 749, 100, '2027-09-01', 'in_stock'),
('REG-011', 'Amlodipine besilate 10mg tablet', 'Amlodipine Besilate', 2, 'regular', '2608095', 'Tablet', 0.50, 6481, 500, '2028-11-01', 'in_stock'),
('REG-012', 'Amlodipine besilate 5mg tablet', 'Amlodipine Besilate', 2, 'regular', '2605075', 'Tablet', 0.45, 44153, 1000, '2029-11-01', 'in_stock'),
('REG-013', 'Amoxicillin trihydrate 500mg capsule', 'Amoxicillin', 1, 'regular', '706251067', 'Capsule', 6.00, 2841, 300, '2028-10-01', 'in_stock'),
('REG-014', 'Ampicillin sodium 1g vial', 'Ampicillin Sodium', 1, 'regular', '2603005', 'Vial', 13.00, 3148, 250, '2029-03-01', 'in_stock'),
('REG-015', 'Ascorbic Acid 500mg tablet', 'Vitamin C', 6, 'regular', '2601156', 'Tablet', 4.10, 14182, 1000, '2029-01-01', 'in_stock'),
('REG-016', 'Aspirin 80mg tablet', 'Acetylsalicylic Acid', 2, 'regular', '260270', 'Tablet', 1.76, 5853, 500, '2028-02-01', 'in_stock'),
('REG-017', 'Atorvastatin 10mg tablet', 'Atorvastatin', 2, 'regular', '2603117', 'Tablet', 0.98, 4915, 300, '2028-03-01', 'in_stock'),
('REG-018', 'Atorvastatin 20mg tablet', 'Atorvastatin', 2, 'regular', 'KT500', 'Tablet', 1.95, 2161, 200, '2028-11-01', 'in_stock'),
('REG-019', 'Paracetamol 500mg tablet', 'Paracetamol', 4, 'regular', '260337', 'Tablet', 2.25, 2159, 300, '2029-03-01', 'in_stock'),
('REG-020', 'Paracetamol 10mg/ml 100ml bottle (1g IV)', 'Paracetamol IV', 4, 'regular', '260567', 'Bottle', 54.00, 510, 80, '2029-05-01', 'in_stock');

-- 2. DONATION STOCKS
INSERT INTO `medicines` (`sku`, `name`, `generic_name`, `category_id`, `stock_type`, `batch_number`, `dosage_form`, `unit_price`, `quantity`, `min_alert_qty`, `expiry_date`, `status`) VALUES
('DON-001', 'Amikacin 125mg/ml vial', 'Amikacin', 1, 'donation', '37800224', 'Vial', 0.00, 500, 50, '2027-10-01', 'in_stock'),
('DON-002', 'Amikacin 250mg/ml vial', 'Amikacin', 1, 'donation', '37900324', 'Vial', 0.00, 500, 50, '2027-02-01', 'in_stock'),
('DON-003', 'Amino Acid 7% solution', 'Amino Acids', 7, 'donation', '2250117', 'Bottle', 0.00, 24, 30, '2026-12-01', 'critical_stock'),
('DON-004', 'Amlodipine 10mg tablet (Donated)', 'Amlodipine', 2, 'donation', 'GT24128', 'Tablet', 0.00, 5000, 500, '2027-01-01', 'in_stock'),
('DON-005', 'Azithromycin 500mg vial', 'Azithromycin', 1, 'donation', 'R45240151', 'Vial', 0.00, 397, 50, '2027-01-01', 'in_stock'),
('DON-006', 'BCG Vaccine', 'BCG', 1, 'donation', '0375MA079', 'Vial', 0.00, 18, 20, '2027-09-01', 'critical_stock'),
('DON-007', 'Cefuroxime 1.5g vial', 'Cefuroxime', 1, 'donation', 'PGH-2026', 'Vial', 0.00, 660, 100, '2026-11-01', 'in_stock'),
('DON-008', 'Cefuroxime sodium 750mg vial', 'Cefuroxime', 1, 'donation', 'RETA-2026', 'Vial', 0.00, 750, 100, '2027-01-01', 'in_stock'),
('DON-009', 'Diphenhydramine 50mg/ml 1ml ampule', 'Diphenhydramine', 3, 'donation', 'DOH-TALA', 'Ampule', 0.00, 7207, 500, '2027-03-01', 'in_stock'),
('DON-010', 'Doxycycline 100mg capsule', 'Doxycycline', 1, 'donation', 'DOT602', 'Capsule', 0.00, 5000, 300, '2029-05-31', 'in_stock'),
('DON-011', 'Human Insulin (Regular)', 'Regular Insulin', 2, 'donation', '54A2404', 'Vial', 0.00, 239, 40, '2027-01-01', 'in_stock'),
('DON-012', 'Hydralazine 20mg/ml ampule', 'Hydralazine', 2, 'donation', '24AP01', 'Ampule', 0.00, 458, 50, '2027-05-01', 'in_stock'),
('DON-013', 'Losartan 100mg tablet', 'Losartan', 2, 'donation', 'EX406', 'Tablet', 0.00, 6229, 500, '2027-09-01', 'in_stock'),
('DON-014', 'Sterile Water 10ml ampule', 'Sterile Water', 7, 'donation', '18Z3264', 'Ampule', 0.00, 1107, 100, '2027-07-01', 'in_stock');

-- 3. MHMAP (Mental Health Medicines Access Program)
INSERT INTO `medicines` (`sku`, `name`, `generic_name`, `category_id`, `stock_type`, `batch_number`, `dosage_form`, `unit_price`, `quantity`, `min_alert_qty`, `expiry_date`, `status`) VALUES
('MHM-001', 'Carbamazepine 200mg tablet', 'Carbamazepine', 5, 'mhmap', 'PH21-79', 'Tablet', 0.00, 0, 50, '2027-01-01', 'out_of_stock'),
('MHM-002', 'Chlorpromazine HCl 200mg tablet', 'Chlorpromazine', 5, 'mhmap', 'N/A', 'Tablet', 0.00, 0, 30, '2027-01-01', 'out_of_stock'),
('MHM-003', 'Clozapine 100mg tablet', 'Clozapine', 5, 'mhmap', 'B344KO15', 'Tablet', 0.00, 0, 30, '2027-02-01', 'out_of_stock'),
('MHM-004', 'Donepezil HCl 10mg tablet', 'Donepezil', 5, 'mhmap', 'BZ01K010', 'Tablet', 0.00, 100, 25, '2027-07-01', 'in_stock'),
('MHM-005', 'Escitalopram oxalate 10mg tablet', 'Escitalopram', 5, 'mhmap', 'ESC-2026', 'Tablet', 0.00, 73, 20, '2027-06-01', 'in_stock'),
('MHM-006', 'Fluoxetine 20mg capsule', 'Fluoxetine', 5, 'mhmap', 'PH-2519', 'Capsule', 0.00, 0, 20, '2027-02-01', 'out_of_stock'),
('MHM-007', 'Haloperidol 5mg tablet', 'Haloperidol', 5, 'mhmap', 'CFB24004', 'Tablet', 0.00, 88, 20, '2027-11-30', 'in_stock'),
('MHM-008', 'Haloperidol 5mg/ml 1ml injection', 'Haloperidol Injection', 5, 'mhmap', '243532', 'Ampule', 0.00, 3, 10, '2027-11-30', 'critical_stock'),
('MHM-009', 'Lamotrigine 100mg tablet', 'Lamotrigine', 5, 'mhmap', 'B662L006', 'Tablet', 0.00, 90, 20, '2028-12-31', 'in_stock'),
('MHM-010', 'Lithium Carbonate 450mg tablet', 'Lithium Carbonate', 5, 'mhmap', 'N/A', 'Tablet', 0.00, 0, 25, '2027-01-01', 'out_of_stock'),
('MHM-011', 'Memantine HCl 10mg tablet', 'Memantine', 5, 'mhmap', 'BDMGL004', 'Tablet', 0.00, 0, 30, '2027-05-31', 'out_of_stock'),
('MHM-012', 'Olanzapine HCl 10mg tablet', 'Olanzapine', 5, 'mhmap', 'N/A', 'Tablet', 0.00, 0, 30, '2027-01-01', 'out_of_stock'),
('MHM-013', 'Quetiapine fumarate 200mg tablet', 'Quetiapine', 5, 'mhmap', 'BUX2303', 'Tablet', 0.00, 0, 40, '2026-09-01', 'out_of_stock'),
('MHM-014', 'Risperidone 2mg tablet', 'Risperidone', 5, 'mhmap', 'N/A', 'Tablet', 0.00, 0, 50, '2027-01-01', 'out_of_stock'),
('MHM-015', 'Sertraline 50mg tablet', 'Sertraline', 5, 'mhmap', 'BPHO-631', 'Tablet', 0.00, 0, 40, '2026-10-01', 'out_of_stock'),
('MHM-016', 'Valproic acid 250mg tablet', 'Valproic Acid', 5, 'mhmap', 'N/A', 'Tablet', 0.00, 0, 30, '2027-01-01', 'out_of_stock'),
('MHM-017', 'Valproic acid 500mg tablet', 'Valproic Acid', 5, 'mhmap', 'N/A', 'Tablet', 0.00, 0, 30, '2027-01-01', 'out_of_stock'),
('MHM-018', 'Valproic acid 250mg/5ml syrup, 120ml', 'Valproic Acid Syrup', 5, 'mhmap', 'N/A', 'Syrup', 0.00, 0, 15, '2027-01-01', 'out_of_stock');
