<?php
session_start();
include '../config/db.php';

// Pastikan meja_id ada
if (!isset($_SESSION['meja_id'])) {
    header("Location: register.php");
    exit;
}

$meja_id = $_SESSION['meja_id'];
$customer_id = $_SESSION['customer_id'] ?? null;
$order_id = $_SESSION['order_id'] ?? null;

// Kalo ada order_id, cek statusnya
if ($order_id) {
    $stmt = $conn->prepare("SELECT status FROM orders WHERE order_id = ? AND customer_id = ?");
    $stmt->bind_param("ii", $order_id, $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0 || $result->fetch_assoc()['status'] !== 'done') {
        header("Location: success.php");
        exit;
    }
}

// Bersihkan session yang relevan, tapi simpen meja_id
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
        body {
            font-family: 'Inter', sans-serif;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in { animation: fadeIn 1s ease-out forwards; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-xl p-6 max-w-md w-full text-center shadow-sm border border-green-200 animate-fade-in">
        <div class="mb-6">
            <svg class="w-20 h-20 mx-auto text-green-500 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
        </div>
        <h1 class="text-xl font-bold text-gray-800 mb-2">Pesanan Selesai!</h1>
        <p class="text-gray-600 mb-4">Terima kasih sudah memesan di ZidanKitchen. Kami tunggu kunjungan Anda lagi!</p>
        <a href="register.php?table=<?= urlencode($meja_id) ?>" class="block bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg transition duration-200">
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