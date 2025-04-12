<?php
session_start();
include '../config/db.php';

// Pastikan customer_id ada
if (!isset($_SESSION['customer_id'])) {
    header("Location: register.php?table=" . urlencode($_SESSION['id_meja'] ?? ''));
    exit;
}

$customer_id = $_SESSION['customer_id'];
$id_meja = $_SESSION['id_meja'] ?? '';

// Bersihkan session yang relevan, tapi simpen id_meja
unset($_SESSION['customer_id']);
unset($_SESSION['order_id']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Selesai - ZidanKitchen</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.5.1/dist/confetti.browser.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in { animation: fadeIn 1s ease-out forwards; }
    </style>
</head>
<body class="bg-gray-50 font-['Inter'] min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-xl p-6 max-w-md w-full text-center shadow-md border border-blue-100 animate-fade-in">
        <div class="mb-6">
            <svg class="w-20 h-20 mx-auto text-green-500 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-gray-800 mb-2">Pesanan Selesai!</h1>
        <p class="text-gray-600 mb-4">Terima kasih sudah memesan di ZidanKitchen. Kami tunggu kunjungan Anda lagi!</p>
        <a href="register.php?table=<?= urlencode($id_meja) ?>" class="block bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition duration-200">
            Pesan Lagi
        </a>
    </div>
    <script>
        // Trigger confetti on load
        confetti({
            particleCount: 150,
            spread: 90,
            origin: { y: 0.6 }
        });
    </script>
</body>
</html>