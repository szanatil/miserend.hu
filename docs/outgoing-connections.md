# Kimenő hálózati kapcsolatok

> Ez a lista azokat a külső host:port-okat foglalja össze, amikre a miserend.hu szerver fut-időben rácsatlakozik.
> Hasznos szerver-firewall, kimenő-allow-list, container egress-policy beállítások előtt — egyenként engedélyezős környezetben ezek hiányában a telepítés ripityára esik.
>
> Tracker: #214

## Külső API-k

| Cél | Host | Port | Mire kell | Forrás (osztály) |
|---|---|---|---|---|
| **OSM Nominatim** | `nominatim.openstreetmap.org` | 443 | Cím → koordináta keresés (geocoding) | `\ExternalApi\NominatimApi` |
| **OSM Overpass** | `overpass-api.de` | 80 | Templom-elemek és határok lekérése OSM-ből (`url:miserend` tag) | `\ExternalApi\OverpassApi` |
| **OSM opening_hours evaluator** | `openingh.openstreetmap.de` | 443 | `opening_hours` mező parse / evaluate | `\ExternalApi\OpeninghApi` |
| **OSM OAuth** | `master.apis.dev.openstreetmap.org` | 443 | OSM OAuth flow (admin) | `\ExternalApi\OpenStreetMapApi` |
| **Mapquest Directions** | `open.mapquestapi.com` | 80 | Útvonal-távolság számítás templomok között (cron) | `\ExternalApi\MapquestApi` |
| **Közösségek API** | `kozossegek.hu` | 443 | Templomhoz tartozó közösségek lekérése | `\ExternalApi\KozossegekApi` |
| **Szentségimádások** | `szentsegimadas.hu` | 443 | Adoráció / szentségimádás-időpontok | `\ExternalApi\SzentsegimadasApi` |
| **Napi lelki batyu** | `szentjozsefhackathon.github.io` | 443 | Napi lelki batyu (idézet / olvasmány) | `\ExternalApi\NapiLelkibatyuApi` |

## Belső szolgáltatások (Docker compose network)

| Cél | Host | Port | Mire kell |
|---|---|---|---|
| **Elasticsearch** | `elasticsearch` | 9200 | Mise / templom kereső index |
| **MariaDB** | `mysql` | 3306 | Fő adatbázis |
| **Mailcatcher** | `mailcatcher` | 1025 (SMTP) / 1080 (web) | Dev környezetben email-elfogás |

## SMTP (production)

Production-ben a SMTP a `.env` `SMTP_HOST` / `SMTP_PORT` szerint (config.php `production` ág). Engedélyezni kell:

| Cél | Host | Port |
|---|---|---|
| SMTP relay | (config-tól függ) | 25 / 465 / 587 |

## Container image registry-k

Build / pull időben kellhet:

| Cél | Host | Port | Mire kell |
|---|---|---|---|
| GitHub Container Registry | `ghcr.io` | 443 | A `miserend.hu` image pull |
| Docker Hub | `registry-1.docker.io`, `auth.docker.io` | 443 | `mariadb`, `node`, `busybox`, `dockage/mailcatcher` |
| Elastic Registry | `docker.elastic.co` | 443 | ES image pull |

## Build-time (csak fejlesztői gépen)

| Cél | Host | Port | Mire kell |
|---|---|---|---|
| npm registry | `registry.npmjs.org` | 443 | Angular dep (`calendar/`) |
| Packagist | `packagist.org`, `repo.packagist.org` | 443 | Composer dep |
| nodejs.org | `nodejs.org` | 443 | Node binary (Docker build esetén) |
| GitHub | `github.com`, `api.github.com`, `codeload.github.com` | 443 | git clone / composer github source |

---

## Karbantartás

Ha új külső API integrációt vezetsz be:
1. Vedd fel ide a host-ot és a célt
2. A `\ExternalApi\...` osztály implementációja a `webapp/classes/externalapi/` alatt szokott lenni
3. Production-allow-list frissítéséhez küldj jelzést a szerver-üzemeltetőnek

Hiányzik valami? Nézd át a `webapp/classes/externalapi/` mappa fájljait, a `$apiUrl` mezőket — ez a lista azokból generálódott.
