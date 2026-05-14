import {Component, Inject, OnInit} from '@angular/core';
import {CommonModule} from '@angular/common';
import {MatDialogModule, MAT_DIALOG_DATA, MatDialogRef} from '@angular/material/dialog';
import {MatButtonModule} from '@angular/material/button';
import {MatCardModule} from '@angular/material/card';
import {MatIconModule} from '@angular/material/icon';
import {TranslatePipe, TranslateService} from '@ngx-translate/core';
import {Period} from '../../model/period';
import {PeriodService} from '../../services/period.service';
import {GeneratedPeriod} from '../../model/generated-period';

export interface DeletePeriodDialogData {
  period: Period;
  generatedPeriods: GeneratedPeriod[];
  massCount: number;
}

@Component({
  selector: 'app-delete-period-dialog',
  imports: [
    CommonModule,
    MatDialogModule,
    MatButtonModule,
    MatCardModule,
    MatIconModule,
    TranslatePipe
  ],
  templateUrl: './delete-period-dialog.component.html',
  styleUrl: './delete-period-dialog.component.css'
})
export class DeletePeriodDialogComponent implements OnInit {

  periodColor?: string;
  periodRangeInfo: string = '';

  constructor(
    private translateService: TranslateService,
    private periodService: PeriodService,
    public dialogRef: MatDialogRef<DeletePeriodDialogComponent>,
    @Inject(MAT_DIALOG_DATA) public data: DeletePeriodDialogData
  ) {
  }

  ngOnInit(): void {
    this.enrichPeriodInfo();
  }

  private enrichPeriodInfo(): void {
    // Get color for period
    if (this.data.generatedPeriods && this.data.generatedPeriods.length > 0) {
      this.periodColor = this.data.generatedPeriods[0].color;
    }

    // Get period range info
    this.periodRangeInfo = this.getPeriodRangeInfo(this.data.period);
  }

  private getPeriodRangeInfo(period: Period): string {
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
    this.dialogRef.close(false);
  }

  onConfirmDelete(): void {
    this.dialogRef.close(true);
  }

}
