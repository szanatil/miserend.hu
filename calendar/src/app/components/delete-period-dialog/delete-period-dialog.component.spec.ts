import { ComponentFixture, TestBed } from '@angular/core/testing';

import { DeletePeriodDialogComponent } from './delete-period-dialog.component';

describe('DeletePeriodDialogComponent', () => {
  let component: DeletePeriodDialogComponent;
  let fixture: ComponentFixture<DeletePeriodDialogComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [ DeletePeriodDialogComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(DeletePeriodDialogComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
