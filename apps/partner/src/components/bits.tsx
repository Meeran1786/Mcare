import { MaterialCommunityIcons } from '@expo/vector-icons';
import * as DocumentPicker from 'expo-document-picker';
import { Image, Linking, Pressable, StyleSheet, Switch, View } from 'react-native';

import { fileType, type PickedFile } from '@/api/partner';
import { statusLabel } from '@/i18n';
import { useSession } from '@/state/session';
import { colors, space } from '@/theme';

import { pickImage, pickImages } from './pick-image';
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

/** Small previews, each with a remove button. */
function Thumbs({ items, onRemove }: { items: { key: string; uri: string | null; pdfName?: string }[]; onRemove: (i: number) => void }) {
  const { t } = useSession();
  if (!items.length) return null;
  return (
    <View style={styles.thumbs}>
      {items.map((it, i) => (
        <View key={it.key} style={styles.thumbBox}>
          {it.uri ? (
            <Image source={{ uri: it.uri }} style={styles.thumb} resizeMode="cover" />
          ) : (
            <View style={[styles.thumb, styles.pdfThumb]}>
              <MaterialCommunityIcons name="file-pdf-box" size={40} color={colors.danger} />
              <T variant="small" center>
                {it.pdfName ?? 'PDF'}
              </T>
            </View>
          )}
          <Pressable accessibilityRole="button" accessibilityLabel={t('removePhoto')} onPress={() => onRemove(i)} style={styles.remove} hitSlop={8}>
            <MaterialCommunityIcons name="close" size={20} color={colors.onPrimary} />
          </Pressable>
        </View>
      ))}
    </View>
  );
}

/** Several shop photos. The first is the main photo patients see. */
export function PhotosSlot({ label, uris, max, onChange }: { label: string; uris: string[]; max: number; onChange: (uris: string[]) => void }) {
  const { t } = useSession();
  const room = max - uris.length;
  const add = async (source: 'camera' | 'library') => {
    const picked = await pickImages(source, room);
    if (picked.length) onChange([...uris, ...picked].slice(0, max));
  };
  return (
    <Card>
      <T variant="heading">{label}</T>
      <T variant="small">{t('photosHint', { max })}</T>
      <Thumbs items={uris.map((u) => ({ key: u, uri: u }))} onRemove={(i) => onChange(uris.filter((_, j) => j !== i))} />
      {room > 0 && (
        <View style={styles.row}>
          <BigButton label={t('takePhoto')} icon="camera" kind="secondary" onPress={() => add('camera')} style={{ flex: 1 }} />
          <BigButton label={t('chooseFromGallery')} icon="image-multiple" kind="secondary" onPress={() => add('library')} style={{ flex: 1 }} />
        </View>
      )}
    </Card>
  );
}

/** A licence or GST document: several page photos (camera or gallery), or PDF files. */
export function DocumentSlot({ label, files, max, onChange }: { label: string; files: PickedFile[]; max: number; onChange: (f: PickedFile[]) => void }) {
  const { t } = useSession();
  const room = max - files.length;
  const photos = async (source: 'camera' | 'library') => {
    const uris = await pickImages(source, room);
    if (uris.length) onChange([...files, ...uris.map((uri) => ({ uri }))].slice(0, max));
  };
  const pdf = async () => {
    const res = await DocumentPicker.getDocumentAsync({ type: 'application/pdf', copyToCacheDirectory: true });
    const asset = res.canceled ? null : res.assets[0];
    if (asset) onChange([...files, { uri: asset.uri, name: asset.name, mimeType: asset.mimeType ?? 'application/pdf' }].slice(0, max));
  };
  return (
    <Card>
      <T variant="heading">{label}</T>
      <T variant="small">{t('pagesHint', { max })}</T>
      <Thumbs
        items={files.map((f) => {
          const isPdf = fileType(f).ext === 'pdf';
          return { key: f.uri, uri: isPdf ? null : f.uri, pdfName: f.name ?? undefined };
        })}
        onRemove={(i) => onChange(files.filter((_, j) => j !== i))}
      />
      {room > 0 && (
        <>
          <View style={styles.row}>
            <BigButton label={t('takePhoto')} icon="camera" kind="secondary" onPress={() => photos('camera')} style={{ flex: 1 }} />
            <BigButton label={t('chooseFromGallery')} icon="image-multiple" kind="secondary" onPress={() => photos('library')} style={{ flex: 1 }} />
          </View>
          <BigButton label={t('choosePdf')} icon="file-pdf-box" kind="secondary" onPress={pdf} />
        </>
      )}
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
  thumbs: { flexDirection: 'row', flexWrap: 'wrap', gap: space.sm },
  thumbBox: { position: 'relative' },
  thumb: { width: 96, height: 96, borderRadius: 10, backgroundColor: colors.border },
  pdfThumb: { alignItems: 'center', justifyContent: 'center', padding: 4 },
  remove: {
    position: 'absolute',
    top: -8,
    right: -8,
    width: 30,
    height: 30,
    borderRadius: 15,
    backgroundColor: colors.danger,
    alignItems: 'center',
    justifyContent: 'center',
  },
  prescription: { width: '100%', height: 360, borderRadius: 12, backgroundColor: '#EEF2F1' },
});
