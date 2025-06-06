<?php
// Start session jika belum dimulai
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// DEBUG: Show if we received POST data
if ($_POST) {
    echo "<div style='background: yellow; padding: 10px; margin: 10px;'>";
    echo "<strong>POST data received in index.php:</strong><br>";
    echo "<pre>" . print_r($_POST, true) . "</pre>";
    echo "</div>";
}

// Include auth functions
require_once 'auth_check.php';

// Get session data - gunakan username bukan name
$username = $_SESSION['username'] ?? null;
$alerts = $_SESSION['alerts'] ?? [];
$active_form = $_SESSION['active_form'] ?? '';

// Clear alerts setelah ditampilkan
unset($_SESSION['alerts']);
unset($_SESSION['active_form']);

// Redirect jika sudah login
if ($username !== null) {
    header('Location: dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login & Registration - To Do List</title>
    <link rel="stylesheet" href="style.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    
    <style>
        /* Alert Styles */
        .alert-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            max-width: 400px;
        }

        .alert {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            margin-bottom: 10px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            animation: slideIn 0.3s ease-out;
            font-weight: 500;
        }

        .alert.success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .alert.warning {
            background: #fff3cd;
            color: #856404;
            border-left: 4px solid #ffc107;
        }

        .alert i {
            margin-right: 10px;
            font-size: 18px;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideOut {
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }

        /* Password toggle */
        .password-toggle {
            position: absolute;
            right: 30px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #fff;
            cursor: pointer;
            padding: 5px;
            border-radius: 3px;
            transition: color 0.3s ease;
        }

        .password-toggle:hover {
            color: #007bff;
        }

        /* Loading spinner */
        .spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 0.8s linear infinite;
            margin-right: 8px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Password strength meter */
        .strength-meter {
            margin-top: 5px;
            height: 4px;
            background: #e9ecef;
            border-radius: 2px;
            overflow: hidden;
            display: none;
        }

        .strength-bar {
            height: 100%;
            transition: all 0.3s ease;
            border-radius: 2px;
        }

        .strength-weak { background: #dc3545; width: 25%; }
        .strength-fair { background: #ffc107; width: 50%; }
        .strength-good { background: #28a745; width: 75%; }
        .strength-strong { background: #007bff; width: 100%; }

        .strength-text {
            font-size: 12px;
            margin-top: 3px;
            font-weight: 500;
        }

        /* Responsive */
        @media (max-width: 480px) {
            .alert-container {
                left: 10px;
                right: 10px;
                max-width: none;
            }
            
            .alert {
                margin-bottom: 5px;
                padding: 12px 15px;
            }
        }
    </style>
</head>

<body>
    <section>
        <h1>To Do List</h1>
        
    </section>
    
    <!-- Alert Messages -->
    <?php if (!empty($alerts)): ?>
    <div class="alert-container">
        <?php foreach ($alerts as $alert): ?> 
        <div class="alert <?= htmlspecialchars($alert['type']); ?>">
            <i class='bx <?= $alert['type'] === 'success' ? 'bxs-check-circle' : ($alert['type'] === 'warning' ? 'bxs-error' : 'bxs-x-circle'); ?>'></i> 
            <span><?= htmlspecialchars($alert['message']); ?></span>
            <button onclick="this.parentElement.remove()" style="background: none; border: none; color: inherit; margin-left: auto; cursor: pointer; padding: 0 5px;">&times;</button>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="login-users <?= $active_form === 'register' ? 'show slide' : ($active_form === 'login' ? 'show' : ''); ?>">
        <!-- Login Form -->
        <div class="form-box login">
            <h2>Login</h2>
            <form action="login_process.php" method="POST" id="loginForm">
                <div class="input-box">
                    <input type="email" name="email" id="loginEmail" placeholder="Email" required>
                    <i class='bx bx-envelope-open'></i> 
                </div>
                <div class="input-box">
                    <input type="password" name="password" id="loginPassword" placeholder="Password" required>
                    <button type="button" class="password-toggle" onclick="togglePassword('loginPassword')">
                        <i class='bx bx-show'></i>
                    </button>
                    <i class='bx bxs-lock'></i> 
                </div>
                <button type="submit" name="login_btn" class="btn" id="loginBtn">Login</button>
                <p>Belum punya akun? <a href="#" class="register-link">Daftar di sini</a></p>
            </form>
        </div>

        <!-- Register Form -->
        <div class="form-box register">
            <h2>Daftar</h2>
            <form action="login_process.php" method="POST" id="registerForm">
                <div class="input-box">
                    <input type="text" name="name" id="registerName" placeholder="Nama Lengkap" required minlength="2">
                    <i class='bx bxs-user'></i> 
                </div>
                <div class="input-box">
                    <input type="email" name="email" id="registerEmail" placeholder="Email" required>
                    <i class='bx bx-envelope-open'></i> 
                </div>
                <div class="input-box">
                    <input type="password" name="password" id="registerPassword" placeholder="Password" required minlength="6">
                    <button type="button" class="password-toggle" onclick="togglePassword('registerPassword')">
                        <i class='bx bx-show'></i>
                    </button>
                    <i class='bx bxs-lock'></i> 
                    <div class="strength-meter" id="strengthMeter">
                        <div class="strength-bar" id="strengthBar"></div>
                    </div>
                    <div class="strength-text" id="strengthText"></div>
                </div>
                <button type="submit" name="register_btn" class="btn" id="registerBtn">Daftar</button>
                <p>Sudah punya akun? <a href="#" class="login-link">Login di sini</a></p>
            </form>
        </div>
    </div>

    <script src="script.js"></script>
    
    <script>
        // Auto-hide alerts
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    if (alert.parentElement) {
                        alert.style.animation = 'slideOut 0.3s ease-in forwards';
                        setTimeout(() => alert.remove(), 300);
                    }
                }, 5000);
            });
        });

        // Toggle password visibility
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const button = input.nextElementSibling;
            const icon = button.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'bx bx-hide';
            } else {
                input.type = 'password';
                icon.className = 'bx bx-show';
            }
        }

        // Password strength checker
        if (document.getElementById('registerPassword')) {
            document.getElementById('registerPassword').addEventListener('input', function() {
                const password = this.value;
                const meter = document.getElementById('strengthMeter');
                const bar = document.getElementById('strengthBar');
                const text = document.getElementById('strengthText');
                
                if (password.length === 0) {
                    meter.style.display = 'none';
                    text.textContent = '';
                    return;
                }
                
                meter.style.display = 'block';
                
                let strength = 0;
                let strengthClass = '';
                let strengthLabel = '';
                
                // Check password criteria
                if (password.length >= 6) strength++;
                if (password.match(/[a-z]/)) strength++;
                if (password.match(/[A-Z]/)) strength++;
                if (password.match(/[0-9]/)) strength++;
                if (password.match(/[^a-zA-Z0-9]/)) strength++;
                
                // Determine strength level
                if (strength <= 2) {
                    strengthClass = 'strength-weak';
                    strengthLabel = 'Lemah';
                    text.style.color = '#dc3545';
                } else if (strength <= 3) {
                    strengthClass = 'strength-fair';
                    strengthLabel = 'Cukup';
                    text.style.color = '#ffc107';
                } else if (strength <= 4) {
                    strengthClass = 'strength-good';
                    strengthLabel = 'Baik';
                    text.style.color = '#28a745';
                } else {
                    strengthClass = 'strength-strong';
                    strengthLabel = 'Kuat';
                    text.style.color = '#007bff';
                }
                
                bar.className = 'strength-bar ' + strengthClass;
                text.textContent = 'Kekuatan Password: ' + strengthLabel;
            });
        }

        // Form validation and loading state
        if (document.getElementById('loginForm')) {
            document.getElementById('loginForm').addEventListener('submit', function(e) {
                const email = document.getElementById('loginEmail').value;
                const password = document.getElementById('loginPassword').value;
                const btn = document.getElementById('loginBtn');
                
                if (!email || !password) {
                    e.preventDefault();
                    alert('Semua field harus diisi!');
                    return;
                }
                
                // Show loading state
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner"></span>Memproses...';
            });
        }

        if (document.getElementById('registerForm')) {
            document.getElementById('registerForm').addEventListener('submit', function(e) {
                const name = document.getElementById('registerName').value;
                const email = document.getElementById('registerEmail').value;
                const password = document.getElementById('registerPassword').value;
                const btn = document.getElementById('registerBtn');
                
                if (!name || !email || !password) {
                    e.preventDefault();
                    alert('Semua field harus diisi!');
                    return;
                }
                
                if (name.length < 2) {
                    e.preventDefault();
                    alert('Nama minimal 2 karakter!');
                    return;
                }
                
                if (password.length < 6) {
                    e.preventDefault();
                    alert('Password minimal 6 karakter!');
                    return;
                }
                
                // Show loading state
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner"></span>Mendaftar...';
            });
        }
       
        // Reset loading state if page reloads
        window.addEventListener('load', function() {
            const loginBtn = document.getElementById('loginBtn');
            const registerBtn = document.getElementById('registerBtn');
            
            if (loginBtn) {
                loginBtn.disabled = false;
                loginBtn.innerHTML = 'Login';
            }
            
            if (registerBtn) {
                registerBtn.disabled = false;
                registerBtn.innerHTML = 'Daftar';
            }
        });
    </script>
</body>
</html>