/**
 * Sensor event interface based on API response structure
 * Represents events detected by confessional sensors
 */
export interface SensorEvent {
  /**
   * Unique identifier (e.g., "confession_1", "confession_2")
   */
  id: string;

  /**
   * Event title (e.g., "gyóntatás")
   */
  title: string;

  /**
   * Event start date/time (e.g., "2026-05-04T14:21:57")
   */
  startDate: string;

  /**
   * Event duration in seconds
   */
  duration: number;

  /**
   * Church ID
   */
  churchId: number;

  /**
   * Period ID (typically null for sensor events)
   */
  periodId?: number | null;

  /**
   * Event types (typically empty for sensor events)
   */
  types?: string[] | null;

  /**
   * Rite (typically null for sensor events)
   */
  rite?: string | null;
}
