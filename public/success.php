<?php
session_start();
require_once '../vendor/autoload.php';
include '../config/db.php';

// Load Midtrans config
$midtrans_config = include '../config/midtrans.php';

// Validate session
if (!isset($_SESSION['customer_id']) || !isset($_SESSION['meja_id']) || !isset($_SESSION['order_id'])) {
    header("Location: menu.php");
    exit;
}

// Ambil order_id dari session
$order_id = (int)$_SESSION['order_id'];
$meja_id = $_SESSION['meja_id'];
$customer_id = $_SESSION['customer_id'];

// Ambil data order dari database
$stmt_order = $conn->prepare("
    SELECT order_id, status, total_harga, midtrans_order_id, metode_pembayaran 
    FROM orders 
    WHERE order_id = ? AND customer_id = ?
");
$stmt_order->bind_param("ii", $order_id, $customer_id);
$stmt_order->execute();
$result_order = $stmt_order->get_result();

if ($result_order->num_rows === 0) {
    header("Location: menu.php");
    exit;
}

$order = $result_order->fetch_assoc();
$stmt_order->close();

// Re-check status with Midtrans if pending
if ($order['status'] === 'pending' && !empty($order['midtrans_order_id'])) {
    try {
        \Midtrans\Config::$serverKey = $midtrans_config['server_key'];
        \Midtrans\Config::$isProduction = $midtrans_config['is_production'];

        /** @var \stdClass $status_response */
        $status_response = \Midtrans\Transaction::status($order['midtrans_order_id']);
        if (!is_object($status_response) || !isset($status_response->transaction_status)) {
            throw new Exception("Invalid Midtrans response: " . json_encode($status_response));
        }

        $new_status = 'pending';
        switch ($status_response->transaction_status) {
            case 'capture':
            case 'settlement':
                $new_status = 'paid';
                break;
            case 'deny':
            case 'cancel':
            case 'expire':
                $new_status = 'failed';
                break;
        }

        if ($new_status !== $order['status']) {
            $update_stmt = $conn->prepare("UPDATE orders SET status = ?, metode_pembayaran = ? WHERE order_id = ?");
            $update_stmt->bind_param("ssi", $new_status, $status_response->payment_type, $order_id);
            $update_stmt->execute();
            $order['status'] = $new_status;
            $order['metode_pembayaran'] = $status_response->payment_type;
        }
    } catch (Exception $e) {
        error_log("Midtrans status check failed for order $order_id: " . $e->getMessage());
    }
}

// Redirect if status is failed
if ($order['status'] === 'failed') {
    header("Location: menu.php?payment=error");
    exit;
}

// Redirect if status is done
if ($order['status'] === 'done') {
    header("Location: done.php");
    exit;
}

// Ambil order items
$stmt = $conn->prepare("
    SELECT oi.jumlah, oi.subtotal, m.nama_menu, m.gambar 
    FROM order_items oi 
    JOIN menu m ON oi.menu_id = m.menu_id 
    WHERE oi.order_id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();

$order_items = [];
while ($row = $result->fetch_assoc()) {
    $order_items[] = $row;
}
$stmt->close();

// Estimasi waktu (contoh: 15 menit)
$estimated_minutes = 15;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Sedang Dimasak - ZidanKitchen</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            padding-bottom: 20px;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        @keyframes slideIn {
            from { transform: translateX(-100%); }
            to { transform: translateX(0); }
        }
        @keyframes fall {
            0% { transform: translateY(-100px); opacity: 1; }
            100% { transform: translateY(100px); opacity: 0; }
        }
        .animate-pulse-custom { animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite; }
        .animate-bounce-custom { animation: bounce 1.5s ease-in-out infinite; }
        .animate-slide-in { animation: slideIn 0.5s ease-out forwards; }
        .animate-fall { animation: fall 2s ease-in infinite; }
        .food-item { transition: all 0.3s ease; }
        .food-item:hover { transform: scale(1.05); }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <div class="bg-blue-600 py-4 px-4 shadow-sm sticky top-0 z-10">
        <div class="flex items-center justify-center">
            <h1 class="text-xl font-bold text-white">Pesanan Sedang Dimasak</h1>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-6 max-w-md">
        <!-- Order Status -->
        <div class="bg-blue-100 rounded-xl p-4 mb-4 shadow-sm border border-blue-200 text-center animate-slide-in">
            <div class="flex items-center justify-between mb-2">
                <span class="font-medium text-blue-800">Order #ZK-<?= str_pad($order_id, 5, '0', STR_PAD_LEFT) ?></span>
                <div class="bg-blue-200 text-blue-800 px-2 py-1 rounded-full text-xs font-medium">
                    Dalam Proses
                </div>
            </div>
            <p class="text-gray-600 text-sm">Meja <?= htmlspecialchars((string)$meja_id) ?></p>
            <p class="text-gray-600 mt-2">Estimasi selesai dalam <span class="font-bold"><?= $estimated_minutes ?> menit</span></p>
            <button id="refresh-status" class="text-blue-600 hover:underline text-sm mt-2">Refresh Status</button>
        </div>

        <!-- Kitchen Animation -->
        <div class="bg-white rounded-xl p-4 mb-4 shadow-sm border border-gray-200">
            <h3 class="font-bold text-gray-800 mb-3">Dapur Kami Sedang Bekerja</h3>
            <div class="relative h-48 bg-gray-100 rounded-lg overflow-hidden flex items-center justify-center">
                <!-- Chef -->
                <div class="absolute left-1/4 bottom-0 w-16 h-24 flex flex-col items-center">
                    <div class="w-8 h-8 bg-white border-2 border-gray-300 rounded-full mb-1 z-10">
                        <div class="w-4 h-4 bg-black rounded-full absolute top-1 left-1/2 transform -translate-x-1/2"></div>
                    </div>
                    <div class="w-10 h-10 bg-white border-2 border-gray-300 rounded-t-full rounded-b-sm flex items-center justify-center">
                        <div class="w-6 h-6 bg-red-500 rounded-full"></div>
                    </div>
                    <div class="w-12 h-4 bg-gray-700 rounded-full absolute top-0 animate-bounce-custom"></div>
                </div>
                <!-- Cooking Pan -->
                <div class="absolute left-1/2 transform -translate-x-1/2 bottom-0 w-24 h-24">
                    <div class="relative w-full h-full">
                        <div class="absolute bottom-0 left-1/2 transform -translate-x-1/2 w-20 h-8 bg-gray-600 rounded-t-lg border-2 border-gray-700"></div>
                        <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 w-16 h-4 bg-orange-500 rounded-full animate-pulse-custom"></div>
                        <div class="absolute bottom-12 left-1/2 transform -translate-x-1/2 flex space-x-2">
                            <div class="w-2 h-6 bg-orange-300 opacity-70 rounded-full animate-bounce-custom" style="animation-delay: 0.1s"></div>
                            <div class="w-2 h-8 bg-orange-400 opacity-70 rounded-full animate-bounce-custom" style="animation-delay: 0.3s"></div>
                            <div class="w-2 h-6 bg-orange-300 opacity-70 rounded-full animate-bounce-custom" style="animation-delay: 0.2s"></div>
                        </div>
                    </div>
                </div>
                <!-- Ingredients -->
                <div class="absolute top-4 left-8 w-6 h-6 bg-yellow-300 rounded-full animate-fall"></div>
                <div class="absolute top-8 right-12 w-5 h-5 bg-red-400 rounded-full animate-fall" style="animation-delay: 0.4s"></div>
                <div class="absolute top-2 right-1/4 w-4 h-4 bg-green-400 rounded-full animate-fall" style="animation-delay: 0.3s"></div>
            </div>
        </div>

        <!-- Order Items -->
        <div class="bg-white rounded-xl p-4 mb-4 shadow-sm border border-gray-200">
            <h3 class="font-bold text-gray-800 mb-3">Detail Pesanan Anda</h3>
            <div class="space-y-4">
                <?php if (empty($order_items)): ?>
                    <p class="text-gray-500 text-center">Tidak ada item pesanan.</p>
                <?php else: ?>
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
                                <p class="text-sm text-gray-500">
                                    <?= $item['jumlah'] ?>x @ Rp <?= number_format($item['subtotal'] / $item['jumlah'], 0, ',', '.') ?>
                                </p>
                            </div>
                            <div class="flex items-center">
                                <div class="w-3 h-3 bg-blue-500 rounded-full mr-2 animate-pulse-custom"></div>
                                <span class="text-sm font-medium text-gray-700">Memasak</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Total Harga -->
        <div class="bg-green-50 rounded-xl p-4 mb-4 shadow-sm border border-green-200">
            <div class="flex justify-between items-center">
                <span class="font-bold text-gray-800">Total Pembayaran</span>
                <span class="font-bold text-green-600 text-xl">Rp <?= number_format($order['total_harga'], 0, ',', '.') ?></span>
            </div>
            <div class="flex justify-between items-center mt-2">
                <span class="font-medium text-gray-700">Metode Pembayaran</span>
                <span class="font-medium text-gray-800"><?= htmlspecialchars($order['metode_pembayaran'] ?? 'Tidak diketahui') ?></span>
            </div>
        </div>
    </div>

    <script>
        function checkStatus() {
            const refreshButton = document.getElementById('refresh-status');
            if (!refreshButton) return;

            refreshButton.innerHTML = 'Memuat...';
            refreshButton.classList.add('opacity-50', 'cursor-not-allowed');

            fetch('check-status.php?order_id=<?= $order_id ?>')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'done') {
                        window.location = 'done.php'; // Redirect ke done.php
                    }
                })
                .catch(error => {
                    console.error('Error checking status:', error);
                })
                .finally(() => {
                    refreshButton.innerHTML = 'Refresh Status';
                    refreshButton.classList.remove('opacity-50', 'cursor-not-allowed');
                });
        }

        // Run checkStatus immediately on page load
        checkStatus();
        // Poll every 5 seconds
        setInterval(checkStatus, 5000);
        // Bind checkStatus to refresh button
        document.getElementById('refresh-status').addEventListener('click', checkStatus);
    </script>
</body>
</html>