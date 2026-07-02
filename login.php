<?php
session_start();
if (isset($_SESSION['user_id'])) { header('Location: admin/dashboard.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'api/koneksi.php';
    $username = $conn->real_escape_string(trim($_POST['username']));
    $password = $_POST['password'];
    $r = $conn->query("SELECT * FROM users WHERE username='$username' AND aktif=1");
    if ($r->num_rows > 0) {
        $user = $r->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_nama'] = $user['nama'];
            $_SESSION['user_role'] = $user['role'];
            header('Location: admin/dashboard.php'); exit;
        }
    }
    $error = 'Username atau password salah.';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login — Absensi Santri</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Plus Jakarta Sans',sans-serif;background:#f0f4f8;min-height:100vh;display:flex;align-items:center;justify-content:center}
.card{background:#fff;border-radius:16px;padding:40px;width:100%;max-width:380px;box-shadow:0 4px 24px rgba(0,0,0,0.08)}
.logo{text-align:center;margin-bottom:28px}
.logo-icon{width:56px;height:56px;background:#1a56db;border-radius:14px;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;font-size:24px}
.logo h1{font-size:18px;font-weight:600;color:#1e293b}
.logo p{font-size:13px;color:#64748b;margin-top:4px}
label{display:block;font-size:13px;font-weight:500;color:#374151;margin-bottom:6px}
input[type=text],input[type=password]{width:100%;padding:10px 14px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:14px;font-family:inherit;outline:none;transition:.2s;color:#1e293b}
input:focus{border-color:#1a56db;box-shadow:0 0 0 3px rgba(26,86,219,.1)}
.form-group{margin-bottom:16px}
.error{background:#fef2f2;border:1px solid #fecaca;color:#dc2626;font-size:13px;padding:10px 14px;border-radius:8px;margin-bottom:16px}
.btn{width:100%;padding:11px;background:#1a56db;color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:600;font-family:inherit;cursor:pointer;transition:.2s}
.btn:hover{background:#1e40af}
.hint{text-align:center;font-size:12px;color:#94a3b8;margin-top:16px}
</style>
</head>
<body>
<div class="card">
  <div class="logo">
    <div class="logo-icon">🕌</div>
    <h1>RTQ Darul Ulum Al-Fadholi</h1>
    <p>Sistem Absensi Santri</p>
  </div>
  <?php if ($error): ?>
  <div class="error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <form method="POST">
    <div class="form-group">
      <label>Username</label>
      <input type="text" name="username" placeholder="admin" required autocomplete="username">
    </div>
    <div class="form-group">
      <label>Password</label>
      <input type="password" name="password" placeholder="••••••••" required autocomplete="current-password">
    </div>
    <button type="submit" class="btn">Masuk</button>
  </form>
  <p class="hint">Default: admin / admin123</p>
</div>
</body>
</html>
