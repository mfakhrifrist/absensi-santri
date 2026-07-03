<?php $page_title = 'Kelola User'; require '_header.php'; require '../api/koneksi.php'; ?>

<?php
$msg = $msg_type = '';

// Hapus user
if (isset($_GET['hapus'])) {
    $id = intval($_GET['hapus']);
    if ($id === $_SESSION['user_id']) {
        $msg = 'Tidak bisa menghapus akun yang sedang digunakan.'; $msg_type = 'error';
    } else {
        $conn->query("UPDATE users SET aktif=0 WHERE id=$id");
        $msg = 'User berhasil dihapus.'; $msg_type = 'success';
    }
}

// Simpan / update user
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id       = intval($_POST['id'] ?? 0);
    $username = $conn->real_escape_string(trim($_POST['username']));
    $nama     = $conn->real_escape_string(trim($_POST['nama']));
    $role     = $conn->real_escape_string($_POST['role'] ?? 'guru');
    $password = trim($_POST['password'] ?? '');

    if ($id > 0) {
        // Update — password hanya diubah jika diisi
        if ($password !== '') {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $sql = "UPDATE users SET username='$username', nama='$nama', role='$role', password='$hash' WHERE id=$id";
        } else {
            $sql = "UPDATE users SET username='$username', nama='$nama', role='$role' WHERE id=$id";
        }
        $msg = 'Data user diperbarui.';
    } else {
        if ($password === '') {
            $msg = 'Password wajib diisi untuk user baru.'; $msg_type = 'error';
            goto skip_save;
        }
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $sql = "INSERT INTO users (username, password, nama, role) VALUES ('$username','$hash','$nama','$role')";
        $msg = 'User baru berhasil ditambahkan.';
    }

    if ($conn->query($sql)) { $msg_type = 'success'; }
    else {
        $msg = 'Gagal: ' . ($conn->error);
        $msg_type = 'error';
    }
    skip_save:;
}
?>

<?php if ($msg): ?>
<div style="background:<?= $msg_type==='success'?'#f0fdf4':'#fef2f2' ?>;border:1px solid <?= $msg_type==='success'?'#bbf7d0':'#fecaca' ?>;color:<?= $msg_type==='success'?'#16a34a':'#dc2626' ?>;padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:13px">
  <?= $msg_type==='success'?'✅':'❌' ?> <?= htmlspecialchars($msg) ?>
</div>
<?php endif; ?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
  <div style="font-size:13px;color:#64748b">
    Kelola akun admin dan guru yang dapat mengakses sistem
  </div>
  <button class="btn btn-primary" onclick="openModal()">+ Tambah User</button>
</div>

<div class="card">
  <table>
    <thead>
      <tr><th>Nama</th><th>Username</th><th>Role</th><th>Dibuat</th><th>Aksi</th></tr>
    </thead>
    <tbody>
      <?php
      $r = $conn->query("SELECT * FROM users WHERE aktif=1 ORDER BY role, nama");
      while ($row = $r->fetch_assoc()):
      ?>
      <tr>
        <td style="font-weight:500"><?= htmlspecialchars($row['nama']) ?></td>
        <td style="font-family:monospace;font-size:12px"><?= htmlspecialchars($row['username']) ?></td>
        <td>
          <span class="badge <?= $row['role']==='admin'?'badge-hadir':'badge-izin' ?>">
            <?= $row['role'] === 'admin' ? '👑 Admin' : '📚 Guru' ?>
          </span>
        </td>
        <td style="font-size:12px;color:#64748b"><?= date('d/m/Y', strtotime($row['created_at'])) ?></td>
        <td style="display:flex;gap:6px">
          <button class="btn btn-outline btn-sm" onclick='openModal(<?= json_encode($row) ?>)'>Edit</button>
          <?php if ($row['id'] !== $_SESSION['user_id']): ?>
          <a href="?hapus=<?= $row['id'] ?>" class="btn btn-danger btn-sm"
             onclick="return confirm('Hapus user <?= htmlspecialchars($row['nama'],ENT_QUOTES) ?>?')">Hapus</a>
          <?php else: ?>
          <span style="font-size:12px;color:#94a3b8;padding:5px 8px">Akun aktif</span>
          <?php endif; ?>
        </td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>

<!-- MODAL -->
<div id="modal-user" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:999;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:16px;padding:28px;width:100%;max-width:420px;margin:16px">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
      <h3 id="modal-title" style="font-size:16px;font-weight:600">Tambah User Baru</h3>
      <button onclick="closeModal()" style="background:none;border:none;font-size:22px;cursor:pointer;color:#64748b">×</button>
    </div>
    <form method="POST">
      <input type="hidden" name="id" id="f-id" value="0">
      <div class="form-group">
        <label>Nama Lengkap</label>
        <input type="text" name="nama" id="f-nama" required placeholder="Ustadz / Ustadzah ...">
      </div>
      <div class="form-group">
        <label>Username</label>
        <input type="text" name="username" id="f-username" required placeholder="username untuk login">
      </div>
      <div class="form-group">
        <label>Password <span id="pass-hint" style="font-size:11px;color:#94a3b8">(wajib diisi)</span></label>
        <input type="text" name="password" id="f-password" placeholder="Kosongkan jika tidak ingin mengubah">
      </div>
      <div class="form-group">
        <label>Role</label>
        <select name="role" id="f-role">
          <option value="guru">📚 Guru</option>
          <option value="admin">👑 Admin</option>
        </select>
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
  const modal = document.getElementById('modal-user');
  const hint  = document.getElementById('pass-hint');
  if (data) {
    document.getElementById('modal-title').textContent = 'Edit User';
    document.getElementById('btn-submit').textContent  = 'Perbarui';
    document.getElementById('f-id').value       = data.id;
    document.getElementById('f-nama').value     = data.nama;
    document.getElementById('f-username').value = data.username;
    document.getElementById('f-password').value = '';
    document.getElementById('f-role').value     = data.role;
    hint.textContent = '(kosongkan jika tidak ingin mengubah)';
  } else {
    document.getElementById('modal-title').textContent = 'Tambah User Baru';
    document.getElementById('btn-submit').textContent  = 'Simpan';
    document.getElementById('f-id').value = 0;
    document.forms[0].reset();
    hint.textContent = '(wajib diisi)';
  }
  modal.style.display = 'flex';
}
function closeModal() { document.getElementById('modal-user').style.display = 'none'; }
</script>

<?php require '_footer.php'; ?>
