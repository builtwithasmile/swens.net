CREATE TABLE IF NOT EXISTS media (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  post_id       INT UNSIGNED NOT NULL,
  filename      VARCHAR(80)  NOT NULL,
  original_name VARCHAR(160) NOT NULL,
  mime          VARCHAR(64)  NOT NULL,
  width         SMALLINT UNSIGNED NOT NULL,
  height        SMALLINT UNSIGNED NOT NULL,
  bytes         INT UNSIGNED NOT NULL,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_post (post_id),
  CONSTRAINT fk_media_post FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
