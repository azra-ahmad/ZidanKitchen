<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}

include '../config/db.php';
date_default_timezone_set('Asia/Jakarta');

// Ambil data pesanan pending & paid, urutkan dari yang paling lama ke terbaru 
$result = $conn->query("
    SELECT * FROM orders 
    WHERE status IN ('paid', 'pending') 
    ORDER BY id ASC
");


// Ambil data pesanan selesai, urutkan dari yang paling lama ke terbaru
$completed_orders = $conn->query("SELECT * FROM orders WHERE status='done' ORDER BY created_at ASC");

// Ambil data pesanan gagal, urutkan dari yang paling lama ke terbaru
$failed_orders = $conn->query("SELECT * FROM orders WHERE status='failed' ORDER BY created_at ASC");

// Hitung total pendapatan
$total_pendapatan = $conn->query("SELECT IFNULL(SUM(total_harga), 0) AS total FROM orders WHERE status='done'")->fetch_assoc()['total'];
$total_pesanan = $conn->query("SELECT COUNT(id) AS total FROM orders")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - ZidanKitchen</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-orange-50 to-red-100 min-h-screen flex">
    <!-- Sidebar -->
    <div class="h-screen w-64 bg-gradient-to-b from-orange-600 to-yellow-900 text-white p-5 shadow-lg fixed flex flex-col">
        <h2 class="text-2xl font-bold text-center mb-6">Admin Panel</h2>
        <nav class="flex-1">
            <ul>
                <li class="mb-4">
                    <a href="dashboard.php" class="block p-3 rounded-lg bg-orange-500">Dashboard</a>
                </li>
                <li class="mb-4">
                    <a href="menu.php" class="block p-3 rounded-lg hover:bg-orange-500">Kelola Menu</a>
                </li>
                <li class="mb-4">
                    <a href="orders.php" class="block p-3 rounded-lg hover:bg-orange-500">Pesanan</a>
                </li>
                <li class="mb-4">
                    <a href="settings.php" class="block p-3 rounded-lg hover:bg-orange-500">Pengaturan</a>
                </li>
            </ul>
        </nav>
        <a href="logout.php" class="block p-3 rounded-lg bg-red-600 hover:bg-red-700 text-center">Logout</a>
    </div>

    <!-- Content -->
    <div class="flex-1 ml-64 p-6">
        <!-- success msg -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="bg-green-500 text-white p-3 rounded-lg shadow-md mb-4">
                <?= $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
            </div>
            <?php endif; ?>
            
        <!-- Error msg -->
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="bg-red-500 text-white p-3 rounded-lg shadow-md mb-4">
                <?= $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>
        
        <!-- Title -->
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-3xl font-bold text-orange-600">Dashboard Admin Zidan Kitchen</h2>
            <a href="logout.php" class="bg-gradient-to-r from-orange-500 to-red-500 text-white px-5 py-2 rounded-lg shadow-md hover:opacity-90 transition">Logout</a>
        </div>

        <!-- Statistik -->
        <div class="grid grid-cols-2 gap-6 mb-8">
            <div class="bg-orange-600 text-white p-6 rounded-lg shadow-lg">
                <h3 class="text-lg font-semibold">Total Pendapatan</h3>
                <p class="text-3xl font-bold">Rp <?= number_format($total_pendapatan, 0, ',', '.') ?></p>
            </div>
            <div class="bg-yellow-900 text-white p-6 rounded-lg shadow-lg">
                <h3 class="text-lg font-semibold">Total Pesanan</h3>
                <p class="text-3xl font-bold"> <?= $total_pesanan ?> </p>
            </div>
        </div>

        <!-- Pesanan Masuk -->
        <h3 class="text-2xl font-bold text-orange-800 mb-3">Pesanan Masuk</h3>
        <table class="w-full bg-white rounded-lg shadow-lg overflow-hidden">
            <thead>
                <tr class="bg-orange-500 text-white">
                    <th class="p-3">Antrian ke-</th>
                    <th class="p-3">Meja</th>
                    <th class="p-3">Total</th>
                    <th class="p-3">Metode</th>
                    <th class="p-3">Status</th>
                    <th class="p-3">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr class="border-b hover:bg-orange-100">
                        <td class="p-3 text-center"> <?= $row['id']; ?> </td>
                        <td class="p-3 text-center"> <?= $row['id_meja'] ?> </td>
                        <td class="p-3 text-center">Rp <?= number_format($row['total_harga'], 0, ',', '.') ?> </td>
                        <td class="p-3 text-center"> <?= $row['metode_pembayaran'] ?> </td>
                        <td class="p-3 text-center"> <?= ucfirst($row['status']) ?> </td>
                        <td class="p-3 text-center">
                            <a href="proses_pesanan.php?id=<?= $row['id'] ?>" class="bg-yellow-600 text-white px-4 py-2 rounded-lg shadow-md hover:opacity-80">Selesaikan</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <!-- Riwayat Pesanan -->
        <h3 class="text-2xl font-bold text-orange-800 mt-6 mb-3">Pesanan Selesai</h3>
        <table class="w-full bg-white rounded-lg shadow-lg overflow-hidden">
            <thead>
                <tr class="bg-orange-500 text-white">
                    <th class="p-3">Antrian ke-</th>
                    <th class="p-3">Meja</th>
                    <th class="p-3">Total</th>
                    <th class="p-3">Metode</th>
                    <th class="p-3">Tanggal</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $completed_orders->fetch_assoc()): ?>
                    <tr class="border-b hover:bg-orange-100">
                        <td class="p-3 text-center"> <?= $row['id'] ?> </td>
                        <td class="p-3 text-center"> <?= $row['id_meja'] ?> </td>
                        <td class="p-3 text-center">Rp <?= number_format($row['total_harga'], 0, ',', '.') ?> </td>
                        <td class="p-3 text-center"> <?= $row['metode_pembayaran'] ?> </td>
                        <td class="p-3 text-center"> <?= date('d-m-Y H:i:s', strtotime($row['created_at'])) ?> </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <!-- Pesanan Gagal -->
        <h3 class="text-2xl font-bold text-orange-800 mt-6 mb-3">Pesanan Gagal</h3>
        <table class="w-full bg-white rounded-lg shadow-lg overflow-hidden">
            <thead>
                <tr class="bg-orange-500 text-white">
                    <th class="p-3">Antrian ke-</th>
                    <th class="p-3">Meja</th>
                    <th class="p-3">Total</th>
                    <th class="p-3">Metode</th>
                    <th class="p-3">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $failed_orders->fetch_assoc()): ?>
                    <tr class="border-b hover:bg-orange-100">
                        <td class="p-3 text-center"> <?= $row['id'] ?> </td>
                        <td class="p-3 text-center"> <?= $row['id_meja'] ?> </td>
                        <td class="p-3 text-center">Rp <?= number_format($row['total_harga'], 0, ',', '.') ?> </td>
                        <td class="p-3 text-center"> <?= $row['metode_pembayaran'] ?> </td>
                        <td class="p-3 text-center"> <?= ucfirst($row['status']) ?> </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>