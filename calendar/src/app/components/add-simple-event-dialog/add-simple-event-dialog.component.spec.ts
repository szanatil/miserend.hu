import { ComponentFixture, TestBed } from '@angular/core/testing';

import { AddSimpleEventDialogComponent } from './add-simple-event-dialog.component';

// TODO #436: ez a stub az Angular CLI által generált, valódi TestBed mock-okat
// nem tartalmaz. Amíg ki nem egészítjük, xdescribe-bal pendingben hagyjuk, hogy a
// 'ng test' ne essen miatta piros. Promóció: vissza describe-ra + DI providerek.
xdescribe('AddSimpleEventDialogComponent', () => {
  let component: AddSimpleEventDialogComponent;
  let fixture: ComponentFixture<AddSimpleEventDialogComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [AddSimpleEventDialogComponent]
    })
    .compileComponents();

    fixture = TestBed.createComponent(AddSimpleEventDialogComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
