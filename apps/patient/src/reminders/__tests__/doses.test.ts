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

describe('refill plan', () => {
  const { refillPlan, MAX_REFILL_ALERTS, MAX_ALARMS } = jest.requireActual('../alarms') as typeof import('../alarms');
  const every = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as Reminder['days'];
  const withDays = (id: number, days_left: number | null, active = true) => ({ ...r(id, '08:00', every, active), days_left });
  const at = (p: { at: Date }) => `${p.at.getDate()} ${p.at.getHours()}:00`;

  test('warns each morning from 3 days before running out', () => {
    const now = new Date(2026, 10, 5, 9, 0); // 5 Nov, 9 am
    const plan = refillPlan([withDays(1, 5)], now);
    expect(plan.map((p) => [at(p), p.daysLeft])).toEqual([
      ['7 10:00', 3],
      ['8 10:00', 2],
      ['9 10:00', 1],
      ['10 10:00', 0],
    ]);
  });

  test('already low: starts today if 10 am has not passed, else tomorrow', () => {
    expect(at(refillPlan([withDays(1, 2)], new Date(2026, 10, 5, 9, 0))[0])).toBe('5 10:00');
    expect(at(refillPlan([withDays(1, 2)], new Date(2026, 10, 5, 11, 0))[0])).toBe('6 10:00');
  });

  test('skips reminders without a tablet count or switched off, and stays within the iPhone limit', () => {
    const now = new Date(2026, 10, 5, 9, 0);
    expect(refillPlan([withDays(1, null), withDays(2, 1, false)], now)).toEqual([]);
    // Plenty left: the first warning is set for 27 days from now, so it comes even if the app stays closed.
    expect(at(refillPlan([withDays(3, 30)], now)[0])).toBe('2 10:00');
    expect(refillPlan([withDays(1, 1), withDays(2, 2), withDays(3, 3)], now)).toHaveLength(MAX_REFILL_ALERTS);
    expect(MAX_ALARMS + MAX_REFILL_ALERTS).toBeLessThanOrEqual(60);
  });
});
