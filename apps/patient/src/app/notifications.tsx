import { api } from '@/api/mcare';
import { Card, Loading, Screen, T } from '@/components/ui';
import { useLoad } from '@/hooks';
import { useSession } from '@/state/session';

export default function NotificationsScreen() {
  const { lang } = useSession();
  const { data } = useLoad(api.notifications);
  return (
    <Screen>
      {!data && <Loading />}
      {data?.map((n) => (
        <Card key={n.id} style={{ opacity: n.is_read ? 0.7 : 1 }}>
          <T variant="heading">{n.title}</T>
          <T>{n.body}</T>
          <T variant="small">{new Date(n.created_at).toLocaleString(lang.speech)}</T>
        </Card>
      ))}
    </Screen>
  );
}
