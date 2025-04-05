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

// Ambil daftar menu dari database
$result = $conn->query("SELECT * FROM menu");

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sidebar Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script type="module" src="https://unpkg.com/@google/model-viewer/dist/model-viewer.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-orange-50 to-red-100 min-h-screen flex">
    <!-- Sidebar -->
    <div class="h-screen w-64 bg-gradient-to-b from-orange-600 to-yellow-900 text-white p-5 shadow-lg fixed flex flex-col">
        <div class="text-center mb-8 pt-4">
            <h2 class="text-2xl font-bold mb-2">Admin Panel</h2>
            <div class="w-16 h-1 bg-orange-300 mx-auto rounded-full"></div>
        </div>
        <nav class="flex-1">
            <ul class="space-y-2">
                <li>
                    <a href="dashboard.php" class="flex items-center p-3 rounded-lg hover:bg-orange-500 transition-colors">
                        <i class="fas fa-tachometer-alt mr-3"></i> Dashboard
                    </a>
                </li>
                <li>
                    <a href="menu.php" class="flex items-center p-3 rounded-lg bg-orange-500 transition-colors">
                        <i class="fas fa-utensils mr-3"></i> Kelola Menu
                    </a>
                </li>
                <li>
                    <a href="promos.php" class="flex items-center p-3 rounded-lg hover:bg-orange-500 transition-colors">
                        <i class="fas fa-tags mr-3"></i> Kelola Promo
                    </a>
                </li>
            </ul>
        </nav>
        <a href="logout.php" class="flex items-center p-3 rounded-lg bg-red-600 hover:bg-red-700 transition-colors">
            <i class="fas fa-sign-out-alt mr-3"></i> Logout
        </a>
    </div>

    <!-- Content -->
    <div class="flex-1 ml-64 p-6">
        <h2 class="text-3xl font-bold text-orange-600 text-center">Daftar Menu</h2>
        <a href="add_menu.php" class="bg-gradient-to-r from-orange-500 to-red-500 text-white px-5 py-2 rounded-lg shadow-md hover:opacity-90 transition mb-4 inline-block">Tambah Menu</a>
        
        <table class="w-full bg-white shadow-lg rounded-lg overflow-hidden">
            <thead class="bg-orange-500 text-white">
                <tr>
                    <th class="p-3">Nama Menu</th>
                    <th class="p-3">Harga</th>
                    <th class="p-3">Kategori</th>
                    <th class="p-3">Gambar</th>
                    <th class="p-3">3D Model</th>
                    <th class="p-3">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()) { ?>
                    <tr class="border-b hover:bg-orange-100">
                        <td class="p-3"> <?= htmlspecialchars($row['nama_menu']) ?> </td>
                        <td class="p-3">Rp<?= number_format($row['harga'], 0, ',', '.') ?></td>
                        <td class="p-3"> <?= htmlspecialchars($row['kategori_menu']) ?> </td>
                        <td class="p-3">
                            <img src="../assets/images/<?= $row['gambar'] ?>" width="50" class="rounded-lg">
                        </td>
                        <td class="p-3">
                            <?php if (!empty($row['model_3d'])): ?>
                                <model-viewer src="../assets/models/<?= htmlspecialchars($row['model_3d']) ?>" 
                                    alt="3D Model" 
                                    camera-controls 
                                    auto-rotate 
                                    style="width: 100px; height: 100px;">
                                </model-viewer>
                            <?php else: ?>
                                <i class="text-gray-500">Tidak ada model 3D</i>
                            <?php endif; ?>
                        </td>
                        <td class="p-3">
                            <a href="edit_menu.php?id=<?= $row['id_menu'] ?>" class="bg-yellow-600 text-white px-4 py-2 rounded-lg shadow-md hover:opacity-80">Edit</a>
                            <a href="delete_menu.php?id=<?= $row['id_menu'] ?>" class="bg-red-600 text-white px-4 py-2 rounded-lg shadow-md hover:opacity-80" onclick="return confirm('Hapus menu ini?')">Hapus</a>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</body>
</html>

