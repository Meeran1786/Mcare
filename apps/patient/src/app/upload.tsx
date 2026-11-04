import { MaterialCommunityIcons } from '@expo/vector-icons';
import * as ImagePicker from 'expo-image-picker';
import * as Location from 'expo-location';
import { router } from 'expo-router';
import { useRef, useState } from 'react';
import { Alert, Image, Pressable, StyleSheet, View } from 'react-native';

import { api, type PharmacyResult } from '@/api/mcare';
import { BigButton, Card, ErrorText, Field, Screen, T } from '@/components/ui';
import { useSession } from '@/state/session';
import { colors, space } from '@/theme';

type Point = { latitude: number; longitude: number; addressId?: number };

/** Delivery point: the default saved address, else where the phone is now. */
async function deliveryPoint(): Promise<Point | null> {
  const addresses = await api.addresses().catch(() => []);
  const home = addresses.find((a) => !!a.is_default) ?? addresses[0];
  if (home) return { latitude: Number(home.latitude), longitude: Number(home.longitude), addressId: home.id };
  const perm = await Location.requestForegroundPermissionsAsync();
  if (!perm.granted) return null;
  const pos = await Location.getCurrentPositionAsync({ accuracy: Location.Accuracy.Balanced });
  return { latitude: pos.coords.latitude, longitude: pos.coords.longitude };
}

const canDeliver = (p: PharmacyResult) => p.is_deliverable === undefined || !!Number(p.is_deliverable);

export default function UploadScreen() {
  const { t } = useSession();
  const [photos, setPhotos] = useState<string[]>([]);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState('');
  // Who gets the prescription: everyone nearby (null), or one chosen pharmacy.
  const [chosen, setChosen] = useState<PharmacyResult | null>(null);
  const [searching, setSearching] = useState(false);
  const [query, setQuery] = useState('');
  const [results, setResults] = useState<PharmacyResult[] | null>(null);
  const [looking, setLooking] = useState(false);
  const [nearbyMode, setNearbyMode] = useState(false);
  const point = useRef<Point | null>(null);

  async function getPoint() {
    point.current = point.current ?? (await deliveryPoint());
    return point.current;
  }

  async function pick(camera: boolean) {
    const opts: ImagePicker.ImagePickerOptions = { mediaTypes: ['images'], quality: 0.6 };
    if (camera) {
      const perm = await ImagePicker.requestCameraPermissionsAsync();
      if (!perm.granted) return;
    }
    const res = camera ? await ImagePicker.launchCameraAsync(opts) : await ImagePicker.launchImageLibraryAsync({ ...opts, allowsMultipleSelection: true });
    if (!res.canceled) setPhotos((p) => [...p, ...res.assets.map((a) => a.uri)].slice(0, 5));
  }

  async function search(nearby = false) {
    if (!nearby && query.trim().length < 2) return;
    setLooking(true);
    setError('');
    setNearbyMode(nearby);
    try {
      const at = await getPoint();
      if (!at) {
        setError(t('needLocation'));
        return;
      }
      setResults(
        nearby
          ? await api.nearbyPharmacies(at.latitude, at.longitude)
          : await api.searchPharmacies(query.trim(), at.latitude, at.longitude),
      );
    } catch {
      setError(t('somethingWrong'));
    } finally {
      setLooking(false);
    }
  }

  function choose(p: PharmacyResult) {
    setChosen(p);
    setSearching(false);
    setResults(null);
    setQuery('');
  }

  async function send() {
    setBusy(true);
    setError('');
    try {
      const at = await getPoint();
      if (!at) {
        setError(t('needLocation'));
        return;
      }
      await api.uploadPrescription({ uris: photos, ...at, pharmacyId: chosen?.id });
      Alert.alert('', t('uploadSent'));
      router.replace('/orders');
    } catch (e) {
      setError(e instanceof Error ? e.message : t('somethingWrong'));
    } finally {
      setBusy(false);
    }
  }

  return (
    <Screen>
      <T>{t('uploadHint')}</T>
      <BigButton label={t('takePhoto')} icon="camera" onPress={() => pick(true)} />
      <BigButton label={t('chooseFromGallery')} icon="image-multiple" kind="secondary" onPress={() => pick(false)} />
      <View style={styles.thumbs}>
        {photos.map((uri) => (
          <Image key={uri} source={{ uri }} style={styles.thumb} />
        ))}
      </View>

      <Card>
        <T variant="heading">{t('sendTo')}</T>
        <T>{chosen ? chosen.pharmacy_name : t('allNearby')}</T>
        {!searching && (
          <BigButton
            label={chosen ? t('changePharmacy') : t('choosePharmacy')}
            icon="store-search"
            kind="secondary"
            onPress={() => setSearching(true)}
          />
        )}
        {!searching && chosen && (
          <BigButton label={t('allNearby')} icon="store-marker" kind="secondary" onPress={() => setChosen(null)} />
        )}
        {searching && (
          <>
            <BigButton label={t('pharmaciesNearMe')} icon="map-marker-radius" kind="secondary" onPress={() => search(true)} loading={looking} />
            <Field label={t('pharmacyName')} value={query} onChangeText={setQuery} onSubmitEditing={() => search()} returnKeyType="search" />
            <BigButton label={t('search')} icon="magnify" onPress={() => search()} loading={looking} disabled={query.trim().length < 2} />
            {results?.length === 0 && <T variant="small">{t(nearbyMode ? 'noPharmacyNearby' : 'noPharmacyFound')}</T>}
            {results?.map((p) => {
              const ok = canDeliver(p);
              const km = p.distance_km != null ? Number(p.distance_km).toFixed(1) : null;
              return (
                <Pressable
                  key={p.id}
                  accessibilityRole="button"
                  disabled={!ok}
                  onPress={() => choose(p)}
                  style={({ pressed }) => [styles.result, !ok && styles.far, pressed && { opacity: 0.8 }]}>
                  {p.photo_urls?.[0] ? (
                    <Image source={{ uri: p.photo_urls[0] }} style={styles.shop} />
                  ) : (
                    <MaterialCommunityIcons name="store" size={28} color={ok ? colors.primary : colors.muted} />
                  )}
                  <View style={{ flex: 1 }}>
                    <T>{p.pharmacy_name}</T>
                    {p.is_new && (
                      <T variant="small" color={colors.success}>
                        {t('newPharmacy')}
                      </T>
                    )}
                    <T variant="small">
                      {[p.area, p.city].filter(Boolean).join(', ')}
                      {km ? ` · ${t('kmAway', { km })}` : ''}
                      {ok ? '' : ` · ${t('tooFarToDeliver')}`}
                    </T>
                  </View>
                </Pressable>
              );
            })}
            <BigButton label={t('cancel')} kind="secondary" onPress={() => setSearching(false)} />
          </>
        )}
      </Card>

      <ErrorText>{error}</ErrorText>
      {!!error && <BigButton label={t('addAddress')} icon="map-marker-plus" kind="secondary" onPress={() => router.push('/address/new')} />}
      {photos.length > 0 && (
        <BigButton
          label={chosen ? t('sendToPharmacy', { name: chosen.pharmacy_name }) : t('sendToPharmacies')}
          icon="send"
          onPress={send}
          loading={busy}
        />
      )}
    </Screen>
  );
}

const styles = StyleSheet.create({
  thumbs: { flexDirection: 'row', flexWrap: 'wrap', gap: space.sm },
  thumb: { width: 100, height: 130, borderRadius: 12 },
  result: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: space.sm,
    padding: space.sm,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: colors.border,
  },
  far: { opacity: 0.55 },
  shop: { width: 56, height: 56, borderRadius: 10 },
});
