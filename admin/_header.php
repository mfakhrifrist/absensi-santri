<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: ../login.php'); exit; }
$current = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $page_title ?? 'Admin' ?> — Absensi Santri</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
*{box-sizing:border-box;margin:0;padding:0}
:root{
  --blue:#1a56db;--blue-light:#eff6ff;--blue-dark:#1e40af;
  --green:#16a34a;--green-light:#f0fdf4;
  --amber:#d97706;--amber-light:#fffbeb;
  --red:#dc2626;--red-light:#fef2f2;
  --gray:#64748b;--bg:#f8fafc;--white:#fff;
  --border:#e2e8f0;--text:#1e293b;--text-muted:#64748b;
  --sidebar:220px;
}
body{font-family:'Plus Jakarta Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;display:flex}

/* SIDEBAR */
.sidebar{width:var(--sidebar);background:var(--white);border-right:1px solid var(--border);display:flex;flex-direction:column;position:fixed;height:100vh;z-index:100}
.sidebar-logo{padding:20px 20px 16px;border-bottom:1px solid var(--border)}
.sidebar-logo h1{font-size:16px;font-weight:700;color:var(--text)}
.sidebar-logo p{font-size:11px;color:var(--text-muted);margin-top:2px}
.sidebar-nav{flex:1;padding:12px 10px;overflow-y:auto}
.nav-label{font-size:10px;font-weight:600;color:#94a3b8;letter-spacing:.8px;padding:8px 10px 4px;text-transform:uppercase}
.nav-item{display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:8px;font-size:13px;font-weight:500;color:var(--text-muted);text-decoration:none;transition:.15s;margin-bottom:2px}
.nav-item:hover{background:var(--bg);color:var(--text)}
.nav-item.active{background:var(--blue-light);color:var(--blue)}
.nav-item .icon{font-size:17px;width:20px;text-align:center}
.sidebar-footer{padding:14px 16px;border-top:1px solid var(--border)}
.user-info{display:flex;align-items:center;gap:10px}
.user-avatar{width:34px;height:34px;border-radius:50%;background:var(--blue);color:#fff;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:600;flex-shrink:0}
.user-name{font-size:13px;font-weight:500}
.user-role{font-size:11px;color:var(--text-muted)}
.logout-btn{margin-left:auto;font-size:18px;text-decoration:none;color:var(--text-muted)}
.logout-btn:hover{color:var(--red)}

/* MAIN */
.main{margin-left:var(--sidebar);flex:1;min-height:100vh;display:flex;flex-direction:column}
.topbar{background:var(--white);border-bottom:1px solid var(--border);padding:14px 28px;display:flex;align-items:center;justify-content:space-between}
.topbar h2{font-size:17px;font-weight:600}
.topbar-right{font-size:13px;color:var(--text-muted)}
.content{padding:24px 28px;flex:1}

/* CARDS */
.stat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:16px;margin-bottom:24px}
.stat-card{background:var(--white);border-radius:12px;padding:18px;border:1px solid var(--border)}
.stat-label{font-size:12px;color:var(--text-muted);margin-bottom:8px;font-weight:500}
.stat-val{font-size:28px;font-weight:700}
.stat-sub{font-size:12px;color:var(--text-muted);margin-top:4px}
.c-blue{color:var(--blue)}.c-green{color:var(--green)}.c-amber{color:var(--amber)}.c-red{color:var(--red)}

/* TABLE */
.card{background:var(--white);border-radius:12px;border:1px solid var(--border);overflow:hidden;margin-bottom:20px}
.card-header{padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
.card-title{font-size:14px;font-weight:600}
table{width:100%;border-collapse:collapse;font-size:13px}
th{background:#f8fafc;padding:10px 16px;text-align:left;font-weight:600;font-size:12px;color:var(--text-muted);border-bottom:1px solid var(--border)}
td{padding:11px 16px;border-bottom:1px solid #f1f5f9;color:var(--text)}
tr:last-child td{border-bottom:none}
tr:hover td{background:#fafbfc}
.badge{display:inline-block;padding:2px 10px;border-radius:20px;font-size:11px;font-weight:600}
.badge-hadir{background:var(--green-light);color:var(--green)}
.badge-izin{background:var(--blue-light);color:var(--blue)}
.badge-sakit{background:var(--amber-light);color:var(--amber)}
.badge-alpa{background:var(--red-light);color:var(--red)}
.badge-face{background:#f3f4f6;color:#374151}
.badge-manual{background:#fdf4ff;color:#7e22ce}

/* BUTTONS */
.btn{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:8px;font-size:13px;font-weight:500;font-family:inherit;cursor:pointer;border:1.5px solid transparent;transition:.15s;text-decoration:none}
.btn-primary{background:var(--blue);color:#fff;border-color:var(--blue)}
.btn-primary:hover{background:var(--blue-dark)}
.btn-outline{background:#fff;color:var(--text);border-color:var(--border)}
.btn-outline:hover{background:var(--bg)}
.btn-danger{background:var(--red-light);color:var(--red);border-color:#fecaca}
.btn-sm{padding:5px 12px;font-size:12px}

/* FORM */
.form-group{margin-bottom:16px}
.form-group label{display:block;font-size:13px;font-weight:500;margin-bottom:6px}
input[type=text],input[type=date],input[type=tel],select,textarea{width:100%;padding:9px 12px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;outline:none;transition:.2s;color:var(--text);background:#fff}
input:focus,select:focus,textarea:focus{border-color:var(--blue);box-shadow:0 0 0 3px rgba(26,86,219,.08)}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:16px}

.empty{text-align:center;padding:40px;color:var(--text-muted);font-size:13px}
.loading{text-align:center;padding:20px;color:var(--text-muted)}
</style>
</head>
<body>
<aside class="sidebar">
  <div class="sidebar-logo">
    <h1>🕌 RTQ Darul Ulum Al-Fadholi</h1>
    <p>Sistem Absensi Santri</p>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-label">Menu Utama</div>
    <a href="dashboard.php" class="nav-item <?= $current==='dashboard'?'active':'' ?>">
      <span class="icon">📊</span> Dashboard
    </a>
    <a href="absensi.php" class="nav-item <?= $current==='absensi'?'active':'' ?>">
      <span class="icon">✅</span> Absensi Hari Ini
    </a>
    <a href="santri.php" class="nav-item <?= $current==='santri'?'active':'' ?>">
      <span class="icon">👥</span> Data Santri
    </a>
    <div class="nav-label">Laporan</div>
    <a href="laporan.php" class="nav-item <?= $current==='laporan'?'active':'' ?>">
      <span class="icon">📋</span> Rekap Bulanan
    </a>
    <?php if ($_SESSION['user_role'] === 'admin'): ?>
    <div class="nav-label">Pengaturan</div>
    <a href="users.php" class="nav-item <?= $current==='users'?'active':'' ?>">
      <span class="icon">⚙️</span> Kelola User
    </a>
    <?php endif; ?>
  </nav>
  <div class="sidebar-footer">
    <div class="user-info">
      <div class="user-avatar"><?= strtoupper(substr($_SESSION['user_nama'],0,2)) ?></div>
      <div>
        <div class="user-name"><?= htmlspecialchars($_SESSION['user_nama']) ?></div>
        <div class="user-role"><?= $_SESSION['user_role'] ?></div>
      </div>
      <a href="../logout.php" class="logout-btn" title="Logout">⏻</a>
    </div>
  </div>
</aside>
<main class="main">
  <div class="topbar">
    <h2><?= $page_title ?? '' ?></h2>
    <div class="topbar-right" id="jam">📅 <?= date('l, d F Y') ?></div>
  </div>
  <div class="content">
