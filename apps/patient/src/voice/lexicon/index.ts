import type { Lexicon } from './types';
import en from './en';
import hi from './hi';
import ur from './ur';
import te from './te';
import bn from './bn';
import mr from './mr';
import ta from './ta';
import gu from './gu';
import kn from './kn';
import or from './or';
import ml from './ml';
import pa from './pa';

export const LEXICONS: Record<string, Lexicon> = { en, hi, ur, te, bn, mr, ta, gu, kn, or, ml, pa };
export type { Lexicon, Period } from './types';
