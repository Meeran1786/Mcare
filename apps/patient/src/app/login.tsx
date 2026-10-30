import { router } from 'expo-router';
import { useState } from 'react';
import { Image } from 'react-native';

import { api } from '@/api/mcare';
import { BigButton, ErrorText, Field, Screen, T } from '@/components/ui';
import { useSession } from '@/state/session';

export default function LoginScreen() {
  const { t } = useSession();
  const [phone, setPhone] = useState('');
  const [error, setError] = useState('');
  const [busy, setBusy] = useState(false);

  async function send() {
    const digits = phone.replace(/\D/g, '').slice(-10);
    if (digits.length !== 10) return setError(t('invalidPhone'));
    setError('');
    setBusy(true);
    try {
      await api.sendOtp(digits);
      router.push({ pathname: '/otp', params: { phone: digits } });
    } catch (e) {
      setError(e instanceof Error ? e.message : t('somethingWrong'));
    } finally {
      setBusy(false);
    }
  }

  return (
    <Screen>
      <Image source={require('@/assets/images/icon.png')} style={{ width: 120, height: 120, alignSelf: 'center' }} />
      <T variant="title">{t('loginTitle')}</T>
      <T>{t('loginHint')}</T>
      <Field
        label="+91"
        value={phone}
        onChangeText={setPhone}
        keyboardType="phone-pad"
        textContentType="telephoneNumber"
        autoComplete="tel"
        maxLength={14}
        autoFocus
      />
      <ErrorText>{error}</ErrorText>
      <BigButton label={t('sendCode')} icon="message-text" onPress={send} loading={busy} />
    </Screen>
  );
}
