<?php
session_start();
include '../config/db.php';
// Validate session and cart
if (!isset($_SESSION['customer_id']) || !isset($_SESSION['id_meja']) || empty($_SESSION['keranjang'])) {
    header("Location: menu.php");
    exit;
}

$id_meja = $_SESSION['id_meja'];
$customer_id = $_SESSION['customer_id'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_checkout'])) {
    // Validate payment method
    if (empty($_POST['metode_pembayaran'])) {
        $errors[] = "Metode pembayaran belum dipilih.";
    } else {
        $metode_pembayaran = $_POST['metode_pembayaran'];
        $total_harga = 0;

        // Start transaction
        $conn->begin_transaction();

        try {
            // 1. Insert main order
            $stmt_order = $conn->prepare("
                INSERT INTO orders 
                (id_meja, customer_id, total_harga, metode_pembayaran, status, created_at) 
                VALUES (?, ?, 0, ?, 'pending', NOW())
            ");
            $stmt_order->bind_param("iis", $id_meja, $customer_id, $metode_pembayaran);
            $stmt_order->execute();
            $order_id = $conn->insert_id;

            // 2. Process each cart item
            foreach ($_SESSION['keranjang'] as $item) {
                $harga = $item['harga_promo'] > 0 ? $item['harga_promo'] : $item['harga'];
                $jumlah = $item['jumlah'];
                
                // Buy 2 Get 1 logic
                $gratis = intdiv($jumlah, 3);
                $jumlah_dibayar = $jumlah - $gratis;
                $subtotal = $harga * $jumlah_dibayar;
                $total_harga += $subtotal;

                // Insert order item
                $stmt_item = $conn->prepare("
                    INSERT INTO order_items 
                    (order_id, id_menu, nama_menu, jumlah, harga, subtotal) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt_item->bind_param(
                    "iisidd", 
                    $order_id, 
                    $item['id_menu'], 
                    $item['nama_menu'], 
                    $jumlah, 
                    $harga, 
                    $subtotal
                );
                $stmt_item->execute();

                // Insert free items if any
                if ($gratis > 0) {
                    $stmt_free = $conn->prepare("
                        INSERT INTO order_items 
                        (order_id, id_menu, nama_menu, jumlah, harga, subtotal) 
                        VALUES (?, ?, ?, ?, 0, 0)
                    ");
                    $nama_gratis = $item['nama_menu'] . ' (Gratis)';
                    $stmt_free->bind_param(
                        "iisi", 
                        $order_id, 
                        $item['id_menu'], 
                        $nama_gratis, 
                        $gratis
                    );
                    $stmt_free->execute();
                }
            }

            // 3. Update order total
            $stmt_update = $conn->prepare("
                UPDATE orders SET total_harga = ? WHERE id = ?
            ");
            $stmt_update->bind_param("di", $total_harga, $order_id);
            $stmt_update->execute();

            // Commit transaction
            $conn->commit();

            // Clear cart and redirect
            unset($_SESSION['keranjang']);
            
            if ($metode_pembayaran === 'Cash') {
                header("Location: success.php?order_id=$order_id");
            } else {
                header("Location: payment-midtrans.php?order_id=$order_id&total=$total_harga");
            }
            exit;

        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Terjadi kesalahan sistem: " . $e->getMessage();
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
