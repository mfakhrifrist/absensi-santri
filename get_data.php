<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once 'koneksi.php';

$action = $_GET['action'] ?? 'hari_ini';

switch ($action) {

    case 'hari_ini':
        $tgl = date('Y-m-d');
        $total_santri = $conn->query("SELECT COUNT(*) as c FROM santri WHERE aktif=1")->fetch_assoc()['c'];
        $hadir = $conn->query("SELECT COUNT(*) as c FROM absensi WHERE tanggal='$tgl' AND status='hadir'")->fetch_assoc()['c'];
        $izin  = $conn->query("SELECT COUNT(*) as c FROM absensi WHERE tanggal='$tgl' AND status='izin'")->fetch_assoc()['c'];
        $sakit = $conn->query("SELECT COUNT(*) as c FROM absensi WHERE tanggal='$tgl' AND status='sakit'")->fetch_assoc()['c'];
        $alpa  = $conn->query("SELECT COUNT(*) as c FROM absensi WHERE tanggal='$tgl' AND status='alpa'")->fetch_assoc()['c'];
        $belum = $total_santri - $hadir - $izin - $sakit - $alpa;

        // Daftar hadir hari ini
        $list = [];
        $r = $conn->query("SELECT s.nama, s.kelas, a.waktu, a.status, a.metode
                           FROM absensi a JOIN santri s ON a.santri_id=s.id
                           WHERE a.tanggal='$tgl' ORDER BY a.waktu DESC");
        while ($row = $r->fetch_assoc()) $list[] = $row;

        echo json_encode(compact('total_santri','hadir','izin','sakit','alpa','belum','list'));
        break;

    case 'mingguan':
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $tgl = date('Y-m-d', strtotime("-$i days"));
            $hari = date('D', strtotime("-$i days"));
            $h = $conn->query("SELECT COUNT(*) as c FROM absensi WHERE tanggal='$tgl' AND status='hadir'")->fetch_assoc()['c'];
            $data[] = ['tanggal' => $tgl, 'hari' => $hari, 'hadir' => (int)$h];
        }
        echo json_encode($data);
        break;

    case 'belum_hadir':
        $tgl = date('Y-m-d');
        $list = [];
        $r = $conn->query("SELECT nama, kelas, wali_telp FROM santri
                           WHERE aktif=1 AND id NOT IN (
                             SELECT santri_id FROM absensi WHERE tanggal='$tgl'
                           ) ORDER BY kelas, nama");
        while ($row = $r->fetch_assoc()) $list[] = $row;
        echo json_encode($list);
        break;

    case 'semua_santri':
        $list = [];
        $r = $conn->query("SELECT s.*, 
                           (SELECT COUNT(*) FROM absensi a WHERE a.santri_id=s.id AND a.status='hadir') as total_hadir,
                           (SELECT COUNT(*) FROM absensi a WHERE a.santri_id=s.id) as total_absensi
                           FROM santri s WHERE s.aktif=1 ORDER BY s.kelas, s.nama");
        while ($row = $r->fetch_assoc()) $list[] = $row;
        echo json_encode($list);
        break;

    case 'laporan':
        $bulan = $_GET['bulan'] ?? date('Y-m');
        $kelas = $_GET['kelas'] ?? '';
        $where_kelas = $kelas ? "AND s.kelas='".($conn->real_escape_string($kelas))."'" : '';
        $list = [];
        $r = $conn->query("SELECT s.nis, s.nama, s.kelas,
                           COUNT(CASE WHEN a.status='hadir' THEN 1 END) as hadir,
                           COUNT(CASE WHEN a.status='izin'  THEN 1 END) as izin,
                           COUNT(CASE WHEN a.status='sakit' THEN 1 END) as sakit,
                           COUNT(CASE WHEN a.status='alpa'  THEN 1 END) as alpa
                           FROM santri s
                           LEFT JOIN absensi a ON s.id=a.santri_id AND DATE_FORMAT(a.tanggal,'%Y-%m')='$bulan'
                           WHERE s.aktif=1 $where_kelas
                           GROUP BY s.id ORDER BY s.kelas, s.nama");
        while ($row = $r->fetch_assoc()) $list[] = $row;
        echo json_encode($list);
        break;

    default:
        echo json_encode(['error' => 'Action tidak dikenal']);
}
?>
