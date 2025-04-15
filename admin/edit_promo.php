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

$error = '';
$success = '';

// Fetch promo data
$promo_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($promo_id <= 0) {
    $_SESSION['error'] = "Promo tidak valid.";
    header("Location: promos.php");
    exit;
}

$stmt = $conn->prepare("SELECT * FROM promos WHERE promo_id = ?");
$stmt->bind_param("i", $promo_id);
$stmt->execute();
$promo_result = $stmt->get_result();
if ($promo_result->num_rows === 0) {
    $stmt->close();
    $_SESSION['error'] = "Promo tidak ditemukan.";
    header("Location: promos.php");
    exit;
}
$promo = $promo_result->fetch_assoc();
$stmt->close();

// Fetch associated menu IDs
$stmt = $conn->prepare("SELECT menu_id FROM promo_menu WHERE promo_id = ?");
$stmt->bind_param("i", $promo_id);
$stmt->execute();
$result = $stmt->get_result();
$menu_ids = [];
while ($row = $result->fetch_assoc()) {
    $menu_ids[] = $row['menu_id'];
}
$stmt->close();

// Fetch all menu items for dropdown
$menu_items = $conn->query("SELECT menu_id, nama_menu FROM menu");
if (!$menu_items) {
    $error = "Gagal mengambil data menu: " . $conn->error;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $title = $conn->real_escape_string($_POST['title']);
        $description = $conn->real_escape_string($_POST['description']);
        $promo_type = $_POST['promo_type'];
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        $discount = !empty($_POST['discount']) ? (int)$_POST['discount'] : null;
        $bundle_discount_value = !empty($_POST['bundle_discount_value']) ? (float)$_POST['bundle_discount_value'] : null;
        $bundle_price = !empty($_POST['bundle_price']) ? (int)$_POST['bundle_price'] : null;
        $new_menu_ids = isset($_POST['menu_ids']) ? array_map('intval', $_POST['menu_ids']) : [];

        // Validations
        if (empty($title) || empty($description) || empty($promo_type) || empty($start_date) || empty($end_date)) {
            throw new Exception("Semua kolom wajib diisi.");
        }
        if (strtotime($start_date) > strtotime($end_date)) {
            throw new Exception("Tanggal mulai tidak boleh setelah tanggal berakhir.");
        }
        if ($promo_type === 'discount') {
            if ($discount === null || $discount < 0 || $discount > 100) {
                throw new Exception("Diskon harus antara 0-100%.");
            }
            if (empty($new_menu_ids)) {
                throw new Exception("Pilih setidaknya 1 menu untuk promo diskon.");
            }
        } elseif ($promo_type === 'bundle') {
            if (count($new_menu_ids) < 2) {
                throw new Exception("Pilih minimal 2 menu untuk promo bundle.");
            }
            if ($bundle_discount_value !== null && ($bundle_discount_value < 0 || $bundle_discount_value > 100)) {
                throw new Exception("Nilai diskon bundle harus antara 0-100%.");
            }
        }
        if ($bundle_price !== null && $bundle_price < 0) {
            throw new Exception("Harga bundle tidak boleh negatif.");
        }

        // Handle image upload
        $image = $promo['image'];
        if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                switch ($_FILES['image']['error']) {
                    case UPLOAD_ERR_INI_SIZE:
                    case UPLOAD_ERR_FORM_SIZE:
                        throw new Exception("Ukuran file terlalu besar (maksimal 2MB).");
                    default:
                        throw new Exception("Terjadi kesalahan saat mengunggah file.");
                }
            }

            $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
            $check = getimagesize($_FILES['image']['tmp_name']);
            if ($check === false || !in_array($check['mime'], $allowed_types)) {
                throw new Exception("File harus berupa gambar JPG atau PNG.");
            }
            if ($_FILES['image']['size'] > 2 * 1024 * 1024) {
                throw new Exception("Ukuran file terlalu besar (maksimal 2MB).");
            }

            $image_ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $image_name = uniqid('promo_') . '.' . $image_ext;
            $target_dir = "../assets/images/";
            $target_file = $target_dir . $image_name;

            if (!move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                throw new Exception("Gagal mengunggah gambar.");
            }
            $image = $image_name;

            if ($promo['image'] !== 'default.png' && file_exists("../assets/images/" . $promo['image'])) {
                unlink("../assets/images/" . $promo['image']);
            }
        }

        // Start transaction
        $conn->begin_transaction();

        // Update promos table
        $stmt = $conn->prepare("
            UPDATE promos SET 
                title = ?, description = ?, promo_type = ?, discount = ?, 
                bundle_price = ?, bundle_discount_value = ?, 
                image = ?, start_date = ?, end_date = ?
            WHERE promo_id = ?
        ");
        $stmt->bind_param(
            "sssisdsssi",
            $title,
            $description,
            $promo_type,
            $discount,
            $bundle_price,
            $bundle_discount_value,
            $image,
            $start_date,
            $end_date,
            $promo_id
        );

        if (!$stmt->execute()) {
            throw new Exception("Gagal memperbarui promo: " . $stmt->error);
        }
        $stmt->close();

        // Sync promo_menu table
        $stmt = $conn->prepare("DELETE FROM promo_menu WHERE promo_id = ?");
        $stmt->bind_param("i", $promo_id);
        if (!$stmt->execute()) {
            throw new Exception("Gagal menghapus menu lama: " . $stmt->error);
        }
        $stmt->close();

        if (!empty($new_menu_ids)) {
            $stmt = $conn->prepare("INSERT INTO promo_menu (promo_id, menu_id) VALUES (?, ?)");
            foreach ($new_menu_ids as $menu_id) {
                $stmt->bind_param("ii", $promo_id, $menu_id);
                if (!$stmt->execute()) {
                    throw new Exception("Gagal menambahkan menu ke promo: " . $stmt->error);
                }
            }
            $stmt->close();
        }

        // Commit transaction
        $conn->commit();
        $_SESSION['success'] = "Promo berhasil diperbarui!";
        header("Location: promos.php");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Promo</title>
    <link rel="icon" type="image/x-icon" href="../assets/images/logo_oren.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <style>
        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: #f97316;
            border: 1px solid #f97316;
            color: white;
        }
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
            color: white;
        }
        .file-upload {
            transition: all 0.3s ease;
        }
        .file-upload:hover {
            border-color: #f97316;
            background-color: #fff7ed;
        }
    </style>
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
                    <i class="fas fa-tags mr-2"></i> Edit Promo
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
                        <p><?= htmlspecialchars($error) ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Form -->
            <form method="POST" enctype="multipart/form-data" class="bg-white p-6 rounded-xl shadow-md space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-gray-700 font-medium">Nama Promo <span class="text-red-500">*</span></label>
                        <input type="text" name="title" value="<?= htmlspecialchars($promo['title']) ?>" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-300 focus:border-orange-300">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-medium">Jenis Promo <span class="text-red-500">*</span></label>
                        <select name="promo_type" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-300 focus:border-orange-300">
                            <option value="">Pilih Jenis Promo</option>
                            <option value="discount" <?= $promo['promo_type'] === 'discount' ? 'selected' : '' ?>>Diskon</option>
                            <option value="bundle" <?= $promo['promo_type'] === 'bundle' ? 'selected' : '' ?>>Bundle</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-gray-700 font-medium">Deskripsi <span class="text-red-500">*</span></label>
                    <textarea name="description" rows="3" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-300 focus:border-orange-300"><?= htmlspecialchars($promo['description']) ?></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-gray-700 font-medium">Tanggal Mulai <span class="text-red-500">*</span></label>
                        <input type="date" name="start_date" value="<?= htmlspecialchars($promo['start_date']) ?>" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-300 focus:border-orange-300">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-medium">Tanggal Berakhir <span class="text-red-500">*</span></label>
                        <input type="date" name="end_date" value="<?= htmlspecialchars($promo['end_date']) ?>" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-300 focus:border-orange-300">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="regular-discount-field">
                        <label class="block text-gray-700 font-medium">Diskon (%)</label>
                        <div class="relative">
                            <input type="number" name="discount" min="0" max="100" value="<?= htmlspecialchars($promo['discount'] ?? '') ?>" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-300 focus:border-orange-300">
                            <span class="absolute right-3 top-2 text-gray-400">%</span>
                        </div>
                        <p class="text-sm text-gray-500">Masukkan nilai antara 0-100 (kosongkan jika bundle)</p>
                    </div>
                    <div class="menu-select-field">
                        <label class="block text-gray-700 font-medium mb-2">Menu Target <span class="text-red-500">*</span></label>
                        <select name="menu_ids[]" multiple class="menu-select w-full px-4 py-2 border rounded-lg">
                            <?php while ($item = $menu_items->fetch_assoc()): ?>
                                <option value="<?= $item['menu_id'] ?>" <?= in_array($item['menu_id'], $menu_ids) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($item['nama_menu']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <p class="text-sm text-gray-500 mt-1">Pilih satu menu untuk diskon, minimal dua untuk bundle</p>
                    </div>
                </div>

                <div id="bundle-fields" class="space-y-4" style="display: none;">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-gray-700 font-medium">Diskon Persentase Bundle</label>
                            <div class="relative">
                                <input type="number" name="bundle_discount_value" min="0" max="100" step="0.01" value="<?= htmlspecialchars($promo['bundle_discount_value'] ?? '') ?>" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-300 focus:border-orange-300">
                                <span class="absolute right-3 top-2 text-gray-400">%</span>
                            </div>
                            <p class="text-sm text-gray-500">Masukkan nilai antara 0-100 (kosongkan jika tidak ada diskon)</p>
                        </div>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-medium">Harga Bundle (Opsional)</label>
                        <input type="number" name="bundle_price" min="0" value="<?= htmlspecialchars($promo['bundle_price'] ?? '') ?>" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-300 focus:border-orange-300">
                        <p class="text-sm text-gray-500">Masukkan harga khusus untuk bundle jika ada</p>
                    </div>
                </div>

                <div>
                    <label class="block text-gray-700 font-medium">Gambar Promo</label>
                    <div class="mb-2">
                        <img src="../assets/images/<?= htmlspecialchars($promo['image']) ?>" alt="Current Image" class="w-32 h-32 object-cover rounded-lg shadow-sm">
                    </div>
                    <div class="flex items-center justify-center w-full">
                        <label class="file-upload flex flex-col w-full h-32 border-2 border-dashed rounded-lg hover:bg-gray-50 hover:border-orange-300 transition">
                            <div class="flex flex-col items-center justify-center pt-7">
                                <i class="fas fa-cloud-upload-alt text-3xl text-gray-400"></i>
                                <p class="pt-1 text-sm text-gray-600">Upload gambar baru (kosongkan jika tidak diganti)</p>
                            </div>
                            <input type="file" name="image" accept="image/*" class="opacity-0 absolute">
                        </label>
                    </div>
                    <p class="text-sm text-gray-500">Format: JPG, PNG (maks. 2MB)</p>
                </div>

                <div class="flex justify-end space-x-3 pt-4">
                    <a href="promos.php" class="px-6 py-2 border rounded-lg text-gray-700 hover:bg-gray-100 transition">
                        <i class="fas fa-times mr-2"></i> Batal
                    </a>
                    <button type="submit" class="px-6 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition">
                        <i class="fas fa-save mr-2"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const promoTypeSelect = document.querySelector('select[name="promo_type"]');
            const bundleFields = document.getElementById('bundle-fields');
            const regularDiscountField = document.querySelector('.regular-discount-field');
            const menuSelectField = document.querySelector('.menu-select-field');
            const menuSelect = document.querySelector('.menu-select');

            $(menuSelect).select2({
                placeholder: "Pilih menu target",
                width: '100%',
                closeOnSelect: false
            });

            function togglePromoFields() {
                if (promoTypeSelect.value === 'bundle') {
                    bundleFields.style.display = 'block';
                    regularDiscountField.style.display = 'none';
                    menuSelectField.querySelector('label').textContent = 'Menu Bundle';
                    $(menuSelect).select2({ placeholder: "Pilih minimal 2 menu untuk bundle" });
                } else if (promoTypeSelect.value === 'discount') {
                    bundleFields.style.display = 'none';
                    regularDiscountField.style.display = 'block';
                    menuSelectField.querySelector('label').textContent = 'Menu Target';
                    $(menuSelect).select2({ placeholder: "Pilih satu menu untuk diskon" });
                } else {
                    bundleFields.style.display = 'none';
                    regularDiscountField.style.display = 'none';
                }
            }

            promoTypeSelect.addEventListener('change', togglePromoFields);
            togglePromoFields();
        });
    </script>
</body>
</html>