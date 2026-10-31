import { getLanguage } from '@/i18n';

import { prepare, type Ctx } from '../actions';
import { parseCommand } from '../parse';

jest.mock('expo-localization', () => ({ getLocales: () => [{ languageCode: 'en' }] }));

function ctx(over: Partial<Ctx['api']> = {}, lang = 'en'): Ctx & { api: jest.Mocked<Ctx['api']> } {
  return {
    lang: getLanguage(lang),
    timezone: 'Asia/Kolkata',
    api: {
      reminders: jest.fn().mockResolvedValue([]),
      createReminder: jest.fn().mockResolvedValue({}),
      orders: jest.fn().mockResolvedValue([]),
      reorder: jest.fn().mockResolvedValue({ message: 'ok' }),
      ...over,
    } as any,
  };
}

const order = (id: number, status: string) => ({
  id,
  status,
  created_at: '2026-10-01T10:00:00Z',
  pharmacy: { id: 9, pharmacy_name: 'City Pharmacy' },
});

it('reads a reminder back and saves it only when run', async () => {
  const c = ctx();
  const p = await prepare(parseCommand('remind me to take metformin at 8 pm every day'), c);
  expect(p.kind).toBe('confirm');
  expect(p.say).toBe('I will remind you to take metformin at 8:00 PM, every day. Shall I save it?');
  expect(c.api.createReminder).not.toHaveBeenCalled();
  if (p.kind !== 'confirm') throw new Error();
  await p.run();
  expect(c.api.createReminder).toHaveBeenCalledWith({
    time: '20:00',
    days: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
    medicine_name: 'metformin',
    timezone: 'Asia/Kolkata',
  });
});

it('reads back in Hindi', async () => {
  const p = await prepare(parseCommand('सोमवार सुबह 8 बजे मेटफॉर्मिन की गोली याद दिलाना'), ctx({}, 'hi'));
  expect(p.say).toBe('मैं आपको सोमवार सुबह 8:00 पर मेटफॉर्मिन लेने की याद दिलाऊँगा। सेव कर दूँ?');
});

it('asks for a time when none was said', async () => {
  const p = await prepare(parseCommand('remind me to take aspirin'), ctx());
  expect(p).toMatchObject({ kind: 'askTime', medicine: 'aspirin' });
});

it('refills the last delivered order after confirmation', async () => {
  const c = ctx({ orders: jest.fn().mockResolvedValue([order(3, 'pending'), order(2, 'delivered')]) });
  const p = await prepare(parseCommand('refill my medicines'), c);
  expect(p.kind).toBe('confirm');
  expect(p.say).toContain('City Pharmacy');
  expect(c.api.reorder).not.toHaveBeenCalled();
  if (p.kind !== 'confirm') throw new Error();
  const out = await p.run();
  expect(c.api.reorder).toHaveBeenCalledWith(2);
  expect(out.navigate).toBe('/orders');
});

it('sends people with no past order to the prescription screen', async () => {
  const p = await prepare(parseCommand('refill my medicines'), ctx());
  expect(p).toMatchObject({ kind: 'say', navigate: '/upload' });
});

it('tells the status of the open order', async () => {
  const c = ctx({ orders: jest.fn().mockResolvedValue([order(3, 'out_for_delivery'), order(2, 'delivered')]) });
  const p = await prepare(parseCommand('where is my order'), c);
  expect(p.say).toBe('Your order from City Pharmacy is: On the way.');
});
