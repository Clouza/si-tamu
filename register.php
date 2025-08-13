<?php
session_start();
require_once 'database.php';

$error = '';
$success = '';
$generated_password = '';

function generatePassword($length = 8)
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nip = trim($_POST['nip']);

    if (empty($nip)) {
        $error = 'NIP harus diisi!';
    } else {
        try {
            // Check if NIP already exists
            $check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE nip = ?");
            $check->execute([$nip]);

            if ($check->fetchColumn() > 0) {
                $error = 'NIP sudah terdaftar!';
            } else {
                // Generate password
                $generated_password = generatePassword();
                $hashed_password = password_hash($generated_password, PASSWORD_DEFAULT);

                // Insert new user
                $stmt = $pdo->prepare("INSERT INTO users (nip, password, role) VALUES (?, ?, ?)");
                $stmt->execute([$nip, $hashed_password, 'admin']);

                $success = 'Akun berhasil dibuat!';
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
    <title>SI-TAMU - Daftar Admin</title>

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

        .copy-button {
            transition: all 0.2s ease;
        }

        .copy-button:hover {
            transform: scale(1.1);
        }

        .copy-success {
            animation: copySuccess 0.3s ease;
        }

        @keyframes copySuccess {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.2);
            }

            100% {
                transform: scale(1);
            }
        }
    </style>
</head>

<body class="font-nunito">
    <div class="container mx-auto px-4">
        <!-- Header -->
        <div class="text-center mt-8 text-white">
            <img src="https://via.placeholder.com/100x100/1a73e8/ffffff?text=LOGO" width="100" alt="Logo" class="mx-auto animate-pulse">
            <h2 class="mt-4 text-2xl font-bold">SI-TAMU <br> PROVINSI BALI</h2>
        </div>

        <div class="flex justify-center mt-8">
            <!-- Register Form -->
            <div class="w-full max-w-md">
                <div class="card bg-white bg-opacity-95 backdrop-blur-sm rounded-lg shadow-2xl">
                    <div class="p-8">
                        <div class="text-center mb-6">
                            <h1 class="text-2xl font-bold text-gray-800">Daftar Admin</h1>
                            <p class="text-gray-600 mt-2">Buat akun admin baru</p>
                        </div>

                        <?php if ($error): ?>
                            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4" role="alert">
                                <div class="flex">
                                    <i class="fas fa-exclamation-triangle mr-2 mt-1"></i>
                                    <span><?= htmlspecialchars($error) ?></span>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($success && $generated_password): ?>
                            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4" role="alert">
                                <div class="flex items-start">
                                    <i class="fas fa-check-circle mr-2 mt-1"></i>
                                    <div class="flex-1">
                                        <p class="font-semibold"><?= htmlspecialchars($success) ?></p>
                                        <div class="mt-3">
                                            <p class="text-sm font-medium mb-2">Password yang dibuat:</p>
                                            <div class="flex items-center space-x-2">
                                                <input type="text"
                                                    id="generatedPassword"
                                                    value="<?= htmlspecialchars($generated_password) ?>"
                                                    readonly
                                                    class="flex-1 px-3 py-2 bg-gray-100 border border-gray-300 rounded text-sm font-mono">
                                                <button type="button"
                                                    onclick="copyPassword()"
                                                    class="copy-button px-3 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-400">
                                                    <i id="copyIcon" class="fas fa-copy"></i>
                                                </button>
                                            </div>
                                            <p class="text-xs text-gray-600 mt-2">
                                                <i class="fas fa-info-circle mr-1"></i>
                                                Simpan password ini dengan aman. Password tidak dapat dilihat lagi setelah halaman ini ditutup.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!$success): ?>
                            <form method="POST" class="space-y-6">
                                <div>
                                    <label for="nip" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-id-card mr-2"></i>NIP
                                    </label>
                                    <input type="text"
                                        id="nip"
                                        name="nip"
                                        class="input-glow w-full px-4 py-3 border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-blue-500 bg-gray-50"
                                        placeholder="Masukkan NIP Baru"
                                        value="<?= isset($_POST['nip']) ? htmlspecialchars($_POST['nip']) : '' ?>"
                                        required>
                                    <p class="text-xs text-gray-500 mt-1">
                                        <i class="fas fa-lightbulb mr-1"></i>
                                        Password akan dibuat otomatis setelah pendaftaran
                                    </p>
                                </div>

                                <button type="submit"
                                    class="btn-animated w-full text-white font-bold py-3 px-4 rounded-full transition duration-300">
                                    <i class="fas fa-user-plus mr-2"></i>Daftar Akun
                                </button>
                            </form>
                        <?php endif; ?>

                        <div class="mt-6 text-center space-y-2">
                            <?php if ($success): ?>
                                <a href="login.php" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                    <i class="fas fa-sign-in-alt mr-1"></i>Login Sekarang
                                </a>
                                <div class="text-gray-400">|</div>
                            <?php else: ?>
                                <a href="login.php" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                    <i class="fas fa-sign-in-alt mr-1"></i>Sudah Punya Akun? Login
                                </a>
                                <div class="text-gray-400">|</div>
                            <?php endif; ?>
                            <a href="index.php" class="text-green-600 hover:text-green-800 text-sm font-medium">
                                <i class="fas fa-book mr-1"></i>Kembali ke Buku Tamu
                            </a>
                        </div>

                        <div class="mt-8 text-center">
                            <p class="text-xs text-gray-500">
                                By.JDIH Prov Bali | 2025 - <?= date('Y') ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function copyPassword() {
            const passwordInput = document.getElementById('generatedPassword');
            const copyIcon = document.getElementById('copyIcon');

            // Select and copy the text
            passwordInput.select();
            passwordInput.setSelectionRange(0, 99999); // For mobile devices

            try {
                navigator.clipboard.writeText(passwordInput.value).then(function() {
                    // Success animation
                    copyIcon.className = 'fas fa-check copy-success';
                    copyIcon.parentElement.classList.add('bg-green-500');
                    copyIcon.parentElement.classList.remove('bg-blue-500');

                    // Reset after 2 seconds
                    setTimeout(function() {
                        copyIcon.className = 'fas fa-copy';
                        copyIcon.parentElement.classList.add('bg-blue-500');
                        copyIcon.parentElement.classList.remove('bg-green-500');
                    }, 2000);
                }).catch(function() {
                    // Fallback for older browsers
                    document.execCommand('copy');
                    showCopySuccess();
                });
            } catch (err) {
                // Fallback method
                document.execCommand('copy');
                showCopySuccess();
            }
        }

        function showCopySuccess() {
            const copyIcon = document.getElementById('copyIcon');
            copyIcon.className = 'fas fa-check copy-success';
            copyIcon.parentElement.classList.add('bg-green-500');
            copyIcon.parentElement.classList.remove('bg-blue-500');

            setTimeout(function() {
                copyIcon.className = 'fas fa-copy';
                copyIcon.parentElement.classList.add('bg-blue-500');
                copyIcon.parentElement.classList.remove('bg-green-500');
            }, 2000);
        }
    </script>
</body>

</html>