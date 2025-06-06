<?php
// models/Task.php
class Task {
    private $conn;
    private $table_name = "tasks";
    
    public $id;
    public $user_id;
    public $title;
    public $description;
    public $due_date;
    public $status;
    public $priority;
    public $created_at;
    public $updated_at;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Create new task
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (user_id, title, description, due_date, status, priority) 
                  VALUES (:user_id, :title, :description, :due_date, :status, :priority)";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize input
        $this->title = htmlspecialchars(strip_tags($this->title));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->due_date = htmlspecialchars(strip_tags($this->due_date));
        $this->status = htmlspecialchars(strip_tags($this->status));
        $this->priority = htmlspecialchars(strip_tags($this->priority));
        
        // Bind values
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':title', $this->title);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':due_date', $this->due_date);
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':priority', $this->priority);
        
        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        
        return false;
    }
    
    // Read all tasks for user
    public function readByUser($user_id, $status = null, $search = null, $order_by = 'created_at', $order_dir = 'DESC') {
        $query = "SELECT * FROM " . $this->table_name . " WHERE user_id = :user_id";
        
        // Add status filter
        if ($status !== null && in_array($status, ['pending', 'completed'])) {
            $query .= " AND status = :status";
        }
        
        // Add search filter
        if ($search !== null && !empty(trim($search))) {
            $query .= " AND (title LIKE :search OR description LIKE :search)";
        }
        
        // Add ordering
        $allowed_columns = ['title', 'due_date', 'status', 'priority', 'created_at'];
        $allowed_directions = ['ASC', 'DESC'];
        
        if (in_array($order_by, $allowed_columns) && in_array($order_dir, $allowed_directions)) {
            $query .= " ORDER BY " . $order_by . " " . $order_dir;
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        
        if ($status !== null && in_array($status, ['pending', 'completed'])) {
            $stmt->bindParam(':status', $status);
        }
        
        if ($search !== null && !empty(trim($search))) {
            $search_term = "%" . htmlspecialchars(strip_tags($search)) . "%";
            $stmt->bindParam(':search', $search_term);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Read single task
    public function readOne($id, $user_id) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE id = :id AND user_id = :user_id LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            $this->id = $row['id'];
            $this->user_id = $row['user_id'];
            $this->title = $row['title'];
            $this->description = $row['description'];
            $this->due_date = $row['due_date'];
            $this->status = $row['status'];
            $this->priority = $row['priority'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            return true;
        }
        
        return false;
    }
    
    // Update task
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET title = :title, description = :description, due_date = :due_date, 
                      status = :status, priority = :priority, updated_at = NOW()
                  WHERE id = :id AND user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize input
        $this->title = htmlspecialchars(strip_tags($this->title));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->due_date = htmlspecialchars(strip_tags($this->due_date));
        $this->status = htmlspecialchars(strip_tags($this->status));
        $this->priority = htmlspecialchars(strip_tags($this->priority));
        
        // Bind values
        $stmt->bindParam(':title', $this->title);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':due_date', $this->due_date);
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':priority', $this->priority);
        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':user_id', $this->user_id);
        
        return $stmt->execute();
    }
    
    // Delete task
    public function delete($id, $user_id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id AND user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':user_id', $user_id);
        
        return $stmt->execute();
    }
    
    // Toggle task status
    public function toggleStatus($id, $user_id) {
        $query = "UPDATE " . $this->table_name . " 
                  SET status = CASE 
                      WHEN status = 'pending' THEN 'completed' 
                      ELSE 'pending' 
                  END,
                  updated_at = NOW()
                  WHERE id = :id AND user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':user_id', $user_id);
        
        return $stmt->execute();
    }
    
    // Get task statistics
    public function getStats($user_id) {
        $query = "SELECT 
                    COUNT(*) as total_tasks,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_tasks,
                    SUM(CASE WHEN due_date < CURDATE() AND status = 'pending' THEN 1 ELSE 0 END) as overdue_tasks
                  FROM " . $this->table_name . " 
                  WHERE user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Validate task data
    public function validate() {
        $errors = array();
        
        if (empty(trim($this->title))) {
            $errors[] = "Judul tugas tidak boleh kosong";
        }
        
        if (strlen($this->title) > 200) {
            $errors[] = "Judul tugas maksimal 200 karakter";
        }
        
        if (!empty($this->due_date) && !$this->isValidDate($this->due_date)) {
            $errors[] = "Format tanggal tidak valid";
        }
        
        if (!in_array($this->status, ['pending', 'completed'])) {
            $errors[] = "Status tidak valid";
        }
        
        if (!in_array($this->priority, ['low', 'medium', 'high'])) {
            $errors[] = "Prioritas tidak valid";
        }
        
        return $errors;
    }
    
    private function isValidDate($date) {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
}
?>