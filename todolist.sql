-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 04, 2025 at 03:31 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `todolist`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `CleanExpiredSessions` ()   BEGIN
    DELETE FROM user_sessions 
    WHERE expires_at < NOW() OR is_active = 0;
    
    SELECT ROW_COUNT() as sessions_cleaned;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `GetUserTaskStats` (IN `p_user_id` INT)   BEGIN
    SELECT 
        COUNT(*) as total_tasks,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_tasks,
        SUM(CASE WHEN due_date < CURDATE() AND status = 'pending' THEN 1 ELSE 0 END) as overdue_tasks,
        SUM(CASE WHEN due_date = CURDATE() AND status = 'pending' THEN 1 ELSE 0 END) as due_today_tasks,
        SUM(CASE WHEN priority = 'high' AND status = 'pending' THEN 1 ELSE 0 END) as high_priority_pending,
        ROUND(
            (SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2
        ) as completion_percentage
    FROM tasks 
    WHERE user_id = p_user_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SearchTasks` (IN `p_user_id` INT, IN `p_search_term` VARCHAR(255), IN `p_status` VARCHAR(20), IN `p_priority` VARCHAR(20), IN `p_order_by` VARCHAR(50), IN `p_order_dir` VARCHAR(10))   BEGIN
    SET @sql = 'SELECT * FROM tasks WHERE user_id = ?';
    
    IF p_search_term IS NOT NULL AND p_search_term != '' THEN
        SET @sql = CONCAT(@sql, ' AND (title LIKE "%', p_search_term, '%" OR description LIKE "%', p_search_term, '%")');
    END IF;
    
    IF p_status IS NOT NULL AND p_status != '' THEN
        SET @sql = CONCAT(@sql, ' AND status = "', p_status, '"');
    END IF;
    
    IF p_priority IS NOT NULL AND p_priority != '' THEN
        SET @sql = CONCAT(@sql, ' AND priority = "', p_priority, '"');
    END IF;
    
    IF p_order_by IS NOT NULL AND p_order_by != '' THEN
        SET @sql = CONCAT(@sql, ' ORDER BY ', p_order_by);
        IF p_order_dir IS NOT NULL AND p_order_dir != '' THEN
            SET @sql = CONCAT(@sql, ' ', p_order_dir);
        END IF;
    ELSE
        SET @sql = CONCAT(@sql, ' ORDER BY created_at DESC');
    END IF;
    
    PREPARE stmt FROM @sql;
    EXECUTE stmt USING p_user_id;
    DEALLOCATE PREPARE stmt;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `status` enum('pending','completed') DEFAULT 'pending',
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`id`, `user_id`, `title`, `description`, `due_date`, `status`, `priority`, `created_at`, `updated_at`) VALUES
(1, 1, 'Setup Database Schema', 'Membuat dan mengoptimalkan skema database todolist', '2025-06-05', 'completed', 'high', '2025-06-03 05:45:34', '2025-06-03 05:45:34'),
(2, 1, 'Implementasi Autentikasi', 'Membuat sistem login dan registrasi yang aman', '2025-06-08', 'pending', 'high', '2025-06-03 05:45:34', '2025-06-03 05:45:34'),
(3, 2, 'Menyelesaikan Project Web', 'Membuat aplikasi todo list dengan PHP dan MySQL', '2025-06-10', 'pending', 'high', '2025-06-03 05:45:34', '2025-06-03 05:45:34'),
(4, 2, 'Belajar JavaScript', 'Mempelajari konsep dasar JavaScript untuk frontend', '2025-06-15', 'pending', 'medium', '2025-06-03 05:45:34', '2025-06-03 05:45:34'),
(5, 3, 'Meeting dengan Tim', 'Diskusi progress project minggu ini\n[Completed: 2025-06-03 13:58:03]', '2025-06-08', 'completed', 'high', '2025-06-03 05:45:34', '2025-06-03 05:58:03'),
(6, 3, 'Code Review', 'Review kode aplikasi todo list\n[Completed: 2025-06-03 13:46:23]\n[Completed: 2025-06-03 13:58:02]\n[Completed: 2025-06-03 13:58:33]\n[Completed: 2025-06-03 13:58:35]', '2025-06-12', 'pending', 'medium', '2025-06-03 05:45:34', '2025-06-03 05:58:37'),
(7, 4, 'Backup Database', 'Melakukan backup rutin database aplikasi', '2025-06-12', 'completed', 'low', '2025-06-03 05:45:34', '2025-06-03 05:45:34'),
(8, 4, 'Testing Aplikasi', 'Melakukan pengujian menyeluruh aplikasi', '2025-06-14', 'pending', 'high', '2025-06-03 05:45:34', '2025-06-03 05:45:34'),
(9, 5, 'Update Dokumentasi', 'Memperbarui dokumentasi API', '2025-06-20', 'pending', 'medium', '2025-06-03 05:45:34', '2025-06-03 05:45:34'),
(10, 6, 'Deploy ke Production', 'Deploy aplikasi ke server production', '2025-06-25', 'pending', 'high', '2025-06-03 05:45:34', '2025-06-03 05:45:34'),
(11, 3, 'Matematika', 'Beli buah\n[Completed: 2025-06-03 13:58:45]', '2025-06-19', 'pending', 'medium', '2025-06-03 05:55:51', '2025-06-03 05:58:51');

--
-- Triggers `tasks`
--
DELIMITER $$
CREATE TRIGGER `tr_task_status_update` BEFORE UPDATE ON `tasks` FOR EACH ROW BEGIN
    -- Set updated_at
    SET NEW.updated_at = NOW();
    
    -- Jika status berubah ke completed, catat waktu completion di description
    IF NEW.status = 'completed' AND OLD.status != 'completed' THEN
        SET NEW.description = CONCAT(
            COALESCE(OLD.description, ''), 
            '\n[Completed: ', NOW(), ']'
        );
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Stand-in structure for view `task_details`
-- (See below for the actual view)
--
CREATE TABLE `task_details` (
`id` int(11)
,`title` varchar(200)
,`description` text
,`due_date` date
,`status` enum('pending','completed')
,`priority` enum('low','medium','high')
,`created_at` timestamp
,`updated_at` timestamp
,`username` varchar(255)
,`email` varchar(255)
,`task_status_detail` varchar(9)
,`days_until_due` int(7)
);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `created_at`) VALUES
(1, 'admin', 'admin@todolist.com', '$2y$10$dlQk.bHZ51CxYu1vCY3mY.DgDZ0VgRcR0lnzIh5OOaITKwCwdi072', '2025-06-03 05:45:34'),
(2, 'wira', 'wirapramana18.id@gmail.com', '$2y$10$hrFC.79dDIweZgjjwN7dR.4Zeh9urOP5wvV0x8sIGXnQNhwUEbxjK', '2025-05-29 21:04:46'),
(3, 'Wira Leonhart', 'putugalager80@sma.belajar.id', '$2y$10$FW8kU/zE1YlbhouiHZZ2seFShhuKawVcd1sOXEBFzMNK.eAODUNRu', '2025-05-30 05:46:56'),
(4, 'dimas', 'kiritolord387@gmail.com', '$2y$10$1Eo3slTuNFVOYTkazblQIOkOUrDMWTkrIQnjo58FCEwA19qZzq8km', '2025-05-30 05:53:52'),
(5, 'galager', 'galager@2005.com', '$2y$10$eZ2ST8wuuAzdxJAoRxpHf.mJhIgAFagT/l2RwgT.ReiyefLPJ..EK', '2025-05-31 20:38:09'),
(6, 'jaya', 'jaya@123.com', '$2y$10$vyZA5O4iwtPg87Q67dsicufJVzeul6ncORSUdJ3.9P2d6IyblhukO', '2025-06-02 06:33:10'),
(7, 'Daniel', 'danilchinam1@gmail.com', '$2y$10$8IP5AKduEJ.HYv7bWCTnfOgGW6E8twPl2sGxSfIsWMmPj5dWAwZOi', '2025-06-03 06:00:20');

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `session_id` varchar(128) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `user_task_stats`
-- (See below for the actual view)
--
CREATE TABLE `user_task_stats` (
`user_id` int(11)
,`username` varchar(255)
,`email` varchar(255)
,`total_tasks` bigint(21)
,`completed_tasks` decimal(22,0)
,`pending_tasks` decimal(22,0)
,`overdue_tasks` decimal(22,0)
,`due_today_tasks` decimal(22,0)
,`completion_percentage` decimal(28,2)
);

-- --------------------------------------------------------

--
-- Structure for view `task_details`
--
DROP TABLE IF EXISTS `task_details`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `task_details`  AS SELECT `t`.`id` AS `id`, `t`.`title` AS `title`, `t`.`description` AS `description`, `t`.`due_date` AS `due_date`, `t`.`status` AS `status`, `t`.`priority` AS `priority`, `t`.`created_at` AS `created_at`, `t`.`updated_at` AS `updated_at`, `u`.`username` AS `username`, `u`.`email` AS `email`, CASE WHEN `t`.`due_date` < curdate() AND `t`.`status` = 'pending' THEN 'overdue' WHEN `t`.`due_date` = curdate() AND `t`.`status` = 'pending' THEN 'due_today' ELSE `t`.`status` END AS `task_status_detail`, to_days(`t`.`due_date`) - to_days(curdate()) AS `days_until_due` FROM (`tasks` `t` join `users` `u` on(`t`.`user_id` = `u`.`id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `user_task_stats`
--
DROP TABLE IF EXISTS `user_task_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `user_task_stats`  AS SELECT `u`.`id` AS `user_id`, `u`.`username` AS `username`, `u`.`email` AS `email`, count(`t`.`id`) AS `total_tasks`, sum(case when `t`.`status` = 'completed' then 1 else 0 end) AS `completed_tasks`, sum(case when `t`.`status` = 'pending' then 1 else 0 end) AS `pending_tasks`, sum(case when `t`.`due_date` < curdate() and `t`.`status` = 'pending' then 1 else 0 end) AS `overdue_tasks`, sum(case when `t`.`due_date` = curdate() and `t`.`status` = 'pending' then 1 else 0 end) AS `due_today_tasks`, round(sum(case when `t`.`status` = 'completed' then 1 else 0 end) / count(`t`.`id`) * 100,2) AS `completion_percentage` FROM (`users` `u` left join `tasks` `t` on(`u`.`id` = `t`.`user_id`)) GROUP BY `u`.`id`, `u`.`username`, `u`.`email` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_due_date` (`due_date`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email_unique` (`email`),
  ADD KEY `idx_username` (`username`);

--
-- Indexes for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_id` (`session_id`),
  ADD KEY `idx_session_id` (`session_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_expires_at` (`expires_at`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
