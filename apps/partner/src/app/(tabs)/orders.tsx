import { router } from 'expo-router';
import { Pressable, View } from 'react-native';

import { api } from '@/api/partner';
import { StatusPill } from '@/components/bits';
import { BigButton, Card, ErrorText, Loading, Screen, T } from '@/components/ui';
import { useLoad } from '@/hooks';
import { formatDateTime } from '@/i18n';
import { money } from '@/logic/orders';
import { useSession } from '@/state/session';
import { colors, space } from '@/theme';

export default function OrdersScreen() {
  const { t, lang } = useSession();
  const { data, error, reload } = useLoad(() => api.orders());

  return (
    <Screen>
      <T variant="title">{t('activeOrders')}</T>
      {error && (
        <>
          <ErrorText>{t('somethingWrong')}</ErrorText>
          <BigButton label={t('retry')} icon="refresh" kind="secondary" onPress={reload} />
        </>
      )}
      {!data && !error && <Loading />}
      {data?.length === 0 && <T color={colors.muted}>{t('noOrders')}</T>}
      {data?.map((o) => (
        <Pressable
          key={o.id}
          accessibilityRole="button"
          onPress={() => router.push({ pathname: '/order/[id]', params: { id: String(o.id) } })}>
          <Card>
            <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', gap: space.sm }}>
              <T variant="heading">{t('orderNo', { id: o.id })}</T>
              <StatusPill status={o.status} />
            </View>
            <T>{o.user?.name ?? t('patient')}</T>
            <T variant="small">{formatDateTime(lang, o.created_at)}</T>
            {o.total_amount != null && Number(o.total_amount) > 0 && <T color={colors.primaryDark}>{money(o.total_amount)}</T>}
          </Card>
        </Pressable>
      ))}
      <BigButton label={t('history')} icon="history" kind="secondary" onPress={() => router.push('/history')} />
    </Screen>
  );
}
