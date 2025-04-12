<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}

include '../config/db.php';
date_default_timezone_set('Asia/Jakarta');

// Check if order ID is provided
if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$order_id = intval($_GET['id']);

// Fetch order details with customer info
$order_query = $conn->prepare("
    SELECT o.*, c.name AS customer_name, c.phone AS customer_phone 
    FROM orders o
    LEFT JOIN meja m ON o.id_meja = m.id_meja
    LEFT JOIN customers c ON o.customer_id = c.id
    WHERE o.id = ?
");
$order_query->bind_param("i", $order_id);
$order_query->execute();
$result = $order_query->get_result();
$order = $result->fetch_assoc();

if (!$order) {
    die("Order not found");
}

// Fetch order items
$items_query = $conn->prepare("
    SELECT oi.*, m.nama_menu, m.gambar 
    FROM order_items oi
    LEFT JOIN menu m ON oi.id_menu = m.id_menu
    WHERE oi.order_id = ?
");
$items_query->bind_param("i", $order_id);
$items_query->execute();
$items_result = $items_query->get_result();
$items = $items_result->fetch_all(MYSQLI_ASSOC);

// Handle alert (success/error) after processing
if (isset($_SESSION['alert'])) {
    $alertType = $_SESSION['alert']['type'];
    $alertMessage = $_SESSION['alert']['message'];
    unset($_SESSION['alert']);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pesanan - ZidanKitchen</title>
    <link rel="icon" type="image/x-icon" href="../assets/images/logo_oren.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.5/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.5/dist/sweetalert2.all.min.js"></script>
    <style>
        .status-text {
            font-weight: 500;
        }
        .status-pending {
            color: #f97316;
        }
        .status-paid {
            color: #1e40af;
        }
        .status-done {
            color: #16a34a;
        }
        .status-failed {
            color: #dc2626;
        }
        .item-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }
        .swal2-container {
            z-index: 99999 !important;
        }
        .swal2-backdrop-show {
            background: rgba(0, 0, 0, 0.4) !important;
        }
        .swal2-popup {
            animation: fadeIn 0.3s, bounceIn 0.5s;
        }
        /* Animasi saat popup muncul */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes bounceIn {
            0% { transform: scale(0.8); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        /* Animasi saat popup ditutup */
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

    <div class="flex-1 ml-64 p-8">
        <div class="max-w-6xl mx-auto">
            <!-- Header -->
            <div class="flex justify-between items-center mb-8">
                <h2 class="text-3xl font-bold text-orange-600">
                    <i class="fas fa-clipboard-list mr-2"></i> Detail Pesanan #<?= $order['id'] ?>
                </h2>
                <div class="flex items-center space-x-4">
                    <a href="order.php" class="text-gray-600 hover:text-orange-600 transition">
                        <i class="fas fa-arrow-left mr-1"></i> Kembali
                    </a>
                    <?php if ($order['status'] == 'pending' || $order['status'] == 'paid'): ?>
                        <a href="proses_pesanan.php?id=<?= $order['id'] ?>" 
                           class="bg-gradient-to-r from-orange-500 to-red-500 text-white px-6 py-3 rounded-lg shadow-md hover:opacity-90 transition flex items-center btn-proses">
                            <i class="fas fa-check-circle mr-2"></i> Proses Pesanan
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Order Summary Card -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden mb-8 border border-gray-100">
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Order Info -->
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 mb-3 border-b border-orange-100 pb-2">
                                <i class="fas fa-info-circle mr-2 text-orange-500"></i> Informasi Pesanan
                            </h3>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-600"><i class="fas fa-tag mr-2"></i> Status:</span>
                                    <span class="status-text status-<?= $order['status'] ?>">
                                        <?= ucfirst($order['status']) ?>
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600"><i class="fas fa-calendar-alt mr-2"></i> Tanggal:</span>
                                    <span class="font-medium">
                                        <?= $order['created_at'] ? date('d M Y H:i', strtotime($order['created_at'])) : '-' ?>
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600"><i class="fas fa-chair mr-2"></i> Meja:</span>
                                    <span class="font-medium"><?= $order['id_meja'] ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Info -->
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 mb-3 border-b border-orange-100 pb-2">
                                <i class="fas fa-wallet mr-2 text-orange-500"></i> Pembayaran
                            </h3>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-600"><i class="fas fa-credit-card mr-2"></i> Metode:</span>
                                    <span class="font-medium"><?= $order['metode_pembayaran'] ? ucfirst($order['metode_pembayaran']) : '-' ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600"><i class="fas fa-money-bill mr-2"></i> Total:</span>
                                    <span class="font-medium text-orange-600">Rp <?= number_format($order['total_harga'], 0, ',', '.') ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600"><i class="fas fa-barcode mr-2"></i> Midtrans ID:</span>
                                    <span class="font-medium"><?= $order['midtrans_order_id'] ?? '-' ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Customer Info -->
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 mb-3 border-b border-orange-100 pb-2">
                                <i class="fas fa-user mr-2 text-orange-500"></i> Informasi Pelanggan
                            </h3>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-600"><i class="fas fa-user-tag mr-2"></i> Nama:</span>
                                    <span class="font-medium"><?= $order['customer_name'] ?? '-' ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600"><i class="fas fa-phone mr-2"></i> No. Telepon:</span>
                                    <span class="font-medium"><?= $order['customer_phone'] ?? '-' ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Items -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-100">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-800">
                        <i class="fas fa-shopping-cart mr-2 text-orange-500"></i> Item Pesanan
                    </h3>
                </div>
                <div class="divide-y divide-gray-100">
                    <?php foreach ($items as $item): ?>
                        <div class="p-6 hover:bg-orange-50 transition-colors">
                            <div class="flex items-center">
                                <img src="../assets/images/<?= $item['gambar'] ?>" alt="<?= $item['nama_menu'] ?>" class="item-image mr-4">
                                <div class="flex-1">
                                    <h4 class="font-medium text-gray-800"><?= $item['nama_menu'] ?></h4>
                                    <div class="flex justify-between mt-2">
                                        <div class="text-gray-600">
                                            <?= $item['jumlah'] ?> x Rp <?= number_format($item['harga'], 0, ',', '.') ?>
                                        </div>
                                        <div class="font-medium text-orange-600">
                                            Rp <?= number_format($item['subtotal'], 0, ',', '.') ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <!-- Order Total -->
                    <div class="p-6 bg-gray-50">
                        <div class="flex justify-between items-center">
                            <span class="text-lg font-semibold text-gray-800">Total Pesanan</span>
                            <span class="text-2xl font-bold text-orange-600">Rp <?= number_format($order['total_harga'], 0, ',', '.') ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Untuk alert setelah proses (success/error)
        <?php if (isset($alertType) && isset($alertMessage)): ?>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: '<?= $alertType ?>',
                title: '<?= $alertMessage ?>',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                toast: true,
                position: 'top-end'
            });
        });
        <?php endif; ?>

        // Untuk konfirmasi sebelum proses
        document.querySelector('.btn-proses')?.addEventListener('click', function(e) {
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
                customClass: {
                    container: 'swal2-container-custom',
                    popup: 'swal2-popup-custom'
                },
                didClose: () => {
                    // Pastikan backdrop dihapus setelah popup ditutup
                    document.querySelector('.swal2-container')?.remove();
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = href;
                }
            });
        });
    </script>
</body>
</html>