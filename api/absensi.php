<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method tidak diizinkan']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['nis'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Data tidak lengkap, nis diperlukan']);
    exit;
}

$nis       = $conn->real_escape_string(trim($input['nis']));
$tanggal   = $conn->real_escape_string($input['tanggal'] ?? date('Y-m-d'));
$waktu     = $conn->real_escape_string($input['waktu']   ?? date('H:i:s'));
$status    = $conn->real_escape_string($input['status']  ?? 'hadir');
$confidence = isset($input['confidence']) ? floatval($input['confidence']) : null;

// Cari santri
$q = $conn->query("SELECT id, nama FROM santri WHERE nis='$nis' AND aktif=1");
if ($q->num_rows === 0) {
    echo json_encode(['error' => 'Santri tidak ditemukan', 'nis' => $nis]);
    exit;
}
$santri = $q->fetch_assoc();

// Simpan absensi (upsert)
$sql = "INSERT INTO absensi (santri_id, tanggal, waktu, status, metode)
        VALUES ({$santri['id']}, '$tanggal', '$waktu', '$status', 'face')
        ON DUPLICATE KEY UPDATE waktu=VALUES(waktu), status=VALUES(status)";

if ($conn->query($sql)) {
    // Simpan log kamera
    if ($confidence !== null) {
        $conn->query("INSERT INTO log_kamera (santri_id, waktu, confidence, hasil)
                      VALUES ({$santri['id']}, NOW(), $confidence, 'dikenal')");
    }
    echo json_encode([
        'success' => true,
        'pesan'   => 'Absensi berhasil',
        'nama'    => $santri['nama'],
        'waktu'   => $waktu
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => $conn->error]);
}
?>
