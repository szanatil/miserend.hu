/*
 * Migrations for existing databases.
 * These ALTER TABLE statements are safe to run multiple times (IF NOT EXISTS guards where possible).
 * For fresh Docker setups, 02-schema.sql already includes these columns.
 */

USE miserend;

-- Add boundaries_checked_at to templomok table
-- Tracks when the boundary check (checkBoundariesForOne) was last run for each church.
-- NULL means never checked. Used by checkBoundaries() to prioritize which churches to process next.
-- Reset to NULL automatically when lat/lon coordinates change (Church::save()).
ALTER TABLE `templomok`
    ADD COLUMN IF NOT EXISTS `boundaries_checked_at` TIMESTAMP NULL DEFAULT NULL AFTER `updated_at`;

-- Index for efficient ordering in checkBoundaries() query
ALTER TABLE `templomok`
    ADD INDEX IF NOT EXISTS `boundaries_checked_at` (`boundaries_checked_at`);

-- Create church_relationships table
-- Stores hierarchical relationships between churches.
-- The relationship is always interpreted bottom-up: the child belongs to the parent.
CREATE TABLE IF NOT EXISTS `church_relationships` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_church_id` int(11) NOT NULL COMMENT 'felsőbbrendű misézőhely',
  `child_church_id`  int(11) NOT NULL COMMENT 'alsóbbrendű misézőhely',
  `type` enum(
    'subordinate',
    'associated',
    'territorially_independent'
  ) NOT NULL COMMENT 'kapcsolat típusa',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_pair` (`parent_church_id`, `child_church_id`),
  KEY `parent_idx` (`parent_church_id`),
  KEY `child_idx`  (`child_church_id`),
  CONSTRAINT `fk_cr_parent` FOREIGN KEY (`parent_church_id`)
    REFERENCES `templomok` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cr_child`  FOREIGN KEY (`child_church_id`)
    REFERENCES `templomok` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;
