import type { Reminder } from "@/api/mcare";

import { doseKey, todaysDoses } from "../doses";

const r = (
    id: number,
    raw_time: string,
    days: Reminder["days"] = ["Mon"],
    is_active = true,
): Reminder => ({
    id,
    raw_time,
    days,
    is_active,
    time: raw_time,
    note: null,
    medicine_name: `M${id}`,
    timezone: "Asia/Kolkata",
});

// Monday 2 November 2026, 09:00 local time
const now = new Date(2026, 10, 2, 9, 0);

test("dose key uses the local date and HH:mm", () => {
    expect(doseKey(now, "08:00:00")).toBe("2026-11-02 08:00");
});

test("today only, sorted, with logged, due and upcoming states", () => {
    const doses = todaysDoses(
        [
            r(1, "21:00"),
            r(2, "08:00:00"),
            r(3, "07:00"),
            r(4, "10:00", ["Tue"]),
            r(5, "06:00", ["Mon"], false),
        ],
        [
            {
                id: 1,
                reminder_id: 3,
                scheduled_for: "2026-11-02 07:00",
                status: "taken",
                taken_at: null,
            },
        ],
        now,
    );
    expect(doses.map((d) => [d.reminder.id, d.state])).toEqual([
        [3, "taken"],
        [2, "due"],
        [1, "upcoming"],
    ]);
});

test("a log from another day does not count", () => {
    const doses = todaysDoses(
        [r(1, "08:00")],
        [
            {
                id: 1,
                reminder_id: 1,
                scheduled_for: "2026-10-26 08:00",
                status: "taken",
                taken_at: null,
            },
        ],
        now,
    );
    expect(doses[0].state).toBe("due");
});
