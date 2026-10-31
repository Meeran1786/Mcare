import type { Day } from '../types';

export type Period = 'morning' | 'afternoon' | 'evening' | 'night';

/**
 * Words the voice parser understands for one language. Each entry is a word or
 * a phrase of space-separated words. A word ending in "*" matches as a prefix,
 * which covers suffixes (Tamil மருந்து* matches மருந்துகளை) and plurals.
 *
 * Every lexicon is merged into one: elderly users mix languages freely and the
 * phone's recogniser may return any script, so the parser checks all of them.
 */
export interface Lexicon {
  /** "remind me", "set an alarm" */
  remind: string[];
  /** "refill", "again", "same as last time" */
  refill: string[];
  /** "order", "buy", "I need", "send me" */
  order: string[];
  /** The noun "order" or "delivery", used to spot "where is my order" */
  orderNoun: string[];
  /** "where", "when", "status" */
  status: string[];
  /** "what", "which", "tell me", used to spot "what are my reminders" */
  question: string[];
  /** "medicine", "tablet", "pill" */
  medicine: string[];
  /** "reminder", "alarm" as a noun */
  reminderNoun: string[];
  today: string[];
  help: string[];
  yes: string[];
  no: string[];
  /** Number words one to twelve, index 0 = one. Several spellings per number allowed. */
  numbers: string[][];
  /** Words after a number that make it a clock time: "o'clock", "baje", "மணிக்கு" */
  timeMarkers: string[];
  /** Before an hour: half past ("saadhe"), quarter past ("sawa"), quarter to ("paune") */
  halfPast?: string[];
  quarterPast?: string[];
  quarterTo?: string[];
  periods: Record<Period, string[]>;
  days: Record<Day, string[]>;
  /** "weekdays" / "weekends" */
  weekdays?: string[];
  weekends?: string[];
  /** Filler that is never a medicine name: "my", "mujhe", "every", "daily" */
  filler: string[];
  /** Separate possessive words between a name and "medicine": "ki", "की", "ਦੀ" */
  possessive?: string[];
  /** Possessive suffixes glued to the name: Marathi "ची", Bengali "এর" */
  nameSuffixes?: string[];
  /** "take" verbs. English puts the name after them, Indian languages before. */
  takeBefore?: string[];
  takeAfter?: string[];
}
