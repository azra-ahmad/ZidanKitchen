<?php
require_once '../vendor/autoload.php';
include '../config/db.php';

// Load Midtrans config
$midtrans_config = include '../config/midtrans.php';

// Setup Midtrans Config
\Midtrans\Config::$serverKey = $midtrans_config['server_key'];
\Midtrans\Config::$isProduction = $midtrans_config['is_production'];

// Ambil data notifikasi dari Midtrans (via POST)
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data) {
    http_response_code(400);
    error_log("Invalid notification data received");
    exit("Invalid notification data");
}

// Verifikasi signature (penting buat keamanan)
$server_key = \Midtrans\Config::$serverKey;
$order_id = $data['order_id'];
$status_code = $data['status_code'];
$gross_amount = $data['gross_amount'];
$signature_key = $data['signature_key'];

$expected_signature = hash('sha512', $order_id . $status_code . number_format($gross_amount, 0, '.', '') . $server_key);
if ($signature_key !== $expected_signature) {
    http_response_code(403);
    error_log("Signature verification failed for order $order_id");
    exit("Signature verification failed");
}

// Ambil order_id asli dari midtrans_order_id (format: ZK-{order_id}-{timestamp})
$order_id_parts = explode('-', $order_id);
if (count($order_id_parts) !== 3 || $order_id_parts[0] !== 'ZK') {
    http_response_code(400);
    error_log("Invalid midtrans_order_id format: $order_id");
    exit("Invalid midtrans_order_id format");
}
$real_order_id = (int)$order_id_parts[1];

// Ambil metode pembayaran dan status dari Midtrans
$payment_type = $data['payment_type'] ?? null;
$transaction_status = $data['transaction_status'] ?? null;

if (!$payment_type || !$transaction_status) {
    http_response_code(400);
    error_log("Missing payment_type or transaction_status for order $real_order_id");
    exit("Missing payment_type or transaction_status");
}

// Tentukan status baru berdasarkan transaction_status
$status = 'pending';
switch ($transaction_status) {
    case 'settlement':
    case 'capture':
        $status = 'paid';
        break;
    case 'pending':
        $status = 'pending';
        break;
    case 'deny':
    case 'cancel':
    case 'expire':
        $status = 'failed';
        break;
    default:
        http_response_code(400);
        error_log("Unknown transaction_status: $transaction_status for order $real_order_id");
        exit("Unknown transaction_status");
}

// Update status dan metode pembayaran di database
$query = "UPDATE orders SET status = ?, metode_pembayaran = ? WHERE order_id = ? AND midtrans_order_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ssis", $status, $payment_type, $real_order_id, $order_id);
if (!$stmt->execute()) {
    http_response_code(500);
    error_log("Failed to update order $real_order_id: " . $conn->error);
    exit("Failed to update order");
}

if ($stmt->affected_rows === 0) {
    http_response_code(404);
    error_log("No order found with id $real_order_id and midtrans_order_id $order_id");
    exit("Order not found");
}

error_log("Order $real_order_id updated: status=$status, metode_pembayaran=$payment_type");
http_response_code(200);
echo "Success";
?>