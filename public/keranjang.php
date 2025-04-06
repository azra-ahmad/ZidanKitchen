<?php
session_start();
include '../config/db.php'; // Koneksi ke database

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
    AND (
        (p.promo_type = 'discount' AND p.discount = (
            SELECT MAX(p2.discount)
            FROM promos p2 
            WHERE p2.category_target = m.kategori_menu
            AND p2.start_date <= CURDATE()
            AND p2.end_date >= CURDATE()
            AND p2.promo_type = 'discount'
        )) OR p.promo_type IN ('bundle', 'buy2get1')
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

// Tambah/kurang jumlah item
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

// Validasi & update info produk
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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        .promo-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            transform: rotate(15deg);
        }
        .item-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        /* Smooth transition untuk FAB */
        .fab-button {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .fab-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
    </style>
</head>
<body class="bg-gradient-to-b from-blue-50 to-white min-h-screen">
    <!-- Sticky Header -->
    <header class="sticky top-0 z-50 bg-blue-600 shadow-lg">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <h1 class="text-xl font-bold text-white">Keranjang Belanja</h1>
            <a href="menu.php" class="text-blue-100 hover:text-white transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
        </div>
    </header>

    <main class="container mx-auto px-4 py-6 max-w-2xl">
        <?php if (count($keranjang) > 0): ?>
            <!-- Daftar Item -->
            <div class="space-y-4 mb-6">
                <?php
                $total = 0;
                foreach ($keranjang as $item):
                    $harga_final = $item['harga_promo'] ?? $item['harga_asli'];
                    
                    if (!empty($item['promo_type']) && $item['promo_type'] === 'buy2get1') {
                        $gratis = floor($item['jumlah'] / 3);
                        $jumlah_dibayar = $item['jumlah'] - $gratis;
                        $subtotal = $harga_final * $jumlah_dibayar;
                    } else {
                        $jumlah_dibayar = $item['jumlah'];
                        $subtotal = $harga_final * $item['jumlah'];
                    }
                    $total += $subtotal;
                ?>
                <div class="relative item-card bg-white rounded-xl shadow-md p-4 transition duration-300">
                    <!-- Promo Badge -->
                    <?php if (!empty($item['promo_type'])): ?>
                        <div class="promo-badge bg-yellow-400 text-yellow-800 text-xs font-bold px-2 py-1 rounded-full">
                            <?= strtoupper($item['promo_type']) ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="flex gap-4">
                        <!-- Gambar Menu -->
                        <div class="flex-shrink-0">
                            <img src="../assets/images/<?= htmlspecialchars($item['gambar']); ?>" 
                                 alt="<?= htmlspecialchars($item['nama_menu']); ?>" 
                                 class="w-20 h-20 object-cover rounded-lg border border-gray-200">
                        </div>
                        
                        <!-- Detail Menu -->
                        <div class="flex-1">
                            <h3 class="font-bold text-gray-800"><?= htmlspecialchars($item['nama_menu']); ?></h3>
                            
                            <!-- Harga -->
                            <div class="mt-1">
                                <?php if (!empty($item['promo_type']) && $item['promo_type'] == 'discount'): ?>
                                    <span class="line-through text-red-500 text-sm">Rp <?= number_format($item['harga_asli'], 0, ',', '.'); ?></span>
                                    <span class="ml-2 text-green-600 font-bold">Rp <?= number_format($item['harga_promo'], 0, ',', '.'); ?></span>
                                <?php elseif (!empty($item['promo_type']) && $item['promo_type'] == 'bundle'): ?>
                                    <span class="text-green-600 font-bold">Rp <?= number_format($item['harga_promo'], 0, ',', '.'); ?></span>
                                    <span class="ml-2 text-xs text-gray-500">(Harga Paket)</span>
                                <?php elseif (!empty($item['promo_type']) && $item['promo_type'] == 'buy2get1'): ?>
                                    <span class="text-blue-600 font-bold">Rp <?= number_format($item['harga_asli'], 0, ',', '.'); ?></span>
                                    <span class="ml-2 text-xs bg-blue-100 text-blue-800 px-2 py-0.5 rounded">Beli <?= $item['jumlah'] ?> Bayar <?= $jumlah_dibayar ?></span>
                                <?php else: ?>
                                    <span class="text-gray-800 font-bold">Rp <?= number_format($item['harga_asli'], 0, ',', '.'); ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Quantity Control -->
                            <div class="flex items-center mt-3">
                                <!-- Form Kurang -->
                                <form method="POST" action="keranjang.php" 
                                    onsubmit="this.querySelector('button').innerHTML = '&lt;svg class=&quot;animate-spin h-4 w-4 text-white&quot; xmlns=&quot;http://www.w3.org/2000/svg&quot; fill=&quot;none&quot; viewBox=&quot;0 0 24 24&quot;&gt;&lt;circle class=&quot;opacity-25&quot; cx=&quot;12&quot; cy=&quot;12&quot; r=&quot;10&quot; stroke=&quot;currentColor&quot; stroke-width=&quot;4&quot;&gt;&lt;/circle&gt;&lt;path class=&quot;opacity-75&quot; fill=&quot;currentColor&quot; d=&quot;M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z&quot;&gt;&lt;/path&gt;&lt;/svg&gt;';">
                                    <input type="hidden" name="id_menu" value="<?= $item['id_menu']; ?>">
                                    <input type="hidden" name="action" value="kurang">
                                    <button type="submit" class="bg-red-500 text-white w-8 h-8 rounded-full flex items-center justify-center hover:bg-red-600 transition">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                                        </svg>
                                    </button>
                                </form>

                                <span class="mx-3 font-medium text-gray-700"><?= $item['jumlah']; ?></span>

                                <!-- Form Tambah -->
                                <form method="POST" action="keranjang.php" 
                                    onsubmit="this.querySelector('button').innerHTML = '&lt;svg class=&quot;animate-spin h-4 w-4 text-white&quot; xmlns=&quot;http://www.w3.org/2000/svg&quot; fill=&quot;none&quot; viewBox=&quot;0 0 24 24&quot;&gt;&lt;circle class=&quot;opacity-25&quot; cx=&quot;12&quot; cy=&quot;12&quot; r=&quot;10&quot; stroke=&quot;currentColor&quot; stroke-width=&quot;4&quot;&gt;&lt;/circle&gt;&lt;path class=&quot;opacity-75&quot; fill=&quot;currentColor&quot; d=&quot;M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z&quot;&gt;&lt;/path&gt;&lt;/svg&gt;';">
                                    <input type="hidden" name="id_menu" value="<?= $item['id_menu']; ?>">
                                    <input type="hidden" name="action" value="tambah">
                                    <button type="submit" class="bg-green-500 text-white w-8 h-8 rounded-full flex items-center justify-center hover:bg-green-600 transition">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                    </button>
                                </form>
                            </div>

                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="fixed bottom-24 right-4 z-20 md:bottom-28 md:right-1/2 md:transform md:translate-x-1/2">
                <a href="menu.php" class="bg-blue-600 text-white p-3 rounded-full shadow-lg hover:bg-blue-700 transition flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    <span class="sr-only">Tambah Menu</span>
                </a>
            </div>

            
            <div class="fixed bottom-0 left-0 right-0 bg-white shadow-lg border-t border-gray-200 px-4 py-3 z-10">
                <div class="max-w-2xl mx-auto flex justify-between items-center">
                    <div>
                        <p class="text-sm text-gray-600">Total Pembayaran</p>
                        <p class="text-lg font-bold text-green-600">Rp <?= number_format($total, 0, ',', '.'); ?></p>
                    </div>
                    <form method="POST" action="checkout.php">
                        <button type="submit" class="bg-yellow-400 hover:bg-yellow-500 text-white font-medium px-6 py-2 rounded-lg shadow transition flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            Checkout
                        </button>
                    </form>
                </div>
            </div>
            
        <?php else: ?>
            <!-- Empty State -->
            <div class="text-center py-12">
                <div class="mx-auto w-48 h-48 bg-blue-100 rounded-full flex items-center justify-center mb-6">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-24 w-24 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-700 mb-2">Keranjang Kosong</h2>
                <p class="text-gray-500 mb-6">Belum ada item di keranjang belanja Anda</p>
                <a href="menu.php" class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-medium px-6 py-3 rounded-lg shadow-md transition duration-300">
                    Lihat Menu
                </a>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>