import { MaterialCommunityIcons } from '@expo/vector-icons';
import * as Location from 'expo-location';
import { router, useFocusEffect } from 'expo-router';
import { useCallback, useEffect, useState } from 'react';
import { Image, Pressable, StyleSheet, TextInput, View } from 'react-native';

import { api, type Address, type PharmacyResult } from '@/api/mcare';
import { BigButton, Card, ErrorText, Loading, Screen, T } from '@/components/ui';
import { useSession } from '@/state/session';
import { colors, font, space, TOUCH } from '@/theme';

type Point = { latitude: number; longitude: number };

const canDeliver = (p: PharmacyResult) => p.is_deliverable === undefined || !!Number(p.is_deliverable);

const fullAddress = (a: Address) =>
  [a.address_line, a.city, a.state, a.pincode].filter(Boolean).join(', ');

/**
 * Order screen: search pharmacies by name, pick the delivery address, send a
 * prescription to everyone nearby, or tap one nearby pharmacy to send it there.
 */
export default function OrderScreen() {
  const { t } = useSession();
  const [addresses, setAddresses] = useState<Address[] | null>(null);
  const [address, setAddress] = useState<Address | null>(null);
  const [here, setHere] = useState<Point | null>(null);
  const [choosing, setChoosing] = useState(false);
  // Results remember what they were fetched for, so stale ones are not shown.
  const [nearbyFor, setNearbyFor] = useState<{ at: string; list: PharmacyResult[] } | null>(null);
  const [query, setQuery] = useState('');
  const [foundFor, setFoundFor] = useState<{ q: string; at: string; list: PharmacyResult[] } | null>(null);
  const [error, setError] = useState('');

  // Reload addresses when coming back, e.g. after adding one.
  useFocusEffect(
    useCallback(() => {
      api
        .addresses()
        .then((list) => {
          setAddresses(list);
          setAddress((cur) => list.find((a) => a.id === cur?.id) ?? list.find((a) => !!a.is_default) ?? list[0] ?? null);
        })
        .catch(() => setAddresses([]));
    }, []),
  );

  // No saved address: use where the phone is now.
  useEffect(() => {
    if (addresses === null || address || here) return;
    (async () => {
      const perm = await Location.requestForegroundPermissionsAsync();
      if (!perm.granted) {
        setError(t('needLocation'));
        return;
      }
      const pos = await Location.getCurrentPositionAsync({ accuracy: Location.Accuracy.Balanced });
      setHere({ latitude: pos.coords.latitude, longitude: pos.coords.longitude });
    })().catch(() => setError(t('needLocation')));
  }, [addresses, address, here, t]);

  const point: Point | null = address
    ? { latitude: Number(address.latitude), longitude: Number(address.longitude) }
    : here;
  const lat = point?.latitude;
  const lng = point?.longitude;
  const at = `${lat},${lng}`;
  const q = query.trim();
  const nearby = nearbyFor?.at === at ? nearbyFor.list : null;
  const found = q.length >= 2 && foundFor?.q === q && foundFor.at === at ? foundFor.list : null;

  useEffect(() => {
    if (lat == null || lng == null) return;
    const key = `${lat},${lng}`;
    api
      .nearbyPharmacies(lat, lng)
      .then((list) => setNearbyFor({ at: key, list }))
      .catch(() => {
        setNearbyFor({ at: key, list: [] });
        setError(t('somethingWrong'));
      });
  }, [lat, lng, t]);

  // Search as the person types, after a short pause.
  useEffect(() => {
    if (q.length < 2 || lat == null || lng == null) return;
    const key = `${lat},${lng}`;
    const timer = setTimeout(() => {
      api
        .searchPharmacies(q, lat, lng)
        .then((list) => setFoundFor({ q, at: key, list }))
        .catch(() => setFoundFor({ q, at: key, list: [] }));
    }, 400);
    return () => clearTimeout(timer);
  }, [q, lat, lng]);

  function openUpload(p?: PharmacyResult) {
    router.push({
      pathname: '/upload',
      params: {
        ...(address ? { addressId: String(address.id) } : {}),
        ...(p ? { pharmacyId: String(p.id), pharmacyName: p.pharmacy_name } : {}),
      },
    });
  }

  const searching = q.length >= 2;
  const list = searching ? found : nearby;

  return (
    <Screen>
      <View style={styles.search}>
        <MaterialCommunityIcons name="magnify" size={28} color={colors.accent} />
        <TextInput
          value={query}
          onChangeText={setQuery}
          placeholder={t('searchPharmacy')}
          placeholderTextColor={colors.muted}
          accessibilityLabel={t('searchPharmacy')}
          returnKeyType="search"
          style={styles.searchInput}
        />
        {!!query && (
          <Pressable onPress={() => setQuery('')} accessibilityRole="button" accessibilityLabel={t('cancel')} hitSlop={10}>
            <MaterialCommunityIcons name="close-circle" size={26} color={colors.muted} />
          </Pressable>
        )}
      </View>

      <Card>
        <View style={styles.row}>
          <T variant="small" style={{ flex: 1 }}>
            {t('deliverTo').toUpperCase()}
          </T>
          {!!addresses?.length && (
            <Pressable onPress={() => setChoosing((c) => !c)} accessibilityRole="button" style={styles.pill}>
              <T color={colors.accent} style={{ fontWeight: '700' }}>
                {t('change')}
              </T>
            </Pressable>
          )}
        </View>
        {address ? (
          <>
            <T variant="heading">{address.label || t('addresses')}</T>
            {!!address.name && <T style={{ fontWeight: '600' }}>{t('patientName', { name: address.name })}</T>}
            <T variant="small">{fullAddress(address)}</T>
          </>
        ) : (
          <T variant="heading">{t('currentLocation')}</T>
        )}
        {choosing &&
          addresses?.map((a) => (
            <Pressable
              key={a.id}
              accessibilityRole="button"
              onPress={() => {
                setAddress(a);
                setChoosing(false);
              }}
              style={[styles.option, a.id === address?.id && styles.optionOn]}>
              <T style={{ fontWeight: '600' }}>{a.label || a.name || t('addresses')}</T>
              <T variant="small">{fullAddress(a)}</T>
            </Pressable>
          ))}
        {(choosing || addresses?.length === 0) && (
          <BigButton label={t('addAddress')} icon="map-marker-plus" kind="secondary" onPress={() => router.push('/address/new')} />
        )}
      </Card>

      <Pressable accessibilityRole="button" onPress={() => openUpload()} style={({ pressed }) => [styles.upload, pressed && { opacity: 0.85 }]}>
        <View style={styles.uploadIcon}>
          <MaterialCommunityIcons name="cloud-upload-outline" size={34} color={colors.accent} />
        </View>
        <View style={{ flex: 1 }}>
          <T variant="heading">{t('uploadPrescription')}</T>
          <T variant="small">{t('uploadNearbyHint')}</T>
        </View>
        <MaterialCommunityIcons name="chevron-right" size={30} color={colors.muted} />
      </Pressable>

      <ErrorText>{error}</ErrorText>

      <T variant="heading">{searching ? t('search') : t('nearbyPharmacies')}</T>
      {!list && !error && <Loading />}
      {list?.length === 0 && <T variant="small">{t(searching ? 'noPharmacyFound' : 'noPharmacyNearby')}</T>}
      {list?.map((p) => {
        const ok = canDeliver(p);
        const km = p.distance_km != null ? Number(p.distance_km).toFixed(1) : null;
        return (
          <Pressable
            key={p.id}
            accessibilityRole="button"
            disabled={!ok}
            onPress={() => openUpload(p)}
            style={({ pressed }) => [styles.pharmacy, !ok && { opacity: 0.55 }, pressed && { opacity: 0.85 }]}>
            {p.photo_urls?.[0] ? (
              <Image source={{ uri: p.photo_urls[0] }} style={styles.shop} />
            ) : (
              <View style={[styles.shop, styles.noShop]}>
                <MaterialCommunityIcons name="store" size={36} color={colors.primary} />
              </View>
            )}
            <View style={{ flex: 1, gap: 2 }}>
              <T style={{ fontWeight: '700' }}>{p.pharmacy_name}</T>
              {!!p.area && <T variant="small">{p.area}</T>}
              {!!p.city && <T variant="small">{p.city}</T>}
              {p.is_new && (
                <T variant="small" color={colors.success}>
                  {t('newPharmacy')}
                </T>
              )}
              {!ok && <T variant="small">{t('tooFarToDeliver')}</T>}
            </View>
            {km && (
              <View style={styles.km}>
                <T variant="small" color={colors.success} style={{ fontWeight: '700' }}>
                  {t('kmAway', { km })}
                </T>
              </View>
            )}
          </Pressable>
        );
      })}
    </Screen>
  );
}

const styles = StyleSheet.create({
  search: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: space.sm,
    minHeight: TOUCH,
    paddingHorizontal: space.md,
    borderRadius: 16,
    backgroundColor: colors.card,
    borderWidth: 1,
    borderColor: colors.border,
  },
  searchInput: { flex: 1, fontSize: font.body, color: colors.text, paddingVertical: space.sm },
  row: { flexDirection: 'row', alignItems: 'center', gap: space.sm },
  pill: {
    paddingHorizontal: space.md,
    paddingVertical: space.xs,
    borderRadius: 999,
    backgroundColor: '#EFE8FC',
    minHeight: 44,
    justifyContent: 'center',
  },
  option: { padding: space.sm, borderRadius: 12, borderWidth: 1, borderColor: colors.border },
  optionOn: { borderColor: colors.accent, borderWidth: 2 },
  upload: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: space.md,
    padding: space.md,
    borderRadius: 16,
    backgroundColor: colors.card,
    borderWidth: 1,
    borderColor: colors.border,
  },
  uploadIcon: {
    width: 64,
    height: 64,
    borderRadius: 16,
    backgroundColor: '#EFE8FC',
    alignItems: 'center',
    justifyContent: 'center',
  },
  pharmacy: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: space.md,
    padding: space.sm,
    borderRadius: 16,
    backgroundColor: colors.card,
    borderWidth: 1,
    borderColor: colors.border,
  },
  shop: { width: 84, height: 84, borderRadius: 12 },
  noShop: { backgroundColor: colors.background, alignItems: 'center', justifyContent: 'center' },
  km: { paddingHorizontal: space.sm, paddingVertical: space.xs, borderRadius: 999, backgroundColor: '#E3F5EA' },
});
