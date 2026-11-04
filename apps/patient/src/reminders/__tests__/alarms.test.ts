jest.mock('expo-notifications', () => ({
  AndroidAudioContentType: {},
  AndroidAudioUsage: {},
  AndroidImportance: {},
  AndroidNotificationPriority: {},
  AndroidNotificationVisibility: {},
  SchedulableTriggerInputTypes: {},
}));

import { ALARM_SOUND, isDoseAlarm, REMINDER_CHANNEL } from '../alarms';

describe('medicine alarms', () => {
  it('rings on the alarm channel with the bundled alarm tone', () => {
    expect(REMINDER_CHANNEL).not.toBe('medicine-reminders');
    expect(ALARM_SOUND).toBe('medicine_alarm.wav');
  });

  it('opens the alarm page only for medicine alarms and snoozes', () => {
    expect(isDoseAlarm({ kind: 'mcare-medicine-reminder' })).toBe(true);
    expect(isDoseAlarm({ kind: 'mcare-medicine-snooze' })).toBe(true);
    expect(isDoseAlarm({ type: 'MISSED_DOSE' })).toBe(false);
    expect(isDoseAlarm(undefined)).toBe(false);
  });
});
