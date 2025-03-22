<?php
include '../config/db.php';

$menu_query = "SELECT 
    m.*, 
    p.promo_type, 
    p.discount, 
    p.bundle_price
FROM menu m 
LEFT JOIN promos p ON m.kategori_menu = p.category_target 
    AND p.valid_until >= CURDATE()";

$menus = $conn->query($menu_query);

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu - ZidanKitchen</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script type="module" src="https://unpkg.com/@google/model-viewer"></script>
</head>
<body class="bg-gray-100">
    <div class="max-w-6xl mx-auto p-6">
        <h1 class="text-3xl font-bold text-center text-gray-800 mb-6">Menu ZidanKitchen</h1>
        
        <div class="grid md:grid-cols-3 sm:grid-cols-2 grid-cols-1 gap-6">
            <?php while ($menu = $menus->fetch_assoc()): ?>
                <div class="relative bg-white p-4 rounded-xl shadow-lg hover:shadow-xl transition-all">
                    <img src="../assets/images/<?= $menu['gambar']; ?>" alt="<?= $menu['nama_menu']; ?>" class="w-full h-48 object-cover rounded-lg">
                    <h2 class="text-xl font-semibold mt-4"> <?= $menu['nama_menu']; ?> </h2>
                    <p class="text-gray-500 text-sm">Kategori: <?= $menu['kategori_menu']; ?></p>

                    <?php if ($menu['promo_type'] == 'discount'): ?>
                        <?php $harga_promo = $menu['harga'] - ($menu['harga'] * $menu['discount'] / 100); ?>
                        <p class="text-lg font-bold text-red-500">
                            Rp <?= number_format($harga_promo, 0, ',', '.'); ?>
                        </p>
                        <p class="text-gray-400 line-through text-sm">
                            Rp <?= number_format($menu['harga'], 0, ',', '.'); ?>
                        </p>
                    <?php elseif ($menu['promo_type'] == 'bundle'): ?>
                        <p class="text-lg font-bold text-green-500">
                            Paket Promo: Rp <?= number_format($menu['bundle_price'], 0, ',', '.'); ?>
                        </p>
                    <?php else: ?>
                        <p class="text-lg font-bold text-gray-700">
                            Rp <?= number_format($menu['harga'], 0, ',', '.'); ?>
                        </p>
                    <?php endif; ?>

                    <button class="mt-4 w-full bg-blue-500 text-white py-2 rounded-lg hover:bg-blue-600 transition">
                        Tambah ke Keranjang
                    </button>
                </div>
            <?php endwhile; ?>

        </div>
    </div>
</body>
</html>
