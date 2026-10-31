/**
 * Rule-based understanding of spoken commands, entirely on the phone.
 *
 * The keyword lists live in ./lexicon, one file per language. They are merged
 * because elderly users mix languages and the phone's recogniser can return
 * any script. Nothing here acts on the result: the caller always reads the
 * understood command back and waits for a "yes" before saving or ordering.
 */
import { LEXICONS, type Lexicon, type Period } from './lexicon';
import { ALL_DAYS, type Day, type Intent } from './types';

// ---------------------------------------------------------------------------
// Normalisation
// ---------------------------------------------------------------------------

// Code point of "0" in every decimal digit block a recogniser may return.
const DIGIT_ZEROS = [
  0x0660, 0x06f0, // Arabic-Indic, Urdu
  0x0966, 0x09e6, 0x0a66, 0x0ae6, 0x0b66, 0x0be6, 0x0c66, 0x0ce6, 0x0d66, // Indic
];
const NUKTAS = /[़়਼઼଼]/g;
const CHILLU: Record<string, string> = {
  'ൺ': 'ണ്', 'ൻ': 'ന്', 'ർ': 'ര്',
  'ൽ': 'ല്', 'ൾ': 'ള്', 'ൿ': 'ക്',
};

export function normalize(input: string): string {
  return input
    .normalize('NFC')
    .normalize('NFD') // split precomposed nukta letters (ज़, য়, ਸ਼) ...
    .replace(NUKTAS, '') // ... and drop the nukta, so spellings with and without it match
    .normalize('NFC')
    .toLowerCase()
    .replace(/\p{Nd}/gu, (ch) => {
      const code = ch.codePointAt(0)!;
      const zero = DIGIT_ZEROS.find((z) => code >= z && code <= z + 9);
      return zero === undefined ? ch : String(code - zero);
    })
    .replace(/[‌‍]/g, '') // zero-width joiners
    .replace(/[ൺ-ൿ]/g, (ch) => CHILLU[ch]) // Malayalam chillu letters
    .replace(/ँ/g, 'ं') // Devanagari chandrabindu -> anusvara: हाँ -> हां
    .replace(/ي/g, 'ی') // Arabic yeh -> Urdu yeh
    .replace(/ك/g, 'ک') // Arabic kaf -> Urdu keheh
    .replace(/a\.\s?m\b\.?/g, 'am')
    .replace(/p\.\s?m\b\.?/g, 'pm')
    .replace(/o'?\s?clock/g, 'oclock')
    .replace(/(\d)[:.](\d)/g, '$1:$2') // 8.30 -> 8:30
    .replace(/[^\p{L}\p{M}\p{N}:'\s-]/gu, ' ')
    .replace(/(\d)(\p{L})/gu, '$1 $2') // 8pm, ৮টায়, 500mg -> separate tokens
    .replace(/\s+/g, ' ')
    .trim();
}

const tokens = (s: string): string[] => (s.length ? s.split(' ') : []);

// ---------------------------------------------------------------------------
// Merged, normalised lexicon
// ---------------------------------------------------------------------------

/** Normalise a keyword the same way as speech, keeping a trailing "*". */
const norm = (w: string) => (w.endsWith('*') ? normalize(w.slice(0, -1)) + '*' : normalize(w));
const all = (pick: (l: Lexicon) => string[] | undefined) =>
  Object.values(LEXICONS).flatMap((l) => (pick(l) ?? []).map(norm));

const L = {
  remind: all((l) => l.remind),
  refill: all((l) => l.refill),
  order: all((l) => l.order),
  orderNoun: all((l) => l.orderNoun),
  status: all((l) => l.status),
  question: all((l) => l.question),
  medicine: all((l) => l.medicine),
  reminderNoun: all((l) => l.reminderNoun),
  today: all((l) => l.today),
  help: all((l) => l.help),
  yes: all((l) => l.yes),
  no: all((l) => l.no),
  timeMarkers: all((l) => l.timeMarkers),
  halfPast: all((l) => l.halfPast),
  quarterPast: all((l) => l.quarterPast),
  quarterTo: all((l) => l.quarterTo),
  weekdays: all((l) => l.weekdays),
  weekends: all((l) => l.weekends),
  filler: all((l) => l.filler),
  possessive: all((l) => l.possessive),
  nameSuffixes: all((l) => l.nameSuffixes).sort((a, b) => b.length - a.length),
  takeBefore: all((l) => l.takeBefore),
  takeAfter: all((l) => l.takeAfter),
  periods: Object.fromEntries(
    (['morning', 'afternoon', 'evening', 'night'] as Period[]).map((p) => [p, all((l) => l.periods[p])]),
  ) as Record<Period, string[]>,
  days: Object.fromEntries(ALL_DAYS.map((d) => [d, all((l) => l.days[d])])) as Record<Day, string[]>,
};

const NUMBER_WORDS = new Map<string, number>();
Object.values(LEXICONS).forEach((l) =>
  l.numbers.forEach((spellings, i) => spellings.forEach((w) => NUMBER_WORDS.set(normalize(w), i + 1))),
);

// ---------------------------------------------------------------------------
// Matching
// ---------------------------------------------------------------------------

const matchWord = (pattern: string, tok: string) =>
  pattern.endsWith('*') ? tok.startsWith(pattern.slice(0, -1)) : tok === pattern;

const isAny = (tok: string | undefined, list: string[]) =>
  tok !== undefined && list.some((p) => !p.includes(' ') && matchWord(p, tok));

function findPhrase(toks: string[], phrase: string): number {
  const parts = phrase.split(' ');
  outer: for (let i = 0; i + parts.length <= toks.length; i++) {
    for (let j = 0; j < parts.length; j++) {
      if (!matchWord(parts[j], toks[i + j])) continue outer;
    }
    return i;
  }
  return -1;
}

/** True when any phrase occurs as consecutive whole words ("word*" = prefix). */
export function hasAny(text: string, phrases: string[]): boolean {
  const toks = tokens(text);
  return phrases.some((phrase) => findPhrase(toks, phrase) >= 0);
}

export function isNegative(spoken: string): boolean {
  return hasAny(normalize(spoken), L.no);
}

export function isAffirmative(spoken: string): boolean {
  const n = normalize(spoken);
  return !hasAny(n, L.no) && hasAny(n, L.yes);
}

// ---------------------------------------------------------------------------
// Time
// ---------------------------------------------------------------------------

const PERIOD_DEFAULT: Record<Period, number> = { morning: 8, afternoon: 14, evening: 18, night: 21 };

function findPeriod(text: string): Period | undefined {
  return (Object.keys(L.periods) as Period[]).find((p) => hasAny(text, L.periods[p]));
}

/**
 * Reads an hour from one token: "8", "8:30", "eight", or a number word with a
 * time marker glued on, as Bengali and Odia write it ("আটটায়" = at eight).
 */
function readHour(tok: string): { h: number; m: number; clock: boolean; glued: boolean } | undefined {
  const clock = /^(\d{1,2})(?::(\d{2}))?$/.exec(tok);
  if (clock) return { h: Number(clock[1]), m: clock[2] ? Number(clock[2]) : 0, clock: !!clock[2], glued: false };
  const word = NUMBER_WORDS.get(tok);
  if (word !== undefined) return { h: word, m: 0, clock: false, glued: false };
  for (const [w, n] of NUMBER_WORDS) {
    if (w.length >= 2 && tok.startsWith(w) && isAny(tok.slice(w.length), L.timeMarkers)) {
      return { h: n, m: 0, clock: false, glued: true };
    }
  }
  return undefined;
}

const pad = (n: number) => String(n).padStart(2, '0');

/** Returns "HH:mm" (24 hour), or undefined when no time was said. */
export function parseTime(text: string): string | undefined {
  const toks = tokens(text);
  const period = findPeriod(text);
  let hour: number | undefined;
  let minute = 0;
  let meridiem: 'am' | 'pm' | undefined;

  for (let i = 0; i < toks.length; i++) {
    const read = readHour(toks[i]);
    if (!read || read.h > 23 || read.m > 59) continue;
    let { h, m } = read;

    const next = toks[i + 1] ?? '';
    const prev = toks[i - 1] ?? '';
    const marked = read.glued || read.clock || isAny(next, L.timeMarkers);
    const afterAt = ['at', 'by', 'around'].includes(prev);
    const half = isAny(prev, L.halfPast);
    const qPast = isAny(prev, L.quarterPast);
    const qTo = isAny(prev, L.quarterTo);
    // A bare digit ("metformin 500", "take 2 tablets") is only a time when a
    // time of day was said too and nothing marks it as a dose.
    const bareOk = /^\d+$/.test(toks[i]) && !!period && h <= 12 && !isAny(next, [...L.medicine, 'mg', 'ml']);
    if (!(marked || afterAt || half || qPast || qTo || bareOk)) continue;

    if (half) m = 30;
    else if (qPast) m = 15;
    else if (qTo) {
      h = h === 1 ? 12 : h - 1;
      m = 45;
    }
    // "8 बजकर 30 मिनट", "8 bajkar 30"
    if (/^(बजकर|bajkar|بج)$/.test(next) && /^\d{1,2}$/.test(toks[i + 2] ?? '')) m = Number(toks[i + 2]);
    if (next === 'am' || next === 'pm') meridiem = next;
    hour = h;
    minute = m;
    break;
  }

  if (hour === undefined) return period ? `${pad(PERIOD_DEFAULT[period])}:00` : undefined;

  if (hour <= 12) {
    if (meridiem === 'pm' && hour < 12) hour += 12;
    else if (meridiem === 'am' && hour === 12) hour = 0;
    else if (!meridiem) {
      if (period === 'afternoon' && hour <= 6) hour += 12;
      else if (period === 'evening' && hour < 12) hour += 12;
      else if (period === 'night') hour = hour === 12 ? 0 : hour >= 5 ? hour + 12 : hour;
      // With no am/pm and no time of day, 1 to 6 is far more likely the
      // afternoon for a medicine. The read-back lets the person correct it.
      else if (!period && hour >= 1 && hour <= 6) hour += 12;
    }
  }
  return `${pad(hour)}:${pad(minute)}`;
}

// ---------------------------------------------------------------------------
// Days
// ---------------------------------------------------------------------------

export function parseDays(text: string): Day[] {
  if (hasAny(text, L.weekdays)) return ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'];
  if (hasAny(text, L.weekends)) return ['Sat', 'Sun'];
  const named = ALL_DAYS.filter((d) => hasAny(text, L.days[d]));
  return named.length ? named : [...ALL_DAYS];
}

// ---------------------------------------------------------------------------
// Medicine name
// ---------------------------------------------------------------------------

/** Words that describe when or how, never what. */
function notAName(tok: string): boolean {
  return (
    /^\d/.test(tok) ||
    NUMBER_WORDS.has(tok) ||
    readHour(tok)?.glued === true ||
    isAny(tok, L.filler) ||
    isAny(tok, L.medicine) ||
    isAny(tok, L.possessive) ||
    isAny(tok, L.timeMarkers) ||
    isAny(tok, L.remind) ||
    isAny(tok, L.reminderNoun) ||
    isAny(tok, L.today) ||
    isAny(tok, L.takeAfter) ||
    isAny(tok, L.halfPast) ||
    isAny(tok, L.quarterPast) ||
    isAny(tok, L.quarterTo) ||
    Object.values(L.periods).some((p) => isAny(tok, p)) ||
    Object.values(L.days).some((d) => isAny(tok, d))
  );
}

function stripSuffix(tok: string): string {
  for (const s of L.nameSuffixes) {
    if (tok.endsWith(s) && [...tok].length - [...s].length >= 3) return tok.slice(0, -s.length);
  }
  return tok;
}

const ENGLISH_STOP = new Set([
  'at', 'every', 'daily', 'each', 'in', 'on', 'tomorrow', 'tonight', 'today', 'before', 'after',
  'with', 'and', 'everyday', 'please', 'reminder', 'remind', 'am', 'pm', 'oclock',
]);
const ENGLISH_SKIP = new Set(['my', 'the', 'a', 'an', 'me', 'to', 'take']);

/** The name just before position `end` ("metformin 500" ← ki goli), or undefined. */
function nameBefore(toks: string[], end: number): string | undefined {
  let k = end - 1;
  while (k >= 0 && isAny(toks[k], L.possessive)) k--;
  const words: string[] = [];
  if (k > 0 && /^\d+$/.test(toks[k]) && !isAny(toks[k + 1], L.timeMarkers)) words.unshift(toks[k--]);
  if (k >= 0 && !notAName(toks[k]) && !ENGLISH_SKIP.has(toks[k])) {
    words.unshift(stripSuffix(toks[k]));
    return words.join(' ');
  }
  return undefined;
}

export function parseMedicine(text: string): string | undefined {
  const toks = tokens(text);

  // English: "take metformin 500 at 8", "for my bp tablet", "about vitamin d"
  for (let i = 0; i < toks.length; i++) {
    if (!isAny(toks[i], L.takeBefore)) continue;
    const words: string[] = [];
    for (let j = i + 1; j < toks.length && words.length < 4; j++) {
      const w = toks[j];
      if (ENGLISH_STOP.has(w) || isAny(w, L.timeMarkers)) break;
      if (/^\d{1,2}(:\d{2})?$/.test(w) && isAny(toks[j + 1], L.timeMarkers)) break;
      if (ENGLISH_SKIP.has(w) || (isAny(w, L.medicine) && !words.length)) continue;
      if (isAny(w, L.medicine)) break;
      words.push(w);
    }
    if (words.length) return words.join(' ');
  }

  // Before a medicine word ("metformin ki goli", "মেটফরমিন ওষুধ", "மெட்ஃபார்மின் மாத்திரை")
  // or a take verb ("metformin lene", "मेटफॉर्मिन घ्यायला").
  for (let i = 1; i < toks.length; i++) {
    if (isAny(toks[i], L.medicine) || isAny(toks[i], L.takeAfter) || isAny(toks[i], L.reminderNoun)) {
      const name = nameBefore(toks, i);
      if (name) return name;
    }
  }
  return undefined;
}

// ---------------------------------------------------------------------------
// Intent
// ---------------------------------------------------------------------------

export function parseCommand(spoken: string): Intent {
  const text = normalize(spoken);
  if (!text) return { type: 'unknown', text: spoken };

  const remind = hasAny(text, L.remind);
  const refill = hasAny(text, L.refill);

  if (hasAny(text, L.orderNoun) && hasAny(text, L.status) && !refill) return { type: 'order_status' };
  if (refill && !remind) return { type: 'refill_last' };

  const time = parseTime(text);
  const medicine = parseMedicine(text);
  const asksList =
    hasAny(text, L.question) &&
    (hasAny(text, L.reminderNoun) || hasAny(text, L.medicine) || hasAny(text, L.today));
  if (asksList && !time) return { type: 'list_reminders' };

  const ordering = hasAny(text, L.order);
  if (remind || (time && medicine && !ordering) || (time && hasAny(text, L.medicine) && !ordering)) {
    return { type: 'create_reminder', time, days: parseDays(text), medicine };
  }
  if (ordering) return { type: 'new_order' };
  if (hasAny(text, L.help)) return { type: 'help' };
  return { type: 'unknown', text: spoken };
}
