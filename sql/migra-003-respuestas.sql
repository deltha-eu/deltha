ALTER TABLE comments ADD COLUMN parent_id INT UNSIGNED DEFAULT NULL AFTER story_id, ADD COLUMN root_id INT UNSIGNED DEFAULT NULL AFTER parent_id, ADD KEY idx_root (story_id, root_id, created);
