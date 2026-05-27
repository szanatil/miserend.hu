import { MassTitleCategory, MASS_CATEGORY_DEFINITIONS, type MassTitleCategory as MassTitleCategoryType } from '../enum/mass-categories';
import { Rite } from '../model/mass';

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
 * Kategóriák automatikus generálása a MASTER SOURCE OF TRUTH-ból
 * MASS_CATEGORY_DEFINITIONS az egyetlen hely az értékek definiálásához!
 */
const generateCategories = (): CategoryDefinition[] => {
  return MASS_CATEGORY_DEFINITIONS.map(({ key, color }) => ({
    key: key as MassTitleCategory,
    color
  }));
};

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

/**
 * Központi adatstruktúra az összes MASS_TITLE definícióval
 * Ez a SINGLE SOURCE OF TRUTH az összes misekategória és rite adatra
 */
export const MASS_DEFINITIONS_DATA: MassDefinitionsData = {
  version: '1.0.0',
  lastUpdated: '2026-05-27',
  definitions: [
    // ============ MASS kategória ============

    {
      key: 'MASS_TITLE.HOLY_MASS2',
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
      key: 'MASS_TITLE.LITURGY_OF_THE_WORD2',
      category: MassTitleCategory.MASS,
      rites: [{ rite: Rite.ROMAN_CATHOLIC }],
      description: 'Liturgy of the Word without Eucharist',
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

    // ============ ADORATION kategória ============

    {
      key: 'MASS_TITLE.ADORATION',
      category: MassTitleCategory.ADORATION,
      rites: [{ rite: Rite.ROMAN_CATHOLIC }],
      description: 'Eucharistic Adoration',
      specialUsage: null
    },

    // ============ CONFESSION kategória ============

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

    // ============ OTHER kategória ============

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

  categories: generateCategories(),

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
