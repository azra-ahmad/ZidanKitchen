<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}

include '../config/db.php';
date_default_timezone_set('Asia/Jakarta');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $discount = $_POST['discount'];
    $promo_type = $_POST['promo_type'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $category_target = $_POST['category_target'] ?? null;
    $bundle_price = $_POST['bundle_price'] ?? null;
    
    // Validations
    if (strtotime($start_date) > strtotime($end_date)) {
        $error = 'Tanggal mulai tidak boleh setelah tanggal berakhir';
    } 
    
    // Validate discount percentage 
    if ($discount > 100) {
        $error = 'Diskon tidak boleh melebihi 100%';
    }
    
    // Berjalan jika tidak ada error
    if (empty($error)) {
        // Handle image upload
        if ($_FILES['image']['name']) {
            $image = $_FILES['image']['name'];
            $target_file = "../assets/images/" . basename($image);
            
            // Cek apakah image file valid
            $check = getimagesize($_FILES['image']['tmp_name']);
            if ($check === false) {
                $error = 'File yang diupload bukan gambar yang valid';
            } elseif (!move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $error = 'Gagal mengupload gambar';
            }
        } else {
            $image = "default.png";
        }
        
        if (empty($error)) {
            $query = $conn->prepare("INSERT INTO promos (title, description, start_date, end_date, discount, promo_type, category_target, bundle_price, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $query->bind_param("ssssissis", $title, $description, $start_date, $end_date, $discount, $promo_type, $category_target, $bundle_price, $image);

            if ($query->execute()) {
                $success = 'Promo berhasil ditambahkan!';
            } else {
                $error = 'Gagal menambahkan promo: ' . $conn->error;
            }
        }
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto">
            <div class="bg-white rounded-xl shadow-md overflow-hidden">
                <div class="bg-gradient-to-r from-orange-500 to-red-500 p-6 text-white">
                    <h1 class="text-2xl font-bold">
                        <i class="fas fa-tags mr-2"></i> Tambah Promo Baru
                    </h1>
                    <p class="text-orange-100">Isi formulir di bawah untuk membuat promo baru</p>
                </div>
                
                <!-- Error/Success Messages -->
                <?php if ($error): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mx-6 mt-4 rounded">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            <p><?php echo $error; ?></p>
                        </div>
                    </div>
                <?php elseif ($success): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mx-6 mt-4 rounded">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle mr-2"></i>
                            <p><?php echo $success; ?></p>
                        </div>
                        <a href="promos.php" class="mt-2 inline-block text-sm text-green-600 hover:underline">
                            <i class="fas fa-arrow-left mr-1"></i> Kembali ke daftar promo
                        </a>
                    </div>
                <?php endif; ?>
                
                <!-- Form -->
                <form method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="block text-gray-700 font-medium">Nama Promo <span class="text-red-500">*</span></label>
                            <input type="text" name="title" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-300 focus:border-orange-300" required>
                        </div>
                        
                        <div class="space-y-2">
                            <label class="block text-gray-700 font-medium">Jenis Promo <span class="text-red-500">*</span></label>
                            <select name="promo_type" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-300 focus:border-orange-300" required>
                                <option value="">Pilih Jenis Promo</option>
                                <option value="discount">Diskon</option>
                                <option value="buy2get1">Beli 2 Gratis 1</option>
                                <option value="bundle">Bundle</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="space-y-2">
                        <label class="block text-gray-700 font-medium">Deskripsi <span class="text-red-500">*</span></label>
                        <textarea name="description" rows="3" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-300 focus:border-orange-300" required></textarea>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="block text-gray-700 font-medium">Tanggal Mulai <span class="text-red-500">*</span></label>
                            <input type="date" name="start_date" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-300 focus:border-orange-300" required>
                        </div>
                        
                        <div class="space-y-2">
                            <label class="block text-gray-700 font-medium">Tanggal Berakhir <span class="text-red-500">*</span></label>
                            <input type="date" name="end_date" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-300 focus:border-orange-300" required>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="block text-gray-700 font-medium">Diskon (%)</label>
                            <div class="relative">
                                <input type="number" name="discount" min="0" max="100" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-300 focus:border-orange-300" placeholder="0-100">
                                <span class="absolute right-3 top-2 text-gray-400">%</span>
                            </div>
                            <p class="text-sm text-gray-500">Masukkan nilai antara 0-100</p>
                        </div>
                        
                        <div class="space-y-2">
                            <label class="block text-gray-700 font-medium">Kategori Target</label>
                            <select name="category_target" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-300 focus:border-orange-300">
                                <option value="">Semua Kategori</option>
                                <option value="makanan">Makanan</option>
                                <option value="minuman">Minuman</option>
                                <option value="dessert">Dessert</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="space-y-2">
                        <label class="block text-gray-700 font-medium">Harga Bundle (Rp)</label>
                        <div class="relative">
                            <span class="absolute left-3 top-2 text-gray-500">Rp</span>
                            <input type="number" name="bundle_price" min="0" class="w-full px-10 py-2 border rounded-lg focus:ring-2 focus:ring-orange-300 focus:border-orange-300">
                        </div>
                    </div>
                    
                    <div class="space-y-2">
                        <label class="block text-gray-700 font-medium">Gambar Promo</label>
                        <div class="flex items-center justify-center w-full">
                            <label class="flex flex-col w-full h-32 border-2 border-dashed rounded-lg hover:bg-gray-50 hover:border-orange-300 transition-all">
                                <div class="flex flex-col items-center justify-center pt-7">
                                    <i class="fas fa-cloud-upload-alt text-3xl text-gray-400"></i>
                                    <p class="pt-1 text-sm text-gray-600">Upload gambar promo</p>
                                </div>
                                <input type="file" name="image" class="opacity-0 absolute">
                            </label>
                        </div>
                        <p class="text-sm text-gray-500">Format: JPG, PNG (Maksimal 2MB)</p>
                    </div>
                    
                    <div class="flex justify-end space-x-3 pt-4">
                        <a href="promos.php" class="px-6 py-2 border rounded-lg text-gray-700 hover:bg-gray-100 transition-colors">
                            <i class="fas fa-times mr-2"></i> Batal
                        </a>
                        <button type="submit" class="px-6 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition-colors">
                            <i class="fas fa-save mr-2"></i> Simpan Promo
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // client-side validasi
        document.querySelector('form').addEventListener('submit', function(e) {
            const discount = document.querySelector('input[name="discount"]');
            if (discount.value > 100) {
                alert('Diskon tidak boleh melebihi 100%');
                e.preventDefault();
                discount.focus();
                return false;
            }
            
            const startDate = new Date(document.querySelector('input[name="start_date"]').value);
            const endDate = new Date(document.querySelector('input[name="end_date"]').value);
            
            if (startDate > endDate) {
                alert('Tanggal mulai tidak boleh setelah tanggal berakhir');
                e.preventDefault();
                return false;
            }
        });
        
        // Show selected file name
        document.querySelector('input[type="file"]').addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name || 'Belum ada file dipilih';
            const uploadText = e.target.parentElement.querySelector('p');
            if (uploadText) {
                uploadText.textContent = fileName;
                uploadText.className = 'pt-1 text-sm text-orange-600 font-medium';
            }
        });
    </script>
</body>
</html>