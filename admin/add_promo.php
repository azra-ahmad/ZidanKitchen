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
        $menu_ids = isset($_POST['menu_ids']) ? $_POST['menu_ids'] : [];

        // Validations
        if (empty($title) || empty($description) || empty($promo_type) || empty($start_date) || empty($end_date)) {
            throw new Exception("Semua kolom wajib diisi.");
        }
        if (strtotime($start_date) > strtotime($end_date)) {
            throw new Exception("Tanggal mulai tidak boleh setelah tanggal berakhir.");
        }
        if ($promo_type === 'discount' && ($discount === null || $discount < 0 || $discount > 100)) {
            throw new Exception("Diskon harus antara 0-100%.");
        }
        if ($promo_type === 'bundle' && $bundle_discount_value !== null && ($bundle_discount_value < 0 || $bundle_discount_value > 100)) {
            throw new Exception("Nilai diskon bundle harus antara 0-100%.");
        }
        if ($promo_type === 'bundle' && count($menu_ids) < 2) {
            throw new Exception("Pilih minimal 2 menu untuk promo bundle.");
        }
        if ($promo_type === 'discount' && empty($menu_ids)) {
            throw new Exception("Pilih setidaknya 1 menu untuk promo diskon.");
        }

        // Handle image upload
        $image = "default.png"; // Default image
        if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            error_log("DEBUG: File upload info: " . print_r($_FILES['image'], true));
            if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                switch ($_FILES['image']['error']) {
                    case UPLOAD_ERR_INI_SIZE:
                    case UPLOAD_ERR_FORM_SIZE:
                        throw new Exception("Ukuran file terlalu besar (maksimal 2MB).");
                    case UPLOAD_ERR_PARTIAL:
                        throw new Exception("File hanya terunggah sebagian.");
                    case UPLOAD_ERR_NO_TMP_DIR:
                        throw new Exception("Direktori sementara untuk upload tidak ditemukan.");
                    case UPLOAD_ERR_CANT_WRITE:
                        throw new Exception("Gagal menulis file ke disk.");
                    default:
                        throw new Exception("Terjadi kesalahan saat mengunggah file: Error code " . $_FILES['image']['error']);
                }
            }

            $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
            $check = getimagesize($_FILES['image']['tmp_name']);
            if ($check === false) {
                throw new Exception("File yang diupload bukan gambar yang valid.");
            }
            if (!in_array($check['mime'], $allowed_types)) {
                throw new Exception("Format file harus JPG atau PNG.");
            }
            if ($_FILES['image']['size'] > 2 * 1024 * 1024) {
                throw new Exception("Ukuran file terlalu besar (maksimal 2MB).");
            }

            // Sanitasi nama file asli
            $original_name = pathinfo($_FILES['image']['name'], PATHINFO_FILENAME);
            $image_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            // Ganti spasi dengan underscore, hapus karakter berbahaya
            $sanitized_name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $original_name);
            $sanitized_name = trim($sanitized_name, '_');
            if (empty($sanitized_name)) {
                $sanitized_name = 'promo'; // Fallback jika nama file kosong setelah sanitasi
            }

            $target_dir = "../assets/images/";
            $image_name = $sanitized_name . '.' . $image_ext;
            $target_file = $target_dir . $image_name;

            // Cek apakah file sudah ada, tambahkan sufiks jika perlu
            $counter = 1;
            while (file_exists($target_file)) {
                $image_name = $sanitized_name . '_' . $counter . '.' . $image_ext;
                $target_file = $target_dir . $image_name;
                $counter++;
            }

            // Ensure upload directory exists and is writable
            if (!file_exists($target_dir)) {
                if (!mkdir($target_dir, 0755, true)) {
                    throw new Exception("Gagal membuat direktori upload: " . $target_dir);
                }
                error_log("DEBUG: Created directory: " . $target_dir);
            }
            if (!is_writable($target_dir)) {
                throw new Exception("Direktori upload tidak dapat ditulis: " . $target_dir);
            }

            // Move uploaded file
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                error_log("DEBUG: Failed to move file to: " . $target_file);
                throw new Exception("Gagal mengunggah gambar ke " . $target_file);
            }
            $image = $image_name;
            error_log("DEBUG: Image uploaded successfully: " . $image);
        } else {
            error_log("DEBUG: No file uploaded, using default.png");
        }

        // Start transaction
        $conn->begin_transaction();

        $stmt = $conn->prepare("
            INSERT INTO promos (title, description, promo_type, discount, bundle_price, bundle_discount_value, image, start_date, end_date)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "sssiidsss",
            $title,
            $description,
            $promo_type,
            $discount,
            $bundle_price,
            $bundle_discount_value,
            $image,
            $start_date,
            $end_date
        );
        if (!$stmt->execute()) {
            throw new Exception("Gagal menambahkan promo: " . $stmt->error);
        }
        $promo_id = $conn->insert_id;
        $stmt->close();
        error_log("DEBUG: Promo inserted with ID: " . $promo_id);

        // Insert into promo_menu table
        if (!empty($menu_ids)) {
            $stmt = $conn->prepare("INSERT INTO promo_menu (promo_id, menu_id) VALUES (?, ?)");
            foreach ($menu_ids as $menu_id) {
                $menu_id = (int)$menu_id;
                $stmt->bind_param("ii", $promo_id, $menu_id);
                if (!$stmt->execute()) {
                    throw new Exception("Gagal menambahkan menu ke promo: " . $stmt->error);
                }
            }
            $stmt->close();
        }

        // Commit transaction
        $conn->commit();
        $_SESSION['success'] = "Promo berhasil ditambahkan!";
        header("Location: promos.php");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        $error = $e->getMessage();
        error_log("ERROR: " . $e->getMessage());
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
                        <p><?= htmlspecialchars($error) ?></p>
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
                    <div class="regular-discount-field">
                        <label class="block text-gray-700 font-medium">Diskon (%)</label>
                        <div class="relative">
                            <input type="number" name="discount" min="0" max="100" placeholder="0-100" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-300 focus:border-orange-300">
                            <span class="absolute right-3 top-2 text-gray-400">%</span>
                        </div>
                        <p class="text-sm text-gray-500">Masukkan nilai antara 0-100</p>
                    </div>
                    <div class="mb-4 menu-select-field">
                        <label class="block text-gray-700 font-medium mb-2">Menu Target <span class="text-red-500">*</span></label>
                        <select name="menu_ids[]" multiple class="menu-select w-full px-4 py-2 border rounded-lg">
                            <?php while ($item = $menu_items->fetch_assoc()): ?>
                                <option value="<?= $item['menu_id'] ?>"><?= htmlspecialchars($item['nama_menu']) ?></option>
                            <?php endwhile; ?>
                        </select>
                        <p class="text-sm text-gray-500 mt-1">Pilih satu menu untuk diskon, minimal dua untuk bundle</p>
                    </div>
                </div>

                <!-- Bundle Fields -->
                <div id="bundle-fields" style="display: none;" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-gray-700 font-medium">Nilai Diskon Bundle (%)</label>
                            <div class="relative">
                                <input type="number" name="bundle_discount_value" min="0" max="100" step="0.01" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-300 focus:border-orange-300">
                                <span class="absolute right-3 top-2 text-gray-400">%</span>
                            </div>
                            <p class="text-sm text-gray-500">Masukkan nilai antara 0-100 (kosongkan jika tidak ada diskon)</p>
                        </div>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-medium">Harga Bundle (Opsional)</label>
                        <input type="number" name="bundle_price" min="0" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-300 focus:border-orange-300">
                        <p class="text-sm text-gray-500">Masukkan harga khusus untuk bundle jika ada</p>
                    </div>
                </div>

                <div>
                    <label class="block text-gray-700 font-medium">Gambar Promo</label>
                    <label class="flex flex-col items-center justify-center h-32 border-2 border-dashed rounded-lg hover:bg-gray-50 hover:border-orange-300 cursor-pointer transition">
                        <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-1"></i>
                        <p class="text-sm text-gray-600">Upload gambar promo</p>
                        <input type="file" name="image" accept="image/*" class="hidden">
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
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const promoTypeSelect = document.querySelector('select[name="promo_type"]');
            const bundleFields = document.getElementById('bundle-fields');
            const regularDiscountField = document.querySelector('.regular-discount-field');
            const menuSelectField = document.querySelector('.menu-select-field');
            const menuSelect = document.querySelector('.menu-select');
            const fileInput = document.querySelector('input[type="file"]');
            const fileLabel = fileInput?.closest('label');
            const fileText = fileLabel?.querySelector('p');

            // Initialize Select2
            $(menuSelect).select2({
                placeholder: "Pilih menu target",
                width: '100%',
                closeOnSelect: false
            });

            // Toggle fields based on promo type
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
                    menuSelectField.style.display = 'none';
                }
            }

            // Validate bundle discount
            function validateBundleDiscount() {
                const bundleDiscountValue = document.querySelector('input[name="bundle_discount_value"]');
                if (bundleDiscountValue) {
                    const value = parseFloat(bundleDiscountValue.value);
                    if (bundleDiscountValue.value !== '' && (isNaN(value) || value < 0 || value > 100)) {
                        bundleDiscountValue.setCustomValidity('Diskon bundle harus antara 0-100%');
                        return false;
                    }
                    bundleDiscountValue.setCustomValidity('');
                }
                return true;
            }

            // Validate form
            function validateForm(e) {
                let isValid = true;
                const errorMessages = [];
                if (!form.checkValidity()) {
                    form.reportValidity();
                    return false;
                }
                const startDate = new Date(document.querySelector('input[name="start_date"]').value);
                const endDate = new Date(document.querySelector('input[name="end_date"]').value);
                if (startDate > endDate) {
                    errorMessages.push('Tanggal mulai tidak boleh setelah tanggal berakhir');
                    isValid = false;
                }
                if (promoTypeSelect.value === 'discount') {
                    const discount = document.querySelector('input[name="discount"]');
                    if (!discount.value || discount.value < 0 || discount.value > 100) {
                        errorMessages.push('Diskon harus antara 0-100%');
                        isValid = false;
                    }
                    const selectedMenus = $(menuSelect).val();
                    if (!selectedMenus || selectedMenus.length === 0) {
                        errorMessages.push('Pilih setidaknya satu menu untuk diskon');
                        isValid = false;
                    }
                } else if (promoTypeSelect.value === 'bundle') {
                    const selectedMenus = $(menuSelect).val();
                    if (!selectedMenus || selectedMenus.length < 2) {
                        errorMessages.push('Pilih minimal 2 menu untuk bundle');
                        isValid = false;
                    }
                    if (!validateBundleDiscount()) {
                        errorMessages.push('Masukkan nilai diskon bundle yang valid');
                        isValid = false;
                    }
                    const bundlePrice = document.querySelector('input[name="bundle_price"]');
                    if (bundlePrice.value && bundlePrice.value < 0) {
                        errorMessages.push('Harga bundle tidak boleh negatif');
                        isValid = false;
                    }
                }
                if (fileInput && fileInput.files.length > 0) {
                    const file = fileInput.files[0];
                    const validTypes = ['image/jpeg', 'image/png', 'image/jpg'];
                    const maxSize = 2 * 1024 * 1024;
                    if (!validTypes.includes(file.type)) {
                        errorMessages.push('Format file harus JPG atau PNG');
                        isValid = false;
                    }
                    if (file.size > maxSize) {
                        errorMessages.push('Ukuran file terlalu besar (maksimal 2MB)');
                        isValid = false;
                    }
                }
                if (!isValid) {
                    e.preventDefault();
                    showErrorMessages(errorMessages);
                    return false;
                }
                return true;
            }

            // Show error messages
            function showErrorMessages(messages) {
                let errorContainer = document.getElementById('client-side-errors');
                if (!errorContainer) {
                    errorContainer = document.createElement('div');
                    errorContainer.id = 'client-side-errors';
                    errorContainer.className = 'bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded';
                    form.parentNode.insertBefore(errorContainer, form);
                }
                errorContainer.innerHTML = `
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <div>
                            ${messages.map(msg => `<p class="mb-1">${msg}</p>`).join('')}
                        </div>
                    </div>
                `;
                errorContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }

            // Update file name
            function updateFileName() {
                if (fileInput && fileText) {
                    const fileName = fileInput.files[0]?.name || 'Upload gambar promo';
                    fileText.textContent = fileName;
                    fileText.className = fileInput.files[0] ? 'text-sm text-orange-600 font-medium' : 'text-sm text-gray-600';
                }
            }

            // Initialize event listeners
            promoTypeSelect.addEventListener('change', togglePromoFields);
            document.querySelector('input[name="bundle_discount_value"]').addEventListener('input', validateBundleDiscount);
            document.querySelector('input[name="discount"]').addEventListener('input', function() {
                this.setCustomValidity(this.value > 100 ? 'Diskon tidak boleh melebihi 100%' : '');
            });
            form.addEventListener('submit', validateForm);
            fileInput.addEventListener('change', updateFileName);
            togglePromoFields();
        });
    </script>
</body>
</html>