<?php
include('../config/db.php'); 

if (!isset($conn)) {
    die("Error: Koneksi database tidak tersedia. Periksa file database.php");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $harga = $_POST['price'];
    $kategori_menu = $_POST['category'];
    $image = null;
    $modelPath = null;

    $targetDir = "../assets/";
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    // **1. Upload image Menu**
    if (!empty($_FILES['image']['name'])) {
        $image = basename($_FILES['image']['name']);
        $targetFile = $targetDir. "images/" . $image;
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
            die("Gagal mengunggah image.");
        }
    }

    // **2. Upload & Ekstrak Model 3D (ZIP)**
    if (!empty($_FILES['model_zip']['name'])) {
        $zipFile = $_FILES['model_zip']['tmp_name'];
        $folderName = strtolower(str_replace(" ", "_", $name));
        $modelDir = $targetDir . "models/" . $folderName . "/";

        if (!file_exists($modelDir)) {
            mkdir($modelDir, 0777, true);
        }

        $zip = new ZipArchive;
        if ($zip->open($zipFile) === TRUE) {
            $zip->extractTo($modelDir);
            $zip->close();
            $modelPath = "$folderName/scene.gltf"; 
        } else {
            die("Gagal mengekstrak ZIP.");
        }
    }

    // **3. Simpan ke Database**
    $stmt = $conn->prepare("INSERT INTO menu (nama_menu, harga, kategori_menu, gambar, model_3d) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sdsss", $name, $harga, $kategori_menu, $image, $modelPath);

    if ($stmt->execute()) {
        header("Location: dashboard.php");
        exit;
    } else {
        echo "Error: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Menu</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-r from-blue-300 via-blue-100 to-blue-300">
    <div class="min-h-screen flex items-center justify-center">
        <div class="bg-white shadow-md rounded-lg p-6 w-full max-w-md">
            <h2 class="text-2xl font-bold text-center text-gray-700 mb-6">Tambah Menu</h2>
            <form action="add_menu.php" method="POST" enctype="multipart/form-data">
                <div class="mb-4">
                    <label for="name" class="block text-gray-700 font-semibold mb-2">Nama Menu</label>
                    <input type="text" name="name" id="name" class="w-full px-4 py-2 border rounded-md" required>
                </div>
                <div class="mb-4">
                    <label for="price" class="block text-gray-700 font-semibold mb-2">Harga</label>
                    <input type="number" name="price" id="price" class="w-full px-4 py-2 border rounded-md" required>
                </div>
                <div class="mb-4">
                    <label for="category" class="block text-gray-700 font-semibold mb-2">Kategori</label>
                    <select name="category" id="category" class="w-full px-4 py-2 border rounded-md">
                        <option value="Makanan">Makanan</option>
                        <option value="Minuman">Minuman</option>
                        <option value="Dessert">Dessert</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label for="image" class="block text-gray-700 font-semibold mb-2">Gambar Menu</label>
                    <input type="file" name="image" id="image" class="w-full px-4 py-2 border rounded-md">
                </div>
                <div class="mb-4">
                    <label for="model_3d" class="block text-gray-700 font-semibold mb-2">Upload Model 3D (ZIP)</label>
                    <input type="file" name="model_zip" accept=".zip" required>
                </div>
                <div class="flex justify-between">
                    <a href="../dashboard.php" class="bg-gray-400 text-white py-2 px-4 rounded hover:bg-gray-500 transition">
                        Kembali
                    </a>
                    <button type="submit" class="bg-blue-500 text-white py-2 px-4 rounded hover:bg-blue-600 transition">
                        Tambah Menu
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
