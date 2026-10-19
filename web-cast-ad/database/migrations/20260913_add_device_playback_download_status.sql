ALTER TABLE devices
  ADD COLUMN current_playlist_item_id INT NULL AFTER current_playlist_version,
  ADD COLUMN current_media_id INT NULL AFTER current_playlist_item_id,
  ADD COLUMN current_media_type VARCHAR(20) NULL AFTER current_media_id,
  ADD COLUMN last_playback_time DATETIME NULL AFTER current_media_type,
  ADD COLUMN last_download_status VARCHAR(20) NULL AFTER last_sync_time,
  ADD COLUMN last_download_message VARCHAR(255) NULL AFTER last_download_status,
  ADD COLUMN last_download_time DATETIME NULL AFTER last_download_message,
  ADD COLUMN storage_available_bytes BIGINT NULL AFTER last_download_time,
  ADD INDEX idx_devices_last_playback_time (last_playback_time);
