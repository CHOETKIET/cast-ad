CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  display_name VARCHAR(150) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE devices (
  id INT AUTO_INCREMENT PRIMARY KEY,
  device_id VARCHAR(100) NOT NULL UNIQUE,
  device_name VARCHAR(150) NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'offline',
  last_seen DATETIME NULL,
  app_version VARCHAR(50) NULL,
  current_playlist_version INT NOT NULL DEFAULT 0,
  last_sync_time DATETIME NULL,
  force_resync_token VARCHAR(64) NULL,
  force_resync_requested_at DATETIME NULL,
  force_resync_completed_at DATETIME NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_devices_last_seen (last_seen),
  INDEX idx_devices_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE media (
  id INT AUTO_INCREMENT PRIMARY KEY,
  media_type VARCHAR(20) NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  file_name VARCHAR(255) NOT NULL,
  file_path VARCHAR(500) NOT NULL,
  public_url VARCHAR(500) NOT NULL,
  mime_type VARCHAR(100) NOT NULL,
  file_size BIGINT NOT NULL DEFAULT 0,
  width INT NULL,
  height INT NULL,
  duration_seconds INT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_media_type (media_type),
  INDEX idx_media_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE playlists (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  description TEXT NULL,
  version INT NOT NULL DEFAULT 1,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_playlists_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE playlist_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  playlist_id INT NOT NULL,
  media_id INT NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  duration_seconds INT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_playlist_items_playlist_id (playlist_id),
  INDEX idx_playlist_items_media_id (media_id),
  CONSTRAINT fk_playlist_items_playlist
    FOREIGN KEY (playlist_id) REFERENCES playlists(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_playlist_items_media
    FOREIGN KEY (media_id) REFERENCES media(id)
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE device_playlist (
  id INT AUTO_INCREMENT PRIMARY KEY,
  device_id INT NOT NULL,
  playlist_id INT NOT NULL,
  assigned_at DATETIME NOT NULL,
  assigned_by INT NULL,
  UNIQUE KEY uniq_device_playlist_device (device_id),
  INDEX idx_device_playlist_playlist_id (playlist_id),
  CONSTRAINT fk_device_playlist_device
    FOREIGN KEY (device_id) REFERENCES devices(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_device_playlist_playlist
    FOREIGN KEY (playlist_id) REFERENCES playlists(id)
    ON DELETE RESTRICT,
  CONSTRAINT fk_device_playlist_user
    FOREIGN KEY (assigned_by) REFERENCES users(id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
