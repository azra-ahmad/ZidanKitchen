<?php
session_start();
include '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validasi input
    if (!isset($_POST['menu_id']) || !isset($_POST['nama_menu']) || !isset($_POST['harga'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Data menu tidak lengkap']);
        exit;
    }

    $menu_id = (int)$_POST['menu_id'];
    $nama_menu = trim($_POST['nama_menu']);
    $harga = (float)$_POST['harga'];
    $harga_promo = isset($_POST['harga_promo']) ? (float)$_POST['harga_promo'] : $harga;
    $gambar = isset($_POST['gambar']) ? trim($_POST['gambar']) : 'default.jpg';
    $promo_type = isset($_POST['promo_type']) ? trim($_POST['promo_type']) : null;

    // Inisialisasi keranjang jika belum ada
    if (!isset($_SESSION['keranjang'])) {
        $_SESSION['keranjang'] = [];
    }

    // Cari item di keranjang
    $item_index = null;
    foreach ($_SESSION['keranjang'] as $index => $item) {
        if ($item['menu_id'] == $menu_id && $item['promo_type'] == $promo_type) { // Ganti id_menu jadi menu_id, promo_id diganti promo_type
            $item_index = $index;
            break;
        }
    }

    // Jika item sudah ada, tambahkan jumlah
    if ($item_index !== null) {
        $_SESSION['keranjang'][$item_index]['jumlah'] += 1;
    } else {
        // Tambahkan item baru ke keranjang
        $_SESSION['keranjang'][] = [
            'menu_id' => $menu_id, // Ganti id_menu jadi menu_id
            'nama_menu' => $nama_menu,
            'harga' => $harga,
            'harga_promo' => $harga_promo,
            'gambar' => $gambar,
            'jumlah' => 1,
            'promo_type' => $promo_type
        ];
    }

    // Kirim response sukses
    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'Item berhasil ditambahkan ke keranjang']);
    exit;
}

// Jika akses langsung ke file
http_response_code(405);
echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
exit;
?>