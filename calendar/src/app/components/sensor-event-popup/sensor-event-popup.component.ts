import { Component, Inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { MatDialogModule } from '@angular/material/dialog';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatCardModule } from '@angular/material/card';
import { MatDividerModule } from '@angular/material/divider';
import { SensorEvent } from '../../model/sensor-event';

export interface SensorEventDialogData {
  sensorEvent: SensorEvent;
  churchName: string;
}

@Component({
  selector: 'app-sensor-event-popup',
  standalone: true,
  imports: [CommonModule, MatDialogModule, MatButtonModule, MatIconModule, MatCardModule, MatDividerModule],
  templateUrl: './sensor-event-popup.component.html',
  styleUrls: ['./sensor-event-popup.component.css']
})
export class SensorEventPopupComponent {

  constructor(
    public dialogRef: MatDialogRef<SensorEventPopupComponent>,
    @Inject(MAT_DIALOG_DATA) public data: SensorEventDialogData
  ) {}

  onClose(): void {
    this.dialogRef.close();
  }

  /**
   * Get formatted time range from startDate and duration
   */
  getTimeRange(): { start: string; end: string; duration: string } {
    const startDate = new Date(this.data.sensorEvent.startDate);
    const durationSeconds = this.data.sensorEvent.duration || 0;
    const endDate = new Date(startDate.getTime() + durationSeconds * 1000);

    const formatTime = (date: Date) => {
      const hours = String(date.getHours()).padStart(2, '0');
      const minutes = String(date.getMinutes()).padStart(2, '0');
      const seconds = String(date.getSeconds()).padStart(2, '0');
      return `${hours}:${minutes}:${seconds}`;
    };

    const formatDate = (date: Date) => {
      const year = date.getFullYear();
      const month = String(date.getMonth() + 1).padStart(2, '0');
      const day = String(date.getDate()).padStart(2, '0');
      return `${year}.${month}.${day}`;
    };

    // Format duration as hours:minutes:seconds
    const hours = Math.floor(durationSeconds / 3600);
    const minutes = Math.floor((durationSeconds % 3600) / 60);
    const seconds = durationSeconds % 60;
    const durationStr = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;

    return {
      start: `${formatDate(startDate)} ${formatTime(startDate)}`,
      end: `${formatDate(endDate)} ${formatTime(endDate)}`,
      duration: durationStr
    };
  }

  /**
   * Generate info URLs based on sensor event type
   */
  getInfoUrls(): { label: string; url: string }[] {
    // Extract sensor type from ID (e.g., "confession_1" -> "confession")
    const sensorType = this.data.sensorEvent.id?.split('_')[0] || 'sensor';

    const urls: Record<string, { label: string; url: string }[]> = {
      confession: [
        { label: 'Gyóntatás információ', url: 'https://miserend.hu/confession' },
        { label: 'Egyházi törvények', url: 'https://miserend.hu/canon-law' }
      ]
    };

    return urls[sensorType] || [
      { label: 'Szenzor információ', url: 'https://miserend.hu/sensor-info' }
    ];
  }

  openLink(url: string): void {
    window.open(url, '_blank');
  }
}
