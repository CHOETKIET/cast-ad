ALTER TABLE devices
  ADD COLUMN force_resync_token VARCHAR(64) NULL AFTER last_sync_time,
  ADD COLUMN force_resync_requested_at DATETIME NULL AFTER force_resync_token,
  ADD COLUMN force_resync_completed_at DATETIME NULL AFTER force_resync_requested_at;
