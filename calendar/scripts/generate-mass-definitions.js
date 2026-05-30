/**
 * Script to generate mass-definitions.json from TypeScript definitions
 * Run during Angular build process
 * Output: webapp/mass-definitions.json
 * 
 * This script uses ts-node or tsx to compile and run TypeScript
 */

const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

/**
 * Check if a package is installed
 */
function isPackageInstalled(packageName) {
  try {
    execSync(`npm list ${packageName}`, { 
      cwd: path.join(__dirname, '..'),
      stdio: 'ignore'
    });
    return true;
  } catch {
    return false;
  }
}

/**
 * Get available TypeScript runner
 */
function getTypeScriptRunner() {
  // Try tsx first (lighter weight)
  if (isPackageInstalled('tsx')) {
    return 'tsx';
  }
  
  // Fallback to ts-node
  if (isPackageInstalled('ts-node')) {
    return 'ts-node';
  }
  
  // Check if npx can find them globally
  try {
    execSync('npx tsx --version', { stdio: 'ignore' });
    return 'npx tsx';
  } catch {
    try {
      execSync('npx ts-node --version', { stdio: 'ignore' });
      return 'npx ts-node';
    } catch {
      return null;
    }
  }
}

/**
 * Main function to generate mass-definitions.json
 */
function generateMassDefinitionsJson() {
  try {
    console.log('[GENERATE] Starting mass-definitions.json generation...');

    // Create the temporary TypeScript executor script
    const tempScriptPath = path.join(__dirname, '.temp-mass-def-generator.ts');
    
    const tempScript = `
import { generateMassDefinitionsJson } from '../src/app/data/mass-definitions-export';
import * as fs from 'fs';
import * as path from 'path';

try {
  // Call the export function
  const jsonData = generateMassDefinitionsJson();
  
  // Determine output path
  const outputPath = path.join(__dirname, '..', '..', 'webapp', 'mass-definitions.json');
  
  // Create directory if needed
  const outputDir = path.dirname(outputPath);
  if (!fs.existsSync(outputDir)) {
    fs.mkdirSync(outputDir, { recursive: true });
  }
  
  // Write JSON file
  fs.writeFileSync(outputPath, JSON.stringify(jsonData, null, 2), 'utf-8');
  
  console.log(\`✓ mass-definitions.json generated successfully at \${outputPath}\`);
  console.log(\`  - Categories: \${jsonData.categories.length}\`);
  console.log(\`  - Definitions: \${jsonData.definitions.length}\`);
  console.log(\`  - Rites: \${jsonData.rites.length}\`);
  
  process.exit(0);
} catch (error) {
  console.error('✗ Error generating mass-definitions.json:', error instanceof Error ? error.message : error);
  process.exit(1);
}
`;

    fs.writeFileSync(tempScriptPath, tempScript, 'utf-8');
    
    // Determine which TypeScript runner to use
    const runner = getTypeScriptRunner();
    
    if (!runner) {
      throw new Error(
        'No TypeScript runtime found. Please install one of:\\n' +
        '  - npm install --save-dev tsx\\n' +
        '  - npm install --save-dev ts-node\\n' +
        'Or use fallback by setting SKIP_TS_NODE=1'
      );
    }

    console.log(`[GENERATE] Using ${runner} for TypeScript execution...`);
    
    // Execute the temporary script
    try {
      execSync(`${runner} ${tempScriptPath}`, {
        cwd: path.join(__dirname, '..'),
        stdio: 'inherit'
      });
    } finally {
      // Clean up temp file
      if (fs.existsSync(tempScriptPath)) {
        fs.unlinkSync(tempScriptPath);
      }
    }
    
  } catch (error) {
    console.error('[ERROR] Failed to generate mass-definitions.json');
    console.error(error instanceof Error ? error.message : error);
    
    // Fallback: create minimal JSON
    if (process.env.ALLOW_FALLBACK === '1') {
      console.log('[FALLBACK] Creating minimal fallback JSON...');
      
      const fallbackData = {
        _generator: 'mass-definitions-export.ts (fallback)',
        _warning: 'This JSON was generated in fallback mode. TypeScript compilation failed.',
        categories: [],
        rites: [],
        definitions: [],
        titlesByCategory: {},
        titlesByRite: {}
      };

      const outputPath = path.join(__dirname, '..', '..', 'webapp', 'mass-definitions.json');
      const outputDir = path.dirname(outputPath);
      
      if (!fs.existsSync(outputDir)) {
        fs.mkdirSync(outputDir, { recursive: true });
      }

      fs.writeFileSync(outputPath, JSON.stringify(fallbackData, null, 2), 'utf-8');
      console.log(`[FALLBACK] JSON created at ${outputPath}`);
      process.exit(0);
    } else {
      process.exit(1);
    }
  }
}

// Run the function
generateMassDefinitionsJson();
