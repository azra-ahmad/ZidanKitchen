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

    // Handle upload model 3D jika ada
    if (!empty($_FILES['model_3d']['name'])) {
        $zip_name = $_FILES['model_3d']['name'];
        $zip_tmp = $_FILES['model_3d']['tmp_name'];
        $target_dir = "../assets/models/";
        $target_file = $target_dir . basename($zip_name);

        if (move_uploaded_file($zip_tmp, $target_file)) {
            $query = "UPDATE menu SET nama_menu='$nama_menu', harga='$harga', kategori_menu='$kategori', model_3d='$zip_name' WHERE id_menu=$id_menu";
        } else {
            echo "<script>alert('Gagal mengunggah model 3D.');</script>";
        }
    } else {
        $query = "UPDATE menu SET nama_menu='$nama_menu', harga='$harga', kategori_menu='$kategori' WHERE id_menu=$id_menu";
    }

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
            <form method="POST" enctype="multipart/form-data">
                <label class="block font-semibold text-orange-700 mb-1">Nama Menu</label>
                <input type="text" name="nama_menu" value="<?= htmlspecialchars($menu['nama_menu']) ?>" class="w-full p-3 border rounded-lg mb-4 focus:outline-none focus:ring-2 focus:ring-orange-400" required>

                <label class="block font-semibold text-orange-700 mb-1">Harga</label>
                <input type="number" name="harga" value="<?= $menu['harga'] ?>" class="w-full p-3 border rounded-lg mb-4 focus:outline-none focus:ring-2 focus:ring-orange-400" required>

                <label class="block font-semibold text-orange-700 mb-1">Kategori</label>
                <input type="text" name="kategori_menu" value="<?= htmlspecialchars($menu['kategori_menu']) ?>" class="w-full p-3 border rounded-lg mb-4 focus:outline-none focus:ring-2 focus:ring-orange-400" required>

                <label class="block font-semibold text-orange-700 mb-1">Upload Model 3D (ZIP)</label>
                <input type="file" name="model_3d" class="w-full p-3 border rounded-lg mb-4 focus:outline-none focus:ring-2 focus:ring-orange-400">
                <p class="text-sm text-gray-500 mb-4">*Kosongkan jika tidak ingin mengganti model 3D.</p>

                <div class="flex justify-between">
                    <button type="submit" name="update" class="bg-gradient-to-r from-orange-500 to-red-500 text-white px-5 py-2 rounded-lg shadow-md hover:opacity-90 transition">Simpan Perubahan</button>
                    <a href="menu.php" class="bg-gray-500 text-white px-5 py-2 rounded-lg shadow-md hover:opacity-80">Batal</a>
                </div>
            </form>
        </div>
    </div>
</body>

</html>