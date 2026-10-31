import {
  addressLine,
  deliveryAddress,
  isClosed,
  missingForDispatch,
  money,
  nextDays,
  nextStatus,
  parseAmount,
  shiftTime,
  suggestedTime,
} from '../orders';

const blank = { total_amount: null, delivery_date: null, delivery_clock_time: null, delivery_time: null };

describe('order flow', () => {
  it('moves through the pharmacy steps in order', () => {
    expect(nextStatus('pending')).toBe('accepted');
    expect(nextStatus('accepted')).toBe('preparing');
    expect(nextStatus('preparing')).toBe('ready_for_delivery');
    expect(nextStatus('ready_for_delivery')).toBe('out_for_delivery');
    expect(nextStatus('out_for_delivery')).toBe('delivered');
  });

  it('stops after delivery and for closed or unknown orders', () => {
    expect(nextStatus('delivered')).toBeNull();
    expect(nextStatus('completed')).toBeNull();
    expect(nextStatus('cancelled')).toBeNull();
    expect(nextStatus('something_new')).toBeNull();
    expect(isClosed('completed')).toBe(true);
    expect(isClosed('preparing')).toBe(false);
  });
});

describe('delivery details before dispatch', () => {
  it('lists everything missing on a new order', () => {
    expect(missingForDispatch(blank)).toEqual(['amount', 'delivery_date', 'delivery_time']);
  });

  it('accepts details already on the order or in the update', () => {
    expect(missingForDispatch({ ...blank, total_amount: '450.00', delivery_date: '2026-10-31' }, { delivery_clock_time: '18:00' })).toEqual([]);
    expect(missingForDispatch({ ...blank, delivery_time: '2026-10-31T18:00:00Z' }, { amount: 99, delivery_date: '2026-10-31' })).toEqual([]);
  });

  it('treats a zero amount as missing', () => {
    expect(missingForDispatch({ ...blank, total_amount: '0.00', delivery_date: 'x', delivery_clock_time: 'y' })).toEqual(['amount']);
  });
});

describe('dates, times and money', () => {
  it('gives today and the next days in local time, across a month end', () => {
    expect(nextDays(new Date(2026, 9, 31, 22, 0))).toEqual(['2026-10-31', '2026-11-01', '2026-11-02']);
  });

  it('shifts times and wraps round midnight', () => {
    expect(shiftTime('18:45', 30)).toBe('19:15');
    expect(shiftTime('23:45', 30)).toBe('00:15');
    expect(shiftTime('00:15', -30)).toBe('23:45');
  });

  it('suggests the next half hour at least 30 minutes ahead', () => {
    expect(suggestedTime(new Date(2026, 9, 31, 10, 5))).toBe('11:00');
    expect(suggestedTime(new Date(2026, 9, 31, 10, 30))).toBe('11:00');
    expect(suggestedTime(new Date(2026, 9, 31, 23, 50))).toBe('00:30');
  });

  it('formats and reads amounts', () => {
    expect(money(1250)).toBe('₹1,250');
    expect(money('99.5')).toBe('₹99.50');
    expect(parseAmount('₹ 1,250')).toBe(1250);
    expect(parseAmount('99.50')).toBe(99.5);
    expect(parseAmount('0')).toBeNull();
    expect(parseAmount('12a')).toBeNull();
  });
});

describe('delivery address', () => {
  const a = { id: 1, address_line: '1 Main Rd', city: 'Hyderabad', pincode: '500001' };
  it('prefers the prescription address, then the order, then the patient default', () => {
    expect(deliveryAddress({ address: a })).toBe(a);
    expect(deliveryAddress({ order: { address: a } })).toBe(a);
    expect(deliveryAddress({ user: { address: a } })).toBe(a);
    expect(deliveryAddress(null)).toBeNull();
    expect(addressLine(a)).toBe('1 Main Rd, Hyderabad, 500001');
  });
});
