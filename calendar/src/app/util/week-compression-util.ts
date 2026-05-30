/**
 * #358: a heti naptár-nézet idősáv-tömörítése.
 *
 * borazslo issue-leírása: „A hét nézet nehezen fér el egy képernyőn, mert hát
 * a reggeli misék és az esti misék között jó nagy a távolság. De nem lehet
 * simán kiiktatni a közepét sem az éjszakát, mert van olyan hogy nagyszombat,
 * és van olyan hogy valami extra."
 *
 * Ez a util tisztán számolja a tömörítési határokat a heti nézet eseményeiből,
 * és felsorolja azokat az eseményeket, amik a tömörített ablakból kiesnek
 * (így a UI footer-panelben mégis megjeleníthetők, sehol nem veszik el adat).
 */

export interface CompressionInput {
  /** Az aktuális nézet kezdő dátuma (inclusive). */
  weekStart: Date;
  /** Az aktuális nézet vég-dátuma (exclusive). */
  weekEnd: Date;
  /** Az események listája. Csak `start` és `end` (Date) kötelező; egyebek átmennek. */
  events: ReadonlyArray<WeekEvent>;
  /** Kapcsolók — alapértelmezés ipari. */
  options?: CompressionOptions;
}

export interface WeekEvent {
  start: Date;
  end: Date;
  title?: string;
  extendedProps?: Record<string, any>;
}

export interface CompressionOptions {
  /** Reggel/délelőtt határa (óra). Default: 12. */
  morningEndHour?: number;
  /** Délután/este határa (óra). Default: 14. */
  eveningStartHour?: number;
  /** Minimum „nagy lyuk" amit megéri tömöríteni (óra). Default: 3. */
  minGapHours?: number;
  /** Minimum eseményszám amelynél tömörítünk. Kevesebbnél nem érdemes. Default: 3. */
  minEventsThreshold?: number;
  /** Hozzáad puffer-órákat a `slotMinTime` előtt és a `slotMaxTime` után. Default: 0. */
  paddingHours?: number;
}

export interface CompressionResult {
  /** Aktívan kell-e tömöríteni. Ha false, a default slotMinTime/slotMaxTime maradjon. */
  shouldCompress: boolean;
  /** Az ajánlott `slotMinTime` ('HH:MM:SS'). */
  slotMinTime: string;
  /** Az ajánlott `slotMaxTime` ('HH:MM:SS'). */
  slotMaxTime: string;
  /**
   * Azok az események, amik a (slotMinTime, slotMaxTime) ablakon KÍVÜL esnek
   * — vagy mert előbb vannak, vagy utóbb, vagy mert a kiesett gap-ben vannak.
   * A UI ezeket egy footer-panelben tudja megmutatni „Egyéb alkalmak ezen a héten"
   * címen.
   */
  outOfRangeEvents: WeekEvent[];
  /** Diagnosztika a megjelenítéshez (tooltip). */
  diagnostics: CompressionDiagnostics;
}

export interface CompressionDiagnostics {
  totalEvents: number;
  reason:
    | 'no-events'
    | 'too-few-events'
    | 'no-gap-detected'
    | 'gap-too-small'
    | 'compressed';
  /** A felismert gap kezdete (HH:MM) — csak ha compressed. */
  gapStart?: string;
  /** A felismert gap vége (HH:MM) — csak ha compressed. */
  gapEnd?: string;
  /** A felismert gap mérete órában — csak ha compressed. */
  gapSizeHours?: number;
}

export class WeekCompressionUtil {

  static readonly DEFAULTS: Required<CompressionOptions> = {
    morningEndHour: 12,
    eveningStartHour: 14,
    minGapHours: 3,
    minEventsThreshold: 3,
    paddingHours: 0,
  };

  /**
   * Központi belépő. Idempotens, pure-function — semmi side-effect.
   */
  static analyze(input: CompressionInput): CompressionResult {
    const opts: Required<CompressionOptions> = {...WeekCompressionUtil.DEFAULTS, ...(input.options ?? {})};

    // 1. Szűrjük a heti nézet idősávjába tartozó eseményeket.
    const weekEvents = (input.events ?? []).filter(e =>
      e.start && e.end
      && e.end.getTime() > input.weekStart.getTime()
      && e.start.getTime() < input.weekEnd.getTime(),
    );

    const totalEvents = weekEvents.length;

    // 2. Korai kilépés: nincs vagy túl kevés esemény.
    if (totalEvents === 0) {
      return WeekCompressionUtil.noCompressResult(totalEvents, 'no-events', []);
    }
    if (totalEvents < opts.minEventsThreshold) {
      return WeekCompressionUtil.noCompressResult(totalEvents, 'too-few-events', []);
    }

    // 3. Számoljuk a globális min/max órát az események alapján.
    let globalMinMin = 24 * 60;  // perces felbontásban
    let globalMaxMin = 0;
    let morningLatestMin = 0;    // legkésőbbi délelőtti (morningEndHour előtt befejeződő) esemény vége
    let eveningEarliestMin = 24 * 60;  // legkorábbi esti (eveningStartHour után kezdődő) esemény eleje

    for (const e of weekEvents) {
      const startMin = e.start.getHours() * 60 + e.start.getMinutes();
      const endMin = e.end.getHours() * 60 + e.end.getMinutes();
      // Ha az esemény ENDe „átnyúlik másnapra", clamp 24:00-ra (a gap-analízis a napon belül érvényes).
      const cappedEnd = endMin > 0 && endMin < startMin ? 24 * 60 : endMin;

      if (startMin < globalMinMin) globalMinMin = startMin;
      if (cappedEnd > globalMaxMin) globalMaxMin = cappedEnd;

      // Délelőtti: az esemény vége strictly a morningEndHour előtt van.
      if (cappedEnd <= opts.morningEndHour * 60) {
        if (cappedEnd > morningLatestMin) morningLatestMin = cappedEnd;
      }
      // Esti: az esemény kezdete a eveningStartHour után van.
      if (startMin >= opts.eveningStartHour * 60) {
        if (startMin < eveningEarliestMin) eveningEarliestMin = startMin;
      }
    }

    // 4. Detektáljuk a gap-et.
    //    Ha nincs délelőtti VAGY nincs esti esemény, nincs értelme tömöríteni
    //    (mert akkor nincs amibe ütközni az amúgy üres közepet).
    const hasMorning = morningLatestMin > 0;
    const hasEvening = eveningEarliestMin < 24 * 60;

    if (!hasMorning || !hasEvening) {
      return WeekCompressionUtil.noCompressResult(totalEvents, 'no-gap-detected', []);
    }

    const gapMinutes = eveningEarliestMin - morningLatestMin;
    if (gapMinutes < opts.minGapHours * 60) {
      return WeekCompressionUtil.noCompressResult(totalEvents, 'gap-too-small', []);
    }

    // 5. Tömörítés. A slotMin/Max a globális min/max + opcionális padding.
    const padMin = Math.max(0, opts.paddingHours) * 60;
    const slotMinMin = Math.max(0, globalMinMin - padMin);
    const slotMaxMin = Math.min(24 * 60, globalMaxMin + padMin);

    // 6. Out-of-range események: a tömörítési ablakon kívülre eső események.
    //    Egy esemény out-of-range ha:
    //    - a vége korábban van mint slotMinMin, VAGY
    //    - a kezdete később mint slotMaxMin, VAGY
    //    - teljesen a gap-en belül van (morningLatestMin .. eveningEarliestMin)
    const outOfRange: WeekEvent[] = [];
    for (const e of weekEvents) {
      const startMin = e.start.getHours() * 60 + e.start.getMinutes();
      const endMin = e.end.getHours() * 60 + e.end.getMinutes();
      const cappedEnd = endMin > 0 && endMin < startMin ? 24 * 60 : endMin;

      const beforeWindow = cappedEnd <= slotMinMin;
      const afterWindow = startMin >= slotMaxMin;
      // gap-event: teljesen a kiesett középső ablakban (a gap szigorúan a morningLatestMin
      // utántól az eveningEarliestMin előtt). Az események amik a gap-be esnek
      // megjelennek a slot-tartományban (mert a slot magában foglalja a gap-et),
      // de UI-szinten szeretnénk ŐKET is megemlíteni a footerben.
      const inGap = startMin >= morningLatestMin && cappedEnd <= eveningEarliestMin
                    && (startMin > morningLatestMin || cappedEnd < eveningEarliestMin);

      if (beforeWindow || afterWindow || inGap) {
        outOfRange.push(e);
      }
    }

    return {
      shouldCompress: true,
      slotMinTime: WeekCompressionUtil.minutesToTimeString(slotMinMin),
      slotMaxTime: WeekCompressionUtil.minutesToTimeString(slotMaxMin),
      outOfRangeEvents: outOfRange,
      diagnostics: {
        totalEvents,
        reason: 'compressed',
        gapStart: WeekCompressionUtil.minutesToTimeString(morningLatestMin).slice(0, 5),
        gapEnd: WeekCompressionUtil.minutesToTimeString(eveningEarliestMin).slice(0, 5),
        gapSizeHours: Math.round(gapMinutes / 60 * 10) / 10,
      },
    };
  }

  private static noCompressResult(
    totalEvents: number,
    reason: CompressionDiagnostics['reason'],
    outOfRangeEvents: WeekEvent[],
  ): CompressionResult {
    return {
      shouldCompress: false,
      slotMinTime: '00:00:00',
      slotMaxTime: '24:00:00',
      outOfRangeEvents,
      diagnostics: {totalEvents, reason},
    };
  }

  /** 0-1440 perc → 'HH:MM:SS'. 1440-re 24:00:00 megy vissza (FullCalendar-konform). */
  static minutesToTimeString(min: number): string {
    const clamped = Math.max(0, Math.min(24 * 60, Math.round(min)));
    const h = Math.floor(clamped / 60);
    const m = clamped % 60;
    return `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}:00`;
  }
}
