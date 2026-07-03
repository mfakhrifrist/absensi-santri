<?php
$page_title = 'Laporan Absensi';
require '_header.php';
require '../api/koneksi.php';

$tab = $_GET['tab'] ?? 'bulanan';

// ── REKAP BULANAN ──────────────────────────────────────────
$bulan       = $_GET['bulan'] ?? date('Y-m');
$kelas       = $_GET['kelas'] ?? '';
$where_kelas = $kelas ? "AND s.kelas='".$conn->real_escape_string($kelas)."'" : '';
$hari_kerja  = 0;
$days_in_month = date('t', strtotime($bulan.'-01'));
for ($d = 1; $d <= $days_in_month; $d++) {
    if (date('N', strtotime("$bulan-".str_pad($d,2,'0',STR_PAD_LEFT))) < 6) $hari_kerja++;
}
$r = $conn->query("
    SELECT s.id, s.nis, s.nama, s.kelas,
           COUNT(CASE WHEN a.status='hadir' THEN 1 END) as hadir,
           COUNT(CASE WHEN a.status='izin'  THEN 1 END) as izin,
           COUNT(CASE WHEN a.status='sakit' THEN 1 END) as sakit,
           COUNT(CASE WHEN a.status='alpa'  THEN 1 END) as alpa
    FROM santri s
    LEFT JOIN absensi a ON s.id=a.santri_id AND DATE_FORMAT(a.tanggal,'%Y-%m')='$bulan'
    WHERE s.aktif=1 $where_kelas GROUP BY s.id ORDER BY s.kelas, s.nama
");
$data_santri = [];
while ($row = $r->fetch_assoc()) $data_santri[] = $row;
$total_santri = count($data_santri);
$total_hadir  = array_sum(array_column($data_santri,'hadir'));
$total_alpa   = array_sum(array_column($data_santri,'alpa'));
$avg_pct      = $total_santri>0&&$hari_kerja>0 ? round($total_hadir/($total_santri*$hari_kerja)*100) : 0;

// ── PER SANTRI ─────────────────────────────────────────────
$santri_id    = intval($_GET['id'] ?? 0);
$view_bulan   = $_GET['view_bulan'] ?? date('Y-m'); // bulan yang sedang dilihat
$all_santri   = [];
$rs = $conn->query("SELECT id, nis, nama, kelas FROM santri WHERE aktif=1 ORDER BY kelas, nama");
while ($s = $rs->fetch_assoc()) $all_santri[] = $s;

$santri = null;
if ($santri_id > 0) {
    $q = $conn->query("SELECT * FROM santri WHERE id=$santri_id");
    $santri = $q->fetch_assoc();
    $tab = 'per_santri';
}

// Data bulan yang dipilih
$bulan_detail = [];
$stat_bulan   = [];
if ($santri) {
    // Statistik semua bulan (untuk navigasi)
    $tahun_view = substr($view_bulan, 0, 4);
    for ($m = 1; $m <= 12; $m++) {
        $bs = sprintf('%04d-%02d', $tahun_view, $m);
        $rb = $conn->query("SELECT
            COUNT(CASE WHEN status='hadir' THEN 1 END) as hadir,
            COUNT(CASE WHEN status='izin'  THEN 1 END) as izin,
            COUNT(CASE WHEN status='sakit' THEN 1 END) as sakit,
            COUNT(CASE WHEN status='alpa'  THEN 1 END) as alpa
            FROM absensi WHERE santri_id=$santri_id AND DATE_FORMAT(tanggal,'%Y-%m')='$bs'");
        $rb2 = $rb->fetch_assoc();
        $hk = 0;
        $days = cal_days_in_month(CAL_GREGORIAN, $m, $tahun_view);
        for ($d=1;$d<=$days;$d++) { if (date('N',mktime(0,0,0,$m,$d,$tahun_view))<6) $hk++; }
        $pct = $hk>0 ? round($rb2['hadir']/$hk*100) : 0;
        $stat_bulan[$m] = ['hadir'=>(int)$rb2['hadir'],'izin'=>(int)$rb2['izin'],'sakit'=>(int)$rb2['sakit'],'alpa'=>(int)$rb2['alpa'],'hari_kerja'=>$hk,'pct'=>$pct];
    }

    // Detail hari untuk bulan yang sedang dilihat
    $rd = $conn->query("SELECT tanggal,waktu,status,metode,keterangan FROM absensi
                        WHERE santri_id=$santri_id AND DATE_FORMAT(tanggal,'%Y-%m')='$view_bulan'
                        ORDER BY tanggal ASC");
    while ($row = $rd->fetch_assoc()) $bulan_detail[] = $row;
}

$bulan_nama = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$hari_nama  = ['','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'];

// Navigasi bulan (prev/next)
$prev_bulan = date('Y-m', strtotime($view_bulan.'-01 -1 month'));
$next_bulan = date('Y-m', strtotime($view_bulan.'-01 +1 month'));
$bulan_int  = (int)date('m', strtotime($view_bulan.'-01'));
$tahun_int  = (int)date('Y', strtotime($view_bulan.'-01'));
$tahun_options = range(date('Y'), date('Y')-3);
?>

<!-- TAB NAVIGATION -->
<div style="display:flex;gap:4px;margin-bottom:24px;background:#f1f5f9;border-radius:10px;padding:4px;width:fit-content">
  <a href="?tab=bulanan" style="padding:8px 20px;border-radius:7px;font-size:13px;font-weight:500;text-decoration:none;transition:.15s;background:<?= $tab==='bulanan'?'#fff':'transparent' ?>;color:<?= $tab==='bulanan'?'#1e293b':'#64748b' ?>;box-shadow:<?= $tab==='bulanan'?'0 1px 3px rgba(0,0,0,.1)':'' ?>">
    📋 Rekap Bulanan
  </a>
  <a href="?tab=per_santri<?= $santri_id?'&id='.$santri_id.'&view_bulan='.$view_bulan:'' ?>" style="padding:8px 20px;border-radius:7px;font-size:13px;font-weight:500;text-decoration:none;transition:.15s;background:<?= $tab==='per_santri'?'#fff':'transparent' ?>;color:<?= $tab==='per_santri'?'#1e293b':'#64748b' ?>;box-shadow:<?= $tab==='per_santri'?'0 1px 3px rgba(0,0,0,.1)':'' ?>">
    👤 Per Santri
  </a>
</div>

<?php if ($tab === 'bulanan'): ?>
<!-- ═══ TAB REKAP BULANAN ═══ -->
<div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin-bottom:20px">
  <form method="GET" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
    <input type="hidden" name="tab" value="bulanan">
    <div style="display:flex;align-items:center;gap:8px">
      <label style="font-size:13px;font-weight:500">Bulan:</label>
      <input type="month" name="bulan" value="<?= $bulan ?>" style="width:160px">
    </div>
    <div style="display:flex;align-items:center;gap:8px">
      <label style="font-size:13px;font-weight:500">Kelas:</label>
      <select name="kelas" style="width:140px">
        <option value="">Semua</option>
        <?php $rk=$conn->query("SELECT DISTINCT kelas FROM santri WHERE aktif=1 ORDER BY kelas"); while($k=$rk->fetch_assoc()): ?>
        <option value="<?= $k['kelas'] ?>" <?= $kelas===$k['kelas']?'selected':'' ?>><?= htmlspecialchars($k['kelas']) ?></option>
        <?php endwhile; ?>
      </select>
    </div>
    <button type="submit" class="btn btn-primary btn-sm">Tampilkan</button>
  </form>
  <button class="btn btn-outline btn-sm" onclick="cetakBulanan()">🖨️ Cetak</button>
</div>
<div class="stat-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:20px">
  <div class="stat-card"><div class="stat-label">Total Santri</div><div class="stat-val"><?= $total_santri ?></div><div class="stat-sub"><?= $kelas?:'semua kelas' ?></div></div>
  <div class="stat-card"><div class="stat-label">Hari Efektif</div><div class="stat-val"><?= $hari_kerja ?></div><div class="stat-sub">hari kerja</div></div>
  <div class="stat-card"><div class="stat-label">Rata-rata Hadir</div><div class="stat-val c-green"><?= $avg_pct ?>%</div><div class="stat-sub">total: <?= $total_hadir ?></div></div>
  <div class="stat-card"><div class="stat-label">Total Alpa</div><div class="stat-val c-red"><?= $total_alpa ?></div><div class="stat-sub">perlu perhatian</div></div>
</div>
<div class="card" id="area-cetak-bulanan">
  <div class="card-header">
    <span class="card-title">Rekap — <?= date('F Y', strtotime($bulan.'-01')) ?> <?= $kelas?"— $kelas":'' ?></span>
  </div>
  <?php if(empty($data_santri)): ?><div class="empty">Tidak ada data</div>
  <?php else: ?>
  <div style="overflow-x:auto"><table>
    <thead><tr><th>No</th><th>Nama</th><th>Kelas</th><th style="color:#16a34a">Hadir</th><th style="color:#1a56db">Izin</th><th style="color:#d97706">Sakit</th><th style="color:#dc2626">Alpa</th><th>%</th><th>Status</th><th>Detail</th></tr></thead>
    <tbody>
    <?php foreach($data_santri as $i=>$row):
      $pct_s=$hari_kerja>0?round($row['hadir']/$hari_kerja*100):0;
      $sk=$pct_s>=90?'baik':($pct_s>=75?'cukup':'kurang');
      $sc=['baik'=>'badge-hadir','cukup'=>'badge-izin','kurang'=>'badge-alpa'];
    ?>
    <tr>
      <td style="color:#94a3b8"><?= $i+1 ?></td>
      <td style="font-weight:500"><?= htmlspecialchars($row['nama']) ?></td>
      <td><?= htmlspecialchars($row['kelas']) ?></td>
      <td style="text-align:center;color:#16a34a;font-weight:500"><?= $row['hadir'] ?></td>
      <td style="text-align:center;color:#1a56db"><?= $row['izin'] ?></td>
      <td style="text-align:center;color:#d97706"><?= $row['sakit'] ?></td>
      <td style="text-align:center;color:#dc2626;font-weight:<?= $row['alpa']>3?600:400 ?>"><?= $row['alpa'] ?></td>
      <td style="text-align:center">
        <div style="background:#e2e8f0;border-radius:99px;height:6px;width:60px;display:inline-block;vertical-align:middle;margin-right:4px">
          <div style="background:<?= $pct_s>=90?'#16a34a':($pct_s>=75?'#1a56db':'#dc2626') ?>;width:<?= $pct_s ?>%;height:100%;border-radius:99px"></div>
        </div><?= $pct_s ?>%
      </td>
      <td><span class="badge <?= $sc[$sk] ?>"><?= ucfirst($sk) ?></span></td>
      <td><a href="?tab=per_santri&id=<?= $row['id'] ?>&view_bulan=<?= $bulan ?>" class="btn btn-outline btn-sm">👤 Detail</a></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php endif; ?>
</div>

<?php else: ?>
<!-- ═══ TAB PER SANTRI ═══ -->

<!-- SEARCH SANTRI -->
<div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:20px;margin-bottom:20px">
  <div style="display:flex;gap:16px;align-items:flex-end;flex-wrap:wrap">
    <div style="flex:1;min-width:220px">
      <label style="font-size:12px;font-weight:600;color:#64748b;display:block;margin-bottom:8px">CARI NAMA SANTRI</label>
      <div style="position:relative">
        <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);font-size:15px">🔍</span>
        <input type="text" id="search-santri" placeholder="Ketik nama santri..." autocomplete="off"
               style="width:100%;padding:10px 12px 10px 38px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;font-family:inherit;outline:none"
               oninput="filterSantri()" onfocus="showDropdown()" value="<?= $santri?htmlspecialchars($santri['nama'],ENT_QUOTES):'' ?>">
        <div id="dropdown-santri" style="display:none;position:absolute;top:100%;left:0;right:0;background:#fff;border:1.5px solid #e2e8f0;border-top:none;border-radius:0 0 8px 8px;max-height:220px;overflow-y:auto;z-index:50;box-shadow:0 4px 12px rgba(0,0,0,.08)">
          <?php foreach($all_santri as $s): ?>
          <div class="santri-opt" data-id="<?= $s['id'] ?>" data-nama="<?= htmlspecialchars($s['nama'],ENT_QUOTES) ?>"
               onclick="pilihSantri(<?= $s['id'] ?>, '<?= htmlspecialchars($s['nama'],ENT_QUOTES) ?>')"
               style="padding:10px 14px;cursor:pointer;font-size:13px;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center"
               onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='#fff'">
            <span style="font-weight:500"><?= htmlspecialchars($s['nama']) ?></span>
            <span style="font-size:11px;color:#94a3b8"><?= htmlspecialchars($s['kelas']) ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <?php if($santri): ?>
    <button onclick="window.print()" class="btn btn-outline">🖨️ Cetak</button>
    <?php endif; ?>
  </div>
</div>

<?php if(!$santri): ?>
<div style="text-align:center;padding:80px 20px;color:#94a3b8">
  <div style="font-size:48px;margin-bottom:16px">🔍</div>
  <div style="font-size:16px;font-weight:500;color:#64748b;margin-bottom:8px">Cari nama santri di atas</div>
  <div style="font-size:13px">Ketik nama untuk menampilkan laporan kehadiran bulanan</div>
</div>

<?php else:
  $stat_aktif = $stat_bulan[$bulan_int];
  $total_hadir_th = array_sum(array_column($stat_bulan,'hadir'));
  $total_hk_th    = array_sum(array_column($stat_bulan,'hari_kerja'));
  $pct_tahun      = $total_hk_th>0 ? round($total_hadir_th/$total_hk_th*100) : 0;
?>

<!-- Info Santri -->
<div style="background:linear-gradient(135deg,#1a56db,#1e40af);border-radius:14px;padding:20px 24px;margin-bottom:20px;color:#fff;display:flex;align-items:center;gap:16px">
  <div style="width:48px;height:48px;background:rgba(255,255,255,.2);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0">
    <?= $santri['jenis_kelamin']==='L'?'👦':'👧' ?>
  </div>
  <div style="flex:1">
    <div style="font-size:17px;font-weight:700"><?= htmlspecialchars($santri['nama']) ?></div>
    <div style="font-size:12px;opacity:.8;margin-top:2px"><?= htmlspecialchars($santri['kelas']) ?> · NIS: <?= $santri['nis'] ?><?= $santri['wali_nama']?' · Wali: '.htmlspecialchars($santri['wali_nama']):'' ?></div>
  </div>
  <div style="text-align:right;flex-shrink:0">
    <div style="font-size:26px;font-weight:700"><?= $pct_tahun ?>%</div>
    <div style="font-size:11px;opacity:.75">kehadiran <?= $tahun_int ?></div>
  </div>
</div>

<!-- NAVIGASI BULAN -->
<div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:0;margin-bottom:20px;overflow:hidden">
  <!-- Header navigasi -->
  <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 20px;border-bottom:1px solid #e2e8f0">
    <a href="?tab=per_santri&id=<?= $santri_id ?>&view_bulan=<?= $prev_bulan ?>"
       style="width:34px;height:34px;border-radius:8px;border:1px solid #e2e8f0;display:flex;align-items:center;justify-content:center;text-decoration:none;color:#374151;font-size:16px;transition:.15s"
       onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='#fff'">‹</a>

    <div style="text-align:center">
      <div style="font-size:17px;font-weight:700;color:#1e293b"><?= $bulan_nama[$bulan_int] ?> <?= $tahun_int ?></div>
      <div style="font-size:12px;color:#64748b;margin-top:2px"><?= $hari_kerja ?> hari efektif bulan ini</div>
    </div>

    <a href="?tab=per_santri&id=<?= $santri_id ?>&view_bulan=<?= $next_bulan ?>"
       style="width:34px;height:34px;border-radius:8px;border:1px solid #e2e8f0;display:flex;align-items:center;justify-content:center;text-decoration:none;color:#374151;font-size:16px;transition:.15s"
       onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='#fff'">›</a>
  </div>

  <!-- Pilih bulan cepat (12 bulan) -->
  <div style="display:grid;grid-template-columns:repeat(6,1fr);gap:0;border-bottom:1px solid #f1f5f9">
    <?php for($m=1;$m<=12;$m++):
      $sb  = $stat_bulan[$m];
      $bm  = sprintf('%04d-%02d',$tahun_int,$m);
      $aktif = $m===$bulan_int;
      $w   = $sb['pct']>=90?'#16a34a':($sb['pct']>=75?'#1a56db':($sb['pct']>=50?'#d97706':'#dc2626'));
      $bg  = $aktif ? '#1a56db' : '#fff';
      $cl  = $aktif ? '#fff' : '#374151';
      $br  = $m%6!==0?'1px solid #f1f5f9':'none';
      $bb  = $m<=6?'1px solid #f1f5f9':'none';
    ?>
    <a href="?tab=per_santri&id=<?= $santri_id ?>&view_bulan=<?= $bm ?>"
       style="display:block;padding:10px 6px;text-align:center;text-decoration:none;background:<?= $bg ?>;border-right:<?= $br ?>;border-bottom:<?= $bb ?>;transition:.15s"
       onmouseover="this.style.background='<?= $aktif?'#1a56db':'#f8fafc' ?>'" onmouseout="this.style.background='<?= $bg ?>'">
      <div style="font-size:11px;font-weight:<?= $aktif?700:500 ?>;color:<?= $cl ?>"><?= substr($bulan_nama[$m],0,3) ?></div>
      <?php if($sb['hadir']>0||$sb['izin']>0||$sb['sakit']>0||$sb['alpa']>0): ?>
      <div style="font-size:10px;font-weight:600;color:<?= $aktif?'rgba(255,255,255,.8)':$w ?>;margin-top:2px"><?= $sb['pct'] ?>%</div>
      <?php else: ?>
      <div style="font-size:10px;color:<?= $aktif?'rgba(255,255,255,.5)':'#cbd5e1' ?>;margin-top:2px">—</div>
      <?php endif; ?>
    </a>
    <?php endfor; ?>
  </div>

  <!-- Stat bulan aktif -->
  <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:0">
    <?php
    $items = [
      ['label'=>'Hadir','val'=>$stat_aktif['hadir'],'color'=>'#16a34a','bg'=>'#f0fdf4'],
      ['label'=>'Izin', 'val'=>$stat_aktif['izin'], 'color'=>'#1a56db','bg'=>'#eff6ff'],
      ['label'=>'Sakit','val'=>$stat_aktif['sakit'],'color'=>'#d97706','bg'=>'#fffbeb'],
      ['label'=>'Alpa', 'val'=>$stat_aktif['alpa'], 'color'=>'#dc2626','bg'=>'#fef2f2'],
      ['label'=>'%',    'val'=>$stat_aktif['pct'].'%','color'=>$stat_aktif['pct']>=75?'#16a34a':'#dc2626','bg'=>'#f8fafc'],
    ];
    foreach($items as $idx=>$item): ?>
    <div style="padding:14px;text-align:center;border-right:<?= $idx<4?'1px solid #f1f5f9':'none' ?>">
      <div style="font-size:20px;font-weight:700;color:<?= $item['color'] ?>"><?= $item['val'] ?></div>
      <div style="font-size:11px;color:#64748b;margin-top:2px"><?= $item['label'] ?></div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- DETAIL HARIAN BULAN INI -->
<div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;margin-bottom:20px">
  <div style="padding:14px 20px;border-bottom:1px solid #e2e8f0">
    <span style="font-size:14px;font-weight:600">Detail Kehadiran — <?= $bulan_nama[$bulan_int] ?> <?= $tahun_int ?></span>
  </div>
  <?php if(empty($bulan_detail)): ?>
  <div style="text-align:center;padding:40px;color:#94a3b8;font-size:13px">
    Belum ada data absensi di bulan <?= $bulan_nama[$bulan_int] ?>
  </div>
  <?php else: ?>
  <table style="width:100%;border-collapse:collapse;font-size:13px">
    <thead>
      <tr style="background:#f8fafc">
        <th style="padding:10px 16px;text-align:left;font-size:12px;font-weight:600;color:#64748b;border-bottom:1px solid #e2e8f0">Tanggal</th>
        <th style="padding:10px 16px;text-align:left;font-size:12px;font-weight:600;color:#64748b;border-bottom:1px solid #e2e8f0">Hari</th>
        <th style="padding:10px 16px;text-align:left;font-size:12px;font-weight:600;color:#64748b;border-bottom:1px solid #e2e8f0">Waktu</th>
        <th style="padding:10px 16px;text-align:left;font-size:12px;font-weight:600;color:#64748b;border-bottom:1px solid #e2e8f0">Status</th>
        <th style="padding:10px 16px;text-align:left;font-size:12px;font-weight:600;color:#64748b;border-bottom:1px solid #e2e8f0">Metode</th>
        <th style="padding:10px 16px;text-align:left;font-size:12px;font-weight:600;color:#64748b;border-bottom:1px solid #e2e8f0">Keterangan</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach($bulan_detail as $d):
      $dow = date('N',strtotime($d['tanggal']));
      $sc  = ['hadir'=>['#f0fdf4','#16a34a'],'izin'=>['#eff6ff','#1a56db'],'sakit'=>['#fffbeb','#d97706'],'alpa'=>['#fef2f2','#dc2626']];
      $c   = $sc[$d['status']] ?? ['#f8fafc','#64748b'];
    ?>
    <tr style="border-bottom:1px solid #f1f5f9">
      <td style="padding:11px 16px;font-weight:500"><?= date('d F Y',strtotime($d['tanggal'])) ?></td>
      <td style="padding:11px 16px;color:#64748b"><?= $hari_nama[$dow] ?></td>
      <td style="padding:11px 16px;font-family:monospace"><?= $d['waktu']?substr($d['waktu'],0,5):'—' ?></td>
      <td style="padding:11px 16px"><span style="background:<?= $c[0] ?>;color:<?= $c[1] ?>;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600"><?= ucfirst($d['status']) ?></span></td>
      <td style="padding:11px 16px;font-size:12px;color:#64748b"><?= $d['metode']==='face'?'📷 Kamera':'✏️ Manual' ?></td>
      <td style="padding:11px 16px;font-size:12px;color:#64748b"><?= htmlspecialchars($d['keterangan']??'—') ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>

<!-- Grafik tahunan -->
<div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:20px">
  <div style="font-size:14px;font-weight:600;margin-bottom:16px">Grafik Kehadiran Sepanjang <?= $tahun_int ?></div>
  <canvas id="chartTahunan" height="90"></canvas>
</div>
<script>
new Chart(document.getElementById('chartTahunan').getContext('2d'), {
  type:'bar',
  data:{
    labels:<?= json_encode(array_map(fn($m)=>substr($bulan_nama[$m],0,3),range(1,12))) ?>,
    datasets:[
      {label:'Hadir',data:<?= json_encode(array_column($stat_bulan,'hadir')) ?>,backgroundColor:'#bbf7d0',borderColor:'#16a34a',borderWidth:1.5,borderRadius:4},
      {label:'Izin', data:<?= json_encode(array_column($stat_bulan,'izin'))  ?>,backgroundColor:'#bfdbfe',borderColor:'#1a56db',borderWidth:1.5,borderRadius:4},
      {label:'Sakit',data:<?= json_encode(array_column($stat_bulan,'sakit')) ?>,backgroundColor:'#fde68a',borderColor:'#d97706',borderWidth:1.5,borderRadius:4},
      {label:'Alpa', data:<?= json_encode(array_column($stat_bulan,'alpa'))  ?>,backgroundColor:'#fecaca',borderColor:'#dc2626',borderWidth:1.5,borderRadius:4},
    ]
  },
  options:{responsive:true,plugins:{legend:{position:'top',labels:{font:{size:12},boxWidth:12}}},scales:{y:{beginAtZero:true,ticks:{stepSize:1},grid:{color:'#f1f5f9'}},x:{grid:{display:false}}}}
});
</script>

<?php endif; // end santri check ?>
<?php endif; // end tab per_santri ?>

<script>
function filterSantri() {
  const q = document.getElementById('search-santri').value.toLowerCase();
  document.querySelectorAll('.santri-opt').forEach(el => {
    el.style.display = el.dataset.nama.toLowerCase().includes(q) ? '' : 'none';
  });
  document.getElementById('dropdown-santri').style.display = 'block';
}
function showDropdown() {
  document.getElementById('dropdown-santri').style.display = 'block';
}
function pilihSantri(id, nama) {
  document.getElementById('search-santri').value = nama;
  document.getElementById('dropdown-santri').style.display = 'none';
  window.location.href = '?tab=per_santri&id=' + id + '&view_bulan=<?= $view_bulan ?>';
}
document.addEventListener('click', function(e) {
  const wrap = document.querySelector('#search-santri')?.closest('div');
  if (wrap && !wrap.contains(e.target)) {
    document.getElementById('dropdown-santri').style.display = 'none';
  }
});
function cetakBulanan() {
  const area = document.getElementById('area-cetak-bulanan');
  if (!area) return;
  const win = window.open('','_blank');
  win.document.write('<html><head><title>Laporan</title><style>body{font-family:sans-serif;font-size:12px}table{width:100%;border-collapse:collapse}th,td{border:1px solid #ccc;padding:6px 10px;text-align:left}th{background:#f5f5f5}</style></head><body><h2>Rekap Absensi — <?= date('F Y',strtotime($bulan.'-01')) ?></h2>'+area.innerHTML+'</body></html>');
  win.document.close(); win.print();
}
</script>
<style>
@media print{.sidebar,.topbar,.btn,select,input,form,a[href]{display:none!important}.main{margin-left:0!important}.content{padding:0!important}}
</style>

<?php require '_footer.php'; ?>
