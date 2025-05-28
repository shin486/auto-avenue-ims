-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 28, 2025 at 05:17 AM
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
(17, 2, 'Low stock alert for product ID 2 (Current: 9)', '2025-05-13 01:06:46', 0),
(18, 2, 'Low stock alert for product ID 2 (Current: 10)', '2025-05-13 01:06:47', 0),
(19, 2, 'Low stock alert for product ID 2 (Current: 8)', '2025-05-15 02:23:17', 0),
(20, 2, 'Low stock alert for product ID 2 (Current: 7)', '2025-05-21 05:13:58', 0),
(21, 2, 'Low stock alert for product ID 2 (Current: 6)', '2025-05-21 05:14:12', 0),
(22, 2, 'Low stock alert for product ID 2 (Current: 5)', '2025-05-21 05:14:13', 0),
(23, 2, 'Low stock alert for product ID 2 (Current: 4)', '2025-05-21 05:14:13', 0),
(24, 2, 'Low stock alert for product ID 2 (Current: 3)', '2025-05-21 05:14:25', 0),
(25, 2, 'Low stock alert for product ID 2 (Current: 4)', '2025-05-21 05:14:56', 0),
(26, 2, 'Low stock alert for product ID 2 (Current: 5)', '2025-05-21 05:14:57', 0),
(27, 2, 'Low stock alert for product ID 2 (Current: 6)', '2025-05-21 05:14:57', 0),
(28, 2, 'Low stock alert for product ID 2 (Current: 7)', '2025-05-21 05:14:57', 0),
(29, 2, 'Low stock alert for product ID 2 (Current: 8)', '2025-05-21 05:14:57', 0),
(30, 2, 'Low stock alert for product ID 2 (Current: 9)', '2025-05-21 05:14:57', 0),
(31, 2, 'Low stock alert for product ID 2 (Current: 10)', '2025-05-21 05:14:58', 0),
(32, 2, 'Low stock alert for product ID 2 (Current: 0)', '2025-05-21 05:15:10', 0),
(33, 12, 'Low stock alert for product ID 12 (Current: 1)', '2025-05-26 02:59:27', 0),
(34, 15, 'Low stock alert for product ID 15 (Current: 0)', '2025-05-26 05:50:43', 0),
(35, 2, 'Low stock alert for product ID 2 (Current: 1)', '2025-05-26 05:59:34', 0),
(36, 2, 'Low stock alert for product ID 2 (Current: 2)', '2025-05-26 05:59:35', 0);

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
  `supplier` varchar(100) DEFAULT NULL,
  `min_stock_level` int(11) DEFAULT 10,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1,
  `warranty_period` varchar(50) DEFAULT NULL,
  `warranty_terms` text DEFAULT NULL,
  `vin_code` varchar(17) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `name`, `quantity`, `price`, `category`, `supplier`, `min_stock_level`, `last_updated`, `is_active`, `warranty_period`, `warranty_terms`, `vin_code`) VALUES
(1, 'Engine Oil 5W-30', 12, 450.00, 'Fluids', 'Shell Philippines', 10, '2025-05-26 05:51:15', 1, NULL, NULL, NULL),
(2, 'Brake Pads', 2, 1200.00, 'Brakes', 'Brembo', 10, '2025-05-26 05:59:35', 1, NULL, NULL, NULL),
(10, 'Gizmo', 3, 300.00, 'Brake', 'Brembo', 10, '2025-05-21 05:10:39', 1, NULL, NULL, NULL),
(11, 'Ludwig', 10, 10.00, 'K', 'K', 3, '2025-05-21 05:23:01', 1, NULL, NULL, NULL),
(12, 'asd', 1, 3.00, 'asd', 'asd', 3, '2025-05-26 06:40:59', 1, '1 year', 'asdasd', '12345678912345678'),
(13, 'Gizmo', 12, 123.00, 'asds', 'asd', 2, '2025-05-21 06:21:33', 1, '1', 'Physical', ''),
(14, 'ASD', 3, 3.00, 'ASD', 'ASD', 3, '2025-05-21 07:02:28', 1, '1 month', 'P', '12345678901231233'),
(15, 'z', 0, 1.00, 'z', 'z', 13, '2025-05-26 05:50:43', 1, '1', '', '');

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
(4, 2, 3, '2025-05-15 02:23:17'),
(5, 2, 1, '2025-05-21 05:13:58'),
(6, 2, 1, '2025-05-21 05:14:25'),
(7, 2, 11, '2025-05-21 05:15:10'),
(8, 12, 2, '2025-05-26 02:59:27'),
(9, 15, 1, '2025-05-26 05:50:43'),
(10, 1, 3, '2025-05-26 05:51:15');

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
  ADD PRIMARY KEY (`product_id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`sale_id`),
  ADD KEY `product_id` (`product_id`);

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
  MODIFY `alert_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `sale_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

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
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;