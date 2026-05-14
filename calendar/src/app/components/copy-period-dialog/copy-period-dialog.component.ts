import {Component, Inject, OnInit} from '@angular/core';
import {CommonModule} from '@angular/common';
import {MatDialogModule, MAT_DIALOG_DATA, MatDialogRef} from '@angular/material/dialog';
import {MatFormFieldModule} from '@angular/material/form-field';
import {MatSelectModule} from '@angular/material/select';
import {MatButtonModule} from '@angular/material/button';
import {FormBuilder, FormGroup, ReactiveFormsModule, Validators} from '@angular/forms';
import {TranslatePipe, TranslateService} from '@ngx-translate/core';
import {Period} from '../../model/period';
import {PeriodService} from '../../services/period.service';

export interface CopyPeriodDialogData {
  sourcePeriodId: number;
  sourcePeriodName: string;
  sourcePeriodInfo?: Period;
  availablePeriods: Period[];
  massCount: number;
}

interface PeriodWithColor extends Period {
  color?: string;
}

@Component({
  selector: 'app-copy-period-dialog',
  imports: [
    CommonModule,
    MatDialogModule,
    MatFormFieldModule,
    MatSelectModule,
    MatButtonModule,
    ReactiveFormsModule,
    TranslatePipe
  ],
  templateUrl: './copy-period-dialog.component.html',
  styleUrl: './copy-period-dialog.component.css'
})
export class CopyPeriodDialogComponent implements OnInit {

  form!: FormGroup;
  selectedPeriodInfo?: PeriodWithColor;
  availablePeriodsWithColors: PeriodWithColor[] = [];
  sourcePeriodColor?: string;

  constructor(
    private fb: FormBuilder,
    private translateService: TranslateService,
    private periodService: PeriodService,
    public dialogRef: MatDialogRef<CopyPeriodDialogComponent>,
    @Inject(MAT_DIALOG_DATA) public data: CopyPeriodDialogData
  ) {
  }

  ngOnInit(): void {
    this.initializeForm();
    this.enrichPeriodsWithColors();
  }

  private enrichPeriodsWithColors(): void {
    // Get color for source period
    this.sourcePeriodColor = this.getColorForPeriod(this.data.sourcePeriodId);

    // Get colors for all available periods
    this.availablePeriodsWithColors = this.data.availablePeriods.map(period => ({
      ...period,
      color: this.getColorForPeriod(period.id)
    }));
  }

  private getColorForPeriod(periodId: number): string | undefined {
    const generatedPeriods = this.periodService.getGeneratedPeriodsByPeriodId(periodId);
    if (generatedPeriods && generatedPeriods.length > 0) {
      return generatedPeriods[0].color;
    }
    return undefined;
  }

  private initializeForm(): void {
    this.form = this.fb.group({
      targetPeriodId: [null, Validators.required]
    });

    this.form.get('targetPeriodId')?.valueChanges.subscribe((periodId: number) => {
      this.selectedPeriodInfo = this.availablePeriodsWithColors.find(p => p.id === periodId);
    });
  }

  getPeriodRangeInfo(period: Period): string {
    // Handle periods bound to other periods (startPeriodId and/or endPeriodId)
    if (period.startPeriodId || period.endPeriodId) {
      const parts: string[] = [];
      
      if (period.startPeriodId) {
        const startPeriodName = this.periodService.getPeriodNameById(period.startPeriodId);
        parts.push(startPeriodName);
      }
      
      if (period.endPeriodId) {
        const endPeriodName = this.periodService.getPeriodNameById(period.endPeriodId);
        parts.push(endPeriodName);
      }
      
      return parts.join(' - ');
    } else if (period.startMonthDay && period.endMonthDay) {
      return period.startMonthDay + ' - ' + period.endMonthDay;
    }
    return '';
  }

  onCancel(): void {
    this.dialogRef.close(null);
  }

  onOk(): void {
    if (this.form.valid) {
      const targetPeriodId = this.form.get('targetPeriodId')?.value;
      this.dialogRef.close({
        targetPeriodId: targetPeriodId,
        selectedPeriodInfo: this.selectedPeriodInfo
      });
    }
  }

}
