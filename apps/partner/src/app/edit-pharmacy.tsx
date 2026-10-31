import { router } from 'expo-router';
import { useState } from 'react';

import { apiErrorText } from '@/api/client';
import { api, type PharmacyUpdate } from '@/api/partner';
import { BigButton, ErrorText, Field, Screen, T } from '@/components/ui';
import { useSession } from '@/state/session';
import { colors } from '@/theme';

type Form = Omit<PharmacyUpdate, 'mobile_number'>;
const REQUIRED: (keyof Form)[] = ['name', 'pharmacy_name', 'license_number', 'address_line', 'area', 'city', 'state', 'pincode'];

/** Owner and pharmacy details. The login number is shown but not editable here. */
export default function EditPharmacyScreen() {
  const { t, pharmacy, owner, refreshPharmacy } = useSession();
  const [form, setForm] = useState<Form>({
    name: owner?.name ?? pharmacy?.owner_name ?? '',
    email: owner?.email ?? '',
    pharmacy_name: pharmacy?.pharmacy_name ?? '',
    license_number: pharmacy?.license_number ?? '',
    registration_number: pharmacy?.registration_number ?? '',
    gst_number: pharmacy?.gst_number ?? '',
    address_line: pharmacy?.address_line ?? '',
    area: pharmacy?.area ?? '',
    landmark: pharmacy?.landmark ?? '',
    city: pharmacy?.city ?? '',
    state: pharmacy?.state ?? '',
    pincode: pharmacy?.pincode ?? '',
  });
  const [tried, setTried] = useState(false);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState('');

  const set = (key: keyof Form) => (value: string) => setForm((f) => ({ ...f, [key]: value }));
  const missing = (key: keyof Form) => tried && REQUIRED.includes(key) && !String(form[key] ?? '').trim();

  async function save() {
    setTried(true);
    if (REQUIRED.some((k) => !String(form[k] ?? '').trim()) || !owner?.mobile_number) return setError(t('required'));
    setBusy(true);
    setError('');
    try {
      const trimmed = Object.fromEntries(Object.entries(form).map(([k, v]) => [k, typeof v === 'string' ? v.trim() : v])) as Form;
      await api.updatePharmacy({ ...trimmed, email: trimmed.email || null, mobile_number: owner.mobile_number });
      await refreshPharmacy();
      router.back();
    } catch (e) {
      setError(apiErrorText(e, t('somethingWrong')));
      setBusy(false);
    }
  }

  const field = (key: keyof Form, label: string, extra: Partial<Parameters<typeof Field>[0]> = {}) => (
    <Field
      label={label}
      value={String(form[key] ?? '')}
      onChangeText={set(key)}
      style={missing(key) ? { borderColor: colors.danger } : undefined}
      {...extra}
    />
  );

  return (
    <Screen>
      {field('name', t('ownerName'), { autoComplete: 'name' })}
      <T variant="small">{t('mobileFixed', { phone: owner?.mobile_number ?? '' })}</T>
      {field('email', t('email'), { keyboardType: 'email-address', autoCapitalize: 'none' })}
      {field('pharmacy_name', t('pharmacyName'))}
      {field('license_number', t('licenseNumber'), { autoCapitalize: 'characters' })}
      {field('registration_number', t('registrationNumber'), { autoCapitalize: 'characters' })}
      {field('gst_number', t('gstNumber'), { autoCapitalize: 'characters' })}
      {field('address_line', t('addressLine'))}
      {field('area', t('area'))}
      {field('landmark', t('landmark'))}
      {field('city', t('city'))}
      {field('state', t('state'))}
      {field('pincode', t('pincode'), { keyboardType: 'number-pad', maxLength: 6 })}
      <ErrorText>{error}</ErrorText>
      <BigButton label={t('save')} icon="content-save" onPress={save} loading={busy} />
    </Screen>
  );
}
