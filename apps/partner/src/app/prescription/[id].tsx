import { router, useLocalSearchParams } from 'expo-router';
import { useState } from 'react';

import { ApiError } from '@/api/client';
import { api } from '@/api/partner';
import { CallButton, PrescriptionImages } from '@/components/bits';
import { BigButton, Card, ErrorText, Field, Loading, Screen, T } from '@/components/ui';
import { useLoad } from '@/hooks';
import { formatDateTime, type StringKey } from '@/i18n';
import { addressLine, deliveryAddress } from '@/logic/orders';
import { useSession } from '@/state/session';
import { colors } from '@/theme';

const REASONS: StringKey[] = ['reasonOutOfStock', 'reasonUnclear', 'reasonTooFar', 'reasonClosed', 'reasonOther'];

export default function PrescriptionScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const { t, lang, pharmacy } = useSession();
  const { data } = useLoad(api.inbox);
  const [mode, setMode] = useState<'view' | 'reject'>('view');
  const [reason, setReason] = useState<StringKey | null>(null);
  const [other, setOther] = useState('');
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState('');
  const [done, setDone] = useState('');

  if (!data) return <Screen><Loading /></Screen>;
  const a = data.find((x) => String(x.id) === id);
  if (!a || a.status !== 'sent')
    return (
      <Screen>
        <T variant="heading">{done || t('takenByOther')}</T>
        <BigButton label={t('tabOrders')} icon="package-variant-closed" onPress={() => router.replace('/orders')} />
      </Screen>
    );
  const p = a.prescription;
  const address = deliveryAddress(p);

  async function answer(status: 'accepted' | 'rejected') {
    const text = reason === 'reasonOther' ? other.trim() : reason ? t(reason) : '';
    if (status === 'rejected' && !text) return setError(t('required'));
    setBusy(true);
    setError('');
    try {
      await api.answerPrescription(a!.id, status, status === 'rejected' ? text : undefined);
      if (status === 'accepted') {
        router.replace('/orders');
      } else {
        setDone(t('rejected'));
        router.back();
      }
    } catch (e) {
      const offline = e instanceof ApiError && e.status === 422 && !pharmacy?.is_delivery_available;
      const taken = e instanceof ApiError && (e.status === 400 || e.status === 404);
      setError(offline ? t('goOnlineFirst') : taken ? t('takenByOther') : t('somethingWrong'));
      setBusy(false);
    }
  }

  return (
    <Screen>
      <PrescriptionImages urls={(p.files ?? []).map((f) => f.url).filter((u): u is string => !!u)} />
      <Card>
        <T variant="small">{t('patient')}</T>
        <T variant="heading">{p.user?.name ?? '—'}</T>
        <T variant="small">{t('receivedAt', { time: formatDateTime(lang, a.created_at) })}</T>
        {a.distance_km != null && <T color={colors.primaryDark}>{t('kmAway', { km: a.distance_km })}</T>}
      </Card>
      {address && (
        <Card>
          <T variant="small">{t('deliverTo')}</T>
          <T>{addressLine(address)}</T>
        </Card>
      )}
      {!!p.notes && (
        <Card>
          <T variant="small">{t('patientNote')}</T>
          <T>{p.notes}</T>
        </Card>
      )}

      {mode === 'view' ? (
        <>
          <ErrorText>{error}</ErrorText>
          <BigButton label={t('accept')} icon="check-bold" onPress={() => answer('accepted')} loading={busy} />
          <BigButton label={t('reject')} icon="close" kind="secondary" onPress={() => setMode('reject')} disabled={busy} />
          <CallButton phone={p.user?.mobile_number} />
        </>
      ) : (
        <>
          <T variant="heading">{t('rejectReasonTitle')}</T>
          {REASONS.map((r) => (
            <BigButton key={r} label={t(r)} kind={reason === r ? 'primary' : 'secondary'} onPress={() => setReason(r)} />
          ))}
          {reason === 'reasonOther' && <Field label={t('writeReason')} value={other} onChangeText={setOther} multiline />}
          <ErrorText>{error}</ErrorText>
          <BigButton label={t('confirmReject')} icon="close-circle" kind="danger" onPress={() => answer('rejected')} loading={busy} disabled={!reason} />
          <BigButton label={t('cancel')} kind="secondary" onPress={() => setMode('view')} />
        </>
      )}
    </Screen>
  );
}
