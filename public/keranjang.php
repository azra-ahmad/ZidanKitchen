<?php
session_start();
include '../config/db.php'; // Koneksi ke database

// Jika keranjang kosong, set sebagai array kosong
if (!isset($_SESSION['keranjang'])) {
    $_SESSION['keranjang'] = [];
}

// Ambil data menu dan promo
$menu_query = "SELECT 
    m.id_menu, 
    m.nama_menu, 
    m.gambar, 
    m.harga AS harga_asli, 
    p.promo_type, 
    p.discount, 
    p.bundle_price
FROM menu m
LEFT JOIN promos p ON m.kategori_menu = p.category_target 
    AND p.start_date <= CURDATE()
    AND p.end_date >= CURDATE()
    AND p.discount = (
        SELECT MAX(p2.discount)
        FROM promos p2 WHERE p2.category_target = m.kategori_menu
        AND p2.start_date <= CURDATE()
        AND p2.end_date >= CURDATE()
        AND p2.promo_type = 'discount'
    )";

$menu_result = $conn->query($menu_query);
$menu_data = [];

while ($row = $menu_result->fetch_assoc()) {
    if ($row['promo_type'] == 'discount') {
        $row['harga_promo'] = $row['harga_asli'] - ($row['harga_asli'] * $row['discount'] / 100);
    } elseif ($row['promo_type'] == 'bundle') {
        $row['harga_promo'] = $row['bundle_price'];
    } else {
        $row['harga_promo'] = null;
    }

    $menu_data[$row['id_menu']] = $row;
}

// Proses tambah/kurang jumlah di keranjang
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['id_menu']) && isset($_POST['action'])) {
        $id_menu = $_POST['id_menu'];

        foreach ($_SESSION['keranjang'] as $i => $item) {
            if ($item['id_menu'] == $id_menu) {
                if ($_POST['action'] === 'tambah') {
                    $_SESSION['keranjang'][$i]['jumlah']++;
                } elseif ($_POST['action'] === 'kurang') {
                    $_SESSION['keranjang'][$i]['jumlah']--;
                    if ($_SESSION['keranjang'][$i]['jumlah'] <= 0) {
                        array_splice($_SESSION['keranjang'], $i, 1);
                    }
                }
                break;
            }
        }
    }
}

// Validasi item di keranjang
foreach ($_SESSION['keranjang'] as $i => $item) {
    $id_menu = $item['id_menu'];
    if (!isset($menu_data[$id_menu])) {
        array_splice($_SESSION['keranjang'], $i, 1);
    } else {
        $_SESSION['keranjang'][$i]['harga_asli'] = $menu_data[$id_menu]['harga_asli'];
        $_SESSION['keranjang'][$i]['harga_promo'] = $menu_data[$id_menu]['harga_promo'];
        $_SESSION['keranjang'][$i]['gambar'] = $menu_data[$id_menu]['gambar'];
        $_SESSION['keranjang'][$i]['promo_type'] = $menu_data[$id_menu]['promo_type'];
    }
}

$keranjang = $_SESSION['keranjang'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Keranjang - ZidanKitchen</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-blue-50 to-white min-h-screen font-sans">
    <div class="sticky top-0 z-50 bg-white shadow-md px-4 py-4 flex justify-between items-center">
        <h1 class="text-2xl font-bold text-blue-600">Keranjang</h1>
        <a href="menu.php" class="text-blue-500 hover:text-blue-700">← Kembali</a>
    </div>
    <div class="max-w-4xl mx-auto p-6">
        <?php if (count($keranjang) > 0): ?>
            <div class="bg-white rounded-xl shadow-xl p-4 space-y-4 animate-fadeIn">
                <?php
                $total = 0;
                foreach ($keranjang as $item):
                    $harga_final = $item['harga_promo'] ?? $item['harga_asli'];
                    $subtotal = $harga_final * $item['jumlah'];
                    $total += $subtotal;
                ?>
                <div class="flex items-center gap-4 bg-white rounded-lg shadow-sm p-4">
                    <!-- Gambar -->
                    <img src="../assets/images/<?= htmlspecialchars($item['gambar']); ?>" alt="<?= htmlspecialchars($item['nama_menu']); ?>" class="w-20 h-20 object-cover rounded-lg">

                    <!-- Info Produk -->
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold"><?= htmlspecialchars($item['nama_menu']); ?></h3>
                        
                        <?php if (!empty($item['promo_type']) && $item['promo_type'] == 'discount'): ?>
                            <span class="line-through text-red-400">Rp <?= number_format($item['harga_asli'], 0, ',', '.'); ?></span>
                            <span class="text-gray-700 font-semibold">Rp <?= number_format($item['harga_promo'], 0, ',', '.'); ?></span>
                        <?php elseif (!empty($item['promo_type']) && $item['promo_type'] == 'bundle'): ?>
                            <span class="text-green-500 font-bold">Rp <?= number_format($item['harga_promo'], 0, ',', '.'); ?> (Paket)</span>
                        <?php else: ?>
                            <span class="text-gray-800 font-bold">Rp <?= number_format($item['harga_asli'], 0, ',', '.'); ?></span>
                        <?php endif; ?>

                        <!-- Tombol Tambah Kurang -->
                        <div class="flex items-center mt-2 space-x-2">
                            <form method="POST" action="keranjang.php">
                                <input type="hidden" name="id_menu" value="<?= $item['id_menu']; ?>">
                                <input type="hidden" name="action" value="kurang">
                                <button class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600" type="submit">-</button>
                            </form>

                            <span class="text-gray-700 font-semibold"><?= $item['jumlah']; ?></span>

                            <form method="POST" action="keranjang.php">
                                <input type="hidden" name="id_menu" value="<?= $item['id_menu']; ?>">
                                <input type="hidden" name="action" value="tambah">
                                <button class="bg-green-500 text-white px-3 py-1 rounded hover:bg-green-600" type="submit">+</button>
                            </form>
                        </div>
                    </div>

                    <!-- Subtotal -->
                    <div class="text-right">
                        <p class="text-blue-600 font-bold">Rp <?= number_format($subtotal, 0, ',', '.'); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>

                <!-- Total -->
                <div class="flex justify-between items-center mt-4">
                    <h3 class="text-xl font-bold text-gray-700">Total:</h3>
                    <p class="text-2xl font-extrabold text-green-600">Rp <?= number_format($total, 0, ',', '.'); ?></p>
                </div>

                <!-- Checkout -->
                <form method="POST" action="checkout.php">
                    <button type="submit" class="w-full mt-4 bg-yellow-400 hover:bg-yellow-500 text-white font-semibold py-3 rounded-xl shadow-md transition duration-300">
                        Checkout Sekarang
                    </button>
                </form>
            </div>
        <?php else: ?>
            <div class="text-center py-12">
                <h2 class="text-2xl font-semibold text-gray-600">Keranjangmu kosong 😢</h2>
                <p class="text-gray-400 mt-2">Yuk tambahkan menu favoritmu!</p>
                <a href="menu.php" class="mt-6 inline-block bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-full">Lihat Menu</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
