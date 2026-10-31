import { View } from 'react-native';

import { api } from '@/api/partner';
import { StatusPill } from '@/components/bits';
import { Card, ErrorText, Loading, Screen, T } from '@/components/ui';
import { useLoad } from '@/hooks';
import { formatDateTime } from '@/i18n';
import { money } from '@/logic/orders';
import { useSession } from '@/state/session';
import { colors, space } from '@/theme';

export default function HistoryScreen() {
  const { t, lang } = useSession();
  const { data, error } = useLoad(api.history);
  return (
    <Screen>
      {error && <ErrorText>{t('somethingWrong')}</ErrorText>}
      {!data && !error && <Loading />}
      {data?.length === 0 && <T color={colors.muted}>{t('noHistory')}</T>}
      {data?.map((o) => (
        <Card key={o.id}>
          <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', gap: space.sm }}>
            <T variant="heading">{t('orderNo', { id: o.id })}</T>
            <StatusPill status={o.status} />
          </View>
          <T>{o.user?.name ?? t('patient')}</T>
          <T variant="small">{formatDateTime(lang, o.completed_at ?? o.created_at)}</T>
          {o.total_amount != null && Number(o.total_amount) > 0 && <T color={colors.primaryDark}>{money(o.total_amount)}</T>}
        </Card>
      ))}
    </Screen>
  );
}
