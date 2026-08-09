import {Calendar} from '@fullcalendar/core';
import timeGridPlugin from '@fullcalendar/timegrid';
import {WeekCompressionUtil, WeekEvent} from './week-compression-util';

/**
 * #358: VALÓDI render-teszt (nem csak aritmetika). Egy nyers FullCalendar
 * timeGridWeek-et mountolunk a document.body-ba, ráinjektáljuk a komponens
 * collapse-CSS-ét, és MÉRT geometriával igazoljuk, hogy:
 *  - az üres slot-lane-ek megkapják az `fc-empty-slot` osztályt (a foglaltak nem),
 *  - egy collapsed sor tényleg ~0 magasságú, egy nyitott sor nem,
 *  - egy KÖZÉPSŐ mise (nagyszombat 15:00) a rácsban marad, a reggeli és az esti
 *    mise KÖZÖTT — vagyis a tengely köré törik, nem rajta át.
 * Ez pont az a lyuk amit az audit jelzett: a korábbi tesztek sosem rendereltek rácsot.
 */
describe('#358 week-compression DOM collapse (valódi render)', () => {
  let host: HTMLElement;
  let styleEl: HTMLStyleElement;
  let calendar: Calendar | undefined;

  // A komponens CSS-ének a lényege (::ng-deep nélkül, mert itt nincs Angular-scope).
  const COLLAPSE_CSS = `
    .fc-timegrid-slots tr:has(> td.fc-timegrid-slot-lane.fc-empty-slot) > td {
      height: 0 !important; padding: 0 !important; line-height: 0 !important;
      overflow: hidden !important; border-top: 1px dashed #c9c9c9 !important;
    }
    .fc-timegrid-slots tr:has(> td.fc-timegrid-slot-lane.fc-empty-slot) > td > * {
      display: none !important;
    }`;

  beforeEach(() => {
    host = document.createElement('div');
    host.style.width = '900px';
    document.body.appendChild(host);
    styleEl = document.createElement('style');
    styleEl.textContent = COLLAPSE_CSS;
    document.head.appendChild(styleEl);
  });

  afterEach(() => {
    calendar?.destroy();
    calendar = undefined;
    host?.remove();
    styleEl?.remove();
  });

  it('a középső üres sávot összehúzza, a nagyszombati misét a helyén tartja', () => {
    // Ugyanazon a napon: reggel 08:00, nagyszombat 15:00, este 18:00.
    const DAY = '2026-03-09';
    const events = [
      {title: 'reggel', start: `${DAY}T08:00:00`, end: `${DAY}T09:00:00`},
      {title: 'nagyszombat', start: `${DAY}T15:00:00`, end: `${DAY}T16:00:00`},
      {title: 'este', start: `${DAY}T18:00:00`, end: `${DAY}T19:00:00`},
    ];

    // A collapsed-halmazt UGYANAZZAL a util-lal számoljuk, amit a komponens is használ.
    const weekEvents: WeekEvent[] = events.map(e => ({start: new Date(e.start), end: new Date(e.end), title: e.title}));
    const result = WeekCompressionUtil.analyze({
      weekStart: new Date(2026, 2, 9), weekEnd: new Date(2026, 2, 16),
      events: weekEvents, options: {slotDurationMinutes: 30},
    });
    expect(result.shouldCompress).toBe(true);
    const collapsed = new Set(result.collapsedSlotMinutes);

    calendar = new Calendar(host, {
      plugins: [timeGridPlugin],
      initialView: 'timeGridWeek',
      initialDate: DAY,
      slotDuration: '00:30',
      slotMinTime: result.slotMinTime,
      slotMaxTime: result.slotMaxTime,
      height: 'auto',
      headerToolbar: false,
      allDaySlot: false,
      slotLaneClassNames: (arg: any) =>
        collapsed.has(arg.date.getHours() * 60 + arg.date.getMinutes()) ? ['fc-empty-slot'] : [],
      events,
    });
    calendar.render();

    // 1) Lane-osztályozás: üres slot -> fc-empty-slot, foglalt -> nincs.
    const laneByTime: Record<string, HTMLElement> = {};
    host.querySelectorAll('td.fc-timegrid-slot-lane').forEach(l => {
      const t = l.getAttribute('data-time');
      if (t) laneByTime[t] = l as HTMLElement;
    });
    expect(laneByTime['10:00:00']?.classList.contains('fc-empty-slot')).toBe(true);   // üres
    expect(laneByTime['08:00:00']?.classList.contains('fc-empty-slot')).toBe(false);  // reggel
    expect(laneByTime['15:00:00']?.classList.contains('fc-empty-slot')).toBe(false);  // nagyszombat marad

    // 2) Sor-magasságok: collapsed sor ~0, nyitott sor nem.
    const rowOf = (time: string) => laneByTime[time]?.closest('tr') as HTMLElement;
    expect(rowOf('10:00:00').offsetHeight).toBeLessThan(4);
    expect(rowOf('08:00:00').offsetHeight).toBeGreaterThan(12);

    // 3) A nagyszombat 15:00 esemény FÜGGŐLEGESEN a reggeli és az esti KÖZÖTT van.
    const eventTop = (title: string): number => {
      const evEl = Array.from(host.querySelectorAll('.fc-timegrid-event'))
        .find(e => (e.textContent || '').includes(title)) as HTMLElement | undefined;
      return evEl ? evEl.getBoundingClientRect().top : NaN;
    };
    const morn = eventTop('reggel');
    const naga = eventTop('nagyszombat');
    const eve = eventTop('este');
    expect(morn).toBeLessThan(naga);
    expect(naga).toBeLessThan(eve);
  });

  /**
   * #358 regresszió: a misék a backendből PONT-ESEMÉNYKÉNT jönnek, `end` nélkül.
   *
   * Az eredeti hiba pontosan itt bújt meg: a komponens `!!e.start && !!e.end`-del
   * szűrt, ezért minden misét kidobott — a tömörítés némán nem csinált semmit, a
   * kapcsoló holt volt. A fenti teszt azért nem fogta meg, mert `end`-del adott
   * eseményeket, és a WeekEvent-listát kézzel építette, kihagyva ezt a lépést.
   *
   * Itt ugyanazt a leképezést hívjuk, amit a komponens (WeekCompressionUtil.toWeekEvents),
   * és valódi FullCalendaron mérjük a geometriát.
   */
  it('end nélküli pont-eseményeknél is tömörít (a toggle nem holt)', () => {
    const DAY = '2026-03-09';
    // Se `end`, se `allDay` — pont úgy, ahogy a miserend adja.
    const events = [
      {title: 'reggeli mise', start: `${DAY}T08:00:00`},
      {title: 'esti mise', start: `${DAY}T18:00:00`},
      {title: 'reggeli mise 2', start: `2026-03-10T08:00:00`},
      {title: 'esti mise 2', start: `2026-03-10T18:00:00`},
    ];

    calendar = new Calendar(host, {
      plugins: [timeGridPlugin],
      initialView: 'timeGridWeek',
      initialDate: DAY,
      slotDuration: '00:30:00',
      headerToolbar: false,
      height: 'auto',
      events,
    });
    calendar.render();

    // A komponens útja: a FullCalendar eseményeiből WeekEvent-lista.
    const weekEvents = WeekCompressionUtil.toWeekEvents(calendar.getEvents());

    expect(weekEvents.length)
      .toBe(events.length, 'Egyetlen end nélküli eseményt sem szabad kidobni.');
    weekEvents.forEach(we => {
      expect(we.end.getTime())
        .toBeGreaterThan(we.start.getTime(), 'end hiányában is legyen értelmes időtartam.');
    });

    const result = WeekCompressionUtil.analyze({
      weekStart: new Date(2026, 2, 9), weekEnd: new Date(2026, 2, 16),
      events: weekEvents, options: {slotDurationMinutes: 30},
    });

    expect(result.shouldCompress)
      .toBe(true, 'Reggel 8 és este 6 között van mit összehúzni.');
    expect(result.collapsedSlotMinutes.length).toBeGreaterThan(0);

    // A foglalt slotok nem tömörödnek.
    const collapsed = new Set(result.collapsedSlotMinutes);
    expect(collapsed.has(8 * 60)).toBe(false, 'A reggeli mise slotja marad.');
    expect(collapsed.has(18 * 60)).toBe(false, 'Az esti mise slotja marad.');
    expect(collapsed.has(12 * 60)).toBe(true, 'A délelőtt-délutáni holt sáv összehúzódik.');
  });

  /**
   * A `toWeekEvents` a start nélküli eseményt eldobja — az FullCalendarban elvileg
   * nem fordul elő, de ha mégis, ne szálljon el a mérés.
   */
  it('a start nélküli eseményt kihagyja', () => {
    const mapped = WeekCompressionUtil.toWeekEvents([
      {start: new Date('2026-03-09T08:00:00')},
      {start: null},
    ]);
    expect(mapped.length).toBe(1);
  });


  /**
   * #358 kimérés VALÓDI adaton: a Szent István-bazilika 2026-03-09..15 hete, ahogy az
   * Elasticsearchből kijön (20 mise: hétköznap 07:00 és 18:00, vasárnap 08:30-tól 18:00-ig).
   *
   * Ez a teszt nem csak azt mondja, hogy „tömörít", hanem meg is méri, MENNYIT — és
   * hogy közben egyetlen misés slot sem tűnik el. A vasárnapi 10:00/12:00/16:00 pont az
   * a „valami extra", amit a jegy kivételként említ.
   */
  it('valódi heti miserenden mérhetően kisebb a rács, és egy mise sem vész el', () => {
    const MASSES: Array<[string, string]> = [
      ['2026-03-09', '07:00'], ['2026-03-09', '18:00'],   // hétfő
      ['2026-03-10', '07:00'], ['2026-03-10', '18:00'],   // kedd
      ['2026-03-11', '07:00'], ['2026-03-11', '18:00'],   // szerda
      ['2026-03-12', '07:00'], ['2026-03-12', '18:00'],   // csütörtök
      ['2026-03-13', '07:00'], ['2026-03-13', '18:00'],   // péntek
      ['2026-03-14', '07:00'], ['2026-03-14', '18:00'],   // szombat
      ['2026-03-15', '08:30'], ['2026-03-15', '10:00'],   // vasárnap
      ['2026-03-15', '12:00'], ['2026-03-15', '16:00'],
      ['2026-03-15', '18:00'],
    ];

    const weekEvents = WeekCompressionUtil.toWeekEvents(
      MASSES.map(([d, t]) => ({start: new Date(`${d}T${t}:00`), title: 'Szentmise'}))
    );

    const result = WeekCompressionUtil.analyze({
      weekStart: new Date(2026, 2, 9), weekEnd: new Date(2026, 2, 16),
      events: weekEvents, options: {slotDurationMinutes: 30},
    });

    expect(result.shouldCompress).toBe(true);

    // Mennyi marad? A levágott fej/láb + a collapsed közép után.
    const visibleFrom = result.slotMinTime;
    const visibleTo = result.slotMaxTime;
    const toMin = (t: string) => {
      const [h, m] = t.split(':').map(Number);
      return h * 60 + m;
    };
    const teljes = 24 * 60 / 30;                                   // 48 slot egy teljes nap
    const ablak = (toMin(visibleTo) - toMin(visibleFrom)) / 30;    // fej/láb levágás után
    const marad = ablak - result.collapsedSlotMinutes.length;      // a közép összehúzása után

    // Konkrét, ellenőrizhető számok — ha a logika elcsúszik, itt bukik.
    expect(teljes).toBe(48);
    expect(ablak).toBeLessThan(teljes);
    expect(marad).toBeLessThan(ablak);
    expect(marad).toBeLessThanOrEqual(teljes / 2);

    // ...és közben EGYETLEN misés slot sem esett ki.
    const collapsed = new Set(result.collapsedSlotMinutes);
    weekEvents.forEach(e => {
      const slot = Math.floor((e.start.getHours() * 60 + e.start.getMinutes()) / 30) * 30;
      expect(collapsed.has(slot))
        .toBe(false, `A ${WeekCompressionUtil.minutesToTimeString(slot)} misés slot nem tömöríthető.`);
    });

    // A vasárnapi „extra" (10:00, 12:00, 16:00) épp a holt sávba esne — ezért marad nyitva.
    [10 * 60, 12 * 60, 16 * 60].forEach(min => {
      expect(collapsed.has(min))
        .toBe(false, `A ${WeekCompressionUtil.minutesToTimeString(min)} vasárnapi mise miatt nyitva marad.`);
    });

    // Diagnosztika a PR-hez.
    // eslint-disable-next-line no-console
    console.log(`[#358 mérés] teljes nap ${teljes} slot -> fej/láb levágva ${ablak} (${visibleFrom}..${visibleTo})`
      + ` -> ${result.collapsedSlotMinutes.length} slot összehúzva -> ${marad} látható`);
  });

});
