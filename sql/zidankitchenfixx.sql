-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Apr 15, 2025 at 10:21 PM
-- Server version: 8.0.30
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `zidankitchenfixx`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `username`, `password`, `created_at`) VALUES
(1, 'admin', '$2y$10$e8N7U46aYtNyMDqxORTJu.0BefDiPth2m9TvWDzjJNytUkAJampE6', '2025-03-25 09:47:18'),
(2, 'apuila', '$2y$10$yP2jqJSyBdVLgCrdQNqUj.ZO0GCsQjEtlK19/YdMc0F4H8ovbaDG6', '2025-03-25 10:11:04'),
(3, 'Naura', '$2y$10$yuZsJfw2p7LOJV/jz2WECeq6w5uTIFxau0FfYJLwQQAPhmhdq1sxC', '2025-04-15 18:34:14');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `customer_id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `table_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`customer_id`, `name`, `phone`, `table_id`, `created_at`) VALUES
(1, 'Bruno Mars', '0812-8765-4321', 1, '2025-03-20 15:32:47'),
(2, 'Sasuke', '0813-1234-5678', 1, '2025-03-23 05:44:17'),
(3, 'Ngab Owi', '0812-9876-5432', 1, '2025-03-27 08:01:55'),
(4, 'Nganis', '0813-4567-8901', 1, '2025-04-03 09:09:04'),
(5, 'Nganjar', '0812-3456-7890', 2, '2025-04-09 09:21:32'),
(6, 'Mulyadi', '0813-5678-9012', 2, '2025-03-21 12:08:31'),
(7, 'Chris Evan', '0812-6789-0123', 3, '2025-03-24 12:13:16'),
(8, 'Hai', '0812-3456-7890', 1, '2025-03-28 12:20:23'),
(9, 'Ajra Ganteng', '0813-1234-5678', 1, '2025-04-04 12:24:39'),
(10, 'Furina', '0813-1234-5678', 2, '2025-04-10 04:28:49'),
(11, 'Om Jamal', '0813-4567-8901', 2, '2025-03-22 06:01:37'),
(12, 'Yuli', '0812-3456-7890', 3, '2025-03-25 06:03:08'),
(13, 'Rafli', '0812-1129-4199', 1, '2025-03-29 09:52:34'),
(14, 'Rara Anindita', '0815-5779-8064', 2, '2025-04-05 09:54:02'),
(15, 'Adit', '0838-6497-5825', 1, '2025-04-11 10:20:47'),
(16, 'Mulyono', '0813-5678-9012', 3, '2025-03-26 04:03:32'),
(17, 'Sinta Rahayu', '0815-4512-6431', 2, '2025-03-30 04:31:30'),
(18, 'Jaka Pratama', '0812-3456-7890', 3, '2025-04-06 04:08:49'),
(19, 'Oyo', '0815-4333-2235', 3, '2025-04-12 06:23:41'),
(20, 'Hadi Santoso', '0813-5678-9012', 2, '2025-04-13 08:20:49'),
(26, 'Naura', '087887987025', 2, '2025-04-15 19:14:47'),
(27, 'tes', '12301239', 2, '2025-04-15 22:00:02'),
(28, 'Naura', '09871233', 2, '2025-04-15 22:15:40');

-- --------------------------------------------------------

--
-- Table structure for table `meja`
--

CREATE TABLE `meja` (
  `meja_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `meja`
--

INSERT INTO `meja` (`meja_id`) VALUES
(1),
(2),
(3);

-- --------------------------------------------------------

--
-- Table structure for table `menu`
--

CREATE TABLE `menu` (
  `menu_id` int NOT NULL,
  `nama_menu` varchar(255) NOT NULL,
  `kategori_menu` enum('makanan','minuman','dessert') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `harga` decimal(10,2) NOT NULL,
  `gambar` varchar(255) NOT NULL,
  `model_3d` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `harga_promo` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `menu`
--

INSERT INTO `menu` (`menu_id`, `nama_menu`, `kategori_menu`, `harga`, `gambar`, `model_3d`, `created_at`, `updated_at`, `harga_promo`) VALUES
(1, 'Sushi asli Jepun🍣', 'makanan', '50000.00', 'sushi.png', 'sushi/scene.gltf', '2025-03-22 14:32:46', '2025-04-05 17:47:38', NULL),
(2, 'Birthday Cake', 'dessert', '120000.00', 'birthdayCake.jpeg', 'birthdayCake/scene.gltf', '2025-03-22 17:28:12', '2025-04-13 11:52:24', NULL),
(3, 'Coca Cola', 'minuman', '10000.00', 'cocaCola.jpeg', 'cocaCola/scene.gltf', '2025-03-25 13:03:21', NULL, NULL),
(4, 'Gâteau Basque', 'dessert', '150000.00', 'Gâteau Basque.jpeg', 'gâteau_basque/scene.gltf', '2025-03-26 05:52:03', NULL, NULL),
(5, 'Roasted Chicken Ramen', 'makanan', '49000.00', 'roastedChickenRamenCompress.jpg', NULL, '2025-04-16 02:13:40', NULL, NULL),
(6, 'Kimbap', 'makanan', '30000.00', 'kimbapCompress.jpg', NULL, '2025-04-16 02:17:18', NULL, NULL),
(7, 'Shoyu Ramen', 'makanan', '45000.00', 'shoyuRamenCompress.jpg', NULL, '2025-04-16 02:18:40', NULL, NULL),
(8, 'Chinese Noodles', 'makanan', '47000.00', 'chineseNoodles.jpg', NULL, '2025-04-16 02:20:09', '2025-04-16 02:25:28', NULL),
(9, 'Kopi Susu', 'minuman', '12000.00', 'kopsuCompress.jpg', NULL, '2025-04-16 02:21:57', NULL, NULL),
(10, 'Susu Sapi', 'minuman', '8000.00', 'susuSapiCompress.jpg', NULL, '2025-04-16 02:23:21', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int NOT NULL,
  `meja_id` int NOT NULL,
  `customer_id` int DEFAULT NULL,
  `total_harga` decimal(10,2) NOT NULL,
  `metode_pembayaran` varchar(20) DEFAULT NULL,
  `status` enum('pending','paid','failed','done') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT 'pending',
  `snap_token` varchar(255) DEFAULT NULL,
  `midtrans_order_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `meja_id`, `customer_id`, `total_harga`, `metode_pembayaran`, `status`, `snap_token`, `midtrans_order_id`, `created_at`) VALUES
(1, 1, 1, '150000.00', 'qris', 'failed', 'f7f0afd2-6afd-4f84-8a8e-5f50ae40d836', 'ZK-1-1744376812', '2025-03-20 15:32:59'),
(2, 1, 2, '160000.00', 'gopay', 'done', 'fd47c2a2-e9f7-4597-872d-a985c91eb832', 'ZK-2-1744350501', '2025-03-23 05:44:34'),
(3, 1, 3, '100000.00', 'gopay', 'done', '073d6439-8efb-4a2e-ab24-d5cd1e317a32', 'ZK-3-1744359591', '2025-03-27 08:15:14'),
(4, 1, 4, '10000.00', 'gopay', 'done', '05b8fba5-df13-4572-81e3-71cc7a3d49a3', 'ZK-4-1744362661', '2025-04-03 09:11:01'),
(5, 2, 5, '50000.00', 'gopay', 'done', 'ccaf2923-3a17-49d6-a25e-bfbb14204698', 'ZK-5-1744363301', '2025-04-09 09:21:41'),
(6, 2, 6, '150000.00', 'bank_transfer', 'done', '81e4422c-3beb-447b-9226-af21f9139d8a', 'ZK-6-1744373327', '2025-03-21 12:08:47'),
(7, 3, 7, '460000.00', 'bank_transfer', 'done', '19208f33-febf-4bba-9df8-849d1fe641a1', 'ZK-7-1744373616', '2025-03-24 12:13:36'),
(8, 1, 8, '150000.00', 'gopay', 'done', '833154e8-9f50-456e-9b23-97340cd097f2', 'ZK-8-1744374086', '2025-03-28 12:21:26'),
(9, 1, 9, '160000.00', 'gopay', 'done', '13e59232-3cf8-4fcd-940a-41986e952113', 'ZK-9-1744374421', '2025-04-04 12:27:00'),
(10, 2, 10, '12500.00', 'bank_transfer', 'done', '9ec6c484-237b-42b1-b781-aa6cd3da803c', 'ZK-10-1744432170', '2025-04-10 04:29:30'),
(11, 2, 11, '12500.00', 'qris', 'failed', '323f0bfb-ba7a-4a3c-a6b9-3f5172f05844', 'ZK-11-1744431861', '2025-03-22 04:24:21'),
(12, 3, 12, '112000.00', 'gopay', 'failed', '19542564-eed4-4e49-865d-6d3f2631618a', 'ZK-12-1744435739', '2025-03-25 05:28:59'),
(13, 1, 13, '150000.00', 'bank_transfer', 'failed', 'd902b44e-0b4f-4403-83fc-4822448401ea', 'ZK-13-1744451574', '2025-03-29 09:52:54'),
(14, 2, 14, '170000.00', 'bank_transfer', 'failed', '5e927719-3804-4afb-bec1-30d965b0cb03', 'ZK-14-1744451674', '2025-04-05 09:54:34'),
(15, 1, 15, '150000.00', 'bank_transfer', 'failed', 'aaa07453-bc3a-4286-b578-2d8ee8facb61', 'ZK-15-1744453285', '2025-04-11 10:21:25'),
(16, 3, 16, '124500.00', 'bank_transfer', 'failed', '52211288-782d-48c4-a0a6-f4b4299447c4', 'ZK-16-1744605170', '2025-03-26 04:32:49'),
(17, 2, 17, '112000.00', 'bank_transfer', 'failed', '4e8a8c34-da80-4713-b713-ad08a9ea6550', 'ZK-17-1744605403', '2025-03-30 04:36:43'),
(18, 3, 18, '12500.00', 'qris', 'failed', '4b30803d-6280-43e0-8445-83695bdd620b', 'ZK-18-1744690233', '2025-04-06 04:10:33'),
(19, 3, 19, '10000.00', 'qris', 'failed', '496e0b4b-b43c-442b-b48b-c889d9f9956f', 'ZK-19-1744698306', '2025-04-12 06:24:45'),
(20, 2, 20, '12500.00', 'qris', 'done', '0c0d51aa-1990-4abc-8582-158509c92330', 'ZK-20-1744711191', '2025-04-13 09:59:36'),
(29, 2, 27, '39200.00', 'bank_transfer', 'done', 'ae5cf351-93cb-4a04-a22c-8aa524775523', 'ZK-29-1744755039', '2025-04-15 22:10:24'),
(30, 2, 28, '37100.00', 'bank_transfer', 'done', '368a51ec-f70a-4cd8-8a3c-daeaf3885a61', 'ZK-30-1744755408', '2025-04-15 22:16:48');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `order_item_id` int NOT NULL,
  `order_id` int NOT NULL,
  `menu_id` int NOT NULL,
  `jumlah` int NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`order_item_id`, `order_id`, `menu_id`, `jumlah`, `subtotal`) VALUES
(1, 1, 2, 1, '150000.00'),
(2, 2, 3, 1, '10000.00'),
(3, 2, 4, 1, '150000.00'),
(4, 3, 1, 2, '100000.00'),
(5, 4, 3, 1, '10000.00'),
(6, 5, 1, 1, '50000.00'),
(7, 6, 4, 1, '150000.00'),
(8, 7, 3, 1, '10000.00'),
(9, 7, 4, 2, '300000.00'),
(10, 7, 2, 1, '150000.00'),
(11, 8, 2, 1, '150000.00'),
(12, 9, 3, 1, '10000.00'),
(13, 9, 2, 1, '150000.00'),
(14, 10, 1, 1, '12500.00'),
(15, 11, 1, 1, '12500.00'),
(16, 12, 4, 1, '105000.00'),
(17, 12, 3, 1, '7000.00'),
(18, 13, 2, 1, '150000.00'),
(19, 14, 3, 2, '20000.00'),
(20, 14, 2, 1, '150000.00'),
(21, 15, 2, 1, '150000.00'),
(22, 16, 3, 1, '7000.00'),
(23, 16, 1, 1, '12500.00'),
(24, 16, 4, 1, '105000.00'),
(25, 17, 3, 1, '7000.00'),
(26, 17, 4, 1, '105000.00'),
(27, 18, 1, 1, '12500.00'),
(28, 19, 3, 1, '10000.00'),
(29, 20, 1, 1, '12500.00'),
(43, 29, 5, 1, '39200.00'),
(44, 30, 7, 1, '31500.00'),
(45, 30, 10, 1, '5600.00');

-- --------------------------------------------------------

--
-- Table structure for table `promos`
--

CREATE TABLE `promos` (
  `promo_id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `discount` int DEFAULT NULL,
  `promo_type` enum('bundle','discount') NOT NULL,
  `bundle_price` int DEFAULT NULL,
  `bundle_discount_value` decimal(10,2) DEFAULT NULL,
  `image` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `promos`
--

INSERT INTO `promos` (`promo_id`, `title`, `description`, `discount`, `promo_type`, `bundle_price`, `bundle_discount_value`, `image`, `created_at`, `updated_at`, `start_date`, `end_date`) VALUES
(1, 'Ramadhan Kareem! 🕌', 'Nikmati promo bukber hingga 70% hanya di ZidanKitchen! 🤑😍', 30, 'discount', NULL, NULL, 'promoBukber.jpeg', '2025-04-05 15:02:49', '2025-04-16 04:02:58', '2025-04-04', '2025-05-09'),
(2, 'Eid Mubarak 2025!', 'blablabla', 50, 'discount', NULL, NULL, 'promo_67fecdfe5d313.png', '2025-04-05 15:07:32', '2025-04-16 04:22:06', '2025-03-31', '2025-04-07'),
(3, 'Diskon Sushi 75%', '50? 60? 70? 75‼️', 75, 'discount', NULL, NULL, 'promo_67fece0e659d2.jpg', '2025-04-11 23:56:28', '2025-04-16 04:22:22', '2025-03-25', '2025-04-08'),
(4, 'Paket Makan Malam ', 'Dinner with dinna n nigg', 30, 'bundle', NULL, '30.00', 'promoBundleDinner.jpeg', '2025-04-12 00:01:53', '2025-04-16 04:17:39', '2025-04-11', '2025-04-30'),
(9, 'Japanese Food!', 'Nikmati cita rasa autentik Jepang tanpa harus jauh-jauh ke Tokyo! Zidankitchen hadir dengan promo spesial untuk kamu para pecinta Japanese food.', 20, 'discount', NULL, NULL, 'promoJapaneseFoodCompress.jpg', '2025-04-16 04:59:32', '2025-04-16 05:06:13', '2025-04-16', '2025-04-30');

-- --------------------------------------------------------

--
-- Table structure for table `promo_menu`
--

CREATE TABLE `promo_menu` (
  `promo_menu_id` int NOT NULL,
  `promo_id` int NOT NULL,
  `menu_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `promo_menu`
--

INSERT INTO `promo_menu` (`promo_menu_id`, `promo_id`, `menu_id`) VALUES
(16, 4, 3),
(17, 4, 4),
(18, 1, 7),
(19, 1, 10),
(22, 2, 9),
(23, 3, 1),
(24, 3, 10),
(29, 9, 1),
(30, 9, 5),
(31, 9, 7);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`customer_id`),
  ADD KEY `table_id` (`table_id`);

--
-- Indexes for table `meja`
--
ALTER TABLE `meja`
  ADD PRIMARY KEY (`meja_id`);

--
-- Indexes for table `menu`
--
ALTER TABLE `menu`
  ADD PRIMARY KEY (`menu_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `fk_order_customer` (`customer_id`),
  ADD KEY `fk_order_meja` (`meja_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`order_item_id`),
  ADD KEY `fk_order_item_order` (`order_id`),
  ADD KEY `fk_order_item_menu` (`menu_id`);

--
-- Indexes for table `promos`
--
ALTER TABLE `promos`
  ADD PRIMARY KEY (`promo_id`);

--
-- Indexes for table `promo_menu`
--
ALTER TABLE `promo_menu`
  ADD PRIMARY KEY (`promo_menu_id`),
  ADD KEY `fk_promo_menu_promo` (`promo_id`),
  ADD KEY `fk_promo_menu_menu` (`menu_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `customer_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `meja`
--
ALTER TABLE `meja`
  MODIFY `meja_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `menu`
--
ALTER TABLE `menu`
  MODIFY `menu_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `order_item_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `promos`
--
ALTER TABLE `promos`
  MODIFY `promo_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `promo_menu`
--
ALTER TABLE `promo_menu`
  MODIFY `promo_menu_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `customers`
--
ALTER TABLE `customers`
  ADD CONSTRAINT `fk_customer_meja` FOREIGN KEY (`table_id`) REFERENCES `meja` (`meja_id`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_order_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`),
  ADD CONSTRAINT `fk_order_meja` FOREIGN KEY (`meja_id`) REFERENCES `meja` (`meja_id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_order_item_menu` FOREIGN KEY (`menu_id`) REFERENCES `menu` (`menu_id`),
  ADD CONSTRAINT `fk_order_item_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`);

--
-- Constraints for table `promo_menu`
--
ALTER TABLE `promo_menu`
  ADD CONSTRAINT `fk_promo_menu_menu` FOREIGN KEY (`menu_id`) REFERENCES `menu` (`menu_id`),
  ADD CONSTRAINT `fk_promo_menu_promo` FOREIGN KEY (`promo_id`) REFERENCES `promos` (`promo_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
