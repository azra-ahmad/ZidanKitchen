<?php
session_start();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Meja - ZidanKitchen</title>
</head>
<body>
    <h2>Masukkan Kode OTP</h2>
    <form action="verify_otp.php" method="POST">
        <input type="text" name="otp" placeholder="Masukkan OTP" required>
        <button type="submit">Masuk</button>
    </form>
</body>
</html>
