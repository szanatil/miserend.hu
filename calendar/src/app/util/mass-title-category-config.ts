import { MassTitleCategory } from '../enum/mass-title-category';
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
   * Fallback: halványabb árnyalatok, megfelelőek fehér szöveghez
   */
  static get CATEGORY_COLORS(): Record<MassTitleCategory, string> {
    const colors: Record<MassTitleCategory, string> = {
      [MassTitleCategory.MASS]: '#0A0A0A',
      [MassTitleCategory.ADORATION]: '#C4B5A0',
      [MassTitleCategory.CONFESSION]: '#7A4D9A',
      [MassTitleCategory.OTHER]: '#A8B8D0'
    };

    // Felülírjuk a MASS_DEFINITIONS_DATA-ból
    for (const categoryDef of MASS_DEFINITIONS_DATA.categories) {
      const key = categoryDef.key as MassTitleCategory;
      if (categoryDef.color) {
        colors[key] = categoryDef.color;
      }
    }

    return colors;
  }

  /**
   * Kategória-cím párosítások (i18n key-ek) a MASS_DEFINITIONS_DATA-ból
   */
  static get CATEGORY_TITLES(): Record<MassTitleCategory, string[]> {
    const titles: Record<MassTitleCategory, string[]> = {
      [MassTitleCategory.MASS]: [],
      [MassTitleCategory.ADORATION]: [],
      [MassTitleCategory.CONFESSION]: [],
      [MassTitleCategory.OTHER]: []
    };

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
    const translatedValues: Record<MassTitleCategory, string[]> = {
      [MassTitleCategory.MASS]: [],
      [MassTitleCategory.ADORATION]: [],
      [MassTitleCategory.CONFESSION]: [],
      [MassTitleCategory.OTHER]: []
    };

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
      return MassTitleCategory.OTHER;
    }

    // Először próbáljuk meg az i18n key alapján (pl. "MASS_TITLE.ADORATION")
    for (const [category, titles] of Object.entries(this.CATEGORY_TITLES)) {
      if (titles.includes(title)) {
        return category as MassTitleCategory;
      }
    }

    // Lekérjük a lefordított értékeket
    if (!translate) {
      return MassTitleCategory.OTHER;
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

    return MassTitleCategory.OTHER;
  }

  /**
   * Összes kategória lista
   */
  static getAllCategories(): MassTitleCategory[] {
    return Object.values(MassTitleCategory);
  }
}
