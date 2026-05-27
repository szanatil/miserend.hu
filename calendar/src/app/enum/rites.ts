/**
 * Rite definíciók - MASTER SOURCE OF TRUTH
 * EZ az egyetlen hely, ahol a ritusokat kell definiálni!
 *
 * Új rite hozzáadása:
 * 1. Ide vesz fel egy új elemet a listára (key és icon)
 * 2. Kész! Az enum, a type és az összes definíció automatikusan generálódik
 */
export const RITE_DEFINITIONS = [
  {
    key: 'ROMAN_CATHOLIC',
    icon: 'church',
    simpleTitle: 'HOLY_MASS',
  },
  {
    key: 'GREEK_CATHOLIC',
    icon: 'church',
    simpleTitle: 'DIVINE_LITURGY',
  },
  {
    key: 'TRADITIONAL',
    icon: 'church',
    simpleTitle: 'TRADITIONAL_LATIN_MASS',
  },  
] as const;

/**
 * Auto-generált type az enum helyett
 * Csak a RITE_DEFINITIONS-ből származik
 */
export type Rite = (typeof RITE_DEFINITIONS)[number]['key'];

/**
 * Konstans object az enum helyett - értékek elérésére
 * Automatikusan generálódik a RITE_DEFINITIONS-ből
 */
function createRiteConstant(): Record<Rite, Rite> {
  const result = {} as Record<Rite, Rite>;
  for (const rite of RITE_DEFINITIONS) {
    result[rite.key as Rite] = rite.key as Rite;
  }
  return result;
}

export const Rite = createRiteConstant();
