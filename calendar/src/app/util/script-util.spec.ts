import {ScriptUtil} from './script-util';

/**
 * #374: ScriptUtil tiszta segédfüggvények lefedése. A deepEqual sok helyen dönt
 * (modifiedSuggestions, isMassUnchanged), de valójában JSON.stringify-alapú — két
 * finom csapdával (kulcs-sorrend számít, undefined elnyelődik), amiket teszt rögzít.
 */
describe('ScriptUtil', () => {

  describe('deepEqual', () => {
    it('azonos tartalom -> true', () => {
      expect(ScriptUtil.deepEqual({a: 1, b: 2}, {a: 1, b: 2})).toBe(true);
    });

    it('eltérő érték -> false', () => {
      expect(ScriptUtil.deepEqual({a: 1}, {a: 2})).toBe(false);
    });

    it('tömb-sorrend számít', () => {
      expect(ScriptUtil.deepEqual([1, 2, 3], [1, 2, 3])).toBe(true);
      expect(ScriptUtil.deepEqual([1, 2], [2, 1])).toBe(false);
    });

    it('CSAPDA: a kulcs-sorrend eltérése false-t ad (JSON.stringify-alapú)', () => {
      expect(ScriptUtil.deepEqual({a: 1, b: 2}, {b: 2, a: 1})).toBe(false);
    });

    it('CSAPDA: az undefined mező elnyelődik -> true', () => {
      expect(ScriptUtil.deepEqual({a: 1}, {a: 1, b: undefined})).toBe(true);
    });
  });

  describe('clone', () => {
    it('mély másolat: a klón mutálása nem érinti az eredetit', () => {
      const orig = {a: {b: 1}, list: [1, 2]};
      const copy = ScriptUtil.clone(orig);
      copy.a.b = 99;
      copy.list.push(3);
      expect(orig.a.b).toBe(1);
      expect(orig.list).toEqual([1, 2]);
      expect(copy.a.b).toBe(99);
    });
  });

  describe('isNull / isNotNull', () => {
    it('isNull: null és undefined -> true; 0/üres string -> false', () => {
      expect(ScriptUtil.isNull(null)).toBe(true);
      expect(ScriptUtil.isNull(undefined)).toBe(true);
      expect(ScriptUtil.isNull(0)).toBe(false);
      expect(ScriptUtil.isNull('')).toBe(false);
    });

    it('isNotNull az inverze (0 és üres string not-null)', () => {
      expect(ScriptUtil.isNotNull(null)).toBe(false);
      expect(ScriptUtil.isNotNull(undefined)).toBe(false);
      expect(ScriptUtil.isNotNull(0)).toBe(true);
      expect(ScriptUtil.isNotNull('')).toBe(true);
    });
  });
});
