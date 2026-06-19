CREATE TABLE IF NOT EXISTS posts (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  building    VARCHAR(24)  NOT NULL,
  tier        ENUM('public','keyed') NOT NULL DEFAULT 'public',
  kind        VARCHAR(24)  NOT NULL DEFAULT 'note',
  title       VARCHAR(160) NOT NULL,
  slug        VARCHAR(160) NOT NULL,
  body_md     MEDIUMTEXT   NOT NULL,
  tags        VARCHAR(255) NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_building_slug (building, slug),
  KEY idx_feed (building, tier, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
