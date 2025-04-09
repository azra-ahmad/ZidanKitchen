<?php
include '../config/db.php';
session_start();
if (!isset($_SESSION['id_meja']) || !isset($_SESSION['customer_id'])) {
    header("Location: order.php?table=" . $_SESSION['id_meja'] ?? '');
    exit;
}

// Ambil daftar kategori
$kategori_result = $conn->query("SELECT DISTINCT kategori_menu FROM menu");
$kategori_list = [];
while ($row = $kategori_result->fetch_assoc()) {
    $kategori_list[] = $row['kategori_menu'];
}

$menu_query = "
SELECT 
    m.*, 
    p.promo_type, 
    p.discount, 
    p.bundle_price
FROM menu m
LEFT JOIN promos p
    ON  m.kategori_menu = p.category_target
    AND p.start_date <= CURDATE()
    AND p.end_date >= CURDATE()
    AND p.discount = (
        SELECT MAX(p2.discount)
        FROM promos p2 WHERE p2.category_target = m.kategori_menu
        AND p2.start_date <= CURDATE()
        AND p2.end_date >= CURDATE()
        AND p2.promo_type = 'discount'
    )
";

$menus = $conn->query($menu_query);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0 viewport-fit=cover" />
    <title>Menu - ZidanKitchen</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script type="module" src="https://unpkg.com/@google/model-viewer"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        @keyframes fadeIn {
            0% {
                opacity: 0;
                transform: translateY(10px);
            }

            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fadeIn {
            animation: fadeIn 0.4s ease-out forwards;
        }

        .card-hover {
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .category-active {
            background: linear-gradient(135deg, #3B82F6 0%, #1D4ED8 100%);
            color: white;
            box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.3);
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8fafc;
        }

        .menu-item {
            animation-delay: calc(var(--order) * 0.1s);
        }

        .floating-cart {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .floating-cart:hover {
            transform: scale(1.05);
        }

        /* Custom scrollbar for categories */
        .category-scroll {
            scrollbar-width: none;
            /* Firefox */
            -ms-overflow-style: none;
            /* IE/Edge */
        }

        .category-scroll::-webkit-scrollbar {
            display: none;
            /* Chrome/Safari/Opera */
        }

        /* Smooth scrolling for categories */
        .category-scroll {
            scroll-behavior: smooth;
        }
    </style>
</head>

<body class="min-h-screen m-0 p-0 pb-16">

    <!-- Header -->
    <header class="sticky top-0 z-50 bg-white shadow-sm px-4 py-3 flex justify-between items-center border-b border-gray-100">
        <div class="flex items-center space-x-2">
            <div class="w-full h-full rounded-lg flex items-center justify-center overflow-hidden">
                <img src="../assets/images/logo_biru.png" alt="Logo" class="w-20 h-20 object-contain">
            </div>
            <h1 class="text-3xl font-bold text-gray-800">Zidan<span class="text-blue-600">Kitchen</span></h1>
        </div>

        <a href="keranjang.php" class="relative">
            <div class="p-2 rounded-full bg-gray-100 hover:bg-gray-200 transition">
                <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"></path>
                </svg>
                <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs px-1.5 py-0.5 rounded-full">0</span>
            </div>
        </a>
    </header>

    <!-- Filter Kategori with Horizontal Scroll -->
    <div class="sticky top-16 z-40 bg-white/90 backdrop-blur-sm px-6 py-3 border-b border-gray-100">
        <div class="category-scroll flex space-x-3 overflow-x-auto pb-3 -mx-6 px-6">
            <button class="px-5 py-2 rounded-full category-active text-sm font-medium whitespace-nowrap transition-all duration-300 flex-shrink-0">Semua Menu</button>
            <button class="px-5 py-2 bg-white text-gray-600 border border-gray-200 rounded-full hover:bg-gray-50 text-sm font-medium whitespace-nowrap transition-all duration-300 flex-shrink-0">Makanan Berat</button>
            <button class="px-5 py-2 bg-white text-gray-600 border border-gray-200 rounded-full hover:bg-gray-50 text-sm font-medium whitespace-nowrap transition-all duration-300 flex-shrink-0">Makanan Ringan</button>
            <button class="px-5 py-2 bg-white text-gray-600 border border-gray-200 rounded-full hover:bg-gray-50 text-sm font-medium whitespace-nowrap transition-all duration-300 flex-shrink-0">Minuman Dingin</button>
            <button class="px-5 py-2 bg-white text-gray-600 border border-gray-200 rounded-full hover:bg-gray-50 text-sm font-medium whitespace-nowrap transition-all duration-300 flex-shrink-0">Minuman Panas</button>
            <button class="px-5 py-2 bg-white text-gray-600 border border-gray-200 rounded-full hover:bg-gray-50 text-sm font-medium whitespace-nowrap transition-all duration-300 flex-shrink-0">Snack</button>
            <button class="px-5 py-2 bg-white text-gray-600 border border-gray-200 rounded-full hover:bg-gray-50 text-sm font-medium whitespace-nowrap transition-all duration-300 flex-shrink-0">Promo Spesial</button>
            <button class="px-5 py-2 bg-white text-gray-600 border border-gray-200 rounded-full hover:bg-gray-50 text-sm font-medium whitespace-nowrap transition-all duration-300 flex-shrink-0">Paket Keluarga</button>
        </div>
    </div>

    <!-- Menu -->
    <main class="container mx-auto px-4 py-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <?php while ($menu = $menus->fetch_assoc()): ?>
                <div class="bg-white rounded-xl overflow-hidden shadow-md card-hover animate-fadeIn menu-item" style="--order: <?= $menu['id_menu'] % 10 ?>">
                    <div class="relative">
                        <div class="h-48 w-full bg-gradient-to-br from-gray-100 to-gray-200 overflow-hidden">
                            <img src="../assets/images/<?= $menu['gambar']; ?>" alt="<?= $menu['nama_menu']; ?>" class="w-full h-full object-cover transition duration-500 hover:scale-105">
                        </div>

                        <?php if (!empty($menu['model_3d'])): ?>
                            <div class="absolute bottom-3 right-3 w-20 h-20 bg-white/90 p-1 rounded-lg shadow-md border border-gray-100 backdrop-blur-sm">
                                <model-viewer src="../assets/models/<?= htmlspecialchars($menu['model_3d']); ?>"
                                    alt="3D Model" camera-controls auto-rotate style="width: 100%; height: 100%;">
                                </model-viewer>
                            </div>
                        <?php endif; ?>

                        <?php if ($menu['promo_type'] == 'discount'): ?>
                            <div class="absolute top-3 left-3 bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-full">
                                -<?= $menu['discount'] ?>%
                            </div>
                        <?php elseif ($menu['promo_type'] == 'bundle'): ?>
                            <div class="absolute top-3 left-3 bg-green-500 text-white text-xs font-bold px-2 py-1 rounded-full">
                                Paket Hemat
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="p-4">
                        <div class="flex justify-between items-start">
                            <h3 class="text-lg font-semibold text-gray-800"><?= $menu['nama_menu']; ?></h3>
                            <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded-full"><?= $menu['kategori_menu']; ?></span>
                        </div>

                        <div class="mt-2">
                            <?php if ($menu['promo_type'] == 'discount'): ?>
                                <?php $harga_promo = $menu['harga'] - ($menu['harga'] * $menu['discount'] / 100); ?>
                                <div class="flex items-center space-x-2">
                                    <span class="text-lg font-bold text-gray-800">Rp <?= number_format($harga_promo, 0, ',', '.'); ?></span>
                                    <span class="text-sm line-through text-gray-400">Rp <?= number_format($menu['harga'], 0, ',', '.'); ?></span>
                                </div>
                            <?php elseif ($menu['promo_type'] == 'bundle'): ?>
                                <div class="flex items-center space-x-2">
                                    <span class="text-lg font-bold text-green-600">Rp <?= number_format($menu['bundle_price'], 0, ',', '.'); ?></span>
                                    <span class="text-xs text-gray-500">(Paket Hemat)</span>
                                </div>
                            <?php else: ?>
                                <span class="text-lg font-bold text-gray-800">Rp <?= number_format($menu['harga'], 0, ',', '.'); ?></span>
                            <?php endif; ?>
                        </div>

                        <form method="POST" action="add_to_cart.php" class="mt-4">
                            <input type="hidden" name="id_menu" value="<?= $menu['id_menu'] ?>">
                            <input type="hidden" name="nama_menu" value="<?= $menu['nama_menu']; ?>">
                            <input type="hidden" name="harga" value="<?= $menu['harga']; ?>">
                            <input type="hidden" name="harga_promo" value="<?= $menu['harga_promo'] ?? ''; ?>">
                            <input type="hidden" name="gambar" value="<?= $menu['gambar']; ?>">
                            <button type="submit" class="w-full bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white py-2.5 rounded-lg transition font-medium text-sm shadow-md hover:shadow-lg">
                                + Tambah ke Keranjang
                            </button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </main>

    <!-- Floating Cart Button - Positioned above bottom navigation -->
    <a href="keranjang.php" class="fixed bottom-20 right-6 bg-gradient-to-r from-green-500 to-green-600 text-white px-5 py-3 rounded-full shadow-xl floating-cart flex items-center space-x-2 z-50">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z"></path>
        </svg>
        <span class="font-medium">Keranjang</span>
        <span class="bg-white text-green-600 text-xs font-bold px-2 py-0.5 rounded-full">0</span>
    </a>

    <!-- Bottom Navigation - Simplified -->
    <nav class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 py-2 px-4 shadow-lg">
        <div class="flex justify-around max-w-md mx-auto">
            <a href="menu.php" class="flex flex-col items-center px-4 py-1 text-blue-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                </svg>
                <span class="text-xs mt-1">Menu</span>
            </a>
            <a href="success.php" class="flex flex-col items-center px-4 py-1 text-gray-500 hover:text-blue-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                </svg>
                <span class="text-xs mt-1">Pesanan</span>
            </a>
        </div>
    </nav>
</body>

</html>

<script>
    function filterMenu() {
        const selected = document.getElementById('filterKategori').value;
        const cards = document.querySelectorAll('.menu-card');
        cards.forEach(card => {
            if (selected === 'all' || card.dataset.kategori === selected) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    }

    function tambahKeKeranjang(nama) {
        alert(`"${nama}" ditambahkan ke keranjang!`);
        // Tambahkan kode Ajax jika ingin menyimpan ke server
    }
</script>
</body>

</html>