<?php
session_start();
require_once '../vendor/autoload.php';
include '../config/db.php';

// Validate session and parameters
if (!isset($_SESSION['customer_id']) || !isset($_GET['order_id'])) {
    header("Location: menu.php");
    exit;
}

// Ambil data dari URL
$order_id = $_GET['order_id'];
$total_harga = $_GET['total'];

// Setup Midtrans
\Midtrans\Config::$serverKey = 'SB-Mid-server-p_rr6ZhgUcuXXt7ZJaAJsSM2';
\Midtrans\Config::$isProduction = false;
\Midtrans\Config::$isSanitized = true;
\Midtrans\Config::$is3ds = true;

$customer_id = $_SESSION['customer_id'];
$result = $conn->query("SELECT name, phone FROM customers WHERE id = $customer_id");

if ($result && $result->num_rows > 0) {
    $customer = $result->fetch_assoc();
} else {
    die("Customer tidak ditemukan.");
}

$params = [
    'transaction_details' => [
        'order_id' => 'ZK-'.$order_id.'-'.time(),
        'gross_amount' => $total_harga
    ],
    'customer_details' => [
        'first_name' => $customer['name'],
        'phone' => $customer['phone'],
        'billing_address' => [
            'address' => 'Meja '.$_SESSION['id_meja']
        ]
    ],
    'item_details' => getOrderItems($order_id, $conn),
    'callbacks' => [
        'finish' => 'http://yourdomain.com/success.php'
    ]
];

try {
    $snapToken = \Midtrans\Snap::getSnapToken($params);
    
    // Store payment token
    $conn->query("UPDATE orders SET snap_token = '$snapToken' WHERE id = $order_id");
    
} catch (Exception $e) {
    die("Payment Error: ".$e->getMessage());
}


// Simpan snap_token ke database (untuk callback)
$stmt = $conn->prepare("UPDATE orders SET snap_token = ? WHERE id = ?");
$stmt->bind_param("si", $snapToken, $order_id);
$stmt->execute();

function getOrderItems($order_id, $conn) {
    $items = [];
    $result = $conn->query("
        SELECT id_menu, nama_menu, jumlah, harga, 
               (jumlah * harga) as subtotal 
        FROM order_items 
        WHERE order_id = $order_id
    ");
    
    while ($row = $result->fetch_assoc()) {
        $items[] = [
            'id' => $row['id_menu'],
            'name' => $row['nama_menu'],
            'price' => $row['harga'],
            'quantity' => $row['jumlah']
        ];
        
        // Add free items if any (Buy 2 Get 1)
        if ($row['jumlah'] >= 3) {
            $free_qty = floor($row['jumlah'] / 3);
            $items[] = [
                'id' => $row['id_menu'].'-FREE',
                'name' => $row['nama_menu'].' (Gratis)',
                'price' => 0,
                'quantity' => $free_qty
            ];
        }
    }
    return $items;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran - ZidanKitchen</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://app.sandbox.midtrans.com/snap/snap.js"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center px-4 sm:px-6 lg:px-8">
        <div class="bg-white p-6 sm:p-8 rounded-lg shadow-md w-full max-w-sm sm:max-w-md text-center">
            <!-- Loading Spinner -->
            <div class="animate-spin rounded-full h-12 w-12 sm:h-16 sm:w-16 border-t-2 border-b-2 border-blue-500 mx-auto mb-4"></div>
            
            <!-- Title -->
            <h2 class="text-lg sm:text-xl font-semibold mb-2">Mempersiapkan Pembayaran</h2>
            
            <!-- Description -->
            <p class="text-gray-600 text-sm sm:text-base mb-6">Anda akan diarahkan ke halaman pembayaran...</p>
            
            <!-- Midtrans Snap Script -->
            <script>
                snap.pay('<?= $snapToken ?>', {
                    onSuccess: function(result) {
                        window.location = 'success.php?order_id=<?= $order_id ?>';
                    },
                    onPending: function(result) {
                        window.location = 'pending.php?order_id=<?= $order_id ?>';
                    },
                    onError: function(error) {
                        window.location = 'error.php?order_id=<?= $order_id ?>';
                    },
                    onClose: function() {
                        window.location = 'menu.php';
                    }
                });
            </script>
        </div>
    </div>
</body>
</html>
