<?php $page_title = 'Data Santri'; require '_header.php'; require '../api/koneksi.php'; ?>

<?php
$msg = $msg_type = '';

// Hapus santri
if (isset($_GET['hapus'])) {
    $id = intval($_GET['hapus']);
    if ($conn->query("UPDATE santri SET aktif=0 WHERE id=$id")) {
        $msg = 'Santri berhasil dihapus.'; $msg_type = 'success';
    }
}

// Simpan / update santri
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id       = intval($_POST['id'] ?? 0);
    $nis      = $conn->real_escape_string(trim($_POST['nis']));
    $nama     = $conn->real_escape_string(trim($_POST['nama']));
    $kelas    = $conn->real_escape_string(trim($_POST['kelas']));
    $jk       = $conn->real_escape_string($_POST['jenis_kelamin'] ?? 'L');
    $wali     = $conn->real_escape_string(trim($_POST['wali_nama'] ?? ''));
    $telp     = $conn->real_escape_string(trim($_POST['wali_telp'] ?? ''));
    $alamat   = $conn->real_escape_string(trim($_POST['alamat'] ?? ''));

    if ($id > 0) {
        $sql = "UPDATE santri SET nis='$nis',nama='$nama',kelas='$kelas',jenis_kelamin='$jk',
                wali_nama='$wali',wali_telp='$telp',alamat='$alamat' WHERE id=$id";
        $msg = 'Data santri diperbarui.';
    } else {
        $sql = "INSERT INTO santri (nis,nama,kelas,jenis_kelamin,wali_nama,wali_telp,alamat)
                VALUES ('$nis','$nama','$kelas','$jk','$wali','$telp','$alamat')";
        $msg = 'Santri baru berhasil ditambahkan.';
    }
    if ($conn->query($sql)) { $msg_type = 'success'; }
    else { $msg = 'Gagal: ' . $conn->error; $msg_type = 'error'; }
}
?>

<?php if ($msg): ?>
<div style="background:<?= $msg_type==='success'?'#f0fdf4':'#fef2f2' ?>;border:1px solid <?= $msg_type==='success'?'#bbf7d0':'#fecaca' ?>;color:<?= $msg_type==='success'?'#16a34a':'#dc2626' ?>;padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:13px">
  <?= $msg_type==='success'?'✅':'❌' ?> <?= htmlspecialchars($msg) ?>
</div>
<?php endif; ?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
  <div style="font-size:13px;color:#64748b">
    <?= $conn->query("SELECT COUNT(*) as c FROM santri WHERE aktif=1")->fetch_assoc()['c'] ?> santri aktif
  </div>
  <button class="btn btn-primary" onclick="openModal()">+ Tambah Santri</button>
</div>

<!-- Filter -->
<div style="display:flex;gap:10px;margin-bottom:16px">
  <input type="text" id="cari" placeholder="🔍 Cari nama atau NIS..." style="width:240px" oninput="filterTable()">
  <select id="filter-kelas" onchange="filterTable()" style="width:160px">
    <option value="">Semua Kelas</option>
    <?php
    $rk = $conn->query("SELECT DISTINCT kelas FROM santri WHERE aktif=1 AND kelas IS NOT NULL ORDER BY kelas");
    while ($k = $rk->fetch_assoc()): ?>
    <option value="<?= htmlspecialchars($k['kelas']) ?>"><?= htmlspecialchars($k['kelas']) ?></option>
    <?php endwhile; ?>
  </select>
</div>

<div class="card">
  <table id="tabel-santri">
    <thead>
      <tr><th>NIS</th><th>Nama</th><th>Kelas</th><th>L/P</th><th>Wali / No. HP</th><th>Total Hadir</th><th>Aksi</th></tr>
    </thead>
    <tbody>
      <?php
      $r = $conn->query("SELECT s.*,
                         (SELECT COUNT(*) FROM absensi a WHERE a.santri_id=s.id AND a.status='hadir') as total_hadir
                         FROM santri s WHERE s.aktif=1 ORDER BY s.kelas, s.nama");
      while ($row = $r->fetch_assoc()):
      ?>
      <tr data-nama="<?= strtolower($row['nama']) ?>" data-nis="<?= $row['nis'] ?>" data-kelas="<?= $row['kelas'] ?>">
        <td style="font-family:monospace;font-size:12px"><?= $row['nis'] ?></td>
        <td style="font-weight:500"><?= htmlspecialchars($row['nama']) ?></td>
        <td><?= htmlspecialchars($row['kelas']) ?></td>
        <td><?= $row['jenis_kelamin'] ?></td>
        <td>
          <div style="font-size:13px"><?= htmlspecialchars($row['wali_nama'] ?? '—') ?></div>
          <div style="font-size:11px;color:#64748b"><?= $row['wali_telp'] ?? '' ?></div>
        </td>
        <td><span class="badge badge-hadir"><?= $row['total_hadir'] ?>x</span></td>
        <td style="display:flex;gap:6px">
          <button class="btn btn-outline btn-sm" onclick='openModal(<?= htmlspecialchars(json_encode($row), ENT_QUOTES) ?>)'>Edit</button>
          <a href="?hapus=<?= $row['id'] ?>" class="btn btn-danger btn-sm"
             onclick="return confirm('Hapus santri <?= htmlspecialchars($row['nama'],ENT_QUOTES) ?>?')">Hapus</a>
        </td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>

<!-- MODAL FORM SANTRI -->
<div id="modal-santri" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:999;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:16px;padding:28px;width:100%;max-width:520px;margin:16px;max-height:90vh;overflow-y:auto">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
      <h3 id="modal-title" style="font-size:16px;font-weight:600">Tambah Santri Baru</h3>
      <button onclick="closeModal()" style="background:none;border:none;font-size:22px;cursor:pointer;color:#64748b">×</button>
    </div>
    <form method="POST" id="form-santri">
      <input type="hidden" name="id" id="f-id" value="0">
      <div class="form-row">
        <div class="form-group">
          <label>NIS <span style="color:red">*</span></label>
          <input type="text" name="nis" id="f-nis" required placeholder="2024001">
        </div>
        <div class="form-group">
          <label>Jenis Kelamin</label>
          <select name="jenis_kelamin" id="f-jk">
            <option value="L">Laki-laki</option>
            <option value="P">Perempuan</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label>Nama Lengkap <span style="color:red">*</span></label>
        <input type="text" name="nama" id="f-nama" required placeholder="Ahmad Fauzi">
      </div>
      <div class="form-group">
        <label>Kelas</label>
        <input type="text" name="kelas" id="f-kelas" placeholder="Kelas 1A">
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Nama Wali</label>
          <input type="text" name="wali_nama" id="f-wali" placeholder="Bapak/Ibu ...">
        </div>
        <div class="form-group">
          <label>No. HP Wali</label>
          <input type="tel" name="wali_telp" id="f-telp" placeholder="08xxx">
        </div>
      </div>
      <div class="form-group">
        <label>Alamat</label>
        <textarea name="alamat" id="f-alamat" rows="2" style="resize:none" placeholder="Alamat lengkap..."></textarea>
      </div>
      <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:8px">
        <button type="button" class="btn btn-outline" onclick="closeModal()">Batal</button>
        <button type="submit" class="btn btn-primary" id="btn-submit">Simpan</button>
      </div>
    </form>
  </div>
</div>

<script>
function openModal(data) {
  const modal = document.getElementById('modal-santri');
  if (data) {
    document.getElementById('modal-title').textContent = 'Edit Data Santri';
    document.getElementById('btn-submit').textContent = 'Perbarui';
    document.getElementById('f-id').value    = data.id;
    document.getElementById('f-nis').value   = data.nis;
    document.getElementById('f-nama').value  = data.nama;
    document.getElementById('f-kelas').value = data.kelas || '';
    document.getElementById('f-jk').value    = data.jenis_kelamin || 'L';
    document.getElementById('f-wali').value  = data.wali_nama || '';
    document.getElementById('f-telp').value  = data.wali_telp || '';
    document.getElementById('f-alamat').value= data.alamat || '';
  } else {
    document.getElementById('modal-title').textContent = 'Tambah Santri Baru';
    document.getElementById('btn-submit').textContent = 'Simpan';
    document.getElementById('form-santri').reset();
    document.getElementById('f-id').value = 0;
  }
  modal.style.display = 'flex';
}
function closeModal() { document.getElementById('modal-santri').style.display = 'none'; }
function filterTable() {
  const cari   = document.getElementById('cari').value.toLowerCase();
  const kelas  = document.getElementById('filter-kelas').value.toLowerCase();
  document.querySelectorAll('#tabel-santri tbody tr').forEach(tr => {
    const nama  = tr.dataset.nama || '';
    const nis   = tr.dataset.nis  || '';
    const kls   = tr.dataset.kelas?.toLowerCase() || '';
    const matchCari  = !cari  || nama.includes(cari) || nis.includes(cari);
    const matchKelas = !kelas || kls === kelas;
    tr.style.display = matchCari && matchKelas ? '' : 'none';
  });
}
</script>

<?php require '_footer.php'; ?>
