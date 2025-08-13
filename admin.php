<?php
session_start();
require_once 'database.php';

// Authentication and role check
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
$success = '';
$action = $_GET['action'] ?? '';
$entry_id = $_GET['id'] ?? '';

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF token validation
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token!';
    } else {
        if (isset($_POST['add_entry'])) {
            // Add new guest entry
            $visitor_name = trim($_POST['nama']);
            $ktp_number = trim($_POST['noktp']);
            $institution = trim($_POST['instansi']);
            $job = trim($_POST['pekerjaan']);
            $required_info = trim($_POST['informasi']);
            $legal_product_purpose = trim($_POST['tujuan']);

            if (
                empty($visitor_name) || empty($ktp_number) || empty($institution) ||
                empty($job) || empty($required_info) || empty($legal_product_purpose)
            ) {
                $error = "Semua field harus diisi!";
            } elseif (strlen($ktp_number) < 10) {
                $error = "Nomor KTP tidak valid!";
            } else {
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO guest_entries (visitor_name, ktp_number, institution, job, required_info, legal_product_purpose) 
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$visitor_name, $ktp_number, $institution, $job, $required_info, $legal_product_purpose]);
                    $success = "Data buku tamu berhasil ditambahkan!";
                } catch (PDOException $e) {
                    if (strpos($e->getMessage(), 'UNIQUE constraint failed') !== false) {
                        $error = "Data dengan KTP tersebut sudah terdaftar!";
                    } else {
                        $error = "Terjadi kesalahan sistem!";
                    }
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

    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <style>
        /* Background gradasi animasi */
        body {
            background: linear-gradient(-45deg, #1a73e8, #0f9d58, #fbbc05, #ea4335);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
        }

        @keyframes gradientBG {
            0% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }

            100% {
                background-position: 0% 50%;
            }
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

        /* Efek glow berkedip saat hover/focus */
        .input-glow {
            transition: all 0.3s ease;
        }

        .input-glow:hover,
        .input-glow:focus {
            box-shadow: 0 0 15px rgba(255, 255, 255, 0.8);
            animation: blinkGlow 1s infinite alternate;
            border: 2px solid #fff;
        }

        @keyframes blinkGlow {
            from {
                box-shadow: 0 0 5px #fff;
            }

            to {
                box-shadow: 0 0 15px #ffeb3b;
            }
        }

        /* Tombol submit animasi hover */
        .btn-animated {
            background: linear-gradient(90deg, #1a73e8, #0f9d58);
            transition: all 0.4s ease;
        }

        .btn-animated:hover {
            background: linear-gradient(90deg, #0f9d58, #1a73e8);
            transform: scale(1.05);
            box-shadow: 0 0 15px rgba(255, 255, 255, 0.8);
        }

        .stat-card {
            backdrop-filter: blur(10px);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
        }

        .entry-item {
            transition: all 0.3s ease;
        }

        .entry-item:hover {
            transform: translateX(2px);
        }
    </style>
</head>

<body class="font-nunito">
    <div class="container mx-auto px-4">
        <!-- Admin Header -->
        <div class="card bg-white bg-opacity-95 backdrop-blur-sm rounded-lg shadow-lg mt-8 mb-6">
            <div class="p-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <img src="https://via.placeholder.com/60x60/1a73e8/ffffff?text=LOGO" width="60" alt="Logo" class="animate-pulse">
                        <div>
                            <h2 class="text-xl font-bold text-gray-800">SI-TAMU Admin</h2>
                            <p class="text-sm text-gray-600">PROVINSI BALI</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="text-sm text-gray-700">Welcome, <strong><?= htmlspecialchars($_SESSION['user_nip']) ?></strong></span>
                        <a href="index.php" class="px-3 py-1 bg-blue-500 text-white rounded-full text-sm hover:bg-blue-600 transition">
                            <i class="fas fa-eye mr-1"></i>User View
                        </a>
                        <a href="?logout=1" class="px-3 py-1 bg-red-500 text-white rounded-full text-sm hover:bg-red-600 transition">
                            <i class="fas fa-sign-out-alt mr-1"></i>Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Left Column - Form -->
            <div class="lg:col-span-7">
                <div class="card bg-white bg-opacity-95 backdrop-blur-sm rounded-lg shadow-lg">
                    <div class="p-6">
                        <div class="text-center mb-6">
                            <h1 class="text-2xl font-bold text-gray-800 mb-2">
                                <?= $edit_entry ? 'Edit Data Buku Tamu' : 'Tambah Data Buku Tamu' ?>
                            </h1>
                        </div>

                        <?php if ($error): ?>
                            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4" role="alert">
                                <div class="flex">
                                    <i class="fas fa-exclamation-triangle mr-2 mt-1"></i>
                                    <span><?= htmlspecialchars($error) ?></span>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4" role="alert">
                                <div class="flex">
                                    <i class="fas fa-check-circle mr-2 mt-1"></i>
                                    <span><?= htmlspecialchars($success) ?></span>
                                </div>
                            </div>
                        <?php endif; ?>

                        <form action="" method="POST" class="space-y-4">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <?php if ($edit_entry): ?>
                                <input type="hidden" name="id" value="<?= $edit_entry['id'] ?>">
                            <?php endif; ?>

                            <div>
                                <input type="text" name="nama"
                                    class="input-glow w-full px-4 py-3 border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-blue-500 bg-gray-50"
                                    placeholder="Nama Pengunjung"
                                    value="<?= $edit_entry ? htmlspecialchars($edit_entry['visitor_name']) : '' ?>" required>
                            </div>
                            <div>
                                <input type="text" name="noktp"
                                    class="input-glow w-full px-4 py-3 border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-blue-500 bg-gray-50"
                                    placeholder="Nomor KTP"
                                    value="<?= $edit_entry ? htmlspecialchars($edit_entry['ktp_number']) : '' ?>" required>
                            </div>
                            <div>
                                <input type="text" name="instansi"
                                    class="input-glow w-full px-4 py-3 border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-blue-500 bg-gray-50"
                                    placeholder="Instansi"
                                    value="<?= $edit_entry ? htmlspecialchars($edit_entry['institution']) : '' ?>" required>
                            </div>
                            <div>
                                <input type="text" name="pekerjaan"
                                    class="input-glow w-full px-4 py-3 border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-blue-500 bg-gray-50"
                                    placeholder="Pekerjaan"
                                    value="<?= $edit_entry ? htmlspecialchars($edit_entry['job']) : '' ?>" required>
                            </div>
                            <div>
                                <textarea name="informasi" rows="3"
                                    class="input-glow w-full px-4 py-3 border border-gray-300 rounded-2xl focus:outline-none focus:ring-2 focus:ring-blue-500 bg-gray-50"
                                    placeholder="Informasi yang Dibutuhkan" required><?= $edit_entry ? htmlspecialchars($edit_entry['required_info']) : '' ?></textarea>
                            </div>
                            <div>
                                <textarea name="tujuan" rows="3"
                                    class="input-glow w-full px-4 py-3 border border-gray-300 rounded-2xl focus:outline-none focus:ring-2 focus:ring-blue-500 bg-gray-50"
                                    placeholder="Tujuan Memperoleh Informasi Produk Hukum" required><?= $edit_entry ? htmlspecialchars($edit_entry['legal_product_purpose']) : '' ?></textarea>
                            </div>

                            <div class="space-y-2">
                                <button type="submit" name="<?= $edit_entry ? 'update_entry' : 'add_entry' ?>"
                                    class="btn-animated w-full text-white font-bold py-3 px-4 rounded-full transition duration-300">
                                    <i class="fas fa-<?= $edit_entry ? 'edit' : 'plus' ?> mr-2"></i>
                                    <?= $edit_entry ? 'Update Data' : 'Simpan Data' ?>
                                </button>

                                <?php if ($edit_entry): ?>
                                    <a href="admin.php" class="w-full bg-gray-500 hover:bg-gray-600 text-white font-bold py-3 px-4 rounded-full transition duration-300 text-center block">
                                        <i class="fas fa-times mr-2"></i>Batal Edit
                                    </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Right Column - Statistics -->
            <div class="lg:col-span-5 space-y-4">
                <!-- Today's Statistics -->
                <div class="stat-card bg-white bg-opacity-95 rounded-lg shadow-lg p-6 text-center">
                    <div class="text-4xl font-bold text-blue-600"><?= $today_count ?? 0 ?></div>
                    <div class="text-gray-600 font-medium">Pengunjung Hari Ini</div>
                    <div class="text-sm text-gray-500 mt-1"><?= date('d F Y') ?></div>
                </div>

                <!-- 7 Days Statistics -->
                <div class="stat-card bg-white bg-opacity-95 rounded-lg shadow-lg p-6">
                    <h6 class="font-bold mb-4 text-gray-800">
                        <i class="fas fa-chart-line mr-2 text-blue-600"></i>7 Hari Terakhir
                    </h6>
                    <div class="space-y-2">
                        <?php foreach ($last_7_days as $day): ?>
                            <div class="flex justify-between items-center">
                                <div>
                                    <span class="font-semibold"><?= $day['short_date'] ?></span>
                                    <span class="text-sm text-gray-500 ml-1"><?= substr($day['day'], 0, 3) ?></span>
                                </div>
                                <div class="px-2 py-1 rounded-full text-xs font-semibold <?= $day['count'] > 0 ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-600' ?>">
                                    <?= $day['count'] ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Total Statistics -->
                <div class="stat-card bg-white bg-opacity-95 rounded-lg shadow-lg p-6 text-center">
                    <div class="text-3xl font-bold text-green-600"><?= $total_entries ?? 0 ?></div>
                    <div class="text-gray-600 font-medium">Total Pengunjung</div>
                </div>

                <!-- Recent Entries -->
                <div class="stat-card bg-white bg-opacity-95 rounded-lg shadow-lg p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h6 class="font-bold text-gray-800">
                            <i class="fas fa-list mr-2 text-blue-600"></i>Data Terbaru
                        </h6>
                        <?php if (!isset($_GET['show_all'])): ?>
                            <a href="?show_all=1" class="px-3 py-1 bg-blue-500 text-white rounded-full text-sm hover:bg-blue-600 transition">
                                <i class="fas fa-eye mr-1"></i>Semua
                            </a>
                        <?php else: ?>
                            <a href="admin.php" class="px-3 py-1 bg-gray-500 text-white rounded-full text-sm hover:bg-gray-600 transition">
                                <i class="fas fa-eye-slash mr-1"></i>Tutup
                            </a>
                        <?php endif; ?>
                    </div>

                    <div class="max-h-80 overflow-y-auto space-y-3">
                        <?php if (empty($recent_entries)): ?>
                            <div class="text-center text-gray-500 py-8">
                                <i class="fas fa-inbox fa-2x mb-2"></i>
                                <p>Belum ada data pengunjung</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recent_entries as $entry): ?>
                                <div class="entry-item bg-gray-50 rounded-lg p-3 border-l-4 border-blue-500">
                                    <div class="flex justify-between items-start">
                                        <div class="flex-1">
                                            <div class="font-semibold text-gray-800"><?= htmlspecialchars($entry['visitor_name']) ?></div>
                                            <div class="text-sm text-gray-600">
                                                <i class="fas fa-building mr-1"></i><?= htmlspecialchars($entry['institution']) ?>
                                            </div>
                                            <div class="text-xs text-gray-500 mt-1">
                                                <i class="fas fa-clock mr-1"></i><?= date('d/m/Y H:i', strtotime($entry['created_at'])) ?>
                                            </div>
                                        </div>
                                        <div class="flex space-x-1 ml-2">
                                            <a href="?action=edit&id=<?= $entry['id'] ?>"
                                                class="p-1 bg-blue-500 text-white rounded hover:bg-blue-600 transition" title="Edit">
                                                <i class="fas fa-edit text-xs"></i>
                                            </a>
                                            <a href="?action=delete&id=<?= $entry['id'] ?>"
                                                class="p-1 bg-red-500 text-white rounded hover:bg-red-600 transition" title="Delete"
                                                onclick="return confirm('Yakin ingin menghapus data ini?')">
                                                <i class="fas fa-trash text-xs"></i>
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
        <div class="text-center text-white mt-8 mb-4">
            <small>By.JDIH Prov Bali | 2025 - <?= date('Y') ?></small>
        </div>
    </div>
</body>

</html>