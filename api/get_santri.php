<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once 'koneksi.php';

// Ambil semua santri aktif
// nama_model = nama class di YOLOv8 (pakai nama tanpa spasi)
$r = $conn->query("SELECT nis, nama FROM santri WHERE aktif=1");
$data = [];
while ($row = $r->fetch_assoc()) {
    // Otomatis konversi: "Ahmad Fauzi" → "Ahmad_Fauzi"
    $nama_model = str_replace(' ', '_', $row['nama']);
    $data[$nama_model] = $row['nis'];
}
echo json_encode($data);
?>