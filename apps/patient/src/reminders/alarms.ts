/**
 * Medicine reminders as alarms scheduled on the phone, so they ring on time
 * even without internet. The backend stops pushing reminders for phones that
 * register with `local_reminders: true` (see registerForPush), so people do
 * not get each reminder twice.
 */
import * as Notifications from 'expo-notifications';
import {
  AndroidAudioContentType,
  AndroidAudioUsage,
  AndroidImportance,
  AndroidNotificationPriority,
  AndroidNotificationVisibility,
  SchedulableTriggerInputTypes,
} from 'expo-notifications';
import { Platform } from 'react-native';

import type { Reminder } from '@/api/mcare';
import { type Language, translate } from '@/i18n';
import { ALL_DAYS, type Day } from '@/voice/types';

/**
 * Android fixes a channel's sound once it exists, so the alarm sound needs a
 * new channel id. The old one ('medicine-reminders') only gave a short chime.
 */
export const REMINDER_CHANNEL = 'medicine-alarm';
const OLD_CHANNEL = 'medicine-reminders';
/** A 28-second alarm tone, bundled through the expo-notifications plugin. */
export const ALARM_SOUND = 'medicine_alarm.wav';
export const DOSE_CATEGORY = 'medicine-dose';
export const ACTION_TAKEN = 'taken';
export const ACTION_SNOOZE = 'snooze';
export const SNOOZE_MINUTES = 10;
const KIND = 'mcare-medicine-reminder';
const SNOOZE_KIND = 'mcare-medicine-snooze';

/** True for a medicine alarm or its snooze (not order updates or family alerts). */
export const isDoseAlarm = (data: Record<string, unknown> | undefined) =>
  data?.kind === KIND || data?.kind === SNOOZE_KIND;

/**
 * iPhones keep at most 64 scheduled notifications per app, so a reminder for
 * every day becomes one daily alarm instead of seven weekly ones. Some room is
 * left for snoozes.
 */
export const MAX_ALARMS = 60;

export async function setupReminderChannel() {
  if (Platform.OS !== 'android') return;
  await Notifications.setNotificationChannelAsync(REMINDER_CHANNEL, {
    name: 'Medicine alarms',
    importance: AndroidImportance.MAX,
    sound: ALARM_SOUND,
    // Plays on the alarm volume like a clock alarm, so it rings even when
    // the phone is on silent or vibrate for calls and messages.
    audioAttributes: { usage: AndroidAudioUsage.ALARM, contentType: AndroidAudioContentType.SONIFICATION },
    bypassDnd: true,
    lockscreenVisibility: AndroidNotificationVisibility.PUBLIC,
    vibrationPattern: [0, 800, 400, 800, 400, 800, 400, 800],
  });
  await Notifications.deleteNotificationChannelAsync(OLD_CHANNEL).catch(() => undefined);
}

/** The "I took it" and "Snooze 10 min" buttons on the alarm, in the person's language. */
async function setupDoseCategory(lang: Language) {
  await Notifications.setNotificationCategoryAsync(DOSE_CATEGORY, [
    { identifier: ACTION_TAKEN, buttonTitle: translate(lang, 'doseTaken'), options: { opensAppToForeground: true } },
    {
      identifier: ACTION_SNOOZE,
      buttonTitle: translate(lang, 'snooze', { min: SNOOZE_MINUTES }),
      options: { opensAppToForeground: true },
    },
  ]);
}

/** Rings the same dose again in 10 minutes, keeping the original dose time. */
export async function snoozeDose(
  content: Pick<Notifications.NotificationContent, 'title' | 'body' | 'data'>,
  scheduledFor: string,
) {
  if (Platform.OS === 'web') return;
  await Notifications.scheduleNotificationAsync({
    content: {
      title: content.title,
      body: content.body,
      sound: ALARM_SOUND,
      priority: AndroidNotificationPriority.MAX,
      categoryIdentifier: DOSE_CATEGORY,
      data: { ...content.data, kind: SNOOZE_KIND, scheduledFor },
    },
    trigger: {
      type: SchedulableTriggerInputTypes.TIME_INTERVAL,
      seconds: SNOOZE_MINUTES * 60,
      channelId: REMINDER_CHANNEL,
    },
  });
}

/** One alarm per active reminder and day, or one daily alarm for an every-day reminder. */
export function alarmPlan(reminders: Reminder[]): { reminder: Reminder; day: Day | null }[] {
  const plan: { reminder: Reminder; day: Day | null }[] = [];
  for (const r of reminders.filter((x) => x.is_active)) {
    if (ALL_DAYS.every((d) => r.days.includes(d))) plan.push({ reminder: r, day: null });
    else for (const day of r.days) plan.push({ reminder: r, day });
  }
  return plan.slice(0, MAX_ALARMS);
}

let syncing: Promise<void> = Promise.resolve();

/** Replaces every scheduled medicine alarm with the current reminders. */
export function syncAlarms(reminders: Reminder[], lang: Language): Promise<void> {
  // Run one sync at a time, so two quick reloads cannot schedule duplicates.
  syncing = syncing.then(() => doSync(reminders, lang)).catch(() => undefined);
  return syncing;
}

async function doSync(reminders: Reminder[], lang: Language) {
  if (Platform.OS === 'web') return;
  await setupDoseCategory(lang);
  const scheduled = await Notifications.getAllScheduledNotificationsAsync();
  await Promise.all(
    scheduled
      .filter((n) => n.content.data?.kind === KIND)
      .map((n) => Notifications.cancelScheduledNotificationAsync(n.identifier)),
  );

  for (const { reminder: r, day } of alarmPlan(reminders)) {
    const [hour, minute] = r.raw_time.split(':').map(Number);
    const medicine = r.medicine_name || translate(lang, 'yourMedicine');
    await Notifications.scheduleNotificationAsync({
      identifier: `reminder-${r.id}-${day ?? 'daily'}`,
      content: {
        title: translate(lang, 'medicineTime'),
        body: translate(lang, 'medicineTimeBody', { medicine }),
        sound: ALARM_SOUND,
        priority: AndroidNotificationPriority.MAX,
        categoryIdentifier: DOSE_CATEGORY,
        data: { kind: KIND, medicine, reminderId: r.id, rawTime: r.raw_time.slice(0, 5) },
      },
      trigger: day
        ? {
            type: SchedulableTriggerInputTypes.WEEKLY,
            channelId: REMINDER_CHANNEL,
            weekday: ALL_DAYS.indexOf(day) + 1, // 1 = Sunday
            hour,
            minute,
            delivery: 'alarmClock',
          }
        : { type: SchedulableTriggerInputTypes.DAILY, channelId: REMINDER_CHANNEL, hour, minute, delivery: 'alarmClock' },
    });
  }
}
