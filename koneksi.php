<?php
date_default_timezone_set('Asia/Jakarta'); // ← tambahkan baris ini

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'absensi_santri');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$conn->set_charset('utf8mb4');

if ($conn->connect_error) {
    http_response_code(500);
    die(json_encode(['error' => 'Koneksi gagal: ' . $conn->connect_error]));
}
?>