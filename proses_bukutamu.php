<?php
require_once 'database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $visitor_name = trim($_POST['nama']);
    $ktp_number = trim($_POST['noktp']);
    $institution = trim($_POST['instansi']);
    $job = trim($_POST['pekerjaan']);
    $required_info = trim($_POST['informasi']);
    $legal_product_purpose = trim($_POST['tujuan']);
    
    // Validation
    if (empty($visitor_name) || empty($ktp_number) || empty($institution) || 
        empty($job) || empty($required_info) || empty($legal_product_purpose)) {
        $error = "Semua field harus diisi!";
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
            
            $success = "Data buku tamu berhasil disimpan!";
        } catch (PDOException $e) {
            $error = "Terjadi kesalahan: " . $e->getMessage();
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
    <title>SI-TAMU - Status</title>
    <link href="assets/css/sb-admin-2.min.css" rel="stylesheet">
    <style>
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
        
        .card {
            animation: fadeInUp 1s ease;
        }
        
        @keyframes fadeInUp {
            0% { opacity: 0; transform: translateY(20px); }
            100% { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="head text-center mt-4 text-white">
            <img src="assets/img/logo.png.png" width="100" alt="Logo">
            <h2 class="mt-2">SI-TAMU <br> PROVINSI BALI</h2>
        </div>

        <div class="row justify-content-center mt-4">
            <div class="col-lg-8">
                <div class="card shadow">
                    <div class="card-body text-center p-5">
                        <?php if (isset($success)): ?>
                            <div class="alert alert-success" role="alert">
                                <i class="fas fa-check-circle fa-3x mb-3 text-success"></i>
                                <h4 class="alert-heading">Berhasil!</h4>
                                <p><?= htmlspecialchars($success) ?></p>
                            </div>
                        <?php elseif (isset($error)): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="fas fa-times-circle fa-3x mb-3 text-danger"></i>
                                <h4 class="alert-heading">Error!</h4>
                                <p><?= htmlspecialchars($error) ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <a href="user.php" class="btn btn-primary">
                            <i class="fas fa-arrow-left"></i> Kembali ke Form
                        </a>
                        <a href="admin.php" class="btn btn-secondary ml-2">
                            <i class="fas fa-list"></i> Lihat Data (Admin)
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>