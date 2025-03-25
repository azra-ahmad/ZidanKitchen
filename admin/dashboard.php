<?php
include '../config/db.php';
$result = $conn->query("SELECT * FROM orders WHERE status='pending' ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dapur - ZidanKitchen</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">
    <h2 class="text-2xl font-bold mb-4">Pesanan Masuk</h2>
    <table class="w-full bg-white rounded-lg shadow-lg">
        <thead>
            <tr class="bg-gray-200">
                <th class="p-2">Meja</th>
                <th class="p-2">Total</th>
                <th class="p-2">Metode</th>
                <th class="p-2">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr class="border-b">
                    <td class="p-2 text-center"><?= $row['id_meja'] ?></td>
                    <td class="p-2 text-center">Rp <?= number_format($row['total_harga'], 0, ',', '.') ?></td>
                    <td class="p-2 text-center"><?= $row['metode_pembayaran'] ?></td>
                    <td class="p-2 text-center">
                        <a href="proses_pesanan.php?id=<?= $row['id'] ?>" class="bg-green-500 text-white px-3 py-1 rounded">Selesaikan</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</body>
</html>
