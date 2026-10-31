import { MaterialCommunityIcons } from '@expo/vector-icons';
import { Image, Linking, Pressable, StyleSheet, Switch, View } from 'react-native';

import { statusLabel } from '@/i18n';
import { useSession } from '@/state/session';
import { colors, space } from '@/theme';

import { pickImage } from './pick-image';
import { BigButton, Card, T } from './ui';

const STATUS_COLOR: Record<string, string> = {
  pending: colors.accent,
  sent: colors.accent,
  accepted: colors.primary,
  preparing: colors.warning,
  ready_for_delivery: colors.warning,
  out_for_delivery: colors.accent,
  delivered: colors.success,
  completed: colors.success,
  cancelled: colors.danger,
  rejected: colors.danger,
};

export function StatusPill({ status }: { status: string }) {
  const { lang } = useSession();
  const color = STATUS_COLOR[status] ?? colors.muted;
  return (
    <View style={[styles.pill, { borderColor: color }]}>
      <T variant="small" color={color}>
        {statusLabel(lang, status)}
      </T>
    </View>
  );
}

/** The "receiving orders" switch at the top of the requests screen and profile. */
export function OnlineSwitch({ online, busy, onChange }: { online: boolean; busy?: boolean; onChange: (v: boolean) => void }) {
  const { t } = useSession();
  return (
    <Card style={{ borderColor: online ? colors.success : colors.danger, borderWidth: 2 }}>
      <View style={styles.row}>
        <MaterialCommunityIcons name={online ? 'store-check' : 'store-off'} size={32} color={online ? colors.success : colors.danger} />
        <View style={{ flex: 1 }}>
          <T variant="heading" color={online ? colors.success : colors.danger}>
            {online ? t('online') : t('offline')}
          </T>
        </View>
        <Switch
          value={online}
          disabled={busy}
          onValueChange={onChange}
          accessibilityLabel={online ? t('goOffline') : t('goOnline')}
          trackColor={{ true: colors.success, false: colors.border }}
        />
      </View>
      {!online && <T variant="small">{t('offlineHint')}</T>}
    </Card>
  );
}

/** A photo slot with camera and gallery buttons, for the pharmacy photo and documents. */
export function PhotoSlot({ label, uri, onPicked }: { label: string; uri: string | null; onPicked: (uri: string) => void }) {
  const { t } = useSession();
  const pick = async (source: 'camera' | 'library') => {
    const picked = await pickImage(source);
    if (picked) onPicked(picked);
  };
  return (
    <Card>
      <T variant="heading">{label}</T>
      {uri ? (
        <Image source={{ uri }} style={styles.preview} resizeMode="cover" accessibilityLabel={label} />
      ) : null}
      <View style={styles.row}>
        <BigButton label={t('takePhoto')} icon="camera" kind="secondary" onPress={() => pick('camera')} style={{ flex: 1 }} />
        <BigButton label={t('chooseFromGallery')} icon="image" kind="secondary" onPress={() => pick('library')} style={{ flex: 1 }} />
      </View>
    </Card>
  );
}

/** Prescription photos; tapping one opens it full size in the browser. */
export function PrescriptionImages({ urls }: { urls: string[] }) {
  const { t } = useSession();
  if (!urls.length) return <T color={colors.muted}>{t('noImage')}</T>;
  return (
    <View style={{ gap: space.sm }}>
      {urls.map((url) => (
        <Pressable key={url} accessibilityRole="imagebutton" accessibilityLabel={t('prescriptionImage')} onPress={() => Linking.openURL(url)}>
          <Image source={{ uri: url }} style={styles.prescription} resizeMode="contain" />
        </Pressable>
      ))}
      <T variant="small">{t('openImage')}</T>
    </View>
  );
}

export function CallButton({ phone }: { phone?: string | null }) {
  const { t } = useSession();
  if (!phone) return null;
  return <BigButton label={`${t('call')} ${phone}`} icon="phone" kind="secondary" onPress={() => Linking.openURL(`tel:${phone}`)} />;
}

const styles = StyleSheet.create({
  pill: { borderWidth: 1.5, borderRadius: 999, paddingHorizontal: space.sm, paddingVertical: 2, alignSelf: 'flex-start' },
  row: { flexDirection: 'row', alignItems: 'center', gap: space.sm },
  preview: { width: '100%', height: 180, borderRadius: 12, backgroundColor: colors.border },
  prescription: { width: '100%', height: 360, borderRadius: 12, backgroundColor: '#EEF2F1' },
});
