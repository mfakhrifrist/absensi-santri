<?php $page_title = 'Absensi Hari Ini'; require '_header.php'; require '../api/koneksi.php'; ?>

<?php
$tgl = $_GET['tgl'] ?? date('Y-m-d');
$msg = '';

// Proses input manual
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['santri_id'])) {
    $sid    = intval($_POST['santri_id']);
    $status = $conn->real_escape_string($_POST['status']);
    $ket    = $conn->real_escape_string($_POST['keterangan'] ?? '');
    $waktu  = date('H:i:s');
    $sql = "INSERT INTO absensi (santri_id, tanggal, waktu, status, metode, keterangan, dicatat_oleh)
            VALUES ($sid, '$tgl', '$waktu', '$status', 'manual', '$ket', {$_SESSION['user_id']})
            ON DUPLICATE KEY UPDATE status='$status', metode='manual', keterangan='$ket', dicatat_oleh={$_SESSION['user_id']}";
    if ($conn->query($sql)) {
        $msg = 'Absensi berhasil disimpan.';
    } else {
        $msg = 'Gagal: ' . $conn->error;
    }
}
?>

<?php if ($msg): ?>
<div style="background:#f0fdf4;border:1px solid #bbf7d0;color:#16a34a;padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:13px">
  ✅ <?= htmlspecialchars($msg) ?>
</div>
<?php endif; ?>

<!-- Filter tanggal -->
<div style="display:flex;align-items:center;gap:12px;margin-bottom:20px">
  <form method="GET" style="display:flex;gap:10px;align-items:center">
    <label style="font-size:13px;font-weight:500">Tanggal:</label>
    <input type="date" name="tgl" value="<?= $tgl ?>" style="width:160px">
    <button type="submit" class="btn btn-primary btn-sm">Tampilkan</button>
  </form>
  <span style="font-size:12px;color:#64748b">— atau —</span>
  <button class="btn btn-outline btn-sm" onclick="document.getElementById('modal-manual').style.display='flex'">
    ✏️ Input Manual
  </button>
</div>

<!-- Stats mini -->
<?php
$total = $conn->query("SELECT COUNT(*) as c FROM santri WHERE aktif=1")->fetch_assoc()['c'];
$hadir = $conn->query("SELECT COUNT(*) as c FROM absensi WHERE tanggal='$tgl' AND status='hadir'")->fetch_assoc()['c'];
$izin  = $conn->query("SELECT COUNT(*) as c FROM absensi WHERE tanggal='$tgl' AND status='izin'")->fetch_assoc()['c'];
$sakit = $conn->query("SELECT COUNT(*) as c FROM absensi WHERE tanggal='$tgl' AND status='sakit'")->fetch_assoc()['c'];
$alpa  = $conn->query("SELECT COUNT(*) as c FROM absensi WHERE tanggal='$tgl' AND status='alpa'")->fetch_assoc()['c'];
$belum = $total - $hadir - $izin - $sakit - $alpa;
?>
<div class="stat-grid" style="grid-template-columns:repeat(5,1fr);margin-bottom:20px">
  <div class="stat-card"><div class="stat-label">Hadir</div><div class="stat-val c-green"><?= $hadir ?></div></div>
  <div class="stat-card"><div class="stat-label">Izin</div><div class="stat-val c-blue"><?= $izin ?></div></div>
  <div class="stat-card"><div class="stat-label">Sakit</div><div class="stat-val c-amber"><?= $sakit ?></div></div>
  <div class="stat-card"><div class="stat-label">Alpa</div><div class="stat-val c-red"><?= $alpa ?></div></div>
  <div class="stat-card"><div class="stat-label">Belum</div><div class="stat-val"><?= $belum ?></div></div>
</div>

<!-- Tabel absensi -->
<div class="card">
  <div class="card-header">
    <span class="card-title">Data Absensi — <?= date('d F Y', strtotime($tgl)) ?></span>
    <span style="font-size:12px;color:#64748b"><?= $hadir + $izin + $sakit + $alpa ?> tercatat dari <?= $total ?> santri</span>
  </div>
  <?php
  $r = $conn->query("SELECT s.id, s.nis, s.nama, s.kelas, a.waktu, a.status, a.metode, a.keterangan
                     FROM santri s
                     LEFT JOIN absensi a ON s.id=a.santri_id AND a.tanggal='$tgl'
                     WHERE s.aktif=1 ORDER BY s.kelas, s.nama");
  ?>
  <table>
    <thead>
      <tr><th>NIS</th><th>Nama</th><th>Kelas</th><th>Waktu</th><th>Status</th><th>Metode</th><th>Aksi</th></tr>
    </thead>
    <tbody>
      <?php while ($row = $r->fetch_assoc()): ?>
      <tr>
        <td style="font-family:monospace;font-size:12px"><?= $row['nis'] ?></td>
        <td style="font-weight:500"><?= htmlspecialchars($row['nama']) ?></td>
        <td><?= htmlspecialchars($row['kelas']) ?></td>
        <td><?= $row['waktu'] ? substr($row['waktu'],0,5) : '<span style="color:#94a3b8">—</span>' ?></td>
        <td>
          <?php if ($row['status']): ?>
            <span class="badge badge-<?= $row['status'] ?>"><?= ucfirst($row['status']) ?></span>
          <?php else: ?>
            <span class="badge" style="background:#f1f5f9;color:#64748b">Belum</span>
          <?php endif; ?>
        </td>
        <td>
          <?php if ($row['metode']): ?>
            <span class="badge badge-<?= $row['metode'] ?>"><?= $row['metode']==='face'?'📷 Kamera':'✏️ Manual' ?></span>
          <?php else: ?><span style="color:#94a3b8">—</span><?php endif; ?>
        </td>
        <td>
          <button class="btn btn-outline btn-sm" onclick="editAbsensi(<?= $row['id'] ?>, '<?= htmlspecialchars($row['nama'],ENT_QUOTES) ?>', '<?= $row['status'] ?>')">
            Edit
          </button>
        </td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>

<!-- MODAL INPUT MANUAL -->
<div id="modal-manual" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:999;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:16px;padding:28px;width:100%;max-width:440px;margin:16px">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
      <h3 style="font-size:16px;font-weight:600">Input Absensi Manual</h3>
      <button onclick="document.getElementById('modal-manual').style.display='none'" style="background:none;border:none;font-size:20px;cursor:pointer;color:#64748b">×</button>
    </div>
    <form method="POST">
      <div class="form-group">
        <label>Santri</label>
        <select name="santri_id" required>
          <option value="">— Pilih Santri —</option>
          <?php
          $rs = $conn->query("SELECT id, nis, nama, kelas FROM santri WHERE aktif=1 ORDER BY kelas, nama");
          $kelas_saat = '';
          while ($s = $rs->fetch_assoc()):
            if ($s['kelas'] !== $kelas_saat) {
                if ($kelas_saat) echo '</optgroup>';
                echo '<optgroup label="'.htmlspecialchars($s['kelas']).'">';
                $kelas_saat = $s['kelas'];
            }
          ?>
          <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nama']) ?> (<?= $s['nis'] ?>)</option>
          <?php endwhile; if ($kelas_saat) echo '</optgroup>'; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Status</label>
        <select name="status" required>
          <option value="hadir">✅ Hadir</option>
          <option value="izin">🔵 Izin</option>
          <option value="sakit">🟡 Sakit</option>
          <option value="alpa">🔴 Alpa</option>
        </select>
      </div>
      <div class="form-group">
        <label>Keterangan (opsional)</label>
        <textarea name="keterangan" rows="2" style="resize:none" placeholder="Contoh: izin acara keluarga"></textarea>
      </div>
      <div style="display:flex;gap:10px;justify-content:flex-end">
        <button type="button" class="btn btn-outline" onclick="document.getElementById('modal-manual').style.display='none'">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan</button>
      </div>
    </form>
  </div>
</div>

<script>
function editAbsensi(id, nama, status) {
  const modal = document.getElementById('modal-manual');
  const sel = modal.querySelector('select[name=santri_id]');
  const statusSel = modal.querySelector('select[name=status]');
  for (let o of sel.options) { if (o.value == id) { o.selected = true; break; } }
  if (status) { for (let o of statusSel.options) { if (o.value == status) { o.selected = true; break; } } }
  modal.style.display = 'flex';
}
</script>

<?php require '_footer.php'; ?>
