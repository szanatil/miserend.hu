# Mass Definitions Generator

## Leírás

A `generate-mass-definitions.js` script automatikusan generálja a `webapp/mass-definitions.json` fájlt az Angular build során. Ez a JSON fájl tartalmazza az összes misekategória, rite és tömegdefiníció metaadatait, amely a webapp és API számára használható.

## Működés

### Automatikus futtatás (ajánlott)

Az npm build script már integrálta a generálást:

```bash
cd calendar
npm run build
```

Ez futtatja:
1. `ng build` - Angular alkalmazás fordítása
2. `node scripts/generate-mass-definitions.js` - JSON generálása

### Kézi futtatás

Ha szükséges, futtathatod közvetlenül:

```bash
cd calendar
node scripts/generate-mass-definitions.js
```

### Watcher módban (desenvolvimento)

A `npm start:integrated` parancs watcher módban futtatja az Angular buildet és automatikusan generálja a JSON-t minden egyes fordítás után:

```bash
cd calendar
npm run start:integrated
```

## Előfeltételek

A TypeScript runtime futtatásához szükséges az egyik:

### Option 1: tsx (ajánlott - könnyebb)
```bash
npm install --save-dev tsx
```

### Option 2: ts-node
```bash
npm install --save-dev ts-node
```

A script automatikusan felismeri, hogy melyik van telepítve és azt használja.

## Kimenet

### Fájl helye
```
webapp/mass-definitions.json
```

### JSON struktúra
```json
{
  "_generator": "mass-definitions-export.ts",
  "_warning": "Auto-generated from calendar/src/app/data/mass-definitions.ts during Angular build",
  "_buildDate": "2026-05-27T06:48:00.000Z",
  "categories": [...],
  "rites": [...],
  "definitions": [...],
  "titlesByCategory": {...},
  "titlesByRite": {...}
}
```

### Tartalom
- **categories**: Tömegkategóriák (MASS, ADORATION, CONFESSION, stb.)
- **rites**: Egyházi rítusok (ROMAN_CATHOLIC, ORTHODOX, stb.)
- **definitions**: Részletes tömeg definíciók (i18n key, kategória, rítusok, stb.)
- **titlesByCategory**: Tömegnevek csoportosítva kategória szerint
- **titlesByRite**: Tömegnevek csoportosítva rítus szerint

## Forrásfájlok

A script az alábbi TypeScript fájlokra támaszkodik:

1. **calendar/src/app/data/mass-definitions.ts**
   - A MASS_DEFINITIONS_DATA objektum tartalmazza az összes adatot

2. **calendar/src/app/data/mass-definitions-export.ts**
   - A `generateMassDefinitionsJson()` függvény végzi az exportálást

## Troubleshooting

### "No TypeScript runtime found"

Telepítsd az tsx vagy ts-node-ot:
```bash
cd calendar
npm install --save-dev tsx
```

### "Build output not found"

Győződj meg, hogy az Angular build sikeresen befejeződött:
```bash
cd calendar
npm run build
```

### Fallback mód (hibakezelés)

Ha a TypeScript runtime nem érhető el és az `ALLOW_FALLBACK=1` environment variable be van állítva, a script egy minimális fallback JSON-t generál:

```bash
cd calendar
ALLOW_FALLBACK=1 npm run build
```

## Integráció

### Docker deploy

A `calendar/build-and-deploy.js` watcher automatikusan futtatja a scriptet az Angular build után, majd elindítja a Python deploy scriptet.

### CI/CD

A build script futási sorrendje:
1. Angular build (`ng build`)
2. JSON generálás (`node scripts/generate-mass-definitions.js`)
3. (Opcionális) Docker deploy (`python ../docker/miserend/calendar_deploy.py`)

## Verzió

- **Script verzió**: 1.0.0
- **Frissítve**: 2026-05-27
- **Kompatibilis**: Node.js 18+, Angular 19+
