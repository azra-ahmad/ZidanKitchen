<?php
include '../config/db.php';

// Validasi parameter table
if (!isset($_GET['table'])) {
    die("Parameter meja tidak valid");
}

$table_id = intval($_GET['table']); // Pastikan integer

// Cek status meja
$active_order = $conn->query("
    SELECT 1 FROM orders 
    WHERE id_meja = $table_id AND status IN ('pending', 'paid')
");

if ($active_order->num_rows > 0) {
    die("Meja $table_id masih digunakan. Silakan selesaikan pesanan sebelumnya.");
}

header("Location: register.php?table=".$table_id);
exit();
?>