import type { Lexicon } from './types';

const en: Lexicon = {
  remind: ['remind*', 'alarm*', 'alert*'],
  refill: ['refill*', 'again', 're-order', 'reorder*', 're order', 'order again', 'same medicine*', 'same order', 'last order', 'previous order', 'same as last'],
  order: ['order*', 'buy', 'purchase', 'deliver*', 'send me', 'prescription*', 'need medicine*', 'need my medicine*'],
  orderNoun: ['order*', 'delivery', 'parcel'],
  status: ['where', 'status', 'track*', 'when will', 'arriv*', 'come'],
  question: ['what', 'which', 'list', 'tell me', 'show', 'read'],
  medicine: ['medicine*', 'medication*', 'tablet*', 'pill*', 'dose*', 'capsule*', 'syrup*', 'insulin', 'drops'],
  reminderNoun: ['reminder*', 'alarm*'],
  today: ['today*', 'tonight'],
  help: ['help', 'what can you do'],
  yes: ['yes', 'yeah', 'yep', 'yup', 'ok', 'okay', 'confirm*', 'sure', 'correct', 'right', 'go ahead', 'do it'],
  no: ['no', 'nope', 'cancel*', 'stop', 'wrong', "don't", 'dont', 'not'],
  numbers: [['one'], ['two'], ['three'], ['four'], ['five'], ['six'], ['seven'], ['eight'], ['nine'], ['ten'], ['eleven'], ['twelve']],
  timeMarkers: ['am', 'pm', 'oclock'],
  periods: {
    morning: ['morning', 'breakfast'],
    afternoon: ['afternoon', 'noon', 'lunch'],
    evening: ['evening'],
    night: ['night', 'tonight', 'bedtime', 'dinner'],
  },
  days: {
    Sun: ['sunday*'], Mon: ['monday*'], Tue: ['tuesday*'], Wed: ['wednesday*'],
    Thu: ['thursday*'], Fri: ['friday*'], Sat: ['saturday*'],
  },
  weekdays: ['weekday*', 'working day*'],
  weekends: ['weekend*'],
  filler: ['my', 'the', 'a', 'an', 'me', 'every', 'daily', 'everyday', 'each', 'day', 'please', 'at', 'on', 'in', 'to', 'set', 'and'],
  takeBefore: ['take', 'taking', 'for', 'about'],
};

export default en;
