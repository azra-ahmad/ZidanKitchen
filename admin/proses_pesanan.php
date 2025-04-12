<?php
session_start();
include '../config/db.php';

// Cek apakah admin sudah login
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}

// Pastikan ada ID pesanan yang dikirim
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Error: ID pesanan tidak ditemukan.");
}

$order_id = intval($_GET['id']); // Pastikan ID valid

// Update status pesanan menjadi selesai
$sql = "UPDATE orders SET status = 'done' WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $order_id);

if ($stmt->execute()) {
    $_SESSION['alert'] = [
        'type' => 'success',
        'message' => "Pesanan #$order_id berhasil diselesaikan!"
    ];
} else {
    $_SESSION['alert'] = [
        'type' => 'error',
        'message' => "Gagal menyelesaikan pesanan #$order_id"
    ];
}

// Redirect kembali ke detail_pesanan.php biar user bisa lihat hasilnya
header("Location: detail_pesanan.php?id=$order_id");
exit;
?>