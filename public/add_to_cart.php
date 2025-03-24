<?php
session_start();

// Ambil data dari POST
$id_menu = $_POST['id_menu'];
$nama_menu = $_POST['nama_menu'];
$harga_asli = $_POST['harga'];
$harga_promo = $_POST['harga_promo'];
$gambar = $_POST['gambar'];

// Gunakan harga promo jika ada, kalau tidak ada pakai harga asli
$harga_final = !empty($harga_promo) ? $harga_promo : $harga_asli;

// Cek apakah sudah ada keranjang
if (!isset($_SESSION['keranjang'])) {
    $_SESSION['keranjang'] = [];
}

$found = false;

// Cek apakah item sudah ada di keranjang
foreach ($_SESSION['keranjang'] as &$item) {
    if ($item['id_menu'] == $id_menu) {
        $item['jumlah'] += 1;
        $found = true;
        break;
    }
}

if (!$found) {
    $_SESSION['keranjang'][] = [
        'id_menu' => $id_menu,
        'nama_menu' => $nama_menu,
        'harga' => $harga_final, 
        'gambar' => $gambar,
        'jumlah' => 1
    ];
}

// Kembali ke halaman sebelumnya
header("Location: " . $_SERVER['HTTP_REFERER']);
exit;
