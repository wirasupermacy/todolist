<?php
// session_helper.php - Central session management

// Function to safely start session
function safeSessionStart() {
    if (session_status() === PHP_SESSION_NONE) {
        // Set session parameters before starting
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_secure', 0); // Set to 1 for HTTPS
        ini_set('session.gc_maxlifetime', 1800);
        ini_set('session.cookie_lifetime', 1800);
        
        session_start();
        return true;
    }
    return false; // Session was already active
}

// Initialize session safely
safeSessionStart();
?>