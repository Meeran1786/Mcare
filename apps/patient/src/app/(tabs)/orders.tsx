import { useState } from 'react';
import { Alert, StyleSheet, View } from 'react-native';

import { api, type Order } from '@/api/mcare';
import { BigButton, Card, Loading, Screen, T } from '@/components/ui';
import { useLoad } from '@/hooks';
import { statusLabel } from '@/i18n';
import { useSession } from '@/state/session';
import { colors, space } from '@/theme';

const DONE = ['delivered', 'completed'];

function rupees(value: string | number | null | undefined): string {
  const n = Number(value ?? 0);
  return `₹${n.toLocaleString('en-IN', { maximumFractionDigits: 2 })}`;
}

const styles = StyleSheet.create({
  quote: { gap: space.sm, padding: space.md, borderRadius: 16, backgroundColor: '#EEF3FF' },
});

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

  function answer(o: Order, approve: boolean) {
    const go = async () => {
      setBusy(o.id);
      try {
        await (approve ? api.approveQuote(o.id) : api.declineQuote(o.id));
        Alert.alert('', approve ? t('quoteApprovedMsg') : t('quoteDeclinedMsg'));
        reload();
      } catch {
        Alert.alert('', t('somethingWrong'));
      } finally {
        setBusy(null);
      }
    };
    if (approve) return go();
    Alert.alert(t('declineQuoteQ'), '', [
      { text: t('cancel'), style: 'cancel' },
      { text: t('declineQuote'), style: 'destructive', onPress: go },
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
          {o.quote_status === 'pending' ? (
            <View style={styles.quote}>
              <T variant="heading">
                {t('quotePrice', { pharmacy: o.pharmacy?.pharmacy_name ?? 'Mcare', amount: rupees(o.quote_amount) })}
              </T>
              {!!o.quote_note && <T variant="small">{o.quote_note}</T>}
              <BigButton label={t('approveQuote')} icon="check-bold" onPress={() => answer(o, true)} loading={busy === o.id} />
              <BigButton label={t('declineQuote')} icon="close" kind="secondary" onPress={() => answer(o, false)} disabled={busy === o.id} />
            </View>
          ) : o.total_amount ? (
            <T>{rupees(o.total_amount)}</T>
          ) : null}
          {DONE.includes(o.status) && (
            <BigButton label={t('reorder')} icon="refresh" onPress={() => reorder(o)} loading={busy === o.id} />
          )}
        </Card>
      ))}
    </Screen>
  );
}
