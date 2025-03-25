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
    <title>Dashboard Admin - ZidanKitchen</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="bg-green-500 text-white p-2 rounded mb-4">
            <?= $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="bg-red-500 text-white p-2 rounded mb-4">
            <?= $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
        </div>
    <?php endif; ?>

    <div class="flex justify-between items-center mb-4">
        <h2 class="text-2xl font-bold">Dashboard Admin</h2>
        <a href="logout.php" class="bg-red-500 text-white px-4 py-2 rounded">Logout</a>
    </div>

    <!-- Statistik -->
    <div class="grid grid-cols-2 gap-4 mb-6">
        <div class="bg-blue-500 text-white p-4 rounded-lg shadow">
            <h3 class="text-lg font-semibold">Total Pendapatan</h3>
            <p class="text-2xl font-bold">Rp <?= number_format($total_pendapatan, 0, ',', '.') ?></p>
        </div>
        <div class="bg-green-500 text-white p-4 rounded-lg shadow">
            <h3 class="text-lg font-semibold">Total Pesanan</h3>
            <p class="text-2xl font-bold"><?= $total_pesanan ?></p>
        </div>
    </div>

    <!-- Pesanan Masuk -->
    <h3 class="text-xl font-bold mb-2">Pesanan Masuk</h3>
    <table class="w-full bg-white rounded-lg shadow-lg">
        <thead>
            <tr class="bg-gray-200">
                <th class="p-2">Antrian ke-</th>
                <th class="p-2">Meja</th>
                <th class="p-2">Total</th>
                <th class="p-2">Metode</th>
                <th class="p-2">Status</th>
                <th class="p-2">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr class="border-b">
                    <td class="p-2 text-center"><?= $row['id']; ?></td>
                    <td class="p-2 text-center"><?= $row['id_meja'] ?></td>
                    <td class="p-2 text-center">Rp <?= number_format($row['total_harga'], 0, ',', '.') ?></td>
                    <td class="p-2 text-center"><?= $row['metode_pembayaran'] ?></td>
                    <td class="p-2 text-center"><?= ucfirst($row['status']) ?></td>
                    <td class="p-2 text-center">
                        <a href="proses_pesanan.php?id=<?= $row['id'] ?>" class="bg-green-500 text-white px-3 py-1 rounded">Selesaikan</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>


    <!-- Riwayat Pesanan -->
    <h3 class="text-xl font-bold mb-2">Pesanan Selesai</h3>
    <table class="w-full bg-white rounded-lg shadow-lg">
        <thead>
            <tr class="bg-gray-200">
                <th class="p-2">Antrian ke-</th>
                <th class="p-2">Meja</th>
                <th class="p-2">Total</th>
                <th class="p-2">Metode</th>
                <th class="p-2">Tanggal</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            while ($row = $completed_orders->fetch_assoc()): ?>
                <tr class="border-b">
                    <td class="p-2 text-center"><?= $row['id'] ?></td>
                    <td class="p-2 text-center"><?= $row['id_meja'] ?></td>
                    <td class="p-2 text-center">Rp <?= number_format($row['total_harga'], 0, ',', '.') ?></td>
                    <td class="p-2 text-center"><?= $row['metode_pembayaran'] ?></td>
                    <td class="p-2 text-center"><?= date('d-m-Y H:i:s', strtotime($row['created_at'])) ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <!-- Pesanan Gagal -->
    <h3 class="text-xl font-bold mb-2">Pesanan Gagal</h3>
    <table class="w-full bg-white rounded-lg shadow-lg mb-6">
        <thead>
            <tr class="bg-gray-200">
                <th class="p-2">Antrian ke-</th>
                <th class="p-2">Meja</th>
                <th class="p-2">Total</th>
                <th class="p-2">Metode</th>
                <th class="p-2">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            while ($row = $failed_orders->fetch_assoc()): 
            ?>
                <tr class="border-b">
                    <td class="p-2 text-center"><?= $row['id'] ?></td>
                    <td class="p-2 text-center"><?= $row['id_meja'] ?></td>
                    <td class="p-2 text-center">Rp <?= number_format($row['total_harga'], 0, ',', '.') ?></td>
                    <td class="p-2 text-center"><?= $row['metode_pembayaran'] ?></td>
                    <td class="p-2 text-center"><?= ucfirst($row['status']) ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</body>
</html>
