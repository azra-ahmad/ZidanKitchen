<?php
session_start();
include '../config/db.php';
include '../config/functions.php';

// Ambil semua menu untuk mapping
$menu_query = "SELECT * FROM menu";
$menu_result = $conn->query($menu_query);
$menu_data = [];
while ($row = $menu_result->fetch_assoc()) {
    $menu_data[$row['menu_id']] = $row; // Ganti id_menu jadi menu_id
}

// Ambil promo yang masih berlaku pake getActivePromos()
$promos = getActivePromos($conn);

// Format promo_list untuk tampilan
$promo_list = [];
foreach ($promos as $promo) {
    $promo['menu_names'] = [];
    if ($promo['promo_type'] === 'discount' && !empty($promo['menu_ids'])) { // Ganti menu_target jadi menu_ids
        foreach ($promo['menu_ids'] as $menu_id) {
            if (isset($menu_data[$menu_id])) {
                $promo['menu_names'][] = $menu_data[$menu_id]['nama_menu'];
            }
        }
    } elseif ($promo['promo_type'] === 'bundle' && !empty($promo['menu_ids'])) { // Ganti bundle_items jadi menu_ids
        foreach ($promo['menu_ids'] as $menu_id) {
            if (isset($menu_data[$menu_id])) {
                $promo['menu_names'][] = $menu_data[$menu_id]['nama_menu'];
            }
        }
    }
    $promo_list[] = $promo;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Promo - ZidanKitchen</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;5
00;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/promo.css">
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
    </script>

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

    <!-- Promo Section -->
    <main class="container mx-auto px-5 py-8 max-w-7xl pt-6 pb-20">
        <h1 class="text-3xl font-bold text-center text-gray-800 mb-8">Promo Spesial ZidanKitchen</h1>
        <?php if (count($promo_list) > 0): ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php $index = 0; foreach ($promo_list as $promo): ?>
                    <div class="bg-white rounded-2xl overflow-hidden shadow-lg card-hover animate-in relative" style="animation-delay: <?php echo ($index % 10) * 0.1; ?>s;">
                        <div class="h-48 w-full overflow-hidden">
                            <img src="../assets/images/<?php echo htmlspecialchars($promo['image']); ?>" alt="<?php echo htmlspecialchars($promo['title']); ?>" class="w-full h-full object-cover transition-transform duration-500 hover:scale-110">
                        </div>
                        <div class="p-5">
                            <h2 class="text-xl font-semibold text-gray-800 mb-2"><?php echo htmlspecialchars($promo['title']); ?></h2>
                            <p class="text-gray-500 text-sm mb-1">Berlaku: <?php echo date('d M Y', strtotime($promo['start_date'])); ?> - <?php echo date('d M Y', strtotime($promo['end_date'])); ?></p>
                            <p class="text-gray-700 text-sm mb-3"><?php echo htmlspecialchars($promo['description']); ?></p>
                            <?php if ($promo['promo_type'] === 'discount'): ?>
                                <div class="flex items-center space-x-2">
                                    <span class="bg-red-500 text-white text-xs font-bold px-3 py-1 rounded-full">Diskon <?php echo number_format($promo['discount'], 0); ?>%</span>
                                    <span class="text-gray-600 text-sm">untuk <?php echo implode(', ', $promo['menu_names']); ?></span>
                                </div>
                            <?php elseif ($promo['promo_type'] === 'bundle'): ?>
                                <div class="flex items-center space-x-2">
                                    <span class="bg-green-500 text-white text-xs font-bold px-3 py-1 rounded-full">Diskon <?php echo number_format($promo['bundle_discount_value'], 0); ?>%</span>
                                    <span class="text-gray-600 text-sm">untuk paket <?php echo implode(' + ', $promo['menu_names']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php $index++; endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <!-- Bottom Navigation -->
    <nav class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 py-2 px-4 shadow-lg">
        <div class="flex justify-around max-w-md mx-auto">
            <a href="promo.php" class="flex flex-col items-center px-4 py-1 text-blue-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span class="text-xs mt-1">Promo</span>
            </a>
            <a href="menu.php" class="flex flex-col items-center px-4 py-1 hover:text-blue-600">
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
</body>

</html>