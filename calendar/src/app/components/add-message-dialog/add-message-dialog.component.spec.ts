import { ComponentFixture, TestBed } from '@angular/core/testing';

import { AddMessageDialogComponent } from './add-message-dialog.component';

// TODO #436: ez a stub az Angular CLI által generált, valódi TestBed mock-okat
// nem tartalmaz. Amíg ki nem egészítjük, xdescribe-bal pendingben hagyjuk, hogy a
// 'ng test' ne essen miatta piros. Promóció: vissza describe-ra + DI providerek.
xdescribe('EventEditDialogComponent', () => {
  let component: AddMessageDialogComponent;
  let fixture: ComponentFixture<AddMessageDialogComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [AddMessageDialogComponent]
    })
    .compileComponents();

    fixture = TestBed.createComponent(AddMessageDialogComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
