<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}

include '../config/db.php';
date_default_timezone_set('Asia/Jakarta');

if (!isset($conn)) {
    die("Error: Koneksi database tidak tersedia.");
}

$error = '';

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $menu_id = (int)$_GET['id'];

    try {
        // Check if menu exists
        $stmt = $conn->prepare("SELECT menu_id FROM menu WHERE menu_id = ?");
        $stmt->bind_param("i", $menu_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            throw new Exception("Menu tidak ditemukan.");
        }
        $stmt->close();

        // Start transaction
        $conn->begin_transaction();

        // Delete from promo_menu
        $stmt = $conn->prepare("DELETE FROM promo_menu WHERE menu_id = ?");
        $stmt->bind_param("i", $menu_id);
        if (!$stmt->execute()) {
            throw new Exception("Gagal menghapus relasi promo: " . $stmt->error);
        }
        $stmt->close();

        // Delete from menu
        $stmt = $conn->prepare("DELETE FROM menu WHERE menu_id = ?");
        $stmt->bind_param("i", $menu_id);
        if (!$stmt->execute()) {
            throw new Exception("Gagal menghapus menu: " . $stmt->error);
        }
        $stmt->close();

        // Commit transaction
        $conn->commit();
        $_SESSION['success'] = "Menu berhasil dihapus!";
        header("Location: menu.php");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        $error = $e->getMessage();
        $_SESSION['error'] = $error;
        header("Location: menu.php");
        exit;
    }
} else {
    $_SESSION['error'] = "ID menu tidak valid.";
    header("Location: menu.php");
    exit;
}
?>