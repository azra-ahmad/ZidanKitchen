<?php
session_start();
include '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    include '../config/db.php';
    $otp = $_POST['otp'];

    // Cek OTP di database
    $query = "SELECT * FROM meja WHERE kode_otp = '$otp' AND otp_expiry >= NOW()";
    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $id_meja = $row['id_meja'];
        
        // Tambahkan debugging
        echo "OTP valid untuk Meja: " . $id_meja . "<br>";

        // Update status meja ke 'digunakan'
        $update = "UPDATE meja SET status = 'digunakan' WHERE id_meja = $id_meja";
        if ($conn->query($update) === TRUE) {
            echo "Status meja berhasil diubah.";
            $_SESSION['id_meja'] = $id_meja;
            header("Location: menu.php");
            exit();
        } else {
            echo "Gagal update status meja: " . $conn->error;
        }
    } else {
        echo "OTP salah atau sudah kedaluwarsa.";
    }
}

?>
