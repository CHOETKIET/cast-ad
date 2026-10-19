ALTER TABLE media
  ADD COLUMN checksum VARCHAR(64) NULL AFTER file_size;
