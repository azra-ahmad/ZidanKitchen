<?php
include '../config/db.php';
session_start();
if (!isset($_SESSION['id_meja'])) {
    header("Location: index.php");
    exit();
}

// Ambil daftar kategori
$kategori_result = $conn->query("SELECT DISTINCT kategori_menu FROM menu");
$kategori_list = [];
while ($row = $kategori_result->fetch_assoc()) {
    $kategori_list[] = $row['kategori_menu'];
}

$menu_query = "SELECT 
    m.*, 
    p.promo_type, 
    p.discount, 
    p.bundle_price
FROM menu m 
LEFT JOIN promos p ON m.kategori_menu = p.category_target 
    AND p.valid_until IS NOT NULL
    AND p.valid_until >= CURDATE()";

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
    <style>
        @keyframes fadeUp {
            0% {
                opacity: 0;
                transform: translateY(20px);
            }

            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fadeUp {
            animation: fadeUp 0.5s ease-in-out forwards;
        }

        .glass {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
        }
    </style>
</head>

<body class="bg-gradient-to-br from-blue-100 to-blue-50 min-h-screen font-sans m-0 p-0">

    <!-- Header -->
    <div class="sticky top-0 z-50 bg-white shadow-md px-4 py-4 flex justify-between items-center">
        <h1 class="text-2xl font-bold text-blue-600">Zidan<span class="text-yellow-400">Kitchen</span></h1>
        <a href="keranjang.php" class="relative">
            <svg class="w-7 h-7 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M3 3h2l.4 2M7 13h10l4-8H5.4"></path>
                <circle cx="9" cy="21" r="1"></circle>
                <circle cx="20" cy="21" r="1"></circle>
            </svg>
            <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs px-1.5 py-0.5 rounded-full"></span>
        </a>
    </div>

    <!-- Filter Kategori -->
    <div class="sticky top-16 z-40 backdrop-blur-md px-4 py-2 shadow-md">
        <div class="flex space-x-2 overflow-x-auto pb-2">
            <button class="px-4 py-2 bg-blue-500 text-white rounded-full shadow-md hover:bg-blue-600 transition">Semua</button>
            <button class="px-4 py-2 bg-white text-gray-700 border rounded-full hover:bg-gray-100 transition">Makanan</button>
            <button class="px-4 py-2 bg-white text-gray-700 border rounded-full hover:bg-gray-100 transition">Minuman</button>
            <button class="px-4 py-2 bg-white text-gray-700 border rounded-full hover:bg-gray-100 transition">Snack</button>
        </div>
    </div>

    <!-- Menu -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6 p-4">
        <?php while ($menu = $menus->fetch_assoc()): ?>
            <div class="bg-white rounded-xl shadow-xl p-4 transform transition duration-300 hover:-translate-y-1 hover:shadow-2xl animate-fadeUp relative">
                <div class="relative">
                    <img src="../assets/images/<?= $menu['gambar']; ?>" alt="<?= $menu['nama_menu']; ?>" class="rounded-xl w-full h-48 object-cover">
                    <?php if (!empty($menu['model_3d'])): ?>
                        <div class="absolute bottom-2 right-2 w-24 h-24 bg-white p-1 rounded-lg shadow-md border border-gray-200">
                            <model-viewer src="../assets/models/<?= htmlspecialchars($menu['model_3d']); ?>"
                                alt="3D Model" camera-controls auto-rotate style="width: 100%; height: 100%;">
                            </model-viewer>
                        </div>
                    <?php endif; ?>
                </div>

                <h3 class="text-lg font-bold mt-3"><?= $menu['nama_menu']; ?></h3>
                <p class="text-sm text-gray-500 mb-1">Kategori: <?= $menu['kategori_menu']; ?></p>

                <?php if ($menu['promo_type'] == 'discount'): ?>
                    <?php $harga_promo = $menu['harga'] - ($menu['harga'] * $menu['discount'] / 100); ?>
                    <p class="text-xl font-bold text-red-500">Rp <?= number_format($harga_promo, 0, ',', '.'); ?></p>
                    <p class="text-sm line-through text-gray-400">Rp <?= number_format($menu['harga'], 0, ',', '.'); ?></p>
                <?php elseif ($menu['promo_type'] == 'bundle'): ?>
                    <p class="text-xl font-bold text-green-500">Paket: Rp <?= number_format($menu['bundle_price'], 0, ',', '.'); ?></p>
                <?php else: ?>
                    <p class="text-xl font-bold text-gray-800">Rp <?= number_format($menu['harga'], 0, ',', '.'); ?></p>
                <?php endif; ?>

                <form method="POST" action="add_to_cart.php">
                    <input type="hidden" name="id_menu" value="<?= $menu['id_menu'] ?>">
                    <input type="hidden" name="nama_menu" value="<?= $menu['nama_menu']; ?>">
                    <input type="hidden" name="harga" value="<?= $menu['harga']; ?>">
                    <input type="hidden" name="harga_promo" value="<?= $menu['harga_promo'] ?? ''; ?>"> 
                    <input type="hidden" name="gambar" value="<?= $menu['gambar']; ?>">
                    <button type="submit" class="mt-4 w-full bg-blue-500 hover:bg-blue-600 text-white py-2 rounded-xl transition font-semibold">
                        Tambah ke Keranjang
                    </button>
                </form>
            </div>
        <?php endwhile; ?>
    </div>

    <!-- Floating Keranjang -->
    <a href="keranjang.php" class="fixed bottom-6 right-6 bg-green-500 text-white px-4 py-3 rounded-full shadow-lg hover:bg-green-600 transition flex items-center space-x-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="M3 3h2l.4 2M7 13h10l4-8H5.4"></path>
            <circle cx="9" cy="21" r="1"></circle>
            <circle cx="20" cy="21" r="1"></circle>
        </svg>
        <span>Keranjang</span>
    </a>

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