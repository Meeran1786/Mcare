import { router } from 'expo-router';
import { useState } from 'react';

import { apiErrorText } from '@/api/client';
import { api, type PickedFile } from '@/api/partner';
import { DocumentSlot } from '@/components/bits';
import { BigButton, ErrorText, Screen, T } from '@/components/ui';
import { useSession } from '@/state/session';

/** The server takes up to 5 pages per document. */
const MAX_PAGES = 5;

export default function DocumentsScreen() {
  const { t, refreshPharmacy } = useSession();
  const [license, setLicense] = useState<PickedFile[]>([]);
  const [gst, setGst] = useState<PickedFile[]>([]);
  const [error, setError] = useState('');
  const [busy, setBusy] = useState(false);

  async function submit() {
    if (!license.length) return setError(t('licenseRequired'));
    setBusy(true);
    setError('');
    try {
      await api.uploadDocuments(license, gst);
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
      <DocumentSlot label={t('licenseFile')} files={license} max={MAX_PAGES} onChange={setLicense} />
      <DocumentSlot label={t('gstFile')} files={gst} max={MAX_PAGES} onChange={setGst} />
      <ErrorText>{error}</ErrorText>
      <BigButton label={t('submitDocuments')} icon="send" onPress={submit} loading={busy} />
    </Screen>
  );
}
