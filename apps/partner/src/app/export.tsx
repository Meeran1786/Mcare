import * as FileSystem from 'expo-file-system/legacy';
import * as Print from 'expo-print';
import * as Sharing from 'expo-sharing';
import { useEffect, useState } from 'react';
import { Platform, View } from 'react-native';

import { api, type ExportResult } from '@/api/partner';
import { BigButton, Card, ErrorText, Loading, Screen, T } from '@/components/ui';
import { formatDate } from '@/i18n';
import { monthRange, toCsv, toHtml } from '@/logic/export';
import { money } from '@/logic/orders';
import { useSession } from '@/state/session';
import { colors, space } from '@/theme';

type Range = 'thisMonth' | 'lastMonth' | 'last3Months';

function rangeFor(r: Range, now = new Date()) {
  if (r === 'thisMonth') return monthRange(now);
  if (r === 'lastMonth') return monthRange(now, 1);
  return { from: monthRange(now, 2).from, to: monthRange(now).to };
}

/** Delivered orders for a period, shared as a spreadsheet or PDF (WhatsApp, email, Drive…). */
export default function ExportScreen() {
  const { t, lang, pharmacy } = useSession();
  const [range, setRange] = useState<Range>('thisMonth');
  const [result, setResult] = useState<ExportResult | null>(null);
  const [error, setError] = useState('');
  const [busy, setBusy] = useState<'csv' | 'pdf' | null>(null);
  const { from, to } = rangeFor(range);

  useEffect(() => {
    let live = true;
    api.exportDelivered(from, to).then(
      (r) => live && (setResult(r), setError('')),
      () => live && setError(t('somethingWrong')),
    );
    return () => {
      live = false;
    };
  }, [from, to, t]);

  const labels = {
    orderId: t('colOrder'),
    customer: t('colCustomer'),
    prescriptions: t('colPrescriptions'),
    date: t('colDate'),
    amount: t('colAmount'),
    status: t('colStatus'),
    total: t('colTotal'),
  };
  const period = `${formatDate(lang, from)} – ${formatDate(lang, to)}`;
  const fileBase = `mcare-delivered-${from}-to-${to}`;

  async function share(kind: 'csv' | 'pdf') {
    if (!result) return;
    setBusy(kind);
    setError('');
    try {
      let uri: string;
      if (kind === 'csv') {
        uri = `${FileSystem.cacheDirectory}${fileBase}.csv`;
        await FileSystem.writeAsStringAsync(uri, toCsv(result, labels));
      } else {
        const html = toHtml(result, { ...labels, title: t('exportTitle'), period, pharmacy: pharmacy?.pharmacy_name ?? '' });
        if (Platform.OS === 'web') {
          await Print.printAsync({ html });
          return;
        }
        const printed = await Print.printToFileAsync({ html });
        uri = `${FileSystem.cacheDirectory}${fileBase}.pdf`;
        await FileSystem.moveAsync({ from: printed.uri, to: uri });
      }
      await Sharing.shareAsync(uri, {
        mimeType: kind === 'csv' ? 'text/csv' : 'application/pdf',
        dialogTitle: t('exportTitle'),
        UTI: kind === 'csv' ? 'public.comma-separated-values-text' : 'com.adobe.pdf',
      });
    } catch {
      setError(t('somethingWrong'));
    } finally {
      setBusy(null);
    }
  }

  return (
    <Screen>
      <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: space.sm }}>
        {(['thisMonth', 'lastMonth', 'last3Months'] as Range[]).map((r) => (
          <BigButton
            key={r}
            label={t(r)}
            kind={range === r ? 'primary' : 'secondary'}
            onPress={() => {
              setResult(null);
              setRange(r);
            }}
            style={{ flexGrow: 1, minHeight: 48 }}
          />
        ))}
      </View>
      <T variant="small">{period}</T>
      {!result && !error && <Loading />}
      <ErrorText>{error}</ErrorText>
      {result && (
        <>
          <Card>
            <T variant="heading" color={colors.primaryDark}>
              {t('exportSummary', { n: result.summary.orders_count, amount: money(result.summary.total_earnings) })}
            </T>
            {result.data.length === 0 && <T color={colors.muted}>{t('noExportRows')}</T>}
            {result.data.slice(0, 5).map((r) => (
              <T key={r.order_id} variant="small">{`#${r.order_id} · ${r.customer_name} · ${money(r.amount)}`}</T>
            ))}
            {result.data.length > 5 && <T variant="small">…</T>}
          </Card>
          <BigButton label={t('shareExcel')} icon="microsoft-excel" onPress={() => share('csv')} loading={busy === 'csv'} disabled={!!busy || !result.data.length} />
          <BigButton label={t('sharePdf')} icon="file-pdf-box" kind="secondary" onPress={() => share('pdf')} loading={busy === 'pdf'} disabled={!!busy || !result.data.length} />
        </>
      )}
    </Screen>
  );
}
