import {Mass, Rite} from './mass';
import {SensorEvent} from './sensor-event';

export interface Church {
  id: number;
  name: string;
  rite: Rite;
  timeZone: string;
  masses: Mass[];
  eventsFromSensor?: SensorEvent[];
}
