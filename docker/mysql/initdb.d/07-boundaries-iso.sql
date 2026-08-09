/*
 * #498: az országkód tárolása a határon.
 *
 * Eddig az „ország -> kód" leképezés KIZÁRÓLAG a régi `templomok.orszag` oszlopon
 * keresztül létezett (az `orszagok` táblában nincs ISO-kód, csak `telkod`). Ezért
 * az oszlop kivezetése magával vitte volna a statisztikát (`stat.php`: orszag=12)
 * és az Angular naptárnak átadott országkódot is.
 *
 * Az OSM országrelációi viszont hordozzák, ellenőrizve:
 *   rel/21335 Magyarország  ISO3166-1=HU
 *   rel/90689 România       ISO3166-1=RO
 *   rel/14296 Slovensko     ISO3166-1=SK
 *
 * Ez a fájl a MEGLÉVŐ adatbázisokhoz kell (a 02-schema már eleve tartalmazza az
 * oszlopot az újonnan létrehozottaknál).
 *
 * ÉLES ADATBÁZISON KÉZZEL, mert nincs migrációs rendszerünk:
 *   ALTER TABLE boundaries ADD COLUMN IF NOT EXISTS `iso3166_1` varchar(2) NULL DEFAULT NULL;
 *   ALTER TABLE boundaries ADD INDEX IF NOT EXISTS `index3` (`iso3166_1`);
 * Az oszlop a következő boundary-szinkronnál töltődik fel magától; addig NULL,
 * amit minden hívó kezel.
 */

USE miserend;

ALTER TABLE `boundaries`
    ADD COLUMN IF NOT EXISTS `iso3166_1` varchar(2) NULL DEFAULT NULL AFTER `denomination`;

ALTER TABLE `boundaries`
    ADD INDEX IF NOT EXISTS `index3` (`iso3166_1`);
