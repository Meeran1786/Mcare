import { Redirect } from 'expo-router';

import { BigButton, Loading, Screen, T } from '@/components/ui';
import { useSession } from '@/state/session';

/** Sends people to the right screen for where they are in signing up. */
export default function Index() {
  const { token, languageChosen, stage, t, refreshPharmacy, signOut } = useSession();
  if (!languageChosen) return <Redirect href="/language" />;
  if (!token) return <Redirect href="/login" />;
  if (stage === 'register') return <Redirect href="/register" />;
  if (stage === 'documents') return <Redirect href="/documents" />;
  if (stage === 'pending') return <Redirect href="/pending" />;
  if (stage === 'ready') return <Redirect href="/requests" />;
  if (stage === 'error')
    return (
      <Screen>
        <T variant="heading">{t('somethingWrong')}</T>
        <BigButton label={t('retry')} icon="refresh" onPress={refreshPharmacy} />
        <BigButton label={t('logout')} kind="secondary" onPress={signOut} />
      </Screen>
    );
  return (
    <Screen>
      <Loading />
    </Screen>
  );
}
