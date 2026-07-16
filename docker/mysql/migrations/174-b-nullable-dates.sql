-- #174-B: a '0000-00-00' / '0000-00-00 00:00:00' default-ok cseréje.
-- A séma-diffből (initdb.d/02-schema.sql) 1:1 generálva -> csak LÉTEZŐ,
-- ténylegesen változott oszlopokat érint.
--
-- !!! ELOTTE BACKUP:  bash docker/mysql/dump.sh > backup-PRE-174-B.sql
--
-- Sorrend: (1) sql_mode lazitas, (2) ALTER-ek (az oszlopok nullable-le/
--          NOT NULL DEFAULT CURRENT_TIMESTAMP-pa valnak; a regi 0000-00-00
--          adat egyelore marad), (3) adat-takaritas UPDATE-ekkel. Az ALTER
--          ELOSZOR megy, kulonben egy meg-NOT-NULL oszlop UPDATE-je NULL-ra
--          nem-strict modban csendben visszakenyszerulne 0000-00-00-ra.
--          NOT NULL cel-oszlopoknal a takaritas CURRENT_TIMESTAMP-ra megy.

SET SESSION sql_mode = 'NO_ENGINE_SUBSTITUTION';

USE miserend;

-- ==== 1) Sema: default-ok/nullability atallitasa (a 02-schema.sql-lel azonos) ====

ALTER TABLE `boundaries` MODIFY COLUMN `created_at` DATE NULL DEFAULT NULL;
ALTER TABLE `boundaries` MODIFY COLUMN `updated_at` DATE NULL DEFAULT NULL;

ALTER TABLE `chat` MODIFY COLUMN `datum` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `church_holders` MODIFY COLUMN `updated_at` timestamp NULL DEFAULT NULL;

ALTER TABLE `crons` MODIFY COLUMN `deadline_at` timestamp NULL DEFAULT NULL;
ALTER TABLE `crons` MODIFY COLUMN `lastsuccess_at` timestamp NULL DEFAULT NULL;
ALTER TABLE `crons` MODIFY COLUMN `created_at` timestamp NULL DEFAULT NULL;
ALTER TABLE `crons` MODIFY COLUMN `updated_At` timestamp NULL DEFAULT NULL;

ALTER TABLE `distances` MODIFY COLUMN `updated_at` timestamp NULL DEFAULT NULL;
ALTER TABLE `distances` MODIFY COLUMN `created_at` timestamp NULL DEFAULT NULL;

ALTER TABLE `emails` MODIFY COLUMN `updated_at` timestamp NULL DEFAULT NULL;

ALTER TABLE `favorites` MODIFY COLUMN `created_at` timestamp NULL DEFAULT NULL;
ALTER TABLE `favorites` MODIFY COLUMN `updated_at` timestamp NULL DEFAULT NULL;

ALTER TABLE `keyword_shortcuts` MODIFY COLUMN `created_at` timestamp NULL DEFAULT NULL;
ALTER TABLE `keyword_shortcuts` MODIFY COLUMN `updated_at` timestamp NULL DEFAULT NULL;

ALTER TABLE `lookup_boundary_church` MODIFY COLUMN `created_at` timestamp NULL DEFAULT NULL;
ALTER TABLE `lookup_boundary_church` MODIFY COLUMN `updated_at` timestamp NULL DEFAULT NULL;

ALTER TABLE `lookup_church_osm` MODIFY COLUMN `created_at` timestamp NULL DEFAULT NULL;
ALTER TABLE `lookup_church_osm` MODIFY COLUMN `updated_at` timestamp NULL DEFAULT NULL;

ALTER TABLE `lookup_osm_enclosed` MODIFY COLUMN `created_at` varchar(45) DEFAULT NULL;
ALTER TABLE `lookup_osm_enclosed` MODIFY COLUMN `updated_at` timestamp NULL DEFAULT NULL;

ALTER TABLE `osm` MODIFY COLUMN `created_at` timestamp NULL DEFAULT NULL;
ALTER TABLE `osm` MODIFY COLUMN `updated_at` timestamp NULL DEFAULT NULL;

ALTER TABLE `osmtags` MODIFY COLUMN `created_at` timestamp NULL DEFAULT NULL;
ALTER TABLE `osmtags` MODIFY COLUMN `updated_at` timestamp NULL DEFAULT NULL;

ALTER TABLE `photos` MODIFY COLUMN `created_at` timestamp NULL DEFAULT NULL;
ALTER TABLE `photos` MODIFY COLUMN `updated_at` timestamp NULL DEFAULT NULL;

ALTER TABLE `remarks` MODIFY COLUMN `admindatum` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `remarks` MODIFY COLUMN `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `remarks` MODIFY COLUMN `updated_at` timestamp NULL DEFAULT NULL;

ALTER TABLE `templomok` MODIFY COLUMN `frissites` date NULL DEFAULT NULL;
ALTER TABLE `templomok` MODIFY COLUMN `created_at` timestamp NULL DEFAULT NULL;
ALTER TABLE `templomok` MODIFY COLUMN `moddatum` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `templomok` MODIFY COLUMN `updated_at` timestamp NULL DEFAULT NULL;

ALTER TABLE `templomok_full` MODIFY COLUMN `frissites` date NULL DEFAULT NULL;
ALTER TABLE `templomok_full` MODIFY COLUMN `created_at` timestamp NULL DEFAULT NULL;
ALTER TABLE `templomok_full` MODIFY COLUMN `moddatum` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `templomok_full` MODIFY COLUMN `updated_at` timestamp NULL DEFAULT NULL;

ALTER TABLE `tokens` MODIFY COLUMN `created_at` timestamp NULL DEFAULT NULL;
ALTER TABLE `tokens` MODIFY COLUMN `updated_at` timestamp NULL DEFAULT NULL;

ALTER TABLE `user` MODIFY COLUMN `regdatum` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `user` MODIFY COLUMN `lastlogin` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP;

-- ==== 2) Adat-takaritas: a megmaradt 0000-00-00 ertekek cseréje ====

UPDATE `boundaries` SET `created_at` = NULL WHERE `created_at` IN ('0000-00-00','0000-00-00 00:00:00');
UPDATE `boundaries` SET `updated_at` = NULL WHERE `updated_at` IN ('0000-00-00','0000-00-00 00:00:00');

UPDATE `chat` SET `datum` = CURRENT_TIMESTAMP WHERE `datum` IN ('0000-00-00','0000-00-00 00:00:00') OR `datum` IS NULL;

UPDATE `church_holders` SET `updated_at` = NULL WHERE `updated_at` IN ('0000-00-00','0000-00-00 00:00:00');

UPDATE `crons` SET `deadline_at` = NULL WHERE `deadline_at` IN ('0000-00-00','0000-00-00 00:00:00');
UPDATE `crons` SET `lastsuccess_at` = NULL WHERE `lastsuccess_at` IN ('0000-00-00','0000-00-00 00:00:00');
UPDATE `crons` SET `created_at` = NULL WHERE `created_at` IN ('0000-00-00','0000-00-00 00:00:00');
UPDATE `crons` SET `updated_At` = NULL WHERE `updated_At` IN ('0000-00-00','0000-00-00 00:00:00');

UPDATE `distances` SET `updated_at` = NULL WHERE `updated_at` IN ('0000-00-00','0000-00-00 00:00:00');
UPDATE `distances` SET `created_at` = NULL WHERE `created_at` IN ('0000-00-00','0000-00-00 00:00:00');

UPDATE `emails` SET `updated_at` = NULL WHERE `updated_at` IN ('0000-00-00','0000-00-00 00:00:00');

UPDATE `favorites` SET `created_at` = NULL WHERE `created_at` IN ('0000-00-00','0000-00-00 00:00:00');
UPDATE `favorites` SET `updated_at` = NULL WHERE `updated_at` IN ('0000-00-00','0000-00-00 00:00:00');

UPDATE `keyword_shortcuts` SET `created_at` = NULL WHERE `created_at` IN ('0000-00-00','0000-00-00 00:00:00');
UPDATE `keyword_shortcuts` SET `updated_at` = NULL WHERE `updated_at` IN ('0000-00-00','0000-00-00 00:00:00');

UPDATE `lookup_boundary_church` SET `created_at` = NULL WHERE `created_at` IN ('0000-00-00','0000-00-00 00:00:00');
UPDATE `lookup_boundary_church` SET `updated_at` = NULL WHERE `updated_at` IN ('0000-00-00','0000-00-00 00:00:00');

UPDATE `lookup_church_osm` SET `created_at` = NULL WHERE `created_at` IN ('0000-00-00','0000-00-00 00:00:00');
UPDATE `lookup_church_osm` SET `updated_at` = NULL WHERE `updated_at` IN ('0000-00-00','0000-00-00 00:00:00');

UPDATE `lookup_osm_enclosed` SET `created_at` = NULL WHERE `created_at` IN ('0000-00-00','0000-00-00 00:00:00');
UPDATE `lookup_osm_enclosed` SET `updated_at` = NULL WHERE `updated_at` IN ('0000-00-00','0000-00-00 00:00:00');

UPDATE `osm` SET `created_at` = NULL WHERE `created_at` IN ('0000-00-00','0000-00-00 00:00:00');
UPDATE `osm` SET `updated_at` = NULL WHERE `updated_at` IN ('0000-00-00','0000-00-00 00:00:00');

UPDATE `osmtags` SET `created_at` = NULL WHERE `created_at` IN ('0000-00-00','0000-00-00 00:00:00');
UPDATE `osmtags` SET `updated_at` = NULL WHERE `updated_at` IN ('0000-00-00','0000-00-00 00:00:00');

UPDATE `photos` SET `created_at` = NULL WHERE `created_at` IN ('0000-00-00','0000-00-00 00:00:00');
UPDATE `photos` SET `updated_at` = NULL WHERE `updated_at` IN ('0000-00-00','0000-00-00 00:00:00');

UPDATE `remarks` SET `admindatum` = CURRENT_TIMESTAMP WHERE `admindatum` IN ('0000-00-00','0000-00-00 00:00:00') OR `admindatum` IS NULL;
UPDATE `remarks` SET `created_at` = CURRENT_TIMESTAMP WHERE `created_at` IN ('0000-00-00','0000-00-00 00:00:00') OR `created_at` IS NULL;
UPDATE `remarks` SET `updated_at` = NULL WHERE `updated_at` IN ('0000-00-00','0000-00-00 00:00:00');

UPDATE `templomok` SET `frissites` = NULL WHERE `frissites` IN ('0000-00-00','0000-00-00 00:00:00');
UPDATE `templomok` SET `created_at` = NULL WHERE `created_at` IN ('0000-00-00','0000-00-00 00:00:00');
UPDATE `templomok` SET `moddatum` = CURRENT_TIMESTAMP WHERE `moddatum` IN ('0000-00-00','0000-00-00 00:00:00') OR `moddatum` IS NULL;
UPDATE `templomok` SET `updated_at` = NULL WHERE `updated_at` IN ('0000-00-00','0000-00-00 00:00:00');

UPDATE `templomok_full` SET `frissites` = NULL WHERE `frissites` IN ('0000-00-00','0000-00-00 00:00:00');
UPDATE `templomok_full` SET `created_at` = NULL WHERE `created_at` IN ('0000-00-00','0000-00-00 00:00:00');
UPDATE `templomok_full` SET `moddatum` = CURRENT_TIMESTAMP WHERE `moddatum` IN ('0000-00-00','0000-00-00 00:00:00') OR `moddatum` IS NULL;
UPDATE `templomok_full` SET `updated_at` = NULL WHERE `updated_at` IN ('0000-00-00','0000-00-00 00:00:00');

UPDATE `tokens` SET `created_at` = NULL WHERE `created_at` IN ('0000-00-00','0000-00-00 00:00:00');
UPDATE `tokens` SET `updated_at` = NULL WHERE `updated_at` IN ('0000-00-00','0000-00-00 00:00:00');

UPDATE `user` SET `regdatum` = CURRENT_TIMESTAMP WHERE `regdatum` IN ('0000-00-00','0000-00-00 00:00:00') OR `regdatum` IS NULL;
UPDATE `user` SET `lastlogin` = CURRENT_TIMESTAMP WHERE `lastlogin` IN ('0000-00-00','0000-00-00 00:00:00') OR `lastlogin` IS NULL;

-- Osszesen 42 oszlop, 19 tabla.
