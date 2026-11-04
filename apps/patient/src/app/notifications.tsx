import { MaterialCommunityIcons } from '@expo/vector-icons';
import { useState } from 'react';
import { Alert, Pressable, View } from 'react-native';

import { api } from '@/api/mcare';
import { BigButton, Card, Loading, Screen, T } from '@/components/ui';
import { useLoad } from '@/hooks';
import { useSession } from '@/state/session';
import { colors, space } from '@/theme';

/** Notifications with a remove button on each, and "Clear all". */
export default function NotificationsScreen() {
  const { t, lang } = useSession();
  const { data, reload } = useLoad(api.notifications);
  const [gone, setGone] = useState<number[]>([]);
  const list = data?.filter((n) => !gone.includes(n.id));

  const remove = (id: number) => {
    setGone((g) => [...g, id]);
    api.clearNotification(id).catch(() => {
      setGone((g) => g.filter((x) => x !== id));
      Alert.alert(t('somethingWrong'));
    });
  };

  const clearAll = () =>
    Alert.alert('', t('clearAllQ'), [
      { text: t('cancel'), style: 'cancel' },
      {
        text: t('clearAll'),
        style: 'destructive',
        onPress: () =>
          api
            .clearAllNotifications()
            .then(() => {
              setGone([]);
              reload();
            })
            .catch(() => Alert.alert(t('somethingWrong'))),
      },
    ]);

  return (
    <Screen>
      {!data && <Loading />}
      {list?.length === 0 && <T color={colors.muted}>{t('noNotifications')}</T>}
      {!!list?.length && <BigButton label={t('clearAll')} icon="notification-clear-all" kind="secondary" onPress={clearAll} />}
      {list?.map((n) => (
        <Card key={n.id} style={{ opacity: n.is_read ? 0.7 : 1 }}>
          <View style={{ flexDirection: 'row', alignItems: 'flex-start', gap: space.sm }}>
            <View style={{ flex: 1 }}>
              <T variant="heading">{n.title}</T>
            </View>
            <Pressable
              accessibilityRole="button"
              accessibilityLabel={t('removeNotification')}
              onPress={() => remove(n.id)}
              hitSlop={12}
              style={{ padding: 4 }}>
              <MaterialCommunityIcons name="close" size={28} color={colors.muted} />
            </Pressable>
          </View>
          <T>{n.body}</T>
          <T variant="small">{new Date(n.created_at).toLocaleString(lang.speech)}</T>
        </Card>
      ))}
    </Screen>
  );
}
