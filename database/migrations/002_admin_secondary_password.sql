ALTER TABLE users
    ADD COLUMN secondary_password_hash VARCHAR(255) NULL AFTER password_hash,
    ADD COLUMN secondary_password_required TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 AFTER secondary_password_hash,
    ADD COLUMN secondary_password_changed_at TIMESTAMP NULL AFTER secondary_password_required;
