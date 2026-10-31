import { MaterialCommunityIcons } from '@expo/vector-icons';
import { Redirect, router } from 'expo-router';
import { useState } from 'react';

import { BigButton, Card, Screen, T } from '@/components/ui';
import { useSession } from '@/state/session';
import { colors } from '@/theme';

/** Shown until an admin approves the pharmacy in the Mcare admin panel. */
export default function PendingScreen() {
  const { t, pharmacy, stage, refreshPharmacy, signOut } = useSession();
  const [busy, setBusy] = useState(false);
  if (stage === 'documents') return <Redirect href="/documents" />;

  const rejected = pharmacy?.verification_status === 'rejected';
  return (
    <Screen>
      <MaterialCommunityIcons
        name={rejected ? 'file-alert' : 'timer-sand'}
        size={96}
        color={rejected ? colors.danger : colors.primary}
        style={{ alignSelf: 'center', marginTop: 40 }}
      />
      <T variant="title" center>
        {rejected ? t('rejectedTitle') : t('pendingTitle')}
      </T>
      <Card>
        <T variant="heading">{pharmacy?.pharmacy_name}</T>
        <T>{rejected ? t('rejectedHint') : t('pendingHint')}</T>
      </Card>
      {rejected && <BigButton label={t('uploadAgain')} icon="upload" onPress={() => router.push('/documents')} />}
      <BigButton
        label={t('checkAgain')}
        icon="refresh"
        kind={rejected ? 'secondary' : 'primary'}
        loading={busy}
        onPress={async () => {
          setBusy(true);
          await refreshPharmacy();
          setBusy(false);
        }}
      />
      <BigButton label={t('help')} icon="lifebuoy" kind="secondary" onPress={() => router.push('/help')} />
      <BigButton label={t('language')} icon="translate" kind="secondary" onPress={() => router.push('/change-language')} />
      <BigButton label={t('logout')} icon="logout" kind="secondary" onPress={signOut} />
    </Screen>
  );
}
