import { router } from 'expo-router';

import { BigButton, Card, Screen, T } from '@/components/ui';
import { useSession } from '@/state/session';

export default function ProfileScreen() {
  const { t, user, lang, signOut } = useSession();
  return (
    <Screen>
      <T variant="title">{t('tabProfile')}</T>
      <Card>
        <T variant="heading">{user?.name ?? ''}</T>
        <T>+91 {user?.mobile_number ?? ''}</T>
      </Card>
      <BigButton label={`${t('language')}: ${lang.name}`} icon="translate" kind="secondary" onPress={() => router.push('/change-language')} />
      <BigButton label={t('notifications')} icon="bell" kind="secondary" onPress={() => router.push('/notifications')} />
      <BigButton label={t('logout')} icon="logout" kind="danger" onPress={() => signOut().then(() => router.replace('/login'))} />
    </Screen>
  );
}
