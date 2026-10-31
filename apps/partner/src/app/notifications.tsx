import { api } from '@/api/partner';
import { Card, Loading, Screen, T } from '@/components/ui';
import { useLoad } from '@/hooks';
import { formatDateTime } from '@/i18n';
import { useSession } from '@/state/session';
import { colors } from '@/theme';

export default function NotificationsScreen() {
  const { t, lang } = useSession();
  const { data } = useLoad(api.notifications);
  return (
    <Screen>
      {!data && <Loading />}
      {data?.length === 0 && <T color={colors.muted}>{t('noNotifications')}</T>}
      {data?.map((n) => (
        <Card key={n.id} style={{ opacity: n.is_read ? 0.7 : 1 }}>
          <T variant="heading">{n.title}</T>
          <T>{n.body}</T>
          <T variant="small">{formatDateTime(lang, n.created_at)}</T>
        </Card>
      ))}
    </Screen>
  );
}
