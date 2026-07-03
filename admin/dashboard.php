<?php $page_title = 'Dashboard'; require '_header.php'; require '../api/koneksi.php'; ?>

<?php
$tgl = date('Y-m-d');
$total   = $conn->query("SELECT COUNT(*) as c FROM santri WHERE aktif=1")->fetch_assoc()['c'];
$hadir   = $conn->query("SELECT COUNT(*) as c FROM absensi WHERE tanggal='$tgl' AND status='hadir'")->fetch_assoc()['c'];
$izin    = $conn->query("SELECT COUNT(*) as c FROM absensi WHERE tanggal='$tgl' AND status='izin'")->fetch_assoc()['c'];
$sakit   = $conn->query("SELECT COUNT(*) as c FROM absensi WHERE tanggal='$tgl' AND status='sakit'")->fetch_assoc()['c'];
$alpa    = $conn->query("SELECT COUNT(*) as c FROM absensi WHERE tanggal='$tgl' AND status='alpa'")->fetch_assoc()['c'];
$belum   = $total - $hadir - $izin - $sakit - $alpa;
$pct     = $total > 0 ? round($hadir / $total * 100) : 0;
?>

<div class="stat-grid">
  <div class="stat-card">
    <div class="stat-label">Total Santri</div>
    <div class="stat-val"><?= $total ?></div>
    <div class="stat-sub">santri aktif</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Hadir Hari Ini</div>
    <div class="stat-val c-green"><?= $hadir ?></div>
    <div class="stat-sub"><?= $pct ?>% kehadiran</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Belum Absen</div>
    <div class="stat-val c-amber"><?= $belum ?></div>
    <div class="stat-sub">dari <?= $total ?> santri</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Izin / Sakit</div>
    <div class="stat-val c-blue"><?= $izin + $sakit ?></div>
    <div class="stat-sub">izin: <?= $izin ?> · sakit: <?= $sakit ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Alpa</div>
    <div class="stat-val c-red"><?= $alpa ?></div>
    <div class="stat-sub">tidak keterangan</div>
  </div>
</div>

<!-- Progress bar -->
<div class="card" style="padding:20px 24px;margin-bottom:20px">
  <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:8px">
    <span style="font-weight:500">Kehadiran Hari Ini — <?= date('d F Y') ?></span>
    <span style="color:#64748b"><?= $hadir ?> / <?= $total ?> santri (<?= $pct ?>%)</span>
  </div>
  <div style="background:#e2e8f0;border-radius:99px;height:10px;overflow:hidden">
    <div style="background:#16a34a;width:<?= $pct ?>%;height:100%;border-radius:99px;transition:.5s"></div>
  </div>
  <div style="display:flex;gap:20px;margin-top:12px;font-size:12px;color:#64748b">
    <span>🟢 Hadir: <?= $hadir ?></span>
    <span>🔵 Izin: <?= $izin ?></span>
    <span>🟡 Sakit: <?= $sakit ?></span>
    <span>🔴 Alpa: <?= $alpa ?></span>
    <span>⬜ Belum: <?= $belum ?></span>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">

  <!-- Grafik 7 hari -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">Kehadiran 7 Hari Terakhir</span>
    </div>
    <div style="padding:16px">
      <canvas id="chartMingguan" height="180"></canvas>
    </div>
  </div>

  <!-- Belum hadir -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">⚠️ Belum Absen Hari Ini</span>
      <span class="badge badge-alpa"><?= $belum ?> santri</span>
    </div>
    <div style="max-height:260px;overflow-y:auto">
    <?php
    $r = $conn->query("SELECT nama, kelas FROM santri WHERE aktif=1 AND id NOT IN
                       (SELECT santri_id FROM absensi WHERE tanggal='$tgl') ORDER BY kelas, nama");
    if ($r->num_rows === 0): ?>
      <div class="empty">🎉 Semua santri sudah absen!</div>
    <?php else: ?>
      <table>
        <thead><tr><th>Nama</th><th>Kelas</th></tr></thead>
        <tbody>
          <?php while ($row = $r->fetch_assoc()): ?>
          <tr><td><?= htmlspecialchars($row['nama']) ?></td><td><?= htmlspecialchars($row['kelas']) ?></td></tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    <?php endif; ?>
    </div>
  </div>

</div>

<!-- Absensi terbaru hari ini -->
<div class="card">
  <div class="card-header">
    <span class="card-title">Absensi Terbaru Hari Ini</span>
    <a href="absensi.php" class="btn btn-outline btn-sm">Lihat Semua</a>
  </div>
  <?php
  $r = $conn->query("SELECT s.nama, s.kelas, a.waktu, a.status, a.metode
                     FROM absensi a JOIN santri s ON a.santri_id=s.id
                     WHERE a.tanggal='$tgl' ORDER BY a.waktu DESC LIMIT 10");
  if ($r->num_rows === 0): ?>
    <div class="empty">Belum ada absensi hari ini</div>
  <?php else: ?>
  <table>
    <thead><tr><th>Nama</th><th>Kelas</th><th>Waktu</th><th>Status</th><th>Metode</th></tr></thead>
    <tbody>
      <?php while ($row = $r->fetch_assoc()): ?>
      <tr>
        <td><?= htmlspecialchars($row['nama']) ?></td>
        <td><?= htmlspecialchars($row['kelas']) ?></td>
        <td><?= substr($row['waktu'],0,5) ?></td>
        <td><span class="badge badge-<?= $row['status'] ?>"><?= ucfirst($row['status']) ?></span></td>
        <td><span class="badge badge-<?= $row['metode'] ?>"><?= $row['metode'] === 'face' ? '📷 Kamera' : '✏️ Manual' ?></span></td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>

<script>
<?php
$labels = []; $data_hadir = [];
for ($i = 6; $i >= 0; $i--) {
    $t = date('Y-m-d', strtotime("-$i days"));
    $h = $conn->query("SELECT COUNT(*) as c FROM absensi WHERE tanggal='$t' AND status='hadir'")->fetch_assoc()['c'];
    $labels[] = date('d/m', strtotime("-$i days"));
    $data_hadir[] = (int)$h;
}
?>
const ctx = document.getElementById('chartMingguan').getContext('2d');
new Chart(ctx, {
  type: 'bar',
  data: {
    labels: <?= json_encode($labels) ?>,
    datasets: [{
      label: 'Hadir',
      data: <?= json_encode($data_hadir) ?>,
      backgroundColor: '#dbeafe',
      borderColor: '#1a56db',
      borderWidth: 1.5,
      borderRadius: 6
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: {
      y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: '#f1f5f9' } },
      x: { grid: { display: false } }
    }
  }
});
</script>

<?php require '_footer.php'; ?>
