import * as Location from 'expo-location';
import { router } from 'expo-router';
import { useState } from 'react';

import { api } from '@/api/mcare';
import { BigButton, ErrorText, Field, Screen, T } from '@/components/ui';
import { useSession } from '@/state/session';
import { colors } from '@/theme';

/**
 * The API needs map coordinates for every address (pharmacies deliver within
 * 5 km), so the form starts from the phone's location and fills in the text.
 */
export default function NewAddressScreen() {
  const { t, user } = useSession();
  const [name, setName] = useState(user?.name ?? '');
  const [phone, setPhone] = useState(user?.mobile_number?.slice(-10) ?? '');
  const [line, setLine] = useState('');
  const [city, setCity] = useState('');
  const [pincode, setPincode] = useState('');
  const [coords, setCoords] = useState<{ latitude: number; longitude: number } | null>(null);
  const [locating, setLocating] = useState(false);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState('');

  async function locate() {
    setLocating(true);
    setError('');
    try {
      const perm = await Location.requestForegroundPermissionsAsync();
      if (!perm.granted) return setError(t('needLocation'));
      const pos = await Location.getCurrentPositionAsync({ accuracy: Location.Accuracy.High });
      setCoords(pos.coords);
      const [place] = await Location.reverseGeocodeAsync(pos.coords).catch(() => []);
      if (place) {
        setLine((cur) => cur || [place.name, place.street, place.district].filter(Boolean).join(', '));
        setCity((cur) => cur || place.city || place.subregion || '');
        setPincode((cur) => cur || place.postalCode || '');
      }
    } catch {
      setError(t('somethingWrong'));
    } finally {
      setLocating(false);
    }
  }

  async function save() {
    if (!coords) return setError(t('needLocation'));
    const digits = phone.replace(/\D/g, '').slice(-10);
    if (digits.length !== 10) return setError(t('invalidPhone'));
    if (!line.trim() || !name.trim()) return;
    setBusy(true);
    try {
      await api.addAddress({
        name: name.trim(),
        phone: digits,
        address_line: line.trim(),
        city: city.trim() || undefined,
        pincode: pincode.trim() || undefined,
        ...coords,
      });
      router.back();
    } catch {
      setError(t('somethingWrong'));
      setBusy(false);
    }
  }

  return (
    <Screen>
      <BigButton
        label={coords ? `✓ ${t('locationFound')}` : t('useMyLocation')}
        icon="crosshairs-gps"
        kind={coords ? 'secondary' : 'primary'}
        onPress={locate}
        loading={locating}
      />
      <Field label={t('addressLine')} value={line} onChangeText={setLine} multiline style={{ minHeight: 96 }} />
      <Field label={t('city')} value={city} onChangeText={setCity} />
      <Field label={t('pincode')} value={pincode} onChangeText={setPincode} keyboardType="number-pad" maxLength={6} />
      <Field label={t('contactName')} value={name} onChangeText={setName} />
      <Field label={t('contactPhone')} value={phone} onChangeText={setPhone} keyboardType="phone-pad" maxLength={14} />
      {!coords && <T variant="small" color={colors.muted}>{t('needLocation')}</T>}
      <ErrorText>{error}</ErrorText>
      <BigButton label={t('save')} icon="check-bold" onPress={save} loading={busy} disabled={!coords || !line.trim()} />
    </Screen>
  );
}
