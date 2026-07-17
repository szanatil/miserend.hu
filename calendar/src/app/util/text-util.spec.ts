import {TextUtil} from './text-util';

/**
 * #374: TextUtil tiszta string-építő helperek lefedése. Nincs TestBed/DOM — sima import.
 */
describe('TextUtil', () => {

  describe('concatDays', () => {
    const sep = ', ';
    const lastSep = ' és ';

    it('üres lista -> üres string', () => {
      expect(TextUtil.concatDays([], sep, lastSep)).toBe('');
    });

    it('egy elem -> önmaga', () => {
      expect(TextUtil.concatDays(['H'], sep, lastSep)).toBe('H');
    });

    it('két elem -> lastSeparator', () => {
      expect(TextUtil.concatDays(['H', 'K'], sep, lastSep)).toBe('H és K');
    });

    it('három elem -> separator + lastSeparator', () => {
      expect(TextUtil.concatDays(['H', 'K', 'Sze'], sep, lastSep)).toBe('H, K és Sze');
    });

    it('négy elem', () => {
      expect(TextUtil.concatDays(['H', 'K', 'Sze', 'Cs'], sep, lastSep)).toBe('H, K, Sze és Cs');
    });
  });

  describe('getReadableDuration', () => {
    it('üres duration -> üres string', () => {
      expect(TextUtil.getReadableDuration({})).toBe('');
    });

    it('csak óra', () => {
      expect(TextUtil.getReadableDuration({hours: 1})).toBe('1 óra');
    });

    it('nulla perc IS szerepel (0 !== undefined)', () => {
      expect(TextUtil.getReadableDuration({minutes: 0})).toBe('0 perc');
    });

    it('óra és perc', () => {
      expect(TextUtil.getReadableDuration({hours: 1, minutes: 30})).toBe('1 óra, 30 perc');
    });

    it('nap, óra, perc együtt', () => {
      expect(TextUtil.getReadableDuration({days: 1, hours: 2, minutes: 30})).toBe('1 nap, 2 óra, 30 perc');
    });
  });
});
