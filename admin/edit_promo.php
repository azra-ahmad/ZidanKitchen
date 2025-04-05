<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}

include '../config/db.php';
date_default_timezone_set('Asia/Jakarta');

if (!isset($_GET['id'])) {
    die("ID Promo tidak ditemukan!");
}

$id = $_GET['id'];
$query = $conn->prepare("SELECT * FROM promos WHERE id = ?");
$query->bind_param("i", $id);
$query->execute();
$result = $query->get_result();
$promo = $result->fetch_assoc();

if (!$promo) {
    die("Promo tidak ditemukan!");
}

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
    
    if ($_FILES['image']['name']) {
        $image = $_FILES['image']['name'];
        move_uploaded_file($_FILES['image']['tmp_name'], "../assets/images/$image");
    } else {
        $image = $promo['image'];
    }


    $updateQuery = $conn->prepare("UPDATE promos SET title = ?, description = ?, start_date = ?, end_date = ?, discount = ?, promo_type = ?, category_target = ?, bundle_price = ?, image = ? WHERE id = ?");
    $updateQuery->bind_param("ssssissisi", $title, $description, $start_date, $end_date, $discount, $promo_type, $category_target, $bundle_price, $image, $id);

    if ($updateQuery->execute()) {
        echo "<script>alert('Promo berhasil diperbarui!'); window.location.href='promos.php';</script>";
    } else {
        echo "<script>alert('Gagal memperbarui promo!');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Promo</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-orange-50 to-red-100 p-6 min-h-screen">

<div class="max-w-lg mx-auto bg-white p-6 rounded-lg shadow-md">
    <h2 class="text-2xl font-bold text-orange-800 text-center mb-6">Edit Promo</h2>
    <form method="POST" enctype="multipart/form-data">
        <div class="mb-4">
            <label class="block text-gray-700">Nama Promo</label>
            <input type="text" name="title" value="<?= htmlspecialchars($promo['title']) ?>" class="w-full p-2 border rounded-lg" required>
        </div>
        <div class="mb-4">
            <label class="block text-gray-700">Deskripsi</label>
            <textarea name="description" class="w-full p-2 border rounded-lg" required><?= htmlspecialchars($promo['description']) ?></textarea>
        </div>
        <div class="mb-4">
            <label class="block text-gray-700">Tanggal Mulai</label>
            <input type="date" name="start_date" value="<?= $promo['start_date'] ?>" class="w-full p-2 border rounded-lg">
        </div>
        <div class="mb-4">
            <label class="block text-gray-700">Tanggal Berakhir</label>
            <input type="date" name="end_date" value="<?= $promo['end_date'] ?>" class="w-full p-2 border rounded-lg">
        </div>
        <div class="mb-4">
            <label class="block text-gray-700">Jenis Promo</label>
            <select name="promo_type" class="w-full p-2 border rounded-lg" required>
                <option value="discount" <?= $promo['promo_type'] == 'discount' ? 'selected' : '' ?>>Diskon</option>
                <option value="buy2get1" <?= $promo['promo_type'] == 'buy2get1' ? 'selected' : '' ?>>Beli 2 Gratis 1</option>
                <option value="bundle" <?= $promo['promo_type'] == 'bundle' ? 'selected' : '' ?>>Bundle</option>
            </select>
        </div>
        <div class="mb-4">
            <label class="block text-gray-700">Kategori Target</label>
            <select name="category_target" class="w-full p-2 border rounded-lg">
                <option value="">-- Pilih Kategori --</option>
                <option value="makanan" <?= $promo['category_target'] == 'makanan' ? 'selected' : '' ?>>Makanan</option>
                <option value="minuman" <?= $promo['category_target'] == 'minuman' ? 'selected' : '' ?>>Minuman</option>
                <option value="dessert" <?= $promo['category_target'] == 'dessert' ? 'selected' : '' ?>>Dessert</option>
            </select>
        </div>
        <div class="mb-4">
            <label class="block text-gray-700">Diskon (%)</label>
            <input type="number" name="discount" value="<?= $promo['discount'] ?>" class="w-full p-2 border rounded-lg">
        </div>
        <div class="mb-4">
            <label class="block text-gray-700">Harga Bundle</label>
            <input type="number" name="bundle_price" value="<?= $promo['bundle_price'] ?>" class="w-full p-2 border rounded-lg">
        </div>
        <div class="mb-4">
            <label class="block text-gray-700">Gambar Promo</label>
            <input type="file" name="image" class="w-full p-2 border rounded-lg">
            <img src="../assets/images/<?= htmlspecialchars($promo['image']) ?>" width="100" class="mt-2 rounded-lg">
        </div>
        <div class="text-center">
            <button type="submit" class="bg-orange-500 text-white px-5 py-2 rounded-lg shadow-md hover:opacity-90">Simpan Perubahan</button>
            <a href="promos.php" class="bg-gray-500 text-white px-5 py-2 rounded-lg shadow-md hover:opacity-90 ml-2">Batal</a>
        </div>
    </form>
</div>

</body>
</html>
