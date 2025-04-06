<?php
session_start();
include '../config/db.php';

// Jika belum ada keranjang atau ID meja, redirect ke menu
if (!isset($_SESSION['keranjang']) || count($_SESSION['keranjang']) === 0 || !isset($_SESSION['id_meja'])) {
    header("Location: menu.php");
    exit;
}

$id_meja = $_SESSION['id_meja'];
$errors = [];
$success = false;

// Proses setelah form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Pastikan ini adalah submit dari form checkout, bukan redirect dari keranjang
    if (isset($_POST['submit_checkout'])) {
        if (!isset($_POST['metode_pembayaran']) || empty($_POST['metode_pembayaran'])) {
            $errors[] = "Metode pembayaran belum dipilih.";
        } else {
            $metode_pembayaran = $_POST['metode_pembayaran'];
            $total_harga = 0;

            // Simpan order ke tabel orders
            $sql_order = "INSERT INTO orders (id_meja, total_harga, metode_pembayaran, status, created_at) VALUES (?, ?, ?, 'pending', NOW())";
            $stmt = $conn->prepare($sql_order);
            $stmt->bind_param("ids", $id_meja, $total_harga, $metode_pembayaran);
            $stmt->execute();
            $order_id = $stmt->insert_id;

            // Simpan order_items (dengan Buy2Get1)
            foreach ($_SESSION['keranjang'] as $item) {
                $harga = isset($item['harga_promo']) && $item['harga_promo'] > 0 ? $item['harga_promo'] : $item['harga'];
                $jumlah = $item['jumlah'];

                // --- Logika Buy 2 Get 1 Free ---
                $gratis = intdiv($jumlah, 3); // beli 3 → bayar 2
                $jumlah_bayar = $jumlah - $gratis;
                $subtotal = $harga * $jumlah_bayar;
                $total_harga += $subtotal;

                // Simpan item
                $sql_detail = "INSERT INTO order_items (order_id, id_menu, nama_menu, jumlah, harga, subtotal) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql_detail);
                $stmt->bind_param("iisidd", $order_id, $item['id_menu'], $item['nama_menu'], $jumlah, $harga, $subtotal);
                $stmt->execute();
            }

            // Update total_harga
            $sql_update = "UPDATE orders SET total_harga = ? WHERE id = ?";
            $stmt = $conn->prepare($sql_update);
            $stmt->bind_param("di", $total_harga, $order_id);
            $stmt->execute();

            // Bersihkan keranjang
            unset($_SESSION['keranjang']);
            $success = true;

            // Redirect ke pembayaran
            if ($metode_pembayaran === 'QRIS') {
                header("Location: payment.php?order_id=$order_id");
                exit;
            } else {
                header("Location: success.php");
                exit;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - ZidanKitchen</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Inter untuk typography lebih modern -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Custom color palette */
        .btn-primary {
            background-color: #3B82F6; /* blue-500 */
            color: white;
        }
        .btn-primary:hover {
            background-color: #2563EB; /* blue-600 */
        }
        .btn-warning {
            background-color: #F59E0B; /* yellow-500 */
            color: white;
        }
        .btn-warning:hover {
            background-color: #D97706; /* yellow-600 */
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header Checkout -->
    <div class="bg-blue-500 py-4 px-4 shadow-sm sticky top-0 z-10">
        <div class="flex items-center justify-center">
            <h1 class="text-xl font-bold text-white">Checkout Pesanan</h1>
        </div>
    </div>

    <!-- Main Content -->
    <div class="checkout-container p-4 max-w-md mx-auto">
        <!-- Info Meja -->
        <div class="bg-blue-100 rounded-xl p-4 mb-4 shadow-sm border border-blue-200">
            <div class="flex items-center justify-between">
                <span class="font-medium text-blue-800">Nomor Meja</span>
                <span class="font-bold text-lg text-blue-600"><?= htmlspecialchars((string)$id_meja) ?></span>
            </div>
        </div>

        <!-- Daftar Pesanan -->
        <div class="bg-white rounded-xl p-4 mb-4 shadow-sm border border-blue-100">
            <h3 class="font-bold text-gray-800 mb-3">Detail Pesanan</h3>
            <div class="space-y-3">
                <?php 
                $total = 0;
                foreach ($_SESSION['keranjang'] as $item):
                    $harga = $item['harga_promo'] !== null ? $item['harga_promo'] : $item['harga'];
                    $jumlah = $item['jumlah'];
                    $gratis = intdiv($jumlah, 3);
                    $jumlah_bayar = $jumlah - $gratis;
                    $subtotal = $harga * $jumlah_bayar;
                    $total += $subtotal;
                ?>
                    <div class="flex justify-between items-start pb-3 border-b border-gray-100 last:border-0">
                        <div>
                            <p class="font-medium text-gray-800"><?= htmlspecialchars($item['nama_menu']) ?></p>
                            <p class="text-sm text-gray-500">
                                <?= $jumlah ?>x (<?= $gratis > 0 ? "Gratis $gratis" : "Tidak ada promo" ?>)
                            </p>
                        </div>
                        <p class="font-medium">Rp <?= number_format($subtotal, 0, ',', '.') ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Total Harga -->
        <div class="bg-green-50 rounded-xl p-4 mb-4 shadow-sm border border-green-200">
            <div class="flex justify-between items-center">
                <span class="font-bold text-gray-800">Total Pembayaran</span>
                <span class="font-bold text-green-600 text-xl">Rp <?= number_format($total, 0, ',', '.') ?></span>
            </div>
        </div>

        <!-- Error Message (jika ada) -->
        <?php if (!empty($errors)): ?>
            <div class="bg-red-50 border-l-4 border-red-500 p-3 mb-4 rounded-lg">
                <p class="text-red-700 font-medium">⚠️ <?= implode('<br>', $errors) ?></p>
            </div>
        <?php endif; ?>

        <!-- Form Pembayaran -->
        <form method="POST" class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
            <h3 class="font-bold text-gray-800 mb-3">Metode Pembayaran</h3>
            
            <!-- Pilihan Metode Pembayaran -->
            <div class="space-y-2 mb-4">
                <label class="flex items-center space-x-3 p-3 border border-gray-200 rounded-lg hover:bg-blue-50 hover:border-blue-300 cursor-pointer transition">
                    <input type="radio" name="metode_pembayaran" value="Cash" class="h-5 w-5 text-blue-500 focus:ring-blue-400">
                    <span class="font-medium">Bayar di Kasir (Cash)</span>
                </label>

                <label class="flex items-center space-x-3 p-3 border border-gray-200 rounded-lg hover:bg-blue-50 hover:border-blue-300 cursor-pointer transition">
                    <input type="radio" name="metode_pembayaran" value="QRIS" class="h-5 w-5 text-blue-500 focus:ring-blue-400">
                    <span class="font-medium">QRIS</span>
                </label>

                <label class="flex items-center space-x-3 p-3 border border-gray-200 rounded-lg hover:bg-blue-50 hover:border-blue-300 cursor-pointer transition">
                    <input type="radio" name="metode_pembayaran" value="E-Wallet" class="h-5 w-5 text-blue-500 focus:ring-blue-400">
                    <span class="font-medium">E-Wallet (DANA/OVO/Gopay)</span>
                </label>
            </div>

            <!-- Tombol Submit -->
            <button type="submit" name="submit_checkout" 
                class="w-full py-3 btn-primary font-bold rounded-lg shadow-md transition duration-200 active:scale-95">
                Konfirmasi & Bayar
            </button>
        </form>

        <!-- Tombol Kembali -->
        <a href="keranjang.php" 
            class="block mt-4 text-center py-3 btn-warning font-bold rounded-lg shadow-md transition duration-200 active:scale-95">
            Kembali ke Keranjang
        </a>
    </div>
</body>
</html>
