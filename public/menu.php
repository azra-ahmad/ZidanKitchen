<?php
session_start();
include '../config/db.php';
include '../config/functions.php';

if (!isset($_SESSION['meja_id']) || !isset($_SESSION['customer_id'])) {
    header("Location: register.php?table=" . ($_SESSION['meja_id'] ?? ''));
    exit;
}

// Ambil daftar kategori
$kategori_result = $conn->query("SELECT DISTINCT kategori_menu FROM menu");
$kategori_list = [];
while ($row = $kategori_result->fetch_assoc()) {
    $kategori_list[] = $row['kategori_menu'];
}

// Ambil semua menu
$menu_query = "SELECT * FROM menu ORDER BY nama_menu";
$menu_result = $conn->query($menu_query);
$menu_data = [];
while ($row = $menu_result->fetch_assoc()) {
    $menu_data[$row['menu_id']] = $row;
}

// Ambil promo aktif
$promos = getActivePromos($conn);

// Data untuk pop-up promo
$active_promos = [];
foreach ($promos as $promo) {
    $promo['menu_names'] = [];
    if ($promo['promo_type'] === 'discount' && !empty($promo['menu_ids'])) {
        foreach ($promo['menu_ids'] as $menu_id) {
            if (isset($menu_data[$menu_id])) {
                $promo['menu_names'][] = $menu_data[$menu_id]['nama_menu'];
            }
        }
    } elseif ($promo['promo_type'] === 'bundle' && !empty($promo['menu_ids'])) {
        foreach ($promo['menu_ids'] as $menu_id) {
            if (isset($menu_data[$menu_id])) {
                $promo['menu_names'][] = $menu_data[$menu_id]['nama_menu'];
            }
        }
    }
    $active_promos[] = $promo;
}

// Hitung harga promo untuk setiap menu
$menus = [];
foreach ($menu_data as $menu) {
    $menu['harga_promo'] = getItemPrice($menu['menu_id'], $_SESSION['keranjang'] ?? [], $menu_data, $promos);
    $menu['discount'] = getMenuDiscount($menu['menu_id'], $promos);
    $menu['promo_type'] = null;
    $menu['promo_title'] = null;
    $menu['promo_message'] = null;

    // Cek promo discount
    if ($menu['discount'] > 0) {
        $menu['promo_type'] = 'discount';
        foreach ($promos as $promo) {
            if ($promo['promo_type'] === 'discount' && in_array($menu['menu_id'], $promo['menu_ids'])) {
                $menu['promo_title'] = $promo['title'];
                break;
            }
        }
    }

    // Cek promo bundle (tampilkan meskipun belum lengkap)
    foreach ($promos as $promo) {
        if ($promo['promo_type'] === 'bundle' && in_array($menu['menu_id'], $promo['menu_ids'])) {
            $menu['promo_type'] = checkBundlePromo($_SESSION['keranjang'] ?? [], $promo) ? 'bundle' : 'bundle_incomplete';
            $menu['promo_title'] = $promo['title'];
            $menu['bundle_discount'] = $promo['bundle_discount_value'];
            // Cek item yang kurang untuk bundle
            $missing_items = [];
            foreach ($promo['menu_ids'] as $bundle_item_id) {
                $found = false;
                foreach ($_SESSION['keranjang'] ?? [] as $cart_item) {
                    if ($cart_item['menu_id'] == $bundle_item_id && $cart_item['jumlah'] > 0) {
                        $found = true;
                        break;
                    }
                }
                if (!$found && $bundle_item_id != $menu['menu_id']) {
                    $missing_items[] = $menu_data[$bundle_item_id]['nama_menu'];
                }
            }
            if (!empty($missing_items)) {
                $menu['promo_message'] = "Tambah " . implode(" & ", $missing_items) . " untuk diskon " . number_format($promo['bundle_discount_value'], 0) . "%!";
            }
            break;
        }
    }
    $menus[] = $menu;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
    <title>Menu - ZidanKitchen</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script type="module" src="https://unpkg.com/@google/model-viewer"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/menu.css">
</head>

<body class="min-h-screen m-0 p-0">
    <!-- Toast Notification -->
    <script>
        function showToast(message) {
            const toast = document.createElement('div');
            toast.className = 'toast';
            toast.innerText = message;
            document.body.appendChild(toast);
            setTimeout(() => toast.classList.add('show'), 100);
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => document.body.removeChild(toast), 400);
            }, 2500);
        }

        <?php if (isset($_GET['payment'])): ?>
            <?php if ($_GET['payment'] == 'success'): ?>
                showToast("Pembayaran berhasil! Makanan sedang dimasak.");
            <?php elseif ($_GET['payment'] == 'pending'): ?>
                showToast("Pembayaran sedang diproses. Silakan tunggu konfirmasi.");
            <?php elseif ($_GET['payment'] == 'error'): ?>
                showToast("Pembayaran gagal. Silakan coba lagi.");
            <?php endif; ?>
        <?php endif; ?>
    </script>

    <!-- Promo Pop-Up -->
    <div id="promoModal" class="promo-modal">
        <div class="promo-modal-content">
            <span class="close-btn" onclick="closePromoModal()">×</span>
            <?php foreach ($active_promos as $promo): ?>
                <div class="promo-item mb-4">
                    <img src="../assets/images/<?php echo htmlspecialchars($promo['image']); ?>" alt="<?php echo htmlspecialchars($promo['title']); ?>">
                    <h2 class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($promo['title']); ?></h2>
                    <p class="text-gray-600">
                        <?php if ($promo['promo_type'] === 'discount'): ?>
                            Diskon <?php echo number_format($promo['discount'], 0); ?>% untuk <?php echo implode(', ', $promo['menu_names']); ?>!
                        <?php else: ?>
                            Diskon <?php echo number_format($promo['bundle_discount_value'], 0); ?>% untuk paket <?php echo implode(' + ', $promo['menu_names']); ?>!
                        <?php endif; ?>
                    </p>
                </div>
            <?php endforeach; ?>
            <a href="promo.php" class="block bg-blue-600 text-white px-6 py-2 rounded-full hover:bg-blue-700 transition-all text-center">Lihat Semua Promo</a>
        </div>
    </div>

    <!-- Header -->
    <header class="sticky top-0 z-50 glass shadow-lg px-6 py-4">
        <div class="flex justify-between items-center max-w-7xl mx-auto">
            <div class="flex items-center space-x-3">
                <img src="../assets/images/logo_biru.png" alt="Logo" class="w-12 h-12 object-contain transition-transform hover:scale-105">
                <h1 class="text-3xl font-bold text-gray-800">
                    Zidan<span class="text-blue-600">Kitchen</span>
                </h1>
            </div>
            <!-- Hapus icon cart dari header -->
        </div>
    </header>

    <!-- Category Filter -->
    <div class="fixed top-20 z-40 glass w-full px-6 py-4 shadow-md">
        <div class="category-scroll flex space-x-3 overflow-x-auto max-w-7xl mx-auto">
            <button class="px-6 py-2.5 rounded-full bg-gradient-to-r from-blue-500 to-blue-700 text-white text-sm font-medium transition-all duration-300 hover:from-blue-600 hover:to-blue-800 shadow-md hover:shadow-lg whitespace-nowrap" data-filter="all">Semua Menu</button>
            <?php foreach ($kategori_list as $kategori): ?>
                <button class="px-6 py-2.5 rounded-full bg-white/50 text-gray-700 border border-gray-200 text-sm font-medium transition-all duration-300 hover:bg-white hover:shadow-md whitespace-nowrap" data-filter="<?php echo htmlspecialchars($kategori); ?>">
                    <?php echo htmlspecialchars($kategori); ?>
                </button>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Menu -->
    <main class="container mx-auto px-6 py-8 max-w-7xl pt-24 pb-20">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <?php $index = 0; foreach ($menus as $menu): ?>
                <div class="bg-white/80 rounded-2xl overflow-hidden shadow-lg card-hover animate-in menu-card" style="animation-delay: <?php echo ($index % 10) * 0.1; ?>s;" data-kategori="<?php echo htmlspecialchars($menu['kategori_menu']); ?>">
                    <div class="relative">
                        <div class="h-56 w-full overflow-hidden">
                            <img src="../assets/images/<?php echo htmlspecialchars($menu['gambar']); ?>" alt="<?php echo htmlspecialchars($menu['nama_menu']); ?>" class="w-full h-full object-cover transition-transform duration-500 hover:scale-110">
                        </div>
                        <?php if (!empty($menu['model_3d'])): ?>
                            <div class="absolute bottom-4 right-4 w-24 h-24 bg-white/90 rounded-xl shadow-xl border border-gray-100 p-2 transform hover:scale-105 transition-transform duration-300">
                                <model-viewer src="../assets/models/<?php echo htmlspecialchars($menu['model_3d']); ?>" alt="3D Model" camera-controls auto-rotate style="width: 100%; height: 100%;"></model-viewer>
                            </div>
                        <?php endif; ?>
                        <?php if ($menu['promo_type'] == 'discount'): ?>
                            <div class="absolute top-4 left-4 bg-gradient-to-r from-red-500 to-red-600 text-white text-xs font-bold px-3 py-1.5 rounded-full shadow-md">
                                -<?php echo number_format($menu['discount'], 0); ?>%
                            </div>
                            <div class="absolute top-4 right-4 bg-white/90 text-red-600 text-xs font-semibold px-3 py-1.5 rounded-full shadow-sm">
                                <?php echo htmlspecialchars($menu['promo_title'] ?? ''); ?>
                            </div>
                        <?php elseif ($menu['promo_type'] == 'bundle'): ?>
                            <div class="absolute top-4 left-4 bg-gradient-to-r from-green-500 to-green-600 text-white text-xs font-bold px-3 py-1.5 rounded-full shadow-md">
                                -<?php echo number_format($menu['bundle_discount'], 0); ?>%
                            </div>
                            <div class="absolute top-4 right-4 bg-white/90 text-green-600 text-xs font-semibold px-3 py-1.5 rounded-full shadow-sm">
                                Paket Hemat
                            </div>
                        <?php elseif ($menu['promo_type'] == 'bundle_incomplete'): ?>
                            <div class="absolute top-4 left-4 bg-gradient-to-r from-yellow-500 to-yellow-600 text-white text-xs font-bold px-3 py-1.5 rounded-full shadow-md">
                                -<?php echo number_format($menu['bundle_discount'], 0); ?>%
                            </div>
                            <div class="absolute top-4 right-4 bg-white/90 text-yellow-600 text-xs font-semibold px-3 py-1.5 rounded-full shadow-sm">
                                Promo Bundle
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="p-5">
                        <div class="flex justify-between items-start mb-3">
                            <h3 class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($menu['nama_menu']); ?></h3>
                            <span class="text-xs text-gray-500 bg-gray-100/50 px-2.5 py-1 rounded-full"><?php echo htmlspecialchars($menu['kategori_menu']); ?></span>
                        </div>
                        <div class="mb-4">
                            <?php if ($menu['promo_type'] == 'discount'): ?>
                                <?php
                                $harga_promo = $menu['harga'] * (1 - ($menu['discount'] / 100));
                                $diskon_value = $menu['harga'] - $harga_promo;
                                ?>
                                <div class="space-y-1">
                                    <div class="flex items-center space-x-2">
                                        <span class="text-lg font-bold text-gray-800">Rp <?php echo number_format($harga_promo, 0, ',', '.'); ?></span>
                                        <span class="text-sm line-through text-gray-400">Rp <?php echo number_format($menu['harga'], 0, ',', '.'); ?></span>
                                    </div>
                                    <div class="text-xs text-green-600 font-medium">
                                        Hemat Rp <?php echo number_format($diskon_value, 0, ',', '.'); ?> (<?php echo number_format($menu['discount'], 0); ?>%)
                                    </div>
                                </div>
                            <?php elseif ($menu['promo_type'] == 'bundle'): ?>
                                <div class="space-y-1">
                                    <div class="flex items-center space-x-2">
                                        <span class="text-lg font-bold text-green-600">Rp <?php echo number_format($menu['harga_promo'], 0, ',', '.'); ?></span>
                                        <span class="text-xs text-gray-500">(Paket Hemat)</span>
                                    </div>
                                    <div class="text-xs text-blue-600 font-medium">
                                        <?php echo htmlspecialchars($menu['promo_title'] ?? ''); ?>
                                    </div>
                                </div>
                            <?php elseif ($menu['promo_type'] == 'bundle_incomplete'): ?>
                                <div class="space-y-1">
                                    <div class="flex items-center space-x-2">
                                        <span class="text-lg font-bold text-gray-800">Rp <?php echo number_format($menu['harga'], 0, ',', '.'); ?></span>
                                    </div>
                                    <div class="text-xs text-yellow-600 font-medium">
                                        <?php echo htmlspecialchars($menu['promo_message'] ?? ''); ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <span class="text-lg font-bold text-gray-800">Rp <?php echo number_format($menu['harga'], 0, ',', '.'); ?></span>
                            <?php endif; ?>
                        </div>
                        <form method="POST" action="add_to_cart.php">
                            <input type="hidden" name="menu_id" value="<?php echo $menu['menu_id']; ?>">
                            <input type="hidden" name="nama_menu" value="<?php echo htmlspecialchars($menu['nama_menu']); ?>">
                            <input type="hidden" name="harga" value="<?php echo $menu['harga']; ?>">
                            <input type="hidden" name="harga_promo" value="<?php echo isset($menu['harga_promo']) ? $menu['harga_promo'] : $menu['harga']; ?>">
                            <input type="hidden" name="gambar" value="<?php echo htmlspecialchars($menu['gambar']); ?>">
                            <input type="hidden" name="promo_type" value="<?php echo $menu['promo_type'] ?? ''; ?>">
                            <button type="submit" class="btn-tambah w-full bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white py-3 rounded-xl font-medium text-sm transition-all duration-300 shadow-md hover:shadow-xl neon-button">
                                + Tambah ke Keranjang
                            </button>
                        </form>
                    </div>
                </div>
            <?php $index++; endforeach; ?>
        </div>
    </main>

    <!-- Bottom Navigation -->
    <nav class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 py-2 px-4 shadow-lg">
        <div class="flex justify-around max-w-md mx-auto">
            <a href="promo.php" class="flex flex-col items-center px-4 py-1 hover:text-blue-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span class="text-xs mt-1">Promo</span>
            </a>
            <a href="menu.php" class="flex flex-col items-center px-4 py-1 text-blue-600">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                </svg>
                <span class="text-xs mt-1">Menu</span>
            </a>
            <a href="keranjang.php" class="flex flex-col items-center px-4 py-1 hover:text-blue-600">
                <svg class="w-6 h-6 text-black-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"></path>
                </svg>
                <span class="text-xs mt-1">Keranjang</span>
            </a>
        </div>
    </nav>

    <!-- JavaScript -->
    <script>
        // Promo Pop-Up
        document.addEventListener('DOMContentLoaded', function() {
            const promoModal = document.getElementById('promoModal');
            if (promoModal) {
                setTimeout(() => {
                    promoModal.style.display = 'block';
                }, 1000);
            }

            // Close on click outside
            promoModal.addEventListener('click', function(e) {
                if (e.target === promoModal) {
                    closePromoModal();
                }
            });

            // Close on Esc key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closePromoModal();
                }
            });
        });

        function closePromoModal() {
            const promoModal = document.getElementById('promoModal');
            promoModal.classList.add('fade-out');
            setTimeout(() => {
                promoModal.style.display = 'none';
            }, 300);
        }

        // Handle Add to Cart
        document.addEventListener('DOMContentLoaded', function() {
            const addButtons = document.querySelectorAll('.btn-tambah');
            addButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const form = this.closest('form');
                    fetch('add_to_cart.php', {
                        method: 'POST',
                        body: new FormData(form)
                    }).then(response => {
                        if (response.ok) {
                            // animateCart(); // Hapus karena icon cart udah ga di header
                            showToast('Berhasil ditambahkan ke keranjang!');
                        } else {
                            showToast('Gagal menambahkan ke keranjang.');
                        }
                    }).catch(error => {
                        console.error('Error:', error);
                        showToast('Terjadi kesalahan, coba lagi.');
                    });
                });
            });
        });

        // Category Filter
        document.querySelectorAll('.category-scroll button').forEach(button => {
            button.addEventListener('click', function() {
                document.querySelectorAll('.category-scroll button').forEach(btn => {
                    btn.classList.remove('bg-gradient-to-r', 'from-blue-500', 'to-blue-700', 'text-white');
                    btn.classList.add('bg-white/50', 'text-gray-700', 'border', 'border-gray-200');
                });
                this.classList.remove('bg-white/50', 'text-gray-700', 'border', 'border-gray-200');
                this.classList.add('bg-gradient-to-r', 'from-blue-500', 'to-blue-700', 'text-white');

                const filter = this.dataset.filter;
                const cards = document.querySelectorAll('.menu-card');
                cards.forEach(card => {
                    if (filter === 'all' || card.dataset.kategori === filter) {
                        card.style.display = 'block';
                        card.classList.add('animate-in');
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        });
    </script>
</body>

</html>