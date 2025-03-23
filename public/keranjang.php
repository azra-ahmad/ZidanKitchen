<?php
session_start();

// Contoh data dummy jika belum ada session keranjang
if (!isset($_SESSION['keranjang'])) {
    $_SESSION['keranjang'] = [
        [
            'id_menu' => 1,
            'nama_menu' => 'sushi',
            'gambar' => 'sushi.png',
            'harga' => 40000,
            'jumlah' => 2
        ],
        [
            'id_menu' => 2,
            'nama_menu' => 'Birthday Cake',
            'gambar' => 'birthdayCake.jpeg',
            'harga' => 150000,
            'jumlah' => 1
        ]
    ];
}

// Tangani penambahan atau pengurangan jumlah
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_SESSION['keranjang'] as $i => $item) {
        $id_menu = $item['id_menu'];

        if (isset($_POST['tambah_' . $id_menu])) {
            $_SESSION['keranjang'][$i]['jumlah']++;
        } elseif (isset($_POST['kurang_' . $id_menu])) {
            $_SESSION['keranjang'][$i]['jumlah']--;
            if ($_SESSION['keranjang'][$i]['jumlah'] <= 0) {
                array_splice($_SESSION['keranjang'], $i, 1);
            }
        }
    }
}


$keranjang = $_SESSION['keranjang'];
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Keranjang - ZidanKitchen</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gradient-to-br from-blue-50 to-white min-h-screen font-sans">

    <!-- Header -->
    <div class="sticky top-0 z-50 bg-white shadow-md px-4 py-4 flex justify-between items-center">
        <h1 class="text-2xl font-bold text-blue-600">Keranjang <span class="text-yellow-400">ZidanKitchen</span></h1>
        <a href="menu.php" class="text-blue-500 hover:text-blue-700">← Kembali</a>
    </div>

    <div class="max-w-4xl mx-auto p-6">
        <?php if (count($keranjang) > 0): ?>
            <form method="POST" class="bg-white rounded-xl shadow-xl p-4 space-y-4 animate-fadeIn">
                <?php
                $total = 0;
                foreach ($keranjang as $item):
                    $subtotal = $item['harga'] * $item['jumlah'];
                    $total += $subtotal;
                ?>
                    <div class="flex items-center gap-4 bg-white rounded-lg shadow-sm p-4">
                        <!-- Gambar -->
                        <img src="../assets/images/<?= htmlspecialchars($item['gambar']); ?>" alt="<?= $item['nama_menu']; ?>" class="w-20 h-20 object-cover rounded-lg">

                        <!-- Info Produk -->
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold"><?= $item['nama_menu']; ?></h3>
                            <p class="text-gray-500 text-sm">Harga: Rp <?= number_format($item['harga'], 0, ',', '.'); ?></p>

                            <!-- Tombol Tambah Kurang -->
                            <div class="flex items-center mt-2 space-x-2">
                                <button name="kurang_<?= ($item['id_menu']) ?>" class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600" type="submit">-</button>
                                <span class="text-gray-700 font-semibold"><?= $item['jumlah']; ?></span>
                                <button name="tambah_<?= ($item['id_menu']) ?>" class="bg-green-500 text-white px-3 py-1 rounded hover:bg-green-600" type="submit">+</button>
                            </div>
                        </div>

                        <!-- Subtotal -->
                        <div class="text-right">
                            <p class="text-blue-600 font-bold">Rp <?= number_format($subtotal, 0, ',', '.'); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- Total -->
                <div class="flex justify-between items-center mt-4">
                    <h3 class="text-xl font-bold text-gray-700">Total:</h3>
                    <p class="text-2xl font-extrabold text-green-600">Rp <?= number_format($total, 0, ',', '.'); ?></p>
                </div>

                <!-- Checkout -->
                <button type="submit" class="w-full mt-4 bg-yellow-400 hover:bg-yellow-500 text-white font-semibold py-3 rounded-xl shadow-md transition duration-300">
                    Checkout Sekarang
                </button>
            </form>
        <?php else: ?>
            <div class="text-center py-12">
                <h2 class="text-2xl font-semibold text-gray-600">Keranjangmu kosong 😢</h2>
                <p class="text-gray-400 mt-2">Yuk tambahkan menu favoritmu!</p>
                <a href="menu.php" class="mt-6 inline-block bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-full transition">Lihat Menu</a>
            </div>
        <?php endif; ?>
    </div>

</body>

</html>

</html>