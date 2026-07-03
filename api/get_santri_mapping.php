<?php
/**
 * get_santri_mapping.php
 * Dipakai Raspberry Pi untuk ambil daftar santri dari database
 * URL: http://[IP_PC]/absensi_santri/api/get_santri_mapping.php
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'koneksi.php';

$r = $conn->query("
    SELECT nis, nama 
    FROM santri 
    WHERE aktif=1 
    ORDER BY nama
");

$mapping = [];
while ($row = $r->fetch_assoc()) {
    // Simpan 3 versi nama supaya cocok dengan berbagai format class di model:
    // 1. Nama asli          : "Ahmad Fauzi"
    // 2. Ganti spasi → _    : "Ahmad_Fauzi"
    // 3. Huruf kecil semua  : "ahmad fauzi"
    $nama       = $row['nama'];
    $nama_under = str_replace(' ', '_', $nama);
    $nama_lower = strtolower($nama);
    $nama_lower_under = strtolower($nama_under);

    $mapping[$nama]             = $row['nis'];
    $mapping[$nama_under]       = $row['nis'];
    $mapping[$nama_lower]       = $row['nis'];
    $mapping[$nama_lower_under] = $row['nis'];
}

echo json_encode([
    'success' => true,
    'total'   => $r->num_rows,
    'data'    => $mapping
]);
?>
