import { router } from 'expo-router';
import { Alert, Pressable, Switch, View } from 'react-native';

import { api } from '@/api/mcare';
import { BigButton, Card, Loading, Screen, T } from '@/components/ui';
import { useLoad } from '@/hooks';
import { formatDays, formatTime } from '@/i18n';
import { useSession } from '@/state/session';
import { colors, space } from '@/theme';

export default function RemindersScreen() {
  const { t, lang } = useSession();
  const { data, reload } = useLoad(api.reminders);

  function remove(id: number) {
    Alert.alert(t('deleteReminderQ'), '', [
      { text: t('cancel'), style: 'cancel' },
      { text: t('delete'), style: 'destructive', onPress: () => api.deleteReminder(id).then(reload) },
    ]);
  }

  return (
    <Screen>
      <T variant="title">{t('tabReminders')}</T>
      <BigButton label={t('addReminder')} icon="alarm-plus" onPress={() => router.push('/reminder/new')} />
      {!data && <Loading />}
      {data?.length === 0 && <T>{t('noReminders')}</T>}
      {data?.map((r) => (
        <Pressable key={r.id} onPress={() => router.push({ pathname: '/reminder/[id]', params: { id: String(r.id) } })}>
          <Card style={{ opacity: r.is_active ? 1 : 0.6 }}>
            <View style={{ flexDirection: 'row', alignItems: 'center', gap: space.md }}>
              <View style={{ flex: 1, gap: 4 }}>
                <T variant="heading">{formatTime(lang, r.raw_time.slice(0, 5))}</T>
                <T>{r.medicine_name || t('yourMedicine')}</T>
                <T variant="small">{formatDays(lang, r.days)}</T>
              </View>
              <Switch
                value={!!r.is_active}
                onValueChange={() => api.toggleReminder(r.id).then(reload)}
                trackColor={{ true: colors.primary }}
                style={{ transform: [{ scale: 1.4 }] }}
              />
            </View>
            <BigButton label={t('delete')} icon="trash-can" kind="secondary" onPress={() => remove(r.id)} />
          </Card>
        </Pressable>
      ))}
    </Screen>
  );
}
