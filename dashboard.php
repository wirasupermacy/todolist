<?php
// Fixed dashboard.php - Perbaikan untuk session name issue

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'auth_check.php';
checkAuth(); // Check if user is authenticated

// Gunakan function getUserInfo() yang sudah ada di auth_check.php
$user = getUserInfo();

// Debug: Uncomment untuk melihat session data
// echo "<pre>SESSION DEBUG: " . print_r($_SESSION, true) . "</pre>";
// echo "<pre>USER DEBUG: " . print_r($user, true) . "</pre>";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Todo List - Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="dashboard.css" rel="stylesheet">
    
</head>
<body>
    <!-- Auth Protection Alert -->
    <div id="auth-protection-alert" class="auth-protection-alert">
        <div style="display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-lock"></i>
            <div>
                <strong>Akses Ditolak!</strong><br>
                Anda harus login terlebih dahulu untuk mengakses dashboard.
                <a href="index.php" style="color: #742a2a; text-decoration: underline;">Login di sini</a>
            </div>
        </div>
    </div>

    <!-- Header -->
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-tasks"></i> Todo List</h1>
            <div class="header-actions">
                <h1>Selamat datang, <?php echo htmlspecialchars($user['username'] ?? 'User'); ?></h1>
                <button class="btn btn-danger" onclick="logout()">
                    <i class="fas fa-sign-out-alt"></i> Keluar
                </button>
            </div>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon total">
                    <i class="fas fa-list"></i>
                </div>
                <div class="stat-number" id="total-tasks">0</div>
                <div class="stat-label">Total Tugas</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon completed">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-number" id="completed-tasks">0</div>
                <div class="stat-label">Selesai</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon pending">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-number" id="pending-tasks">0</div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon overdue">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-number" id="overdue-tasks">0</div>
                <div class="stat-label">Terlambat</div>
            </div>
        </div>

        <!-- Alert -->
        <div id="alert" class="alert">
            <button class="alert-close" onclick="closeAlert()">&times;</button>
            <span id="alert-message"></span>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Task Form -->
            <div class="task-form-section">
                <h2 class="section-title">
                    <i class="fas fa-plus-circle"></i> Tambah Tugas Baru
                </h2>
                <form id="task-form">
                    <div class="form-group">
                        <label class="form-label" for="title">Judul Tugas</label>
                        <input type="text" id="title" name="title" class="form-input" required maxlength="200">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="description">Deskripsi</label>
                        <textarea id="description" name="description" class="form-textarea" rows="3" maxlength="1000"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="due_date">Tanggal Jatuh Tempo</label>
                        <input type="date" id="due_date" name="due_date" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="due_time">Jam Jatuh Tempo</label>
                        <input type="time" id="due_time" name="due_time" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="priority">Prioritas</label>
                        <select id="priority" name="priority" class="form-select">
                            <option value="low">Rendah</option>
                            <option value="medium" selected>Sedang</option>
                            <option value="high">Tinggi</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Tambah Tugas
                    </button>
                </form>
            </div>

            <!-- Task List -->
            <div class="task-list-section">
                <h2 class="section-title">
                    <i class="fas fa-list-ul"></i> Daftar Tugas
                </h2>
                
                <div class="task-list-content">
                    <!-- Filters -->
                    <div class="task-filters">
                        <div class="search-box">
                            <input type="text" id="search" class="search-input" placeholder="Cari tugas...">
                            <i class="fas fa-search search-icon"></i>
                        </div>
                        <select id="status-filter" class="filter-select">
                            <option value="">Semua Status</option>
                            <option value="pending">Pending</option>
                            <option value="completed">Selesai</option>
                        </select>
                        <select id="sort-by" class="filter-select">
                            <option value="created_at-DESC">Terbaru</option>
                            <option value="created_at-ASC">Terlama</option>
                            <option value="due_date-ASC">Jatuh Tempo</option>
                            <option value="priority-DESC">Prioritas</option>
                            <option value="title-ASC">Judul A-Z</option>
                        </select>
                    </div>

                    <!-- Scrollable Task Container -->
                    <div class="task-list-container">
                        <!-- Loading -->
                        <div id="loading" class="loading">
                            <div class="spinner"></div>
                            <p>Memuat tugas...</p>
                        </div>

                        <!-- Task List -->
                        <div id="task-list"></div>

                        <!-- Empty State -->
                        <div id="empty-state" class="empty-state" style="display: none;">
                            <i class="fas fa-inbox"></i>
                            <h3>Belum ada tugas</h3>
                            <p>Mulai dengan menambahkan tugas pertama Anda!</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Task Modal -->
    <div id="edit-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-edit"></i> Edit Tugas
                </h3>
                <button class="close" onclick="closeEditModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="edit-task-form">
                    <input type="hidden" id="edit-task-id" name="task_id">
                    <div class="form-group">
                        <label class="form-label" for="edit-title">Judul Tugas *</label>
                        <input type="text" id="edit-title" name="title" class="form-input" required maxlength="200">
                        <div class="validation-error" id="edit-title-error">Judul tugas harus diisi</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="edit-description">Deskripsi</label>
                        <textarea id="edit-description" name="description" class="form-textarea" rows="3" maxlength="1000"></textarea>
                        <div class="validation-error" id="edit-description-error">Deskripsi terlalu panjang</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="edit-due-date">Tanggal Jatuh Tempo</label>
                        <input type="date" id="edit-due-date" name="due_date" class="form-input">
                    </div>
                    <div class="form-group">
    <label class="form-label" for="edit-due-time">Jam Jatuh Tempo</label>
    <input type="time" id="edit-due-time" name="due_time" class="form-input">
    <div class="validation-error" id="edit-due-time-error">Jam tidak valid</div>
</div>
                    <div class="form-group">
                        <label class="form-label" for="edit-priority">Prioritas</label>
                        <select id="edit-priority" name="priority" class="form-select">
                            <option value="low">Rendah</option>
                            <option value="medium">Sedang</option>
                            <option value="high">Tinggi</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="edit-status">Status</label>
                        <select id="edit-status" name="status" class="form-select">
                            <option value="pending">Pending</option>
                            <option value="completed">Selesai</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">
                    <i class="fas fa-times"></i> Batal
                </button>
                <button type="button" class="btn btn-primary" onclick="updateTask()">
                    <i class="fas fa-save"></i> Simpan Perubahan
                </button>
            </div>
        </div>
    </div>

    <script>
        // Global variables
        let tasks = [];
        let currentEditTask = null;

        // Check authentication on page load
        function checkAuthenticationStatus() {
            // Simulate checking if user is authenticated
            // In real implementation, this would check session or token
            const urlParams = new URLSearchParams(window.location.search);
            const authError = urlParams.get('auth_error');
            
            if (authError === 'required') {
                showAuthProtectionAlert();
                // Redirect to login after showing alert
                setTimeout(() => {
                    window.location.href = 'index.php';
                }, 3000);
                return false;
            }
            return true;
        }

        // Show auth protection alert
        function showAuthProtectionAlert() {
            const alert = document.getElementById('auth-protection-alert');
            alert.style.display = 'block';
            
            setTimeout(() => {
                alert.style.display = 'none';
            }, 5000);
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            // Check authentication first
            if (!checkAuthenticationStatus()) {
                return;
            }

            loadStats();
            loadTasks();
            
            // Event listeners
            // Ganti event listener form submit yang ada:
document.getElementById('task-form').addEventListener('submit', async function(e) {
    e.preventDefault();

    // Validasi form (opsional, bisa pakai validateForm() jika ingin)
    // if (!validateForm()) return;

    const title = document.getElementById('title').value.trim();
    const description = document.getElementById('description').value.trim();
    const due_date = document.getElementById('due_date').value;
    const due_time = document.getElementById('due_time').value;
    const priority = document.getElementById('priority').value;

    const formData = new FormData();
    formData.append('action', 'create');
    formData.append('title', title);
    formData.append('description', description);
    formData.append('due_date', due_date);
    formData.append('due_time', due_time);
    formData.append('priority', priority);

    const response = await fetch('TaskController.php', {
        method: 'POST',
        body: formData
    });
    const result = await response.json();

    if (result.success) {
        showAlert(result.message, 'success');
        this.reset();
        loadTasks();
        loadStats();
    } else {
        showAlert(result.message, 'error');
    }
});
            document.getElementById('search').addEventListener('input', debounce(loadTasks, 300));
            document.getElementById('status-filter').addEventListener('change', loadTasks);
            document.getElementById('sort-by').addEventListener('change', loadTasks);
            
            // Set minimum date to today
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('due_date').setAttribute('min', today);
            document.getElementById('edit-due-date').setAttribute('min', today);

            // Modal event listeners
            document.getElementById('edit-modal').addEventListener('click', function(e) {
                if (e.target === this) {
                    closeEditModal();
                }
            });

            // Close modal with Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && document.getElementById('edit-modal').classList.contains('show')) {
                    closeEditModal();
                }
            });
        });

        // Form validation
        function validateForm(formPrefix = '') {
            let isValid = true;
            const prefix = formPrefix ? formPrefix + '-' : '';
            
            // Title validation
            const title = document.getElementById(prefix + 'title');
            const titleError = document.getElementById(prefix + 'title-error');
            if (!title.value.trim()) {
                showFieldError(title, titleError, 'Judul tugas harus diisi');
                isValid = false;
            } else if (title.value.length > 200) {
                showFieldError(title, titleError, 'Judul tugas terlalu panjang (maksimal 200 karakter)');
                isValid = false;
            } else {
                hideFieldError(title, titleError);
            }

            // Description validation
            const description = document.getElementById(prefix + 'description');
            const descriptionError = document.getElementById(prefix + 'description-error');
            if (description.value.length > 1000) {
                showFieldError(description, descriptionError, 'Deskripsi terlalu panjang (maksimal 1000 karakter)');
                isValid = false;
            } else {
                hideFieldError(description, descriptionError);
            }

            // Due date validation
            const dueDate = document.getElementById(prefix + 'due-date') || document.getElementById(prefix + 'due_date');
            const dueDateError = document.getElementById(prefix + 'due-date-error') || document.getElementById(prefix + 'due_date-error');
            if (dueDate && dueDate.value) {
                const selectedDate = new Date(dueDate.value);
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                
                if (selectedDate < today) {
                    showFieldError(dueDate, dueDateError, 'Tanggal jatuh tempo tidak boleh di masa lalu');
                    isValid = false;
                } else {
                    hideFieldError(dueDate, dueDateError);
                }
            }

            return isValid;
        }

        function showFieldError(field, errorElement, message) {
            field.classList.add('error');
            if (errorElement) {
                errorElement.textContent = message;
                errorElement.style.display = 'block';
            }
        }

        function hideFieldError(field, errorElement) {
            field.classList.remove('error');
            if (errorElement) {
                errorElement.style.display = 'none';
            }
        }

        // API calls
        async function apiCall(url, data = null, method = 'GET') {
            const options = {
                method: method,
                headers: {
                    'Content-Type': method === 'POST' ? 'application/x-www-form-urlencoded' : 'application/json'
                }
            };
            
            if (data && method === 'POST') {
                options.body = new URLSearchParams(data);
            }
            
            try {
                const response = await fetch(url, options);
                const result = await response.json();
                
                // Handle authentication errors
                if (result.error === 'authentication_required') {
                    showAlert('Sesi Anda telah berakhir. Silakan login kembali.', 'error');
                    setTimeout(() => {
                        window.location.href = 'index.php';
                    }, 2000);
                    return { success: false, message: 'Authentication required' };
                }
                
                return result;
            } catch (error) {
                console.error('API Error:', error);
                showAlert('Terjadi kesalahan koneksi', 'error');
                return { success: false, message: 'Kesalahan koneksi' };
            }
        }

        // Load tasks
        async function loadTasks() {
            showLoading(true);
            
            const search = document.getElementById('search').value;
            const status = document.getElementById('status-filter').value;
            const sortBy = document.getElementById('sort-by').value;
            const [orderBy, orderDir] = sortBy.split('-');
            
            const params = new URLSearchParams({
                action: 'read',
                search: search,
                status: status,
                order_by: orderBy,
                order_dir: orderDir
            });
            
            const result = await apiCall(`TaskController.php?${params}`);
            
            showLoading(false);
            
            if (result.success) {
                tasks = result.tasks;
                renderTasks();
            } else {
                showAlert(result.message, 'error');
            }
        }

        // Load statistics
        async function loadStats() {
            const result = await apiCall('TaskController.php?action=stats');
            
            if (result.success) {
                const stats = result.stats;
                document.getElementById('total-tasks').textContent = stats.total_tasks || 0;
                document.getElementById('completed-tasks').textContent = stats.completed_tasks || 0;
                document.getElementById('pending-tasks').textContent = stats.pending_tasks || 0;
                document.getElementById('overdue-tasks').textContent = stats.overdue_tasks || 0;
            }
        }

        // Render tasks
        function renderTasks() {
            const container = document.getElementById('task-list');
            const emptyState = document.getElementById('empty-state');
            
            if (tasks.length === 0) {
                container.innerHTML = '';
                emptyState.style.display = 'block';
                return;
            }
            
            emptyState.style.display = 'none';
            
            container.innerHTML = tasks.map(task => {
                const isOverdue = task.due_date && new Date(task.due_date) < new Date() && task.status === 'pending';
                const taskClass = task.status === 'completed' ? 'completed' : (isOverdue ? 'overdue' : '');
                
                return `
                    <div class="task-item ${taskClass}">
                        <div class="task-header">
                            <div>
                                <div class="task-title ${task.status === 'completed' ? 'completed' : ''}">${escapeHtml(task.title)}</div>
                                <div class="task-priority priority-${task.priority}">${getPriorityText(task.priority)}</div>
                            </div>
                        </div>
                        ${task.description ? `<div class="task-description">${escapeHtml(task.description)}</div>` : ''}
                        <div class="task-meta">
                            <div class="task-due-date">
                                ${task.due_date ? `<i class="fas fa-calendar"></i> ${formatDate(task.due_date)}` : ''}
                                ${isOverdue ? '<span style="color: var(--danger-color); margin-left: 10px;"><i class="fas fa-exclamation-triangle"></i> Terlambat</span>' : ''}
                            </div>
                            <div class="task-actions">
                                <button class="btn btn-small ${task.status === 'completed' ? 'btn-warning' : 'btn-success'}" 
                                        onclick="toggleTask(${task.id})" title="${task.status === 'completed' ? 'Tandai belum selesai' : 'Tandai selesai'}">
                                    <i class="fas ${task.status === 'completed' ? 'fa-undo' : 'fa-check'}"></i>
                                </button>
                                <button class="btn btn-small btn-info" onclick="openEditModal(${task.id})" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-small btn-danger" onclick="deleteTask(${task.id})" title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }

        // Utility functions
        function showAlert(message, type) {
            const alert = document.getElementById('alert');
            const messageEl = document.getElementById('alert-message');
            
            alert.className = `alert alert-${type}`;
            messageEl.textContent = message;
            alert.style.display = 'block';
            
            // Auto hide after 5 seconds
            setTimeout(() => {
                closeAlert();
            }, 5000);
        }

        function closeAlert() {
            const alert = document.getElementById('alert');
            alert.style.display = 'none';
        }

        function showLoading(show) {
            document.getElementById('loading').style.display = show ? 'block' : 'none';
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Ganti fungsi formatDate() yang ada:
function formatDate(dateString) {
    if (!dateString) return '';
    
    const date = new Date(dateString);
    
    // Check if date is valid
    if (isNaN(date.getTime())) return dateString;
    
    const options = {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    };
    
    const dateFormatted = date.toLocaleDateString('id-ID', options);
    
    // Check if time is included in the original string
    if (dateString.includes(':')) {
        const timeOptions = {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false
        };
        const timeFormatted = date.toLocaleTimeString('id-ID', timeOptions);
        return `${dateFormatted} ${timeFormatted}`;
    }
    
    return dateFormatted;
}

        function getPriorityText(priority) {
            const priorities = {
                'low': 'Rendah',
                'medium': 'Sedang',
                'high': 'Tinggi'
            };
            return priorities[priority] || priority;
        }

        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        function logout() {
            if (confirm('Apakah Anda yakin ingin keluar?')) {
                // Show loading state
                showAlert('Logging out...', 'info');
                
                // Redirect to logout page
                setTimeout(() => {
                    window.location.href = 'logout.php';
                }, 1000);
            }
        }

        // Handle create task
        async function handleCreateTask(e) {
            e.preventDefault();
            
            if (!validateForm()) {
                showAlert('Mohon periksa kembali form Anda', 'error');
                return;
            }
            
            const formData = new FormData(e.target);
            formData.append('action', 'create');
            
            const data = Object.fromEntries(formData);
            const result = await apiCall('TaskController.php', data, 'POST');
            
            if (result.success) {
                showAlert(result.message, 'success');
                e.target.reset();
                loadTasks();
                loadStats();
            } else {
                showAlert(result.message, 'error');
            }
        }

        // Toggle task status
        async function toggleTask(taskId) {
            const result = await apiCall('TaskController.php', {
                action: 'toggle',
                task_id: taskId
            }, 'POST');
            
            if (result.success) {
                showAlert(result.message, 'success');
                loadTasks();
                loadStats();
            } else {
                showAlert(result.message, 'error');
            }
        }

        // Delete task
        async function deleteTask(taskId) {
            if (!confirm('Apakah Anda yakin ingin menghapus tugas ini?')) return;
            
            const result = await apiCall('TaskController.php', {
                action: 'delete',
                task_id: taskId
            }, 'POST');
            
            if (result.success) {
                showAlert(result.message, 'success');
                loadTasks();
                loadStats();
            } else {
                showAlert(result.message, 'error');
            }
        }

        // Edit task functions
        // Ganti fungsi openEditModal() yang ada:
function openEditModal(taskId) {
    const task = tasks.find(t => t.id == taskId);
    if (!task) {
        showAlert('Tugas tidak ditemukan', 'error');
        return;
    }
    
    currentEditTask = task;
    
    // Parse due_date yang mungkin berformat datetime
    let dueDate = '';
    let dueTime = '';
    if (task.due_date) {
        const dateObj = new Date(task.due_date);
        if (!isNaN(dateObj.getTime())) {
            // Format YYYY-MM-DD untuk input date
            dueDate = dateObj.toISOString().split('T')[0];
            // Format HH:MM untuk input time
            dueTime = dateObj.toTimeString().split(' ')[0].substring(0, 5);
        }
    }

    document.getElementById('edit-task-id').value = task.id;
    document.getElementById('edit-title').value = task.title;
    document.getElementById('edit-description').value = task.description || '';
    document.getElementById('edit-due-date').value = dueDate;
    document.getElementById('edit-due-time').value = dueTime;
    document.getElementById('edit-priority').value = task.priority;
    document.getElementById('edit-status').value = task.status;
    
    // Clear any previous validation errors
    clearValidationErrors('edit');
    
    // Show modal
    const modal = document.getElementById('edit-modal');
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
    
    // Focus on title field
    setTimeout(() => {
        document.getElementById('edit-title').focus();
    }, 100);
}

        function closeEditModal() {
            const modal = document.getElementById('edit-modal');
            modal.classList.remove('show');
            document.body.style.overflow = 'auto';
            currentEditTask = null;
            
            // Clear form
            document.getElementById('edit-task-form').reset();
            clearValidationErrors('edit');
        }

        function clearValidationErrors(prefix = '') {
            const prefixStr = prefix ? prefix + '-' : '';
            const errorElements = document.querySelectorAll(`[id*="${prefixStr}"][id$="-error"]`);
            const inputElements = document.querySelectorAll(`[id*="${prefixStr}"].form-input, [id*="${prefixStr}"].form-textarea, [id*="${prefixStr}"].form-select`);
            
            errorElements.forEach(el => el.style.display = 'none');
            inputElements.forEach(el => el.classList.remove('error'));
        }

        async function updateTask() {
            if (!validateForm('edit')) {
                showAlert('Mohon periksa kembali form Anda', 'error');
                return;
            }
            
            if (!currentEditTask) {
                showAlert('Tidak ada tugas yang dipilih untuk diedit', 'error');
                return;
            }
            
            const taskData = {
                action: 'update',
                task_id: currentEditTask.id,
                title: document.getElementById('edit-title').value.trim(),
                description: document.getElementById('edit-description').value.trim(),
                due_date: document.getElementById('edit-due-date').value,
                due_time: document.getElementById('edit-due-time').value,
                priority: document.getElementById('edit-priority').value,
                status: document.getElementById('edit-status').value
            };
            
            const result = await apiCall('TaskController.php', taskData, 'POST');
            
            if (result.success) {
                showAlert(result.message, 'success');
                closeEditModal();
                loadTasks();
                loadStats();
            } else {
                showAlert(result.message, 'error');
            }
        }
    </script>
</body>
</html>