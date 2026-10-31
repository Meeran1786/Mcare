import * as ImagePicker from 'expo-image-picker';
import * as Location from 'expo-location';
import { router } from 'expo-router';
import { useState } from 'react';
import { Alert, Image, StyleSheet, View } from 'react-native';

import { api } from '@/api/mcare';
import { BigButton, ErrorText, Screen, T } from '@/components/ui';
import { useSession } from '@/state/session';
import { space } from '@/theme';

/** Delivery point: the default saved address, else where the phone is now. */
async function deliveryPoint(): Promise<{ latitude: number; longitude: number; addressId?: number } | null> {
  const addresses = await api.addresses().catch(() => []);
  const home = addresses.find((a) => !!a.is_default) ?? addresses[0];
  if (home) return { latitude: Number(home.latitude), longitude: Number(home.longitude), addressId: home.id };
  const perm = await Location.requestForegroundPermissionsAsync();
  if (!perm.granted) return null;
  const pos = await Location.getCurrentPositionAsync({ accuracy: Location.Accuracy.Balanced });
  return { latitude: pos.coords.latitude, longitude: pos.coords.longitude };
}

export default function UploadScreen() {
  const { t } = useSession();
  const [photos, setPhotos] = useState<string[]>([]);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState('');

  async function pick(camera: boolean) {
    const opts: ImagePicker.ImagePickerOptions = { mediaTypes: ['images'], quality: 0.6 };
    if (camera) {
      const perm = await ImagePicker.requestCameraPermissionsAsync();
      if (!perm.granted) return;
    }
    const res = camera ? await ImagePicker.launchCameraAsync(opts) : await ImagePicker.launchImageLibraryAsync({ ...opts, allowsMultipleSelection: true });
    if (!res.canceled) setPhotos((p) => [...p, ...res.assets.map((a) => a.uri)].slice(0, 5));
  }

  async function send() {
    setBusy(true);
    setError('');
    try {
      const point = await deliveryPoint();
      if (!point) {
        setError(t('needLocation'));
        return;
      }
      await api.uploadPrescription({ uris: photos, ...point });
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
      <ErrorText>{error}</ErrorText>
      {!!error && <BigButton label={t('addAddress')} icon="map-marker-plus" kind="secondary" onPress={() => router.push('/address/new')} />}
      {photos.length > 0 && (
        <BigButton label={t('sendToPharmacies')} icon="send" onPress={send} loading={busy} />
      )}
    </Screen>
  );
}

const styles = StyleSheet.create({
  thumbs: { flexDirection: 'row', flexWrap: 'wrap', gap: space.sm },
  thumb: { width: 100, height: 130, borderRadius: 12 },
});
