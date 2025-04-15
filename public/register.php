<?php
session_start();
include '../config/db.php';

// PROSES FORM JIKA DISUBMIT
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $table_id = intval($_POST['table_id']);
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);

    // Validasi input
    if (empty($name) || empty($phone)) {
        die("Nama dan nomor HP wajib diisi");
    }

    // 2. Simpan data customer
    $stmt = $conn->prepare("INSERT INTO customers (name, phone, table_id) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $name, $phone, $table_id);
    
    if (!$stmt->execute()) {
        die("Error: Gagal menyimpan data customer");
    }
    $customer_id = $conn->insert_id;

    // 4. Set session
    $_SESSION['customer_id'] = $customer_id;
    $_SESSION['meja_id'] = $table_id;
    
    header("Location: menu.php");
    exit();
}

// TAMPILKAN FORM JIKA GET REQUEST
$table_id = isset($_GET['table']) ? intval($_GET['table']) : die("Parameter meja tidak valid");

// Cek apakah meja valid
$table_exists = $conn->query("SELECT 1 FROM meja WHERE meja_id = $table_id")->num_rows > 0;
if (!$table_exists) {
    die("Meja $table_id tidak ditemukan");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Registrasi Meja <?= $table_id ?> | ZidanKitchen</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: 'Inter', sans-serif; 
            background-color: #f5f5f5; 
            -webkit-font-smoothing: antialiased;
        }
        .brand-gradient { 
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); 
        }
        input:focus { 
            outline: none; 
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3); 
        }
    </style>
</head>
<body class="min-h-screen bg-gray-100 flex flex-col">
    <div class="flex-1 flex items-center justify-center p-4">
        <div class="w-full max-w-[400px] bg-white rounded-2xl shadow-lg p-6 space-y-5">
            <!-- Header -->
            <div class="text-center space-y-2">
                <img src="../assets/images/logo_oren.png" alt="logo" class="mx-auto w-14 h-14 object-contain">
                <h1 class="text-xl font-semibold text-gray-800">Registrasi Meja <?= $table_id ?></h1>
                <p class="text-sm text-gray-500">Masukkan data untuk mulai pesan</p>
            </div>

            <!-- Error Message -->
            <?php if (isset($_GET['error'])): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-3 rounded-lg flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-500 mr-2" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                    <p class="text-red-700 text-sm"><?= htmlspecialchars($_GET['error']) ?></p>
                </div>
            <?php endif; ?>

            <!-- Registration Form -->
            <form method="POST" class="space-y-4">
                <input type="hidden" name="table_id" value="<?= $table_id ?>">
                
                <div class="space-y-1">
                    <label for="name" class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
                    <input 
                        type="text" 
                        id="name" 
                        name="name" 
                        required 
                        class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:ring-0 focus:border-blue-500 transition placeholder:text-gray-400"
                        placeholder="Contoh: Mulyono Aja">
                </div>
                
                <div class="space-y-1">
                    <label for="phone" class="block text-sm font-medium text-gray-700">Nomor HP</label>
                    <input 
                        type="tel" 
                        id="phone" 
                        name="phone" 
                        required 
                        class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:ring-0 focus:border-blue-500 transition placeholder:text-gray-400"
                        placeholder="Contoh: 08123456789">
                </div>

                <button 
                    type="submit" 
                    class="w-full brand-gradient text-white py-3 rounded-lg text-sm font-medium hover:opacity-90 transition flex items-center justify-center active:scale-[0.98]">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    Mulai Pesan Sekarang
                </button>
            </form>

            <!-- Footer Note -->
            <p class="text-center text-gray-500 text-xs">
                Dengan melanjutkan, Anda menyetujui 
                <a href="#" class="text-blue-600 hover:underline">Syarat & Ketentuan</a> kami.
            </p>
        </div>
    </div>
</body>
</html>