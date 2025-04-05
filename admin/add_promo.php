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
    <link rel="icon" type="image/x-icon" href="../assets/images/logo_oren.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen flex">
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
                    <a href="menu.php" class="flex items-center p-3 rounded-lg hover:bg-orange-500 transition-colors">
                        <i class="fas fa-utensils mr-3"></i> Kelola Menu
                    </a>
                </li>
                <li>
                    <a href="promos.php" class="flex items-center p-3 rounded-lg bg-orange-500 transition-colors">
                        <i class="fas fa-tags mr-3"></i> Kelola Promo
                    </a>
                </li>
                <li>
                    <a href="order.php" class="flex items-center p-3 rounded-lg hover:bg-orange-500 transition-colors">
                        <i class="fas fa-receipt mr-3"></i> Kelola Pesanan
                    </a>
                </li>
            </ul>
        </nav>
        <a href="logout.php" class="flex items-center p-3 rounded-lg bg-red-600 hover:bg-red-700 transition-colors">
            <i class="fas fa-sign-out-alt mr-3"></i> Logout
        </a>
    </div>

    <!-- Content -->
    <div class="flex-1 ml-64 p-8">
        <div class="max-w-3xl mx-auto">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-orange-600">
                    <i class="fas fa-tags mr-2"></i> Tambah Promo Baru
                </h1>
                <a href="promos.php" class="text-orange-500 hover:text-orange-700 flex items-center">
                    <i class="fas fa-arrow-left mr-1"></i> Kembali
                </a>
            </div>

            <!-- Alert -->
            <?php if ($error): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <p><?php echo $error; ?></p>
                    </div>
                </div>
            <?php elseif ($success): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle mr-2"></i>
                        <p><?php echo $success; ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Form -->
            <form method="POST" enctype="multipart/form-data" class="bg-white p-6 rounded-xl shadow-md space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-gray-700 font-medium">Nama Promo <span class="text-red-500">*</span></label>
                        <input type="text" name="title" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-300 focus:border-orange-300">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-medium">Jenis Promo <span class="text-red-500">*</span></label>
                        <select name="promo_type" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-300 focus:border-orange-300">
                            <option value="">Pilih Jenis Promo</option>
                            <option value="discount">Diskon</option>
                            <option value="buy2get1">Beli 2 Gratis 1</option>
                            <option value="bundle">Bundle</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-gray-700 font-medium">Deskripsi <span class="text-red-500">*</span></label>
                    <textarea name="description" rows="3" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-300 focus:border-orange-300"></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-gray-700 font-medium">Tanggal Mulai <span class="text-red-500">*</span></label>
                        <input type="date" name="start_date" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-300 focus:border-orange-300">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-medium">Tanggal Berakhir <span class="text-red-500">*</span></label>
                        <input type="date" name="end_date" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-300 focus:border-orange-300">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-gray-700 font-medium">Diskon (%)</label>
                        <div class="relative">
                            <input type="number" name="discount" min="0" max="100" placeholder="0-100" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-300 focus:border-orange-300">
                            <span class="absolute right-3 top-2 text-gray-400">%</span>
                        </div>
                        <p class="text-sm text-gray-500">Masukkan nilai antara 0-100</p>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-medium">Kategori Target</label>
                        <select name="category_target" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-300 focus:border-orange-300">
                            <option value="">Pilih Kategori</option>
                            <option value="makanan">Makanan</option>
                            <option value="minuman">Minuman</option>
                            <option value="dessert">Dessert</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-gray-700 font-medium">Harga Bundle (Rp)</label>
                    <div class="relative">
                        <span class="absolute left-3 top-2 text-gray-500">Rp</span>
                        <input type="number" name="bundle_price" min="0" class="w-full px-10 py-2 border rounded-lg focus:ring-2 focus:ring-orange-300 focus:border-orange-300">
                    </div>
                </div>

                <div>
                    <label class="block text-gray-700 font-medium">Gambar Promo</label>
                    <label class="flex flex-col items-center justify-center h-32 border-2 border-dashed rounded-lg hover:bg-gray-50 hover:border-orange-300 cursor-pointer transition">
                        <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-1"></i>
                        <p class="text-sm text-gray-600">Upload gambar promo</p>
                        <input type="file" name="image" class="hidden">
                    </label>
                    <p class="text-sm text-gray-500">Format: JPG, PNG (maks. 2MB)</p>
                </div>

                <div class="flex justify-end space-x-3 pt-4">
                    <a href="promos.php" class="px-6 py-2 border rounded-lg text-gray-700 hover:bg-gray-100 transition">
                        <i class="fas fa-times mr-2"></i> Batal
                    </a>
                    <button type="submit" class="px-6 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition">
                        <i class="fas fa-save mr-2"></i> Simpan Promo
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.querySelector('form').addEventListener('submit', function(e) {
            const discount = document.querySelector('input[name="discount"]');
            if (discount.value > 100) {
                alert('Diskon tidak boleh melebihi 100%');
                e.preventDefault();
                discount.focus();
                return false;
            }

            const select = document.querySelector('select[name="category_target"]');
            if (select.value === "") select.value = null;

            const startDate = new Date(document.querySelector('input[name="start_date"]').value);
            const endDate = new Date(document.querySelector('input[name="end_date"]').value);
            if (startDate > endDate) {
                alert('Tanggal mulai tidak boleh setelah tanggal berakhir');
                e.preventDefault();
                return false;
            }
        });

        document.querySelector('input[type="file"]').addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name || 'Belum ada file dipilih';
            const label = e.target.closest('label');
            const text = label.querySelector('p');
            if (text) {
                text.textContent = fileName;
                text.className = 'text-sm text-orange-600 font-medium';
            }
        });
    </script>
</body>

</html>