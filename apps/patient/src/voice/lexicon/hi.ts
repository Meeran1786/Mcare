// Hindi: Devanagari plus romanised "Hinglish". NEEDS NATIVE-SPEAKER REVIEW.
import type { Lexicon } from './types';

const hi: Lexicon = {
  remind: ['yaad', 'yad', 'याद', 'रिमाइंडर', 'रिमाइन्डर', 'अलार्म'],
  refill: ['dobara', 'dubara', 'phir se', 'fir se', 'pichla', 'pichhla', 'pichli', 'रिफिल', 'रीफिल', 'दोबारा', 'दुबारा', 'फिर से', 'पिछला', 'पिछली', 'वही दवा*'],
  order: ['mangao', 'mangwa*', 'manga*', 'chahiye', 'ऑर्डर', 'आर्डर', 'मंगवा*', 'मंगा*', 'चाहिए', 'खरीद*', 'parcha', 'पर्चा', 'पर्ची', 'भेज*', 'bhej*'],
  orderNoun: ['ऑर्डर', 'आर्डर', 'डिलीवरी', 'पार्सल'],
  status: ['kahan', 'kaha', 'kab', 'कहां', 'कहा', 'कब', 'स्टेटस', 'पहुंच*', 'aayega', 'आएगा', 'आयेगा'],
  question: ['kya', 'kaun', 'kaunsi', 'konsi', 'bata*', 'क्या', 'कौन', 'कौनसी', 'बता*', 'सुना*'],
  medicine: ['dawa', 'dawai', 'davai', 'goli', 'goliyan', 'दवा', 'दवाई', 'दवाइयां', 'दवाएं', 'गोली', 'गोलियां', 'टैबलेट', 'टेबलेट', 'कैप्सूल', 'इंसुलिन'],
  reminderNoun: ['रिमाइंडर', 'अलार्म'],
  today: ['aaj', 'आज'],
  help: ['madad', 'मदद', 'sahayata', 'सहायता', 'kya kar sakte', 'क्या कर सकते'],
  yes: ['haan', 'han', 'ha', 'haa', 'ji', 'theek', 'thik', 'sahi', 'bilkul', 'हां', 'हा', 'जी', 'ठीक', 'सही', 'ओके', 'बिलकुल', 'बिल्कुल', 'करो', 'कर दो'],
  no: ['nahi', 'nahin', 'mat', 'galat', 'नहीं', 'नही', 'ना', 'मत', 'गलत', 'रद्द', 'कैंसल', 'रुको'],
  numbers: [
    ['ek', 'एक'], ['do', 'दो'], ['teen', 'तीन'], ['char', 'chaar', 'चार'], ['paanch', 'panch', 'पांच', 'पाच'],
    ['chhe', 'che', 'chhah', 'छह', 'छः', 'छे'], ['saat', 'सात'], ['aath', 'आठ'], ['nau', 'नौ'], ['das', 'दस'],
    ['gyarah', 'gyara', 'ग्यारह'], ['barah', 'bara', 'बारह'],
  ],
  timeMarkers: ['baje', 'bje', 'बजे', 'बजकर', 'bajkar'],
  halfPast: ['saadhe', 'sadhe', 'साढ़े'],
  quarterPast: ['sawa', 'सवा'],
  quarterTo: ['paune', 'पौने'],
  periods: {
    morning: ['subah', 'subha', 'savere', 'sawere', 'सुबह', 'सवेरे', 'नाश्ते*'],
    afternoon: ['dopahar', 'dopehar', 'दोपहर', 'लंच'],
    evening: ['shaam', 'sham', 'शाम'],
    night: ['raat', 'rat', 'रात', 'sone', 'सोने', 'खाने के बाद रात'],
  },
  days: {
    Sun: ['ravivar', 'itwar', 'itvaar', 'रविवार', 'इतवार'],
    Mon: ['somvar', 'somwar', 'सोमवार'],
    Tue: ['mangalvar', 'mangalwar', 'मंगलवार'],
    Wed: ['budhvar', 'budhwar', 'बुधवार'],
    Thu: ['guruvar', 'guruwar', 'brihaspativar', 'गुरुवार', 'बृहस्पतिवार', 'वीरवार'],
    Fri: ['shukravar', 'shukrawar', 'शुक्रवार'],
    Sat: ['shanivar', 'shaniwar', 'शनिवार'],
  },
  filler: ['roz', 'rozana', 'roj', 'har', 'din', 'mujhe', 'meri', 'mera', 'apni', 'apna', 'ko', 'par', 'pe',
    'रोज', 'रोजाना', 'हर', 'दिन', 'मुझे', 'मेरी', 'मेरा', 'अपनी', 'अपना', 'को', 'पर', 'पे', 'की', 'का', 'के'],
  possessive: ['ki', 'ka', 'ke', 'की', 'का', 'के'],
  takeAfter: ['lene', 'lena', 'khane', 'khana', 'लेने', 'लेना', 'खाने', 'खाना', 'लगाने'],
};

export default hi;
