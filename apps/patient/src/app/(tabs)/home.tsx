import { MaterialCommunityIcons } from '@expo/vector-icons';
import { router } from 'expo-router';
import { Pressable, StyleSheet, View } from 'react-native';

import { api, type Reminder } from '@/api/mcare';
import { BigButton, Card, Screen, T } from '@/components/ui';
import { useLoad } from '@/hooks';
import { formatTime } from '@/i18n';
import { useSession } from '@/state/session';
import { colors, space } from '@/theme';
import { ALL_DAYS } from '@/voice/types';
import { useVoice } from '@/voice/VoiceAssistant';

/** The next active reminder from now, looking a week ahead. */
function nextReminder(reminders: Reminder[]): Reminder | undefined {
  const now = new Date();
  const nowMin = now.getHours() * 60 + now.getMinutes();
  let best: { r: Reminder; wait: number } | undefined;
  for (const r of reminders.filter((x) => x.is_active)) {
    const [h, m] = r.raw_time.split(':').map(Number);
    for (let d = 0; d < 8; d++) {
      const day = ALL_DAYS[(now.getDay() + d) % 7];
      const wait = d * 1440 + h * 60 + m - nowMin;
      if (wait >= 0 && r.days.includes(day)) {
        if (!best || wait < best.wait) best = { r, wait };
        break;
      }
    }
  }
  return best?.r;
}

export default function HomeScreen() {
  const { t, user, lang } = useSession();
  const { open } = useVoice();
  const { data: reminders } = useLoad(api.reminders);
  const next = reminders ? nextReminder(reminders) : undefined;

  return (
    <Screen>
      <T variant="title">{t('hello', { name: user?.name ?? '' })}</T>

      <Pressable accessibilityRole="button" onPress={open} style={({ pressed }) => [styles.voice, pressed && { opacity: 0.9 }]}>
        <View style={styles.voiceIcon}>
          <MaterialCommunityIcons name="microphone" size={56} color={colors.onPrimary} />
        </View>
        <T variant="heading" color={colors.onPrimary} center>
          {t('tapToSpeak')}
        </T>
        <T variant="small" color="#E8EEFF" center>
          {t('voiceExample')}
        </T>
      </Pressable>

      {next && (
        <Card>
          <T variant="small">{t('nextMedicine')}</T>
          <T variant="heading">{next.medicine_name || t('yourMedicine')}</T>
          <T>{formatTime(lang, next.raw_time.slice(0, 5))}</T>
        </Card>
      )}

      <BigButton label={t('setReminder')} icon="alarm-plus" kind="secondary" onPress={() => router.push('/reminder/new')} />
      <BigButton label={t('refillMedicines')} icon="refresh" kind="secondary" onPress={() => router.push('/orders')} />
      <BigButton label={t('uploadPrescription')} icon="camera" kind="secondary" onPress={() => router.push('/upload')} />
    </Screen>
  );
}

const styles = StyleSheet.create({
  voice: {
    backgroundColor: colors.primary,
    borderRadius: 24,
    padding: space.lg,
    alignItems: 'center',
    gap: space.sm,
  },
  voiceIcon: {
    width: 104,
    height: 104,
    borderRadius: 52,
    backgroundColor: colors.accent,
    alignItems: 'center',
    justifyContent: 'center',
  },
});
