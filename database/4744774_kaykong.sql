-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: fdb1033.awardspace.net
-- Generation Time: Mar 31, 2026 at 04:57 AM
-- Server version: 8.0.32
-- PHP Version: 8.1.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `4744774_kaykong`
--

-- --------------------------------------------------------

--
-- Table structure for table `addresses`
--

CREATE TABLE `addresses` (
  `address_id` int NOT NULL,
  `home_no` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `moo` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `soi` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `road` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `village` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `remark` text COLLATE utf8mb4_general_ci,
  `sub_dist_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bill_purchases`
--

CREATE TABLE `bill_purchases` (
  `purchases_id` int NOT NULL,
  `sp_id` int DEFAULT NULL,
  `total_cost` decimal(12,2) DEFAULT '0.00',
  `purchase_date` date DEFAULT NULL,
  `received_date` date DEFAULT NULL,
  `purchase_status` tinyint DEFAULT NULL COMMENT '1=Pending, 2=Received, 0=Cancelled',
  `remark` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bill_sales`
--

CREATE TABLE `bill_sales` (
  `sale_id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `address_id` int DEFAULT NULL,
  `sale_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `total_price` decimal(12,2) NOT NULL,
  `shipping_type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `payment_type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sale_status` tinyint DEFAULT '1',
  `slip_img` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `slip_confirmed` tinyint DEFAULT '0',
  `remark` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `cart_id` int NOT NULL,
  `user_id` int NOT NULL,
  `prod_id` int NOT NULL,
  `quantity` int DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ;

-- --------------------------------------------------------

--
-- Table structure for table `details_sales`
--

CREATE TABLE `details_sales` (
  `dtl_sale_id` int NOT NULL,
  `sale_id` int NOT NULL,
  `product_id` int NOT NULL,
  `batch_id` int DEFAULT NULL,
  `quantity` int NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `subtotal` decimal(12,2) NOT NULL,
  `pmt_id` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ;

-- --------------------------------------------------------

--
-- Table structure for table `detail_purchases`
--

CREATE TABLE `detail_purchases` (
  `dlt_purchases_id` int NOT NULL,
  `purchases_id` int NOT NULL,
  `product_id` int NOT NULL,
  `order_qty` int NOT NULL,
  `received_qty` int DEFAULT '0',
  `unit_cost` decimal(10,2) NOT NULL,
  `subtotal` decimal(12,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ;

-- --------------------------------------------------------

--
-- Table structure for table `districts`
--

CREATE TABLE `districts` (
  `dist_id` int NOT NULL,
  `dist_name` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `prov_id` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `exp_id` int NOT NULL,
  `exp_cate_id` int NOT NULL,
  `user_id` int NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `exp_date` date NOT NULL,
  `remark` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expense_categories`
--

CREATE TABLE `expense_categories` (
  `exp_cate_id` int NOT NULL,
  `exp_cate_name` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `exp_cate_status` tinyint DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `not_id` int NOT NULL,
  `user_id` int NOT NULL,
  `title` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `message` text COLLATE utf8mb4_general_ci,
  `not_type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ref_id` int DEFAULT NULL,
  `is_read` tinyint DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `prod_id` int NOT NULL,
  `prod_name` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `type_id` int DEFAULT NULL,
  `barcode` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `detail` text COLLATE utf8mb4_general_ci,
  `img` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` tinyint DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ;

-- --------------------------------------------------------

--
-- Table structure for table `product_batches`
--

CREATE TABLE `product_batches` (
  `batche_id` int NOT NULL,
  `detail_purchase_id` int DEFAULT NULL,
  `product_id` int NOT NULL,
  `lot_qty` int NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `received_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ;

-- --------------------------------------------------------

--
-- Table structure for table `prod_types`
--

CREATE TABLE `prod_types` (
  `type_id` int NOT NULL,
  `type_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `type_status` tinyint DEFAULT '1',
  `create_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `prod_types`
--

INSERT INTO `prod_types` (`type_id`, `type_name`, `type_status`, `create_at`, `updated_at`) VALUES
(1, 'ขนม', 1, '2026-03-30 15:31:04', '2026-03-30 15:31:04'),
(2, 'เครื่องดื่ม', 1, '2026-03-30 15:31:42', '2026-03-30 15:31:42'),
(3, 'อาหารแห้ง', 1, '2026-03-30 15:31:52', '2026-03-30 15:31:52'),
(4, 'ครัวและเครื่องปรุงรส', 1, '2026-03-30 15:32:22', '2026-03-30 15:32:22'),
(5, 'ของใช้ในครัวเรือน', 1, '2026-03-30 15:32:49', '2026-03-30 15:32:49');

-- --------------------------------------------------------

--
-- Table structure for table `promotions`
--

CREATE TABLE `promotions` (
  `pmt_id` int NOT NULL,
  `pmt_name` varchar(150) NOT NULL,
  `pmt_type` tinyint DEFAULT NULL COMMENT '1=Fixed Price Single, 2=Fixed Price Bundle',
  `pmt_price` decimal(10,2) NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `pmt_status` tinyint DEFAULT '1'
) ;

-- --------------------------------------------------------

--
-- Table structure for table `promotion_items`
--

CREATE TABLE `promotion_items` (
  `pmt_item_id` int NOT NULL,
  `pmt_id` int NOT NULL,
  `prod_id` int NOT NULL,
  `qty_required` int DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `provinces`
--

CREATE TABLE `provinces` (
  `prov_id` int NOT NULL,
  `prov_name` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stocks`
--

CREATE TABLE `stocks` (
  `stock_id` int NOT NULL,
  `prod_id` int NOT NULL,
  `total_qty` int DEFAULT '0',
  `min_stock` int DEFAULT '5',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ;

-- --------------------------------------------------------

--
-- Table structure for table `stock_movements`
--

CREATE TABLE `stock_movements` (
  `movement_id` int NOT NULL,
  `prod_id` int NOT NULL,
  `movement_type` enum('IN','OUT','ADJUST') COLLATE utf8mb4_general_ci NOT NULL,
  `quantity` int NOT NULL,
  `ref_id` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `remark` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subdistricts`
--

CREATE TABLE `subdistricts` (
  `sub_dist_id` int NOT NULL,
  `sub_dist_name` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `zip_code` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `dist_id` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `supplers`
--

CREATE TABLE `supplers` (
  `sp_id` int NOT NULL,
  `sp_name` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `sp_tax` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sp_address` int DEFAULT NULL,
  `sp_email` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sp_status` tinyint DEFAULT '1',
  `ct_first_name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ct_last_name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ct_position` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ct_phone` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `first_name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nick_name` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phone_no` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `address_id` int DEFAULT NULL,
  `user_status` tinyint DEFAULT '1' COMMENT '1=Active, 0=Inactive',
  `role` enum('admin','staff','customer') COLLATE utf8mb4_general_ci DEFAULT 'customer',
  `profile` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `first_name`, `last_name`, `nick_name`, `phone_no`, `email`, `address_id`, `user_status`, `role`, `profile`, `created_at`, `updated_at`) VALUES
(2, 'user', '$2y$10$eHXSTsocjLGLRIO0gsRg3eLOT9B1O0mtuMKRPsrjmp8EQR7uaQuM.', 'ทดสอบ', 'ทดสอบ', NULL, '', 'test@gmail.com', NULL, 1, 'customer', NULL, '2026-03-29 18:04:06', '2026-03-29 18:04:06'),
(3, 'a', '$2y$10$.hrASaowGTWmDOi6fheYtO8W1UbeLPhvsZLvcCyURg1X5ZFEpZks.', 'System', 'Admin', NULL, '', 'admin@gmail.com', NULL, 1, 'admin', NULL, '2026-03-29 18:12:28', '2026-03-29 18:12:51');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `addresses`
--
ALTER TABLE `addresses`
  ADD PRIMARY KEY (`address_id`),
  ADD KEY `sub_dist_id` (`sub_dist_id`);

--
-- Indexes for table `bill_purchases`
--
ALTER TABLE `bill_purchases`
  ADD PRIMARY KEY (`purchases_id`),
  ADD KEY `sp_id` (`sp_id`);

--
-- Indexes for table `bill_sales`
--
ALTER TABLE `bill_sales`
  ADD PRIMARY KEY (`sale_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `address_id` (`address_id`);

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`cart_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `prod_id` (`prod_id`);

--
-- Indexes for table `details_sales`
--
ALTER TABLE `details_sales`
  ADD PRIMARY KEY (`dtl_sale_id`),
  ADD KEY `sale_id` (`sale_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `batch_id` (`batch_id`),
  ADD KEY `pmt_id` (`pmt_id`);

--
-- Indexes for table `detail_purchases`
--
ALTER TABLE `detail_purchases`
  ADD PRIMARY KEY (`dlt_purchases_id`),
  ADD KEY `purchases_id` (`purchases_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `districts`
--
ALTER TABLE `districts`
  ADD PRIMARY KEY (`dist_id`),
  ADD KEY `prov_id` (`prov_id`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`exp_id`),
  ADD KEY `exp_cate_id` (`exp_cate_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `expense_categories`
--
ALTER TABLE `expense_categories`
  ADD PRIMARY KEY (`exp_cate_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`not_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`prod_id`),
  ADD UNIQUE KEY `barcode` (`barcode`),
  ADD KEY `type_id` (`type_id`);

--
-- Indexes for table `product_batches`
--
ALTER TABLE `product_batches`
  ADD PRIMARY KEY (`batche_id`),
  ADD KEY `detail_purchase_id` (`detail_purchase_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `prod_types`
--
ALTER TABLE `prod_types`
  ADD PRIMARY KEY (`type_id`);

--
-- Indexes for table `promotions`
--
ALTER TABLE `promotions`
  ADD PRIMARY KEY (`pmt_id`);

--
-- Indexes for table `promotion_items`
--
ALTER TABLE `promotion_items`
  ADD PRIMARY KEY (`pmt_item_id`),
  ADD KEY `pmt_id` (`pmt_id`),
  ADD KEY `prod_id` (`prod_id`);

--
-- Indexes for table `provinces`
--
ALTER TABLE `provinces`
  ADD PRIMARY KEY (`prov_id`),
  ADD UNIQUE KEY `prov_name` (`prov_name`);

--
-- Indexes for table `stocks`
--
ALTER TABLE `stocks`
  ADD PRIMARY KEY (`stock_id`),
  ADD UNIQUE KEY `prod_id` (`prod_id`);

--
-- Indexes for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD PRIMARY KEY (`movement_id`),
  ADD KEY `prod_id` (`prod_id`);

--
-- Indexes for table `subdistricts`
--
ALTER TABLE `subdistricts`
  ADD PRIMARY KEY (`sub_dist_id`),
  ADD KEY `dist_id` (`dist_id`);

--
-- Indexes for table `supplers`
--
ALTER TABLE `supplers`
  ADD PRIMARY KEY (`sp_id`),
  ADD KEY `sp_address` (`sp_address`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `address_id` (`address_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `addresses`
--
ALTER TABLE `addresses`
  MODIFY `address_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bill_purchases`
--
ALTER TABLE `bill_purchases`
  MODIFY `purchases_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bill_sales`
--
ALTER TABLE `bill_sales`
  MODIFY `sale_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `cart_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `details_sales`
--
ALTER TABLE `details_sales`
  MODIFY `dtl_sale_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `detail_purchases`
--
ALTER TABLE `detail_purchases`
  MODIFY `dlt_purchases_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `districts`
--
ALTER TABLE `districts`
  MODIFY `dist_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `exp_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `expense_categories`
--
ALTER TABLE `expense_categories`
  MODIFY `exp_cate_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `not_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `prod_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_batches`
--
ALTER TABLE `product_batches`
  MODIFY `batche_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prod_types`
--
ALTER TABLE `prod_types`
  MODIFY `type_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `promotions`
--
ALTER TABLE `promotions`
  MODIFY `pmt_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `promotion_items`
--
ALTER TABLE `promotion_items`
  MODIFY `pmt_item_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `provinces`
--
ALTER TABLE `provinces`
  MODIFY `prov_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stocks`
--
ALTER TABLE `stocks`
  MODIFY `stock_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock_movements`
--
ALTER TABLE `stock_movements`
  MODIFY `movement_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subdistricts`
--
ALTER TABLE `subdistricts`
  MODIFY `sub_dist_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `supplers`
--
ALTER TABLE `supplers`
  MODIFY `sp_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `addresses`
--
ALTER TABLE `addresses`
  ADD CONSTRAINT `addresses_ibfk_1` FOREIGN KEY (`sub_dist_id`) REFERENCES `subdistricts` (`sub_dist_id`) ON UPDATE CASCADE;

--
-- Constraints for table `bill_purchases`
--
ALTER TABLE `bill_purchases`
  ADD CONSTRAINT `bill_purchases_ibfk_1` FOREIGN KEY (`sp_id`) REFERENCES `supplers` (`sp_id`);

--
-- Constraints for table `bill_sales`
--
ALTER TABLE `bill_sales`
  ADD CONSTRAINT `bill_sales_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `bill_sales_ibfk_2` FOREIGN KEY (`address_id`) REFERENCES `addresses` (`address_id`) ON DELETE SET NULL;

--
-- Constraints for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_items_ibfk_2` FOREIGN KEY (`prod_id`) REFERENCES `products` (`prod_id`) ON DELETE CASCADE;

--
-- Constraints for table `details_sales`
--
ALTER TABLE `details_sales`
  ADD CONSTRAINT `details_sales_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `bill_sales` (`sale_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `details_sales_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`prod_id`),
  ADD CONSTRAINT `details_sales_ibfk_3` FOREIGN KEY (`batch_id`) REFERENCES `product_batches` (`batche_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `details_sales_ibfk_4` FOREIGN KEY (`pmt_id`) REFERENCES `promotions` (`pmt_id`) ON DELETE SET NULL;

--
-- Constraints for table `detail_purchases`
--
ALTER TABLE `detail_purchases`
  ADD CONSTRAINT `detail_purchases_ibfk_1` FOREIGN KEY (`purchases_id`) REFERENCES `bill_purchases` (`purchases_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `detail_purchases_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`prod_id`);

--
-- Constraints for table `districts`
--
ALTER TABLE `districts`
  ADD CONSTRAINT `districts_ibfk_1` FOREIGN KEY (`prov_id`) REFERENCES `provinces` (`prov_id`) ON UPDATE CASCADE;

--
-- Constraints for table `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `expenses_ibfk_1` FOREIGN KEY (`exp_cate_id`) REFERENCES `expense_categories` (`exp_cate_id`),
  ADD CONSTRAINT `expenses_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`type_id`) REFERENCES `prod_types` (`type_id`) ON UPDATE CASCADE;

--
-- Constraints for table `product_batches`
--
ALTER TABLE `product_batches`
  ADD CONSTRAINT `product_batches_ibfk_1` FOREIGN KEY (`detail_purchase_id`) REFERENCES `detail_purchases` (`dlt_purchases_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `product_batches_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`prod_id`) ON DELETE CASCADE;

--
-- Constraints for table `promotion_items`
--
ALTER TABLE `promotion_items`
  ADD CONSTRAINT `promotion_items_ibfk_1` FOREIGN KEY (`pmt_id`) REFERENCES `promotions` (`pmt_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `promotion_items_ibfk_2` FOREIGN KEY (`prod_id`) REFERENCES `products` (`prod_id`) ON DELETE CASCADE;

--
-- Constraints for table `stocks`
--
ALTER TABLE `stocks`
  ADD CONSTRAINT `stocks_ibfk_1` FOREIGN KEY (`prod_id`) REFERENCES `products` (`prod_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD CONSTRAINT `stock_movements_ibfk_1` FOREIGN KEY (`prod_id`) REFERENCES `products` (`prod_id`) ON DELETE CASCADE;

--
-- Constraints for table `subdistricts`
--
ALTER TABLE `subdistricts`
  ADD CONSTRAINT `subdistricts_ibfk_1` FOREIGN KEY (`dist_id`) REFERENCES `districts` (`dist_id`) ON UPDATE CASCADE;

--
-- Constraints for table `supplers`
--
ALTER TABLE `supplers`
  ADD CONSTRAINT `supplers_ibfk_1` FOREIGN KEY (`sp_address`) REFERENCES `addresses` (`address_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`address_id`) REFERENCES `addresses` (`address_id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
