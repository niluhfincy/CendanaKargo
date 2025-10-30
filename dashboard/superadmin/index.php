<?php
session_start();
if(!isset($_SESSION['username'])){
    header("Location: ../../auth/login");
    exit;
}

if(isset($_SESSION['role']) && $_SESSION['role'] !== 'superAdmin'){
    header("Location: ../../?error=unauthorized");
    exit;
}

include '../../config/database.php';

 $current_month = date('m');
 $current_year = date('Y');
 $current_date = date('Y-m-d');

 $filter = isset($_GET['filter']) ? $_GET['filter'] : 'bulan';
 $where_clause = ($filter === 'hari')
    ? "DATE(tanggal) = '$current_date'"
    : "MONTH(tanggal) = '$current_month' AND YEAR(tanggal) = '$current_year'";

// === variabel untuk ditampilkan di kotak info ===
 $selected_date_display = ($filter === 'hari') ? " (" . date('d F Y') . ")" : " " . date('F Y');

// === TOTAL DATA ===
 $total_pengiriman = $conn->query("SELECT COUNT(*) AS total FROM pengiriman WHERE $where_clause")->fetch_assoc()['total'] ?? 0;
 $total_surat_jalan = $conn->query("SELECT COUNT(*) AS total FROM surat_jalan WHERE $where_clause")->fetch_assoc()['total'] ?? 0;
 $total_pendapatan = $conn->query("SELECT SUM(total_tarif) AS total FROM pengiriman WHERE $where_clause")->fetch_assoc()['total'] ?? 0;


// === PENDAPATAN PER ADMIN ===
 $pendapatan_admin = $conn->query("
    SELECT u.id, u.username,
        SUM(CASE WHEN p.pembayaran = 'cash' THEN p.total_tarif ELSE 0 END) AS cash,
        SUM(CASE WHEN p.pembayaran = 'transfer' THEN p.total_tarif ELSE 0 END) AS transfer,
        SUM(CASE WHEN p.pembayaran = 'bayar di tempat' THEN p.total_tarif ELSE 0 END) AS cod,
        SUM(p.total_tarif) AS total
    FROM pengiriman p
    JOIN user u ON p.id_user = u.id
    WHERE $where_clause AND u.role != 'superSuperAdmin'
    GROUP BY u.id, u.username
");

// === PENGIRIMAN PER ADMIN ===
 $pengiriman_admin = $conn->query("
    SELECT u.id, u.username,
        SUM(CASE WHEN p.status = 'bkd' THEN 1 ELSE 0 END) AS bkd,
        SUM(CASE WHEN p.status = 'dalam pengiriman' THEN 1 ELSE 0 END) AS perjalanan,
        SUM(CASE WHEN p.status = 'sampai tujuan' THEN 1 ELSE 0 END) AS sampai,
        SUM(CASE WHEN p.status = 'pod' THEN 1 ELSE 0 END) AS pod,
        SUM(CASE WHEN p.status = 'dibatalkan' THEN 1 ELSE 0 END) AS batal,
        COUNT(p.id) AS total
    FROM pengiriman p
    JOIN user u ON p.id_user = u.id
    WHERE $where_clause AND u.role != 'superSuperAdmin'
    GROUP BY u.id, u.username
");

// === SURAT JALAN PER ADMIN ===
 $surat_jalan_admin = $conn->query("
    SELECT u.id, u.username,
        SUM(CASE WHEN s.status = 'dalam perjalanan' THEN 1 ELSE 0 END) AS perjalanan,
        SUM(CASE WHEN s.status = 'sampai tujuan' THEN 1 ELSE 0 END) AS sampai,
        SUM(CASE WHEN s.status = 'dibatalkan' THEN 1 ELSE 0 END) AS batal,
        COUNT(s.id) AS total
    FROM surat_jalan s
    JOIN user u ON s.id_user = u.id
    WHERE $where_clause AND u.role != 'superSuperAdmin'
    GROUP BY u.id, u.username
");
?>

<?php
 $title = "Dashboard - Cendana Kargo";
 $page = "dashboard";
include '../../templates/header.php';
include '../../components/navDashboard.php';
include '../../components/sidebar_offcanvas.php';
?>

<div class="container-fluid">
  <div class="row">
    <?php include '../../components/sidebar.php'; ?>

    <main class="col-lg-10 bg-light">
      <div class="container-fluid p-4">

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
          <div>
            <h1 class="h3 fw-bold mb-1">Dashboard SuperAdmin</h1>
            <p class="text-muted small mb-0">Selamat datang, <?= htmlspecialchars($_SESSION['username']); ?>! Kelola semua data sistem Cendana Kargo</p>
          </div>
          <div class="btn-group shadow-sm" role="group">
            <a href="?filter=bulan" class="btn btn-outline-primary btn-sm <?= $filter === 'bulan' ? 'active' : ''; ?>">Bulan Ini</a>
            <a href="?filter=hari" class="btn btn-outline-success btn-sm <?= $filter === 'hari' ? 'active' : ''; ?>">Hari Ini</a>
          </div>
        </div>

        <!-- Periode Info Box -->
<div class="alert alert-info py-2 px-3 small d-inline-block mb-4" role="alert">
    <i class="fa-solid fa-calendar-alt me-2"></i>
    Data Agregat untuk periode: <strong><?= $selected_date_display; ?></strong>
</div>

        <!-- KARTU STATISTIK -->
                <!-- KARTU STATISTIK -->
        <div class="row g-4 mb-4">
          <!-- Total Pendapatan -->
          <div class="col-xl-4 col-md-6">
            <div class="card border-0 shadow-sm h-100">
              <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <p class="text-muted small mb-1">Total Pendapatan <span class="text-primary">(<?= $filter === 'hari' ? "Hari Ini (" . date('d F Y') . ")" : "Bulan " . date('F Y'); ?>)</span></p>
                        <h3 class="fw-bold mb-0">Rp <?= number_format($total_pendapatan, 0, ',', '.'); ?></h3>
                    </div>
                    <div class="flex-shrink-0 ms-auto">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-cash-stack text-primary" viewBox="0 0 16 16">
                            <path d="M1 3a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1H1zm7 8a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"/>
                            <path d="M0 5a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H1a1 1 0 0 1-1-1V5zm3 0a2 2 0 0 1-2 2v4a2 2 0 0 1 2 2h10a2 2 0 0 1 2-2V7a2 2 0 0 1-2-2H3z"/>
                        </svg>
                    </div>
                </div>
              </div>
            </div>
          </div>
          <!-- Total Pengiriman -->
          <div class="col-xl-4 col-md-6">
            <div class="card border-0 shadow-sm h-100">
              <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <p class="text-muted small mb-1">Total Pengiriman <span class="text-success">(<?= $filter === 'hari' ? "Hari Ini (" . date('d F Y') . ")" : "Bulan " . date('F Y'); ?>)</span></p>
                        <h3 class="fw-bold mb-0"><?= $total_pengiriman; ?></h3>
                    </div>
                    <div class="flex-shrink-0 ms-auto">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-cash-stack text-success" viewBox="0 0 16 16">
                            <path d="M1 3a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1H1zm7 8a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"/>
                            <path d="M0 5a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H1a1 1 0 0 1-1-1V5zm3 0a2 2 0 0 1-2 2v4a2 2 0 0 1 2 2h10a2 2 0 0 1 2-2V7a2 2 0 0 1-2-2H3z"/>
                        </svg>
                    </div>
                </div>
              </div>
            </div>
          </div>
          <!-- Total Surat Jalan -->
          <div class="col-xl-4 col-md-6">
            <div class="card border-0 shadow-sm h-100">
              <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <p class="text-muted small mb-1">Total Surat Jalan <span class="text-warning">(<?= $filter === 'hari' ? "Hari Ini (" . date('d F Y') . ")" : "Bulan " . date('F Y'); ?>)</span></p>
                        <h3 class="fw-bold mb-0"><?= $total_surat_jalan; ?></h3>
                    </div>
                    <div class="flex-shrink-0 ms-auto">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-file-earmark-text text-warning" viewBox="0 0 16 16">
                            <path d="M5.5 7a.5.5 0 0 0 0 1h5a.5.5 0 0 0 0-1h-5zM5 9.5a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5zm0 2a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 0 1h-2a.5.5 0 0 1-.5-.5z"/>
                            <path d="M9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V4.5L9.5 0zm0 1v2A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5z"/>
                        </svg>
                    </div>
                </div>
              </div>
            </div>
          </div>
        </div>
                <!-- TABEL PENDAPATAN -->
        <div class="card border-0 shadow-sm mb-4">
          <div class="card-header bg-white fw-bold">Pendapatan per Admin</div>
          <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
              <thead class="table-light">
                <tr>
                  <th>ID</th>
                  <th>Username</th>
                  <th class="text-end">Cash</th>
                  <th class="text-end">Transfer</th>
                  <th class="text-end">Bayar di Tempat</th>
                  <th class="text-end">Total</th>
                </tr>
              </thead>
              <tbody>
                <?php if($pendapatan_admin->num_rows > 0): ?>
                  <?php while($row = $pendapatan_admin->fetch_assoc()): ?>
                    <tr>
                      <td><?= $row['id']; ?></td>
                      <td><?= htmlspecialchars($row['username']); ?></td>
                      <td class="text-end">Rp <?= number_format($row['cash'], 0, ',', '.'); ?></td>
                      <td class="text-end">Rp <?= number_format($row['transfer'], 0, ',', '.'); ?></td>
                      <td class="text-end">Rp <?= number_format($row['cod'], 0, ',', '.'); ?></td>
                      <td class="text-end"><strong>Rp <?= number_format($row['total'], 0, ',', '.'); ?></strong></td>
                    </tr>
                  <?php endwhile; ?>
                <?php else: ?>
                  <tr><td colspan="6" class="text-muted py-3 text-center">Belum ada data <?= $filter === 'hari' ? 'hari ini' : 'bulan ini'; ?>.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- TABEL PENGIRIMAN -->
        <div class="card border-0 shadow-sm mb-4">
          <div class="card-header bg-white fw-bold">Pengiriman per Admin</div>
          <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
              <thead class="table-light">
                <tr>
                  <th>ID</th>
                  <th>Username</th>
                  <th class="text-center">BKD</th>
                  <th class="text-center">Dalam Perjalanan</th>
                  <th class="text-center">Sampai Tujuan</th>
                  <th class="text-center">POD</th>
                  <th class="text-center">Dibatalkan</th>
                  <th class="text-center">Total</th>
                </tr>
              </thead>
              <tbody>
                <?php if($pengiriman_admin->num_rows > 0): ?>
                  <?php while($row = $pengiriman_admin->fetch_assoc()): ?>
                    <tr>
                      <td><?= $row['id']; ?></td>
                      <td><?= htmlspecialchars($row['username']); ?></td>
                      <td class="text-center"><?= $row['bkd']; ?></td>
                      <td class="text-center"><?= $row['perjalanan']; ?></td>
                      <td class="text-center"><?= $row['sampai']; ?></td>
                      <td class="text-center"><?= $row['pod']; ?></td>
                      <td class="text-center"><?= $row['batal']; ?></td>
                      <td class="text-center"><strong><?= $row['total']; ?></strong></td>
                    </tr>
                  <?php endwhile; ?>
                <?php else: ?>
                  <tr><td colspan="8" class="text-muted py-3 text-center">Belum ada data <?= $filter === 'hari' ? 'hari ini' : 'bulan ini'; ?>.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- TABEL SURAT JALAN -->
        <div class="card border-0 shadow-sm mb-5">
          <div class="card-header bg-white fw-bold">Surat Jalan per Admin</div>
          <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
              <thead class="table-light">
                <tr>
                  <th>ID</th>
                  <th>Username</th>
                  <th class="text-center">Dalam Perjalanan</th>
                  <th class="text-center">Sampai Tujuan</th>
                  <th class="text-center">Dibatalkan</th>
                  <th class="text-center">Total</th>
                </tr>
              </thead>
              <tbody>
                <?php if($surat_jalan_admin->num_rows > 0): ?>
                  <?php while($row = $surat_jalan_admin->fetch_assoc()): ?>
                    <tr>
                      <td><?= $row['id']; ?></td>
                      <td><?= htmlspecialchars($row['username']); ?></td>
                      <td class="text-center"><?= $row['perjalanan']; ?></td>
                      <td class="text-center"><?= $row['sampai']; ?></td>
                      <td class="text-center"><?= $row['batal']; ?></td>
                      <td class="text-center"><strong><?= $row['total']; ?></strong></td>
                    </tr>
                  <?php endwhile; ?>
                <?php else: ?>
                  <tr><td colspan="6" class="text-muted py-3 text-center">Belum ada data <?= $filter === 'hari' ? 'hari ini' : 'bulan ini'; ?>.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

      </div>
    </main>
  </div>
</div>

<?php include '../../templates/footer.php'; ?>
