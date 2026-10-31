import type { DeliveryUpdate, Order } from '@/api/partner';

/** The steps a pharmacy moves an order through. The patient then confirms delivery ("completed"). */
export const ORDER_FLOW = ['pending', 'accepted', 'preparing', 'ready_for_delivery', 'out_for_delivery', 'delivered'] as const;

export const CLOSED = ['delivered', 'completed', 'cancelled', 'rejected'];

export function isClosed(status: string): boolean {
  return CLOSED.includes(status);
}

/** The next step for the big button on an order, or null when the pharmacy has nothing left to do. */
export function nextStatus(status: string): string | null {
  const i = ORDER_FLOW.indexOf(status as (typeof ORDER_FLOW)[number]);
  if (i < 0 || i === ORDER_FLOW.length - 1) return null;
  return ORDER_FLOW[i + 1];
}

export type DeliveryField = 'amount' | 'delivery_date' | 'delivery_time';

/**
 * The API refuses "out for delivery" and "delivered" until the order has a
 * delivery date, time and final amount. Check first, so we can ask for them.
 */
export function missingForDispatch(order: Pick<Order, 'total_amount' | 'delivery_date' | 'delivery_clock_time' | 'delivery_time'>, update: DeliveryUpdate = {}): DeliveryField[] {
  const missing: DeliveryField[] = [];
  const amount = update.amount ?? order.total_amount;
  if (amount === null || amount === undefined || amount === '' || Number(amount) <= 0) missing.push('amount');
  if (!update.delivery_date && !order.delivery_date) missing.push('delivery_date');
  if (!update.delivery_clock_time && !order.delivery_clock_time && !order.delivery_time) missing.push('delivery_time');
  return missing;
}

export function needsDeliveryDetails(status: string): boolean {
  return status === 'out_for_delivery' || status === 'delivered';
}

/** "2026-10-31" in the phone's own time zone. */
export function isoDate(d: Date): string {
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

/** Today, tomorrow and the day after, for the delivery-date buttons. */
export function nextDays(now: Date, count = 3): string[] {
  return Array.from({ length: count }, (_, i) => isoDate(new Date(now.getFullYear(), now.getMonth(), now.getDate() + i)));
}

/** "18:45" + 30 -> "19:15", wrapping round midnight. */
export function shiftTime(hhmm: string, minutes: number): string {
  const [h, m] = hhmm.split(':').map(Number);
  const total = (((h * 60 + m + minutes) % 1440) + 1440) % 1440;
  return `${String(Math.floor(total / 60)).padStart(2, '0')}:${String(total % 60).padStart(2, '0')}`;
}

/** A sensible default delivery time: the next half hour, at least 30 minutes from now. */
export function suggestedTime(now: Date): string {
  const mins = now.getHours() * 60 + now.getMinutes() + 30;
  const rounded = Math.ceil(mins / 30) * 30;
  return shiftTime('00:00', rounded);
}

/** "₹1,250" or "₹99.50". */
export function money(value: number | string | null | undefined): string {
  const n = Number(value ?? 0);
  return `₹${n.toLocaleString('en-IN', { minimumFractionDigits: n % 1 ? 2 : 0, maximumFractionDigits: 2 })}`;
}

/** Accepts "1250", "1,250", "₹ 1250.5"; null when it is not a positive amount. */
export function parseAmount(text: string): number | null {
  const cleaned = text.replace(/[₹,\s]/g, '');
  if (!/^\d+(\.\d{1,2})?$/.test(cleaned)) return null;
  const n = Number(cleaned);
  return n > 0 ? n : null;
}

/** Best address to deliver a prescription to, in the same order the API uses. */
export function deliveryAddress(p: { address?: Order['address']; order?: { address?: Order['address'] } | null; user?: { address?: Order['address'] } | null } | null | undefined) {
  return p?.address ?? p?.order?.address ?? p?.user?.address ?? null;
}

export function addressLine(a: Order['address'] | null | undefined): string {
  if (!a) return '';
  return [a.address_line, a.city, a.pincode].filter(Boolean).join(', ');
}
