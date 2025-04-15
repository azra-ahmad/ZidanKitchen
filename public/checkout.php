<?php
session_start();
include '../config/db.php';
include '../config/functions.php';

// Validate session and cart
if (!isset($_SESSION['customer_id']) || !isset($_SESSION['meja_id']) || empty($_SESSION['keranjang'])) {
    header("Location: menu.php");
    exit;
}

$meja_id = $_SESSION['meja_id'];
$customer_id = $_SESSION['customer_id'];
$errors = [];

// Ambil data menu
$menu_ids = implode(',', array_column($_SESSION['keranjang'], 'menu_id'));
$menu_query = "SELECT * FROM menu WHERE menu_id IN ($menu_ids)";
$menu_result = $conn->query($menu_query);
$menu_data = [];
while ($row = $menu_result->fetch_assoc()) {
    $menu_data[$row['menu_id']] = $row;
}

// Ambil promo aktif
$promos = getActivePromos($conn);

// Hitung total untuk display
$total_display = 0;
$cart_items = [];
foreach ($_SESSION['keranjang'] as $item) {
    if (isset($menu_data[$item['menu_id']])) {
        $menu_item = $menu_data[$item['menu_id']];
        $harga_promo = getItemPrice($item['menu_id'], $_SESSION['keranjang'], $menu_data, $promos);
        $subtotal = $harga_promo * $item['jumlah'];
        $total_display += $subtotal;

        $promo_type = null;
        $promo_title = null;
        $discount = getMenuDiscount($item['menu_id'], $promos);
        if ($discount > 0) {
            $promo_type = 'discount';
            foreach ($promos as $promo) {
                if ($promo['promo_type'] === 'discount' && in_array($item['menu_id'], $promo['menu_ids'])) {
                    $promo_title = $promo['title'];
                    break;
                }
            }
        } else {
            foreach ($promos as $promo) {
                if ($promo['promo_type'] === 'bundle' && in_array($item['menu_id'], $promo['menu_ids']) && checkBundlePromo($_SESSION['keranjang'], $promo)) {
                    $promo_type = 'bundle';
                    $promo_title = $promo['title'];
                    break;
                }
            }
        }

        $cart_items[] = [
            'menu_id' => $item['menu_id'],
            'nama_menu' => $menu_item['nama_menu'],
            'harga_asli' => $menu_item['harga'],
            'harga_promo' => $harga_promo,
            'promo_type' => $promo_type,
            'promo_title' => $promo_title,
            'jumlah' => $item['jumlah'],
            'subtotal' => $subtotal
        ];
    }
}

// Proses checkout jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_checkout'])) {
    if (!isset($_POST['metode_pembayaran']) || empty($_POST['metode_pembayaran'])) {
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
                (meja_id, customer_id, total_harga, metode_pembayaran, status, created_at) 
                VALUES (?, ?, 0, ?, 'pending', NOW())
            ");
            $stmt_order->bind_param("iis", $meja_id, $customer_id, $metode_pembayaran);
            $stmt_order->execute();
            $order_id = $conn->insert_id;

            // 2. Process each cart item
            foreach ($_SESSION['keranjang'] as $item) {
                $menu_item = $menu_data[$item['menu_id']];
                $harga = getItemPrice($item['menu_id'], $_SESSION['keranjang'], $menu_data, $promos);
                $jumlah = $item['jumlah'];
                $subtotal = $harga * $jumlah;
                $total_harga += $subtotal;

                // Insert order item
                $stmt_item = $conn->prepare("
                    INSERT INTO order_items 
                    (order_id, menu_id, jumlah, subtotal) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt_item->bind_param(
                    "iiid",
                    $order_id,
                    $item['menu_id'],
                    $jumlah,
                    $subtotal
                );
                $stmt_item->execute();
            }

            // 3. Update order total
            $stmt_update = $conn->prepare("
                UPDATE orders SET total_harga = ? WHERE order_id = ?
            ");
            $stmt_update->bind_param("di", $total_harga, $order_id);
            $stmt_update->execute();

            // Commit transaction
            $conn->commit();

            // Clear cart
            unset($_SESSION['keranjang']);

            // Redirect ke Midtrans
            header("Location: payment-midtrans.php?order_id=$order_id&total=$total_harga");
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Terjadi kesalahan sistem: " . $e->getMessage();
        }
    }
}

// Simpan total ke session
$_SESSION['total_harga'] = $total_display;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - ZidanKitchen</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            padding-bottom: 20px;
        }
        .promo-badge {
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 9999px;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header Checkout -->
    <div class="bg-blue-600 py-4 px-4 shadow-sm sticky top-0 z-10">
        <div class="flex items-center justify-center">
            <h1 class="text-xl font-bold text-white">Checkout Pesanan</h1>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-6 max-w-md">
        <!-- Info Meja -->
        <div class="bg-blue-100 rounded-xl p-4 mb-4 shadow-sm border border-blue-200">
            <div class="flex items-center justify-between">
                <span class="font-medium text-blue-800">Nomor Meja</span>
                <span class="font-bold text-lg text-blue-600"><?= htmlspecialchars($meja_id) ?></span>
            </div>
        </div>

        <!-- Daftar Pesanan -->
        <div class="bg-white rounded-xl p-4 mb-4 shadow-sm border border-gray-200">
            <h3 class="font-bold text-gray-800 mb-3">Detail Pesanan</h3>
            <div class="space-y-4">
                <?php foreach ($cart_items as $item): ?>
                    <div class="flex justify-between items-start pb-3 border-b border-gray-100 last:border-0">
                        <div class="flex-1">
                            <div class="flex items-start gap-2">
                                <p class="font-medium text-gray-800"><?= htmlspecialchars($item['nama_menu']) ?></p>
                                <?php if ($item['promo_type']): ?>
                                    <span class="promo-badge bg-yellow-100 text-yellow-800">
                                        <?= htmlspecialchars($item['promo_title']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="mt-1">
                                <?php if ($item['promo_type'] == 'discount'): ?>
                                    <span class="text-green-600 font-medium">Rp <?= number_format($item['harga_promo'], 0, ',', '.') ?></span>
                                    <span class="ml-2 text-xs text-gray-400 line-through">Rp <?= number_format($item['harga_asli'], 0, ',', '.') ?></span>
                                <?php elseif ($item['promo_type'] == 'bundle'): ?>
                                    <span class="text-green-600 font-medium">Rp <?= number_format($item['harga_promo'], 0, ',', '.') ?></span>
                                    <span class="ml-2 text-xs text-gray-500">(Harga Paket)</span>
                                <?php else: ?>
                                    <span class="text-gray-700 font-medium">Rp <?= number_format($item['harga_asli'], 0, ',', '.') ?></span>
                                <?php endif; ?>
                            </div>
                            <p class="text-sm text-gray-500 mt-1">
                                <?= $item['jumlah'] ?>x
                            </p>
                        </div>
                        <p class="font-medium text-gray-800">Rp <?= number_format($item['subtotal'], 0, ',', '.') ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Total Harga -->
        <div class="bg-green-50 rounded-xl p-4 mb-4 shadow-sm border border-green-200">
            <div class="flex justify-between items-center">
                <span class="font-bold text-gray-800">Total Pembayaran</span>
                <span class="font-bold text-green-600 text-xl">Rp <?= number_format($total_display, 0, ',', '.') ?></span>
            </div>
        </div>

        <!-- Error Message (jika ada) -->
        <?php if (!empty($errors)): ?>
            <div class="bg-red-50 border-l-4 border-red-500 p-3 mb-4 rounded-lg">
                <p class="text-red-700 font-medium">⚠️ <?= implode('<br>', $errors) ?></p>
            </div>
        <?php endif; ?>

        <!-- Form Pembayaran -->
        <form method="POST" class="bg-white rounded-xl p-4 shadow-sm border border-gray-200">
            <div class="mb-4">
                <h3 class="font-bold text-gray-800 mb-2">Metode Pembayaran</h3>
                <div class="space-y-2">
                    <label class="flex items-center p-3 bg-gray-100 rounded-lg cursor-pointer hover:bg-gray-200 transition">
                        <input type="radio" name="metode_pembayaran" value="Midtrans" class="form-radio text-blue-600 h-5 w-5" checked>
                        <span class="ml-3 text-gray-700">Midtrans (QRIS, VA, dll.)</span>
                    </label>
                </div>
            </div>
            <button type="submit" name="submit_checkout"
                class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg shadow-md transition duration-200">
                Lanjut ke Pembayaran
            </button>
        </form>

        <!-- Tombol Kembali -->
        <a href="keranjang.php"
            class="block mt-4 text-center py-3 bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold rounded-lg shadow-md transition duration-200">
            Kembali ke Keranjang
        </a>
    </div>
</body>
</html>