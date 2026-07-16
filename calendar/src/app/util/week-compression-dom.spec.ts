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
});
