<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}

include '../config/db.php';
date_default_timezone_set('Asia/Jakarta');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $title = $_POST['title'];
    $description = $_POST['description'];
    $discount = $_POST['discount'];
    $promo_type = $_POST['promo_type'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $category_target = $_POST['category_target'] ?? null;
    $bundle_price = $_POST['bundle_price'] ?? null;
    
    // Validasi periode promo
    if (strtotime($start_date) > strtotime($end_date)) {
        die("<script>alert('Tanggal mulai tidak boleh setelah tanggal berakhir'); window.location.href='promos.php';</script>");
    } 

    // Cek apakah ada file yang diupload
    if ($_FILES['image']['name']) {
        $image = $_FILES['image']['name'];
        move_uploaded_file($_FILES['image']['tmp_name'], "../assets/images/$image");
    } else {
        $image = "default.png"; // Jika tidak ada gambar, pakai default
    }
    
    $query = $conn->prepare("INSERT INTO promos (title, description, start_date, end_date, discount, promo_type, category_target, bundle_price, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $query->bind_param("ssssissis", $title, $description, $start_date, $end_date, $discount, $promo_type, $category_target, $bundle_price, $image);

    if ($query->execute()) {
        echo "<script>alert('Promo berhasil ditambahkan!'); window.location.href='promos.php';</script>";
    } else {
        echo "<script>alert('Gagal menambahkan promo!'); window.location.href='promos.php';</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Promo</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-orange-50 to-red-100 p-6 min-h-screen">

<div class="max-w-lg mx-auto bg-white p-6 rounded-lg shadow-md">
    <h2 class="text-2xl font-bold text-orange-800 text-center mb-6">Tambah Promo</h2>
    <form method="POST" enctype="multipart/form-data">
        <div class="mb-4">
            <label class="block text-gray-700">Nama Promo</label>
            <input type="text" name="title" class="w-full p-2 border rounded-lg" required>
        </div>
        <div class="mb-4">
            <label class="block text-gray-700">Deskripsi</label>
            <textarea name="description" class="w-full p-2 border rounded-lg" required></textarea>
        </div>
        <div class="mb-4">
            <label class="block text-gray-700">Tanggal Mulai</label>
            <input type="date" name="start_date" class="w-full p-2 border rounded-lg">
        </div>
        <div class="mb-4">
            <label class="block text-gray-700">Tanggal Berakhir</label>
            <input type="date" name="end_date" class="w-full p-2 border rounded-lg">
        </div>
        <div class="mb-4">
            <label class="block text-gray-700">Jenis Promo</label>
            <select name="promo_type" class="w-full p-2 border rounded-lg" required>
                <option value="">-- Pilih Kategori --</option>
                <option value="discount">Diskon</option>
                <option value="buy2get1">Beli 2 Gratis 1</option>
                <option value="bundle">Bundle</option>
            </select>
        </div>
        <div class="mb-4">
            <label class="block text-gray-700">Kategori Target</label>
            <select name="category_target" class="w-full p-2 border rounded-lg">
                <option value="">-- Pilih Kategori --</option>
                <option value="makanan">Makanan</option>
                <option value="minuman">Minuman</option>
                <option value="dessert">Dessert</option>
            </select>
        </div>
        <div class="mb-4">
            <label class="block text-gray-700">Diskon (%)</label>
            <input type="number" name="discount" class="w-full p-2 border rounded-lg">
        </div>
        <div class="mb-4">
            <label class="block text-gray-700">Harga Bundle</label>
            <input type="number" name="bundle_price" class="w-full p-2 border rounded-lg">
        </div>
        <div class="mb-4">
            <label class="block text-gray-700">Gambar Promo</label>
            <input type="file" name="image" class="w-full p-2 border rounded-lg">
        </div>
        <div class="text-center">
            <button type="submit" class="bg-orange-500 text-white px-5 py-2 rounded-lg shadow-md hover:opacity-90">Tambah Promo</button>
            <a href="promos.php" class="bg-gray-500 text-white px-5 py-2 rounded-lg shadow-md hover:opacity-90 ml-2">Batal</a>
        </div>
    </form>
</div>

</body>
</html>
