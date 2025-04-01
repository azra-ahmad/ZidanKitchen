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
    
    // Update query tanpa gambar
    $query = "UPDATE menu SET nama_menu='$nama_menu', harga='$harga', kategori_menu='$kategori' WHERE id_menu=$id_menu";

    if ($conn->query($query)) {
        echo "<script>alert('Menu berhasil diperbarui!'); window.location='menu.php';</script>";
    } else {
        echo "Gagal memperbarui menu: " . $conn->error;
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Menu</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto py-10">
        <h2 class="text-2xl font-bold text-center mb-6">Edit Menu</h2>
        <form method="POST" class="bg-white p-6 rounded shadow-md max-w-md mx-auto">
            <label class="block mb-2">Nama Menu</label>
            <input type="text" name="nama_menu" value="<?= htmlspecialchars($menu['nama_menu']) ?>" class="w-full p-2 border rounded mb-4" required>

            <label class="block mb-2">Harga</label>
            <input type="number" name="harga" value="<?= $menu['harga'] ?>" class="w-full p-2 border rounded mb-4" required>

            <label class="block mb-2">Kategori</label>
            <input type="text" name="kategori_menu" value="<?= htmlspecialchars($menu['kategori_menu']) ?>" class="w-full p-2 border rounded mb-4" required>

            <button type="submit" name="update" class="bg-blue-500 text-white px-4 py-2 rounded">Simpan Perubahan</button>
            <a href="menu.php" class="bg-gray-500 text-white px-4 py-2 rounded">Batal</a>
        </form>
    </div>
</body>
</html>
