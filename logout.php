<?php
// logout.php - Handler untuk logout user

// Include session helper untuk menangani session dengan aman
require_once 'session_helper.php';
require_once 'config.php';

// Initialize session manager
$database = new Database();
$pdo = $database->getConnection();

// Get user name for goodbye message
$user_name = $_SESSION['name'] ?? 'User';

// Log logout activity (optional)
if (isset($_SESSION['user_id'])) {
    try {
        // You can log the logout activity here if needed
        $log_query = "INSERT INTO user_activity_log (user_id, activity, ip_address, created_at) 
                     VALUES (:user_id, 'logout', :ip_address, NOW())";
        // Uncomment below if you have user_activity_log table
        // $log_stmt = $pdo->prepare($log_query);
        // $log_stmt->bindParam(':user_id', $_SESSION['user_id']);
        // $log_stmt->bindParam(':ip_address', $_SERVER['REMOTE_ADDR']);
        // $log_stmt->execute();
    } catch (Exception $e) {
        error_log("Error logging logout activity: " . $e->getMessage());
    }
}

// Destroy session from database if exists
if (isset($_SESSION['session_id'])) {
    try {
        $sessionManager->destroySession();
    } catch (Exception $e) {
        error_log("Error destroying database session: " . $e->getMessage());
    }
}

// Ambil username dari session sebelum destroy
$user_name = isset($_SESSION['username']) ? $_SESSION['username'] : 'User';

// ... destroy session ...
session_destroy();

// Mulai session baru untuk alert
session_start();

$_SESSION['alerts'][] = [
    'type' => 'success',
    'message' => "Sampai jumpa, {$user_name}! Anda telah berhasil logout."
];

// Redirect to login page
header('Location: index.php');
exit();
?>