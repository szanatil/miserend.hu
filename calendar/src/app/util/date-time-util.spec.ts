import {DateTimeUtil} from './date-time-util';
import {Day} from '../enum/day';

/**
 * #374: DateTimeUtil tiszta dátum/idő-string építők lefedése. A getOnlyDateString/
 * getIsoString natív Date + padStart alapú, determinisztikus (nincs luxon/timezone-
 * függés); a getShortEnDay a hét napját képezi a Day enumra. Ezek hajtják az RRULE
 * dtstart-okat a naptárban — egy elrontott padding csendben rossz dátumot adna.
 */
describe('DateTimeUtil', () => {

  describe('getOnlyDateString', () => {
    it('egyjegyű hónapot/napot nullázva ad vissza', () => {
      expect(DateTimeUtil.getOnlyDateString(new Date(2026, 0, 5))).toBe('2026-01-05');
    });

    it('kétjegyű hónap/nap', () => {
      expect(DateTimeUtil.getOnlyDateString(new Date(2026, 11, 25))).toBe('2026-12-25');
    });

    it('vegyes (szeptember 9)', () => {
      expect(DateTimeUtil.getOnlyDateString(new Date(2026, 8, 9))).toBe('2026-09-09');
    });
  });

  describe('getIsoString', () => {
    it('dátum + nullázott idő', () => {
      expect(DateTimeUtil.getIsoString(new Date(2026, 2, 1, 7, 5, 9))).toBe('2026-03-01T07:05:09');
    });

    it('éjfél (00:00:00)', () => {
      expect(DateTimeUtil.getIsoString(new Date(2026, 2, 1, 0, 0, 0))).toBe('2026-03-01T00:00:00');
    });

    it('periodDate felülírja a dátumot, az idő a Date-ből jön', () => {
      expect(DateTimeUtil.getIsoString(new Date(2026, 2, 1, 7, 5, 9), '2026-06-15'))
        .toBe('2026-06-15T07:05:09');
    });
  });

  describe('getShortEnDay', () => {
    it('hétfő -> Day.MO', () => {
      // 2026-07-20 hétfő
      expect(DateTimeUtil.getShortEnDay(new Date(2026, 6, 20))).toBe(Day.MO);
    });

    it('vasárnap -> Day.SU', () => {
      // 2026-07-19 vasárnap
      expect(DateTimeUtil.getShortEnDay(new Date(2026, 6, 19))).toBe(Day.SU);
    });

    it('péntek -> Day.FR', () => {
      // 2026-07-17 péntek
      expect(DateTimeUtil.getShortEnDay(new Date(2026, 6, 17))).toBe(Day.FR);
    });
  });
});
