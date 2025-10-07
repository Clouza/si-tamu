<?php
session_start();
require_once 'database.php';

// Check if user is logged in and show appropriate greeting
$logged_in_user = null;
if (isset($_SESSION['user_logged_in'])) {
    $logged_in_user = $_SESSION['user_nip'];
}

// Show login success message
if (isset($_GET['logged_in']) && $_GET['logged_in'] == '1' && $logged_in_user) {
    $login_success = "Selamat datang, " . htmlspecialchars($logged_in_user) . "!";
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $visitor_name = trim($_POST['nama']);
    $ktp_number = trim($_POST['noktp']);
    $institution = trim($_POST['instansi']);
    $job = trim($_POST['pekerjaan']);
    $required_info = trim($_POST['informasi']);
    $legal_product_purpose = trim($_POST['tujuan']);

    // Validation
    if (
        empty($visitor_name) || empty($ktp_number) || empty($institution) ||
        empty($job) || empty($required_info) || empty($legal_product_purpose)
    ) {
        $error = "Semua field harus diisi!";
    } elseif (strlen($ktp_number) < 10) {
        $error = "Nomor KTP tidak valid!";
    } elseif (strlen($visitor_name) < 2) {
        $error = "Nama pengunjung minimal 2 karakter!";
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO guest_entries (visitor_name, ktp_number, institution, job, required_info, legal_product_purpose) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $visitor_name,
                $ktp_number,
                $institution,
                $job,
                $required_info,
                $legal_product_purpose
            ]);

            $success = "Data buku tamu berhasil disimpan! Terima kasih atas kunjungan Anda.";

            // Clear form data after successful submission
            $visitor_name = $ktp_number = $institution = $job = $required_info = $legal_product_purpose = '';
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'UNIQUE constraint failed') !== false) {
                $error = "Data dengan KTP tersebut sudah terdaftar!";
            } else {
                $error = "Terjadi kesalahan sistem. Silakan coba lagi.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>SI-TAMU - Buku Tamu</title>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/startbootstrap-sb-admin-2/4.1.4/css/sb-admin-2.min.css" rel="stylesheet">

    <style>
        body {
            background: #1e3a8a;
        }

        /* Animasi form muncul */
        .card {
            animation: fadeInUp 1s ease;
        }

        @keyframes fadeInUp {
            0% {
                opacity: 0;
                transform: translateY(20px);
            }

            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-control-user {
            transition: all 0.3s ease;
            outline: none;
            border: 2px solid transparent;
        }

        .form-control-user:hover,
        .form-control-user:focus {
            border: 2px solid #dc2626;
            outline: none;
        }

        .btn-animated {
            background: #dc2626;
            border: none;
            transition: all 0.3s ease;
            color: white;
            font-weight: bold;
        }

        .btn-animated:hover {
            background: #b91c1c;
        }
    </style>
</head>

<body>

    <div class="container">
        <!-- Head -->
        <div class="head text-center mt-4 text-white">
            <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                <img src="biro-hukum-logo.png" width="100" alt="Logo" style="background: white; border-radius: 100%; padding: 1rem;">
                <img src="jdih-bali.png" width="100" alt="Logo" style="background: white; border-radius: 100%; padding: 0.5rem;">
            </div>
            <h2 class="mt-2">SI-TAMU <br> PROVINSI BALI</h2>
            <?php if ($logged_in_user): ?>
                <div class="mt-3">
                    <span class="bg-opacity-20 px-4 py-2 rounded-full text-sm" style="background-color: black; border-radius: 6px;">
                        <i class="fas fa-user mr-2"></i><?= htmlspecialchars($logged_in_user) ?>
                        <a href="?logout=1" class="ml-2 text-red-200 hover:text-white">
                            <i class="fas fa-sign-out-alt"></i>
                        </a>
                    </span>
                </div>
            <?php endif; ?>
        </div>

        <div class="row mt-4">
            <!-- Form -->
            <div class="col-lg-7 mb-3">
                <div class="card shadow bg-gradient-light">
                    <div class="card-body">
                        <div class="p-4">
                            <div class="text-center">
                                <h1 class="h4 text-gray-900 mb-4">Form Buku Tamu</h1>
                            </div>

                            <?php if (isset($login_success)): ?>
                                <div class="alert alert-info" role="alert">
                                    <i class="fas fa-info-circle mr-2"></i>
                                    <?= htmlspecialchars($login_success) ?>
                                </div>
                            <?php endif; ?>

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
                                <div class="form-group">
                                    <input type="text" class="form-control form-control-user" name="nama" placeholder="Nama Pengunjung" value="<?= isset($visitor_name) ? htmlspecialchars($visitor_name) : '' ?>" required>
                                </div>
                                <div class="form-group">
                                    <input type="text" class="form-control form-control-user" name="noktp" placeholder="Nomor KTP" value="<?= isset($ktp_number) ? htmlspecialchars($ktp_number) : '' ?>" required>
                                </div>
                                <div class="form-group">
                                    <input type="text" class="form-control form-control-user" name="instansi" placeholder="Instansi" value="<?= isset($institution) ? htmlspecialchars($institution) : '' ?>" required>
                                </div>
                                <div class="form-group">
                                    <input type="text" class="form-control form-control-user" name="pekerjaan" placeholder="Pekerjaan" value="<?= isset($job) ? htmlspecialchars($job) : '' ?>" required>
                                </div>
                                <div class="form-group">
                                    <textarea class="form-control form-control-user" name="informasi" placeholder="Informasi yang Dibutuhkan" rows="3" required><?= isset($required_info) ? htmlspecialchars($required_info) : '' ?></textarea>
                                </div>
                                <div class="form-group">
                                    <textarea class="form-control form-control-user" name="tujuan" placeholder="Tujuan Memperoleh Informasi Produk Hukum" rows="3" required><?= isset($legal_product_purpose) ? htmlspecialchars($legal_product_purpose) : '' ?></textarea>
                                </div>
                                <button type="submit" class="btn btn-animated btn-user btn-block">
                                    Simpan Buku Tamu
                                </button>
                            </form>

                            <div class="text-center mt-2">
                                <a class="small" href="https://jdih.baliprov.go.id">By JDIH Prov Bali | 2025 - <?= date('Y') ?></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Info -->
            <div class="col-lg-5 mb-3">
                <div class="card shadow bg-gradient-light">
                    <div class="card-body">
                        <h5 class="text-center font-weight-bold">Informasi</h5>
                        <p class="mb-3 text-justify">
                            Selamat datang di layanan buku tamu Provinsi Bali. Silakan isi data dengan benar untuk keperluan administrasi.
                            Data Anda akan digunakan sesuai ketentuan yang berlaku.
                            Kami berkomitmen menjaga kerahasiaan informasi Anda.
                        </p>

                        <div class="text-center">
                            <div class="btn-group-vertical" role="group">
                                <?php if ($logged_in_user): ?>
                                    <?php if ($_SESSION['user_role'] === 'admin'): ?>
                                        <a href="admin.php" class="btn btn-outline-primary btn-sm mb-2">
                                            <i class="fas fa-tachometer-alt mr-1"></i>Dashboard Admin
                                        </a>
                                    <?php endif; ?>
                                    <a href="?logout=1" class="btn btn-outline-danger btn-sm">
                                        <i class="fas fa-sign-out-alt mr-1"></i>Logout
                                    </a>
                                <?php else: ?>
                                    <a href="admin.php" class="btn btn-outline-primary btn-sm mb-2">
                                        <i class="fas fa-list mr-1"></i>Lihat Data Buku Tamu
                                    </a>
                                    <a href="login.php" class="btn btn-outline-secondary btn-sm mb-2">
                                        <i class="fas fa-sign-in-alt mr-1"></i>Login
                                    </a>
                                    <a href="register.php" class="btn btn-outline-success btn-sm">
                                        <i class="fas fa-user-plus mr-1"></i>Daftar Admin
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap core JavaScript-->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.0/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-easing/1.4.1/jquery.easing.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/startbootstrap-sb-admin-2/4.1.4/js/sb-admin-2.min.js"></script>

</body>

</html>