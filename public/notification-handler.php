<?php
$notif = new \Midtrans\Notification();
$order_id = str_replace('ORDER-', '', explode('-', $notif->order_id)[0]);

if ($notif->transaction_status == 'settlement') {
    $conn->query("UPDATE orders SET status = 'paid' WHERE id = $order_id");
}
?>