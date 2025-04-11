<?php
date_default_timezone_set('Asia/Jakarta');

// Fungsi untuk ambil promo aktif
function getActivePromos($conn) {
    $today = date('Y-m-d');
    $query = "SELECT * FROM promos 
              WHERE start_date <= ? AND end_date >= ? 
              AND (promo_type = 'discount' OR promo_type = 'bundle')";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $today, $today);
    $stmt->execute();
    $result = $stmt->get_result();
    $promos = [];
    while ($row = $result->fetch_assoc()) {
        $row['menu_target'] = json_decode($row['menu_target'] ?? '[]', true);
        $row['bundle_items'] = json_decode($row['bundle_items'] ?? '[]', true);
        $promos[] = $row;
    }
    $stmt->close();
    return $promos;
}

// Fungsi untuk hitung harga setelah diskon persentase
function applyDiscount($price, $discount) {
    return $price - ($price * $discount / 100);
}

// Fungsi untuk cek apakah menu dapet diskon (promo tipe Discount)
function getMenuDiscount($menu_id, $promos) {
    foreach ($promos as $promo) {
        if ($promo['promo_type'] === 'discount' && !empty($promo['menu_target'])) {
            if (in_array($menu_id, $promo['menu_target'])) {
                return $promo['discount'];
            }
        }
    }
    return 0;
}

// Fungsi untuk cek apakah keranjang memenuhi syarat promo bundle
function checkBundlePromo($cart, $promo) {
    if ($promo['promo_type'] !== 'bundle' || empty($promo['bundle_items'])) {
        return false;
    }

    // Cek apakah semua item dalam bundle ada di keranjang
    foreach ($promo['bundle_items'] as $bundle_item_id) {
        $found = false;
        foreach ($cart as $cart_item) {
            if ($cart_item['id_menu'] == $bundle_item_id && $cart_item['jumlah'] > 0) {
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
    $bundle_items = $promo['bundle_items'];
    foreach ($cart as $cart_item) {
        if (in_array($cart_item['id_menu'], $bundle_items)) {
            $total_original += $menu_data[$cart_item['id_menu']]['harga'] * $cart_item['jumlah'];
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
        // Proporsikan diskon ke setiap item dalam bundle
        $bundle_items = $applicable_bundle_promo['bundle_items'];
        $total_original_bundle = 0;
        foreach ($cart as $cart_item) {
            if (in_array($cart_item['id_menu'], $bundle_items)) {
                $total_original_bundle += $menu_data[$cart_item['id_menu']]['harga'] * $cart_item['jumlah'];
            }
        }

        // Hitung proporsi diskon untuk item ini
        if ($total_original_bundle > 0) {
            $item_original_total = $original_price;
            $discount_proportion = ($item_original_total / $total_original_bundle) * $bundle_discount_total;
            return $original_price - ($discount_proportion / $cart[array_search($menu_id, array_column($cart, 'id_menu'))]['jumlah']);
        }
    }

    return $original_price;
}
?>