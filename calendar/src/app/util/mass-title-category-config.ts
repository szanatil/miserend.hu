import { MassTitleCategory } from '../enum/mass-title-category';
import { TranslateService } from '@ngx-translate/core';

/**
 * Kategória-szín és kategória-cím párosítások konfigurációja
 * Biztosítja az egyházilag illő halványabb árnyalatokat
 */
export class MassTitleCategoryConfig {
  // Kategória színek: halványabb árnyalatok, megfelelőek fehér szöveghez
  static readonly CATEGORY_COLORS: Record<MassTitleCategory, string> = {
    [MassTitleCategory.MASS]: '#D4A574',          // Arany/barna - mise
    [MassTitleCategory.ADORATION]: '#C4B5A0',    // Vöröses barna - szentségimádás
    [MassTitleCategory.CONFESSION]: '#B8A8C0',   // Halványlila - gyóntatás
    [MassTitleCategory.OTHER]: '#A8B8D0'         // Halványkék - egyéb
  };

  // Kategória-cím párosítások (i18n key-ek)
  static readonly CATEGORY_TITLES: Record<MassTitleCategory, string[]> = {
    [MassTitleCategory.MASS]: [
      'MASS_TITLE.HOLY_MASS',
      'MASS_TITLE.LITURGY_OF_THE_WORD',
      'MASS_TITLE.DIVINE_LITURGY',
      'MASS_TITLE.LITURGY_OF_THE_PRESANCTIFIED_GIFTS',
      'MASS_TITLE.MASS_OF_THE_LORD_S_SUPPER',
      'MASS_TITLE.GOOD_FRIDAY_LITURGY',
      'MASS_TITLE.EASTER_VIGIL',
      'MASS_TITLE.TRADITIONAL_LATIN_MASS',
      'MASS_TITLE.TRADITIONAL_MASS_OF_THE_LORD_S_SUPPER',
      'MASS_TITLE.TRADITIONAL_GOOD_FRIDAY_LITURGY',
      'MASS_TITLE.TRADITIONAL_EASTER_VIGIL',
      'MASS_TITLE.MATINS',
      'MASS_TITLE.VESPRES'
    ],
    [MassTitleCategory.ADORATION]: [
      'MASS_TITLE.ADORATION'
    ],
    [MassTitleCategory.CONFESSION]: [
      'MASS_TITLE.CONFESSION'
    ],
    [MassTitleCategory.OTHER]: [
      'MASS_TITLE.BREVIARY',
      'MASS_TITLE.ROSARY',
      'MASS_TITLE.LITANY'
    ]
  };

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
   * Fallback értékek statikus hardcoded fordítások
   * Ezek az értékek akkor kerülnek felhasználásra, ha a TranslateService nem érhető el
   */
  static readonly CATEGORY_TRANSLATED_VALUES: Record<MassTitleCategory, string[]> = {
    [MassTitleCategory.MASS]: [
      // Magyar
      'Szentmise', 'Igeliturgia', 'Szent Liturgia', 'Előszenteltek liturgiája',
      'Az utolsó vacsora emlékmiséje', 'Nagypénteki szertartás', 'Húsvét vigíliája',
      'Régi rítusú szentmise', 'Az utolsó vacsora emlékmiséje (régi rítusú)',
      'Nagypénteki szertartás (régi rítusú)', 'Húsvét vigíliája (régi rítusú)',
      'Utrenye', 'Vecsernye',
      // Angol
      'Holy Mass', 'Liturgy of the Word', 'Divine Liturgy', 'Liturgy of the Presanctified Gifts',
      'Mass of the Lord\'s Supper', 'Good Friday Liturgy', 'Easter Vigil',
      'Traditional Latin Mass', 'Traditional Mass of the Lord\'s Supper',
      'Traditional Good Friday Liturgy', 'Traditional Easter Vigil', 'Matins', 'Vespres'
    ],
    [MassTitleCategory.ADORATION]: [
      // Magyar
      'Szentségimádás',
      // Angol
      'Adoration'
    ],
    [MassTitleCategory.CONFESSION]: [
      // Magyar
      'Gyóntatás',
      // Angol
      'Confession'
    ],
    [MassTitleCategory.OTHER]: [
      // Magyar
      'Zsolozsma', 'Rózsafüzér', 'Litánia',
      // Angol
      'Breviary', 'Rosary', 'Litany'
    ]
  };

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

    // Lekérjük a lefordított értékeket - dinamikusan vagy fallback-ból
    const translatedValuesToUse = translate ? this.getTranslatedValues(translate) : this.CATEGORY_TRANSLATED_VALUES;

    // Majd próbáljuk a lefordított szövegeket (case-insensitive)
    const lowerTitle = title.toLowerCase();
    for (const [category, translatedValues] of Object.entries(translatedValuesToUse)) {
      if (translatedValues.some(val => val.toLowerCase() === lowerTitle)) {
        return category as MassTitleCategory;
      }
    }

    // Végül fuzzy match: ha a title tartalmazza valamelyik ismert szöveget
    for (const [category, translatedValues] of Object.entries(translatedValuesToUse)) {
      for (const val of translatedValues) {
        if (lowerTitle.includes(val.toLowerCase()) || val.toLowerCase().includes(lowerTitle)) {
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
