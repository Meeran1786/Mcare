import { fileType } from '@/api/partner';

import { monthRange, toCsv, toHtml } from '../export';

const labels = { orderId: 'Order', customer: 'Customer', prescriptions: 'Prescriptions', date: 'Date', amount: 'Amount', status: 'Status', total: 'Total' };
const result = {
  summary: { total_prescriptions_delivered: 3, total_earnings: 1290.5, orders_count: 2 },
  data: [
    { order_id: 12, customer_name: 'Ravi, Jr.', prescription_count: 1, delivered_date: '2026-10-02', amount: 640, delivery_status: 'completed' },
    { order_id: 15, customer_name: 'सीता "माँ"', prescription_count: 2, delivered_date: '2026-10-20', amount: 650.5, delivery_status: 'delivered' },
  ],
};

describe('export file', () => {
  it('writes a CSV with quoting, a total row and a BOM for Excel', () => {
    const csv = toCsv(result, labels);
    expect(csv.startsWith('﻿')).toBe(true);
    const lines = csv.slice(1).trim().split('\r\n');
    expect(lines[0]).toBe('Order,Customer,Prescriptions,Date,Amount,Status');
    expect(lines[1]).toBe('12,"Ravi, Jr.",1,2026-10-02,640.00,completed');
    expect(lines[2]).toBe('15,"सीता ""माँ""",2,2026-10-20,650.50,delivered');
    expect(lines[3]).toBe('Total,,3,,1290.50,');
  });

  it('escapes names in the printable page', () => {
    const html = toHtml({ ...result, data: [{ ...result.data[0], customer_name: '<b>x</b>' }] }, { ...labels, title: 'Delivered', period: 'Oct', pharmacy: 'A&B' });
    expect(html).toContain('&lt;b&gt;x&lt;/b&gt;');
    expect(html).toContain('A&amp;B');
    expect(html).toContain('₹1,290.50');
  });

  it('gives this month up to today and the whole of last month', () => {
    const now = new Date(2026, 9, 31, 15);
    expect(monthRange(now)).toEqual({ from: '2026-10-01', to: '2026-10-31' });
    expect(monthRange(new Date(2026, 0, 10), 1)).toEqual({ from: '2025-12-01', to: '2025-12-31' });
  });
});

describe('uploaded file type', () => {
  it('recognises PDFs from the picker even without an extension', () => {
    expect(fileType({ uri: 'content://docs/123', mimeType: 'application/pdf' }).type).toBe('application/pdf');
    expect(fileType({ uri: 'file:///x/licence.PDF' }).ext).toBe('pdf');
    expect(fileType({ uri: 'file:///x/photo.png' }).ext).toBe('png');
    expect(fileType({ uri: 'file:///x/photo' }).type).toBe('image/jpeg');
  });
});
