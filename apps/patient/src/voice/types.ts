export type Lang = 'en' | 'hi' | 'ur';

export type Day = 'Sun' | 'Mon' | 'Tue' | 'Wed' | 'Thu' | 'Fri' | 'Sat';

export const ALL_DAYS: Day[] = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

export type Intent =
  | {
      type: 'create_reminder';
      /** 24-hour "HH:mm"; missing when the person did not say a time. */
      time?: string;
      days: Day[];
      medicine?: string;
    }
  | { type: 'refill_last' }
  | { type: 'new_order' }
  | { type: 'order_status' }
  | { type: 'list_reminders' }
  | { type: 'help' }
  | { type: 'unknown'; text: string };
