CREATE DATABASE IF NOT EXISTS findit;
USE findit;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  user_type VARCHAR(50) NOT NULL,
  student_id VARCHAR(50) NOT NULL,
  department VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  phone VARCHAR(30),
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE sessions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  session_id VARCHAR(255) NOT NULL UNIQUE,
  login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  ip_address VARCHAR(50),
  user_agent VARCHAR(255),
  is_active BOOLEAN DEFAULT TRUE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE adminsessions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  admin_id INT NOT NULL,
  session_id VARCHAR(255) NOT NULL,
  login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  ip_address VARCHAR(50),
  admin_agent VARCHAR(255),
  is_active TINYINT(1) DEFAULT 1,
  UNIQUE KEY uq_session_id (session_id),
  INDEX idx_admin_id (admin_id),
  CONSTRAINT fk_adminsessions_admin FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Found reports table
CREATE TABLE IF NOT EXISTS found_reports (
  id INT AUTO_INCREMENT PRIMARY KEY,
  report_id VARCHAR(32) NOT NULL UNIQUE,
  user_id INT NOT NULL,
  item_name VARCHAR(255) NOT NULL,
  category VARCHAR(100) NOT NULL,
  description TEXT NOT NULL,
  location_found VARCHAR(255) NOT NULL,
  date_found DATE NOT NULL,
  time_found TIME,
  photo_path VARCHAR(500),
  pickup_location VARCHAR(255) NOT NULL,
  contact_email VARCHAR(150) NOT NULL,
  contact_phone VARCHAR(30) NOT NULL,
  status ENUM('Pending', 'Verified', 'Claimed', 'Rejected') DEFAULT 'Pending',
  admin_notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_user_id_found (user_id),
  INDEX idx_status_found (status),
  INDEX idx_report_id_found (report_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE lost_reports (
  id INT AUTO_INCREMENT PRIMARY KEY,
  report_id VARCHAR(20) NOT NULL UNIQUE,
  user_id INT NOT NULL,
  item_name VARCHAR(255) NOT NULL,
  category VARCHAR(100) NOT NULL,
  description TEXT NOT NULL,
  location VARCHAR(255) NOT NULL,
  date_lost DATE NOT NULL,
  time_lost TIME,
  photo_path VARCHAR(500),
  contact_email VARCHAR(150) NOT NULL,
  contact_phone VARCHAR(30) NOT NULL,
  status ENUM('Pending', 'Verified', 'Claimed', 'Rejected') DEFAULT 'Pending',
  admin_notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_user_id (user_id),
  INDEX idx_status (status),
  INDEX idx_report_id (report_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS claim_requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  request_code VARCHAR(20) NOT NULL UNIQUE,
  report_id VARCHAR(32) NOT NULL,
  item_type ENUM('Found','Lost') DEFAULT 'Found',
  user_id INT NOT NULL,
  details TEXT NOT NULL,
  last_seen_location VARCHAR(255) DEFAULT NULL,
  contact_info VARCHAR(255) NOT NULL,
  id_photo_path VARCHAR(500) NOT NULL,
  status ENUM('Pending','Approved','Rejected','Resolved') DEFAULT 'Pending',
  admin_notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_report_claim (report_id),
  INDEX idx_status_claim (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS match_workflows (
  id INT AUTO_INCREMENT PRIMARY KEY,
  lost_report_id VARCHAR(32) NOT NULL,
  found_report_id VARCHAR(32) NOT NULL,
  status ENUM('pending','claimable','claimed','rejected') NOT NULL DEFAULT 'pending',
  admin_id INT DEFAULT NULL,
  notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_match_pair (lost_report_id, found_report_id),
  INDEX idx_status_match (status),
  INDEX idx_lost_match (lost_report_id),
  INDEX idx_found_match (found_report_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS match_results (
  id INT AUTO_INCREMENT PRIMARY KEY,
  claim_request_id INT NOT NULL,
  found_report_id INT NOT NULL,
  confidence TINYINT UNSIGNED NOT NULL,
  classification ENUM('probable','possible') NOT NULL DEFAULT 'possible',
  breakdown JSON NOT NULL,
  status ENUM('pending_review','need_proof','authorized','rejected','claimed') NOT NULL DEFAULT 'pending_review',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (claim_request_id) REFERENCES claim_requests(id) ON DELETE CASCADE,
  FOREIGN KEY (found_report_id) REFERENCES found_reports(id) ON DELETE CASCADE,
  INDEX idx_claim_match (claim_request_id),
  INDEX idx_found_matchresult (found_report_id),
  INDEX idx_status_matchresult (status),
  UNIQUE KEY uq_claim_found (claim_request_id, found_report_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS match_actions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  match_result_id INT NOT NULL,
  action ENUM('request_proof','reject','authorize_pickup','mark_claimed') NOT NULL,
  admin_id INT NOT NULL,
  notes TEXT,
  pickup_location VARCHAR(255),
  pickup_window_start DATETIME,
  pickup_window_end DATETIME,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (match_result_id) REFERENCES match_results(id) ON DELETE CASCADE,
  FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_match_action (match_result_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_logs (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  action_type VARCHAR(64) NOT NULL,
  actor_id INT DEFAULT NULL,
  actor_role ENUM('user','admin','system') NOT NULL DEFAULT 'system',
  subject_type VARCHAR(64) NOT NULL,
  subject_id VARCHAR(64) NOT NULL,
  payload JSON,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_action_type (action_type),
  INDEX idx_actor (actor_id, actor_role),
  INDEX idx_subject (subject_type, subject_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS email_queue (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  recipient VARCHAR(255) NOT NULL,
  subject VARCHAR(255) NOT NULL,
  template VARCHAR(100) NOT NULL,
  payload JSON NOT NULL,
  status ENUM('pending','sending','sent','failed') NOT NULL DEFAULT 'pending',
  attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
  last_error TEXT,
  available_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_email_status (status),
  INDEX idx_email_available (available_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS app_config (
  config_key VARCHAR(100) PRIMARY KEY,
  config_value VARCHAR(255) NOT NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;