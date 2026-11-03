import { router } from 'expo-router';

import { api } from '@/api/mcare';
import { BigButton, Card, Screen, T } from '@/components/ui';
import { useLoad } from '@/hooks';
import { planLine } from '@/plan';
import { useSession } from '@/state/session';

export default function ProfileScreen() {
  const { t, user, lang, signOut } = useSession();
  const { data: plan } = useLoad(api.plan);
  return (
    <Screen>
      <T variant="title">{t('tabProfile')}</T>
      <Card>
        <T variant="heading">{user?.name ?? ''}</T>
        <T>+91 {user?.mobile_number ?? ''}</T>
        {plan ? <T variant="small">{planLine(t, plan, lang.speech)}</T> : null}
      </Card>
      <BigButton label={`${t('language')}: ${lang.name}`} icon="translate" kind="secondary" onPress={() => router.push('/change-language')} />
      <BigButton label={t('family')} icon="account-heart" kind="secondary" onPress={() => router.push('/family')} />
      <BigButton label={t('addresses')} icon="map-marker" kind="secondary" onPress={() => router.push('/addresses')} />
      <BigButton label={t('notifications')} icon="bell" kind="secondary" onPress={() => router.push('/notifications')} />
      <BigButton label={t('help')} icon="help-circle" kind="secondary" onPress={() => router.push('/help')} />
      <BigButton label={t('logout')} icon="logout" kind="danger" onPress={() => signOut().then(() => router.replace('/login'))} />
    </Screen>
  );
}
