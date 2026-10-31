import { router } from 'expo-router';
import { Alert } from 'react-native';

import { api } from '@/api/mcare';
import { BigButton, Card, Loading, Screen, T } from '@/components/ui';
import { useLoad } from '@/hooks';
import { useSession } from '@/state/session';
import { colors } from '@/theme';

export default function AddressesScreen() {
  const { t } = useSession();
  const { data, reload } = useLoad(api.addresses);

  function remove(id: number) {
    Alert.alert(t('deleteAddressQ'), '', [
      { text: t('cancel'), style: 'cancel' },
      { text: t('delete'), style: 'destructive', onPress: () => api.deleteAddress(id).then(reload) },
    ]);
  }

  return (
    <Screen>
      <BigButton label={t('addAddress')} icon="map-marker-plus" onPress={() => router.push('/address/new')} />
      {!data && <Loading />}
      {data?.length === 0 && <T>{t('noAddresses')}</T>}
      {data?.map((a) => (
        <Card key={a.id}>
          {!!a.is_default && <T variant="small" color={colors.success}>✓ {t('defaultAddress')}</T>}
          <T variant="heading">{a.label || t('addresses')}</T>
          <T>{a.address_line}</T>
          {!a.is_default && (
            <BigButton label={t('makeDefault')} icon="star" kind="secondary" onPress={() => api.setDefaultAddress(a.id).then(reload)} />
          )}
          <BigButton label={t('delete')} icon="trash-can" kind="secondary" onPress={() => remove(a.id)} />
        </Card>
      ))}
    </Screen>
  );
}
