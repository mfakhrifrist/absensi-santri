<?php
date_default_timezone_set('Asia/Jakarta');

define('DB_HOST', getenv('MYSQLHOST')     ?: 'localhost');
define('DB_USER', getenv('MYSQLUSER')     ?: 'root');
define('DB_PASS', getenv('MYSQLPASSWORD') ?: '');
define('DB_NAME', getenv('MYSQLDATABASE') ?: 'absensi_santri');
define('DB_PORT', (int)(getenv('MYSQLPORT') ?: 3306));

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
$conn->set_charset('utf8mb4');

if ($conn->connect_error) {
    http_response_code(500);
    die(json_encode(['error' => 'Koneksi gagal: ' . $conn->connect_error]));
}
?>