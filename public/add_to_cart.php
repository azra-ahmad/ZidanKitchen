<?php
session_start();

// Ambil data dari POST
$id_menu = $_POST['id_menu'];
$nama_menu = $_POST['nama_menu'];
$harga = $_POST['harga'];
$gambar = $_POST['gambar'];

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
        'harga' => $harga,
        'gambar' => $gambar,
        'jumlah' => 1
    ];
}

// Kembali ke halaman sebelumnya
header("Location: " . $_SERVER['HTTP_REFERER']);
exit;
