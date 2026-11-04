import { ApiError } from '../client';
import { otpSendError } from '../otp-error';

const t = (key: string) => key;

describe('otpSendError', () => {
  it('says when the number belongs to a patient account', () => {
    const e = new ApiError('x', 403, { message_key: 'alreadyRegisteredAsUser' });
    expect(otpSendError(e, t)).toBe('numberIsPatient');
  });

  it('names SMS failures, rate limits and no internet', () => {
    expect(otpSendError(new ApiError('x', 502, { message_key: 'otpSmsFailed' }), t)).toBe('smsFailed');
    expect(otpSendError(new ApiError('x', 429, null), t)).toBe('tooManyRequests');
    expect(otpSendError(new TypeError('Network request failed'), t)).toBe('noInternet');
    expect(otpSendError(new ApiError('x', 500, null), t)).toBe('somethingWrong');
  });
});
