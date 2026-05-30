-- #174-B: NULL-bel cseréljük a régi '0000-00-00' és '0000-00-00 00:00:00'
-- default értékeket. A séma-fájl (initdb.d/02-schema.sql) a fresh installra
-- már jól van, de production-ben kézzel kell futtatni ezt a migration-t.
--
-- ELŐTTE: készíts dump-ot! `bash docker/mysql/dump.sh > backup-PRE-174-B.sql`
--
-- Két részből áll:
--   1) ALTER TABLE-ek: oszlopok átállítása (NULL DEFAULT NULL vagy
--      NOT NULL DEFAULT CURRENT_TIMESTAMP)
--   2) UPDATE-ek: a meglévő '0000-00-00' és '0000-00-00 00:00:00' adatok
--      NULL-re cserélése (különben a NOT NULL constraint megsérül)
--
-- Az UPDATE-ek ELŐSZÖR mennek, mert a régi nem-érvényes default-érték
-- nélkül nem tudja a séma elfogadni az ALTER-t.

USE miserend;

-- ============================================================================
-- 1. UPDATE: meglévő invalid dátum-értékek NULL-re
-- ============================================================================

UPDATE boundaries SET created_at = NULL WHERE created_at = '0000-00-00';
UPDATE boundaries SET updated_at = NULL WHERE updated_at = '0000-00-00';

UPDATE chat SET datum = NULL WHERE datum = '0000-00-00 00:00:00';

UPDATE church_holders SET updated_at = NULL WHERE updated_at = '0000-00-00 00:00:00';

UPDATE crons SET deadline_at    = NULL WHERE deadline_at    = '0000-00-00 00:00:00';
UPDATE crons SET lastsuccess_at = NULL WHERE lastsuccess_at = '0000-00-00 00:00:00';
UPDATE crons SET created_at     = NULL WHERE created_at     = '0000-00-00 00:00:00';
UPDATE crons SET updated_At     = NULL WHERE updated_At     = '0000-00-00 00:00:00';

UPDATE distances SET updated_at = NULL WHERE updated_at = '0000-00-00 00:00:00';
UPDATE distances SET created_at = NULL WHERE created_at = '0000-00-00 00:00:00';

UPDATE egyhazmegye SET updated_at = NULL WHERE updated_at = '0000-00-00 00:00:00';

UPDATE remarks SET created_at = NULL WHERE created_at = '0000-00-00 00:00:00';
UPDATE remarks SET updated_at = NULL WHERE updated_at = '0000-00-00 00:00:00';

UPDATE settings SET created_at = NULL WHERE created_at = '0000-00-00 00:00:00';
UPDATE settings SET updated_at = NULL WHERE updated_at = '0000-00-00 00:00:00';

UPDATE stats_externalapi SET created_at = NULL WHERE created_at = '0000-00-00 00:00:00';
UPDATE stats_externalapi SET updated_at = NULL WHERE updated_at = '0000-00-00 00:00:00';

UPDATE templomok SET frissites = NULL WHERE frissites = '0000-00-00';

UPDATE templomok_hist SET frissites = NULL WHERE frissites = '0000-00-00';

UPDATE osmtags SET created_at = NULL WHERE created_at = '0000-00-00 00:00:00';
UPDATE osmtags SET updated_at = NULL WHERE updated_at = '0000-00-00 00:00:00';

UPDATE users SET created_at = NULL WHERE created_at = '0000-00-00 00:00:00';
UPDATE users SET updated_at = NULL WHERE updated_at = '0000-00-00 00:00:00';

-- ============================================================================
-- 2. ALTER TABLE: default-ok átállítása NULL-re vagy CURRENT_TIMESTAMP-ra
-- ============================================================================

ALTER TABLE boundaries
    MODIFY COLUMN created_at DATE NULL DEFAULT NULL,
    MODIFY COLUMN updated_at DATE NULL DEFAULT NULL;

ALTER TABLE chat
    MODIFY COLUMN datum datetime NOT NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE church_holders
    MODIFY COLUMN updated_at timestamp NULL DEFAULT NULL;

ALTER TABLE crons
    MODIFY COLUMN deadline_at    timestamp NULL DEFAULT NULL,
    MODIFY COLUMN lastsuccess_at timestamp NULL DEFAULT NULL,
    MODIFY COLUMN created_at     timestamp NULL DEFAULT NULL,
    MODIFY COLUMN updated_At     timestamp NULL DEFAULT NULL;

ALTER TABLE distances
    MODIFY COLUMN updated_at timestamp NULL DEFAULT NULL,
    MODIFY COLUMN created_at timestamp NULL DEFAULT NULL;

ALTER TABLE egyhazmegye
    MODIFY COLUMN updated_at timestamp NULL DEFAULT NULL;

ALTER TABLE remarks
    MODIFY COLUMN created_at timestamp NULL DEFAULT NULL,
    MODIFY COLUMN updated_at timestamp NULL DEFAULT NULL;

ALTER TABLE settings
    MODIFY COLUMN created_at timestamp NULL DEFAULT NULL,
    MODIFY COLUMN updated_at timestamp NULL DEFAULT NULL;

ALTER TABLE stats_externalapi
    MODIFY COLUMN created_at timestamp NULL DEFAULT NULL,
    MODIFY COLUMN updated_at timestamp NULL DEFAULT NULL;

ALTER TABLE templomok
    MODIFY COLUMN frissites DATE NULL DEFAULT NULL;

ALTER TABLE templomok_hist
    MODIFY COLUMN frissites DATE NULL DEFAULT NULL;

ALTER TABLE osmtags
    MODIFY COLUMN created_at timestamp NULL DEFAULT NULL,
    MODIFY COLUMN updated_at timestamp NULL DEFAULT NULL;

ALTER TABLE users
    MODIFY COLUMN created_at timestamp NULL DEFAULT NULL,
    MODIFY COLUMN updated_at timestamp NULL DEFAULT NULL;

-- ============================================================================
-- 3. Ellenőrzés (futtatás után érdemes lekérdezni)
-- ============================================================================

-- Maradt-e '0000-00-00'-szal sehol érték?
-- SELECT 'templomok.frissites' AS tabla_oszlop, COUNT(*) AS db
--   FROM templomok WHERE frissites = '0000-00-00'
-- UNION ALL SELECT 'crons.deadline_at', COUNT(*) FROM crons WHERE deadline_at = '0000-00-00 00:00:00';
-- (és így tovább minden táblára)
