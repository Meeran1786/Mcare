import { router, useLocalSearchParams } from 'expo-router';
import { useState } from 'react';

import { ApiError } from '@/api/client';
import { api } from '@/api/mcare';
import { BigButton, ErrorText, Field, Screen, T } from '@/components/ui';
import { registerForPush } from '@/push';
import { useSession } from '@/state/session';

export default function OtpScreen() {
  const { phone } = useLocalSearchParams<{ phone: string }>();
  const { t, signIn } = useSession();
  const [code, setCode] = useState('');
  const [error, setError] = useState('');
  const [busy, setBusy] = useState(false);

  async function verify(value = code) {
    if (!/^\d{4}$/.test(value)) return;
    setError('');
    setBusy(true);
    try {
      const res = await api.verifyOtp(phone, value);
      await signIn(res.token, res.user);
      registerForPush();
      router.replace(res.user.name ? '/home' : '/name');
    } catch (e) {
      // 429: too many wrong codes, so the server has thrown this code away.
      setError(e instanceof ApiError && e.status === 429 ? t('tooManyOtp') : t('invalidOtp'));
      setCode('');
      setBusy(false);
    }
  }

  return (
    <Screen>
      <T variant="title">{t('otpTitle')}</T>
      <T>{t('otpHint', { phone: `+91 ${phone}` })}</T>
      <Field
        label=""
        value={code}
        onChangeText={(v) => {
          const digits = v.replace(/\D/g, '').slice(0, 4);
          setCode(digits);
          if (digits.length === 4) verify(digits);
        }}
        keyboardType="number-pad"
        textContentType="oneTimeCode"
        autoComplete="sms-otp"
        maxLength={4}
        autoFocus
        style={{ fontSize: 36, letterSpacing: 16, textAlign: 'center' }}
      />
      <ErrorText>{error}</ErrorText>
      <BigButton label={t('verify')} icon="check-bold" onPress={() => verify()} loading={busy} disabled={code.length !== 4} />
      <BigButton
        label={t('resend')}
        kind="secondary"
        onPress={() =>
          api.sendOtp(phone).then(
            () => setError(''),
            (e) => setError(e instanceof ApiError && e.status === 429 ? t('tooManyRequests') : t('somethingWrong')),
          )
        }
      />
    </Screen>
  );
}
