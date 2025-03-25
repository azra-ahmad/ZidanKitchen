<?php
session_start();
include '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $otp = $_POST['otp'];

    // Cek OTP di database dengan prepared statement
    $stmt = $conn->prepare("SELECT id_meja FROM meja WHERE kode_otp = ? AND otp_expiry >= NOW()");
    $stmt->bind_param("s", $otp);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $id_meja = $row['id_meja'];

        // Update status meja ke 'digunakan'
        $update_stmt = $conn->prepare("UPDATE meja SET status = 'digunakan' WHERE id_meja = ?");
        $update_stmt->bind_param("i", $id_meja);

        if ($update_stmt->execute()) {
            $_SESSION['id_meja'] = $id_meja; // Simpan sesi untuk proteksi akses
            header("Location: menu.php");
            exit();
        } else {
            echo "Gagal update status meja: " . $conn->error;
        }
    } else {
        // Redirect kembali ke OTP dengan pesan error
        $_SESSION['error'] = "OTP salah atau sudah kedaluwarsa.";
        header("Location: login_otp.php");
        exit();
    }
}
?>
