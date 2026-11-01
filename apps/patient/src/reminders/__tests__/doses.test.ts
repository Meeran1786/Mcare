import type { Reminder } from '@/api/mcare';

import { doseKey, todaysDoses } from '../doses';

const r = (id: number, raw_time: string, days: Reminder['days'] = ['Mon'], is_active = true): Reminder => ({
  id,
  raw_time,
  days,
  is_active,
  time: raw_time,
  note: null,
  medicine_name: `M${id}`,
  timezone: 'Asia/Kolkata',
});

// Monday 2 November 2026, 09:00 local time
const now = new Date(2026, 10, 2, 9, 0);

test('dose key uses the local date and HH:mm', () => {
  expect(doseKey(now, '08:00:00')).toBe('2026-11-02 08:00');
});

test('today only, sorted, with logged, due and upcoming states', () => {
  const doses = todaysDoses(
    [r(1, '21:00'), r(2, '08:00:00'), r(3, '07:00'), r(4, '10:00', ['Tue']), r(5, '06:00', ['Mon'], false)],
    [
      {
        id: 1,
        reminder_id: 3,
        scheduled_for: '2026-11-02 07:00',
        status: 'taken',
        taken_at: null,
      },
    ],
    now,
  );
  expect(doses.map((d) => [d.reminder.id, d.state])).toEqual([
    [3, 'taken'],
    [2, 'due'],
    [1, 'upcoming'],
  ]);
});

test('a log from another day does not count', () => {
  const doses = todaysDoses(
    [r(1, '08:00')],
    [
      {
        id: 1,
        reminder_id: 1,
        scheduled_for: '2026-10-26 08:00',
        status: 'taken',
        taken_at: null,
      },
    ],
    now,
  );
  expect(doses[0].state).toBe('due');
});

describe('alarm plan', () => {
  // alarms.ts imports expo-notifications, which jest-expo mocks.
  const { alarmPlan, MAX_ALARMS } = jest.requireActual('../alarms') as typeof import('../alarms');
  const every = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as Reminder['days'];

  test('every-day reminders become one daily alarm', () => {
    const plan = alarmPlan([r(1, '08:00', every), r(2, '21:00', ['Mon', 'Thu']), r(3, '07:00', every, false)]);
    expect(plan.map((p) => [p.reminder.id, p.day])).toEqual([
      [1, null],
      [2, 'Mon'],
      [2, 'Thu'],
    ]);
  });

  test('never more than the iPhone limit', () => {
    const many = Array.from({ length: 20 }, (_, i) => r(i + 1, '08:00', ['Mon', 'Tue', 'Wed', 'Thu', 'Fri']));
    expect(alarmPlan(many)).toHaveLength(MAX_ALARMS);
  });
});

test('running low within 3 days of tablets, active reminders only', () => {
  const { runningLow } = jest.requireActual('../doses') as typeof import('../doses');
  expect(runningLow({ is_active: true, days_left: 3 })).toBe(true);
  expect(runningLow({ is_active: true, days_left: 0 })).toBe(true);
  expect(runningLow({ is_active: true, days_left: 4 })).toBe(false);
  expect(runningLow({ is_active: false, days_left: 1 })).toBe(false);
  expect(runningLow({ is_active: true, days_left: null })).toBe(false);
});
