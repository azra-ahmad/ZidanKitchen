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

// Ambil daftar promo dari database
$result = $conn->query("SELECT * FROM promos");

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Promo</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-orange-50 to-red-100 min-h-screen flex">

    <!-- Sidebar -->
    <div class="h-screen w-64 bg-gradient-to-b from-orange-600 to-yellow-900 text-white p-5 shadow-lg fixed flex flex-col">
        <h2 class="text-2xl font-bold text-center mb-6">Admin Panel</h2>
        <nav class="flex-1">
            <ul>
                <li class="mb-4">
                    <a href="dashboard.php" class="block p-3 rounded-lg hover:bg-orange-500">Dashboard</a>
                </li>
                <li class="mb-4">
                    <a href="menu.php" class="block p-3 rounded-lg hover:bg-orange-500">Kelola Menu</a>
                </li>
                <li class="mb-4">
                    <a href="promos.php" class="block p-3 rounded-lg bg-orange-500">Kelola Promo</a>
                </li>
            </ul>
        </nav>
        <a href="logout.php" class="block p-3 rounded-lg bg-red-600 hover:bg-red-700 text-center">Logout</a>
    </div>

    <div class="flex-1 ml-64 p-6">
        <h2 class="text-3xl font-bold text-orange-800 text-center mb-6">Daftar Promo</h2>
        <a href="add_promo.php" class="bg-gradient-to-r from-orange-500 to-red-500 text-white px-5 py-2 rounded-lg shadow-md hover:opacity-90 transition mb-4 inline-block">Tambah Promo</a>
        
        <table class="w-full bg-white shadow-lg rounded-lg overflow-hidden">
            <thead class="bg-orange-500 text-white">
                <tr>
                    <th class="p-3">Judul</th>
                    <th class="p-3">Deskripsi</th>
                    <th class="p-3">Berlaku Sampai</th>
                    <th class="p-3">Jenis Promo</th>
                    <th class="p-3">Diskon (%)</th>
                    <th class="p-3">Kategori</th>
                    <th class="p-3">Gambar</th>
                    <th class="p-3">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()) { ?>
                    <tr class="border-b hover:bg-orange-100">
                        <td class="p-3"> <?= htmlspecialchars($row['title']) ?> </td>
                        <td class="p-3"> <?= htmlspecialchars($row['description']) ?> </td>
                        <td class="p-3"> <?= htmlspecialchars($row['valid_until']) ?> </td>
                        <td class="p-3"> <?= htmlspecialchars($row['promo_type']) ?> </td>
                        <td class="p-3"> <?= htmlspecialchars($row['discount']) ?> </td>
                        <td class="p-3"> <?= htmlspecialchars($row['category_target']) ?> </td>
                        <td class="p-3">
                            <img src="../assets/images/<?= $row['image'] ?>" width="50" class="rounded-lg">
                        </td>
                        <td class="p-3">
                            <a href="edit_promo.php?id=<?= $row['id'] ?>" class="bg-yellow-600 text-white px-4 py-2 rounded-lg shadow-md hover:opacity-80">Edit</a>
                            <a href="delete_promo.php?id=<?= $row['id'] ?>" class="bg-red-600 text-white px-4 py-2 rounded-lg shadow-md hover:opacity-80" onclick="return confirm('Hapus promo ini?')">Hapus</a>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</body>
</html>
