<?php
include '../config/db.php';

if (!isset($_GET['id_meja'])) {
    die("ID meja tidak diberikan.");
}

$id_meja = intval($_GET['id_meja']);

// Cek apakah OTP masih berlaku
$query = "SELECT kode_otp, otp_expiry FROM meja WHERE id_meja = $id_meja";
$result = $conn->query($query);
$row = $result->fetch_assoc();

if ($row && strtotime($row['otp_expiry']) > time()) {
    echo "OTP untuk Meja $id_meja masih berlaku: " . $row['kode_otp'];
    exit();
}

// Jika OTP sudah expired atau belum ada, buat baru
$otp = rand(100000, 999999);
$expiry = date("Y-m-d H:i:s", strtotime("+5 minutes")); // Berlaku 5 menit

$sql = "UPDATE meja SET kode_otp = '$otp', otp_expiry = '$expiry', status='tersedia' WHERE id_meja = $id_meja";
$conn->query($sql);

echo "OTP baru untuk Meja $id_meja: $otp (berlaku hingga $expiry)";
?>
