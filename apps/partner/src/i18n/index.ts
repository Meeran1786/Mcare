import { getLocales } from 'expo-localization';

import en, { type Strings } from './strings/en';
import hi from './strings/hi';
import te from './strings/te';
import ur from './strings/ur';

// The four languages the old Partner app had. To add one, copy strings/en.ts,
// translate it and list it here (the patient app has eight more).
export type LangCode = 'en' | 'hi' | 'te' | 'ur';

export interface Language {
  code: LangCode;
  /** The language's own name, shown on the picker */
  name: string;
  /** BCP-47 tag for dates and numbers */
  locale: string;
  rtl?: boolean;
  strings: Strings;
}

export const LANGUAGES: Language[] = [
  { code: 'en', name: 'English', locale: 'en-IN', strings: en },
  { code: 'hi', name: 'हिन्दी', locale: 'hi-IN', strings: hi },
  { code: 'te', name: 'తెలుగు', locale: 'te-IN', strings: te },
  { code: 'ur', name: 'اردو', locale: 'ur-IN', rtl: true, strings: ur },
];

export function getLanguage(code: string | null | undefined): Language {
  return LANGUAGES.find((l) => l.code === code) ?? LANGUAGES[0];
}

/** The phone's language if we support it, otherwise English. */
export function deviceLanguage(): LangCode {
  return getLanguage(getLocales()[0]?.languageCode ?? 'en').code;
}

export type StringKey = keyof Strings;

export function translate(lang: Language, key: StringKey, vars: Record<string, string | number> = {}): string {
  const template = lang.strings[key] ?? en[key];
  return template.replace(/\{(\w+)\}/g, (_, name) => String(vars[name] ?? ''));
}

export function statusLabel(lang: Language, status: string): string {
  const key = `status_${status.toLowerCase()}` as StringKey;
  return lang.strings[key] ?? en[key] ?? status.replace(/_/g, ' ');
}

/** "31 Oct, 6:30 pm" in the chosen language. */
export function formatDateTime(lang: Language, iso: string | null | undefined): string {
  if (!iso) return '';
  const d = new Date(iso);
  if (Number.isNaN(d.getTime())) return iso;
  return d.toLocaleString(lang.locale, { day: 'numeric', month: 'short', hour: 'numeric', minute: '2-digit' });
}

/** "2026-10-31" -> "Sat, 31 Oct". */
export function formatDate(lang: Language, ymd: string | null | undefined): string {
  if (!ymd) return '';
  const [y, m, d] = ymd.slice(0, 10).split('-').map(Number);
  return new Date(y, m - 1, d).toLocaleDateString(lang.locale, { weekday: 'short', day: 'numeric', month: 'short' });
}

/** "18:30" -> "6:30 pm". */
export function formatClock(lang: Language, hhmm: string | null | undefined): string {
  if (!hhmm) return '';
  const [h, m] = hhmm.split(':').map(Number);
  return new Date(2000, 0, 1, h, m).toLocaleTimeString(lang.locale, { hour: 'numeric', minute: '2-digit' });
}

export type { Strings };
