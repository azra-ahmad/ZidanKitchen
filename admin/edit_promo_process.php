<?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nama_promo = $_POST['nama_promo'];
        $deskripsi = $_POST['deskripsi'];
        $diskon = $_POST['diskon'];

        if ($_FILES['gambar']['name']) {
            $gambar = $_FILES['gambar']['name'];
            move_uploaded_file($_FILES['gambar']['tmp_name'], "../assets/images/$gambar");
        } else {
            $gambar = $promo['gambar'];
        }

        $updateQuery = $conn->prepare("UPDATE promos SET nama_promo = ?, deskripsi = ?, diskon = ?, gambar = ? WHERE id_promo = ?");
        $updateQuery->bind_param("ssdsi", $nama_promo, $deskripsi, $diskon, $gambar, $id);

        if ($updateQuery->execute()) {
            echo "<script>alert('Promo berhasil diperbarui!'); window.location.href='kelola_promo.php';</script>";
        } else {
            echo "<script>alert('Gagal memperbarui promo!');</script>";
        }
    }
?>