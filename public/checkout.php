<?php
session_start();
include '../config/db.php';

// Pastikan ada keranjang & ID Meja
if (!isset($_SESSION['keranjang']) || count($_SESSION['keranjang']) === 0 || !isset($_SESSION['id_meja'])) {
    header("Location: menu.php");
    exit;
}

$id_meja = $_SESSION['id_meja'] ?? null;
if (!$id_meja) {
    die("Error: ID meja tidak ditemukan. Silakan ulangi proses pemesanan.");
}

?>


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Checkout - ZidanKitchen</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-lg">
        <h2 class="text-2xl font-bold mb-4 text-center">Konfirmasi Pesanan</h2>

        <!-- Tampilkan ID Meja -->
        <p class="text-center text-gray-700 font-semibold mb-4">ID Meja: <?= htmlspecialchars($_SESSION['id_meja']) ?></p>

        <h3 class="text-lg font-semibold mb-2">Pesanan Anda:</h3>
        <ul class="mb-4">
            <?php 
            $total = 0;
            foreach ($_SESSION['keranjang'] as $item): 
                // Gunakan harga promo jika tersedia, jika tidak pakai harga asli
                $harga_final = $item['harga_promo'] !== null ? $item['harga_promo'] : $item['harga'];
                $subtotal = $harga_final * $item['jumlah'];
                $total += $subtotal;
            ?>
                <li class="flex justify-between border-b py-2">
                    <span><?= htmlspecialchars($item['nama_menu']) ?> (x<?= $item['jumlah'] ?>)</span>
                    <span>Rp <?= number_format($subtotal, 0, ',', '.') ?></span>
                </li>
            <?php endforeach; ?>
        </ul>

        <div class="flex justify-between font-bold text-lg mb-4">
            <span>Total:</span>
            <span class="text-green-600">Rp <?= number_format($total, 0, ',', '.') ?></span>
        </div>

        <form method="POST" action="checkout_process.php">
            <div class="mb-4">
                <label class="block font-semibold">Metode Pembayaran</label>
                <select name="metode_pembayaran" required class="w-full border p-2 rounded">
                    <option value="Cash">Bayar di Kasir</option>
                    <option value="QRIS">QRIS</option>
                    <option value="E-Wallet">E-Wallet</option>
                </select>
            </div>

            <button type="submit" class="w-full bg-blue-500 text-white py-2 rounded hover:bg-blue-600">Konfirmasi Pesanan</button>
        </form>
        <br>
        <a href="keranjang.php" class="block w-full text-center bg-red-500 text-white py-2 rounded hover:bg-red-600">Kembali</a>
    </div>

</body>
</html>

