<?php
session_start();
require_once 'database.php';

// Simple authentication check
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';
$action = $_GET['action'] ?? '';
$entry_id = $_GET['id'] ?? '';

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_entry'])) {
        // Add new guest entry
        $visitor_name = trim($_POST['nama']);
        $ktp_number = trim($_POST['noktp']);
        $institution = trim($_POST['instansi']);
        $job = trim($_POST['pekerjaan']);
        $required_info = trim($_POST['informasi']);
        $legal_product_purpose = trim($_POST['tujuan']);
        
        if (empty($visitor_name) || empty($ktp_number) || empty($institution) || 
            empty($job) || empty($required_info) || empty($legal_product_purpose)) {
            $error = "Semua field harus diisi!";
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO guest_entries (visitor_name, ktp_number, institution, job, required_info, legal_product_purpose) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$visitor_name, $ktp_number, $institution, $job, $required_info, $legal_product_purpose]);
                $success = "Data buku tamu berhasil ditambahkan!";
            } catch (PDOException $e) {
                $error = "Terjadi kesalahan: " . $e->getMessage();
            }
        }
    } elseif (isset($_POST['update_entry'])) {
        // Update existing entry
        $id = $_POST['id'];
        $visitor_name = trim($_POST['nama']);
        $ktp_number = trim($_POST['noktp']);
        $institution = trim($_POST['instansi']);
        $job = trim($_POST['pekerjaan']);
        $required_info = trim($_POST['informasi']);
        $legal_product_purpose = trim($_POST['tujuan']);
        
        try {
            $stmt = $pdo->prepare("
                UPDATE guest_entries 
                SET visitor_name = ?, ktp_number = ?, institution = ?, job = ?, required_info = ?, legal_product_purpose = ? 
                WHERE id = ?
            ");
            $stmt->execute([$visitor_name, $ktp_number, $institution, $job, $required_info, $legal_product_purpose, $id]);
            $success = "Data berhasil diperbarui!";
        } catch (PDOException $e) {
            $error = "Terjadi kesalahan: " . $e->getMessage();
        }
    }
}

// Handle delete operation
if ($action === 'delete' && $entry_id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM guest_entries WHERE id = ?");
        $stmt->execute([$entry_id]);
        $success = "Data berhasil dihapus!";
    } catch (PDOException $e) {
        $error = "Terjadi kesalahan: " . $e->getMessage();
    }
}

// Get statistics
try {
    // Today's visitors
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM guest_entries WHERE DATE(created_at) = ?");
    $stmt->execute([$today]);
    $today_count = $stmt->fetchColumn();

    // Last 7 days statistics
    $last_7_days = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM guest_entries WHERE DATE(created_at) = ?");
        $stmt->execute([$date]);
        $count = $stmt->fetchColumn();
        $last_7_days[] = [
            'date' => $date,
            'day' => date('l', strtotime($date)),
            'short_date' => date('d/m', strtotime($date)),
            'count' => $count
        ];
    }

    // Recent entries
    $limit = isset($_GET['show_all']) ? 100 : 5;
    $stmt = $pdo->prepare("SELECT * FROM guest_entries ORDER BY created_at DESC LIMIT ?");
    $stmt->execute([$limit]);
    $recent_entries = $stmt->fetchAll();

    // Total entries
    $stmt = $pdo->query("SELECT COUNT(*) FROM guest_entries");
    $total_entries = $stmt->fetchColumn();

} catch (PDOException $e) {
    $error = "Error fetching statistics: " . $e->getMessage();
}

// Get entry for editing
$edit_entry = null;
if ($action === 'edit' && $entry_id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM guest_entries WHERE id = ?");
        $stmt->execute([$entry_id]);
        $edit_entry = $stmt->fetch();
    } catch (PDOException $e) {
        $error = "Error fetching entry: " . $e->getMessage();
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>SI-TAMU - Admin Dashboard</title>

    <link href="asset/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link href="assets/css/sb-admin-2.min.css" rel="stylesheet">

    <style>
        /* Background gradasi animasi */
        body {
            background: linear-gradient(-45deg, #1a73e8, #0f9d58, #fbbc05, #ea4335);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
        }

        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Animasi form muncul */
        .card {
            animation: fadeInUp 1s ease;
        }

        @keyframes fadeInUp {
            0% { opacity: 0; transform: translateY(20px); }
            100% { opacity: 1; transform: translateY(0); }
        }

        /* Efek glow berkedip saat hover/focus */
        .form-control-user {
            transition: all 0.3s ease;
        }

        .form-control-user:hover,
        .form-control-user:focus {
            box-shadow: 0 0 15px rgba(255, 255, 255, 0.8);
            animation: blinkGlow 1s infinite alternate;
            border: 2px solid #fff;
        }

        @keyframes blinkGlow {
            from { box-shadow: 0 0 5px #fff; }
            to { box-shadow: 0 0 15px #ffeb3b; }
        }

        /* Tombol submit animasi hover */
        .btn-animated {
            background: linear-gradient(90deg, #1a73e8, #0f9d58);
            border: none;
            transition: all 0.4s ease;
            color: white;
            font-weight: bold;
        }

        .btn-animated:hover {
            background: linear-gradient(90deg, #0f9d58, #1a73e8);
            transform: scale(1.05);
            box-shadow: 0 0 15px rgba(255, 255, 255, 0.8);
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #1a73e8;
        }

        .entries-list {
            max-height: 400px;
            overflow-y: auto;
        }

        .entry-item {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 10px;
            border-left: 4px solid #1a73e8;
        }

        .entry-item:hover {
            background: rgba(255, 255, 255, 1);
            transform: translateX(5px);
            transition: all 0.3s ease;
        }

        .admin-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Admin Header -->
        <div class="admin-header mt-4 text-center">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <img src="assets/img/logo.png.png" width="80" alt="Logo">
                </div>
                <div class="col-md-4">
                    <h2 class="mb-0">SI-TAMU Admin</h2>
                    <p class="text-muted mb-0">PROVINSI BALI</p>
                </div>
                <div class="col-md-4">
                    <div class="text-right">
                        <span class="mr-3">Welcome, <?= htmlspecialchars($_SESSION['admin_nip']) ?></span>
                        <a href="?logout=1" class="btn btn-sm btn-outline-danger">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <!-- Left Column - Form -->
            <div class="col-lg-7 mb-3">
                <div class="card shadow bg-gradient-light">
                    <div class="card-body">
                        <div class="p-4">
                            <div class="text-center">
                                <h1 class="h4 text-gray-900 mb-4">
                                    <?= $edit_entry ? 'Edit Data Buku Tamu' : 'Tambah Data Buku Tamu' ?>
                                </h1>
                            </div>
                            
                            <?php if ($error): ?>
                                <div class="alert alert-danger" role="alert">
                                    <i class="fas fa-exclamation-triangle mr-2"></i>
                                    <?= htmlspecialchars($error) ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($success): ?>
                                <div class="alert alert-success" role="alert">
                                    <i class="fas fa-check-circle mr-2"></i>
                                    <?= htmlspecialchars($success) ?>
                                </div>
                            <?php endif; ?>
                            
                            <form class="user" action="" method="POST">
                                <?php if ($edit_entry): ?>
                                    <input type="hidden" name="id" value="<?= $edit_entry['id'] ?>">
                                <?php endif; ?>
                                
                                <div class="form-group">
                                    <input type="text" class="form-control form-control-user" name="nama" 
                                           placeholder="Nama Pengunjung" 
                                           value="<?= $edit_entry ? htmlspecialchars($edit_entry['visitor_name']) : '' ?>" required>
                                </div>
                                <div class="form-group">
                                    <input type="text" class="form-control form-control-user" name="noktp" 
                                           placeholder="Nomor KTP" 
                                           value="<?= $edit_entry ? htmlspecialchars($edit_entry['ktp_number']) : '' ?>" required>
                                </div>
                                <div class="form-group">
                                    <input type="text" class="form-control form-control-user" name="instansi" 
                                           placeholder="Instansi" 
                                           value="<?= $edit_entry ? htmlspecialchars($edit_entry['institution']) : '' ?>" required>
                                </div>
                                <div class="form-group">
                                    <input type="text" class="form-control form-control-user" name="pekerjaan" 
                                           placeholder="Pekerjaan" 
                                           value="<?= $edit_entry ? htmlspecialchars($edit_entry['job']) : '' ?>" required>
                                </div>
                                <div class="form-group">
                                    <textarea class="form-control form-control-user" name="informasi" 
                                              placeholder="Informasi yang Dibutuhkan" rows="3" required><?= $edit_entry ? htmlspecialchars($edit_entry['required_info']) : '' ?></textarea>
                                </div>
                                <div class="form-group">
                                    <textarea class="form-control form-control-user" name="tujuan" 
                                              placeholder="Tujuan Memperoleh Informasi Produk Hukum" rows="3" required><?= $edit_entry ? htmlspecialchars($edit_entry['legal_product_purpose']) : '' ?></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <button type="submit" name="<?= $edit_entry ? 'update_entry' : 'add_entry' ?>" 
                                            class="btn btn-animated btn-user btn-block">
                                        <i class="fas fa-<?= $edit_entry ? 'edit' : 'plus' ?> mr-2"></i>
                                        <?= $edit_entry ? 'Update Data' : 'Simpan Data' ?>
                                    </button>
                                    
                                    <?php if ($edit_entry): ?>
                                        <a href="admin.php" class="btn btn-secondary btn-user btn-block mt-2">
                                            <i class="fas fa-times mr-2"></i>Batal Edit
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </form>

                            <div class="text-center mt-3">
                                <a href="user.php" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye mr-1"></i>Lihat Halaman User
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column - Statistics and Data -->
            <div class="col-lg-5 mb-3">
                <!-- Today's Statistics -->
                <div class="stat-card text-center">
                    <div class="stat-number"><?= $today_count ?? 0 ?></div>
                    <div class="text-muted">Pengunjung Hari Ini</div>
                    <small class="text-muted"><?= date('d F Y') ?></small>
                </div>

                <!-- 7 Days Statistics -->
                <div class="stat-card">
                    <h6 class="font-weight-bold mb-3">
                        <i class="fas fa-chart-line mr-2"></i>7 Hari Terakhir
                    </h6>
                    <?php foreach ($last_7_days as $day): ?>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <strong><?= $day['short_date'] ?></strong>
                                <small class="text-muted ml-1"><?= $day['day'] ?></small>
                            </div>
                            <div class="badge badge-<?= $day['count'] > 0 ? 'primary' : 'secondary' ?>">
                                <?= $day['count'] ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Total Statistics -->
                <div class="stat-card text-center">
                    <div class="stat-number"><?= $total_entries ?? 0 ?></div>
                    <div class="text-muted">Total Pengunjung</div>
                </div>

                <!-- Recent Entries -->
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="font-weight-bold mb-0">
                            <i class="fas fa-list mr-2"></i>Data Terbaru
                        </h6>
                        <?php if (!isset($_GET['show_all'])): ?>
                            <a href="?show_all=1" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-eye mr-1"></i>Lihat Semua
                            </a>
                        <?php else: ?>
                            <a href="admin.php" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-eye-slash mr-1"></i>Sembunyikan
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="entries-list">
                        <?php if (empty($recent_entries)): ?>
                            <div class="entry-item text-center text-muted">
                                <i class="fas fa-inbox fa-2x mb-2"></i>
                                <p>Belum ada data pengunjung</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recent_entries as $entry): ?>
                                <div class="entry-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <strong><?= htmlspecialchars($entry['visitor_name']) ?></strong>
                                            <div class="small text-muted">
                                                <i class="fas fa-building mr-1"></i><?= htmlspecialchars($entry['institution']) ?>
                                            </div>
                                            <div class="small text-muted">
                                                <i class="fas fa-clock mr-1"></i><?= date('d/m/Y H:i', strtotime($entry['created_at'])) ?>
                                            </div>
                                        </div>
                                        <div class="btn-group-vertical btn-group-sm">
                                            <a href="?action=edit&id=<?= $entry['id'] ?>" 
                                               class="btn btn-outline-primary btn-sm" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="?action=delete&id=<?= $entry['id'] ?>" 
                                               class="btn btn-outline-danger btn-sm" title="Delete"
                                               onclick="return confirm('Yakin ingin menghapus data ini?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="text-center text-white mt-4 mb-3">
            <small>By.JDIH Prov Bali | 2025 - <?= date('Y') ?></small>
        </div>
    </div>

    <!-- Bootstrap core JavaScript-->
    <script src="assets/vendor/jquery/jquery.min.js"></script>
    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="assets/js/sb-admin-2.min.js"></script>

</body>
</html>