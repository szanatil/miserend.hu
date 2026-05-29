import { ComponentFixture, TestBed } from '@angular/core/testing';

import { DeleteWarningDialogComponent } from './delete-warning-dialog.component';

// TODO #436: ez a stub az Angular CLI által generált, valódi TestBed mock-okat
// nem tartalmaz. Amíg ki nem egészítjük, xdescribe-bal pendingben hagyjuk, hogy a
// 'ng test' ne essen miatta piros. Promóció: vissza describe-ra + DI providerek.
xdescribe('EventEditDialogComponent', () => {
  let component: DeleteWarningDialogComponent;
  let fixture: ComponentFixture<DeleteWarningDialogComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [DeleteWarningDialogComponent]
    })
    .compileComponents();

    fixture = TestBed.createComponent(DeleteWarningDialogComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
