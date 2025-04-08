<?php
session_start();
require_once '../config/db.php';

$order_id = null;

if (isset($_SESSION['order_id'])) {
    // Ambil order_id dari session
    $session_order_id = $_SESSION['order_id'];

    // Ambil data order dari database
    $stmt_order = $conn->prepare("SELECT order_id FROM orders WHERE order_id = ?");
    $stmt_order->bind_param("s", $session_order_id);
    $stmt_order->execute();
    $result_order = $stmt_order->get_result();

    if ($row_order = $result_order->fetch_assoc()) {
        $order_id = $row_order['order_id'];
    }

    $stmt_order->close();
}

$order_items = [];

if ($order_id) {
    $stmt = $conn->prepare("
        SELECT oi.jumlah, m.nama_menu, m.gambar 
        FROM order_items oi 
        JOIN menu m ON oi.id_menu = m.id_menu 
        WHERE oi.order_id = ?

    ");
    $stmt->bind_param("s", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $order_items[] = $row;
    }

    $stmt->close();
}
?>


<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Diproses - ZidanKitchen</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.5;
            }
        }

        @keyframes bounce {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-10px);
            }
        }

        @keyframes slideIn {
            from {
                transform: translateX(-100%);
            }

            to {
                transform: translateX(0);
            }
        }

        .animate-pulse-custom {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        .animate-bounce-custom {
            animation: bounce 1.5s ease-in-out infinite;
        }

        .animate-slide-in {
            animation: slideIn 0.5s ease-out forwards;
        }

        .progress-bar {
            transition: width 0.5s ease-in-out;
        }

        .food-item {
            transition: all 0.3s ease;
        }

        .food-item:hover {
            transform: scale(1.05);
        }
    </style>
</head>

<body class="bg-gray-50 font-['Inter']">
    <div class="min-h-screen flex flex-col">
        <!-- Header -->
        <div class="bg-blue-600 py-4 px-4 shadow-sm sticky top-0 z-10">
            <div class="flex items-center justify-center">
                <h1 class="text-xl font-bold text-white">Pesanan Sedang Diproses</h1>
            </div>
        </div>

        <!-- Main Content -->
        <main class="flex-1 p-4 max-w-2xl mx-auto w-full">
            <!-- Order Status -->
            <div class="bg-white rounded-xl p-6 mb-6 shadow-md border border-blue-100 animate-slide-in">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-lg font-bold text-gray-800">Order #ZK-<?= htmlspecialchars($order_id ?? 'XXXX') ?></h2>
                        <p class="text-sm text-gray-500">Meja <?= htmlspecialchars((string)$_SESSION['id_meja'] ?? '00') ?></p>
                    </div>
                    <div class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-medium">
                        Dalam Proses
                    </div>
                </div>


                <!-- Kitchen Animation -->
                <div class="bg-white rounded-xl p-6 mb-6 shadow-md border border-blue-100 overflow-hidden">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Dapur Kami Sedang Bekerja</h3>

                    <div class="relative h-64 bg-gray-100 rounded-lg overflow-hidden flex items-center justify-center">
                        <!-- Kitchen Background -->
                        <div class="absolute inset-0 bg-gray-200 opacity-20"></div>

                        <!-- Chef Animation -->
                        <div class="absolute left-1/4 bottom-0 w-16 h-24 flex flex-col items-center">
                            <div class="w-12 h-12 bg-red-500 rounded-full mb-1 animate-bounce-custom"></div>
                            <div class="w-16 h-8 bg-white rounded-full flex items-center justify-center shadow-md">
                                <div class="w-14 h-6 bg-red-500 rounded-full"></div>
                            </div>
                        </div>

                        <!-- Cooking Animation -->
                        <div class="absolute left-1/2 transform -translate-x-1/2 bottom-0 w-20 h-20">
                            <div class="relative w-full h-full">
                                <!-- Pan -->
                                <div class="absolute bottom-0 left-1/2 transform -translate-x-1/2 w-16 h-4 bg-gray-400 rounded-full"></div>
                                <!-- Food -->
                                <div class="absolute bottom-4 left-1/2 transform -translate-x-1/2 w-12 h-2 bg-yellow-600 rounded-full animate-pulse-custom"></div>
                                <!-- Steam -->
                                <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 flex space-x-1">
                                    <div class="w-1 h-4 bg-gray-300 opacity-70 rounded-full animate-bounce-custom" style="animation-delay: 0.1s"></div>
                                    <div class="w-1 h-6 bg-gray-300 opacity-70 rounded-full animate-bounce-custom" style="animation-delay: 0.3s"></div>
                                    <div class="w-1 h-5 bg-gray-300 opacity-70 rounded-full animate-bounce-custom" style="animation-delay: 0.2s"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Ingredients Floating -->
                        <div class="absolute top-4 left-8 w-8 h-8 bg-yellow-300 rounded-full animate-bounce-custom" style="animation-delay: 0.2s"></div>
                        <div class="absolute top-8 right-12 w-6 h-6 bg-red-400 rounded-full animate-bounce-custom" style="animation-delay: 0.4s"></div>
                        <div class="absolute top-2 right-1/4 w-5 h-5 bg-green-400 rounded-full animate-bounce-custom" style="animation-delay: 0.3s"></div>
                    </div>
                </div>

                <!-- Order Items -->
                <div class="bg-white rounded-xl p-6 shadow-md border border-blue-100">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Detail Pesanan Anda</h3>

                    <div class="space-y-4">
                        <?php foreach ($order_items as $item): ?>
                            <div class="food-item flex items-center p-3 bg-gray-50 rounded-lg border border-gray-200 hover:shadow-sm">
                                <div class="w-16 h-16 bg-gray-300 rounded-lg mr-4 overflow-hidden">
                                    <?php if (!empty($item['gambar'])): ?>
                                        <img src="../assets/images/<?= htmlspecialchars($item['gambar']) ?>" alt="<?= htmlspecialchars($item['nama_menu']) ?>" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <div class="w-full h-full flex items-center justify-center text-gray-500">
                                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                            </svg>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="flex-1">
                                    <h4 class="font-medium text-gray-800"><?= htmlspecialchars($item['nama_menu']) ?></h4>
                                    <p class="text-sm text-gray-500"><?= $item['jumlah'] ?>x</p>
                                </div>
                                <div class="flex items-center">
                                    <div class="w-3 h-3 bg-blue-500 rounded-full mr-2 animate-pulse-custom"></div>
                                    <span class="text-sm font-medium text-gray-700">Memasak</span>
                                </div>
                            </div>
                        <?php endforeach; ?>

                    </div>
                </div>
        </main>

        <!-- Footer Navigation -->
        <div class="bg-white border-t border-gray-200 py-3 px-4 sticky bottom-0">
            <div class="flex justify-between items-center max-w-2xl mx-auto">
                <a href="menu.php" class="text-gray-600 hover:text-blue-600 flex flex-col items-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    <span class="text-xs mt-1">Menu</span>
                </a>
                <a href="#" class="text-blue-600 flex flex-col items-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    <span class="text-xs mt-1">Pesanan</span>
                </a>
            </div>
        </div>
    </div>

</body>

</html>