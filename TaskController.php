<?php
// TaskController.php - API untuk CRUD Tasks

// Start session dan include dependencies
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';
require_once 'auth_check.php';

// Set header untuk JSON response
header('Content-Type: application/json');

// Function untuk mengirim JSON response
function sendResponse($success, $message, $data = null) {
    $response = [
        'success' => $success,
        'message' => $message
    ];
    
    if ($data !== null) {
        $response = array_merge($response, $data);
    }
    
    echo json_encode($response);
    exit();
}

// Check authentication
if (!isLoggedIn()) {
    sendResponse(false, 'Authentication required', ['error' => 'authentication_required']);
}

// Get user info
$user = getUserInfo();
$user_id = $user['id'];

// Get action dari URL parameter atau POST
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    // Database connection
    $database = new Database();
    $pdo = $database->getConnection();
    
    switch ($action) {
        case 'create':
            handleCreateTask($pdo, $user_id);
            break;
            
        case 'read':
            handleReadTasks($pdo, $user_id);
            break;
            
        case 'update':
            handleUpdateTask($pdo, $user_id);
            break;
            
        case 'delete':
            handleDeleteTask($pdo, $user_id);
            break;
            
        case 'toggle':
            handleToggleTask($pdo, $user_id);
            break;
            
        case 'stats':
            handleGetStats($pdo, $user_id);
            break;
            
        default:
            sendResponse(false, 'Invalid action');
    }
    
} catch (Exception $e) {
    sendResponse(false, 'Database error: ' . $e->getMessage());
}

// CREATE - Tambah task baru
function handleCreateTask($pdo, $user_id) {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $due_date = $_POST['due_date'] ?? null;
    $due_time = $_POST['due_time'] ?? null;
    $priority = $_POST['priority'] ?? 'medium';
    
    // Validasi
    if (empty($title)) {
        sendResponse(false, 'Judul tugas harus diisi');
    }
    
    if (strlen($title) > 200) {
        sendResponse(false, 'Judul tugas terlalu panjang (maksimal 200 karakter)');
    }
    
    if (strlen($description) > 1000) {
        sendResponse(false, 'Deskripsi terlalu panjang (maksimal 1000 karakter)');
    }
    
    if (!in_array($priority, ['low', 'medium', 'high'])) {
        $priority = 'medium';
    }
    
    // Validasi tanggal
    // Di dalam function handleCreateTask(), ganti query INSERT:
try {
    // Gabungkan due_date dan due_time menjadi datetime
    $due_datetime = null;
    if (!empty($due_date)) {
        $due_datetime = $due_date;
        if (!empty($due_time)) {
            $due_datetime .= ' ' . $due_time . ':00';
        } else {
            $due_datetime .= ' 00:00:00';
        }
    }
    
    $query = "INSERT INTO tasks (user_id, title, description, due_date, priority, status) 
              VALUES (:user_id, :title, :description, :due_date, :priority, 'pending')";
    
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':due_date', $due_datetime); // Gunakan due_datetime
    $stmt->bindParam(':priority', $priority);
    
    if ($stmt->execute()) {
        sendResponse(true, 'Tugas berhasil ditambahkan!');
    } else {
        sendResponse(false, 'Gagal menambahkan tugas');
    }
} catch (Exception $e) {
    sendResponse(false, 'Error: ' . $e->getMessage());
}
}

// READ - Ambil daftar tasks
function handleReadTasks($pdo, $user_id) {
    $search = $_GET['search'] ?? '';
    $status = $_GET['status'] ?? '';
    $order_by = $_GET['order_by'] ?? 'created_at';
    $order_dir = $_GET['order_dir'] ?? 'DESC';
    
    // Validasi order_by
    $allowed_order = ['created_at', 'due_date', 'priority', 'title', 'status'];
    if (!in_array($order_by, $allowed_order)) {
        $order_by = 'created_at';
    }
    
    // Validasi order_dir
    if (!in_array(strtoupper($order_dir), ['ASC', 'DESC'])) {
        $order_dir = 'DESC';
    }
    
    // Build query
    $query = "SELECT * FROM tasks WHERE user_id = :user_id";
    $params = [':user_id' => $user_id];
    
    // Search filter
    if (!empty($search)) {
        $query .= " AND (title LIKE :search OR description LIKE :search)";
        $params[':search'] = '%' . $search . '%';
    }
    
    // Status filter
    if (!empty($status)) {
        $query .= " AND status = :status";
        $params[':status'] = $status;
    }
    
    // Priority order khusus
    if ($order_by === 'priority') {
        $query .= " ORDER BY CASE priority WHEN 'high' THEN 1 WHEN 'medium' THEN 2 WHEN 'low' THEN 3 END " . $order_dir;
    } else {
        $query .= " ORDER BY {$order_by} {$order_dir}";
    }
    
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $tasks = $stmt->fetchAll();
        
        sendResponse(true, 'Tasks loaded successfully', ['tasks' => $tasks]);
    } catch (Exception $e) {
        sendResponse(false, 'Error loading tasks: ' . $e->getMessage());
    }
}

// UPDATE - Update task
function handleUpdateTask($pdo, $user_id) {
    $task_id = $_POST['task_id'] ?? 0;
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $due_date = $_POST['due_date'] ?? null;
    $due_time = $_POST['due_time'] ?? null;
    $priority = $_POST['priority'] ?? 'medium';
    $status = $_POST['status'] ?? 'pending';
    
    // Validasi
    if (empty($task_id) || empty($title)) {
        sendResponse(false, 'Data tidak lengkap');
    }
    
    // Cek apakah task milik user
    $check_query = "SELECT id FROM tasks WHERE id = :task_id AND user_id = :user_id";
    $check_stmt = $pdo->prepare($check_query);
    $check_stmt->bindParam(':task_id', $task_id);
    $check_stmt->bindParam(':user_id', $user_id);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() === 0) {
        sendResponse(false, 'Task tidak ditemukan atau bukan milik Anda');
    }
    
    // Validasi data
    if (strlen($title) > 200) {
        sendResponse(false, 'Judul tugas terlalu panjang');
    }
    
    if (!in_array($status, ['pending', 'completed'])) {
        $status = 'pending';
    }
    
    if (!in_array($priority, ['low', 'medium', 'high'])) {
        $priority = 'medium';
    }
    
    if (empty($due_date)) {
        $due_date = null;
    }
    
    // Validasi waktu
    if (!empty($due_time)) {
        $time = DateTime::createFromFormat('H:i', $due_time);
        if (!$time) {
            sendResponse(false, 'Jam jatuh tempo tidak valid');
        }
    } else {
        $due_time = null;
    }
    
    // Di dalam function handleUpdateTask(), ganti bagian try:
try {
    // Gabungkan due_date dan due_time menjadi datetime
    $due_datetime = null;
    if (!empty($due_date)) {
        $due_datetime = $due_date;
        if (!empty($due_time)) {
            $due_datetime .= ' ' . $due_time . ':00';
        } else {
            $due_datetime .= ' 00:00:00';
        }
    }
    
    $query = "UPDATE tasks SET title = :title, description = :description, 
              due_date = :due_date, priority = :priority, status = :status 
              WHERE id = :task_id AND user_id = :user_id";
    
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':due_date', $due_datetime); // Gunakan due_datetime
    $stmt->bindParam(':priority', $priority);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':task_id', $task_id);
    $stmt->bindParam(':user_id', $user_id);
    
    if ($stmt->execute()) {
        sendResponse(true, 'Tugas berhasil diupdate!');
    } else {
        sendResponse(false, 'Gagal mengupdate tugas');
    }
} catch (Exception $e) {
    sendResponse(false, 'Error: ' . $e->getMessage());
}
}

// DELETE - Hapus task
function handleDeleteTask($pdo, $user_id) {
    $task_id = $_POST['task_id'] ?? 0;
    
    if (empty($task_id)) {
        sendResponse(false, 'Task ID tidak valid');
    }
    
    try {
        $query = "DELETE FROM tasks WHERE id = :task_id AND user_id = :user_id";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':task_id', $task_id);
        $stmt->bindParam(':user_id', $user_id);
        
        if ($stmt->execute() && $stmt->rowCount() > 0) {
            sendResponse(true, 'Tugas berhasil dihapus!');
        } else {
            sendResponse(false, 'Task tidak ditemukan atau gagal dihapus');
        }
    } catch (Exception $e) {
        sendResponse(false, 'Error: ' . $e->getMessage());
    }
}

// TOGGLE - Toggle status task
function handleToggleTask($pdo, $user_id) {
    $task_id = $_POST['task_id'] ?? 0;
    
    if (empty($task_id)) {
        sendResponse(false, 'Task ID tidak valid');
    }
    
    try {
        // Get current status
        $get_query = "SELECT status FROM tasks WHERE id = :task_id AND user_id = :user_id";
        $get_stmt = $pdo->prepare($get_query);
        $get_stmt->bindParam(':task_id', $task_id);
        $get_stmt->bindParam(':user_id', $user_id);
        $get_stmt->execute();
        
        if ($get_stmt->rowCount() === 0) {
            sendResponse(false, 'Task tidak ditemukan');
        }
        
        $current_status = $get_stmt->fetch()['status'];
        $new_status = ($current_status === 'completed') ? 'pending' : 'completed';
        
        // Update status
        $update_query = "UPDATE tasks SET status = :status WHERE id = :task_id AND user_id = :user_id";
        $update_stmt = $pdo->prepare($update_query);
        $update_stmt->bindParam(':status', $new_status);
        $update_stmt->bindParam(':task_id', $task_id);
        $update_stmt->bindParam(':user_id', $user_id);
        
        if ($update_stmt->execute()) {
            $message = ($new_status === 'completed') ? 'Tugas ditandai selesai!' : 'Tugas ditandai belum selesai!';
            sendResponse(true, $message);
        } else {
            sendResponse(false, 'Gagal mengupdate status tugas');
        }
    } catch (Exception $e) {
        sendResponse(false, 'Error: ' . $e->getMessage());
    }
}

// STATS - Get task statistics
function handleGetStats($pdo, $user_id) {
    try {
        $query = "SELECT 
                    COUNT(*) as total_tasks,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_tasks,
                    SUM(CASE WHEN due_date < CURDATE() AND status = 'pending' THEN 1 ELSE 0 END) as overdue_tasks
                  FROM tasks WHERE user_id = :user_id";
        
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $stats = $stmt->fetch();
        
        sendResponse(true, 'Stats loaded successfully', ['stats' => $stats]);
    } catch (Exception $e) {
        sendResponse(false, 'Error loading stats: ' . $e->getMessage());
    }
}
?>
