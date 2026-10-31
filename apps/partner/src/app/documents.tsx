import { router } from 'expo-router';
import { useState } from 'react';

import { apiErrorText } from '@/api/client';
import { api } from '@/api/partner';
import { PhotoSlot } from '@/components/bits';
import { BigButton, ErrorText, Screen, T } from '@/components/ui';
import { useSession } from '@/state/session';

export default function DocumentsScreen() {
  const { t, refreshPharmacy } = useSession();
  const [license, setLicense] = useState<string | null>(null);
  const [gst, setGst] = useState<string | null>(null);
  const [error, setError] = useState('');
  const [busy, setBusy] = useState(false);

  async function submit() {
    if (!license) return setError(t('licenseRequired'));
    setBusy(true);
    setError('');
    try {
      await api.uploadDocuments(license, gst ?? undefined);
      await refreshPharmacy();
      if (router.canGoBack()) router.back();
    } catch (e) {
      setError(apiErrorText(e, t('somethingWrong')));
    } finally {
      setBusy(false);
    }
  }

  return (
    <Screen>
      <T>{t('documentsHint')}</T>
      <PhotoSlot label={t('licenseFile')} uri={license} onPicked={setLicense} />
      <PhotoSlot label={t('gstFile')} uri={gst} onPicked={setGst} />
      <ErrorText>{error}</ErrorText>
      <BigButton label={t('submitDocuments')} icon="send" onPress={submit} loading={busy} />
    </Screen>
  );
}
