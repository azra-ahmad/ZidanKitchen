<?php
session_start();
include '../config/db.php';

// PROSES FORM JIKA DISUBMIT
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $table_id = intval($_POST['table_id']);
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);

    // Validasi input
    if (empty($name) || empty($phone)) {
        die("Nama dan nomor HP wajib diisi");
    }

    // 1. Cek status meja lagi (double validation)
    $table_status = $conn->query("SELECT status FROM meja WHERE id_meja = $table_id")->fetch_assoc();
    if ($table_status['status'] === 'digunakan') {
        die("Meja $table_id sedang digunakan!");
    }

    // 2. Simpan data customer
    $stmt = $conn->prepare("INSERT INTO customers (name, phone, table_id) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $name, $phone, $table_id);
    
    if (!$stmt->execute()) {
        die("Error: Gagal menyimpan data customer");
    }

    $customer_id = $conn->insert_id;

    // 3. Update status meja
    $conn->query("UPDATE meja SET status = 'digunakan' WHERE id_meja = $table_id");

    // 4. Set session
    $_SESSION['customer_id'] = $customer_id;
    $_SESSION['id_meja'] = $table_id;
    
    header("Location: menu.php");
    exit();
}

// TAMPILKAN FORM JIKA GET REQUEST
$table_id = isset($_GET['table']) ? intval($_GET['table']) : die("Parameter meja tidak valid");

// Cek apakah meja valid
$table_exists = $conn->query("SELECT 1 FROM meja WHERE id_meja = $table_id")->num_rows > 0;
if (!$table_exists) {
    die("Meja $table_id tidak ditemukan");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi Meja <?= $table_id ?></title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; max-width: 500px; margin: 0 auto; }
        .error { color: red; margin-bottom: 15px; }
        input, button { width: 100%; padding: 10px; margin: 8px 0; box-sizing: border-box; }
    </style>
</head>
<body>
    <h2>Registrasi Meja <?= $table_id ?></h2>
    
    <?php if (isset($_GET['error'])): ?>
        <div class="error"><?= htmlspecialchars($_GET['error']) ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="table_id" value="<?= $table_id ?>">
        
        <label for="name">Nama Lengkap:</label>
        <input type="text" id="name" name="name" required>
        
        <label for="phone">Nomor HP:</label>
        <input type="tel" id="phone" name="phone" required>
        
        <button type="submit">Mulai Pesan</button>
    </form>
</body>
</html>