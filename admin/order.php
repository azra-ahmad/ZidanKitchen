<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}

include '../config/db.php';
date_default_timezone_set('Asia/Jakarta');

// Pagination setup
$items_per_page = 5;

// Search and Filter Parameters
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status_filter']) ? $_GET['status_filter'] : 'all';

// Current Orders (pending/paid)
$current_page_current = isset($_GET['page_current']) ? (int)$_GET['page_current'] : 1;
$offset_current = ($current_page_current - 1) * $items_per_page;

// Build the WHERE clause for Current Orders
$where_clause_current = "WHERE o.status IN ('pending', 'paid')";
if ($status_filter !== 'all') {
    $status_filter = $conn->real_escape_string($status_filter);
    $where_clause_current .= " AND o.status = '$status_filter'";
}
if ($search_query) {
    $search_query = $conn->real_escape_string($search_query);
    $where_clause_current .= " AND (o.id LIKE '%$search_query%' 
                            OR o.midtrans_order_id LIKE '%$search_query%' 
                            OR c.name LIKE '%$search_query%')";
}

$total_current_query = $conn->query("
    SELECT COUNT(*) 
    FROM orders o
    LEFT JOIN customers c ON o.customer_id = c.id
    $where_clause_current
");
$total_current = $total_current_query->fetch_row()[0];
$total_pages_current = ceil($total_current / $items_per_page);

$current_orders_query = "
    SELECT o.*, c.name AS customer_name 
    FROM orders o
    LEFT JOIN meja m ON o.id_meja = m.id_meja
    LEFT JOIN customers c ON o.customer_id = c.id
    $where_clause_current
    ORDER BY o.created_at DESC
    LIMIT $items_per_page OFFSET $offset_current
";
$current_orders = $conn->query($current_orders_query);
if ($current_orders === false) {
    die("Error executing query for current orders: " . $conn->error);
}

// Completed Orders (done)
$current_page_completed = isset($_GET['page_completed']) ? (int)$_GET['page_completed'] : 1;
$offset_completed = ($current_page_completed - 1) * $items_per_page;

// Build the WHERE clause for Completed Orders
$where_clause_completed = "WHERE o.status = 'done'";
if ($search_query) {
    $where_clause_completed .= " AND (o.id LIKE '%$search_query%' 
                               OR o.midtrans_order_id LIKE '%$search_query%' 
                               OR c.name LIKE '%$search_query%')";
}

$total_completed_query = $conn->query("
    SELECT COUNT(*) 
    FROM orders o
    LEFT JOIN customers c ON o.customer_id = c.id
    $where_clause_completed
");
$total_completed = $total_completed_query->fetch_row()[0];
$total_pages_completed = ceil($total_completed / $items_per_page);

$completed_orders_query = "
    SELECT o.*, c.name AS customer_name 
    FROM orders o
    LEFT JOIN meja m ON o.id_meja = m.id_meja
    LEFT JOIN customers c ON o.customer_id = c.id
    $where_clause_completed
    ORDER BY o.created_at DESC
    LIMIT $items_per_page OFFSET $offset_completed
";
$completed_orders = $conn->query($completed_orders_query);
if ($completed_orders === false) {
    die("Error executing query for completed orders: " . $conn->error);
}

// Failed Orders
$current_page_failed = isset($_GET['page_failed']) ? (int)$_GET['page_failed'] : 1;
$offset_failed = ($current_page_failed - 1) * $items_per_page;

// Build the WHERE clause for Failed Orders
$where_clause_failed = "WHERE o.status = 'failed'";
if ($search_query) {
    $where_clause_failed .= " AND (o.id LIKE '%$search_query%' 
                             OR o.midtrans_order_id LIKE '%$search_query%' 
                             OR c.name LIKE '%$search_query%')";
}

$total_failed_query = $conn->query("
    SELECT COUNT(*) 
    FROM orders o
    LEFT JOIN customers c ON o.customer_id = c.id
    $where_clause_failed
");
$total_failed = $total_failed_query->fetch_row()[0];
$total_pages_failed = ceil($total_failed / $items_per_page);

$failed_orders_query = "
    SELECT o.*, c.name AS customer_name 
    FROM orders o
    LEFT JOIN meja m ON o.id_meja = m.id_meja
    LEFT JOIN customers c ON o.customer_id = c.id
    $where_clause_failed
    ORDER BY o.created_at DESC
    LIMIT $items_per_page OFFSET $offset_failed
";
$failed_orders = $conn->query($failed_orders_query);
if ($failed_orders === false) {
    die("Error executing query for failed orders: " . $conn->error);
}

// Function to build URL with parameters
function buildUrl($params) {
    $query = http_build_query(array_merge($_GET, $params));
    return '?' . $query;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pesanan - ZidanKitchen</title>
    <link rel="icon" type="image/x-icon" href="../assets/images/logo_oren.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.5/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.5/dist/sweetalert2.all.min.js"></script>
    <style>
        body {
            font-size: 0.95rem;
        }
        .main-content {
            margin-left: 16rem;
            padding: 2rem;
            width: calc(100% - 16rem);
            overflow-x: auto;
        }
        .status-badge {
            @apply px-3 py-1 rounded-full text-xs font-medium inline-flex items-center gap-1;
        }
        .status-badge.pending {
            @apply bg-orange-100 text-orange-800;
        }
        .status-badge.paid {
            @apply bg-blue-100 text-blue-800;
        }
        .status-badge.done {
            @apply bg-green-100 text-green-800;
        }
        .status-badge.failed {
            @apply bg-red-100 text-red-800;
        }
        .table-container {
            overflow-x: auto;
            width: 100%;
        }
        table {
            width: 100%;
            table-layout: auto;
        }
        th, td {
            white-space: nowrap;
            padding: 0.75rem 1rem;
        }
        th {
            position: sticky;
            top: 0;
            background-color: #f9fafb;
            z-index: 10;
        }
        .sort-btn {
            cursor: pointer;
            padding: 0 0.25rem;
        }
        .sort-btn.active i {
            color: #f97316;
        }
        .pagination-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            border-top: 1px solid #e5e7eb;
        }
        .pagination-info {
            font-size: 0.875rem;
            color: #6b7280;
        }
        .pagination-info span {
            font-weight: 500;
            color: #111827;
        }
        .pagination-buttons {
            display: flex;
            gap: 0.5rem;
        }
        .pagination-button {
            padding: 0.5rem 1rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.375rem;
            color: #6b7280;
            transition: all 0.2s;
        }
        .pagination-button:hover:not(.disabled) {
            background-color: #f3f4f6;
            color: #374151;
        }
        .pagination-button.active {
            background-color: #f97316;
            color: white;
            border-color: #f97316;
        }
        .pagination-button.disabled {
            color: #d1d5db;
            cursor: not-allowed;
        }
        /* Filter and Search Styles */
        .filter-container {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            align-items: center;
        }
        .filter-select, .search-input {
            padding: 0.5rem 1rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            color: #374151;
            transition: all 0.2s;
        }
        .filter-select:focus, .search-input:focus {
            outline: none;
            border-color: #f97316;
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1);
        }
        .search-input::placeholder {
            color: #9ca3af;
        }
        /* Hover Animation */
        tr {
            transition: background-color 0.3s ease;
        }
        /* SweetAlert2 Custom Styles */
        .swal2-container {
            z-index: 99999 !important;
        }
        .swal2-backdrop-show {
            background: rgba(0, 0, 0, 0.4) !important;
        }
        .swal2-popup {
            animation: fadeIn 0.3s, bounceIn 0.5s;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes bounceIn {
            0% { transform: scale(0.8); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        .swal2-popup.swal2-hide {
            animation: fadeOut 0.3s, bounceOut 0.5s;
        }
        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }
        @keyframes bounceOut {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(0.8); }
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
                    <a href="dashboard.php" class="flex items-center p-3 rounded-lg hover:bg-orange-500 transition-colors">
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
                    <a href="order.php" class="flex items-center p-3 rounded-lg bg-orange-500 transition-colors">
                        <i class="fas fa-clipboard-list mr-3"></i> Kelola Pesanan
                    </a>
                </li>
            </ul>
        </nav>
        <a href="logout.php" class="flex items-center p-3 rounded-lg bg-red-600 hover:bg-red-700 transition-colors">
            <i class="fas fa-sign-out-alt mr-3"></i> Logout
        </a>
    </div>

    <div class="main-content">
        <!-- Header Pesanan -->
        <div class="flex justify-between items-center mb-8 max-w-6xl mx-auto">
            <h1 class="text-3xl font-bold text-orange-600">
                <i class="fas fa-clipboard-list mr-2"></i> Kelola Pesanan
            </h1>
            <div class="text-sm text-gray-500">
                <?= date('l, d F Y') ?>
            </div>
        </div>

        <!-- Current Orders -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden mb-8 max-w-6xl mx-auto">
            <div class="p-6 border-b border-gray-100">
                <h3 class="text-xl font-semibold text-gray-800 flex items-center">
                    <i class="fas fa-clock mr-2 text-orange-500"></i> Pesanan Masuk
                    <span class="ml-auto text-sm font-normal text-gray-500">
                        <?= $total_current ?> pesanan
                    </span>
                </h3>
            </div>
            <!-- Filter and Search -->
            <div class="p-6 border-b border-gray-100">
                <div class="filter-container">
                    <select id="status-filter" class="filter-select">
                        <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>Semua Status</option>
                        <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="paid" <?= $status_filter === 'paid' ? 'selected' : '' ?>>Paid</option>
                    </select>
                    <input type="text" id="search-input" class="search-input" placeholder="Cari ID, Midtrans ID, atau Customer..." value="<?= htmlspecialchars($search_query) ?>">
                </div>
            </div>
            <div class="overflow-x-auto max-w-full">
                <table class="min-w-full table-auto" id="current-orders-table">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                ID
                                <span class="sort-btn" onclick="sortTable('current-orders-table', 0, 'asc')"><i class="fas fa-arrow-up"></i></span>
                                <span class="sort-btn" onclick="sortTable('current-orders-table', 0, 'desc')"><i class="fas fa-arrow-down"></i></span>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Midtrans ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Meja</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Total
                                <span class="sort-btn" onclick="sortTable('current-orders-table', 4, 'asc')"><i class="fas fa-arrow-up"></i></span>
                                <span class="sort-btn" onclick="sortTable('current-orders-table', 4, 'desc')"><i class="fas fa-arrow-down"></i></span>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Metode</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Waktu
                                <span class="sort-btn" onclick="sortTable('current-orders-table', 7, 'asc')"><i class="fas fa-arrow-up"></i></span>
                                <span class="sort-btn" onclick="sortTable('current-orders-table', 7, 'desc')"><i class="fas fa-arrow-down"></i></span>
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php while ($row = $current_orders->fetch_assoc()): ?>
                            <tr class="hover:bg-orange-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#<?= $row['id'] ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= $row['midtrans_order_id'] ?? '-' ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= $row['customer_name'] ?? '-' ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= $row['id_meja'] ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Rp <?= number_format($row['total_harga'], 0, ',', '.') ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= $row['metode_pembayaran'] ? ucfirst($row['metode_pembayaran']) : '-' ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="status-badge <?= $row['status'] ?>">
                                        <i class="fas <?= $row['status'] === 'pending' ? 'fa-hourglass-half' : 'fa-check-circle' ?>"></i>
                                        <?= ucfirst($row['status']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= $row['created_at'] ? date('d/m H:i', strtotime($row['created_at'])) : '-' ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="detail_pesanan.php?id=<?= $row['id'] ?>" class="text-orange-600 hover:text-orange-900 mr-3">
                                        <i class="fas fa-eye"></i> Detail
                                    </a>
                                    <a href="proses_pesanan.php?id=<?= $row['id'] ?>" class="text-green-600 hover:text-green-900 btn-proses">
                                        <i class="fas fa-check"></i> Selesai
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <!-- Pagination for Current Orders -->
            <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                <div class="text-sm text-gray-500">
                    Menampilkan <span class="font-medium"><?= $offset_current + 1 ?></span> sampai 
                    <span class="font-medium"><?= min($offset_current + $items_per_page, $total_current) ?></span> dari 
                    <span class="font-medium"><?= $total_current ?></span> pesanan
                </div>
                <div class="flex space-x-2">
                    <?php if ($current_page_current > 1): ?>
                        <a href="<?= buildUrl(['page_current' => $current_page_current - 1]) ?>" class="px-3 py-1 border rounded-md text-gray-500 hover:bg-gray-50">Sebelumnya</a>
                    <?php else: ?>
                        <span class="px-3 py-1 border rounded-md text-gray-300 cursor-not-allowed">Sebelumnya</span>
                    <?php endif; ?>
                    
                    <?php 
                    $startPage_current = max(1, $current_page_current - 2);
                    $endPage_current = min($total_pages_current, $current_page_current + 2);
                    for ($i = $startPage_current; $i <= $endPage_current; $i++): ?>
                        <a href="<?= buildUrl(['page_current' => $i]) ?>" 
                           class="px-3 py-1 border rounded-md <?= $i == $current_page_current ? 'bg-orange-500 text-white' : 'text-gray-500 hover:bg-gray-50' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($current_page_current < $total_pages_current): ?>
                        <a href="<?= buildUrl(['page_current' => $current_page_current + 1]) ?>" class="px-3 py-1 border rounded-md text-gray-500 hover:bg-gray-50">Selanjutnya</a>
                    <?php else: ?>
                        <span class="px-3 py-1 border rounded-md text-gray-300 cursor-not-allowed">Selanjutnya</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Completed Orders -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden mb-8 max-w-6xl mx-auto">
            <div class="p-6 border-b border-gray-100">
                <h3 class="text-xl font-semibold text-gray-800 flex items-center">
                    <i class="fas fa-check-circle mr-2 text-green-500"></i> Pesanan Selesai
                    <span class="ml-auto text-sm font-normal text-gray-500">
                        <?= $total_completed ?> pesanan
                    </span>
                </h3>
            </div>
            <div class="overflow-x-auto max-w-full">
                <table class="min-w-full table-auto" id="completed-orders-table">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                ID
                                <span class="sort-btn" onclick="sortTable('completed-orders-table', 0, 'asc')"><i class="fas fa-arrow-up"></i></span>
                                <span class="sort-btn" onclick="sortTable('completed-orders-table', 0, 'desc')"><i class="fas fa-arrow-down"></i></span>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Midtrans ID</th>
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
                        <?php while ($row = $completed_orders->fetch_assoc()): ?>
                            <tr class="hover:bg-green-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#<?= $row['id'] ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= $row['midtrans_order_id'] ?? '-' ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= $row['customer_name'] ?? '-' ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= $row['id_meja'] ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Rp <?= number_format($row['total_harga'], 0, ',', '.') ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= $row['metode_pembayaran'] ? ucfirst($row['metode_pembayaran']) : '-' ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= $row['created_at'] ? date('d/m H:i', strtotime($row['created_at'])) : '-' ?>
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
            <!-- Pagination for Completed Orders -->
            <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                <div class="text-sm text-gray-500">
                    Menampilkan <span class="font-medium"><?= $offset_completed + 1 ?></span> sampai 
                    <span class="font-medium"><?= min($offset_completed + $items_per_page, $total_completed) ?></span> dari 
                    <span class="font-medium"><?= $total_completed ?></span> pesanan
                </div>
                <div class="flex space-x-2">
                    <?php if ($current_page_completed > 1): ?>
                        <a href="<?= buildUrl(['page_completed' => $current_page_completed - 1]) ?>" class="px-3 py-1 border rounded-md text-gray-500 hover:bg-gray-50">Sebelumnya</a>
                    <?php else: ?>
                        <span class="px-3 py-1 border rounded-md text-gray-300 cursor-not-allowed">Sebelumnya</span>
                    <?php endif; ?>
                    
                    <?php 
                    $startPage_completed = max(1, $current_page_completed - 2);
                    $endPage_completed = min($total_pages_completed, $current_page_completed + 2);
                    for ($i = $startPage_completed; $i <= $endPage_completed; $i++): ?>
                        <a href="<?= buildUrl(['page_completed' => $i]) ?>" 
                           class="px-3 py-1 border rounded-md <?= $i == $current_page_completed ? 'bg-orange-500 text-white' : 'text-gray-500 hover:bg-gray-50' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($current_page_completed < $total_pages_completed): ?>
                        <a href="<?= buildUrl(['page_completed' => $current_page_completed + 1]) ?>" class="px-3 py-1 border rounded-md text-gray-500 hover:bg-gray-50">Selanjutnya</a>
                    <?php else: ?>
                        <span class="px-3 py-1 border rounded-md text-gray-300 cursor-not-allowed">Selanjutnya</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Failed Orders -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden max-w-6xl mx-auto">
            <div class="p-6 border-b border-gray-100">
                <h3 class="text-xl font-semibold text-gray-800 flex items-center">
                    <i class="fas fa-times-circle mr-2 text-red-500"></i> Pesanan Gagal
                    <span class="ml-auto text-sm font-normal text-gray-500">
                        <?= $total_failed ?> pesanan
                    </span>
                </h3>
            </div>
            <div class="overflow-x-auto max-w-full">
                <table class="min-w-full table-auto" id="failed-orders-table">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                ID
                                <span class="sort-btn" onclick="sortTable('failed-orders-table', 0, 'asc')"><i class="fas fa-arrow-up"></i></span>
                                <span class="sort-btn" onclick="sortTable('failed-orders-table', 0, 'desc')"><i class="fas fa-arrow-down"></i></span>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Midtrans ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Meja</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Total
                                <span class="sort-btn" onclick="sortTable('failed-orders-table', 4, 'asc')"><i class="fas fa-arrow-up"></i></span>
                                <span class="sort-btn" onclick="sortTable('failed-orders-table', 4, 'desc')"><i class="fas fa-arrow-down"></i></span>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Metode</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Waktu
                                <span class="sort-btn" onclick="sortTable('failed-orders-table', 6, 'asc')"><i class="fas fa-arrow-up"></i></span>
                                <span class="sort-btn" onclick="sortTable('failed-orders-table', 6, 'desc')"><i class="fas fa-arrow-down"></i></span>
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php while ($row = $failed_orders->fetch_assoc()): ?>
                            <tr class="hover:bg-red-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#<?= $row['id'] ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= $row['midtrans_order_id'] ?? '-' ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= $row['customer_name'] ?? '-' ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= $row['id_meja'] ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Rp <?= number_format($row['total_harga'], 0, ',', '.') ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= $row['metode_pembayaran'] ? ucfirst($row['metode_pembayaran']) : '-' ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= $row['created_at'] ? date('d/m H:i', strtotime($row['created_at'])) : '-' ?>
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
            <!-- Pagination for Failed Orders -->
            <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                <div class="text-sm text-gray-500">
                    Menampilkan <span class="font-medium"><?= $offset_failed + 1 ?></span> sampai 
                    <span class="font-medium"><?= min($offset_failed + $items_per_page, $total_failed) ?></span> dari 
                    <span class="font-medium"><?= $total_failed ?></span> pesanan
                </div>
                <div class="flex space-x-2">
                    <?php if ($current_page_failed > 1): ?>
                        <a href="<?= buildUrl(['page_failed' => $current_page_failed - 1]) ?>" class="px-3 py-1 border rounded-md text-gray-500 hover:bg-gray-50">Sebelumnya</a>
                    <?php else: ?>
                        <span class="px-3 py-1 border rounded-md text-gray-300 cursor-not-allowed">Sebelumnya</span>
                    <?php endif; ?>
                    
                    <?php 
                    $startPage_failed = max(1, $current_page_failed - 2);
                    $endPage_failed = min($total_pages_failed, $current_page_failed + 2);
                    for ($i = $startPage_failed; $i <= $endPage_failed; $i++): ?>
                        <a href="<?= buildUrl(['page_failed' => $i]) ?>" 
                           class="px-3 py-1 border rounded-md <?= $i == $current_page_failed ? 'bg-orange-500 text-white' : 'text-gray-500 hover:bg-gray-50' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($current_page_failed < $total_pages_failed): ?>
                        <a href="<?= buildUrl(['page_failed' => $current_page_failed + 1]) ?>" class="px-3 py-1 border rounded-md text-gray-500 hover:bg-gray-50">Selanjutnya</a>
                    <?php else: ?>
                        <span class="px-3 py-1 border rounded-md text-gray-300 cursor-not-allowed">Selanjutnya</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Add confirmation for completing orders using SweetAlert2
        document.querySelectorAll('.btn-proses').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const href = this.getAttribute('href');

                Swal.fire({
                    title: 'Konfirmasi Penyelesaian Pesanan',
                    text: "Apakah Anda yakin ingin menyelesaikan pesanan ini?",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#f97316',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Ya, Selesaikan!',
                    cancelButtonText: 'Batal',
                    allowOutsideClick: false,
                    allowEscapeKey: true,
                    allowEnterKey: false,
                    stopKeydownPropagation: false,
                    backdrop: `
                        rgba(249, 115, 22, 0.2)
                        left top
                        no-repeat
                    `,
                    didClose: () => {
                        document.querySelector('.swal2-container')?.remove();
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = href;
                    }
                });
            });
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
                if (colIndex === 4) { // Total (all tables)
                    aValue = parseFloat(aValue.replace(/[^0-9]/g, ''));
                    bValue = parseFloat(bValue.replace(/[^0-9]/g, ''));
                } else if (colIndex === 7 || colIndex === 6) { // Waktu (current: 7, completed/failed: 6)
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

        // Filter and Search Handling
        const statusFilter = document.getElementById('status-filter');
        const searchInput = document.getElementById('search-input');

        function updateUrl() {
            const params = new URLSearchParams(window.location.search);
            params.set('status_filter', statusFilter.value);
            params.set('search', searchInput.value);
            params.set('page_current', '1'); // Reset to first page on filter/search
            window.location.search = params.toString();
        }

        statusFilter.addEventListener('change', updateUrl);
        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                updateUrl();
            }
        });
    </script>
</body>
</html>