import { router } from 'expo-router';
import { useState } from 'react';

import { api } from '@/api/mcare';
import { BigButton, ErrorText, Field, Screen, T } from '@/components/ui';
import { useSession } from '@/state/session';

export default function NameScreen() {
  const { t, setUser } = useSession();
  const [name, setName] = useState('');
  const [error, setError] = useState('');
  const [busy, setBusy] = useState(false);

  async function save() {
    if (name.trim().length < 2) return;
    setBusy(true);
    try {
      const res = await api.completeProfile(name.trim());
      setUser(res.user);
      router.replace('/home');
    } catch {
      setError(t('somethingWrong'));
      setBusy(false);
    }
  }

  return (
    <Screen>
      <T variant="title">{t('nameTitle')}</T>
      <Field label="" value={name} onChangeText={setName} autoFocus autoComplete="name" textContentType="name" />
      <ErrorText>{error}</ErrorText>
      <BigButton label={t('save')} icon="check-bold" onPress={save} loading={busy} disabled={name.trim().length < 2} />
    </Screen>
  );
}
