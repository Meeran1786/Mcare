/**
 * Turns an understood command into something the assistant says and, for
 * anything that changes data or places an order, a step that runs only after
 * the person says "yes".
 */
import type { Href } from 'expo-router';

import type { api as Api, Order } from '@/api/mcare';
import { formatDays, formatTime, type Language, statusLabel, translate } from '@/i18n';

import type { Day, Intent } from './types';

export type Outcome = { say: string; navigate?: Href; changed?: boolean };

export type Prepared =
  | { kind: 'confirm'; say: string; run: () => Promise<Outcome> }
  | { kind: 'askTime'; say: string; days: Day[]; medicine?: string }
  | ({ kind: 'say' } & Outcome);

export interface Ctx {
  lang: Language;
  api: Pick<typeof Api, 'reminders' | 'createReminder' | 'orders' | 'reorder'>;
  timezone: string;
}

const DONE = ['delivered', 'completed'];
const CLOSED = ['delivered', 'completed', 'cancelled', 'rejected'];

function pharmacyName(order: Order): string {
  return order.pharmacy?.pharmacy_name ?? 'Mcare';
}

function shortDate(lang: Language, iso: string): string {
  try {
    return new Date(iso).toLocaleDateString(lang.speech, { day: 'numeric', month: 'long' });
  } catch {
    return iso.slice(0, 10);
  }
}

export function reminderConfirm(ctx: Ctx, time: string, days: Day[], medicine?: string): Prepared {
  const { lang } = ctx;
  const vars = { time: formatTime(lang, time), days: formatDays(lang, days), medicine: medicine ?? '' };
  return {
    kind: 'confirm',
    say: translate(lang, medicine ? 'rbReminder' : 'rbReminderNoName', vars),
    run: async () => {
      await ctx.api.createReminder({ time, days, medicine_name: medicine, timezone: ctx.timezone });
      return { say: translate(lang, 'reminderDone'), changed: true };
    },
  };
}

export async function prepare(intent: Intent, ctx: Ctx): Promise<Prepared> {
  const { lang } = ctx;
  const t = (key: Parameters<typeof translate>[1], vars?: Record<string, string | number>) =>
    translate(lang, key, vars);

  switch (intent.type) {
    case 'create_reminder':
      if (!intent.time) return { kind: 'askTime', say: t('askTime'), days: intent.days, medicine: intent.medicine };
      return reminderConfirm(ctx, intent.time, intent.days, intent.medicine);

    case 'refill_last': {
      const last = (await ctx.api.orders()).find((o) => DONE.includes(o.status.toLowerCase()));
      if (!last) return { kind: 'say', say: t('noPastOrder'), navigate: '/upload' };
      const pharmacy = pharmacyName(last);
      return {
        kind: 'confirm',
        say: t('rbRefill', { pharmacy, date: shortDate(lang, last.created_at) }),
        run: async () => {
          await ctx.api.reorder(last.id);
          return { say: t('refillDone', { pharmacy }), navigate: '/orders', changed: true };
        },
      };
    }

    case 'new_order':
      return { kind: 'say', say: t('newOrderSay'), navigate: '/upload' };

    case 'order_status': {
      const orders = await ctx.api.orders();
      const current = orders.find((o) => !CLOSED.includes(o.status.toLowerCase())) ?? orders[0];
      if (!current) return { kind: 'say', say: t('noOrdersSay') };
      return {
        kind: 'say',
        say: t('statusSay', { pharmacy: pharmacyName(current), status: statusLabel(lang, current.status) }),
        navigate: '/orders',
      };
    }

    case 'list_reminders': {
      const active = (await ctx.api.reminders()).filter((r) => r.is_active);
      if (!active.length) return { kind: 'say', say: t('listEmptySay') };
      const list = active
        .map((r) => `${r.medicine_name || t('yourMedicine')} ${formatTime(lang, r.raw_time.slice(0, 5))}`)
        .join(', ');
      return { kind: 'say', say: t('listSay', { count: active.length, list }), navigate: '/reminders' };
    }

    case 'help':
      return { kind: 'say', say: t('helpSay') };

    case 'unknown':
      return { kind: 'say', say: t('notUnderstood') };
  }
}
