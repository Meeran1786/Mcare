/** Today's doses on the phone, worked out from reminders and the server's dose log. */
import type { DoseLog, Reminder } from "@/api/mcare";
import { ALL_DAYS } from "@/voice/types";

export type DoseState = "taken" | "skipped" | "missed" | "due" | "upcoming";

export interface Dose {
    reminder: Reminder;
    /** "YYYY-MM-DD HH:mm" in the phone's time, the key the server stores. */
    scheduledFor: string;
    hhmm: string;
    state: DoseState;
}

const pad = (n: number) => String(n).padStart(2, "0");

export function dateKey(d: Date): string {
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
}

/** The dose key for a reminder time on the day of `when`. */
export function doseKey(when: Date, rawTime: string): string {
    return `${dateKey(when)} ${rawTime.slice(0, 5)}`;
}

export function todaysDoses(
    reminders: Reminder[],
    logs: DoseLog[],
    now = new Date(),
): Dose[] {
    const today = ALL_DAYS[now.getDay()];
    const nowMin = now.getHours() * 60 + now.getMinutes();
    const byKey = new Map(
        logs.map((l) => [`${l.reminder_id}|${l.scheduled_for}`, l.status]),
    );

    return reminders
        .filter((r) => r.is_active && r.days.includes(today))
        .map((r) => {
            const hhmm = r.raw_time.slice(0, 5);
            const [h, m] = hhmm.split(":").map(Number);
            const scheduledFor = doseKey(now, hhmm);
            const logged = byKey.get(`${r.id}|${scheduledFor}`);
            const state: DoseState =
                logged ?? (h * 60 + m <= nowMin ? "due" : "upcoming");
            return { reminder: r, scheduledFor, hhmm, state };
        })
        .sort((a, b) => a.hhmm.localeCompare(b.hhmm));
}
