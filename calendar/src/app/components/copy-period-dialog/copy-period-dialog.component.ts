import {Component, Inject, OnInit} from '@angular/core';
import {CommonModule} from '@angular/common';
import {MatDialogModule, MAT_DIALOG_DATA, MatDialogRef} from '@angular/material/dialog';
import {MatFormFieldModule} from '@angular/material/form-field';
import {MatSelectModule} from '@angular/material/select';
import {MatAutocompleteModule} from '@angular/material/autocomplete';
import {MatInputModule} from '@angular/material/input';
import {MatButtonModule} from '@angular/material/button';
import {MatIconModule} from '@angular/material/icon';
import {FormBuilder, FormGroup, FormControl, ReactiveFormsModule, Validators} from '@angular/forms';
import {TranslatePipe, TranslateService} from '@ngx-translate/core';
import {Period} from '../../model/period';
import {PeriodService} from '../../services/period.service';
import {Observable, of} from 'rxjs';
import {map, startWith} from 'rxjs/operators';

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
    MatAutocompleteModule,
    MatInputModule,
    MatButtonModule,
    MatIconModule,
    ReactiveFormsModule,
    TranslatePipe
  ],
  templateUrl: './copy-period-dialog.component.html',
  styleUrl: './copy-period-dialog.component.css'
})
export class CopyPeriodDialogComponent implements OnInit {

  form!: FormGroup;
  targetPeriodCtr = new FormControl<PeriodWithColor | null>(null, Validators.required);
  selectedPeriodInfo?: PeriodWithColor;
  availablePeriodsWithColors: PeriodWithColor[] = [];
  sourcePeriodColor?: string;
  filteredPeriods$: Observable<PeriodWithColor[]> = of([]);

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
    this.setupAutocomplete();
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

    this.targetPeriodCtr.valueChanges.subscribe((value: PeriodWithColor | null) => {
      if (value) {
        this.selectedPeriodInfo = value;
      }
    });
  }

  private setupAutocomplete(): void {
    this.filteredPeriods$ = this.targetPeriodCtr.valueChanges.pipe(
      startWith(''),
      map(value => {
        const filterValue = typeof value === 'string' ? value.toLowerCase() : value?.name.toLowerCase() ?? '';
        return this.availablePeriodsWithColors.filter(period => period.name.toLowerCase().includes(filterValue));
      })
    );
  }

  displayPeriod(period: PeriodWithColor | null): string {
    return period ? period.name : '';
  }

  resetPeriod(event: any): void {
    event.preventDefault();
    event.stopPropagation();
    this.targetPeriodCtr.setValue(null);
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
    if (this.targetPeriodCtr.valid && this.selectedPeriodInfo) {
      this.dialogRef.close({
        targetPeriodId: this.selectedPeriodInfo.id,
        selectedPeriodInfo: this.selectedPeriodInfo
      });
    }
  }

}
