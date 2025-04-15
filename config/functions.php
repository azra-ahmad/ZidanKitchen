<?php
date_default_timezone_set('Asia/Jakarta');

// Fungsi untuk ambil promo aktif
function getActivePromos($conn) {
    $today = date('Y-m-d');
    // Update query: Join sama tabel promo_menu buat ambil menu yang terkait promo
    $query = "SELECT p.*, pm.menu_id 
              FROM promos p 
              LEFT JOIN promo_menu pm ON p.promo_id = pm.promo_id
              WHERE p.start_date <= ? AND p.end_date >= ? 
              AND (p.promo_type = 'discount' OR p.promo_type = 'bundle')";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $today, $today);
    $stmt->execute();
    $result = $stmt->get_result();

    // Group data promo biar tiap promo punya array menu_ids
    $promos = [];
    while ($row = $result->fetch_assoc()) {
        $promo_id = $row['promo_id'];
        // Kalo promo belum ada di array, init dulu
        if (!isset($promos[$promo_id])) {
            $promos[$promo_id] = [
                'promo_id' => $row['promo_id'],
                'title' => $row['title'],
                'description' => $row['description'],
                'discount' => $row['discount'],
                'promo_type' => $row['promo_type'],
                'bundle_price' => $row['bundle_price'],
                'bundle_discount_type' => $row['bundle_discount_type'],
                'bundle_discount_value' => $row['bundle_discount_value'],
                'image' => $row['image'],
                'start_date' => $row['start_date'],
                'end_date' => $row['end_date'],
                'menu_ids' => [] // Gantikan menu_target dan bundle_items
            ];
        }
        // Tambahin menu_id ke array menu_ids
        if ($row['menu_id']) {
            $promos[$promo_id]['menu_ids'][] = $row['menu_id'];
        }
    }
    $stmt->close();
    return array_values($promos); // Ubah ke array biasa (tanpa promo_id sebagai key)
}

// Fungsi untuk hitung harga setelah diskon persentase
function applyDiscount($price, $discount) {
    return $price - ($price * $discount / 100);
}

// Fungsi untuk cek apakah menu dapet diskon (promo tipe Discount)
function getMenuDiscount($menu_id, $promos) {
    foreach ($promos as $promo) {
        if ($promo['promo_type'] === 'discount' && !empty($promo['menu_ids'])) {
            if (in_array($menu_id, $promo['menu_ids'])) {
                return $promo['discount'];
            }
        }
    }
    return 0;
}

// Fungsi untuk cek apakah keranjang memenuhi syarat promo bundle
function checkBundlePromo($cart, $promo) {
    if ($promo['promo_type'] !== 'bundle' || empty($promo['menu_ids'])) {
        return false;
    }

    // Cek apakah semua item dalam bundle ada di keranjang
    foreach ($promo['menu_ids'] as $bundle_item_id) {
        $found = false;
        foreach ($cart as $cart_item) {
            // Ubah id_menu jadi menu_id sesuai struktur tabel
            if ($cart_item['menu_id'] == $bundle_item_id && $cart_item['jumlah'] > 0) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            return false; // Kalo ada satu item bundle yang gak ada di keranjang, promo gak berlaku
        }
    }
    return true;
}

// Fungsi untuk hitung total diskon bundle
function applyBundleDiscount($cart, $menu_data, $promo) {
    if (!checkBundlePromo($cart, $promo)) {
        return 0; // Kalo gak memenuhi syarat, diskon 0
    }

    // Hitung total harga asli untuk item dalam bundle
    $total_original = 0;
    $bundle_items = $promo['menu_ids'];
    foreach ($cart as $cart_item) {
        if (in_array($cart_item['menu_id'], $bundle_items)) {
            $total_original += $menu_data[$cart_item['menu_id']]['harga'] * $cart_item['jumlah'];
        }
    }

    // Hitung diskon berdasarkan bundle_discount_type
    if ($promo['bundle_discount_type'] === 'percentage') {
        $discount_value = $promo['bundle_discount_value'];
        $discount_amount = $total_original * ($discount_value / 100);
    } else { // fixed
        $discount_amount = $promo['bundle_discount_value'];
    }

    return $discount_amount;
}

// Fungsi untuk hitung harga per item setelah diskon (termasuk bundle)
function getItemPrice($menu_id, $cart, $menu_data, $promos) {
    $original_price = $menu_data[$menu_id]['harga'];

    // Cek promo Discount
    $discount = getMenuDiscount($menu_id, $promos);
    if ($discount > 0) {
        return applyDiscount($original_price, $discount);
    }

    // Cek promo Bundle
    $bundle_discount_total = 0;
    $applicable_bundle_promo = null;
    foreach ($promos as $promo) {
        if (checkBundlePromo($cart, $promo)) {
            $bundle_discount_total = applyBundleDiscount($cart, $menu_data, $promo);
            $applicable_bundle_promo = $promo;
            break;
        }
    }

    if ($bundle_discount_total > 0) {
        $bundle_items = $applicable_bundle_promo['menu_ids'];
        // Hanya terapkan diskon bundle jika menu_id ada di bundle_items
        if (in_array($menu_id, $bundle_items)) {
            // Proporsikan diskon ke setiap item dalam bundle
            $total_original_bundle = 0;
            foreach ($cart as $cart_item) {
                if (in_array($cart_item['menu_id'], $bundle_items)) {
                    $total_original_bundle += $menu_data[$cart_item['menu_id']]['harga'] * $cart_item['jumlah'];
                }
            }

            // Hitung proporsi diskon untuk item ini
            if ($total_original_bundle > 0) {
                $item_original_total = $original_price;
                $discount_proportion = ($item_original_total / $total_original_bundle) * $bundle_discount_total;
                $item_index = array_search($menu_id, array_column($cart, 'menu_id'));
                $item_quantity = $cart[$item_index]['jumlah'];
                return $original_price - ($discount_proportion / $item_quantity);
            }
        }
    }

    return $original_price;
}
?>