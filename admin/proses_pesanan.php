<?php
session_start();
include '../config/db.php';

// Cek apakah admin sudah login
if (!isset($_SESSION['admin_logged_in'])) {
    $_SESSION['alert'] = [
        'type' => 'error',
        'message' => 'Silakan login sebagai admin terlebih dahulu.'
    ];
    header("Location: login.php");
    exit;
}

// Pastikan ID pesanan valid
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['alert'] = [
        'type' => 'error',
        'message' => 'ID pesanan tidak valid.'
    ];
    header("Location: order.php");
    exit;
}

$order_id = intval($_GET['id']);

try {
    // Cek apakah pesanan ada dan statusnya
    $stmt = $conn->prepare("SELECT order_id, status FROM orders WHERE order_id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        throw new Exception("Pesanan dengan ID #$order_id tidak ditemukan.");
    }
    $order = $result->fetch_assoc();
    $stmt->close();

    // Cek jika sudah 'done'
    if ($order['status'] === 'done') {
        $_SESSION['alert'] = [
            'type' => 'warning',
            'message' => "Pesanan #$order_id sudah selesai."
        ];
        header("Location: detail_pesanan.php?id=$order_id");
        exit;
    }

    // Cek jika status bukan 'paid' (opsional, untuk memastikan alur)
    if ($order['status'] !== 'paid') {
        $_SESSION['alert'] = [
            'type' => 'error',
            'message' => "Pesanan #$order_id belum dibayar. Selesaikan pembayaran terlebih dahulu."
        ];
        header("Location: detail_pesanan.php?id=$order_id");
        exit;
    }

    // Update status pesanan menjadi 'done'
    $stmt = $conn->prepare("UPDATE orders SET status = 'done' WHERE order_id = ?");
    $stmt->bind_param("i", $order_id);
    if (!$stmt->execute()) {
        throw new Exception("Gagal menyelesaikan pesanan #$order_id.");
    }
    $stmt->close();

    // Set pesan sukses
    $_SESSION['alert'] = [
        'type' => 'success',
        'message' => "Pesanan #$order_id berhasil diselesaikan!"
    ];

} catch (Exception $e) {
    error_log("Error di proses_pesanan.php: " . $e->getMessage());
    $_SESSION['alert'] = [
        'type' => 'error',
        'message' => $e->getMessage()
    ];
    header("Location: order.php");
    exit;
}

// Redirect ke detail pesanan
header("Location: detail_pesanan.php?id=$order_id");
exit;
?>