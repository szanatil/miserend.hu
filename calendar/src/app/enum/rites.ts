import { MassType } from './types';

/**
 * Rite definíciók - MASTER SOURCE OF TRUTH
 * EZ az egyetlen hely, ahol a ritusokat kell definiálni!
 *
 * Új rite hozzáadása:
 * 1. Ide vesz fel egy új elemet a listára (key, icon, simpleTitle és massTypes)
 * 2. Kész! Az enum, a type és az összes definíció automatikusan generálódik
 */
export const RITE_DEFINITIONS = [
  {
    key: 'ROMAN_CATHOLIC',
    icon: 'church',
    simpleTitle: 'HOLY_MASS',
    massTypes: [
      MassType.FAMILY,
      MassType.STUDENT,
      MassType.UNIVERSITY_YOUTH,
      MassType.GUITAR,
      MassType.ORGAN,
      MassType.SILENT
    ]
  },
  {
    key: 'GREEK_CATHOLIC',
    icon: 'church',
    simpleTitle: 'DIVINE_LITURGY',
    massTypes: [
      MassType.FAMILY,
      MassType.STUDENT,
      MassType.UNIVERSITY_YOUTH
    ]
  },
  {
    key: 'TRADITIONAL',
    icon: 'church',
    simpleTitle: 'TRADITIONAL_LATIN_MASS',
    massTypes: [
      MassType.SINGER,
      MassType.SILENT
    ]
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

/**
 * Mise típusok alapértelmezett értékei az egyes ritusokhoz
 * Automatikusan generálódik a RITE_DEFINITIONS-ből
 */
export const RiteMassTypes = (() => {
  const result = {} as Record<Rite, MassType[]>;
  for (const rite of RITE_DEFINITIONS) {
    result[rite.key as Rite] = (rite.massTypes as unknown) as MassType[];
  }
  return result;
})() satisfies Record<Rite, MassType[]>;
