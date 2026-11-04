import { MaterialCommunityIcons } from '@expo/vector-icons';
import { router } from 'expo-router';
import { useState } from 'react';
import { Image, Pressable, StyleSheet, View } from 'react-native';

import { apiErrorText } from '@/api/client';
import { api } from '@/api/partner';
import { PhotoSlot } from '@/components/bits';
import { pickImages } from '@/components/pick-image';
import { BigButton, Card, ErrorText, Screen, T } from '@/components/ui';
import { useLoad } from '@/hooks';
import { planLine } from '@/plan';
import { useSession } from '@/state/session';
import { colors, space } from '@/theme';

export default function ProfileScreen() {
  const { t, lang, pharmacy, owner, refreshPharmacy, signOut } = useSession();
  const { data: plan } = useLoad(api.plan);
  const [editingPhoto, setEditingPhoto] = useState(false);
  const [error, setError] = useState('');
  const [info, setInfo] = useState('');
  if (!pharmacy) return null;

  async function uploadPhoto(uri: string) {
    setError('');
    try {
      await api.updatePhoto(uri);
      await refreshPharmacy();
      setEditingPhoto(false);
      setInfo(t('photoUpdated'));
    } catch {
      setError(t('somethingWrong'));
    }
  }

  const rows: [string, string | null][] = [
    [t('ownerName'), pharmacy.owner_name ?? owner?.name ?? null],
    ['☎', owner?.mobile_number ?? null],
    [t('licenseNumber'), pharmacy.license_number],
    [t('registrationNumber'), pharmacy.registration_number],
    [t('gstNumber'), pharmacy.gst_number],
    [t('addressLine'), [pharmacy.address_line, pharmacy.area, pharmacy.city, pharmacy.pincode].filter(Boolean).join(', ')],
  ];

  return (
    <Screen>
      {pharmacy.pharmacy_photo_url ? (
        <Image source={{ uri: pharmacy.pharmacy_photo_url }} style={styles.photo} accessibilityLabel={t('pharmacyPhoto')} />
      ) : null}
      <T variant="title">{pharmacy.pharmacy_name}</T>
      {pharmacy.is_verified && (
        <View style={styles.row}>
          <MaterialCommunityIcons name="check-decagram" size={24} color={colors.success} />
          <T color={colors.success}>{t('verified')}</T>
        </View>
      )}
      {plan ? <T variant="small">{planLine(t, plan, lang.locale)}</T> : null}
      <Card>
        <T variant="heading">{t('pharmacyDetails')}</T>
        {rows
          .filter(([, v]) => !!v)
          .map(([k, v]) => (
            <View key={k} style={{ gap: 2 }}>
              <T variant="small">{k}</T>
              <T>{v}</T>
            </View>
          ))}
      </Card>
      {editingPhoto ? (
        <PhotoSlot label={t('pharmacyPhoto')} uri={null} onPicked={uploadPhoto} />
      ) : (
        <BigButton label={pharmacy.pharmacy_photo_url ? t('changePhoto') : t('addPhoto')} icon="camera" kind="secondary" onPress={() => setEditingPhoto(true)} />
      )}
      <ShopPhotos
        extras={pharmacy.photos ?? []}
        hasCover={!!pharmacy.pharmacy_photo_url}
        onChanged={async (msg) => {
          await refreshPharmacy();
          setError('');
          setInfo(msg);
        }}
        onError={setError}
      />
      <ErrorText>{error}</ErrorText>
      {!!info && <T color={colors.success}>{info}</T>}
      <BigButton label={t('editDetails')} icon="pencil" kind="secondary" onPress={() => router.push('/edit-pharmacy')} />
      <BigButton label={t('history')} icon="history" kind="secondary" onPress={() => router.push('/history')} />
      <BigButton label={t('exportTitle')} icon="file-export" kind="secondary" onPress={() => router.push('/export')} />
      <BigButton label={t('notifications')} icon="bell" kind="secondary" onPress={() => router.push('/notifications')} />
      <BigButton label={t('language')} icon="translate" kind="secondary" onPress={() => router.push('/change-language')} />
      <BigButton label={t('help')} icon="lifebuoy" kind="secondary" onPress={() => router.push('/help')} />
      <BigButton label={t('logout')} icon="logout" kind="danger" onPress={signOut} />
    </Screen>
  );
}

/** Up to 4 more shop photos besides the main one; patients see them all. */
function ShopPhotos({
  extras,
  hasCover,
  onChanged,
  onError,
}: {
  extras: { id: number; url: string }[];
  hasCover: boolean;
  onChanged: (message: string) => Promise<void>;
  onError: (message: string) => void;
}) {
  const { t } = useSession();
  const [busy, setBusy] = useState(false);
  const room = MAX_PHOTOS - extras.length - (hasCover ? 1 : 0);

  async function add(source: 'camera' | 'library') {
    const uris = await pickImages(source, room);
    if (!uris.length) return;
    setBusy(true);
    try {
      await api.addPhotos(uris);
      await onChanged(t('photoUpdated'));
    } catch (e) {
      onError(apiErrorText(e, t('somethingWrong')));
    } finally {
      setBusy(false);
    }
  }

  async function remove(id: number) {
    setBusy(true);
    try {
      await api.deletePhoto(id);
      await onChanged(t('photoUpdated'));
    } catch (e) {
      onError(apiErrorText(e, t('somethingWrong')));
    } finally {
      setBusy(false);
    }
  }

  return (
    <Card>
      <T variant="heading">{t('shopPhotos')}</T>
      <T variant="small">{t('photosHint', { max: MAX_PHOTOS })}</T>
      <View style={styles.thumbs}>
        {extras.map((p) => (
          <View key={p.id}>
            <Image source={{ uri: p.url }} style={styles.thumb} />
            <Pressable
              accessibilityRole="button"
              accessibilityLabel={t('removePhoto')}
              disabled={busy}
              onPress={() => remove(p.id)}
              style={styles.remove}
              hitSlop={8}>
              <MaterialCommunityIcons name="close" size={20} color={colors.onPrimary} />
            </Pressable>
          </View>
        ))}
      </View>
      {room > 0 && (
        <View style={styles.row}>
          <BigButton label={t('takePhoto')} icon="camera" kind="secondary" onPress={() => add('camera')} loading={busy} style={{ flex: 1 }} />
          <BigButton label={t('chooseFromGallery')} icon="image-multiple" kind="secondary" onPress={() => add('library')} loading={busy} style={{ flex: 1 }} />
        </View>
      )}
    </Card>
  );
}

const MAX_PHOTOS = 5;

const styles = StyleSheet.create({
  thumbs: { flexDirection: 'row', flexWrap: 'wrap', gap: space.sm },
  thumb: { width: 96, height: 96, borderRadius: 10, backgroundColor: colors.border },
  remove: {
    position: 'absolute',
    top: -8,
    right: -8,
    width: 30,
    height: 30,
    borderRadius: 15,
    backgroundColor: colors.danger,
    alignItems: 'center',
    justifyContent: 'center',
  },
  photo: { width: '100%', height: 180, borderRadius: 16, backgroundColor: colors.border },
  row: { flexDirection: 'row', alignItems: 'center', gap: space.xs },
});
