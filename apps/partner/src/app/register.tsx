import * as Location from 'expo-location';
import { useState } from 'react';

import { apiErrorText } from '@/api/client';
import { api, type NewPharmacy } from '@/api/partner';
import { PhotoSlot } from '@/components/bits';
import { BigButton, ErrorText, Field, Screen, T } from '@/components/ui';
import { useSession } from '@/state/session';
import { colors } from '@/theme';

type Form = Omit<NewPharmacy, 'latitude' | 'longitude'>;
const REQUIRED: (keyof Form)[] = ['owner_name', 'pharmacy_name', 'license_number', 'registration_number', 'address_line', 'area', 'city', 'state', 'pincode'];

export default function RegisterScreen() {
  const { t, refreshPharmacy } = useSession();
  const [form, setForm] = useState<Form>({
    owner_name: '',
    pharmacy_name: '',
    license_number: '',
    registration_number: '',
    gst_number: '',
    address_line: '',
    area: '',
    landmark: '',
    city: '',
    state: '',
    pincode: '',
  });
  const [coords, setCoords] = useState<{ latitude: number; longitude: number } | null>(null);
  const [photo, setPhoto] = useState<string | null>(null);
  const [locating, setLocating] = useState(false);
  const [note, setNote] = useState('');
  const [error, setError] = useState('');
  const [busy, setBusy] = useState(false);
  const [tried, setTried] = useState(false);

  const set = (key: keyof Form) => (value: string) => setForm((f) => ({ ...f, [key]: value }));
  const missing = (key: keyof Form) => tried && REQUIRED.includes(key) && !form[key]?.trim();

  async function locate() {
    setLocating(true);
    setError('');
    try {
      const perm = await Location.requestForegroundPermissionsAsync();
      if (!perm.granted) return setError(t('locationDenied'));
      const pos = await Location.getCurrentPositionAsync({ accuracy: Location.Accuracy.High });
      setCoords({ latitude: pos.coords.latitude, longitude: pos.coords.longitude });
      const [place] = await Location.reverseGeocodeAsync(pos.coords).catch(() => []);
      if (place) {
        setForm((f) => ({
          ...f,
          address_line: f.address_line || [place.name, place.street].filter(Boolean).join(', '),
          area: f.area || place.district || place.subregion || '',
          city: f.city || place.city || '',
          state: f.state || place.region || '',
          pincode: f.pincode || place.postalCode || '',
        }));
      }
      setNote(t('locationFound'));
    } catch {
      setError(t('somethingWrong'));
    } finally {
      setLocating(false);
    }
  }

  async function submit() {
    setTried(true);
    if (REQUIRED.some((k) => !form[k]?.trim())) return setError(t('required'));
    if (!coords) return setError(t('locationNeeded'));
    setBusy(true);
    setError('');
    try {
      const trimmed = Object.fromEntries(Object.entries(form).map(([k, v]) => [k, v?.trim()])) as Form;
      await api.registerPharmacy({ ...trimmed, ...coords }, photo ?? undefined);
      await refreshPharmacy(); // moves on to the documents step
    } catch (e) {
      setError(apiErrorText(e, t('somethingWrong')));
      setBusy(false);
    }
  }

  const field = (key: keyof Form, label: string, extra: Partial<Parameters<typeof Field>[0]> = {}) => (
    <Field
      label={label}
      value={form[key] ?? ''}
      onChangeText={set(key)}
      style={missing(key) ? { borderColor: colors.danger } : undefined}
      {...extra}
    />
  );

  return (
    <Screen>
      <T>{t('registerHint')}</T>
      {field('owner_name', t('ownerName'), { autoComplete: 'name' })}
      {field('pharmacy_name', t('pharmacyName'))}
      {field('license_number', t('licenseNumber'), { autoCapitalize: 'characters' })}
      {field('registration_number', t('registrationNumber'), { autoCapitalize: 'characters' })}
      {field('gst_number', t('gstNumber'), { autoCapitalize: 'characters' })}

      <BigButton label={t('useMyLocation')} icon="crosshairs-gps" kind={coords ? 'secondary' : 'primary'} onPress={locate} loading={locating} />
      {!!note && <T color={colors.success}>{note}</T>}
      {field('address_line', t('addressLine'))}
      {field('area', t('area'))}
      {field('landmark', t('landmark'))}
      {field('city', t('city'))}
      {field('state', t('state'))}
      {field('pincode', t('pincode'), { keyboardType: 'number-pad', maxLength: 6 })}

      <PhotoSlot label={t('pharmacyPhoto')} uri={photo} onPicked={setPhoto} />

      <ErrorText>{error}</ErrorText>
      <BigButton label={t('continue')} icon="arrow-right" onPress={submit} loading={busy} />
    </Screen>
  );
}
