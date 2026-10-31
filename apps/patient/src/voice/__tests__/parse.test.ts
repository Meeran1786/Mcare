import { isAffirmative, isNegative, parseCommand, parseTime, normalize } from '../parse';
import { ALL_DAYS } from '../types';

const t = (s: string) => parseTime(normalize(s));

describe('parseTime', () => {
  it.each([
    ['at 8 am', '08:00'],
    ['at 8:30 pm', '20:30'],
    ['at 8.30 p.m.', '20:30'],
    ['at 9 in the morning', '09:00'],
    ['7 o\'clock in the evening', '19:00'],
    ['at eight pm', '20:00'],
    ['at 2', '14:00'],
    ['at 12 am', '00:00'],
    ['at 21:15', '21:15'],
    ['every night', '21:00'],
    ['in the morning', '08:00'],
    ['subah 8 baje', '08:00'],
    ['raat 9 baje', '21:00'],
    ['raat 12 baje', '00:00'],
    ['shaam saadhe 6 baje', '18:30'],
    ['सुबह 8 बजे', '08:00'],
    ['सुबह आठ बजे', '08:00'],
    ['रात को ९ बजे', '21:00'],
    ['दोपहर दो बजे', '14:00'],
    ['सवा 9 बजे', '09:15'],
    ['पौने 8 बजे', '07:45'],
    ['8 बजकर 30 मिनट', '08:30'],
    ['صبح 8 بجے', '08:00'],
    ['رات ۹ بجے', '21:00'],
  ])('%s -> %s', (input, expected) => {
    expect(t(input)).toBe(expected);
  });

  it('ignores dose numbers', () => {
    expect(t('metformin 500 mg')).toBeUndefined();
    expect(t('take 2 tablets')).toBeUndefined();
  });
});

describe('parseCommand: reminders', () => {
  it('English, full sentence', () => {
    expect(parseCommand('Remind me to take Metformin at 8 am every day')).toEqual({
      type: 'create_reminder',
      time: '08:00',
      days: ALL_DAYS,
      medicine: 'metformin',
    });
  });

  it('English, specific days and dose', () => {
    expect(parseCommand('Remind me to take vitamin D 1000 at 9 pm on Monday and Thursday')).toEqual({
      type: 'create_reminder',
      time: '21:00',
      days: ['Mon', 'Thu'],
      medicine: 'vitamin d 1000',
    });
  });

  it('English without the word remind', () => {
    expect(parseCommand('I take my BP tablet at 7 in the morning')).toMatchObject({
      type: 'create_reminder',
      time: '07:00',
      medicine: 'bp',
    });
  });

  it('English, no medicine named', () => {
    expect(parseCommand('set an alarm for my medicine at 10 pm')).toEqual({
      type: 'create_reminder',
      time: '22:00',
      days: ALL_DAYS,
      medicine: undefined,
    });
  });

  it('Hinglish', () => {
    expect(parseCommand('mujhe roz subah 8 baje metformin ki goli yaad dilana')).toEqual({
      type: 'create_reminder',
      time: '08:00',
      days: ALL_DAYS,
      medicine: 'metformin',
    });
  });

  it('Hindi in Devanagari', () => {
    expect(parseCommand('मुझे रोज़ रात 9 बजे मेटफॉर्मिन की दवा याद दिलाना')).toEqual({
      type: 'create_reminder',
      time: '21:00',
      days: ALL_DAYS,
      medicine: 'मेटफॉर्मिन',
    });
  });

  it('Hindi with take-verb', () => {
    expect(parseCommand('सोमवार को सुबह आठ बजे थायरॉइड लेने की याद दिलाओ')).toEqual({
      type: 'create_reminder',
      time: '08:00',
      days: ['Mon'],
      medicine: 'थायरॉइड',
    });
  });

  it('Urdu', () => {
    expect(parseCommand('مجھے روزانہ صبح 8 بجے میٹفارمن کی گولی یاد دلانا')).toEqual({
      type: 'create_reminder',
      time: '08:00',
      days: ALL_DAYS,
      medicine: 'میٹفارمن',
    });
  });

  it('reminder with no time keeps time empty so the app can ask', () => {
    expect(parseCommand('remind me to take aspirin')).toEqual({
      type: 'create_reminder',
      time: undefined,
      days: ALL_DAYS,
      medicine: 'aspirin',
    });
  });
});

describe('parseCommand: orders', () => {
  it.each([
    'Refill my medicines',
    'order my last medicines again',
    'reorder please',
    'pichla order dobara mangwa do',
    'मेरी दवाई दोबारा मंगवा दो',
    'पिछला ऑर्डर फिर से भेजो',
    'میری دوائی دوبارہ منگوا دو',
  ])('refill: %s', (s) => {
    expect(parseCommand(s)).toEqual({ type: 'refill_last' });
  });

  it.each([
    'I want to order medicines',
    'I need to upload a prescription',
    'dawai mangwani hai',
    'मुझे दवाई चाहिए',
    'دوائی منگوانی ہے',
  ])('new order: %s', (s) => {
    expect(parseCommand(s)).toEqual({ type: 'new_order' });
  });

  it.each(['Where is my order', 'order status', 'mera order kab aayega', 'मेरा ऑर्डर कहाँ है', 'میرا آرڈر کہاں ہے'])(
    'status: %s',
    (s) => {
      expect(parseCommand(s)).toEqual({ type: 'order_status' });
    },
  );
});

describe('parseCommand: other', () => {
  it.each(['What are my reminders', 'which medicines today', 'aaj ki dawai batao', 'आज कौन सी दवा है', 'آج کون سی دوا ہے'])(
    'list: %s',
    (s) => {
      expect(parseCommand(s)).toEqual({ type: 'list_reminders' });
    },
  );

  it('help', () => {
    expect(parseCommand('help')).toEqual({ type: 'help' });
    expect(parseCommand('मदद')).toEqual({ type: 'help' });
  });

  it('unknown', () => {
    expect(parseCommand('what is the weather')).toEqual({ type: 'unknown', text: 'what is the weather' });
  });
});

describe('confirmation', () => {
  it.each(['yes', 'Yes please', 'okay', 'haan', 'haan ji', 'हाँ', 'जी हां', 'ठीक है', 'ہاں', 'جی ہاں'])('yes: %s', (s) => {
    expect(isAffirmative(s)).toBe(true);
    expect(isNegative(s)).toBe(false);
  });

  it.each(['no', 'cancel', 'nahi', 'नहीं', 'गलत है', 'نہیں', 'not ok'])('no: %s', (s) => {
    expect(isNegative(s)).toBe(true);
    expect(isAffirmative(s)).toBe(false);
  });
});
