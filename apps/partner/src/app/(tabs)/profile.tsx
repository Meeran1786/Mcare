import { MaterialCommunityIcons } from '@expo/vector-icons';
import { router } from 'expo-router';
import { useState } from 'react';
import { Image, StyleSheet, View } from 'react-native';

import { api } from '@/api/partner';
import { PhotoSlot } from '@/components/bits';
import { BigButton, Card, ErrorText, Screen, T } from '@/components/ui';
import { useSession } from '@/state/session';
import { colors, space } from '@/theme';

export default function ProfileScreen() {
  const { t, pharmacy, owner, refreshPharmacy, signOut } = useSession();
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
      <ErrorText>{error}</ErrorText>
      {!!info && <T color={colors.success}>{info}</T>}
      <BigButton label={t('history')} icon="history" kind="secondary" onPress={() => router.push('/history')} />
      <BigButton label={t('notifications')} icon="bell" kind="secondary" onPress={() => router.push('/notifications')} />
      <BigButton label={t('language')} icon="translate" kind="secondary" onPress={() => router.push('/change-language')} />
      <BigButton label={t('help')} icon="lifebuoy" kind="secondary" onPress={() => router.push('/help')} />
      <BigButton label={t('logout')} icon="logout" kind="danger" onPress={signOut} />
    </Screen>
  );
}

const styles = StyleSheet.create({
  photo: { width: '100%', height: 180, borderRadius: 16, backgroundColor: colors.border },
  row: { flexDirection: 'row', alignItems: 'center', gap: space.xs },
});
