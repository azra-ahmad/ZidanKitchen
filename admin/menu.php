<?php
include('../config/db.php');

if (!isset($conn)) {
    die("Error: Koneksi database tidak tersedia.");
}

// Ambil daftar menu dari database
$result = $conn->query("SELECT * FROM menu");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Menu</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script type="module" src="https://unpkg.com/@google/model-viewer/dist/model-viewer.min.js"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto py-10">
        <h2 class="text-2xl font-bold text-center mb-6">Daftar Menu</h2>
        <a href="add_menu.php" class="bg-blue-500 text-white px-4 py-2 rounded mb-4 inline-block">Tambah Menu</a>
        <table class="w-full bg-white shadow-md rounded-lg overflow-hidden">
            <thead class="bg-gray-200">
                <tr>
                    <th class="py-2 px-4">Nama Menu</th>
                    <th class="py-2 px-4">Harga</th>
                    <th class="py-2 px-4">Kategori</th>
                    <th class="py-2 px-4">Gambar</th>
                    <th class="py-2 px-4">3D Model</th>
                    <th class="py-2 px-4">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()) { ?>
                    <tr class="border-b">
                        <td class="py-2 px-4"><?= htmlspecialchars($row['nama_menu']) ?></td>
                        <td class="py-2 px-4">Rp<?= number_format($row['harga'], 0, ',', '.') ?></td>
                        <td class="py-2 px-4"><?= htmlspecialchars($row['kategori_menu']) ?></td>
                        <td class="py-2 px-4">
                            <img src="../assets/images/<?= $row['gambar'] ?>" width="50">
                        </td>
                        <td class="py-3 px-4">
                            <?php if (!empty($row['model_3d'])): ?>
                                <model-viewer src="../assets/models/<?= htmlspecialchars($row['model_3d']) ?>" 
                                    alt="3D Model" 
                                    camera-controls 
                                    auto-rotate 
                                    style="width: 100px; height: 100px;">
                                </model-viewer>
                            <?php else: ?>
                                <i>Tidak ada model 3D</i>
                            <?php endif; ?>
                        </td>
                        <td class="py-2 px-4">
                            <a href="edit_menu.php?id=<?= $row['id_menu'] ?>" class="bg-yellow-500 text-white px-3 py-1 rounded">Edit</a>
                            <a href="delete_menu.php?id=<?= $row['id_menu'] ?>" class="bg-red-500 text-white px-3 py-1 rounded" onclick="return confirm('Hapus menu ini?')">Hapus</a>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</body>
</html>
