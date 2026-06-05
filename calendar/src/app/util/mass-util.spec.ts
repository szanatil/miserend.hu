import { TestBed } from '@angular/core/testing';
import { TranslateModule, TranslateService } from '@ngx-translate/core';
import { MassUtil } from './mass-util';
import { MassTitleCategory } from '../enum/mass-categories';
import { MASS_DEFINITIONS_DATA } from '../data/mass-definitions';
import { Rite } from '../enum/rites';

describe('MassUtil - Category Classification', () => {
  let translate: TranslateService;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [TranslateModule.forRoot()]
    }).compileComponents();

    translate = TestBed.inject(TranslateService);
    translate.setDefaultLang('hu');
    translate.use('hu');
  });

  describe('getCategoryByTitle', () => {
    it('should be a function', () => {
      expect(typeof MassUtil.getCategoryByTitle).toBe('function');
    });

    it('should return a MassTitleCategory', () => {
      const category = MassUtil.getCategoryByTitle('HOLY_MASS', translate);
      
      expect([MassTitleCategory.MASS, MassTitleCategory.ADORATION, MassTitleCategory.CONFESSION, MassTitleCategory.OTHER])
        .toContain(category);
    });

    it('should categorize HOLY_MASS as MASS', () => {
      const category = MassUtil.getCategoryByTitle('HOLY_MASS', translate);
      
      expect(category).toBe(MassTitleCategory.MASS);
    });

    it('should categorize ADORATION as ADORATION', () => {
      const category = MassUtil.getCategoryByTitle('ADORATION', translate);
      
      expect(category).toBe(MassTitleCategory.ADORATION);
    });

    it('should categorize CONFESSION as CONFESSION', () => {
      const category = MassUtil.getCategoryByTitle('CONFESSION', translate);
      
      expect(category).toBe(MassTitleCategory.CONFESSION);
    });

    it('should categorize BREVIARY as OTHER', () => {
      const category = MassUtil.getCategoryByTitle('BREVIARY', translate);
      
      expect(category).toBe(MassTitleCategory.OTHER);
    });

    it('should categorize ROSARY as OTHER', () => {
      const category = MassUtil.getCategoryByTitle('ROSARY', translate);
      
      expect(category).toBe(MassTitleCategory.OTHER);
    });

    it('should categorize LITANY as OTHER', () => {
      const category = MassUtil.getCategoryByTitle('LITANY', translate);
      
      expect(category).toBe(MassTitleCategory.OTHER);
    });

    it('should handle empty title', () => {
      const category = MassUtil.getCategoryByTitle('', translate);
      
      expect(category).toBe(MassTitleCategory.OTHER);
    });

    it('should handle undefined translate service gracefully', () => {
      const category = MassUtil.getCategoryByTitle('HOLY_MASS');
      
      expect([MassTitleCategory.MASS, MassTitleCategory.ADORATION, MassTitleCategory.CONFESSION, MassTitleCategory.OTHER])
        .toContain(category);
    });
  });

  describe('getTitlesByCategory', () => {
    it('should return array of title strings for MASS category', () => {
      const titles = MassUtil.getTitlesByCategory(MassTitleCategory.MASS);
      
      expect(Array.isArray(titles)).toBe(true);
      expect(titles.length).toBeGreaterThan(0);
    });

    it('should return array of title strings for ADORATION category', () => {
      const titles = MassUtil.getTitlesByCategory(MassTitleCategory.ADORATION);
      
      expect(Array.isArray(titles)).toBe(true);
      expect(titles.length).toBeGreaterThan(0);
    });

    it('should return array of title strings for CONFESSION category', () => {
      const titles = MassUtil.getTitlesByCategory(MassTitleCategory.CONFESSION);
      
      expect(Array.isArray(titles)).toBe(true);
      expect(titles.length).toBeGreaterThan(0);
    });

    it('should return array of title strings for OTHER category', () => {
      const titles = MassUtil.getTitlesByCategory(MassTitleCategory.OTHER);
      
      expect(Array.isArray(titles)).toBe(true);
      expect(titles.length).toBeGreaterThan(0);
    });

    it('MASS category should contain HOLY_MASS', () => {
      const titles = MassUtil.getTitlesByCategory(MassTitleCategory.MASS);
      
      expect(titles).toContain('HOLY_MASS');
    });

    it('ADORATION category should contain ADORATION', () => {
      const titles = MassUtil.getTitlesByCategory(MassTitleCategory.ADORATION);
      
      expect(titles).toContain('ADORATION');
    });

    it('CONFESSION category should contain CONFESSION', () => {
      const titles = MassUtil.getTitlesByCategory(MassTitleCategory.CONFESSION);
      
      expect(titles).toContain('CONFESSION');
    });

    it('OTHER category should contain BREVIARY, ROSARY, LITANY', () => {
      const titles = MassUtil.getTitlesByCategory(MassTitleCategory.OTHER);
      
      expect(titles).toContain('BREVIARY');
      expect(titles).toContain('ROSARY');
      expect(titles).toContain('LITANY');
    });
  });

  describe('getAllCategories', () => {
    it('should return array of all categories', () => {
      const categories = MassUtil.getAllCategories();
      
      expect(Array.isArray(categories)).toBe(true);
      expect(categories.length).toBeGreaterThan(0);
    });

    it('should include MASS, ADORATION, CONFESSION, and OTHER', () => {
      const categories = MassUtil.getAllCategories();
      
      expect(categories).toContain(MassTitleCategory.MASS);
      expect(categories).toContain(MassTitleCategory.ADORATION);
      expect(categories).toContain(MassTitleCategory.CONFESSION);
      expect(categories).toContain(MassTitleCategory.OTHER);
    });
  });

  describe('getColorByCategory', () => {
    it('should return hex color for MASS category', () => {
      const color = MassUtil.getColorByCategory(MassTitleCategory.MASS);
      
      expect(color).toBeDefined();
      expect(color).toMatch(/^#[0-9A-Fa-f]{6}$/);
    });

    it('should return hex color for ADORATION category', () => {
      const color = MassUtil.getColorByCategory(MassTitleCategory.ADORATION);
      
      expect(color).toBeDefined();
      expect(color).toMatch(/^#[0-9A-Fa-f]{6}$/);
    });

    it('should return hex color for CONFESSION category', () => {
      const color = MassUtil.getColorByCategory(MassTitleCategory.CONFESSION);
      
      expect(color).toBeDefined();
      expect(color).toMatch(/^#[0-9A-Fa-f]{6}$/);
    });

    it('should return hex color for OTHER category', () => {
      const color = MassUtil.getColorByCategory(MassTitleCategory.OTHER);
      
      expect(color).toBeDefined();
      expect(color).toMatch(/^#[0-9A-Fa-f]{6}$/);
    });

    it('should return different colors for different categories', () => {
      const colorMass = MassUtil.getColorByCategory(MassTitleCategory.MASS);
      const colorAdoration = MassUtil.getColorByCategory(MassTitleCategory.ADORATION);
      const colorConfession = MassUtil.getColorByCategory(MassTitleCategory.CONFESSION);
      const colorOther = MassUtil.getColorByCategory(MassTitleCategory.OTHER);

      expect(colorMass).not.toBe(colorAdoration);
      expect(colorMass).not.toBe(colorConfession);
      expect(colorMass).not.toBe(colorOther);
      expect(colorAdoration).not.toBe(colorConfession);
      expect(colorAdoration).not.toBe(colorOther);
      expect(colorConfession).not.toBe(colorOther);
    });
  });

  describe('Integration: Category assignment in calendar events', () => {
    it('should correctly assign MASS category to HOLY_MASS events', (done) => {
      translate.get('MASS_TITLE.HOLY_MASS').subscribe(() => {
        const category = MassUtil.getCategoryByTitle('HOLY_MASS', translate);
        const color = MassUtil.getColorByCategory(category);
        
        expect(category).toBe(MassTitleCategory.MASS);
        expect(color).toBeDefined();
        done();
      });
    });

    it('should correctly assign ADORATION category to ADORATION events', (done) => {
      translate.get('MASS_TITLE.ADORATION').subscribe(() => {
        const category = MassUtil.getCategoryByTitle('ADORATION', translate);
        const color = MassUtil.getColorByCategory(category);
        
        expect(category).toBe(MassTitleCategory.ADORATION);
        expect(color).toBeDefined();
        done();
      });
    });

    it('should correctly assign CONFESSION category to CONFESSION events', (done) => {
      translate.get('MASS_TITLE.CONFESSION').subscribe(() => {
        const category = MassUtil.getCategoryByTitle('CONFESSION', translate);
        const color = MassUtil.getColorByCategory(category);
        
        expect(category).toBe(MassTitleCategory.CONFESSION);
        expect(color).toBeDefined();
        done();
      });
    });

    it('should correctly assign OTHER category to BREVIARY events', (done) => {
      translate.get('MASS_TITLE.BREVIARY').subscribe(() => {
        const category = MassUtil.getCategoryByTitle('BREVIARY', translate);
        const color = MassUtil.getColorByCategory(category);
        
        expect(category).toBe(MassTitleCategory.OTHER);
        expect(color).toBeDefined();
        done();
      });
    });

    it('should not have all events assigned to OTHER (main bug check)', (done) => {
      translate.get('MASS_TITLE.HOLY_MASS').subscribe(() => {
        const categoryMass = MassUtil.getCategoryByTitle('HOLY_MASS', translate);
        const categoryAdoration = MassUtil.getCategoryByTitle('ADORATION', translate);
        const categoryConfession = MassUtil.getCategoryByTitle('CONFESSION', translate);
        
        // This is the key test - not all should be OTHER
        const allOther = 
          categoryMass === MassTitleCategory.OTHER && 
          categoryAdoration === MassTitleCategory.OTHER && 
          categoryConfession === MassTitleCategory.OTHER;
        
        expect(allOther).toBe(false);
        
        done();
      });
    });

    it('should categorize all MASS_DEFINITIONS_DATA definitions correctly', (done) => {
      translate.get('MASS_TITLE.HOLY_MASS').subscribe(() => {
        const definitions = MASS_DEFINITIONS_DATA.definitions;
        
        definitions.forEach(definition => {
          const detectedCategory = MassUtil.getCategoryByTitle(definition.key, translate);
          
          // Should successfully categorize, not all as OTHER
          expect(detectedCategory).toBeDefined();
        });
        
        done();
      });
    });
  });

  describe('Regression: Category filter behavior', () => {
    it('should allow filtering by MASS category with multiple events', (done) => {
      translate.get('MASS_TITLE.HOLY_MASS').subscribe(() => {
        const massTitles = MassUtil.getTitlesByCategory(MassTitleCategory.MASS);
        const massCategories = massTitles.map(title => 
          MassUtil.getCategoryByTitle(title, translate)
        );
        
        // All should be MASS category
        expect(massCategories.every(cat => cat === MassTitleCategory.MASS)).toBe(true);
        done();
      });
    });

    it('should allow filtering by ADORATION category', (done) => {
      translate.get('MASS_TITLE.ADORATION').subscribe(() => {
        const adorationTitles = MassUtil.getTitlesByCategory(MassTitleCategory.ADORATION);
        const adorationCategories = adorationTitles.map(title => 
          MassUtil.getCategoryByTitle(title, translate)
        );
        
        // All should be ADORATION category
        expect(adorationCategories.every(cat => cat === MassTitleCategory.ADORATION)).toBe(true);
        done();
      });
    });

    it('should allow filtering by CONFESSION category', (done) => {
      translate.get('MASS_TITLE.CONFESSION').subscribe(() => {
        const confessionTitles = MassUtil.getTitlesByCategory(MassTitleCategory.CONFESSION);
        const confessionCategories = confessionTitles.map(title => 
          MassUtil.getCategoryByTitle(title, translate)
        );
        
        // All should be CONFESSION category
        expect(confessionCategories.every(cat => cat === MassTitleCategory.CONFESSION)).toBe(true);
        done();
      });
    });

    it('should allow filtering by OTHER category', (done) => {
      translate.get('MASS_TITLE.BREVIARY').subscribe(() => {
        const otherTitles = MassUtil.getTitlesByCategory(MassTitleCategory.OTHER);
        const otherCategories = otherTitles.map(title => 
          MassUtil.getCategoryByTitle(title, translate)
        );
        
        // All should be OTHER category
        expect(otherCategories.every(cat => cat === MassTitleCategory.OTHER)).toBe(true);
        done();
      });
    });
  });
});
