CREATE DATABASE IF NOT EXISTS suggestion_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE suggestion_portal;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  is_admin TINYINT(1) NOT NULL DEFAULT 0,
  is_verified TINYINT(1) NOT NULL DEFAULT 0,
  reset_token VARCHAR(255) DEFAULT NULL,
  reset_expiry DATETIME DEFAULT NULL,
  last_login DATETIME DEFAULT NULL,
  last_logout DATETIME DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS complaints (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  title VARCHAR(255) NOT NULL,
  description TEXT NOT NULL,
  category VARCHAR(100) NOT NULL,
  priority VARCHAR(20) NOT NULL DEFAULT 'Medium',
  status VARCHAR(20) NOT NULL DEFAULT 'pending',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_complaints_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_complaints_user_id (user_id),
  INDEX idx_complaints_status (status),
  INDEX idx_complaints_category (category),
  INDEX idx_complaints_priority (priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS complaint_messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  complaint_id INT NOT NULL,
  user_id INT NOT NULL,
  sender_type ENUM('user', 'admin') NOT NULL,
  message TEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_complaint_messages_complaint FOREIGN KEY (complaint_id) REFERENCES complaints(id) ON DELETE CASCADE,
  CONSTRAINT fk_complaint_messages_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_complaint_messages_complaint_id (complaint_id),
  INDEX idx_complaint_messages_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS contact_messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(255) NOT NULL,
  subject VARCHAR(255) NOT NULL,
  message TEXT NOT NULL,
  status ENUM('unread', 'read', 'replied', 'archived') NOT NULL DEFAULT 'unread',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_contact_messages_status (status),
  INDEX idx_contact_messages_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS audit_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  admin_id INT NOT NULL,
  admin_name VARCHAR(100) NOT NULL,
  action_type VARCHAR(100) NOT NULL,
  action_description TEXT NOT NULL,
  target_type VARCHAR(50) DEFAULT NULL,
  target_id INT DEFAULT NULL,
  target_details TEXT DEFAULT NULL,
  ip_address VARCHAR(45) DEFAULT NULL,
  user_agent TEXT DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_audit_log_admin_id (admin_id),
  INDEX idx_audit_log_created_at (created_at),
  INDEX idx_audit_log_action_type (action_type),
  INDEX idx_audit_log_admin_name (admin_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS admin_activity_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  admin_id INT NOT NULL,
  admin_name VARCHAR(100) NOT NULL,
  action_type VARCHAR(100) NOT NULL,
  action_description TEXT NOT NULL,
  target_id INT DEFAULT NULL,
  target_type VARCHAR(50) DEFAULT NULL,
  ip_address VARCHAR(45) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_admin_activity_logs_admin_id (admin_id),
  INDEX idx_admin_activity_logs_created_at (created_at),
  INDEX idx_admin_activity_logs_action_type (action_type),
  INDEX idx_admin_activity_logs_admin_name (admin_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS admin_actions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  admin_id INT NOT NULL,
  admin_name VARCHAR(100) NOT NULL,
  action_type VARCHAR(50) NOT NULL,
  target_user_id INT DEFAULT NULL,
  target_user_name VARCHAR(100) DEFAULT NULL,
  target_user_email VARCHAR(255) DEFAULT NULL,
  action_timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  details TEXT DEFAULT NULL,
  INDEX idx_admin_actions_admin_id (admin_id),
  INDEX idx_admin_actions_action_timestamp (action_timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS login_attempts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) DEFAULT NULL,
  ip_address VARCHAR(45) DEFAULT NULL,
  user_agent TEXT DEFAULT NULL,
  attempt_type ENUM('admin', 'user') NOT NULL DEFAULT 'user',
  success TINYINT(1) NOT NULL DEFAULT 0,
  failure_reason VARCHAR(255) DEFAULT NULL,
  attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_login_attempts_email (email),
  INDEX idx_login_attempts_ip_address (ip_address),
  INDEX idx_login_attempts_attempted_at (attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS system_settings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  setting_key VARCHAR(100) NOT NULL UNIQUE,
  setting_value TEXT DEFAULT NULL,
  setting_description TEXT DEFAULT NULL,
  updated_by VARCHAR(100) DEFAULT NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_system_settings_setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
