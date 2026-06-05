import {TestBed} from '@angular/core/testing';
import {HttpClientTestingModule} from '@angular/common/http/testing';

import {PeriodService} from './period.service';
import {MatSnackBarService} from './mat-snack-bar.service';
import {GeneratedPeriod} from '../model/generated-period';

function gp(overrides: Partial<GeneratedPeriod> = {}): GeneratedPeriod {
  return {
    id: 1,
    periodId: 10,
    name: 'Egész évben',
    weight: 1,
    startDate: '2026-01-01',
    endDate: '2027-01-01',
    color: '#ccc',
    ...overrides,
  };
}

describe('PeriodService.getCurrentGeneratedPeriodByPeriodId (#458)', () => {
  let service: PeriodService;

  beforeEach(() => {
    TestBed.configureTestingModule({
      imports: [HttpClientTestingModule],
      providers: [
        PeriodService,
        {provide: MatSnackBarService, useValue: {error: jasmine.createSpy(), success: jasmine.createSpy()}},
      ],
    });
    service = TestBed.inject(PeriodService);
  });

  it('returns the date-matching generated period when one exists', () => {
    const p2026 = gp({id: 1, periodId: 10, startDate: '2026-01-01', endDate: '2027-01-01'});
    service.generatedPeriods$.next([p2026]);

    const result = service.getCurrentGeneratedPeriodByPeriodId(10, new Date('2026-06-15'));

    expect(result).toBe(p2026);
  });

  // #458: ha az adott dátumra NINCS találat (a periodId megfelelő évi példánya
  // nincs betöltve), létező mise szerkesztésekor a periodId-hoz tartozó periódust
  // kell visszaadni — NEM null-t (különben a dialógus rossz időszakot defaultol
  // vagy a mentés blokkolódik).
  it('falls back to a same-periodId instance when no instance matches the date', () => {
    const p2026 = gp({id: 1, periodId: 10, startDate: '2026-01-01', endDate: '2027-01-01'});
    service.generatedPeriods$.next([p2026]);

    // 2030 — semmilyen betöltött példány nem fedi, de a periodId létezik.
    const result = service.getCurrentGeneratedPeriodByPeriodId(10, new Date('2030-03-01'));

    expect(result).not.toBeNull();
    expect(result!.periodId).toBe(10);
  });

  it('picks the closest-start instance among multiple same-periodId fallbacks', () => {
    const p2025 = gp({id: 1, periodId: 10, startDate: '2025-01-01', endDate: '2026-01-01'});
    const p2027 = gp({id: 3, periodId: 10, startDate: '2027-01-01', endDate: '2028-01-01'});
    service.generatedPeriods$.next([p2025, p2027]);

    // 2026-12 — egyik tartomány sem fedi (2025 vége 2026-01-01, 2027 kezdete 2027-01-01),
    // a legközelebbi kezdetű a 2027-es.
    const result = service.getCurrentGeneratedPeriodByPeriodId(10, new Date('2026-12-15'));

    expect(result).toBe(p2027);
  });

  it('returns null when the periodId is not present at all', () => {
    service.generatedPeriods$.next([gp({periodId: 10})]);

    const result = service.getCurrentGeneratedPeriodByPeriodId(99, new Date('2026-06-15'));

    expect(result).toBeNull();
  });

  it('returns null for null inputs', () => {
    expect(service.getCurrentGeneratedPeriodByPeriodId(null, new Date())).toBeNull();
    expect(service.getCurrentGeneratedPeriodByPeriodId(10, null as any)).toBeNull();
  });
});
