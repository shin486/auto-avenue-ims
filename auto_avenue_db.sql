-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 04, 2025 at 02:01 PM
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
-- Database: `auto_avenue_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `alerts`
--

CREATE TABLE `alerts` (
  `alert_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `message` varchar(200) NOT NULL,
  `alert_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_resolved` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `alerts`
--

INSERT INTO `alerts` (`alert_id`, `product_id`, `message`, `alert_date`, `is_resolved`) VALUES
(40, 16, 'Low stock alert for product ID 16 (Current: 10)', '2025-06-03 05:04:14', 0),
(41, 16, 'Low stock alert for product ID 16 (Current: 9)', '2025-06-03 05:04:15', 0),
(42, 17, 'Low stock alert for product ID 17 (Current: 10)', '2025-06-03 06:12:14', 0),
(43, 17, 'Low stock alert for product ID 17 (Current: 9)', '2025-06-03 06:26:56', 0),
(44, 17, 'Low stock alert for product ID 17 (Current: 8)', '2025-06-03 06:27:14', 0),
(45, 16, 'Low stock alert for product ID 16 (Current: 10)', '2025-06-03 06:27:44', 0),
(46, 17, 'Low stock alert for product ID 17 (Current: 9)', '2025-06-03 06:27:45', 0),
(47, 17, 'Low stock alert for product ID 17 (Current: 10)', '2025-06-03 06:27:48', 0),
(48, 17, 'Low stock alert for product ID 17 (Current: 0)', '2025-06-03 06:50:16', 0);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `price` decimal(10,2) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `min_stock_level` int(11) DEFAULT 10,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1,
  `warranty_period` varchar(50) DEFAULT NULL,
  `warranty_terms` text DEFAULT NULL,
  `vin_code` varchar(17) DEFAULT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `supplier` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `name`, `quantity`, `price`, `category`, `min_stock_level`, `last_updated`, `is_active`, `warranty_period`, `warranty_terms`, `vin_code`, `supplier_id`, `supplier`) VALUES
(16, 'AC Delco 41-993', 1062, 200.00, 'Chevrolet Parts PH', 10, '2025-06-04 11:33:56', 1, '7 days replacement', 'Defect-only warranty. No coverage for installation errors or misfiring engines.', '', NULL, 'Spark Plug'),
(17, 'NGK BKR6E-11', 38, 180.00, 'NGK Philippines', 10, '2025-06-04 07:19:33', 1, '7 days replacement', 'Replacement only for factory defects. Must return with packaging and receipt.', '', NULL, 'Spark Plug'),
(18, 'LODWIG', 500, 200.00, 'Chevrolet Parts PH', 10, '2025-06-04 11:26:31', 1, '1 year', 'ASD', '', NULL, 'Brake');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_orders`
--

CREATE TABLE `purchase_orders` (
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `order_date` datetime DEFAULT current_timestamp(),
  `is_confirmed` tinyint(1) NOT NULL DEFAULT 0,
  `order_status` enum('pending','confirmed','rejected') NOT NULL DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_terms` varchar(50) DEFAULT NULL,
  `expected_arrival` date DEFAULT NULL,
  `actual_arrival` date DEFAULT NULL,
  `order_number` varchar(50) NOT NULL,
  `category` varchar(100) NOT NULL,
  `product_id` varchar(100) NOT NULL,
  `purchase_order_number` varchar(20) DEFAULT NULL,
  `receiving_notes` text DEFAULT NULL,
  `remaining_quantity` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchase_orders`
--

INSERT INTO `purchase_orders` (`order_id`, `user_id`, `notes`, `order_date`, `is_confirmed`, `order_status`, `payment_method`, `payment_terms`, `expected_arrival`, `actual_arrival`, `order_number`, `category`, `product_id`, `purchase_order_number`, `receiving_notes`, `remaining_quantity`) VALUES
(84, 5, '', '2025-06-04 15:33:07', 0, '', 'Cash on Delivery', '', '2025-12-08', NULL, 'PO-20250604-001', 'Chevrolet Parts PH', '', NULL, '', 0),
(89, 5, '', '2025-06-04 15:58:39', 0, '', 'Cash on Delivery', '', '2222-02-22', NULL, 'PO-20250604-006', 'Chevrolet Parts PH', '', NULL, '', 0),
(103, 5, '', '2025-06-04 19:33:41', 0, '', 'Cash on Delivery', '', '0123-03-12', NULL, 'PO-20250604-003', 'Chevrolet Parts PH', '', NULL, '', 95),
(104, 6, '', '2025-06-04 20:00:04', 0, 'pending', 'Cash on Delivery', '', '0003-03-31', NULL, 'PO-20250604-004', 'Chevrolet Parts PH', '', NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `purchase_order_items`
--

CREATE TABLE `purchase_order_items` (
  `item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL,
  `supplier_name` varchar(255) DEFAULT NULL,
  `actual_quantity_received` int(11) DEFAULT NULL,
  `product_id` int(11) NOT NULL,
  `supplier` varchar(255) DEFAULT NULL,
  `received_qty` int(11) DEFAULT NULL,
  `defective_qty` int(11) DEFAULT 0,
  `defect_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchase_order_items`
--

INSERT INTO `purchase_order_items` (`item_id`, `order_id`, `product_name`, `category`, `quantity`, `supplier_name`, `actual_quantity_received`, `product_id`, `supplier`, `received_qty`, `defective_qty`, `defect_notes`) VALUES
(222, 84, 'AC Delco 41-993', 'Chevrolet Parts PH', 5, NULL, NULL, 16, NULL, 5, 0, NULL),
(223, 84, 'LODWIG', 'Chevrolet Parts PH', 5, NULL, NULL, 18, NULL, 5, 0, NULL),
(230, 89, 'AC Delco 41-993', 'Chevrolet Parts PH', 15, NULL, NULL, 16, NULL, 15, 0, NULL),
(231, 89, 'LODWIG', 'Chevrolet Parts PH', 15, NULL, NULL, 18, NULL, 15, 0, NULL),
(254, 103, 'AC Delco 41-993', 'Chevrolet Parts PH', 300, NULL, NULL, 16, NULL, 205, 0, NULL),
(255, 104, 'AC Delco 41-993', 'Chevrolet Parts PH', 50, NULL, NULL, 16, NULL, NULL, 0, NULL),
(256, 104, 'LODWIG', 'Chevrolet Parts PH', 50, NULL, NULL, 18, NULL, NULL, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `sale_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity_sold` int(11) NOT NULL,
  `sale_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`sale_id`, `product_id`, `quantity_sold`, `sale_date`) VALUES
(12, 16, 3, '2025-06-02 03:49:43'),
(13, 17, 2, '2025-06-03 06:12:14'),
(14, 17, 1, '2025-06-03 00:26:56'),
(15, 17, 1, '2025-06-03 00:27:14'),
(16, 17, 11, '2025-06-03 00:50:16');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `supplier_id` int(11) NOT NULL,
  `supplier_name` varchar(100) NOT NULL,
  `contact_email` varchar(100) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `transaction_id` int(11) NOT NULL,
  `action_type` varchar(50) NOT NULL,
  `reference_id` int(11) NOT NULL,
  `reference_table` varchar(50) NOT NULL,
  `performed_by` int(11) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `remarks` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`transaction_id`, `action_type`, `reference_id`, `reference_table`, `performed_by`, `timestamp`, `remarks`) VALUES
(1, 'order_created', 76, 'purchase_orders', 5, '2025-06-04 06:15:03', '{\"order_number\":\"PO-20250604-001\",\"items_count\":1}'),
(2, 'order_created', 77, 'purchase_orders', 5, '2025-06-04 06:26:59', '{\"order_number\":\"PO-20250604-002\",\"items_count\":2}'),
(3, 'order_created', 78, 'purchase_orders', 5, '2025-06-04 06:38:20', '{\"order_number\":\"PO-20250604-002\",\"items_count\":2}'),
(4, 'order_created', 79, 'purchase_orders', 5, '2025-06-04 06:42:55', '{\"order_number\":\"PO-20250604-003\",\"items_count\":1}'),
(5, 'order_created', 80, 'purchase_orders', 5, '2025-06-04 06:57:22', '{\"order_number\":\"PO-20250604-004\",\"items_count\":2}'),
(6, 'order_created', 81, 'purchase_orders', 5, '2025-06-04 07:09:50', '{\"order_number\":\"PO-20250604-005\",\"items_count\":2}'),
(7, 'stock_received', 79, 'purchase_orders', 5, '2025-06-04 07:19:33', 'Order #79 received - stock updated'),
(8, 'stock_received', 78, 'purchase_orders', 5, '2025-06-04 07:20:06', 'Order #78 received - stock updated'),
(9, 'order_created', 82, 'purchase_orders', 5, '2025-06-04 07:24:03', '{\"order_number\":\"PO-20250604-003\",\"items_count\":2}'),
(10, 'stock_received', 82, 'purchase_orders', 5, '2025-06-04 07:25:32', 'Order #82 received - stock updated'),
(11, 'order_created', 83, 'purchase_orders', 5, '2025-06-04 07:26:39', '{\"order_number\":\"PO-20250604-004\",\"items_count\":2}'),
(12, 'stock_received', 83, 'purchase_orders', 5, '2025-06-04 07:32:26', 'Order #83 received - stock updated'),
(13, 'order_created', 84, 'purchase_orders', 5, '2025-06-04 07:33:07', '{\"order_number\":\"PO-20250604-001\",\"items_count\":2}'),
(14, 'stock_received', 84, 'purchase_orders', 5, '2025-06-04 07:33:19', 'Order #84 received - stock updated'),
(15, 'order_created', 85, 'purchase_orders', 5, '2025-06-04 07:39:31', '{\"order_number\":\"PO-20250604-002\",\"items_count\":2}'),
(16, 'stock_received', 85, 'purchase_orders', 5, '2025-06-04 07:39:45', 'Order #85 received. Notes: '),
(17, 'order_created', 86, 'purchase_orders', 5, '2025-06-04 07:40:11', '{\"order_number\":\"PO-20250604-003\",\"items_count\":2}'),
(18, 'stock_received', 86, 'purchase_orders', 5, '2025-06-04 07:40:23', 'Order #86 received. Notes: '),
(19, 'order_created', 87, 'purchase_orders', 5, '2025-06-04 07:41:10', '{\"order_number\":\"PO-20250604-004\",\"items_count\":2}'),
(20, 'stock_received', 87, 'purchase_orders', 5, '2025-06-04 07:49:07', 'Order #87 received - stock updated'),
(21, 'order_created', 88, 'purchase_orders', 5, '2025-06-04 07:49:32', '{\"order_number\":\"PO-20250604-005\",\"items_count\":2}'),
(22, 'stock_received', 88, 'purchase_orders', 5, '2025-06-04 07:58:22', 'Order #88 received - stock updated'),
(23, 'order_created', 89, 'purchase_orders', 5, '2025-06-04 07:58:39', '{\"order_number\":\"PO-20250604-006\",\"items_count\":2}'),
(24, 'stock_received', 89, 'purchase_orders', 5, '2025-06-04 08:02:14', 'Order #89 received - stock updated'),
(25, 'order_created', 90, 'purchase_orders', 5, '2025-06-04 08:02:31', '{\"order_number\":\"PO-20250604-007\",\"items_count\":2}'),
(26, 'stock_received', 90, 'purchase_orders', 5, '2025-06-04 08:02:40', 'Order #90 received - stock updated'),
(27, 'order_created', 91, 'purchase_orders', 5, '2025-06-04 08:04:42', '{\"order_number\":\"PO-20250604-008\",\"items_count\":2}'),
(28, 'stock_received', 91, 'purchase_orders', 5, '2025-06-04 08:04:47', 'Order #91 received - stock updated'),
(29, 'order_created', 92, 'purchase_orders', 5, '2025-06-04 08:06:22', '{\"order_number\":\"PO-20250604-009\",\"items_count\":2}'),
(30, 'stock_received', 92, 'purchase_orders', 5, '2025-06-04 08:15:50', 'Order #92 received with shortages'),
(31, 'order_created', 93, 'purchase_orders', 5, '2025-06-04 08:21:24', '{\"order_number\":\"PO-20250604-010\",\"items_count\":2}'),
(32, 'stock_received', 93, 'purchase_orders', 5, '2025-06-04 08:21:33', 'Order #93 received with shortages'),
(33, 'order_created', 94, 'purchase_orders', 5, '2025-06-04 10:04:35', '{\"order_number\":\"PO-20250604-011\",\"items_count\":2}'),
(34, 'order_created', 95, 'purchase_orders', 5, '2025-06-04 10:07:03', '{\"order_number\":\"PO-20250604-012\",\"items_count\":2}'),
(35, 'stock_received', 95, 'purchase_orders', 5, '2025-06-04 10:08:32', 'Order #95 received with shortages (8 items remaining)'),
(36, 'order_created', 96, 'purchase_orders', 5, '2025-06-04 10:09:06', '{\"order_number\":\"PO-20250604-013\",\"items_count\":2}'),
(37, 'stock_received', 96, 'purchase_orders', 5, '2025-06-04 10:11:09', 'Order #96 received with shortages (6 items remaining)'),
(38, 'stock_received', 94, 'purchase_orders', 5, '2025-06-04 10:12:47', 'Order #94 received with shortages (2 items remaining)'),
(39, 'stock_received', 94, 'purchase_orders', 5, '2025-06-04 10:14:51', 'Order #94 fully received'),
(40, 'stock_received', 96, 'purchase_orders', 5, '2025-06-04 10:14:58', 'Order #96 fully received'),
(41, 'stock_received', 95, 'purchase_orders', 5, '2025-06-04 10:15:09', 'Order #95 received with shortages (5 items remaining)'),
(42, 'stock_received', 95, 'purchase_orders', 5, '2025-06-04 10:15:16', 'Order #95 fully received'),
(43, 'stock_received', 96, 'purchase_orders', 5, '2025-06-04 10:20:13', 'Order #96 fully received - Delivery completed'),
(44, 'stock_received', 94, 'purchase_orders', 5, '2025-06-04 10:20:53', 'Order #94 fully received - Delivery completed'),
(45, 'stock_received', 96, 'purchase_orders', 5, '2025-06-04 10:20:58', 'Order #96 fully received - Delivery completed'),
(46, 'stock_received', 96, 'purchase_orders', 5, '2025-06-04 10:21:01', 'Order #96 fully received - Delivery completed'),
(47, 'stock_received', 96, 'purchase_orders', 5, '2025-06-04 10:21:04', 'Order #96 fully received - Delivery completed'),
(48, 'stock_received', 96, 'purchase_orders', 5, '2025-06-04 10:21:10', 'Order #96 fully received - Delivery completed'),
(49, 'stock_received', 90, 'purchase_orders', 5, '2025-06-04 10:24:44', 'Order #90 received with shortages (4 items remaining)'),
(50, 'stock_received', 92, 'purchase_orders', 5, '2025-06-04 10:25:42', 'Order #92 fully received - Delivery completed'),
(51, 'stock_received', 91, 'purchase_orders', 5, '2025-06-04 10:30:28', 'Order #91 received with shortages (5 items remaining) with defects (5 defective items)'),
(52, 'stock_received', 91, 'purchase_orders', 5, '2025-06-04 10:30:48', 'Order #91 received with defects (10 defective items)'),
(53, 'stock_received', 91, 'purchase_orders', 5, '2025-06-04 10:31:02', 'Order #91 received with defects (5 defective items)'),
(54, 'stock_received', 90, 'purchase_orders', 5, '2025-06-04 10:34:22', 'Order #90 received - Delivery completed'),
(55, 'stock_received', 88, 'purchase_orders', 5, '2025-06-04 10:34:32', 'Order #88 received - Delivery completed'),
(56, 'order_created', 97, 'purchase_orders', 5, '2025-06-04 10:43:36', '{\"order_number\":\"PO-20250604-007\",\"items_count\":2}'),
(57, 'stock_received', 97, 'purchase_orders', 5, '2025-06-04 10:44:51', 'Order #97 received with shortages (10 items remaining)'),
(58, 'order_created', 98, 'purchase_orders', 5, '2025-06-04 10:48:08', '{\"order_number\":\"PO-20250604-008\",\"items_count\":2}'),
(59, 'stock_received', 98, 'purchase_orders', 5, '2025-06-04 10:48:21', 'Order #98 received with shortages (50 items remaining)'),
(60, 'order_created', 99, 'purchase_orders', 5, '2025-06-04 10:52:51', '{\"order_number\":\"PO-20250604-009\",\"items_count\":2}'),
(61, 'stock_received', 99, 'purchase_orders', 5, '2025-06-04 10:53:13', 'Order #99 received with shortages (6 items remaining)'),
(62, 'order_created', 100, 'purchase_orders', 5, '2025-06-04 10:55:36', '{\"order_number\":\"PO-20250604-010\",\"items_count\":2}'),
(63, 'stock_received', 100, 'purchase_orders', 5, '2025-06-04 10:55:41', 'Order #100 received with shortages (100 items remaining)'),
(64, 'order_created', 101, 'purchase_orders', 5, '2025-06-04 10:59:31', '{\"order_number\":\"PO-20250604-011\",\"items_count\":2}'),
(65, 'stock_received', 101, 'purchase_orders', 5, '2025-06-04 10:59:39', 'Order #101 received with shortages (5 items remaining)'),
(66, 'stock_received', 84, 'purchase_orders', 5, '2025-06-04 11:03:44', 'Order #84 fully received'),
(67, 'stock_received', 101, 'purchase_orders', 5, '2025-06-04 11:03:50', 'Order #101 fully received'),
(68, 'order_created', 102, 'purchase_orders', 5, '2025-06-04 11:09:00', '{\"order_number\":\"PO-20250604-012\",\"items_count\":2}'),
(69, 'stock_received', 102, 'purchase_orders', 5, '2025-06-04 11:09:22', 'Order #102 received with shortages (50 items remaining)'),
(70, 'stock_received', 102, 'purchase_orders', 5, '2025-06-04 11:09:38', 'Order #102 received with shortages (40 items remaining)'),
(71, 'stock_received', 97, 'purchase_orders', 5, '2025-06-04 11:09:49', 'Order #97 fully received'),
(72, 'stock_received', 102, 'purchase_orders', 5, '2025-06-04 11:12:31', 'Order #102 fully received'),
(73, 'stock_received', 100, 'purchase_orders', 5, '2025-06-04 11:17:05', 'Order #100 fully received'),
(74, 'stock_received', 98, 'purchase_orders', 5, '2025-06-04 11:26:31', 'Order #98 fully received'),
(75, 'order_deleted', 101, 'purchase_orders', 5, '2025-06-04 11:27:29', 'Order #101 deleted'),
(76, 'order_deleted', 102, 'purchase_orders', 5, '2025-06-04 11:27:31', 'Order #102 deleted'),
(77, 'order_deleted', 100, 'purchase_orders', 5, '2025-06-04 11:27:33', 'Order #100 deleted'),
(78, 'order_deleted', 99, 'purchase_orders', 5, '2025-06-04 11:27:35', 'Order #99 deleted'),
(79, 'order_deleted', 98, 'purchase_orders', 5, '2025-06-04 11:27:38', 'Order #98 deleted'),
(80, 'order_deleted', 97, 'purchase_orders', 5, '2025-06-04 11:27:40', 'Order #97 deleted'),
(81, 'order_deleted', 88, 'purchase_orders', 5, '2025-06-04 11:33:14', 'Order #88 deleted (last item removed)'),
(82, 'order_deleted', 86, 'purchase_orders', 5, '2025-06-04 11:33:19', 'Order #86 deleted (last item removed)'),
(83, 'order_deleted', 85, 'purchase_orders', 5, '2025-06-04 11:33:26', 'Order #85 deleted (last item removed)'),
(84, 'order_deleted', 87, 'purchase_orders', 5, '2025-06-04 11:33:30', 'Order #87 deleted'),
(85, 'order_created', 103, 'purchase_orders', 5, '2025-06-04 11:33:41', '{\"order_number\":\"PO-20250604-003\",\"items_count\":2}'),
(86, 'order_confirmed', 103, 'purchase_orders', 5, '2025-06-04 11:33:45', 'Order #103 confirmed'),
(87, 'stock_received', 103, 'purchase_orders', 5, '2025-06-04 11:33:56', 'Order #103 received with shortages (95 items remaining)'),
(88, 'order_created', 104, 'purchase_orders', 6, '2025-06-04 12:00:04', '{\"order_number\":\"PO-20250604-004\",\"items_count\":2}');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','staff','manager') NOT NULL DEFAULT 'staff'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `role`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
(2, 'staff1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff'),
(3, 'allen', '$2y$10$IoFPW76LOO8UI1OAykClfePH8r.y6PC1ZMla1H4sSvEkF1nvw2O2.', 'staff'),
(4, 'allenM', '$2y$10$ZpbrI5N7rv2dwo3u0HxXXeKLevQDhPpVgn.SRZWu0SVjwpe5LqoiG', 'manager'),
(5, 'admin1', '$2y$10$Y/YnAxITFn4VvqX9Q9YRgeL7ThuTptynHw6hP6sEvbHUcSw3vYUDS', 'manager'),
(6, 'allenstaff', '$2y$10$GuPv4cb3kUgm07E08toaPuU6ZtN0H0iFJBk5wQjH.aTK.mume2Xtu', 'staff'),
(7, 'asd', '$2y$10$ZmEh3pWwhgmUFDZHaGeMA.PTL9e7IoYz1LeVYmXkdm1xYg.jxzEEa', 'staff'),
(8, 'asdf', '$2y$10$M4/3Usdayib0cgq.S0O5Vew.8mhjapHVs2AlCxbRWZhdyCwAch1He', 'manager'),
(9, 'staff', '$2y$10$X50C1PRY/LEstrXIkQMSOO0UvgUj6zSiZWaAzDCDNCvZmajB9hiTK', 'staff');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `alerts`
--
ALTER TABLE `alerts`
  ADD PRIMARY KEY (`alert_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `fk_supplier` (`supplier_id`);

--
-- Indexes for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD PRIMARY KEY (`order_id`),
  ADD UNIQUE KEY `purchase_order_number` (`purchase_order_number`);

--
-- Indexes for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`sale_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`supplier_id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`transaction_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `alerts`
--
ALTER TABLE `alerts`
  MODIFY `alert_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=105;

--
-- AUTO_INCREMENT for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=257;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `sale_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `supplier_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=89;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `alerts`
--
ALTER TABLE `alerts`
  ADD CONSTRAINT `alerts_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`);

--
-- Constraints for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD CONSTRAINT `purchase_order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `purchase_orders` (`order_id`) ON DELETE CASCADE;

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
