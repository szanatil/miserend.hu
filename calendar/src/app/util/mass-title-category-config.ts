import { MassTitleCategory } from '../enum/mass-categories';
import { TranslateService } from '@ngx-translate/core';
import { MASS_DEFINITIONS_DATA, MassDefinitionsHelper } from '../data/mass-definitions';

/**
 * Kategória-szín és kategória-cím párosítások konfigurációja
 * Az adatok a centralizált MASS_DEFINITIONS_DATA-ból származnak
 * Biztosítja az egyházilag illő halványabb árnyalatokat
 */
export class MassTitleCategoryConfig {
  /**
   * Kategória színek: a MASS_DEFINITIONS_DATA-ból lekérdezve
   * Minden szín a centralizált adatforrásból származik, nincs fallback
   */
  static get CATEGORY_COLORS(): Record<MassTitleCategory, string> {
    const colors: Record<MassTitleCategory, string> = {} as Record<MassTitleCategory, string>;

    // Kategóriák és színek dinamikusan a MASS_DEFINITIONS_DATA-ból
    for (const categoryDef of MASS_DEFINITIONS_DATA.categories) {
      const key = categoryDef.key as MassTitleCategory;
      colors[key] = categoryDef.color;
    }

    return colors;
  }

  /**
   * Kategória-cím párosítások (i18n key-ek) a MASS_DEFINITIONS_DATA-ból
   * Dinamikusan generálva, kategóriák a MASS_DEFINITIONS_DATA-ból
   */
  static get CATEGORY_TITLES(): Record<MassTitleCategory, string[]> {
    // Inicializáljuk az összes kategóriát dinamikusan
    const titles: Record<MassTitleCategory, string[]> = {} as Record<MassTitleCategory, string[]>;
    
    for (const categoryDef of MASS_DEFINITIONS_DATA.categories) {
      const key = categoryDef.key as MassTitleCategory;
      titles[key] = [];
    }

    // Felépítjük a kategóriák alapján a MASS_DEFINITIONS_DATA-ból
    for (const definition of MASS_DEFINITIONS_DATA.definitions) {
      const category = definition.category as MassTitleCategory;
      if (titles[category]) {
        titles[category].push(definition.key);
      }
    }

    return titles;
  }

  // Lefordított szövegek a kategóriákhoz - dinamikusan generálva az i18n JSON alapján
  private static _translatedValuesCache: Record<MassTitleCategory, string[]> | null = null;

  /**
   * Lefordított szövegek a kategóriákhoz (támogatás a fordított értékekhez)
   * Dinamikusan generálódik az i18n JSON fájlokból
   * @param translate A TranslateService az i18n értékek lekéréséhez
   */
  static getTranslatedValues(translate: TranslateService): Record<MassTitleCategory, string[]> {
    // Inicializáljuk az összes kategóriát dinamikusan
    const translatedValues: Record<MassTitleCategory, string[]> = {} as Record<MassTitleCategory, string[]>;
    
    for (const categoryDef of MASS_DEFINITIONS_DATA.categories) {
      const key = categoryDef.key as MassTitleCategory;
      translatedValues[key] = [];
    }

    // Végigmegyünk az összes kategórián és i18n kulcson
    for (const [category, titleKeys] of Object.entries(this.CATEGORY_TITLES)) {
      const values = new Set<string>();
      
      for (const titleKey of titleKeys) {
        const translated = translate.instant(titleKey);
        
        // Csak akkor adjuk hozzá, ha sikerült lefordítani (nem az i18n key marad meg)
        if (translated && translated !== titleKey) {
          values.add(translated);
        }
      }
      
      translatedValues[category as MassTitleCategory] = Array.from(values);
    }

    return translatedValues;
  }

  

  /**
   * Szín lekérése kategória alapján
   */
  static getColorByCategory(category: MassTitleCategory): string {
    return this.CATEGORY_COLORS[category];
  }

  /**
   * Kategória lekérése title alapján
   * Támogatja az i18n key-eket és a lefordított szövegeket is (case-insensitive)
   * @param title A keresendő cím
   * @param translate Opcionális TranslateService az i18n értékekhez (ha nincs, fallback értékeket használunk)
   */
  static getCategoryByTitle(title: string, translate?: TranslateService): MassTitleCategory {
    if (!title) {
      // Default kategória az MASS_DEFINITIONS_DATA-ból
      return MASS_DEFINITIONS_DATA.categories[MASS_DEFINITIONS_DATA.categories.length - 1].key as MassTitleCategory;
    }

    // Először próbáljuk meg az i18n key alapján (pl. "MASS_TITLE.ADORATION")
    for (const [category, titles] of Object.entries(this.CATEGORY_TITLES)) {
      if (titles.includes(title)) {
        return category as MassTitleCategory;
      }
    }

    // Lekérjük a lefordított értékeket
    if (!translate) {
      // Default kategória az MASS_DEFINITIONS_DATA-ból
      return MASS_DEFINITIONS_DATA.categories[MASS_DEFINITIONS_DATA.categories.length - 1].key as MassTitleCategory;
    }

    const translatedValuesToUse = this.getTranslatedValues(translate);

    // Majd próbáljuk a lefordított szövegeket (case-insensitive)
    const lowerTitle = title.toLowerCase();
    for (const [category, translatedValues] of Object.entries(translatedValuesToUse)) {
      if (translatedValues.some((val: string) => val.toLowerCase() === lowerTitle)) {
        return category as MassTitleCategory;
      }
    }

    // Végül fuzzy match: ha a title tartalmazza valamelyik ismert szöveget
    for (const [category, translatedValues] of Object.entries(translatedValuesToUse)) {
      for (const val of translatedValues) {
        if (lowerTitle.includes((val as string).toLowerCase()) || (val as string).toLowerCase().includes(lowerTitle)) {
          return category as MassTitleCategory;
        }
      }
    }

    // Default kategória az MASS_DEFINITIONS_DATA-ból
    return MASS_DEFINITIONS_DATA.categories[MASS_DEFINITIONS_DATA.categories.length - 1].key as MassTitleCategory;
  }

  /**
   * Összes kategória lista
   */
  static getAllCategories(): MassTitleCategory[] {
    return Object.values(MassTitleCategory);
  }
}
