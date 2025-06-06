<?php
// auth_check.php - Simple authentication check

// Jangan start session di sini, biarkan file yang memanggil yang handle
require_once 'config.php';

function checkAuth() {
    // Pastikan session sudah dimulai
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Cek apakah user sudah login
    if (!isset($_SESSION['username']) || empty($_SESSION['username'])) {
        // Redirect ke index dengan error message
        $_SESSION['alerts'][] = [
            'type' => 'error',
            'message' => 'Anda harus login terlebih dahulu untuk mengakses dashboard!'
        ];
        $_SESSION['active_form'] = 'login';
        header('Location: index.php');
        exit();
    }
    
    return true;
}

function getUserInfo() {
    // Pastikan session sudah dimulai
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['username'])) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'] ?? 0,
        'username' => $_SESSION['username'] ?? 'User', // FIXED: gunakan username bukan name
        'email' => $_SESSION['email'] ?? '',
        'login_time' => $_SESSION['last_activity'] ?? time(),
        'last_activity' => $_SESSION['last_activity'] ?? time()
    ];
}

function isLoggedIn() {
    // Pastikan session sudah dimulai
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    return isset($_SESSION['username']) && !empty($_SESSION['username']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'authentication_required',
            'message' => 'Authentication required'
        ]);
        exit();
    }
}

function redirectIfLoggedIn($redirect_to = 'dashboard.php') {
    if (isLoggedIn()) {
        header('Location: ' . $redirect_to);
        exit();
    }
}
?>