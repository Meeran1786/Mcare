/**
 * Medicine reminders as alarms scheduled on the phone, so they ring on time
 * even without internet. The backend stops pushing reminders for phones that
 * register with `local_reminders: true` (see registerForPush), so people do
 * not get each reminder twice.
 */
import * as Notifications from "expo-notifications";
import {
    AndroidImportance,
    AndroidNotificationPriority,
    SchedulableTriggerInputTypes,
} from "expo-notifications";
import { Platform } from "react-native";

import type { Reminder } from "@/api/mcare";
import { type Language, translate } from "@/i18n";
import { ALL_DAYS } from "@/voice/types";

export const REMINDER_CHANNEL = "medicine-reminders";
export const DOSE_CATEGORY = "medicine-dose";
export const ACTION_TAKEN = "taken";
export const ACTION_SNOOZE = "snooze";
export const SNOOZE_MINUTES = 10;
const KIND = "mcare-medicine-reminder";
const SNOOZE_KIND = "mcare-medicine-snooze";

/** The "Taken" and "Snooze 10 min" buttons on the alarm, in the person's language. */
async function setupDoseCategory(lang: Language) {
    await Notifications.setNotificationCategoryAsync(DOSE_CATEGORY, [
        {
            identifier: ACTION_TAKEN,
            buttonTitle: translate(lang, "doseTaken"),
            options: { opensAppToForeground: true },
        },
        {
            identifier: ACTION_SNOOZE,
            buttonTitle: translate(lang, "snooze", { min: SNOOZE_MINUTES }),
            options: { opensAppToForeground: true },
        },
    ]);
}

/** Rings the same dose again in 10 minutes, keeping the original dose time. */
export async function snoozeDose(
    content: Notifications.NotificationContent,
    scheduledFor: string,
) {
    if (Platform.OS === "web") return;
    await Notifications.scheduleNotificationAsync({
        content: {
            title: content.title,
            body: content.body,
            sound: "default",
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

export async function setupReminderChannel() {
    if (Platform.OS !== "android") return;
    await Notifications.setNotificationChannelAsync(REMINDER_CHANNEL, {
        name: "Medicine reminders",
        importance: AndroidImportance.MAX,
        sound: "default",
        vibrationPattern: [0, 600, 300, 600, 300, 600],
    });
}

let syncing: Promise<void> = Promise.resolve();

/** Replaces every scheduled medicine alarm with one per active reminder and day. */
export function syncAlarms(
    reminders: Reminder[],
    lang: Language,
): Promise<void> {
    // Run one sync at a time, so two quick reloads cannot schedule duplicates.
    syncing = syncing
        .then(() => doSync(reminders, lang))
        .catch(() => undefined);
    return syncing;
}

async function doSync(reminders: Reminder[], lang: Language) {
    if (Platform.OS === "web") return;
    await setupDoseCategory(lang);
    const scheduled = await Notifications.getAllScheduledNotificationsAsync();
    await Promise.all(
        scheduled
            .filter((n) => n.content.data?.kind === KIND)
            .map((n) =>
                Notifications.cancelScheduledNotificationAsync(n.identifier),
            ),
    );

    for (const r of reminders.filter((x) => x.is_active)) {
        const [hour, minute] = r.raw_time.split(":").map(Number);
        const medicine = r.medicine_name || translate(lang, "yourMedicine");
        for (const day of r.days) {
            await Notifications.scheduleNotificationAsync({
                identifier: `reminder-${r.id}-${day}`,
                content: {
                    title: translate(lang, "medicineTime"),
                    body: translate(lang, "medicineTimeBody", { medicine }),
                    sound: "default",
                    priority: AndroidNotificationPriority.MAX,
                    categoryIdentifier: DOSE_CATEGORY,
                    data: {
                        kind: KIND,
                        reminderId: r.id,
                        rawTime: r.raw_time.slice(0, 5),
                    },
                },
                trigger: {
                    type: SchedulableTriggerInputTypes.WEEKLY,
                    channelId: REMINDER_CHANNEL,
                    weekday: ALL_DAYS.indexOf(day) + 1, // 1 = Sunday
                    hour,
                    minute,
                    delivery: "alarmClock",
                },
            });
        }
    }
}
