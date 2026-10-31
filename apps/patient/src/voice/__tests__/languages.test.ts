/**
 * One set of everyday sentences per language. Written by a non-native speaker:
 * when a native speaker reviews a lexicon, add the phrasings they actually use.
 */
import { isAffirmative, isNegative, normalize, parseCommand } from '../parse';
import { ALL_DAYS } from '../types';

type Case = {
  lang: string;
  reminder: [sentence: string, time: string, medicine: string];
  numberWordTime: [sentence: string, time: string];
  refill: string;
  newOrder: string;
  status: string;
  yes: string;
  no: string;
};

const CASES: Case[] = [
  {
    lang: 'Telugu',
    reminder: ['ప్రతిరోజు ఉదయం 8 గంటలకు మెట్‌ఫార్మిన్ మాత్ర వేసుకోవాలని గుర్తు చేయి', '08:00', 'మెట్‌ఫార్మిన్'],
    numberWordTime: ['రాత్రి తొమ్మిది గంటలకు మందు గుర్తు చేయి', '21:00'],
    refill: 'నా మందులు మళ్ళీ తెప్పించండి',
    newOrder: 'నాకు మందులు కావాలి',
    status: 'నా ఆర్డర్ ఎక్కడ ఉంది',
    yes: 'అవును',
    no: 'వద్దు',
  },
  {
    lang: 'Bengali',
    reminder: ['প্রতিদিন সকাল ৮টায় মেটফরমিন ওষুধ খাওয়ার কথা মনে করিয়ে দাও', '08:00', 'মেটফরমিন'],
    numberWordTime: ['রাত দশটায় ওষুধের কথা মনে করিয়ে দিও', '22:00'],
    refill: 'আমার ওষুধ আবার পাঠাও',
    newOrder: 'আমার ওষুধ লাগবে',
    status: 'আমার অর্ডার কোথায়',
    yes: 'হ্যাঁ ঠিক আছে',
    no: 'না',
  },
  {
    lang: 'Marathi',
    reminder: ['रोज सकाळी 8 वाजता मेटफॉर्मिनची गोळी घ्यायची आठवण करून दे', '08:00', 'मेटफॉर्मिन'],
    numberWordTime: ['रात्री साडे नऊ वाजता औषधाची आठवण करून दे', '21:30'],
    refill: 'माझी औषधे पुन्हा मागवा',
    newOrder: 'मला औषध हवे आहे',
    status: 'माझी ऑर्डर कुठे आहे',
    yes: 'होय',
    no: 'नको',
  },
  {
    lang: 'Tamil',
    reminder: ['தினமும் காலை 8 மணிக்கு மெட்ஃபார்மின் மாத்திரை சாப்பிட நினைவூட்டு', '08:00', 'மெட்ஃபார்மின்'],
    numberWordTime: ['இரவு ஒன்பது மணிக்கு மருந்து நினைவூட்டு', '21:00'],
    refill: 'என் மருந்துகளை மீண்டும் அனுப்புங்கள்',
    newOrder: 'எனக்கு மருந்து வேண்டும்',
    status: 'என் ஆர்டர் எங்கே இருக்கு',
    yes: 'ஆமாம் சரி',
    no: 'வேண்டாம்',
  },
  {
    lang: 'Gujarati',
    reminder: ['દરરોજ સવારે 8 વાગ્યે મેટફોર્મિનની ગોળી લેવાનું યાદ કરાવો', '08:00', 'મેટફોર્મિન'],
    numberWordTime: ['રાત્રે સાડા નવ વાગ્યે દવા યાદ કરાવો', '21:30'],
    refill: 'મારી દવા ફરીથી મંગાવો',
    newOrder: 'મને દવા જોઈએ છે',
    status: 'મારો ઓર્ડર ક્યાં છે',
    yes: 'હા બરાબર',
    no: 'ના',
  },
  {
    lang: 'Kannada',
    reminder: ['ಪ್ರತಿದಿನ ಬೆಳಿಗ್ಗೆ 8 ಗಂಟೆಗೆ ಮೆಟ್‌ಫಾರ್ಮಿನ್ ಮಾತ್ರೆ ತೆಗೆದುಕೊಳ್ಳಲು ನೆನಪಿಸು', '08:00', 'ಮೆಟ್‌ಫಾರ್ಮಿನ್'],
    numberWordTime: ['ರಾತ್ರಿ ಒಂಬತ್ತು ಗಂಟೆಗೆ ಔಷಧ ನೆನಪಿಸು', '21:00'],
    refill: 'ನನ್ನ ಔಷಧಿ ಮತ್ತೆ ತರಿಸಿ',
    newOrder: 'ನನಗೆ ಔಷಧ ಬೇಕು',
    status: 'ನನ್ನ ಆರ್ಡರ್ ಎಲ್ಲಿದೆ',
    yes: 'ಹೌದು',
    no: 'ಬೇಡ',
  },
  {
    lang: 'Odia',
    reminder: ['ପ୍ରତିଦିନ ସକାଳ 8ଟାରେ ମେଟଫର୍ମିନ ଔଷଧ ଖାଇବାକୁ ମନେ ପକାଇ ଦିଅ', '08:00', 'ମେଟଫର୍ମିନ'],
    numberWordTime: ['ରାତି ନଅଟାରେ ଔଷଧ ମନେ ପକାଇ ଦିଅ', '21:00'],
    refill: 'ମୋର ଔଷଧ ପୁଣି ପଠାଅ',
    newOrder: 'ମୋତେ ଔଷଧ ଦରକାର',
    status: 'ମୋର ଅର୍ଡର କେଉଁଠି ଅଛି',
    yes: 'ହଁ ଠିକ ଅଛି',
    no: 'ନାହିଁ',
  },
  {
    lang: 'Malayalam',
    reminder: ['എല്ലാ ദിവസവും രാവിലെ 8 മണിക്ക് മെറ്റ്ഫോർമിൻ ഗുളിക കഴിക്കാൻ ഓർമ്മിപ്പിക്കുക', '08:00', 'മെറ്റ്ഫോർമിൻ'],
    numberWordTime: ['രാത്രി ഒമ്പത് മണിക്ക് മരുന്ന് ഓർമ്മിപ്പിക്കുക', '21:00'],
    refill: 'എന്റെ മരുന്ന് വീണ്ടും അയയ്ക്കൂ',
    newOrder: 'എനിക്ക് മരുന്ന് വേണം',
    status: 'എന്റെ ഓർഡർ എവിടെ',
    yes: 'ശരി',
    no: 'വേണ്ട',
  },
  {
    lang: 'Punjabi',
    reminder: ['ਰੋਜ਼ ਸਵੇਰੇ 8 ਵਜੇ ਮੈਟਫਾਰਮਿਨ ਦੀ ਗੋਲੀ ਲੈਣ ਲਈ ਯਾਦ ਕਰਾਓ', '08:00', 'ਮੈਟਫਾਰਮਿਨ'],
    numberWordTime: ['ਰਾਤ ਸਾਢੇ ਨੌਂ ਵਜੇ ਦਵਾਈ ਯਾਦ ਕਰਾਓ', '21:30'],
    refill: 'ਮੇਰੀ ਦਵਾਈ ਦੁਬਾਰਾ ਭੇਜੋ',
    newOrder: 'ਮੈਨੂੰ ਦਵਾਈ ਚਾਹੀਦੀ ਹੈ',
    status: 'ਮੇਰਾ ਆਰਡਰ ਕਿੱਥੇ ਹੈ',
    yes: 'ਹਾਂਜੀ',
    no: 'ਨਹੀਂ',
  },
];

describe.each(CASES)('$lang', (c) => {
  it('sets a reminder with time and medicine', () => {
    const [sentence, time, medicine] = c.reminder;
    expect(parseCommand(sentence)).toEqual({
      type: 'create_reminder',
      time,
      days: ALL_DAYS,
      medicine: normalize(medicine),
    });
  });

  it('reads a time given in words', () => {
    const [sentence, time] = c.numberWordTime;
    expect(parseCommand(sentence)).toMatchObject({ type: 'create_reminder', time });
  });

  it('refills', () => expect(parseCommand(c.refill)).toEqual({ type: 'refill_last' }));
  it('starts a new order', () => expect(parseCommand(c.newOrder)).toEqual({ type: 'new_order' }));
  it('asks for order status', () => expect(parseCommand(c.status)).toEqual({ type: 'order_status' }));

  it('understands yes and no', () => {
    expect(isAffirmative(c.yes)).toBe(true);
    expect(isNegative(c.yes)).toBe(false);
    expect(isNegative(c.no)).toBe(true);
    expect(isAffirmative(c.no)).toBe(false);
  });
});
