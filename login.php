<?php
session_start();
require_once 'database.php';

// Redirect if already logged in based on role
if (isset($_SESSION['user_logged_in'])) {
    if ($_SESSION['user_role'] === 'admin') {
        header('Location: admin.php');
    } else {
        header('Location: index.php');
    }
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nip = trim($_POST['nip']);
    $password = trim($_POST['password']);

    if (empty($nip) || empty($password)) {
        $error = 'NIP dan password harus diisi!';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE nip = ?");
            $stmt->execute([$nip]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_logged_in'] = true;
                $_SESSION['user_nip'] = $user['nip'];
                $_SESSION['user_role'] = $user['role'];

                // Role-based redirect
                if ($user['role'] === 'admin') {
                    header('Location: admin.php');
                } else {
                    header('Location: index.php?logged_in=1');
                }
                exit;
            } else {
                $error = 'NIP atau password salah!';
            }
        } catch (PDOException $e) {
            $error = 'Terjadi kesalahan sistem!';
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
    <title>SI-TAMU - Login Admin</title>

    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

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

        /* Efek glow berkedip saat hover/focus */
        .input-glow {
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .input-glow:hover,
        .input-glow:focus {
            border: 2px solid #dc2626;
        }

        .btn-animated {
            background: #dc2626;
            transition: all 0.3s ease;
        }

        .btn-animated:hover {
            background: #b91c1c;
        }
    </style>
</head>

<body class="font-nunito">
    <div class="container mx-auto px-4">
        <!-- Header -->
        <div class="text-center mt-8 text-white">
            <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                <img src="biro-hukum-logo.png" width="100" alt="Logo">
                <img src="jdih-bali.png" width="100" alt="Logo">
            </div>
            <h2 class="mt-4 text-2xl font-bold">SI-TAMU <br> PROVINSI BALI</h2>
        </div>

        <div class="flex justify-center mt-8">
            <!-- Login Form -->
            <div class="w-full max-w-md">
                <div class="card bg-white bg-opacity-95 backdrop-blur-sm rounded-lg shadow-2xl">
                    <div class="p-8">
                        <div class="text-center mb-6">
                            <h1 class="text-2xl font-bold text-gray-800">Login Admin</h1>
                            <p class="text-gray-600 mt-2">Masuk untuk mengakses dashboard</p>
                        </div>

                        <?php if ($error): ?>
                            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4" role="alert">
                                <div class="flex">
                                    <i class="fas fa-exclamation-triangle mr-2 mt-1"></i>
                                    <span><?= htmlspecialchars($error) ?></span>
                                </div>
                            </div>
                        <?php endif; ?>

                        <form method="POST" class="space-y-6">
                            <div>
                                <label for="nip" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-id-card mr-2"></i>NIP
                                </label>
                                <input type="text"
                                    id="nip"
                                    name="nip"
                                    class="input-glow w-full px-4 py-3 bg-gray-50 rounded-full"
                                    placeholder="Masukkan NIP Anda"
                                    value="<?= isset($_POST['nip']) ? htmlspecialchars($_POST['nip']) : '' ?>"
                                    required>
                            </div>

                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-lock mr-2"></i>Password
                                </label>
                                <div class="relative">
                                    <input type="password"
                                        id="password"
                                        name="password"
                                        class="input-glow w-full px-4 py-3 bg-gray-50 rounded-full pr-12"
                                        placeholder="Masukkan Password"
                                        required>
                                    <button type="button"
                                        onclick="togglePassword()"
                                        class="absolute right-3 top-3 text-gray-500 hover:text-gray-700">
                                        <i id="passwordIcon" class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <button type="submit"
                                class="btn-animated w-full text-white font-bold py-3 px-4 rounded-full transition duration-300">
                                <i class="fas fa-sign-in-alt mr-2"></i>Masuk
                            </button>
                        </form>

                        <div class="mt-6 text-center space-y-2">
                            <a href="register.php" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                <i class="fas fa-user-plus mr-1"></i>Daftar Akun Baru
                            </a>
                            <div class="text-gray-400">|</div>
                            <a href="index.php" class="text-green-600 hover:text-green-800 text-sm font-medium">
                                <i class="fas fa-book mr-1"></i>Kembali ke Buku Tamu
                            </a>
                        </div>

                        <div class="mt-8 text-center">
                            <p class="text-xs text-gray-500">
                                By JDIH Prov Bali | 2025 - <?= date('Y') ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const passwordIcon = document.getElementById('passwordIcon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordIcon.className = 'fas fa-eye-slash';
            } else {
                passwordInput.type = 'password';
                passwordIcon.className = 'fas fa-eye';
            }
        }
    </script>
</body>

</html>