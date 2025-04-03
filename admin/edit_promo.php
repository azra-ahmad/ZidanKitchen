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

    if ($_FILES['image']['name']) {
        $image = $_FILES['image']['name'];
        move_uploaded_file($_FILES['image']['tmp_name'], "../assets/images/$image");
    } else {
        $image = $promo['image'];
    }

    $updateQuery = $conn->prepare("UPDATE promos SET title = ?, description = ?, discount = ?, image = ? WHERE id = ?");
    $updateQuery->bind_param("ssdsi", $title, $description, $discount, $image, $id);

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
            <label class="block text-gray-700">Diskon (%)</label>
            <input type="number" name="discount" value="<?= $promo['discount'] ?>" class="w-full p-2 border rounded-lg" required>
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
