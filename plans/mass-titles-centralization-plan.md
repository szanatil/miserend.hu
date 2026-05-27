# MASS_TITLE Centralizációs Terv

## I. JELENLEGI ADATSTRUKTÚRA ELEMZÉSE

### 1. Szétszórt definíciók helye

#### A. `calendar/src/app/enum/mass-title-category.ts`
- **Tartalom**: 4 kategória enum
```typescript
export enum MassTitleCategory {
  MASS = 'MASS',
  ADORATION = 'ADORATION',
  CONFESSION = 'CONFESSION',
  OTHER = 'OTHER'
}
```

#### B. `calendar/src/app/util/mass-title-category-config.ts`
- **Tartalom**: 
  - `CATEGORY_COLORS`: kategória → szín mapping
  - `CATEGORY_TITLES`: kategória → i18n key lista mapping
  - `CATEGORY_TITLES` felépítése:
    - MASS: 11 title (Holy Mass, Liturgy of Word, Divine Liturgy, Presanctified Gifts, Lord's Supper, Good Friday, Easter Vigil, Traditional variációk)
    - ADORATION: 1 title
    - CONFESSION: 1 title
    - OTHER: 5 title (Breviary, Rosary, Litany, Matins, Vespres)

#### C. `calendar/src/app/util/mass-util.ts`
- **Tartalom**: `getTitles(rite: Rite)` függvény rite-alapú szűréshez
  - **TRADITIONAL rite**: 4 title (Traditional Latin Mass, Traditional sorozat)
  - **GREEK_CATHOLIC rite**: 5 title (Divine Liturgy, Presanctified Gifts, Matins, Vespres, Confession)
  - **ROMAN_CATHOLIC (default)**: 10 title (Holy Mass, Liturgy of Word, Adoration, Confession, Breviary, Rosary, Litany, Lord's Supper, Good Friday, Easter Vigil)

#### D. Másodlagos helyek
- `calendar/src/app/components/add-full-event-dialog/add-full-event-dialog.component.ts`:
  - Easter speciális title-ok leképezése
  - Removals lista (Easter-tól kizárandó title-ok)

### 2. Azonosított MASS_TITLE-k (összes)

**Teljes lista (17 egyedi i18n key):**

| I18n Key | Kategória | ROMAN_CATHOLIC | GREEK_CATHOLIC | TRADITIONAL |
|----------|-----------|---|---|---|
| MASS_TITLE.HOLY_MASS | MASS | ✓ | ✗ | ✗ |
| MASS_TITLE.LITURGY_OF_THE_WORD | MASS | ✓ | ✗ | ✗ |
| MASS_TITLE.DIVINE_LITURGY | MASS | ✗ | ✓ | ✗ |
| MASS_TITLE.LITURGY_OF_THE_PRESANCTIFIED_GIFTS | MASS | ✗ | ✓ | ✗ |
| MASS_TITLE.MATINS | OTHER | ✗ | ✓ | ✗ |
| MASS_TITLE.VESPRES | OTHER | ✗ | ✓ | ✗ |
| MASS_TITLE.MASS_OF_THE_LORD_S_SUPPER | MASS | ✓ | ✗ | ✓ |
| MASS_TITLE.GOOD_FRIDAY_LITURGY | MASS | ✓ | ✗ | ✓ |
| MASS_TITLE.EASTER_VIGIL | MASS | ✓ | ✗ | ✓ |
| MASS_TITLE.TRADITIONAL_LATIN_MASS | MASS | ✗ | ✗ | ✓ |
| MASS_TITLE.TRADITIONAL_MASS_OF_THE_LORD_S_SUPPER | MASS | ✗ | ✗ | ✓ |
| MASS_TITLE.TRADITIONAL_GOOD_FRIDAY_LITURGY | MASS | ✗ | ✗ | ✓ |
| MASS_TITLE.TRADITIONAL_EASTER_VIGIL | MASS | ✗ | ✗ | ✓ |
| MASS_TITLE.ADORATION | ADORATION | ✓ | ✗ | ✗ |
| MASS_TITLE.CONFESSION | CONFESSION | ✓ | ✓ | ✗ |
| MASS_TITLE.BREVIARY | OTHER | ✓ | ✗ | ✗ |
| MASS_TITLE.ROSARY | OTHER | ✓ | ✗ | ✗ |
| MASS_TITLE.LITANY | OTHER | ✓ | ✗ | ✗ |

### 3. Azonosított RITE-ok

```typescript
enum Rite {
  ROMAN_CATHOLIC = 'ROMAN_CATHOLIC',
  GREEK_CATHOLIC = 'GREEK_CATHOLIC',
  TRADITIONAL = 'TRADITIONAL'
}
```

---

## II. AZONOSÍTOTT PROBLÉMÁK

1. **Adatszétszórtság**
   - Title listák 3 helyen (mass-util getTitles, MassTitleCategoryConfig CATEGORY_TITLES, add-full-event-dialog)
   - Rite-Title kapcsolat csak függvénnyel lekezelve, nincs adat-struktúra

2. **Karbantartási nehézségek**
   - Új title hozzáadásakor több helyen kell módosítani
   - Duplikáció és inkonzisztencia lehetősége
   - Hardcoded title listák szétszórva

3. **JSON exportálás hiánya**
   - Jelenleg nem lehetséges JSON-ként exportálni az összes definíciót
   - Build-time generáláshoz nincs infrastructure

4. **Type-safety hiánya**
   - Sztringek helyett erős tipizálás kellene
   - Runtime error lehetőség a title-ok között

---

## III. ÚJ CENTRALIZÁLT ADATSTRUKTÚRA TERV

### 1. Adatformátum sémája (TypeScript interfészek)

```typescript
// calendar/src/app/data/mass-definitions.ts

/**
 * Rite metaadatai (ikon, szín, stb.)
 */
export interface RiteMetadata {
  rite: Rite;
  icon?: string;       // ikon elérési út (pl. "/assets/icons/rite-rc.svg")
  color?: string;      // szín (pl. "#FF5733")
  label?: string;      // i18n key vagy direct string
}

/**
 * Egy MASS_TITLE egyedi definíciója
 * Az összes metaadat egy helyen
 */
export interface MassDefinition {
  /** i18n key (pl. "MASS_TITLE.HOLY_MASS") */
  key: string;
  
  /** Kategória */
  category: MassTitleCategory;
  
  /** Melyik rite-okhoz tartozik (metaadatokkal) */
  rites: RiteMetadata[];
  
  /** Default rite (ha csak egyre jellemző) */
  defaultRite?: Rite;
  
  /** Leírás (comment) */
  description: string;
  
  /** Speciális felhasználás (pl. Easter, Christmas) */
  specialUsage?: 'EASTER' | 'CHRISTMAS' | null;
}

/**
 * Kategória-cím párosítás (TypeScript verzió - titleKeys NÉLKÜL)
 * A titleKeys az export-ban lesz benne, de a TS-ben nem szükséges (kiszámítható)
 */
export interface CategoryDefinition {
  key: MassTitleCategory;
  color: string;
}

/**
 * Rite-cím párosítás (TypeScript verzió - titleKeys NÉLKÜL)
 * A titleKeys az export-ban lesz benne, de a TS-ben nem szükséges (kiszámítható)
 */
export interface RiteDefinition {
  key: Rite;
}

/**
 * A teljes centralizált adatstruktúra (TypeScript)
 */
export interface MassDefinitionsData {
  version: string;
  lastUpdated: string;
  definitions: MassDefinition[];
  categories: CategoryDefinition[];
  rites: RiteDefinition[];
}
```

### 2. Konkrét adat-struktúra feltöltve

```typescript
// calendar/src/app/data/mass-definitions.ts

export const MASS_DEFINITIONS_DATA: MassDefinitionsData = {
  version: '1.0.0',
  lastUpdated: '2026-05-27',
  definitions: [
    // MASS kategória
    {
      key: 'MASS_TITLE.HOLY_MASS',
      category: MassTitleCategory.MASS,
      rites: [
        {
          rite: Rite.ROMAN_CATHOLIC,
          icon: '/assets/icons/rite-roman-catholic.svg',
          color: '#FF5733'
        }
      ],
      description: 'Roman Catholic Sunday/weekday Mass',
      specialUsage: null
    },
    {
      key: 'MASS_TITLE.LITURGY_OF_THE_WORD',
      category: MassTitleCategory.MASS,
      rites: [{ rite: Rite.ROMAN_CATHOLIC }],
      description: 'Liturgy of the Word without Eucharist',
      specialUsage: null
    },
    {
      key: 'MASS_TITLE.DIVINE_LITURGY',
      category: MassTitleCategory.MASS,
      rites: [{ rite: Rite.GREEK_CATHOLIC }],
      defaultRite: Rite.GREEK_CATHOLIC,
      description: 'Greek Catholic Divine Liturgy',
      specialUsage: null
    },
    {
      key: 'MASS_TITLE.LITURGY_OF_THE_PRESANCTIFIED_GIFTS',
      category: MassTitleCategory.MASS,
      rites: [{ rite: Rite.GREEK_CATHOLIC }],
      description: 'Greek Catholic Liturgy of Presanctified Gifts',
      specialUsage: null
    },
    {
      key: 'MASS_TITLE.MASS_OF_THE_LORD_S_SUPPER',
      category: MassTitleCategory.MASS,
      rites: [
        { rite: Rite.ROMAN_CATHOLIC },
        { rite: Rite.TRADITIONAL }
      ],
      description: 'Mass of the Lord\'s Supper (Holy Thursday)',
      specialUsage: 'EASTER'
    },
    {
      key: 'MASS_TITLE.GOOD_FRIDAY_LITURGY',
      category: MassTitleCategory.MASS,
      rites: [
        { rite: Rite.ROMAN_CATHOLIC },
        { rite: Rite.TRADITIONAL }
      ],
      description: 'Good Friday Liturgy of the Passion',
      specialUsage: 'EASTER'
    },
    {
      key: 'MASS_TITLE.EASTER_VIGIL',
      category: MassTitleCategory.MASS,
      rites: [
        { rite: Rite.ROMAN_CATHOLIC },
        { rite: Rite.TRADITIONAL }
      ],
      description: 'Easter Vigil Mass',
      specialUsage: 'EASTER'
    },
    {
      key: 'MASS_TITLE.TRADITIONAL_LATIN_MASS',
      category: MassTitleCategory.MASS,
      rites: [{ rite: Rite.TRADITIONAL, icon: '/assets/icons/rite-traditional.svg' }],
      defaultRite: Rite.TRADITIONAL,
      description: 'Traditional Latin Mass (Tridentine Rite)',
      specialUsage: null
    },
    {
      key: 'MASS_TITLE.TRADITIONAL_MASS_OF_THE_LORD_S_SUPPER',
      category: MassTitleCategory.MASS,
      rites: [{ rite: Rite.TRADITIONAL }],
      description: 'Traditional Latin Mass of the Lord\'s Supper',
      specialUsage: 'EASTER'
    },
    {
      key: 'MASS_TITLE.TRADITIONAL_GOOD_FRIDAY_LITURGY',
      category: MassTitleCategory.MASS,
      rites: [{ rite: Rite.TRADITIONAL }],
      description: 'Traditional Latin Good Friday Liturgy',
      specialUsage: 'EASTER'
    },
    {
      key: 'MASS_TITLE.TRADITIONAL_EASTER_VIGIL',
      category: MassTitleCategory.MASS,
      rites: [{ rite: Rite.TRADITIONAL }],
      description: 'Traditional Latin Easter Vigil',
      specialUsage: 'EASTER'
    },
    // ADORATION kategória
    {
      key: 'MASS_TITLE.ADORATION',
      category: MassTitleCategory.ADORATION,
      rites: [{ rite: Rite.ROMAN_CATHOLIC }],
      description: 'Eucharistic Adoration',
      specialUsage: null
    },
    // CONFESSION kategória
    {
      key: 'MASS_TITLE.CONFESSION',
      category: MassTitleCategory.CONFESSION,
      rites: [
        { rite: Rite.ROMAN_CATHOLIC },
        { rite: Rite.GREEK_CATHOLIC }
      ],
      description: 'Confession/Penance service',
      specialUsage: null
    },
    // OTHER kategória
    {
      key: 'MASS_TITLE.BREVIARY',
      category: MassTitleCategory.OTHER,
      rites: [{ rite: Rite.ROMAN_CATHOLIC }],
      description: 'Liturgy of the Hours (Breviary)',
      specialUsage: null
    },
    {
      key: 'MASS_TITLE.ROSARY',
      category: MassTitleCategory.OTHER,
      rites: [{ rite: Rite.ROMAN_CATHOLIC }],
      description: 'Rosary recitation',
      specialUsage: null
    },
    {
      key: 'MASS_TITLE.LITANY',
      category: MassTitleCategory.OTHER,
      rites: [{ rite: Rite.ROMAN_CATHOLIC }],
      description: 'Litany service',
      specialUsage: null
    },
    // OTHER kategória - görögkatolikus
    {
      key: 'MASS_TITLE.MATINS',
      category: MassTitleCategory.OTHER,
      rites: [{ rite: Rite.GREEK_CATHOLIC }],
      description: 'Greek Catholic Matins service',
      specialUsage: null
    },
    {
      key: 'MASS_TITLE.VESPRES',
      category: MassTitleCategory.OTHER,
      rites: [{ rite: Rite.GREEK_CATHOLIC }],
      description: 'Greek Catholic Vespres service',
      specialUsage: null
    }
  ],
  categories: [
    {
      key: MassTitleCategory.MASS,
      color: '#0A0A0A'
    },
    {
      key: MassTitleCategory.ADORATION,
      color: '#C4B5A0'
    },
    {
      key: MassTitleCategory.CONFESSION,
      color: '#7A4D9A'
    },
    {
      key: MassTitleCategory.OTHER,
      color: '#A8B8D0'
    }
  ],
  rites: [
    {
      key: Rite.ROMAN_CATHOLIC
    },
    {
      key: Rite.GREEK_CATHOLIC
    },
    {
      key: Rite.TRADITIONAL
    }
  ]
};
```

### 3. Statikus helper osztály az adatokhoz

```typescript
// calendar/src/app/data/mass-definitions.ts (folytatás)

/**
 * Segéd osztály a centralizált MASS_TITLE adatok eléréséhez
 */
export class MassDefinitionsHelper {
  
  /**
   * Összes title-definíció lekérése rite alapján szűrve
   */
  static getTitlesByRite(rite: Rite): MassDefinition[] {
    return MASS_DEFINITIONS_DATA.definitions.filter(
      def => def.rites.some(rm => rm.rite === rite)
    );
  }
  
  /**
   * Összes title-definíció lekérése kategória alapján
   */
  static getTitlesByCategory(category: MassTitleCategory): MassDefinition[] {
    return MASS_DEFINITIONS_DATA.definitions.filter(
      def => def.category === category
    );
  }
  
  /**
   * Title-definíció lekérése i18n key alapján
   */
  static getDefinitionByKey(key: string): MassDefinition | undefined {
    return MASS_DEFINITIONS_DATA.definitions.find(def => def.key === key);
  }
  
  /**
   * Kategória szín lekérése
   */
  static getCategoryColor(category: MassTitleCategory): string {
    return MASS_DEFINITIONS_DATA.categories.find(c => c.key === category)?.color || '#A8B8D0';
  }
  
  /**
   * I18n key lista rite alapján
   */
  static getTitleKeysByRite(rite: Rite): string[] {
    return this.getTitlesByRite(rite).map(def => def.key);
  }
  
  /**
   * Default title lekérése rite alapján
   */
  static getDefaultTitleByRite(rite: Rite): string {
    const titles = this.getTitlesByRite(rite);
    const defaultTitle = titles.find(def => def.defaultRite === rite);
    return defaultTitle?.key || titles[0]?.key || 'MASS_TITLE.HOLY_MASS';
  }
  
  /**
   * Összes kategória lekérése
   */
  static getAllCategories(): MassTitleCategory[] {
    return MASS_DEFINITIONS_DATA.categories.map(c => c.key);
  }
}
```

---

## IV. JSON EXPORT FORMÁTUM

### JSON struktúra (webapp/mass-definitions.json)

```json
{
  "_generator": "calendar/src/app/data/mass-definitions-export.ts",
  "_warning": "Ez a fájl az Angular buildből generálódik. Ne szerkeszd közvetlenül! A definíciókat a calendar/src/app/data/mass-definitions.ts fájlban módosítsd.",
  "version": "1.0.0",
  "lastUpdated": "2026-05-27",
  "definitions": [
    {
      "key": "MASS_TITLE.HOLY_MASS",
      "category": "MASS",
      "rites": [
        {
          "rite": "ROMAN_CATHOLIC",
          "icon": "/assets/icons/rite-roman-catholic.svg",
          "color": "#FF5733"
        }
      ],
      "defaultRite": null,
      "description": "Roman Catholic Sunday/weekday Mass",
      "specialUsage": null
    }
  ],
  "categories": [
    {
      "key": "MASS",
      "color": "#0A0A0A",
      "titleKeys": [
        "MASS_TITLE.HOLY_MASS",
        "MASS_TITLE.LITURGY_OF_THE_WORD",
        "MASS_TITLE.DIVINE_LITURGY",
        "MASS_TITLE.LITURGY_OF_THE_PRESANCTIFIED_GIFTS",
        "MASS_TITLE.MASS_OF_THE_LORD_S_SUPPER",
        "MASS_TITLE.GOOD_FRIDAY_LITURGY",
        "MASS_TITLE.EASTER_VIGIL",
        "MASS_TITLE.TRADITIONAL_LATIN_MASS",
        "MASS_TITLE.TRADITIONAL_MASS_OF_THE_LORD_S_SUPPER",
        "MASS_TITLE.TRADITIONAL_GOOD_FRIDAY_LITURGY",
        "MASS_TITLE.TRADITIONAL_EASTER_VIGIL"
      ]
    },
    {
      "key": "ADORATION",
      "color": "#C4B5A0",
      "titleKeys": ["MASS_TITLE.ADORATION"]
    },
    {
      "key": "CONFESSION",
      "color": "#7A4D9A",
      "titleKeys": ["MASS_TITLE.CONFESSION"]
    },
    {
      "key": "OTHER",
      "color": "#A8B8D0",
      "titleKeys": [
        "MASS_TITLE.BREVIARY",
        "MASS_TITLE.ROSARY",
        "MASS_TITLE.LITANY",
        "MASS_TITLE.MATINS",
        "MASS_TITLE.VESPRES"
      ]
    }
  ],
  "rites": [
    {
      "key": "ROMAN_CATHOLIC",
      "titleKeys": [
        "MASS_TITLE.HOLY_MASS",
        "MASS_TITLE.LITURGY_OF_THE_WORD",
        "MASS_TITLE.MASS_OF_THE_LORD_S_SUPPER",
        "MASS_TITLE.GOOD_FRIDAY_LITURGY",
        "MASS_TITLE.EASTER_VIGIL",
        "MASS_TITLE.ADORATION",
        "MASS_TITLE.CONFESSION",
        "MASS_TITLE.BREVIARY",
        "MASS_TITLE.ROSARY",
        "MASS_TITLE.LITANY"
      ]
    },
    {
      "key": "GREEK_CATHOLIC",
      "titleKeys": [
        "MASS_TITLE.DIVINE_LITURGY",
        "MASS_TITLE.LITURGY_OF_THE_PRESANCTIFIED_GIFTS",
        "MASS_TITLE.MATINS",
        "MASS_TITLE.VESPRES",
        "MASS_TITLE.CONFESSION"
      ]
    },
    {
      "key": "TRADITIONAL",
      "titleKeys": [
        "MASS_TITLE.TRADITIONAL_LATIN_MASS",
        "MASS_TITLE.MASS_OF_THE_LORD_S_SUPPER",
        "MASS_TITLE.GOOD_FRIDAY_LITURGY",
        "MASS_TITLE.EASTER_VIGIL"
      ]
    }
  ]
}
```

---

## V. BUILD INTEGRÁCIÓS MEGOLDÁS

### Megközelítés: TypeScript-ből JSON generálás build-időben

#### 5.1. Export TypeScript modul

**Lokáció**: `calendar/src/app/data/mass-definitions-export.ts`

```typescript
// calendar/src/app/data/mass-definitions-export.ts

import * as fs from 'fs';
import * as path from 'path';
import { MASS_DEFINITIONS_DATA } from './mass-definitions';

/**
 * Exportálja a MASS_DEFINITIONS_DATA-t JSON fájlba
 * Hozzáadja a pre-computed titleKeys listákat kategóriákhoz és rite-okhoz
 */
export function generateMassDefinitionsJson(outputPath: string): void {
  const categories = MASS_DEFINITIONS_DATA.categories.map(cat => ({
    key: cat.key,
    color: cat.color,
    titleKeys: MASS_DEFINITIONS_DATA.definitions
      .filter(def => def.category === cat.key)
      .map(def => def.key)
  }));

  const rites = MASS_DEFINITIONS_DATA.rites.map(rite => ({
    key: rite.key,
    titleKeys: MASS_DEFINITIONS_DATA.definitions
      .filter(def => def.rites.some(rm => rm.rite === rite.key))
      .map(def => def.key)
  }));

  const exportData = {
    _generator: 'calendar/src/app/data/mass-definitions-export.ts',
    _warning: 'Ez a fájl az Angular buildből generálódik. Ne szerkeszd közvetlenül! A definíciókat a calendar/src/app/data/mass-definitions.ts fájlban módosítsd.',
    version: MASS_DEFINITIONS_DATA.version,
    lastUpdated: MASS_DEFINITIONS_DATA.lastUpdated,
    definitions: MASS_DEFINITIONS_DATA.definitions,
    categories: categories,
    rites: rites
  };

  const dir = path.dirname(outputPath);
  if (!fs.existsSync(dir)) {
    fs.mkdirSync(dir, { recursive: true });
  }

  fs.writeFileSync(outputPath, JSON.stringify(exportData, null, 2));
  console.log(`✓ Generated: ${outputPath}`);
}
```

#### 5.2. Build script integrációs pont

**Lokáció**: `build-and-deploy.js` vagy `docker/miserend/calendar_deploy.py`

**A. `build-and-deploy.js`-ben:**
```javascript
// build-and-deploy.js

const { generateMassDefinitionsJson } = require('./calendar/src/app/data/mass-definitions-export');
const path = require('path');

async function buildAndDeploy() {
  // ... meglévő build logika ...
  
  // MASS_DEFINITIONS JSON generálása
  const outputPath = path.join(__dirname, 'webapp', 'mass-definitions.json');
  generateMassDefinitionsJson(outputPath);
  
  // ... deploy logika ...
}

module.exports = { buildAndDeploy, generateMassDefinitionsJson };
```

**B. `docker/miserend/calendar_deploy.py`-ben:**
```python
# calendar_deploy.py

import subprocess
import os
from pathlib import Path

def generate_mass_definitions():
    """
    Generálja a mass-definitions.json fájlt
    """
    # Node.js call az export TypeScript modulhoz
    os.system('node -e "require(\'./calendar/src/app/data/mass-definitions-export.js\').generateMassDefinitionsJson(\'webapp/mass-definitions.json\')"')

# deploy() függvénybe integrálva
if __name__ == '__main__':
    generate_mass_definitions()
    # ... deploy logika ...
```

#### 5.3. Output fájl helyzete

**VÉGSŐ HELYZET:**
```
webapp/mass-definitions.json
```

Ez közvetlenül a webapp gyökerében lesz, az i18n/hu.json mellett.

---

## VI. MIGRÁCIÓS STRATÉGIA

### Fázis 1: Új adat-struktúra létrehozása
- [ ] `calendar/src/app/data/mass-definitions.ts` létrehozása
- [ ] `RiteMetadata`, `MassDefinition`, `CategoryDefinition`, `RiteDefinition` interfészek implementálása
- [ ] `MassDefinitionsData` interfész implementálása
- [ ] `MASS_DEFINITIONS_DATA` feltöltése adatokkal (17 definícióval)
- [ ] `MassDefinitionsHelper` statikus segéd osztály implementálása

### Fázis 2: Export modul létrehozása
- [ ] `calendar/src/app/data/mass-definitions-export.ts` létrehozása
- [ ] `generateMassDefinitionsJson()` függvény implementálása (titleKeys kiszámítása)

### Fázis 3: Build script integrálása
- [ ] `build-and-deploy.js` vagy `docker/miserend/calendar_deploy.py` módosítása
- [ ] `generateMassDefinitionsJson()` hívása a megfelelő helyen
- [ ] Build tesztelése

### Fázis 4: Kódmigrációs refactor
- [ ] `MassUtil` frissítése az új `MassDefinitionsHelper`-t használja
- [ ] `MassTitleCategoryConfig` redundáns kód eltávolítása
- [ ] `add-full-event-dialog.component.ts` title-kezelés frissítése

### Fázis 5: Tesztelés és deployment
- [ ] Unit tesztek az új helper klasszhoz
- [ ] Integration tesztek (UI tesztek)
- [ ] Build output validálása
- [ ] `mass-definitions.json` keletkezésének és tartalma ellenőrzése

---

## VII. IMPLEMENTÁCIÓS LÉPÉSEK SORRENDJE

### 1. **TypeScript adat-struktúra**
```
calendar/src/app/data/mass-definitions.ts
├── RiteMetadata interfész
├── MassDefinition interfész
├── CategoryDefinition interfész (NO titleKeys)
├── RiteDefinition interfész (NO titleKeys)
├── MassDefinitionsData interfész
├── MASS_DEFINITIONS_DATA konstans (17 definícióval)
└── MassDefinitionsHelper statikus osztály
```

### 2. **Export TypeScript modul**
```
calendar/src/app/data/mass-definitions-export.ts
├── generateMassDefinitionsJson() függvény
├── titleKeys pre-computation (categories & rites)
├── JSON fejléc generálása (_generator, _warning)
└── Fájlba írás (webapp/mass-definitions.json)
```

### 3. **Build script integrálása**
```
build-and-deploy.js vagy docker/miserend/calendar_deploy.py
└── generateMassDefinitionsJson() hívása
```

### 4. **Meglévő kód refactor**
```
Érintett fájlok:
├── calendar/src/app/util/mass-util.ts (frissítés a helper-hez)
├── calendar/src/app/util/mass-title-category-config.ts (előkészítés eltávolításra)
└── calendar/src/app/components/add-full-event-dialog/ (title-kezelés frissítése)
```

### 5. **Tesztelés és validálás**
```
├── Unit tesztek az MassDefinitionsHelper-hez
├── Build tesztelés
├── mass-definitions.json keletkezésének és tartalma ellenőrzése
└── UI tesztelés
```

---

## VIII. ELŐNYÖK AZ ÚJ STRUKTÚRÁBAN

✅ **Centralizáció**: Egyetlen source of truth az összes MASS_TITLE definícióhoz
✅ **JSON exportálhatóság**: Könnyű kiexportálni az adatokat más rendszerekbe
✅ **Type-safety**: Erős TypeScript interfészek, kevesebb runtime error
✅ **Build-time integrálhatóság**: Automata JSON generálás az Angular buildből
✅ **Karbantarthatóság**: Egyszerűbb új title vagy rite hozzáadása
✅ **Dokumentálhatóság**: Minden title szöveges leírásával
✅ **Kiterjeszthetőség**: Könnyen bővíthető specialUsage, rite metaadatok, stb.
✅ **Pre-computed listák**: categories és rites szekciók elkerülik a kereséseket

---

## IX. MÓDOSÍTÁSOK A FEEDBACK ALAPJÁN

### 1. körös módosítások:

1. ✅ **MATINS és VESPRES kategóriacserélése**: OTHER kategóriára helyezve
2. ✅ **Rites mint objektum-tömb**: `RiteMetadata` interfész hozzáadása icon/szín metaadatokkal
3. ✅ **JSON export - nincs label mező**: Eltávolítva, mert a fordítás hu.json-ból származik
4. ✅ **JSON export - kategóriákban titleKeys**: Pre-computed lista minden kategóriához
5. ✅ **JSON export - rites szekció**: Teljes új rész az exportban, rite-onként titleKeys listákkal

### 2. körös módosítások:

1. ✅ **Build script helyzete**: `build-and-deploy.js` vagy `docker/miserend/calendar_deploy.py`-ba integrálva
2. ✅ **TypeScript-ben NO titleKeys**: `categories` és `rites` array-ek egyszerűek, titleKeys nélküli
3. ✅ **JSON exportban titleKeys**: Az `generateMassDefinitionsJson()` függvény kiszámítja őket
4. ✅ **JSON fejléc metaadat**: `_generator` és `_warning` mezők a fájl elejére
5. ✅ **Output fájl helyzete**: `webapp/mass-definitions.json` (gyökerben, az i18n mellett)

Ez biztosítja, hogy:
- A TypeScript forráskód egyszerű és könnyen karbantartható
- A JSON export teljes és optimalizált (pre-computed listák)
- A JSON fejléce jelzi, hogyan jött létre és ne szerkesszék közvetlenül

---

## X. OPEN KÉRDÉSEK ÉS MEGJEGYZÉSEK

### TypeScript-ből JSON kiírás megoldása

Az export modul TypeScript-ben írható, amelyet Node.js runtime futtat a build során.

### I18n integráció

A kategóriák és rite-ok nevei az `i18n/hu.json`-ből kerülnek megjelenítésre.
Az JSON export nem tartalmaz `label` mezőt - a fordítás a hu.json/en.json alapján történik.

### Future-ready kiterjesztések

Potenciális új mezők:
```typescript
export interface MassDefinition {
  // ... létezőek ...
  isActive: boolean; // Aktív/inaktív flagg
  notes: string[]; // Megjegyzések
  relatedMasses: string[]; // Kapcsolódó title-ok
  liturgicalColor?: string; // Liturgikus szín
}
```

