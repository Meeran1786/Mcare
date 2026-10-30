import { useState } from 'react';
import { Alert } from 'react-native';

import { api, type Order } from '@/api/mcare';
import { BigButton, Card, Loading, Screen, T } from '@/components/ui';
import { useLoad } from '@/hooks';
import { statusLabel } from '@/i18n';
import { useSession } from '@/state/session';
import { colors } from '@/theme';

const DONE = ['delivered', 'completed'];

export default function OrdersScreen() {
  const { t, lang } = useSession();
  const { data, reload } = useLoad(() => api.orders());
  const [busy, setBusy] = useState<number | null>(null);

  function reorder(o: Order) {
    const pharmacy = o.pharmacy?.pharmacy_name ?? 'Mcare';
    const date = new Date(o.created_at).toLocaleDateString(lang.speech, { day: 'numeric', month: 'long' });
    Alert.alert(t('reorder'), t('rbRefill', { pharmacy, date }), [
      { text: t('cancel'), style: 'cancel' },
      {
        text: t('confirm'),
        onPress: async () => {
          setBusy(o.id);
          try {
            await api.reorder(o.id);
            Alert.alert('', t('refillDone', { pharmacy }));
            reload();
          } catch (e) {
            Alert.alert('', e instanceof Error ? e.message : t('somethingWrong'));
          } finally {
            setBusy(null);
          }
        },
      },
    ]);
  }

  return (
    <Screen>
      <T variant="title">{t('myOrders')}</T>
      {!data && <Loading />}
      {data?.length === 0 && <T>{t('noOrders')}</T>}
      {data?.map((o) => (
        <Card key={o.id}>
          <T variant="heading">{t('orderFrom', { pharmacy: o.pharmacy?.pharmacy_name ?? 'Mcare' })}</T>
          <T color={DONE.includes(o.status) ? colors.success : colors.primaryDark}>{statusLabel(lang, o.status)}</T>
          <T variant="small">{new Date(o.created_at).toLocaleDateString(lang.speech)}</T>
          {o.total_amount ? <T>₹ {o.total_amount}</T> : null}
          {DONE.includes(o.status) && (
            <BigButton label={t('reorder')} icon="refresh" onPress={() => reorder(o)} loading={busy === o.id} />
          )}
        </Card>
      ))}
    </Screen>
  );
}
