import { Stack, useLocalSearchParams } from 'expo-router';
import { useState } from 'react';
import { StyleSheet, View } from 'react-native';

import { ApiError } from '@/api/client';
import { api, type DeliveryUpdate, type Order } from '@/api/partner';
import { CallButton, PrescriptionImages, StatusPill } from '@/components/bits';
import { BigButton, Card, ErrorText, Field, Loading, Screen, T } from '@/components/ui';
import { useLoad } from '@/hooks';
import { formatClock, formatDate, statusLabel } from '@/i18n';
import {
  addressLine,
  deliveryAddress,
  isClosed,
  missingForDispatch,
  money,
  needsDeliveryDetails,
  nextDays,
  nextStatus,
  parseAmount,
  shiftTime,
  suggestedTime,
} from '@/logic/orders';
import { useSession } from '@/state/session';
import { colors, space } from '@/theme';

export default function OrderScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const { t, lang } = useSession();
  const { data, error, reload } = useLoad(() => api.order(id));
  // The latest version after an update here, otherwise what the API returned.
  const [updated, setOrder] = useState<Order | null>(null);
  const order = updated ?? data?.data ?? null;

  if (error)
    return (
      <Screen>
        <ErrorText>{t('somethingWrong')}</ErrorText>
        <BigButton label={t('retry')} icon="refresh" onPress={reload} />
      </Screen>
    );
  if (!order || !data) return <Screen><Loading /></Screen>;

  const p = order.prescription;
  const address = order.address ?? deliveryAddress(p) ?? order.user?.address ?? null;
  return (
    <Screen>
      <Stack.Screen options={{ title: t('orderNo', { id: order.id }) }} />
      <View style={styles.row}>
        <T variant="small">{t('status')}</T>
        <StatusPill status={order.status} />
      </View>
      <Card>
        <T variant="small">{t('patient')}</T>
        <T variant="heading">{order.user?.name ?? '—'}</T>
        {address && <T>{addressLine(address)}</T>}
        {data.distance_km != null && <T color={colors.primaryDark}>{t('kmAway', { km: data.distance_km })}</T>}
        {!!p?.notes && <T variant="small">{`${t('patientNote')}: ${p.notes}`}</T>}
      </Card>
      <CallButton phone={order.user?.mobile_number} />

      <Steps order={order} onChange={setOrder} />

      <T variant="heading">{t('prescriptionImage')}</T>
      <PrescriptionImages urls={(p?.files ?? []).map((f) => f.url).filter((u): u is string => !!u)} />
      {order.delivery_date && (
        <T variant="small">{`${t('deliveryDate')}: ${formatDate(lang, order.delivery_date)} ${formatClock(lang, order.delivery_clock_time)}`}</T>
      )}
    </Screen>
  );
}

/** Delivery details plus the one big "next step" button. */
function Steps({ order, onChange }: { order: Order; onChange: (o: Order) => void }) {
  const { t, lang } = useSession();
  const days = nextDays(new Date());
  const [amount, setAmount] = useState(order.total_amount && Number(order.total_amount) > 0 ? String(Number(order.total_amount)) : '');
  const [date, setDate] = useState(order.delivery_date?.slice(0, 10) ?? days[0]);
  const [time, setTime] = useState(order.delivery_clock_time?.slice(0, 5) ?? suggestedTime(new Date()));
  const [note, setNote] = useState(order.delivery_note ?? '');
  const [busy, setBusy] = useState<'save' | 'next' | null>(null);
  const [error, setError] = useState('');
  const [info, setInfo] = useState('');

  if (order.status === 'delivered') return <T color={colors.success}>{t('waitingForPatient')}</T>;
  if (isClosed(order.status)) return <T color={colors.muted}>{t('orderClosed')}</T>;

  const next = nextStatus(order.status);

  function details(): DeliveryUpdate | null {
    const parsed = amount.trim() ? parseAmount(amount) : undefined;
    if (parsed === null) {
      setError(t('invalidAmount'));
      return null;
    }
    return { amount: parsed, delivery_date: date, delivery_clock_time: time, delivery_note: note.trim() || undefined };
  }

  async function run(kind: 'save' | 'next') {
    setError('');
    setInfo('');
    const update = details();
    if (!update) return;
    const status = kind === 'next' && next ? next : order.status;
    if (needsDeliveryDetails(status) && missingForDispatch(order, update).length) return setError(t('needDetails'));
    setBusy(kind);
    try {
      const saved = await api.updateOrder(order.id, status, update);
      onChange({ ...order, ...saved });
      if (kind === 'save') setInfo(t('deliverySaved'));
    } catch (e) {
      const needs = e instanceof ApiError && (e.body as { requires_delivery_update?: boolean } | null)?.requires_delivery_update;
      setError(needs ? t('needDetails') : t('somethingWrong'));
    } finally {
      setBusy(null);
    }
  }

  return (
    <Card>
      <Field label={t('amount')} placeholder={t('amountHint')} value={amount} onChangeText={setAmount} keyboardType="decimal-pad" />
      {!!amount && parseAmount(amount) !== null && <T variant="small">{money(parseAmount(amount))}</T>}

      <T variant="small">{t('deliveryDate')}</T>
      <View style={styles.chips}>
        {days.map((d, i) => (
          <BigButton
            key={d}
            label={i === 0 ? t('today') : i === 1 ? t('tomorrow') : formatDate(lang, d)}
            kind={date === d ? 'primary' : 'secondary'}
            onPress={() => setDate(d)}
            style={styles.chip}
          />
        ))}
      </View>

      <T variant="small">{t('deliveryTime')}</T>
      <View style={styles.row}>
        <BigButton label="−30" kind="secondary" onPress={() => setTime(shiftTime(time, -30))} style={styles.step} />
        <View style={{ flex: 1 }}>
          <T variant="heading" center>
            {formatClock(lang, time)}
          </T>
        </View>
        <BigButton label="+30" kind="secondary" onPress={() => setTime(shiftTime(time, 30))} style={styles.step} />
      </View>

      <Field label={t('deliveryNote')} value={note} onChangeText={setNote} multiline />
      <ErrorText>{error}</ErrorText>
      {!!info && <T color={colors.success}>{info}</T>}
      <BigButton label={t('saveDelivery')} icon="content-save" kind="secondary" onPress={() => run('save')} loading={busy === 'save'} disabled={!!busy} />
      {next && (
        <BigButton
          label={t('markAs', { status: statusLabel(lang, next) })}
          icon="arrow-right-bold"
          onPress={() => run('next')}
          loading={busy === 'next'}
          disabled={!!busy}
        />
      )}
    </Card>
  );
}

const styles = StyleSheet.create({
  row: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: space.sm },
  chips: { flexDirection: 'row', flexWrap: 'wrap', gap: space.sm },
  chip: { flexGrow: 1, minHeight: 48 },
  step: { minWidth: 88, minHeight: 48 },
});
