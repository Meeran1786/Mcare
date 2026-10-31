import type { ExportResult } from '@/api/partner';

import { isoDate, money } from './orders';

/** First and last day of this month and last month, for the quick-pick buttons. */
export function monthRange(now: Date, monthsBack = 0): { from: string; to: string } {
  const first = new Date(now.getFullYear(), now.getMonth() - monthsBack, 1);
  const last = new Date(now.getFullYear(), now.getMonth() - monthsBack + 1, 0);
  return { from: isoDate(first), to: isoDate(monthsBack === 0 ? now : last) };
}

/** Quotes a CSV cell when it holds a comma, quote or line break. */
function cell(value: string | number | null | undefined): string {
  const text = value === null || value === undefined ? '' : String(value);
  return /[",\n\r]/.test(text) ? `"${text.replace(/"/g, '""')}"` : text;
}

export interface ExportLabels {
  orderId: string;
  customer: string;
  prescriptions: string;
  date: string;
  amount: string;
  status: string;
  total: string;
}

/** A spreadsheet file (opens in Excel and Google Sheets), with a total row. */
export function toCsv(result: ExportResult, labels: ExportLabels): string {
  const header = [labels.orderId, labels.customer, labels.prescriptions, labels.date, labels.amount, labels.status];
  const rows = result.data.map((r) => [r.order_id, r.customer_name, r.prescription_count, r.delivered_date, r.amount.toFixed(2), r.delivery_status]);
  const total = [labels.total, '', result.summary.total_prescriptions_delivered, '', result.summary.total_earnings.toFixed(2), ''];
  // The BOM makes Excel read Indian-language names correctly.
  return '﻿' + [header, ...rows, total].map((line) => line.map(cell).join(',')).join('\r\n') + '\r\n';
}

function escapeHtml(text: string | number | null | undefined): string {
  return String(text ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]!);
}

/** A printable page for the PDF version of the same report. */
export function toHtml(result: ExportResult, labels: ExportLabels & { title: string; period: string; pharmacy: string }): string {
  const rows = result.data
    .map(
      (r) =>
        `<tr><td>#${escapeHtml(r.order_id)}</td><td>${escapeHtml(r.customer_name)}</td><td class="n">${escapeHtml(r.prescription_count)}</td><td>${escapeHtml(r.delivered_date)}</td><td class="n">${escapeHtml(money(r.amount))}</td><td>${escapeHtml(r.delivery_status)}</td></tr>`,
    )
    .join('');
  return `<!doctype html><html><head><meta charset="utf-8"><style>
body{font-family:sans-serif;color:#0F1B2D;padding:24px}h1{color:#1D5245;margin:0}p{margin:4px 0 16px;color:#4A5568}
table{width:100%;border-collapse:collapse;font-size:12px}th,td{border-bottom:1px solid #C8DDD7;padding:6px;text-align:left}
th{background:#F4F8F7}.n{text-align:right}tfoot td{font-weight:bold;border-top:2px solid #2E7D6B}
</style></head><body><h1>${escapeHtml(labels.pharmacy)}</h1><p>${escapeHtml(labels.title)} · ${escapeHtml(labels.period)}</p>
<table><thead><tr><th>${escapeHtml(labels.orderId)}</th><th>${escapeHtml(labels.customer)}</th><th class="n">${escapeHtml(labels.prescriptions)}</th><th>${escapeHtml(labels.date)}</th><th class="n">${escapeHtml(labels.amount)}</th><th>${escapeHtml(labels.status)}</th></tr></thead>
<tbody>${rows}</tbody><tfoot><tr><td colspan="2">${escapeHtml(labels.total)}</td><td class="n">${escapeHtml(result.summary.total_prescriptions_delivered)}</td><td></td><td class="n">${escapeHtml(money(result.summary.total_earnings))}</td><td></td></tr></tfoot></table></body></html>`;
}
