import {ComponentFixture, TestBed} from '@angular/core/testing';
import {of} from 'rxjs';
import {MAT_DIALOG_DATA, MatDialogRef} from '@angular/material/dialog';
import {TranslateModule} from '@ngx-translate/core';

import {AddFullEventDialogComponent} from './add-full-event-dialog.component';
import {PeriodService} from '../../services/period.service';
import {GeneratedPeriod} from '../../model/generated-period';
import {Rite} from '../../enum/rites';
import {LanguageCode} from '../../enum/language-code';
import {Renum} from '../../enum/recurrence';
import {Day} from '../../enum/day';

function makeGeneratedPeriod(overrides: Partial<GeneratedPeriod> = {}): GeneratedPeriod {
  return {
    id: 1,
    periodId: 10,
    name: 'Évközi idő',
    weight: 1,
    startDate: '2026-01-01',
    endDate: '2026-12-31',
    color: '#ccc',
    ...overrides,
  };
}

// #450: a komponens a `renum === Renum.NONE` alapján dönti el, hogy egyszeri
// alkalomról van-e szó (`singleEvent`). Egyszeri alkalomhoz NEM rendelünk
// alapértelmezett időszakot (ez volt a #450 bug). Ezért a #308 default-period
// teszteknek ismétlődő misét (EVERY_WEEK) kell használniuk — különben a
// singleEvent-ág kihagyja a period-választást és null marad.
function makeDialogData(
  periodOverride: GeneratedPeriod | null = null,
  existingPeriodIds: number[] = [],
  renum: Renum = Renum.EVERY_WEEK,
) {
  return {
    title: 'ADD_NEW_MASS',
    existingPeriodIds,
    event: {
      period: periodOverride,
      rite: Rite.ROMAN_CATHOLIC,
      types: [],
      title: 'Szentmise',
      start: new Date('2026-03-15T10:00:00'),
      duration: {hours: 1},
      language: LanguageCode.HU,
      renum,
      selectedDays: [Day.SU],
      comment: '',
      editOne: false,
    },
  };
}

describe('AddFullEventDialogComponent (#308 default period)', () => {
  let component: AddFullEventDialogComponent;
  let fixture: ComponentFixture<AddFullEventDialogComponent>;
  let periodServiceMock: { getSelectableGeneratedPeriodsByDate: jasmine.Spy; getPeriodById: jasmine.Spy; getSpecialPeriodType: jasmine.Spy };

  async function setup(periods: GeneratedPeriod[], data = makeDialogData()) {
    periodServiceMock = {
      getSelectableGeneratedPeriodsByDate: jasmine.createSpy().and.returnValue(of(periods)),
      getPeriodById: jasmine.createSpy().and.returnValue(null),
      getSpecialPeriodType: jasmine.createSpy().and.returnValue(null),
    };

    await TestBed.configureTestingModule({
      imports: [AddFullEventDialogComponent, TranslateModule.forRoot()],
      providers: [
        {provide: MAT_DIALOG_DATA, useValue: data},
        {provide: MatDialogRef, useValue: {close: jasmine.createSpy()}},
        {provide: PeriodService, useValue: periodServiceMock},
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(AddFullEventDialogComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  }

  it('falls back to [0] when the church has no existing masses (new church / no overlap)', async () => {
    const relevant = makeGeneratedPeriod({id: 1, periodId: 10, name: 'Iskolaidő'});
    const other = makeGeneratedPeriod({id: 2, periodId: 11, name: 'Nyári szünet'});

    await setup([relevant, other]);

    expect(periodServiceMock.getSelectableGeneratedPeriodsByDate).toHaveBeenCalled();
    expect(component.periodCtr.value).toEqual(relevant);
  });

  it('does not overwrite an existing period selection (edit flow)', async () => {
    const preselected = makeGeneratedPeriod({id: 5, periodId: 50, name: 'Húsvéti idő'});
    const fromServer = makeGeneratedPeriod({id: 1, periodId: 10, name: 'Iskolaidő'});

    await setup([fromServer], makeDialogData(preselected, [10]));

    expect(component.periodCtr.value).toEqual(preselected);
  });

  it('leaves periodCtr null when no selectable periods exist', async () => {
    await setup([]);

    expect(component.periodCtr.value).toBeNull();
  });

  // #308 (borazslo review):
  it('prefers a sortable period the church already has a mass for, over the first sorted period', async () => {
    // Order from PeriodService reflects "May day weight": "Húsvéti idő" first (highest weight
    // for a date in the easter range), then "Iskolaidő" (school time, lower weight but valid).
    const easter      = makeGeneratedPeriod({id: 1, periodId: 50, name: 'Húsvéti idő',      weight: 10});
    const schoolTime  = makeGeneratedPeriod({id: 2, periodId: 10, name: 'Iskolaidő',        weight: 1});
    const summerBreak = makeGeneratedPeriod({id: 3, periodId: 11, name: 'Nyári szünet',     weight: 1});

    // The church already has a Iskolaidő (10) mass — Wednesday addition should default to
    // Iskolaidő, NOT Húsvéti idő, even though Húsvéti idő is the first in the sorted list.
    await setup([easter, schoolTime, summerBreak], makeDialogData(null, [10]));

    expect(component.periodCtr.value).toEqual(schoolTime);
  });

  it('walks the sorted list in order: picks the first existing period, not the highest existingPeriodId', async () => {
    // existingPeriodIds includes both 50 and 11, but the sorted order is [10, 11, 50].
    // We must pick 11, the FIRST existing in sort order, NOT 50.
    const p10 = makeGeneratedPeriod({id: 1, periodId: 10, name: 'A',  weight: 3});
    const p11 = makeGeneratedPeriod({id: 2, periodId: 11, name: 'B',  weight: 2});
    const p50 = makeGeneratedPeriod({id: 3, periodId: 50, name: 'C',  weight: 1});

    await setup([p10, p11, p50], makeDialogData(null, [50, 11]));

    expect(component.periodCtr.value).toEqual(p11);
  });

  it('falls back to [0] when none of the existing periods match the sorted list', async () => {
    // The church has miséket period 999, but 999 isn't in this day's selectable list.
    // Should fall back to the original behaviour: [0].
    const fromServer = makeGeneratedPeriod({id: 1, periodId: 10, name: 'Iskolaidő'});

    await setup([fromServer], makeDialogData(null, [999]));

    expect(component.periodCtr.value).toEqual(fromServer);
  });

  it('synchronously syncs data.event.period when prefilling (avoids save-race)', async () => {
    const evkozi = makeGeneratedPeriod({id: 1, periodId: 10, name: 'Iskolaidő'});

    await setup([evkozi]);

    expect(component.periodCtr.value).toEqual(evkozi);
    expect(component.data.event.period).toEqual(evkozi as any);
  });

  it('onSave defensively syncs data.event.period from periodCtr if they drifted apart', async () => {
    const evkozi = makeGeneratedPeriod({id: 1, periodId: 10});
    await setup([evkozi]);

    (component.data.event as any).period = null;
    component.singleEvent = false;

    component.onSave();

    expect(component.data.event.period).toEqual(evkozi as any);
    expect(component.dialogRef.close).toHaveBeenCalled();
  });

  it('handles missing existingPeriodIds (undefined) like an empty list — fallback to [0]', async () => {
    const p10 = makeGeneratedPeriod({id: 1, periodId: 10, name: 'Iskolaidő'});

    // DialogData without existingPeriodIds key at all (legacy callers / safety net).
    // #450: ismétlődő mise (EVERY_WEEK), hogy a default-period ág lefusson.
    const data = {
      title: 'ADD_NEW_MASS',
      event: {
        period: null,
        rite: Rite.ROMAN_CATHOLIC,
        types: [],
        title: 'Szentmise',
        start: new Date('2026-03-15T10:00:00'),
        duration: {hours: 1},
        language: LanguageCode.HU,
        renum: Renum.EVERY_WEEK,
        selectedDays: [Day.SU],
        comment: '',
        editOne: false,
      },
    };

    await setup([p10], data as any);

    expect(component.periodCtr.value).toEqual(p10);
  });
});

describe('AddFullEventDialogComponent (#450 egyszeri alkalom nem kap időszakot)', () => {
  let component: AddFullEventDialogComponent;
  let fixture: ComponentFixture<AddFullEventDialogComponent>;
  let periodServiceMock: { getSelectableGeneratedPeriodsByDate: jasmine.Spy; getPeriodById: jasmine.Spy; getSpecialPeriodType: jasmine.Spy };

  async function setup(periods: GeneratedPeriod[], data: any) {
    periodServiceMock = {
      getSelectableGeneratedPeriodsByDate: jasmine.createSpy().and.returnValue(of(periods)),
      getPeriodById: jasmine.createSpy().and.returnValue(null),
      getSpecialPeriodType: jasmine.createSpy().and.returnValue(null),
    };

    await TestBed.configureTestingModule({
      imports: [AddFullEventDialogComponent, TranslateModule.forRoot()],
      providers: [
        {provide: MAT_DIALOG_DATA, useValue: data},
        {provide: MatDialogRef, useValue: {close: jasmine.createSpy()}},
        {provide: PeriodService, useValue: periodServiceMock},
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(AddFullEventDialogComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  }

  // #450: egyszeri alkalomnál (renum === NONE) NEM szabad alapértelmezett
  // időszakot rendelni — ez okozta, hogy az időszak első napjára került az esemény.
  it('does NOT assign a default period for a single event (renum NONE), even if periods exist', async () => {
    const evkozi = makeGeneratedPeriod({id: 1, periodId: 10, name: 'Iskolaidő'});

    await setup([evkozi], makeDialogData(null, [10], Renum.NONE));

    expect(component.singleEvent).toBeTrue();
    expect(component.periodCtr.value).toBeNull();
    expect(component.data.event.period).toBeFalsy();
  });

  // #450: ha a felhasználó ismétlődőről egyszerire vált (onRecurrenceModChange),
  // a korábban beállított időszakot ki kell üríteni.
  it('clears the period when switching from recurring to single (onRecurrenceModChange)', async () => {
    const evkozi = makeGeneratedPeriod({id: 1, periodId: 10, name: 'Iskolaidő'});

    // Ismétlődőként indul → kap default period-ot.
    await setup([evkozi], makeDialogData(null, [10], Renum.EVERY_WEEK));
    expect(component.periodCtr.value).toEqual(evkozi);

    // A felhasználó egyszerire vált.
    component.singleEvent = true;
    component.onRecurrenceModChange();

    expect(component.data.event.period).toBeNull();
    expect(component.periodCtr.value).toBeNull();
  });
});
