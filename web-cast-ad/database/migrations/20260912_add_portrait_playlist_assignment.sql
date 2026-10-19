ALTER TABLE device_playlist
  ADD COLUMN portrait_playlist_id INT NULL AFTER playlist_id,
  ADD INDEX idx_device_playlist_portrait_playlist_id (portrait_playlist_id),
  ADD CONSTRAINT fk_device_playlist_portrait_playlist
    FOREIGN KEY (portrait_playlist_id) REFERENCES playlists(id)
    ON DELETE RESTRICT;
