import { router } from 'expo-router';
import { StyleSheet, View } from 'react-native';

import { api } from '@/api/partner';
import { BigButton, Card, ErrorText, Loading, Screen, T } from '@/components/ui';
import { useLoad } from '@/hooks';
import { formatDate } from '@/i18n';
import { money } from '@/logic/orders';
import { useSession } from '@/state/session';
import { colors, space } from '@/theme';

type Row = { label: string; total: number; revenue: number | string };

function Bars({ rows, label }: { rows: Row[]; label: (r: Row) => string }) {
  const { t } = useSession();
  const max = Math.max(1, ...rows.map((r) => Number(r.revenue)));
  return (
    <View style={{ gap: space.sm }}>
      {rows.map((r) => (
        <View key={r.label} style={{ gap: 2 }}>
          <View style={styles.between}>
            <T variant="small">{label(r)}</T>
            <T variant="small">{`${money(r.revenue)} · ${t('orders', { n: r.total })}`}</T>
          </View>
          <View style={styles.track}>
            <View style={[styles.bar, { width: `${(Number(r.revenue) / max) * 100}%` }]} />
          </View>
        </View>
      ))}
    </View>
  );
}

/** Earnings count orders the patient has confirmed as received ("completed"). */
export default function EarningsScreen() {
  const { t, lang } = useSession();
  const { data, error, reload } = useLoad(api.analytics);

  return (
    <Screen>
      <T variant="title">{t('tabEarnings')}</T>
      {error && (
        <>
          <ErrorText>{t('somethingWrong')}</ErrorText>
          <BigButton label={t('retry')} icon="refresh" kind="secondary" onPress={reload} />
        </>
      )}
      {!data && !error && <Loading />}
      {data && (
        <>
          <View style={styles.tiles}>
            <Card style={styles.tile}>
              <T variant="small">{t('totalEarnings')}</T>
              <T variant="title" color={colors.primaryDark}>
                {money(data.totals.total_earnings)}
              </T>
            </Card>
            <Card style={styles.tile}>
              <T variant="small">{t('completedDeliveries')}</T>
              <T variant="title" color={colors.primaryDark}>
                {String(data.totals.completed_deliveries)}
              </T>
            </Card>
          </View>
          <BigButton label={t('exportTitle')} icon="file-export" kind="secondary" onPress={() => router.push('/export')} />
          {data.totals.completed_deliveries === 0 ? (
            <T color={colors.muted}>{t('noEarnings')}</T>
          ) : (
            <>
              <T variant="heading">{t('last7Days')}</T>
              <Bars rows={data.daily} label={(r) => formatDate(lang, r.label)} />
              <T variant="heading">{t('byMonth')}</T>
              <Bars
                rows={data.monthly}
                label={(r) => new Date(`${r.label}-01T00:00:00`).toLocaleDateString(lang.locale, { month: 'long', year: 'numeric' })}
              />
            </>
          )}
        </>
      )}
    </Screen>
  );
}

const styles = StyleSheet.create({
  tiles: { flexDirection: 'row', gap: space.sm },
  tile: { flex: 1 },
  between: { flexDirection: 'row', justifyContent: 'space-between', gap: space.sm },
  track: { height: 12, borderRadius: 6, backgroundColor: colors.border, overflow: 'hidden' },
  bar: { height: 12, borderRadius: 6, backgroundColor: colors.primary },
});
