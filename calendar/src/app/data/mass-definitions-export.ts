import { MASS_DEFINITIONS_DATA, MassDefinition, CategoryDefinition, RiteDefinition } from './mass-definitions';
import { Rite } from '../model/mass';
import { MassTitleCategory } from '../enum/mass-categories';

/**
 * Mass definition in the JSON export
 */
export interface MassDefinitionJsonExport {
  key: string;
  category: MassTitleCategory;
  rites: Rite[];
  defaultRite?: Rite;
  description: string;
  specialUsage?: 'EASTER' | 'CHRISTMAS' | null;
}

/**
 * Category definition in the JSON export
 */
export interface CategoryDefinitionJsonExport {
  key: MassTitleCategory;
  color: string;
}

/**
 * Rite definition in the JSON export
 */
export interface RiteDefinitionJsonExport {
  key: Rite;
}

/**
 * Titles grouped by category
 */
export interface TitlesByCategory {
  [category: string]: string[];
}

/**
 * Titles grouped by rite
 */
export interface TitlesByRite {
  [rite: string]: string[];
}

/**
 * Complete JSON export structure for mass definitions
 */
export interface MassDefinitionsJsonOutput {
  _generator: string;
  _warning: string;
  _buildDate: string;
  categories: CategoryDefinitionJsonExport[];
  rites: RiteDefinitionJsonExport[];
  definitions: MassDefinitionJsonExport[];
  titlesByCategory: TitlesByCategory;
  titlesByRite: TitlesByRite;
}

/**
 * Generates a JSON export of mass definitions with pre-computed indexes
 * This function is Node.js compatible and can be used in build scripts
 * 
 * @returns MassDefinitionsJsonOutput - Complete JSON export with all metadata
 */
export function generateMassDefinitionsJson(): MassDefinitionsJsonOutput {
  // Initialize category index
  const titlesByCategory: TitlesByCategory = {};
  
  // Initialize rite index
  const titlesByRite: TitlesByRite = {};
  
  // Build category index
  MASS_DEFINITIONS_DATA.categories.forEach((cat: CategoryDefinition) => {
    titlesByCategory[cat.key] = [];
  });
  
  // Build rite index
  MASS_DEFINITIONS_DATA.rites.forEach((rite: RiteDefinition) => {
    titlesByRite[rite.key] = [];
  });
  
  // Process definitions and populate indexes
  MASS_DEFINITIONS_DATA.definitions.forEach((def: MassDefinition) => {
    // Add to category index
    if (titlesByCategory[def.category]) {
      titlesByCategory[def.category].push(def.key);
    }
    
    // Add to rite index (for each rite this definition belongs to)
    def.rites.forEach((rite) => {
      if (titlesByRite[rite]) {
        titlesByRite[rite].push(def.key);
      }
    });
  });
  
  // Return the complete JSON export structure
  return {
    _generator: 'mass-definitions-export.ts',
    _warning: 'Auto-generated from calendar/src/app/data/mass-definitions.ts during Angular build',
    _buildDate: new Date().toISOString(),
    categories: MASS_DEFINITIONS_DATA.categories,
    rites: MASS_DEFINITIONS_DATA.rites,
    definitions: MASS_DEFINITIONS_DATA.definitions,
    titlesByCategory,
    titlesByRite
  };
}
