-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Apr 05, 2025 at 08:13 AM
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
-- Database: `zidankitchen`
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
(1, 'admin', '$2y$10$e8N7U46aYtNyMDqxORTJu.0BefDiPth2m9TvWDzjJNytUkAJampE6', '2025-03-25 16:47:18'),
(2, 'apuila', '$2y$10$yP2jqJSyBdVLgCrdQNqUj.ZO0GCsQjEtlK19/YdMc0F4H8ovbaDG6', '2025-03-25 17:11:04');

-- --------------------------------------------------------

--
-- Table structure for table `meja`
--

CREATE TABLE `meja` (
  `id_meja` int NOT NULL,
  `kode_otp` varchar(6) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `status` enum('tersedia','digunakan') DEFAULT 'tersedia',
  `otp_expiry` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `meja`
--

INSERT INTO `meja` (`id_meja`, `kode_otp`, `status`, `otp_expiry`) VALUES
(1, '506234', 'digunakan', '2025-04-05 07:19:54'),
(2, '524462', 'tersedia', '2025-04-02 14:06:48'),
(3, '453496', 'tersedia', '2025-04-03 23:42:46');

-- --------------------------------------------------------

--
-- Table structure for table `menu`
--

CREATE TABLE `menu` (
  `id_menu` int NOT NULL,
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

INSERT INTO `menu` (`id_menu`, `nama_menu`, `kategori_menu`, `harga`, `gambar`, `model_3d`, `created_at`, `updated_at`, `harga_promo`) VALUES
(1, 'sushis', 'makanan', '50000.00', 'sushi.png', 'sushi/scene.gltf', '2025-03-22 14:32:46', '2025-04-03 18:26:38', NULL),
(2, 'Birthday Cake', 'dessert', '150000.00', 'birthdayCake.jpeg', 'birthdayCake/scene.gltf', '2025-03-22 17:28:12', '2025-03-22 17:30:46', NULL),
(3, 'Coca Cola', 'minuman', '10000.00', 'cocaCola.jpeg', 'cocaCola/scene.gltf', '2025-03-25 13:03:21', NULL, NULL),
(4, 'Gâteau Basque', 'dessert', '150000.00', 'Gâteau Basque.jpeg', 'gâteau_basque/scene.gltf', '2025-03-26 05:52:03', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int NOT NULL,
  `id_meja` int NOT NULL,
  `total_harga` decimal(10,2) NOT NULL,
  `metode_pembayaran` enum('Cash','QRIS','E-Wallet') NOT NULL,
  `status` enum('pending','paid','failed','done') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `id_meja`, `total_harga`, `metode_pembayaran`, `status`, `created_at`) VALUES
(1, 1, '210000.00', 'Cash', 'pending', '2025-03-25 11:47:54'),
(2, 1, '210000.00', 'QRIS', 'done', '2025-03-25 11:48:16'),
(3, 1, '100000.00', 'QRIS', 'paid', '2025-03-25 12:17:46'),
(4, 1, '40000.00', 'Cash', 'paid', '2025-03-25 12:20:13'),
(5, 1, '40000.00', 'QRIS', 'failed', '2025-03-25 12:22:21'),
(6, 2, '40000.00', 'QRIS', 'done', '2025-03-25 12:37:55'),
(7, 3, '200000.00', 'E-Wallet', 'pending', '2025-03-26 00:42:30'),
(8, 3, '350000.00', 'QRIS', 'done', '2025-03-26 01:51:35');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int NOT NULL,
  `order_id` int NOT NULL,
  `id_menu` int NOT NULL,
  `nama_menu` varchar(255) NOT NULL,
  `jumlah` int NOT NULL,
  `harga` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `id_menu`, `nama_menu`, `jumlah`, `harga`, `subtotal`) VALUES
(1, 1, 1, 'sushi', 1, '50000.00', '50000.00'),
(2, 1, 3, 'Coca Cola', 1, '10000.00', '10000.00'),
(3, 1, 2, 'Birthday Cake', 1, '150000.00', '150000.00'),
(4, 2, 1, 'sushi', 1, '50000.00', '50000.00'),
(5, 2, 3, 'Coca Cola', 1, '10000.00', '10000.00'),
(6, 2, 2, 'Birthday Cake', 1, '150000.00', '150000.00'),
(7, 3, 1, 'sushi', 2, '50000.00', '100000.00'),
(8, 4, 1, 'sushi', 1, '40000.00', '40000.00'),
(9, 5, 1, 'sushi', 1, '40000.00', '40000.00'),
(10, 6, 1, 'sushi', 1, '40000.00', '40000.00'),
(11, 7, 4, 'Gâteau Basque', 1, '150000.00', '150000.00'),
(12, 7, 1, 'sushi', 1, '40000.00', '40000.00'),
(13, 7, 3, 'Coca Cola', 1, '10000.00', '10000.00'),
(14, 8, 1, 'sushi', 1, '40000.00', '40000.00'),
(15, 8, 3, 'Coca Cola', 1, '10000.00', '10000.00'),
(16, 8, 4, 'Gâteau Basque', 2, '150000.00', '300000.00');

-- --------------------------------------------------------

--
-- Table structure for table `promos`
--

CREATE TABLE `promos` (
  `id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `discount` int DEFAULT NULL,
  `promo_type` enum('discount','buy2get1','bundle') NOT NULL,
  `category_target` enum('makanan','minuman','dessert') DEFAULT NULL,
  `bundle_price` int DEFAULT NULL,
  `image` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `promos`
--

INSERT INTO `promos` (`id`, `title`, `description`, `discount`, `promo_type`, `category_target`, `bundle_price`, `image`, `created_at`, `updated_at`, `start_date`, `end_date`) VALUES
(3, 'Ramadhan Kareem! 🕌', 'Nikmati promo bukber hingga 70% hanya di ZidanKitchen! 🤑😍', 30, 'discount', 'minuman', 0, 'promoBukber.jpeg', '2025-04-05 15:02:49', '2025-04-05 15:03:31', '2025-04-04', '2025-05-09'),
(4, 'Eid Mubarak 2025!', 'blablabla', 50, 'discount', 'minuman', 0, 'promoEid.png', '2025-04-05 15:07:32', NULL, '2025-03-31', '2025-04-07');

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
-- Indexes for table `meja`
--
ALTER TABLE `meja`
  ADD PRIMARY KEY (`id_meja`);

--
-- Indexes for table `menu`
--
ALTER TABLE `menu`
  ADD PRIMARY KEY (`id_menu`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `promos`
--
ALTER TABLE `promos`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `meja`
--
ALTER TABLE `meja`
  MODIFY `id_meja` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `menu`
--
ALTER TABLE `menu`
  MODIFY `id_menu` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `promos`
--
ALTER TABLE `promos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
