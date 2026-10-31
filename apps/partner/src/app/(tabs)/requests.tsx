import { useFocusEffect, router } from 'expo-router';
import { useCallback, useState } from 'react';
import { Image, Pressable, StyleSheet, View } from 'react-native';

import { api } from '@/api/partner';
import { OnlineSwitch } from '@/components/bits';
import { BigButton, Card, ErrorText, Loading, Screen, T } from '@/components/ui';
import { useLoad } from '@/hooks';
import { formatDateTime } from '@/i18n';
import { addressLine, deliveryAddress } from '@/logic/orders';
import { useSession } from '@/state/session';
import { colors, space } from '@/theme';

/** New prescriptions sent to this pharmacy. Refreshes every 30 seconds while open. */
export default function RequestsScreen() {
  const { t, lang, pharmacy, setPharmacy } = useSession();
  const { data, error, reload } = useLoad(api.inbox);
  const [switching, setSwitching] = useState(false);
  const [switchError, setSwitchError] = useState('');

  useFocusEffect(
    useCallback(() => {
      const timer = setInterval(reload, 30_000);
      return () => clearInterval(timer);
      // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []),
  );

  async function toggle(online: boolean) {
    if (!pharmacy) return;
    setSwitching(true);
    setSwitchError('');
    try {
      const now = await api.setOnline(online);
      setPharmacy({ ...pharmacy, is_delivery_available: now });
      reload();
    } catch {
      setSwitchError(t('somethingWrong'));
    } finally {
      setSwitching(false);
    }
  }

  const fresh = data?.filter((a) => a.status === 'sent') ?? [];

  return (
    <Screen>
      <T variant="title">{pharmacy?.pharmacy_name}</T>
      <OnlineSwitch online={!!pharmacy?.is_delivery_available} busy={switching} onChange={toggle} />
      <ErrorText>{switchError}</ErrorText>

      <View style={styles.header}>
        <T variant="heading">{t('newRequests')}</T>
        <BigButton label={t('refresh')} icon="refresh" kind="secondary" onPress={reload} style={styles.refresh} />
      </View>
      {error && <ErrorText>{t('somethingWrong')}</ErrorText>}
      {!data && !error && <Loading />}
      {data && fresh.length === 0 && <T color={colors.muted}>{t('noRequests')}</T>}
      {fresh.map((a) => {
        const p = a.prescription;
        const image = p.files?.[0]?.url;
        return (
          <Pressable
            key={a.id}
            accessibilityRole="button"
            onPress={() => router.push({ pathname: '/prescription/[id]', params: { id: String(a.id) } })}>
            <Card style={styles.card}>
              {image ? <Image source={{ uri: image }} style={styles.thumb} /> : null}
              <View style={{ flex: 1, gap: 2 }}>
                <T variant="heading">{p.user?.name ?? t('patient')}</T>
                {a.distance_km != null && <T color={colors.primaryDark}>{t('kmAway', { km: a.distance_km })}</T>}
                <T variant="small">{addressLine(deliveryAddress(p))}</T>
                <T variant="small">{t('receivedAt', { time: formatDateTime(lang, a.created_at) })}</T>
              </View>
            </Card>
          </Pressable>
        );
      })}
    </Screen>
  );
}

const styles = StyleSheet.create({
  header: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: space.sm },
  refresh: { minHeight: 44, paddingHorizontal: space.md },
  card: { flexDirection: 'row', gap: space.md, alignItems: 'center' },
  thumb: { width: 72, height: 96, borderRadius: 8, backgroundColor: colors.border },
});
