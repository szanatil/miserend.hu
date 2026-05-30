import {WeekCompressionUtil, WeekEvent} from './week-compression-util';

function ev(dayOffset: number, hStart: number, mStart: number, hEnd: number, mEnd: number, title = 'mise'): WeekEvent {
  const weekStart = new Date(2026, 2, 9); // 2026-03-09 Monday (UTC-naive helper)
  const start = new Date(weekStart);
  start.setDate(weekStart.getDate() + dayOffset);
  start.setHours(hStart, mStart, 0, 0);
  const end = new Date(weekStart);
  end.setDate(weekStart.getDate() + dayOffset);
  end.setHours(hEnd, mEnd, 0, 0);
  return {start, end, title};
}

const WEEK_START = new Date(2026, 2, 9);  // Mon 2026-03-09
const WEEK_END = new Date(2026, 2, 16);   // Mon 2026-03-16 (exclusive)

describe('WeekCompressionUtil.analyze', () => {

  it('no-events: nem tömörít, üres out-of-range', () => {
    const result = WeekCompressionUtil.analyze({
      weekStart: WEEK_START, weekEnd: WEEK_END, events: [],
    });
    expect(result.shouldCompress).toBe(false);
    expect(result.diagnostics.reason).toBe('no-events');
    expect(result.diagnostics.totalEvents).toBe(0);
    expect(result.outOfRangeEvents).toEqual([]);
  });

  it('too-few-events: 2 esemény még nem éri el a threshold-ot, nem tömörít', () => {
    const result = WeekCompressionUtil.analyze({
      weekStart: WEEK_START, weekEnd: WEEK_END,
      events: [ev(0, 7, 0, 8, 0), ev(2, 18, 0, 19, 0)],
    });
    expect(result.shouldCompress).toBe(false);
    expect(result.diagnostics.reason).toBe('too-few-events');
    expect(result.diagnostics.totalEvents).toBe(2);
  });

  it('no-gap-detected: csak délelőtti, nincs esti — nem tömörít', () => {
    const result = WeekCompressionUtil.analyze({
      weekStart: WEEK_START, weekEnd: WEEK_END,
      events: [ev(0, 7, 0, 8, 0), ev(1, 8, 0, 9, 0), ev(2, 9, 0, 10, 0)],
    });
    expect(result.shouldCompress).toBe(false);
    expect(result.diagnostics.reason).toBe('no-gap-detected');
  });

  it('gap-too-small: 2 órás lyuk a 3-órás threshold alatt, nem tömörít', () => {
    const result = WeekCompressionUtil.analyze({
      weekStart: WEEK_START, weekEnd: WEEK_END,
      // morningLatest=12:00, eveningEarliest=14:00 → 2 óra gap < 3 óra threshold
      events: [ev(0, 9, 0, 10, 0), ev(1, 11, 0, 12, 0), ev(2, 14, 0, 15, 0)],
    });
    expect(result.shouldCompress).toBe(false);
    expect(result.diagnostics.reason).toBe('gap-too-small');
  });

  it('compress: tipikus reggel-este eset', () => {
    const result = WeekCompressionUtil.analyze({
      weekStart: WEEK_START, weekEnd: WEEK_END,
      events: [
        ev(0, 7, 0, 8, 0, 'reggeli mise hétfő'),
        ev(0, 18, 0, 19, 0, 'esti mise hétfő'),
        ev(2, 7, 30, 8, 30, 'reggeli mise szerda'),
        ev(4, 18, 30, 19, 30, 'esti mise péntek'),
      ],
    });
    expect(result.shouldCompress).toBe(true);
    expect(result.diagnostics.reason).toBe('compressed');
    expect(result.slotMinTime).toBe('07:00:00');
    expect(result.slotMaxTime).toBe('19:30:00');
    expect(result.diagnostics.gapStart).toBe('08:30');  // latest morning end
    expect(result.diagnostics.gapEnd).toBe('18:00');    // earliest evening start
    expect(result.diagnostics.gapSizeHours).toBe(9.5);
  });

  it('out-of-range: gap-en belüli esemény (nagyszombati vigília) bekerül a footer-listába', () => {
    const result = WeekCompressionUtil.analyze({
      weekStart: WEEK_START, weekEnd: WEEK_END,
      events: [
        ev(0, 7, 0, 8, 0),    // hétfő reggel
        ev(0, 18, 0, 19, 0),  // hétfő este
        ev(2, 7, 30, 8, 30),  // szerda reggel
        ev(4, 18, 30, 19, 30),// péntek este
        // Szombat 12:00 — TÉNYLEGESEN a gap-ben van (morningLatest=8:30, eveningEarliest=18:00).
        // 12:00 < 14:00 (eveningStartHour), tehát nem minősül esti eseménynek, így a
        // gap-detektálást nem zavarja meg, de az ablakon kívülre esik.
        ev(5, 12, 0, 13, 0, 'nagyszombati vigília'),
      ],
    });
    expect(result.shouldCompress).toBe(true);
    // A nagyszombati vigília a gap (08:30 - 18:00) közepében van → out-of-range.
    const titles = result.outOfRangeEvents.map(e => e.title);
    expect(titles).toContain('nagyszombati vigília');
  });

  it('out-of-range: kora-hajnali esemény ami a slotMinTime ELŐTT van', () => {
    const result = WeekCompressionUtil.analyze({
      weekStart: WEEK_START, weekEnd: WEEK_END,
      events: [
        ev(0, 6, 0, 7, 0, 'kora-hajnali'),  // ez a globális min
        ev(0, 9, 0, 10, 0),
        ev(1, 9, 0, 10, 0),
        ev(2, 18, 0, 19, 0),
        ev(3, 18, 0, 19, 0),
      ],
      options: {paddingHours: 0.5},  // 30 perc padding → slotMinTime = 05:30
    });
    expect(result.shouldCompress).toBe(true);
    expect(result.slotMinTime).toBe('05:30:00');
  });

  it('opciók: morningEndHour 11-re csökkentve elcsenheti a 11:30-as eseményt', () => {
    const result = WeekCompressionUtil.analyze({
      weekStart: WEEK_START, weekEnd: WEEK_END,
      events: [
        ev(0, 8, 0, 9, 0),
        ev(0, 11, 0, 11, 30),  // 11:30 — morningEnd=11 esetén már NEM délelőtti
        ev(1, 18, 0, 19, 0),
        ev(2, 18, 30, 19, 30),
      ],
      options: {morningEndHour: 11, minEventsThreshold: 3},
    });
    // morningEndHour=11 esetén csak a 8:00-as esemény számít délelőttinek
    // (vége 9:00 ≤ 11:00). A 11:30 kívül esik.
    expect(result.shouldCompress).toBe(true);
    expect(result.diagnostics.gapStart).toBe('09:00');
  });

  it('összes esemény a gap-ben (egy szokatlan templom déli misékkel) → nincs gap, nem tömörít', () => {
    const result = WeekCompressionUtil.analyze({
      weekStart: WEEK_START, weekEnd: WEEK_END,
      events: [
        ev(0, 12, 30, 13, 30),
        ev(1, 12, 30, 13, 30),
        ev(2, 13, 0, 14, 0),
      ],
    });
    expect(result.shouldCompress).toBe(false);
    expect(result.diagnostics.reason).toBe('no-gap-detected');
  });

  it('különböző napokon különböző órák — a global min/max-ot számoljuk', () => {
    const result = WeekCompressionUtil.analyze({
      weekStart: WEEK_START, weekEnd: WEEK_END,
      events: [
        ev(0, 7, 0, 8, 0),    // hétfő 7-8
        ev(2, 9, 30, 10, 30), // szerda 9:30-10:30 — ez a legkésőbbi reggeli
        ev(4, 17, 0, 18, 0),  // péntek 17-18 — ez a legkorábbi esti
        ev(6, 20, 0, 21, 0),  // vasárnap 20-21 — ez a globális max
      ],
    });
    expect(result.shouldCompress).toBe(true);
    expect(result.slotMinTime).toBe('07:00:00');
    expect(result.slotMaxTime).toBe('21:00:00');
    expect(result.diagnostics.gapStart).toBe('10:30');
    expect(result.diagnostics.gapEnd).toBe('17:00');
  });
});

describe('WeekCompressionUtil.minutesToTimeString', () => {
  it('00:00:00 a 0 percre', () => {
    expect(WeekCompressionUtil.minutesToTimeString(0)).toBe('00:00:00');
  });

  it('formattal a HH:MM:SS-t (másodperc mindig 00)', () => {
    expect(WeekCompressionUtil.minutesToTimeString(7 * 60)).toBe('07:00:00');
    expect(WeekCompressionUtil.minutesToTimeString(8 * 60 + 30)).toBe('08:30:00');
    expect(WeekCompressionUtil.minutesToTimeString(23 * 60 + 59)).toBe('23:59:00');
  });

  it('24:00:00 a felső határra', () => {
    expect(WeekCompressionUtil.minutesToTimeString(24 * 60)).toBe('24:00:00');
    expect(WeekCompressionUtil.minutesToTimeString(2000)).toBe('24:00:00');  // clamp
  });

  it('negatív érték 00:00:00-ra clamp-elve', () => {
    expect(WeekCompressionUtil.minutesToTimeString(-30)).toBe('00:00:00');
  });
});
