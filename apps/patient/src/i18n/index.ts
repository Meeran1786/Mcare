import { getLocales } from 'expo-localization';

import type { Day } from '@/voice/types';
import { ALL_DAYS } from '@/voice/types';

import bn from './strings/bn';
import en, { type Strings } from './strings/en';
import gu from './strings/gu';
import hi from './strings/hi';
import kn from './strings/kn';
import ml from './strings/ml';
import mr from './strings/mr';
import or from './strings/or';
import pa from './strings/pa';
import ta from './strings/ta';
import te from './strings/te';
import ur from './strings/ur';

export type LangCode = 'en' | 'hi' | 'bn' | 'mr' | 'te' | 'ta' | 'gu' | 'ur' | 'kn' | 'or' | 'ml' | 'pa';

export interface Language {
  code: LangCode;
  /** The language's own name, shown on the picker */
  name: string;
  /** BCP-47 tag for speech recognition and text-to-speech */
  speech: string;
  rtl?: boolean;
  /** Say "8:00 AM" (English) or "morning 8:00" (Indian languages) */
  ampm?: boolean;
  strings: Strings;
}

// Ordered by number of speakers in India (Census 2011), English first.
export const LANGUAGES: Language[] = [
  { code: 'en', name: 'English', speech: 'en-IN', ampm: true, strings: en },
  { code: 'hi', name: 'हिन्दी', speech: 'hi-IN', strings: hi },
  { code: 'bn', name: 'বাংলা', speech: 'bn-IN', strings: bn },
  { code: 'mr', name: 'मराठी', speech: 'mr-IN', strings: mr },
  { code: 'te', name: 'తెలుగు', speech: 'te-IN', strings: te },
  { code: 'ta', name: 'தமிழ்', speech: 'ta-IN', strings: ta },
  { code: 'gu', name: 'ગુજરાતી', speech: 'gu-IN', strings: gu },
  { code: 'ur', name: 'اردو', speech: 'ur-IN', rtl: true, strings: ur },
  { code: 'kn', name: 'ಕನ್ನಡ', speech: 'kn-IN', strings: kn },
  { code: 'or', name: 'ଓଡ଼ିଆ', speech: 'or-IN', strings: or },
  { code: 'ml', name: 'മലയാളം', speech: 'ml-IN', strings: ml },
  { code: 'pa', name: 'ਪੰਜਾਬੀ', speech: 'pa-IN', strings: pa },
];

export function getLanguage(code: string | null | undefined): Language {
  return LANGUAGES.find((l) => l.code === code) ?? LANGUAGES[0];
}

/** The phone's language if we support it, otherwise English. */
export function deviceLanguage(): LangCode {
  const tag = getLocales()[0]?.languageCode ?? 'en';
  return getLanguage(tag).code;
}

type StringKey = { [K in keyof Strings]: Strings[K] extends string ? K : never }[keyof Strings];

export function translate(lang: Language, key: StringKey, vars: Record<string, string | number> = {}): string {
  const template = lang.strings[key] ?? en[key];
  return template.replace(/\{(\w+)\}/g, (_, name) => String(vars[name] ?? ''));
}

/** "08:30" -> "8:30 AM" in English, "सुबह 8:30" in Hindi. */
export function formatTime(lang: Language, hhmm: string): string {
  const [h, m] = hhmm.split(':').map(Number);
  const h12 = h % 12 === 0 ? 12 : h % 12;
  const clock = `${h12}:${String(m).padStart(2, '0')}`;
  if (lang.ampm) return `${clock} ${h < 12 ? 'AM' : 'PM'}`;
  const s = lang.strings;
  const period = h < 12 && h >= 4 ? s.morning : h < 16 && h >= 12 ? s.afternoon : h < 20 && h >= 16 ? s.evening : s.night;
  return `${period} ${clock}`;
}

/** ["Mon","Thu"] -> "Monday, Thursday"; all seven -> "every day". */
export function formatDays(lang: Language, days: Day[]): string {
  if (ALL_DAYS.every((d) => days.includes(d))) return lang.strings.everyDay;
  return ALL_DAYS.filter((d) => days.includes(d))
    .map((d) => lang.strings.days[ALL_DAYS.indexOf(d)])
    .join(', ');
}

export function statusLabel(lang: Language, status: string): string {
  const key = `status_${status.toLowerCase()}` as keyof Strings;
  const value = lang.strings[key] ?? en[key];
  return typeof value === 'string' ? value : status.replace(/_/g, ' ');
}

export type { Strings };
