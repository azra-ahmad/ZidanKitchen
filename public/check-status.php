<?php
session_start();
include '../config/db.php';

if (!isset($_GET['order_id']) || !isset($_SESSION['customer_id']) || !isset($_SESSION['meja_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

$order_id = (int)$_GET['order_id'];
$customer_id = $_SESSION['customer_id'];

$stmt = $conn->prepare("SELECT status FROM orders WHERE order_id = ? AND customer_id = ?");
$stmt->bind_param("ii", $order_id, $customer_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Order not found']);
    exit;
}

$order = $result->fetch_assoc();
echo json_encode(['status' => $order['status']]);
?>