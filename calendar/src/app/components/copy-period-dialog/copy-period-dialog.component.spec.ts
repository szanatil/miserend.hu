import { ComponentFixture, TestBed } from '@angular/core/testing';

import { CopyPeriodDialogComponent } from './copy-period-dialog.component';

describe('CopyPeriodDialogComponent', () => {
  let component: CopyPeriodDialogComponent;
  let fixture: ComponentFixture<CopyPeriodDialogComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [ CopyPeriodDialogComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(CopyPeriodDialogComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
