-- Keyed visitor system — the people Josh knows (Phase C/D of the Porch build plan).
-- members: invited circle. A key = approval (Josh issues it).
-- checkins: the presence board. Friends' own words (provenance-clean by construction).
-- The keyed story content reuses `posts` with tier='keyed' (seeded by bin/seed-keyed.php).

CREATE TABLE IF NOT EXISTS members (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  email        VARCHAR(160) NOT NULL,
  display_name VARCHAR(80)  NOT NULL,
  relationship VARCHAR(80)  NULL,
  status       ENUM('pending','approved','revoked') NOT NULL DEFAULT 'pending',
  key_token    CHAR(64)     NOT NULL,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  approved_at  TIMESTAMP NULL DEFAULT NULL,
  last_seen_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_member_token (key_token),
  UNIQUE KEY uq_member_email (email),
  KEY idx_member_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS checkins (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  member_id  INT UNSIGNED NOT NULL,
  body       VARCHAR(2000) NOT NULL,
  mood       VARCHAR(40)  NULL,
  media_id   INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_checkins_created (created_at),
  CONSTRAINT fk_checkin_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
  CONSTRAINT fk_checkin_media  FOREIGN KEY (media_id)  REFERENCES media(id)   ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
