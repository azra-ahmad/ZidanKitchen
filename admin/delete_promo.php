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
    $promo_id = (int)$_GET['id'];

    try {
        // Check if promo exists and get image
        $stmt = $conn->prepare("SELECT image FROM promos WHERE promo_id = ?");
        $stmt->bind_param("i", $promo_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            throw new Exception("Promo tidak ditemukan.");
        }
        $promo = $result->fetch_assoc();
        $image = $promo['image'];
        $stmt->close();

        // Start transaction
        $conn->begin_transaction();

        // Delete from promo_menu
        $stmt = $conn->prepare("DELETE FROM promo_menu WHERE promo_id = ?");
        $stmt->bind_param("i", $promo_id);
        if (!$stmt->execute()) {
            throw new Exception("Gagal menghapus relasi menu: " . $stmt->error);
        }
        $stmt->close();

        // Delete from promos
        $stmt = $conn->prepare("DELETE FROM promos WHERE promo_id = ?");
        $stmt->bind_param("i", $promo_id);
        if (!$stmt->execute()) {
            throw new Exception("Gagal menghapus promo: " . $stmt->error);
        }
        $stmt->close();

        // Delete image file if not default
        if ($image !== "default.png" && file_exists("../assets/images/" . $image)) {
            if (!unlink("../assets/images/" . $image)) {
                // Log failure but don't fail the deletion
                error_log("Gagal menghapus gambar promo: ../assets/images/$image");
            }
        }

        // Commit transaction
        $conn->commit();
        $_SESSION['success'] = "Promo berhasil dihapus!";
        header("Location: promos.php");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        $error = $e->getMessage();
        $_SESSION['error'] = $error;
        header("Location: promos.php");
        exit;
    }
} else {
    $_SESSION['error'] = "ID promo tidak valid.";
    header("Location: promos.php");
    exit;
}
?>