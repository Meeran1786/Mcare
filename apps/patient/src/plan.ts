import type { PlanStatus } from '@/api/mcare';

type Translate = (key: 'planFree' | 'planTrial' | 'planPaid', vars?: Record<string, string | number>) => string;

/**
 * One line about the account's plan. Plans are sold only on the website, so
 * the app never shows a price, a buy button or a link to pay (store rules).
 */
export function planLine(t: Translate, plan: PlanStatus, locale: string): string {
  if (plan.plan === 'free') return t('planFree');
  if (plan.is_trial) return t('planTrial', { name: plan.plan_name, days: plan.days_left ?? 0 });
  const date = plan.ends_at
    ? new Date(plan.ends_at).toLocaleDateString(locale, { day: 'numeric', month: 'long', year: 'numeric' })
    : '';
  return t('planPaid', { name: plan.plan_name, date });
}
