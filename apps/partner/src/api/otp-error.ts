import { ApiError } from '@/api/client';

type Key = 'tooManyRequests' | 'numberIsPatient' | 'smsFailed' | 'noInternet' | 'somethingWrong';

/** The reason a code could not be sent, in the person's language, instead of a generic error. */
export function otpSendError(e: unknown, t: (key: Key) => string): string {
  if (!(e instanceof ApiError)) return t('noInternet');
  if (e.status === 429) return t('tooManyRequests');
  const messageKey = (e.body as { message_key?: string } | null)?.message_key;
  if (messageKey === 'alreadyRegisteredAsUser') return t('numberIsPatient');
  if (messageKey === 'otpSmsFailed' || messageKey === 'otpSmsNotConfigured') return t('smsFailed');
  return t('somethingWrong');
}
