-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 12, 2026 at 03:51 AM
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
-- Database: `invois`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `module` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

CREATE TABLE `clients` (
  `id` int(11) NOT NULL,
  `type` enum('client','vendor') DEFAULT 'client',
  `company_name` varchar(200) NOT NULL,
  `contact_person` varchar(150) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `tax_number` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `clients`
--

INSERT INTO `clients` (`id`, `type`, `company_name`, `contact_person`, `email`, `phone`, `address`, `tax_number`, `notes`, `created_at`) VALUES
(6, 'vendor', 'Winnie Solution Enterprise', NULL, NULL, NULL, 'Malaysia', NULL, 'IT hardware & accessories supplier', '2026-06-03 03:15:17'),
(7, 'vendor', 'Pointflex Sdn Bhd', NULL, NULL, NULL, 'Malaysia', NULL, 'Server infrastructure & DR solutions', '2026-06-03 03:15:17'),
(8, 'vendor', 'Agile Network Sdn Bhd', NULL, NULL, NULL, 'Malaysia', NULL, 'Network & antivirus solutions', '2026-06-03 03:15:17'),
(9, 'vendor', 'Nova Knowledge Sdn Bhd', NULL, NULL, NULL, 'Malaysia', NULL, 'ILMS/CMLMS/mobile apps development & support', '2026-06-03 03:15:17'),
(10, 'vendor', 'Vivid Technology', NULL, NULL, NULL, 'Malaysia', NULL, 'IT solutions', '2026-06-03 03:15:17'),
(11, 'vendor', 'C.T Infinite', NULL, NULL, NULL, 'Malaysia', NULL, 'Microsoft licensing', '2026-06-03 03:15:17'),
(12, 'vendor', 'Spectorsoft Solutions Sdn Bhd', NULL, NULL, NULL, 'Malaysia', NULL, 'IT maintenance & subscription services', '2026-06-03 03:15:17'),
(13, 'vendor', 'Nur Networks', NULL, NULL, NULL, 'Malaysia', NULL, 'Network equipment supplier', '2026-06-03 03:15:17'),
(14, 'vendor', 'My Com Technology', NULL, NULL, NULL, 'Malaysia', NULL, 'Notebook & computing equipment', '2026-06-03 03:15:17'),
(15, 'vendor', 'Peri Tech Technology Sdn Bhd', NULL, NULL, NULL, 'Malaysia', NULL, 'Antivirus & software licensing', '2026-06-03 03:15:17'),
(16, 'vendor', 'IP Serverone Solution Sdn Bhd', NULL, NULL, NULL, 'Malaysia', NULL, 'Domain & hosting services', '2026-06-03 03:15:17'),
(17, 'vendor', 'Starwood Resources', NULL, NULL, NULL, 'Malaysia', NULL, 'IT hardware & projector', '2026-06-03 03:15:17'),
(18, 'vendor', 'Harvey Norman', NULL, NULL, NULL, 'Malaysia', NULL, 'Electronics & computing', '2026-06-03 03:15:17'),
(19, 'vendor', 'Terabit Solutions', NULL, NULL, NULL, 'Malaysia', NULL, 'Cybersecurity solutions', '2026-06-03 03:15:17'),
(20, 'vendor', 'Render Tech Resources', NULL, NULL, NULL, 'Malaysia', NULL, 'Software licensing', '2026-06-03 03:15:17'),
(21, 'vendor', 'Baekfactor Sdn Bhd', NULL, NULL, NULL, 'Malaysia', NULL, 'UTM firewall & network maintenance', '2026-06-03 03:15:17'),
(22, 'vendor', 'Exabytes Network Sdn Bhd', NULL, NULL, NULL, 'Malaysia', NULL, 'Cloud server & domain hosting', '2026-06-03 03:15:17'),
(23, 'vendor', 'Riosue Media Group Sdn Bhd', NULL, NULL, NULL, 'Malaysia', NULL, 'Email marketing & digital services', '2026-06-03 03:15:17'),
(24, 'vendor', 'AR Dynamic Tech', NULL, NULL, NULL, 'Malaysia', NULL, 'PABX & telephone systems', '2026-06-03 03:15:17'),
(25, 'vendor', 'Bytely Technologies', NULL, NULL, NULL, 'Malaysia', NULL, 'Microsoft 365 subscription', '2026-06-03 03:15:17'),
(26, 'vendor', 'Enesta Technology Sdn Bhd', NULL, NULL, NULL, 'Malaysia', NULL, 'Portal & web development', '2026-06-03 03:15:17'),
(27, 'vendor', 'AF Setia Resources', NULL, NULL, NULL, 'Malaysia', NULL, 'Fibre cable & networking', '2026-06-03 03:15:17'),
(28, 'vendor', 'MY AB Enterprise', NULL, NULL, NULL, 'Malaysia', NULL, 'IT accessories', '2026-06-03 03:15:17'),
(29, 'vendor', 'Simpleplay Gateway Sdn Bhd', NULL, NULL, NULL, 'Malaysia', NULL, 'Gateway services', '2026-06-03 03:15:17'),
(30, 'vendor', 'All IT Hypermarket Sdn Bhd', NULL, NULL, NULL, 'Malaysia', NULL, 'IT hardware & accessories', '2026-06-03 03:15:17'),
(31, 'vendor', 'Green Empires Micro', NULL, NULL, NULL, 'Malaysia', NULL, 'Printer & toner supplier', '2026-06-03 03:15:17'),
(32, 'vendor', 'Infinitevoice Sdn Bhd', NULL, NULL, NULL, 'Malaysia', NULL, 'SMS credit services', '2026-06-03 03:15:17'),
(33, 'vendor', 'Cheap SSL Shop', NULL, NULL, NULL, 'Malaysia', NULL, 'SSL certificate provider', '2026-06-03 03:15:17'),
(34, 'vendor', 'Peri Technology Sdn Bhd', NULL, NULL, NULL, 'Malaysia', NULL, 'Antivirus renewal', '2026-06-03 03:15:17');

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `expense_date` date NOT NULL,
  `due_date` date DEFAULT NULL COMMENT 'Tarikh perlu bayar kepada vendor',
  `payment_status` enum('unpaid','paid','overdue') NOT NULL DEFAULT 'paid' COMMENT 'Status bayaran expense ini',
  `vendor` varchar(200) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `receipt_path` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expense_categories`
--

CREATE TABLE `expense_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `budget` decimal(15,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `expense_categories`
--

INSERT INTO `expense_categories` (`id`, `name`, `budget`, `created_at`) VALUES
(1, 'Office Supplies', 2000.00, '2026-05-29 02:01:47'),
(2, 'Utilities', 5000.00, '2026-05-29 02:01:47'),
(3, 'Travel', 3000.00, '2026-05-29 02:01:47'),
(4, 'Marketing', 4000.00, '2026-05-29 02:01:47'),
(5, 'Software', 6000.00, '2026-05-29 02:01:47'),
(6, 'Miscellaneous', 1500.00, '2026-05-29 02:01:47'),
(7, 'IT Hardware', 50000.00, '2026-06-03 03:15:17'),
(8, 'IT Maintenance', 40000.00, '2026-06-03 03:15:17'),
(9, 'IT Software & License', 30000.00, '2026-06-03 03:15:17'),
(10, 'IT Cloud & Hosting', 50000.00, '2026-06-03 03:15:17'),
(11, 'IT Security', 20000.00, '2026-06-03 03:15:17'),
(12, 'IT Miscellaneous', 10000.00, '2026-06-03 03:15:17');

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` int(11) NOT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `client_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `issue_date` date NOT NULL,
  `due_date` date NOT NULL,
  `subtotal` decimal(15,2) DEFAULT 0.00,
  `tax_rate` decimal(5,2) DEFAULT 0.00,
  `tax_amount` decimal(15,2) DEFAULT 0.00,
  `total_amount` decimal(15,2) DEFAULT 0.00,
  `paid_amount` decimal(15,2) DEFAULT 0.00,
  `status` enum('draft','sent','paid','partial','overdue','cancelled') DEFAULT 'draft',
  `document_path` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoice_items`
--

CREATE TABLE `invoice_items` (
  `id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `description` varchar(255) NOT NULL,
  `quantity` decimal(10,2) DEFAULT 1.00,
  `unit_price` decimal(15,2) DEFAULT 0.00,
  `total` decimal(15,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `message` text DEFAULT NULL,
  `type` enum('info','warning','danger','success') DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT 0,
  `link` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ocr_scans`
--

CREATE TABLE `ocr_scans` (
  `id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `extracted_text` longtext DEFAULT NULL,
  `extracted_amount` decimal(15,2) DEFAULT NULL,
  `extracted_date` date DEFAULT NULL,
  `extracted_vendor` varchar(200) DEFAULT NULL,
  `extracted_invoice_number` varchar(100) DEFAULT NULL,
  `confidence_score` decimal(5,2) DEFAULT NULL,
  `status` enum('pending','processed','saved','duplicate','failed') DEFAULT 'pending',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `payment_date` date NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `payment_method` enum('cash','bank_transfer','credit_card','cheque','online','other') DEFAULT 'bank_transfer',
  `reference_number` varchar(100) DEFAULT NULL,
  `status` enum('pending','completed','failed','refunded') DEFAULT 'completed',
  `remarks` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','staff') DEFAULT 'staff',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `status`, `created_at`) VALUES
(1, 'Administrator', 'admin@invois.com', '$2b$10$J4aogsrBRQkwEgCvYr1/xu5wKFXek5xROC6HldtsgIkUGCW7t/Boe', 'admin', 'active', '2026-05-29 02:01:47'),
(2, 'Staff User', 'staff@invois.com', '$2b$10$J4aogsrBRQkwEgCvYr1/xu5wKFXek5xROC6HldtsgIkUGCW7t/Boe', 'staff', 'active', '2026-05-29 02:01:47'),
(3, 'MOHD RAHIMI RADZI', 'rahimi.radzi@kowamas.com', '$2y$10$R2RvrbrVciFneqDqQkc/feO741vplxaplcZj.j5tbrpaLvoLYLc/C', 'admin', 'active', '2026-05-29 02:05:50');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_module` (`module`);

--
-- Indexes for table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_company` (`company_name`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_expense_date` (`expense_date`),
  ADD KEY `idx_due_date` (`due_date`),
  ADD KEY `idx_payment_status` (`payment_status`);

--
-- Indexes for table `expense_categories`
--
ALTER TABLE `expense_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_number` (`invoice_number`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_invoice_number` (`invoice_number`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_due_date` (`due_date`);

--
-- Indexes for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_id` (`invoice_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_read` (`user_id`,`is_read`);

--
-- Indexes for table `ocr_scans`
--
ALTER TABLE `ocr_scans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_id` (`invoice_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_payment_date` (`payment_date`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `expense_categories`
--
ALTER TABLE `expense_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `invoice_items`
--
ALTER TABLE `invoice_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ocr_scans`
--
ALTER TABLE `ocr_scans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `expenses_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `expense_categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `expenses_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `invoices_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD CONSTRAINT `invoice_items_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ocr_scans`
--
ALTER TABLE `ocr_scans`
  ADD CONSTRAINT `ocr_scans_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
