<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}

include '../config/db.php';
date_default_timezone_set('Asia/Jakarta');

// Get orders data (pending/paid)
$result = $conn->query("
    SELECT * FROM orders 
    WHERE status IN ('paid', 'pending') 
    ORDER BY created_at ASC
");

// Query for recently completed orders (done)
$completed_orders = $conn->query("
    SELECT o.*, c.name AS customer_name 
    FROM orders o
    LEFT JOIN meja m ON o.id_meja = m.id_meja
    LEFT JOIN customers c ON o.customer_id = c.id
    WHERE o.status = 'done'
    ORDER BY o.created_at DESC
    LIMIT 5
");
if ($completed_orders === false) {
    die("Error executing query for completed orders: " . $conn->error);
}

// Query for failed orders
$failed_orders = $conn->query("
    SELECT o.*, c.name AS customer_name 
    FROM orders o
    LEFT JOIN meja m ON o.id_meja = m.id_meja
    LEFT JOIN customers c ON o.customer_id = c.id
    WHERE o.status = 'failed'
    ORDER BY o.created_at DESC
    LIMIT 5
");
if ($failed_orders === false) {
    die("Error executing query for failed orders: " . $conn->error);
}

// Statistics
$total_pendapatan = $conn->query("SELECT IFNULL(SUM(total_harga), 0) AS total FROM orders WHERE status='done'")->fetch_assoc()['total'];
$total_pesanan = $conn->query("SELECT COUNT(id) AS total FROM orders")->fetch_assoc()['total'];
$pesanan_hari_ini = $conn->query("SELECT COUNT(id) AS total FROM orders WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['total'];
$pendapatan_hari_ini = $conn->query("SELECT IFNULL(SUM(total_harga), 0) AS total FROM orders WHERE status='done' AND DATE(created_at) = CURDATE()")->fetch_assoc()['total'];

// Get recent 5 menu items
$popular_menu = $conn->query("
    SELECT m.nama_menu, COUNT(oi.id_menu) as jumlah 
    FROM order_items oi 
    JOIN menu m ON oi.id_menu = m.id_menu 
    GROUP BY oi.id_menu 
    ORDER BY jumlah DESC 
    LIMIT 5
");

// Generate data for the last 4 weeks (weekly aggregation)
$weeks = [];
$week_labels = [];
$week_date_ranges = [];
$weekly_orders_data = [];
$weekly_revenue_data = [];
$start_date = new DateTime();
$start_date->modify('-27 days'); // 4 weeks (28 days) including today
for ($i = 0; $i < 4; $i++) {
    $week_start = clone $start_date;
    $week_start->modify("+$i weeks");
    $week_end = clone $week_start;
    $week_end->modify("+6 days");
    $weeks[] = [
        'label' => "Minggu " . ($i + 1),
        'date_range' => "(" . $week_start->format('d M') . " - " . $week_end->format('d M') . ")",
        'start' => $week_start->format('Y-m-d'),
        'end' => $week_end->format('Y-m-d')
    ];
    $week_labels[] = "Minggu " . ($i + 1);
    $week_date_ranges[] = $week_start->format('d M') . " - " . $week_end->format('d M');
}

// Query orders and revenue for the last 4 weeks
$weekly_orders_query = $conn->query("
    SELECT 
        WEEK(created_at, 1) AS week_num, 
        COUNT(id) AS count,
        SUM(CASE WHEN status='done' THEN total_harga ELSE 0 END) AS revenue
    FROM orders 
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 28 DAY)
    GROUP BY WEEK(created_at, 1), YEAR(created_at)
    ORDER BY YEAR(created_at), WEEK(created_at, 1)
");
$orders_by_week = [];
while ($row = $weekly_orders_query->fetch_assoc()) {
    $orders_by_week[$row['week_num']] = [
        'count' => $row['count'],
        'revenue' => $row['revenue']
    ];
}

// Merge with weeks array to ensure all 4 weeks are represented
foreach ($weeks as $index => $week) {
    $week_found = false;
    foreach ($orders_by_week as $week_num => $data) {
        $week_start = new DateTime($week['start']);
        $week_num_from_date = $week_start->format('W');
        if ($week_num == $week_num_from_date) {
            $weekly_orders_data[] = $data['count'];
            $weekly_revenue_data[] = $data['revenue'];
            $week_found = true;
            break;
        }
    }
    if (!$week_found) {
        $weekly_orders_data[] = 0;
        $weekly_revenue_data[] = 0;
    }
}

// Data for mini-sparklines in stats cards (last 7 days)
$sparkline_orders = $conn->query("
    SELECT DATE(created_at) AS date, COUNT(id) AS count
    FROM orders 
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at)
    ORDER BY DATE(created_at)
");
$start_date_7days = new DateTime();
$start_date_7days->modify('-6 days');
$sparkline_orders_data = array_fill(0, 7, 0);
while ($row = $sparkline_orders->fetch_assoc()) {
    $index = (new DateTime($row['date']))->diff($start_date_7days)->days;
    if ($index >= 0 && $index < 7) {
        $sparkline_orders_data[$index] = $row['count'];
    }
}

$sparkline_revenue = $conn->query("
    SELECT DATE(created_at) AS date, SUM(total_harga) AS total
    FROM orders 
    WHERE status='done' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at)
    ORDER BY DATE(created_at)
");
$sparkline_revenue_data = array_fill(0, 7, 0);
while ($row = $sparkline_revenue->fetch_assoc()) {
    $index = (new DateTime($row['date']))->diff($start_date_7days)->days;
    if ($index >= 0 && $index < 7) {
        $sparkline_revenue_data[$index] = $row['total'];
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - ZidanKitchen</title>
    <link rel="icon" type="image/x-icon" href="../assets/images/logo_oren.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .pending { background-color: #fef3c7; color: #92400e; }
        .paid { background-color: #d1fae5; color: #065f46; }
        .done { background-color: #dbeafe; color: #1e40af; }
        .failed { background-color: #fee2e2; color: #991b1b; }
        table { table-layout: auto; }
        th, td { white-space: nowrap; }
        .sparkline-canvas {
            width: 80px !important;
            height: 30px !important;
        }
        .alert-dismissible {
            position: relative;
            padding-right: 3rem;
        }
        .alert-dismissible .close-btn {
            position: absolute;
            top: 50%;
            right: 1rem;
            transform: translateY(-50%);
            cursor: pointer;
            font-size: 1.25rem;
            color: inherit;
            opacity: 0.7;
        }
        .alert-dismissible .close-btn:hover {
            opacity: 1;
        }
        .sort-btn {
            cursor: pointer;
            padding: 0 0.25rem;
        }
        .sort-btn.active i {
            color: #f97316;
        }
        .chart-container {
            height: 300px; /* Fixed height for charts */
        }
        .week-dates {
            margin-top: 8px;
            font-size: 0.85rem;
            color: #6b7280;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex">
    <!-- Sidebar -->
    <div class="h-screen w-64 bg-gradient-to-b from-orange-600 to-yellow-900 text-white p-5 shadow-lg fixed flex flex-col">
        <div class="text-center mb-8 pt-4">
            <h2 class="text-2xl font-bold mb-2">Admin Panel</h2>
            <div class="w-16 h-1 bg-orange-300 mx-auto rounded-full"></div>
        </div>
        <nav class="flex-1">
            <ul class="space-y-2">
                <li>
                    <a href="dashboard.php" class="flex items-center p-3 rounded-lg bg-orange-500 transition-colors">
                        <i class="fas fa-tachometer-alt mr-3"></i> Dashboard
                    </a>
                </li>
                <li>
                    <a href="menu.php" class="flex items-center p-3 rounded-lg hover:bg-orange-500 transition-colors">
                        <i class="fas fa-utensils mr-3"></i> Kelola Menu
                    </a>
                </li>
                <li>
                    <a href="promos.php" class="flex items-center p-3 rounded-lg hover:bg-orange-500 transition-colors">
                        <i class="fas fa-tags mr-3"></i> Kelola Promo
                    </a>
                </li>
                <li>
                    <a href="order.php" class="flex items-center p-3 rounded-lg hover:bg-orange-500 transition-colors">
                        <i class="fas fa-receipt mr-3"></i> Kelola Pesanan
                    </a>
                </li>
            </ul>
        </nav>
        <a href="logout.php" class="flex items-center p-3 rounded-lg bg-red-600 hover:bg-red-700 transition-colors">
            <i class="fas fa-sign-out-alt mr-3"></i> Logout
        </a>
    </div>

    <!-- Content -->
    <div class="flex-1 ml-64 p-8">
        <!-- Notification Messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded alert-dismissible" id="success-alert">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    <p><?= htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?></p>
                    <span class="close-btn" onclick="dismissAlert('success-alert')">×</span>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded alert-dismissible" id="error-alert">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <p><?= htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?></p>
                    <span class="close-btn" onclick="dismissAlert('error-alert')">×</span>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Header Dashboard -->
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-orange-600">
                <i class="fas fa-tachometer-alt mr-2"></i> Dashboard Admin
            </h1>
            <div class="flex items-center space-x-3">
                <button id="refresh-btn" class="bg-orange-600 text-white px-4 py-2 rounded-lg hover:bg-orange-700 transition flex items-center">
                    <i class="fas fa-sync-alt mr-2"></i> Refresh
                </button>
                <input type="date" id="date-filter" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500" value="<?= date('Y-m-d') ?>">
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-md p-6 transition-all duration-300 card-hover">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Total Pendapatan</p>
                        <h3 class="text-2xl font-bold text-orange-600">Rp <?= number_format($total_pendapatan, 0, ',', '.') ?></h3>
                    </div>
                    <div class="p-3 rounded-full bg-orange-100 text-orange-600">
                        <i class="fas fa-wallet text-xl"></i>
                    </div>
                </div>
                <div class="mt-4 pt-4 border-t border-gray-100 flex items-center justify-between">
                    <p class="text-sm text-gray-500 flex items-center">
                        <span class="text-green-500 mr-1"><i class="fas fa-arrow-up"></i> Rp <?= number_format($pendapatan_hari_ini, 0, ',', '.') ?></span>
                        <span>hari ini</span>
                    </p>
                    <canvas class="sparkline-canvas" id="sparkline-revenue"></canvas>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6 transition-all duration-300 card-hover">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Total Pesanan</p>
                        <h3 class="text-2xl font-bold text-orange-600"><?= number_format($total_pesanan, 0, ',', '.') ?></h3>
                    </div>
                    <div class="p-3 rounded-full bg-orange-100 text-orange-600">
                        <i class="fas fa-receipt text-xl"></i>
                    </div>
                </div>
                <div class="mt-4 pt-4 border-t border-gray-100 flex items-center justify-between">
                    <p class="text-sm text-gray-500 flex items-center">
                        <span class="text-green-500 mr-1"><i class="fas fa-arrow-up"></i> <?= $pesanan_hari_ini ?></span>
                        <span>hari ini</span>
                    </p>
                    <canvas class="sparkline-canvas" id="sparkline-orders"></canvas>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6 transition-all duration-300 card-hover">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Pesanan Masuk</p>
                        <h3 class="text-2xl font-bold text-orange-600"><?= $result->num_rows ?></h3>
                    </div>
                    <div class="p-3 rounded-full bg-orange-100 text-orange-600">
                        <i class="fas fa-clock text-xl"></i>
                    </div>
                </div>
                <div class="mt-4 pt-4 border-t border-gray-100">
                    <p class="text-sm text-gray-500">
                        <?= $conn->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetch_row()[0] ?> pending
                    </p>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6 transition-all duration-300 card-hover">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Menu Terpopuler</p>
                        <h3 class="text-2xl font-bold text-orange-600 truncate">
                            <?= $popular_menu->num_rows > 0 ? htmlspecialchars($popular_menu->fetch_assoc()['nama_menu']) : '-' ?>
                        </h3>
                    </div>
                    <div class="p-3 rounded-full bg-orange-100 text-orange-600">
                        <i class="fas fa-utensils text-xl"></i>
                    </div>
                </div>
                <div class="mt-4 pt-4 border-t border-gray-100">
                    <p class="text-sm text-gray-500">
                        Lihat semua di <a href="menu.php" class="text-orange-500 hover:underline">Kelola Menu</a>
                    </p>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Orders Chart -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Statistik Pesanan 4 Minggu Terakhir</h3>
                <div class="chart-container">
                    <canvas id="ordersChart"></canvas>
                </div>
                <div class="week-dates">
                    <?php 
                    foreach ($week_date_ranges as $index => $range) {
                        echo "Minggu " . ($index + 1) . ": " . $range . ($index < count($week_date_ranges) - 1 ? " | " : "");
                    }
                    ?>
                </div>
            </div>
            
            <!-- Revenue Chart -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Pendapatan 4 Minggu Terakhir</h3>
                <div class="chart-container">
                    <canvas id="revenueChart"></canvas>
                </div>
                <div class="week-dates">
                    <?php 
                    foreach ($week_date_ranges as $index => $range) {
                        echo "Minggu " . ($index + 1) . ": " . $range . ($index < count($week_date_ranges) - 1 ? " | " : "");
                    }
                    ?>
                </div>
            </div>
        </div>

        <!-- Recent Completed and Failed Orders -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Recent Completed Orders -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="text-xl font-semibold text-gray-800 flex items-center">
                        <i class="fas fa-check-circle mr-2 text-green-500"></i> Pesanan Selesai Terakhir
                    </h3>
                </div>
                <div>
                    <table class="min-w-full table-auto" id="completed-orders-table">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    ID
                                    <span class="sort-btn" onclick="sortTable('completed-orders-table', 0, 'asc')"><i class="fas fa-arrow-up"></i></span>
                                    <span class="sort-btn" onclick="sortTable('completed-orders-table', 0, 'desc')"><i class="fas fa-arrow-down"></i></span>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Meja</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Total
                                    <span class="sort-btn" onclick="sortTable('completed-orders-table', 4, 'asc')"><i class="fas fa-arrow-up"></i></span>
                                    <span class="sort-btn" onclick="sortTable('completed-orders-table', 4, 'desc')"><i class="fas fa-arrow-down"></i></span>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Metode</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Waktu
                                    <span class="sort-btn" onclick="sortTable('completed-orders-table', 6, 'asc')"><i class="fas fa-arrow-up"></i></span>
                                    <span class="sort-btn" onclick="sortTable('completed-orders-table', 6, 'desc')"><i class="fas fa-arrow-down"></i></span>
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php 
                            $completed_orders->data_seek(0); // Reset pointer
                            while ($row = $completed_orders->fetch_assoc()): ?>
                                <tr class="hover:bg-green-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#<?= $row['id'] ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= $row['customer_name'] ?? '-' ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= $row['id_meja'] ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Rp <?= number_format($row['total_harga'], 0, ',', '.') ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= $row['metode_pembayaran'] ? ucfirst($row['metode_pembayaran']) : '-' ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= date('d/m H:i', strtotime($row['created_at'])) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a href="detail_pesanan.php?id=<?= $row['id'] ?>" class="text-orange-600 hover:text-orange-900 mr-3">
                                            <i class="fas fa-eye"></i> Detail
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Failed Orders -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="text-xl font-semibold text-gray-800 flex items-center">
                        <i class="fas fa-times-circle mr-2 text-red-500"></i> Pesanan Gagal Terakhir
                    </h3>
                </div>
                <div>
                    <table class="min-w-full table-auto" id="failed-orders-table">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    ID
                                    <span class="sort-btn" onclick="sortTable('failed-orders-table', 0, 'asc')"><i class="fas fa-arrow-up"></i></span>
                                    <span class="sort-btn" onclick="sortTable('failed-orders-table', 0, 'desc')"><i class="fas fa-arrow-down"></i></span>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Meja</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Total
                                    <span class="sort-btn" onclick="sortTable('failed-orders-table', 3, 'asc')"><i class="fas fa-arrow-up"></i></span>
                                    <span class="sort-btn" onclick="sortTable('failed-orders-table', 3, 'desc')"><i class="fas fa-arrow-down"></i></span>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Metode</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Waktu
                                    <span class="sort-btn" onclick="sortTable('failed-orders-table', 5, 'asc')"><i class="fas fa-arrow-up"></i></span>
                                    <span class="sort-btn" onclick="sortTable('failed-orders-table', 5, 'desc')"><i class="fas fa-arrow-down"></i></span>
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php 
                            $failed_orders->data_seek(0); // Reset pointer
                            while ($row = $failed_orders->fetch_assoc()): ?>
                                <tr class="hover:bg-red-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#<?= $row['id'] ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= $row['customer_name'] ?? '-' ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= $row['id_meja'] ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Rp <?= number_format($row['total_harga'], 0, ',', '.') ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= $row['metode_pembayaran'] ? ucfirst($row['metode_pembayaran']) : '-' ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= date('d/m H:i', strtotime($row['created_at'])) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a href="detail_pesanan.php?id=<?= $row['id'] ?>" class="text-orange-600 hover:text-orange-900 mr-3">
                                            <i class="fas fa-eye"></i> Detail
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Dismiss alert
        function dismissAlert(alertId) {
            const alert = document.getElementById(alertId);
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }

        // Auto-dismiss alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert-dismissible');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);

        // Refresh button
        document.getElementById('refresh-btn').addEventListener('click', () => {
            location.reload();
        });

        // Date filter (placeholder functionality)
        document.getElementById('date-filter').addEventListener('change', (e) => {
            alert('Filter tanggal belum diimplementasikan. Tanggal yang dipilih: ' + e.target.value);
            // Tambah logika untuk filter data berdasarkan tanggal di sini
        });

        // Chart data
        const weeks = <?= json_encode($week_labels) ?>;
        const weekDateRanges = <?= json_encode($week_date_ranges) ?>;
        const ordersData = <?= json_encode($weekly_orders_data) ?>;
        const revenueData = <?= json_encode($weekly_revenue_data) ?>;

        // Orders Chart
        const ordersCtx = document.getElementById('ordersChart').getContext('2d');
        new Chart(ordersCtx, {
            type: 'line',
            data: {
                labels: weeks,
                datasets: [{
                    label: 'Pesanan',
                    data: ordersData,
                    backgroundColor: 'rgba(249, 115, 22, 0.2)',
                    borderColor: 'rgba(249, 115, 22, 1)',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true,
                    pointRadius: 3,
                    pointHoverRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleFont: { size: 14 },
                        bodyFont: { size: 12 },
                        callbacks: {
                            title: function(context) {
                                const index = context[0].dataIndex;
                                return weeks[index] + ' ' + weekDateRanges[index];
                            },
                            label: function(context) {
                                return `Pesanan: ${context.raw}`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { 
                            color: '#6b7280',
                            maxRotation: 0,
                            minRotation: 0
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0, 0, 0, 0.05)' },
                        ticks: { color: '#6b7280' }
                    }
                },
                animation: false // Disable animation for better performance
            }
        });

        // Revenue Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: weeks,
                datasets: [{
                    label: 'Pendapatan (Rp)',
                    data: revenueData,
                    backgroundColor: 'rgba(16, 185, 129, 0.2)',
                    borderColor: 'rgba(16, 185, 129, 1)',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true,
                    pointRadius: 3,
                    pointHoverRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleFont: { size: 14 },
                        bodyFont: { size: 12 },
                        callbacks: {
                            title: function(context) {
                                const index = context[0].dataIndex;
                                return weeks[index] + ' ' + weekDateRanges[index];
                            },
                            label: function(context) {
                                return `Pendapatan: Rp ${context.raw.toLocaleString('id-ID')}`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { 
                            color: '#6b7280',
                            maxRotation: 0,
                            minRotation: 0
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0, 0, 0, 0.05)' },
                        ticks: {
                            color: '#6b7280',
                            callback: function(value) {
                                return 'Rp ' + value.toLocaleString('id-ID');
                            }
                        }
                    }
                },
                animation: false // Disable animation for better performance
            }
        });

        // Sparkline for Orders
        const sparklineOrdersCtx = document.getElementById('sparkline-orders').getContext('2d');
        new Chart(sparklineOrdersCtx, {
            type: 'line',
            data: {
                labels: Array(7).fill(''),
                datasets: [{
                    data: <?= json_encode($sparkline_orders_data) ?>,
                    borderColor: 'rgba(249, 115, 22, 1)',
                    borderWidth: 1,
                    fill: false,
                    pointRadius: 0
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false }, tooltip: { enabled: false } },
                scales: { x: { display: false }, y: { display: false } }
            }
        });

        // Sparkline for Revenue
        const sparklineRevenueCtx = document.getElementById('sparkline-revenue').getContext('2d');
        new Chart(sparklineRevenueCtx, {
            type: 'line',
            data: {
                labels: Array(7).fill(''),
                datasets: [{
                    data: <?= json_encode($sparkline_revenue_data) ?>,
                    borderColor: 'rgba(16, 185, 129, 1)',
                    borderWidth: 1,
                    fill: false,
                    pointRadius: 0
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false }, tooltip: { enabled: false } },
                scales: { x: { display: false }, y: { display: false } }
            }
        });

        // Table sorting
        function sortTable(tableId, colIndex, sortOrder) {
            const table = document.getElementById(tableId);
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));

            rows.sort((a, b) => {
                let aValue = a.cells[colIndex].innerText;
                let bValue = b.cells[colIndex].innerText;

                // Special handling for "Total" (currency) and "Waktu" (date)
                if (colIndex === 4 || colIndex === 3) { // Total (completed: 4, failed: 3)
                    aValue = parseFloat(aValue.replace(/[^0-9]/g, ''));
                    bValue = parseFloat(bValue.replace(/[^0-9]/g, ''));
                } else if (colIndex === 6 || colIndex === 5) { // Waktu (completed: 6, failed: 5)
                    aValue = new Date(aValue.split('/').reverse().join('-') + ' ' + aValue.split(' ')[1]).getTime();
                    bValue = new Date(bValue.split('/').reverse().join('-') + ' ' + bValue.split(' ')[1]).getTime();
                } else if (colIndex === 0) { // ID
                    aValue = parseInt(aValue.replace('#', ''));
                    bValue = parseInt(bValue.replace('#', ''));
                }

                return sortOrder === 'asc' ? aValue - bValue : bValue - aValue;
            });

            tbody.innerHTML = '';
            rows.forEach(row => tbody.appendChild(row));

            // Update sort button styles
            const sortButtons = table.querySelectorAll(`th:nth-child(${colIndex + 1}) .sort-btn`);
            sortButtons.forEach(btn => btn.classList.remove('active'));
            const activeBtn = table.querySelector(`th:nth-child(${colIndex + 1}) .sort-btn[onclick*="${sortOrder}"]`);
            activeBtn.classList.add('active');
        }
    </script>
</body>
</html>