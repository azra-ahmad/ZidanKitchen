<?php
include('../config/db.php');

if (!isset($conn)) {
    die("Error: Koneksi database tidak tersedia.");
}

// Ambil data menu berdasarkan ID
if (isset($_GET['id'])) {
    $id_menu = $_GET['id'];
    $result = $conn->query("SELECT * FROM menu WHERE id_menu = $id_menu");
    $menu = $result->fetch_assoc();
}

// Proses update menu
if (isset($_POST['update'])) {
    $nama_menu = $_POST['nama_menu'];
    $harga = $_POST['harga'];
    $kategori = $_POST['kategori_menu'];

    $query = "UPDATE menu SET nama_menu='$nama_menu', harga='$harga', kategori_menu='$kategori' WHERE id_menu=$id_menu";

    if ($conn->query($query)) {
        echo "<script>alert('Menu berhasil diperbarui!'); window.location='menu.php';</script>";
    } else {
        echo "Gagal memperbarui menu: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Menu</title>
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
                    <a href="orders.php" class="block p-3 rounded-lg hover:bg-orange-500">Pesanan</a>
                </li>
                <li class="mb-4">
                    <a href="settings.php" class="block p-3 rounded-lg hover:bg-orange-500">Pengaturan</a>
                </li>
            </ul>
        </nav>
        <a href="logout.php" class="block p-3 rounded-lg bg-red-600 hover:bg-red-700 text-center">Logout</a>
    </div>

    <!-- Content -->
    <div class="flex-1 ml-64 p-10">
        <h2 class="text-3xl font-bold text-orange-800 text-center mb-6">Edit Menu</h2>
        <div class="bg-white p-6 rounded-lg shadow-lg max-w-lg mx-auto">
            <form method="POST">
                <label class="block font-semibold text-orange-700 mb-1">Nama Menu</label>
                <input type="text" name="nama_menu" value="<?= htmlspecialchars($menu['nama_menu']) ?>" class="w-full p-3 border rounded-lg mb-4 focus:outline-none focus:ring-2 focus:ring-orange-400" required>

                <label class="block font-semibold text-orange-700 mb-1">Harga</label>
                <input type="number" name="harga" value="<?= $menu['harga'] ?>" class="w-full p-3 border rounded-lg mb-4 focus:outline-none focus:ring-2 focus:ring-orange-400" required>

                <label class="block font-semibold text-orange-700 mb-1">Kategori</label>
                <input type="text" name="kategori_menu" value="<?= htmlspecialchars($menu['kategori_menu']) ?>" class="w-full p-3 border rounded-lg mb-4 focus:outline-none focus:ring-2 focus:ring-orange-400" required>

                <div class="flex justify-between">
                    <button type="submit" name="update" class="bg-gradient-to-r from-orange-500 to-red-500 text-white px-5 py-2 rounded-lg shadow-md hover:opacity-90 transition">Simpan Perubahan</button>
                    <a href="menu.php" class="bg-gray-500 text-white px-5 py-2 rounded-lg shadow-md hover:opacity-80">Batal</a>
                </div>
            </form>
        </div>
    </div>
</body>

</html>