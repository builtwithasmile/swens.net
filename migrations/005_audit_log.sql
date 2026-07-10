-- audit_log: append-only trail of privileged admin actions (single-owner
-- model, so `actor` is always the owner's email — no separate admin-users
-- table to join against).

CREATE TABLE IF NOT EXISTS audit_log (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  actor      VARCHAR(160) NOT NULL,
  action     VARCHAR(60)  NOT NULL,
  subject    VARCHAR(120) NULL,
  meta       VARCHAR(500) NULL,
  ip         VARCHAR(45)  NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_audit_created (created_at),
  KEY idx_audit_action (action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
