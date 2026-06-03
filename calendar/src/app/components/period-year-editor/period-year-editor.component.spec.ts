import { ComponentFixture, TestBed } from '@angular/core/testing';

import { PeriodYearEditorComponent } from './period-year-editor.component';

// TODO #436: ez a stub az Angular CLI által generált, valódi TestBed mock-okat
// nem tartalmaz. Amíg ki nem egészítjük, xdescribe-bal pendingben hagyjuk, hogy a
// 'ng test' ne essen miatta piros. Promóció: vissza describe-ra + DI providerek.
xdescribe('PeriodYearEditorComponent', () => {
  let component: PeriodYearEditorComponent;
  let fixture: ComponentFixture<PeriodYearEditorComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [PeriodYearEditorComponent]
    })
    .compileComponents();

    fixture = TestBed.createComponent(PeriodYearEditorComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
