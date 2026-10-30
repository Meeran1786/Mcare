import { getCalendars } from 'expo-localization';
import { router, Stack, useLocalSearchParams } from 'expo-router';
import { useEffect, useState } from 'react';
import { Pressable, StyleSheet, View } from 'react-native';

import { api } from '@/api/mcare';
import { BigButton, ErrorText, Field, Screen, T } from '@/components/ui';
import { formatTime } from '@/i18n';
import { useSession } from '@/state/session';
import { colors, space, TOUCH } from '@/theme';
import { ALL_DAYS, type Day } from '@/voice/types';
import { useVoice } from '@/voice/VoiceAssistant';

const PRESETS: { key: 'morning' | 'afternoon' | 'evening' | 'night'; time: string }[] = [
  { key: 'morning', time: '08:00' },
  { key: 'afternoon', time: '14:00' },
  { key: 'evening', time: '18:00' },
  { key: 'night', time: '21:00' },
];

const pad = (n: number) => String(n).padStart(2, '0');

/** Add 15-minute steps to "HH:mm", wrapping around midnight. */
function shift(time: string, minutes: number): string {
  const [h, m] = time.split(':').map(Number);
  const total = (((h * 60 + m + minutes) % 1440) + 1440) % 1440;
  return `${pad(Math.floor(total / 60))}:${pad(total % 60)}`;
}

export default function ReminderForm() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const isNew = id === 'new';
  const { t, lang } = useSession();
  const { version } = useVoice();
  const [medicine, setMedicine] = useState('');
  const [time, setTime] = useState('08:00');
  const [days, setDays] = useState<Day[]>([...ALL_DAYS]);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState('');

  useEffect(() => {
    if (isNew) return;
    api.reminders().then((list) => {
      const r = list.find((x) => String(x.id) === id);
      if (!r) return;
      setMedicine(r.medicine_name ?? '');
      setTime(r.raw_time.slice(0, 5));
      setDays(r.days);
    });
  }, [id, isNew, version]);

  async function save() {
    if (!days.length) return;
    setBusy(true);
    const body = {
      time,
      days,
      medicine_name: medicine.trim() || undefined,
      timezone: getCalendars()[0]?.timeZone ?? 'Asia/Kolkata',
    };
    try {
      if (isNew) await api.createReminder(body);
      else await api.updateReminder(Number(id), body);
      router.back();
    } catch {
      setError(t('somethingWrong'));
      setBusy(false);
    }
  }

  const toggle = (d: Day) => setDays((cur) => (cur.includes(d) ? cur.filter((x) => x !== d) : [...cur, d]));

  return (
    <Screen>
      <Stack.Screen options={{ title: isNew ? t('addReminder') : t('editReminder') }} />
      <Field label={t('medicineName')} value={medicine} onChangeText={setMedicine} />

      <T variant="small">{t('time')}</T>
      <View style={styles.row}>
        <Step label="−" onPress={() => setTime((x) => shift(x, -15))} />
        <View style={styles.timeBox}>
          <T variant="title" center>
            {formatTime(lang, time)}
          </T>
        </View>
        <Step label="+" onPress={() => setTime((x) => shift(x, 15))} />
      </View>
      <View style={styles.wrap}>
        {PRESETS.map((p) => (
          <Chip key={p.key} label={lang.strings[p.key]} on={time === p.time} onPress={() => setTime(p.time)} />
        ))}
      </View>

      <T variant="small">{t('repeatOn')}</T>
      <View style={styles.wrap}>
        {ALL_DAYS.map((d, i) => (
          <Chip key={d} label={lang.strings.daysShort[i]} on={days.includes(d)} onPress={() => toggle(d)} />
        ))}
      </View>

      <ErrorText>{error}</ErrorText>
      <BigButton label={t('save')} icon="check-bold" onPress={save} loading={busy} disabled={!days.length} />
    </Screen>
  );
}

function Step({ label, onPress }: { label: string; onPress: () => void }) {
  return (
    <Pressable accessibilityRole="button" onPress={onPress} style={styles.step}>
      <T variant="title" color={colors.onPrimary} center>
        {label}
      </T>
    </Pressable>
  );
}

function Chip({ label, on, onPress }: { label: string; on: boolean; onPress: () => void }) {
  return (
    <Pressable
      accessibilityRole="checkbox"
      accessibilityState={{ checked: on }}
      onPress={onPress}
      style={[styles.chip, on && { backgroundColor: colors.primary, borderColor: colors.primary }]}>
      <T color={on ? colors.onPrimary : colors.text} center>
        {label}
      </T>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  row: { flexDirection: 'row', alignItems: 'center', gap: space.sm },
  timeBox: { flex: 1 },
  step: {
    width: TOUCH,
    height: TOUCH,
    borderRadius: TOUCH / 2,
    backgroundColor: colors.primary,
    alignItems: 'center',
    justifyContent: 'center',
  },
  wrap: { flexDirection: 'row', flexWrap: 'wrap', gap: space.sm },
  chip: {
    minHeight: 56,
    minWidth: 72,
    paddingHorizontal: space.md,
    borderRadius: 28,
    borderWidth: 2,
    borderColor: colors.border,
    backgroundColor: colors.card,
    alignItems: 'center',
    justifyContent: 'center',
  },
});
