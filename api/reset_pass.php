<?php
require_once 'api/koneksi.php';
$hash = password_hash('admin123', PASSWORD_BCRYPT);
$conn->query("UPDATE users SET password='$hash' WHERE username='admin'");
echo "Berhasil! Silakan login dengan admin / admin123";
?>