import { MaterialCommunityIcons } from '@expo/vector-icons';
import * as Notifications from 'expo-notifications';
import { router, useLocalSearchParams } from 'expo-router';
import * as Speech from 'expo-speech';
import { useEffect, useRef, useState } from 'react';
import { StyleSheet, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { api } from '@/api/mcare';
import { BigButton, T } from '@/components/ui';
import { formatTime } from '@/i18n';
import { snoozeDose, SNOOZE_MINUTES } from '@/reminders/alarms';
import { useSession } from '@/state/session';
import { colors, space } from '@/theme';

/** Says the reminder aloud this many times, ten seconds apart, until a button is pressed. */
const SPEAK_TIMES = 6;

/**
 * The full-screen medicine alarm: the medicine name, very large, and three
 * buttons. Opens when the alarm is tapped, or by itself when the app is open
 * at medicine time, and reads the reminder aloud in the person's language.
 */
export default function AlarmScreen() {
  const { t, lang } = useSession();
  const p = useLocalSearchParams<{
    notificationId?: string;
    reminderId: string;
    rawTime: string;
    medicine?: string;
    scheduledFor: string;
  }>();
  const [busy, setBusy] = useState<'taken' | 'skipped' | 'snooze' | null>(null);
  const done = useRef(false);
  const medicine = p.medicine || t('yourMedicine');
  const body = t('medicineTimeBody', { medicine });

  useEffect(() => {
    // Opening the page stops the ringing; the voice takes over.
    if (p.notificationId) Notifications.dismissNotificationAsync(p.notificationId).catch(() => undefined);
    let count = 0;
    const speak = () => {
      if (done.current || count >= SPEAK_TIMES) return;
      count += 1;
      Speech.speak(`${t('medicineTime')}. ${body}`, { language: lang.speech, rate: 0.85 });
    };
    speak();
    const timer = setInterval(speak, 10000);
    return () => {
      clearInterval(timer);
      Speech.stop();
    };
  }, [body, lang.speech, p.notificationId, t]);

  async function act(kind: 'taken' | 'skipped' | 'snooze') {
    done.current = true;
    Speech.stop();
    setBusy(kind);
    try {
      if (kind === 'snooze') {
        await snoozeDose(
          {
            title: t('medicineTime'),
            body,
            data: { kind: 'mcare-medicine-reminder', reminderId: Number(p.reminderId), rawTime: p.rawTime, medicine },
          },
          p.scheduledFor,
        );
      } else {
        await api.logDose(Number(p.reminderId), p.scheduledFor, kind);
      }
    } catch {
      // Offline: the dose list on Home still lets them mark it later.
    }
    if (router.canGoBack()) router.back();
    else router.replace('/home');
  }

  return (
    <SafeAreaView style={styles.page}>
      <View style={styles.top}>
        <MaterialCommunityIcons name="alarm" size={96} color={colors.onPrimary} />
        <T variant="heading" center color={colors.onPrimary}>
          {t('medicineTime')}
        </T>
        <T variant="title" center color={colors.onPrimary} style={styles.medicine}>
          {medicine}
        </T>
        {!!p.rawTime && (
          <T variant="heading" center color={colors.onPrimary}>
            {formatTime(lang, p.rawTime)}
          </T>
        )}
      </View>
      <View style={styles.buttons}>
        <BigButton label={t('doseTaken')} icon="check-bold" onPress={() => act('taken')} loading={busy === 'taken'} style={styles.taken} />
        <BigButton
          label={t('snooze', { min: SNOOZE_MINUTES })}
          icon="alarm-snooze"
          kind="secondary"
          onPress={() => act('snooze')}
          loading={busy === 'snooze'}
        />
        <BigButton label={t('doseSkip')} icon="close" kind="secondary" onPress={() => act('skipped')} loading={busy === 'skipped'} />
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  page: { flex: 1, backgroundColor: colors.primaryDark, padding: space.lg, justifyContent: 'space-between' },
  top: { flex: 1, alignItems: 'center', justifyContent: 'center', gap: space.md },
  medicine: { fontSize: 44, lineHeight: 52 },
  buttons: { gap: space.md, paddingBottom: space.lg },
  taken: { backgroundColor: colors.success, minHeight: 84 },
});
