import { MassTitleCategory } from '../../enum/mass-title-category';

export interface ExtendedProps {
  massId?: number;
  sensorEventId?: string;
  isSensorEvent?: boolean;
  recentExDates?: string[];
  recentModifiedDates?: string[];
  massTitleCategory?: MassTitleCategory;

  //Ha a naptárba akarunk majd megjeleníteni infókat, azt ide érdemes felvenni.
}
