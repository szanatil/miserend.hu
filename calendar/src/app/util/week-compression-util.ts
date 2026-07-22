/**
 * #358: a heti naptár-nézet idősáv-tömörítése — VALÓDI törött időtengely.
 *
 * borazslo issue-leírása: „A hét nézet nehezen fér el egy képernyőn, mert hát
 * a reggeli misék és az esti misék között jó nagy a távolság. De nem lehet
 * simán kiiktatni a közepét sem az éjszakát, mert van olyan hogy nagyszombat,
 * és van olyan hogy valami extra."
 *
 * Két, egymást kiegészítő eszköz:
 *  1. `slotMinTime`/`slotMaxTime` — a nap ELEJÉN (első mise előtt) és VÉGÉN
 *     (utolsó mise után) lévő üres sávot levágja (a FullCalendar ki sem rajzolja).
 *  2. `collapsedSlotMinutes` — a KÖZÉPSŐ üres slotok (reggel↔este közti holt idő)
 *     minute-of-day listája. A UI ezeket CSS-sel 0 magasságúra húzza, így az este
 *     felcsúszik a reggel alá — de EGY foglalt slot (pl. Nagyszombat 15:00) SOSEM
 *     kerül a listába, ezért az köré „törik" a tengely, és a helyén marad.
 *
 * Tisztán számoló pure-function, semmi side-effect.
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
  /** A naptár slot-felbontása percben (a FullCalendar `slotDuration`-jével EGYEZZEN). Default: 30. */
  slotDurationMinutes?: number;
}

export interface CompressionResult {
  /** Aktívan kell-e tömöríteni. Ha false, a default slotMinTime/slotMaxTime maradjon. */
  shouldCompress: boolean;
  /** Az ajánlott `slotMinTime` ('HH:MM:SS'). Slot-határra igazítva. */
  slotMinTime: string;
  /** Az ajánlott `slotMaxTime` ('HH:MM:SS'). Slot-határra igazítva. */
  slotMaxTime: string;
  /**
   * #358: azoknak a KÖZÉPSŐ üres slotoknak a minute-of-day értékei (slot-kezdet),
   * amiket a UI-nak 0 magasságúra kell húznia. A [slotMin, slotMax) ablakon belül
   * minden slot-kezdet, amit EGYETLEN esemény sem fed le. A foglalt slotok (bármely
   * mise, akár egy középső Nagyszombat) kimaradnak — köréjük „törik" a tengely.
   */
  collapsedSlotMinutes: number[];
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
  /** Hány középső slotot húzunk össze — csak ha compressed. */
  collapsedSlotCount?: number;
}

export class WeekCompressionUtil {

  static readonly DEFAULTS: Required<CompressionOptions> = {
    morningEndHour: 12,
    eveningStartHour: 14,
    minGapHours: 3,
    minEventsThreshold: 3,
    paddingHours: 0,
    slotDurationMinutes: 30,
  };

  /**
   * Központi belépő. Idempotens, pure-function — semmi side-effect.
   */
  static analyze(input: CompressionInput): CompressionResult {
    const opts: Required<CompressionOptions> = {...WeekCompressionUtil.DEFAULTS, ...(input.options ?? {})};
    const slot = opts.slotDurationMinutes > 0 ? opts.slotDurationMinutes : 30;

    // 1. Szűrjük a heti nézet idősávjába tartozó eseményeket.
    const weekEvents = (input.events ?? []).filter(e =>
      e.start && e.end
      && e.end.getTime() > input.weekStart.getTime()
      && e.start.getTime() < input.weekEnd.getTime(),
    );

    const totalEvents = weekEvents.length;

    // 2. Korai kilépés: nincs vagy túl kevés esemény.
    if (totalEvents === 0) {
      return WeekCompressionUtil.noCompressResult(totalEvents, 'no-events');
    }
    if (totalEvents < opts.minEventsThreshold) {
      return WeekCompressionUtil.noCompressResult(totalEvents, 'too-few-events');
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
      return WeekCompressionUtil.noCompressResult(totalEvents, 'no-gap-detected');
    }

    const gapMinutes = eveningEarliestMin - morningLatestMin;
    if (gapMinutes < opts.minGapHours * 60) {
      return WeekCompressionUtil.noCompressResult(totalEvents, 'gap-too-small');
    }

    // 5. Head/tail trim: a slotMin/Max a globális min/max + opcionális padding,
    //    SLOT-HATÁRRA igazítva (lefelé a min, felfelé a max), hogy a slot-kezdetek
    //    egybeessenek a FullCalendar slat-jaival (különben a collapse elcsúszna).
    const padMin = Math.max(0, opts.paddingHours) * 60;
    const slotMinMin = Math.max(0, Math.floor((globalMinMin - padMin) / slot) * slot);
    const slotMaxMin = Math.min(24 * 60, Math.ceil((globalMaxMin + padMin) / slot) * slot);

    // 6. Occupancy: minden slot-kezdet, amit LEGALÁBB egy esemény lefed.
    //    Egy [from,to) intervallum MINDEN átfedett slotját megjelöljük (nem csak
    //    a kezdőt), különben egy 45/90 perces mise „átlógna" egy összehúzott slotba.
    const occupied = new Set<number>();
    const markRange = (fromMin: number, toMin: number) => {
      const first = Math.floor(fromMin / slot) * slot;
      const lastExclusive = Math.ceil(toMin / slot) * slot;
      for (let s = first; s < lastExclusive; s += slot) {
        occupied.add(s);
      }
    };
    for (const e of weekEvents) {
      const startMin = e.start.getHours() * 60 + e.start.getMinutes();
      const endMin = e.end.getHours() * 60 + e.end.getMinutes();
      if (endMin > startMin) {
        markRange(startMin, endMin);
      } else if (endMin === startMin) {
        // nulla hosszú esemény: a kezdetet tartalmazó egyetlen slot
        markRange(startMin, startMin + 1);
      } else {
        // éjfél-átlógás: [startMin, 1440) + [0, endMin)
        markRange(startMin, 24 * 60);
        markRange(0, endMin);
      }
    }

    // 7. Collapsed slotok: CSAK a detektált középső gap-en belül
    //    (`[morningLatestMin, eveningEarliestMin)`), NEM a teljes [slotMin, slotMax)
    //    ablakban. Ez a szándék (12-15. sori komment): a reggel↔este közti holt
    //    sávot húzzuk össze, a reggeli (vagy esti) misék közti apró réseket NEM
    //    — különben a misék jobban összenyomódnak, mint kéne (#358 review).
    //    A gap-en belüli FOGLALT slotok (pl. középső Nagyszombat) továbbra is
    //    kimaradnak, ezért köréjük „törik" a tengely.
    const gapFirstSlot = Math.ceil(morningLatestMin / slot) * slot;
    const gapLastSlot = Math.floor(eveningEarliestMin / slot) * slot;
    const collapsedSlotMinutes: number[] = [];
    for (let s = gapFirstSlot; s < gapLastSlot; s += slot) {
      if (!occupied.has(s)) {
        collapsedSlotMinutes.push(s);
      }
    }

    return {
      shouldCompress: true,
      slotMinTime: WeekCompressionUtil.minutesToTimeString(slotMinMin),
      slotMaxTime: WeekCompressionUtil.minutesToTimeString(slotMaxMin),
      collapsedSlotMinutes,
      diagnostics: {
        totalEvents,
        reason: 'compressed',
        gapStart: WeekCompressionUtil.minutesToTimeString(morningLatestMin).slice(0, 5),
        gapEnd: WeekCompressionUtil.minutesToTimeString(eveningEarliestMin).slice(0, 5),
        gapSizeHours: Math.round(gapMinutes / 60 * 10) / 10,
        collapsedSlotCount: collapsedSlotMinutes.length,
      },
    };
  }

  private static noCompressResult(
    totalEvents: number,
    reason: CompressionDiagnostics['reason'],
  ): CompressionResult {
    return {
      shouldCompress: false,
      slotMinTime: '00:00:00',
      slotMaxTime: '24:00:00',
      collapsedSlotMinutes: [],
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
