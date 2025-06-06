<?php
// login_process.php - Fixed final version

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include config
require_once 'config.php';

// ...existing code...

// Handle Registration - Cek berdasarkan adanya field 'name'
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name']) && isset($_POST['email']) && isset($_POST['password'])) {
    // Ini adalah register (ada name, email, password)
    
    $username = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    // Validasi input
    if (empty($username) || empty($email) || empty($password)) {
        $_SESSION['alerts'][] = [
            'type' => 'error',
            'message' => 'Semua field harus diisi!'
        ];
        $_SESSION['active_form'] = 'register';
        header('Location: index.php');
        exit();
    }
    
    if (strlen($password) < 6) {
        $_SESSION['alerts'][] = [
            'type' => 'error',
            'message' => 'Password minimal 6 karakter!'
        ];
        $_SESSION['active_form'] = 'register';
        header('Location: index.php');
        exit();
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['alerts'][] = [
            'type' => 'error',
            'message' => 'Format email tidak valid!'
        ];
        $_SESSION['active_form'] = 'register';
        header('Location: index.php');
        exit();
    }
    
    try {
        // Database connection
        $database = new Database();
        $pdo = $database->getConnection();
        
        // Cek apakah email sudah terdaftar
        $check_query = "SELECT id FROM users WHERE email = :email";
        $check_stmt = $pdo->prepare($check_query);
        $check_stmt->bindParam(':email', $email);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            $_SESSION['alerts'][] = [
                'type' => 'error',
                'message' => 'Email sudah terdaftar!'
            ];
            $_SESSION['active_form'] = 'register';
            header('Location: index.php');
            exit();
        }
        
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert user baru
        $insert_query = "INSERT INTO users (username, email, password) VALUES (:username, :email, :password)";
        $insert_stmt = $pdo->prepare($insert_query);
        $insert_stmt->bindParam(':username', $username);
        $insert_stmt->bindParam(':email', $email);
        $insert_stmt->bindParam(':password', $hashed_password);
        
        if ($insert_stmt->execute()) {
            // Registrasi berhasil, tampilkan pesan sukses dan arahkan ke login
            $_SESSION['alerts'][] = [
                'type' => 'success',
                'message' => 'Registrasi berhasil! Silakan login untuk melanjutkan.'
            ];
            $_SESSION['active_form'] = 'login';
            header('Location: index.php');
            exit();
        } else {
            $_SESSION['alerts'][] = [
                'type' => 'error',
                'message' => 'Gagal mendaftarkan akun. Silakan coba lagi.'
            ];
            $_SESSION['active_form'] = 'register';
            header('Location: index.php');
            exit();
        }
        
    } catch (Exception $e) {
        $_SESSION['alerts'][] = [
            'type' => 'error',
            'message' => 'Terjadi kesalahan sistem. Silakan coba lagi.'
        ];
        $_SESSION['active_form'] = 'register';
        header('Location: index.php');
        exit();
    }
}

// Handle Login - Cek berdasarkan tidak adanya field 'name'
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email']) && isset($_POST['password']) && !isset($_POST['name'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Validasi input
    if (empty($email) || empty($password)) {
        $_SESSION['alerts'][] = [
            'type' => 'error',
            'message' => 'Email dan password harus diisi!'
        ];
        $_SESSION['active_form'] = 'login';
        header('Location: index.php');
        exit();
    }

    try {
        $database = new Database();
        $pdo = $database->getConnection();

        $query = "SELECT * FROM users WHERE email = :email LIMIT 1";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if ($stmt->rowCount() === 1) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($password, $user['password'])) {
                // Set session login
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                // Redirect ke dashboard
                header('Location: dashboard.php');
                exit();
            }
        }

        $_SESSION['alerts'][] = [
            'type' => 'error',
            'message' => 'Email atau password salah!'
        ];
        $_SESSION['active_form'] = 'login';
        header('Location: index.php');
        exit();

    } catch (Exception $e) {
        $_SESSION['alerts'][] = [
            'type' => 'error',
            'message' => 'Terjadi kesalahan sistem. Silakan coba lagi.'
        ];
        $_SESSION['active_form'] = 'login';
        header('Location: index.php');
        exit();
    }
}

// Redirect jika tidak ada aksi yang valid
header('Location: index.php');
exit();
?>