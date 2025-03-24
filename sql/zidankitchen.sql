-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Mar 24, 2025 at 07:10 PM
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
(1, '322529', 'digunakan', '2025-03-24 16:58:27'),
(2, '613035', 'tersedia', '2025-03-20 09:59:40'),
(3, NULL, 'tersedia', NULL);

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
(1, 'sushi', 'makanan', '50000.00', 'sushi.png', 'sushi/scene.gltf', '2025-03-22 14:32:46', NULL, NULL),
(2, 'Birthday Cake', 'dessert', '150000.00', 'birthdayCake.jpeg', 'birthdayCake/scene.gltf', '2025-03-22 17:28:12', '2025-03-22 17:30:46', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `pesanan`
--

CREATE TABLE `pesanan` (
  `id_pesanan` int NOT NULL,
  `id_meja` int NOT NULL,
  `id_menu` int NOT NULL,
  `jumlah` int NOT NULL,
  `total_harga` decimal(10,2) NOT NULL,
  `status` enum('pending','diproses','selesai') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `promos`
--

CREATE TABLE `promos` (
  `id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `valid_until` date DEFAULT NULL,
  `discount` int DEFAULT NULL,
  `promo_type` enum('discount','buy2get1','bundle') NOT NULL,
  `category_target` enum('makanan','minuman','dessert') DEFAULT NULL,
  `bundle_price` int DEFAULT NULL,
  `image` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `promos`
--

INSERT INTO `promos` (`id`, `title`, `description`, `valid_until`, `discount`, `promo_type`, `category_target`, `bundle_price`, `image`, `created_at`, `updated_at`) VALUES
(1, 'Promo Special Ramadhan!', 'Bukber lebih berwarna di ZidhanKitchen', '2025-03-31', 20, 'discount', 'makanan', NULL, 'promoBukber.jpeg', '2025-03-22 17:39:39', NULL);

--
-- Indexes for dumped tables
--

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
-- Indexes for table `pesanan`
--
ALTER TABLE `pesanan`
  ADD PRIMARY KEY (`id_pesanan`),
  ADD KEY `id_meja` (`id_meja`),
  ADD KEY `id_menu` (`id_menu`);

--
-- Indexes for table `promos`
--
ALTER TABLE `promos`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `meja`
--
ALTER TABLE `meja`
  MODIFY `id_meja` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `menu`
--
ALTER TABLE `menu`
  MODIFY `id_menu` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `pesanan`
--
ALTER TABLE `pesanan`
  MODIFY `id_pesanan` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `promos`
--
ALTER TABLE `promos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `pesanan`
--
ALTER TABLE `pesanan`
  ADD CONSTRAINT `pesanan_ibfk_1` FOREIGN KEY (`id_meja`) REFERENCES `meja` (`id_meja`),
  ADD CONSTRAINT `pesanan_ibfk_2` FOREIGN KEY (`id_menu`) REFERENCES `menu` (`id_menu`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
