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

function makeDialogData(periodOverride: GeneratedPeriod | null = null) {
  return {
    title: 'ADD_NEW_MASS',
    event: {
      period: periodOverride,
      rite: Rite.ROMAN_CATHOLIC,
      types: [],
      title: 'Szentmise',
      start: new Date('2026-03-15T10:00:00'),
      duration: {hours: 1},
      language: LanguageCode.HU,
      renum: Renum.NONE,
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

  it('pre-fills periodCtr with the first (most relevant) period when none is set', async () => {
    const relevant = makeGeneratedPeriod({id: 1, periodId: 10, name: 'Iskolaidő'});
    const other = makeGeneratedPeriod({id: 2, periodId: 11, name: 'Nyári szünet'});

    await setup([relevant, other]);

    expect(periodServiceMock.getSelectableGeneratedPeriodsByDate).toHaveBeenCalled();
    expect(component.periodCtr.value).toEqual(relevant);
  });

  it('does not overwrite an existing period selection (edit flow)', async () => {
    const preselected = makeGeneratedPeriod({id: 5, periodId: 50, name: 'Húsvéti idő'});
    const fromServer = makeGeneratedPeriod({id: 1, periodId: 10, name: 'Iskolaidő'});

    await setup([fromServer], makeDialogData(preselected));

    expect(component.periodCtr.value).toEqual(preselected);
  });

  it('leaves periodCtr null when no selectable periods exist', async () => {
    await setup([]);

    expect(component.periodCtr.value).toBeNull();
  });
});
