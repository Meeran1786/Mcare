import { MaterialCommunityIcons } from '@expo/vector-icons';
import { getCalendars } from 'expo-localization';
import { router } from 'expo-router';
import * as Speech from 'expo-speech';
import { ExpoSpeechRecognitionModule, useSpeechRecognitionEvent } from 'expo-speech-recognition';
import { createContext, type ReactNode, useCallback, useContext, useMemo, useRef, useState } from 'react';
import { ActivityIndicator, Modal, Pressable, StyleSheet, View } from 'react-native';

import { api } from '@/api/mcare';
import { BigButton, T } from '@/components/ui';
import { useSession } from '@/state/session';
import { colors, space } from '@/theme';

import { type Ctx, type Outcome, prepare, type Prepared, reminderConfirm } from './actions';
import { isAffirmative, isNegative, normalize, parseCommand, parseTime } from './parse';

/**
 * What the assistant is waiting for. Every step that saves or orders goes
 * through "confirm", so nothing happens until the person says yes or taps it.
 */
type Phase =
  | { name: 'closed' }
  | { name: 'listening'; for: 'command' | 'confirm' | 'time' }
  | { name: 'working' }
  | { name: 'confirm'; text: string }
  | { name: 'message'; text: string };

interface VoiceApi {
  open: () => void;
  /** Bumped whenever voice changed data, so screens can reload */
  version: number;
}

const VoiceContext = createContext<VoiceApi>({ open: () => undefined, version: 0 });
export const useVoice = () => useContext(VoiceContext);

export function VoiceProvider({ children }: { children: ReactNode }) {
  const { lang, t } = useSession();
  const [phase, setPhase] = useState<Phase>({ name: 'closed' });
  const [heard, setHeard] = useState('');
  const [version, setVersion] = useState(0);
  const [micOn, setMicOn] = useState(false);
  const pending = useRef<Prepared | null>(null);
  const listeningFor = useRef<'command' | 'confirm' | 'time'>('command');

  const ctx: Ctx = useMemo(
    () => ({ lang, api, timezone: getCalendars()[0]?.timeZone ?? 'Asia/Kolkata' }),
    [lang],
  );

  const listen = useCallback(
    async (purpose: 'command' | 'confirm' | 'time') => {
      const perm = await ExpoSpeechRecognitionModule.requestPermissionsAsync();
      if (!perm.granted) {
        setPhase({ name: 'message', text: t('micDenied') });
        return;
      }
      listeningFor.current = purpose;
      setMicOn(true);
      // While waiting for "yes"/"no" keep the question and its buttons on screen.
      if (purpose !== 'confirm') {
        setHeard('');
        setPhase({ name: 'listening', for: purpose });
      }
      ExpoSpeechRecognitionModule.start({
        lang: lang.speech,
        interimResults: true,
        continuous: false,
        // Older people pause mid-sentence; wait a little longer before stopping.
        androidIntentOptions: { EXTRA_SPEECH_INPUT_COMPLETE_SILENCE_LENGTH_MILLIS: 2500 },
      });
    },
    [lang.speech, t],
  );

  const say = useCallback(
    (text: string, then?: () => void) => {
      Speech.stop();
      Speech.speak(text, { language: lang.speech, rate: 0.9, onDone: then });
    },
    [lang.speech],
  );

  const finish = useCallback(
    (out: Outcome) => {
      pending.current = null;
      setPhase({ name: 'message', text: out.say });
      say(out.say);
      if (out.changed) setVersion((v) => v + 1);
      if (out.navigate) router.navigate(out.navigate);
    },
    [say],
  );

  const present = useCallback(
    (p: Prepared) => {
      if (p.kind === 'say') return finish(p);
      pending.current = p;
      if (p.kind === 'confirm') {
        setPhase({ name: 'confirm', text: p.say });
        say(p.say, () => listen('confirm'));
      } else {
        setPhase({ name: 'message', text: p.say });
        say(p.say, () => listen('time'));
      }
    },
    [finish, listen, say],
  );

  const confirm = useCallback(async () => {
    const p = pending.current;
    if (p?.kind !== 'confirm') return;
    ExpoSpeechRecognitionModule.abort();
    setPhase({ name: 'working' });
    try {
      finish(await p.run());
    } catch {
      finish({ say: t('somethingWrong') });
    }
  }, [finish, t]);

  const cancel = useCallback(() => {
    ExpoSpeechRecognitionModule.abort();
    Speech.stop();
    pending.current = null;
    setPhase({ name: 'closed' });
  }, []);

  const handle = useCallback(
    async (text: string) => {
      const purpose = listeningFor.current;
      if (purpose === 'confirm') {
        if (isAffirmative(text)) return confirm();
        if (isNegative(text)) return finish({ say: t('cancelledSay') });
        setPhase({ name: 'confirm', text: `${pending.current?.say ?? ''}\n\n${t('sayYesOrNo')}` });
        return say(t('sayYesOrNo'), () => listen('confirm'));
      }
      if (purpose === 'time') {
        const p = pending.current;
        const time = parseTime(normalize(text));
        if (p?.kind === 'askTime' && time) return present(reminderConfirm(ctx, time, p.days, p.medicine));
        if (isNegative(text)) return finish({ say: t('cancelledSay') });
        if (p?.kind === 'askTime') return present(p);
        return finish({ say: t('notUnderstood') });
      }
      setPhase({ name: 'working' });
      try {
        present(await prepare(parseCommand(text), ctx));
      } catch {
        finish({ say: t('somethingWrong') });
      }
    },
    [confirm, ctx, finish, listen, present, say, t],
  );

  useSpeechRecognitionEvent('result', (event) => {
    const transcript = event.results[0]?.transcript ?? '';
    setHeard(transcript);
    if (event.isFinal && transcript) handle(transcript);
  });

  useSpeechRecognitionEvent('end', () => setMicOn(false));

  useSpeechRecognitionEvent('error', (event) => {
    if (listeningFor.current === 'confirm') {
      // The buttons are still there; silence just means they will tap instead.
    } else if (event.error === 'no-speech' || event.error === 'speech-timeout') {
      setPhase((ph) => (ph.name === 'listening' ? { name: 'message', text: t('notUnderstood') } : ph));
    } else if (event.error === 'aborted') {
      // We stopped it ourselves.
    } else if (event.error === 'not-allowed') {
      setPhase({ name: 'message', text: t('micDenied') });
    } else {
      setPhase({ name: 'message', text: t('voiceUnavailable') });
    }
  });

  const open = useCallback(() => {
    pending.current = null;
    listen('command');
  }, [listen]);

  const visible = phase.name !== 'closed';

  return (
    <VoiceContext.Provider value={{ open, version }}>
      {children}
      <Modal visible={visible} animationType="slide" transparent onRequestClose={cancel}>
        <View style={styles.backdrop}>
          <View style={styles.sheet}>
            {phase.name === 'listening' && (
              <>
                <View style={styles.micPulse}>
                  <MaterialCommunityIcons name="microphone" size={64} color={colors.onPrimary} />
                </View>
                <T variant="heading" center>
                  {t('listening')}
                </T>
                {!!heard && (
                  <T variant="body" center color={colors.muted}>
                    “{heard}”
                  </T>
                )}
                <BigButton label={t('cancel')} kind="secondary" onPress={cancel} />
              </>
            )}

            {phase.name === 'working' && <ActivityIndicator size="large" color={colors.primary} />}

            {phase.name === 'confirm' && (
              <>
                {!!heard && <T variant="small">{`${t('youSaid')}: “${heard}”`}</T>}
                <T variant="heading">{phase.text}</T>
                {micOn && (
                  <View style={styles.listeningRow}>
                    <MaterialCommunityIcons name="microphone" size={28} color={colors.accent} />
                    <T variant="small">{t('sayYesOrNo')}</T>
                  </View>
                )}
                <BigButton label={t('confirm')} icon="check-bold" onPress={confirm} />
                <BigButton label={t('cancel')} icon="close" kind="secondary" onPress={() => finish({ say: t('cancelledSay') })} />
              </>
            )}

            {phase.name === 'message' && (
              <>
                {!!heard && <T variant="small">{`${t('youSaid')}: “${heard}”`}</T>}
                <T variant="heading">{phase.text}</T>
                <BigButton label={t('tapToSpeak')} icon="microphone" onPress={open} />
                <BigButton label={t('cancel')} kind="secondary" onPress={cancel} />
              </>
            )}
          </View>
        </View>
      </Modal>
    </VoiceContext.Provider>
  );
}

/** The large round microphone shown on every main screen. */
export function MicButton() {
  const { open } = useVoice();
  const { t } = useSession();
  return (
    <Pressable
      accessibilityRole="button"
      accessibilityLabel={t('tapToSpeak')}
      onPress={open}
      style={({ pressed }) => [styles.fab, pressed && { opacity: 0.85 }]}>
      <MaterialCommunityIcons name="microphone" size={44} color={colors.onPrimary} />
    </Pressable>
  );
}

const styles = StyleSheet.create({
  backdrop: { flex: 1, backgroundColor: 'rgba(15,27,45,0.55)', justifyContent: 'flex-end' },
  sheet: {
    backgroundColor: colors.background,
    borderTopLeftRadius: 28,
    borderTopRightRadius: 28,
    padding: space.lg,
    paddingBottom: space.xl * 1.5,
    gap: space.md,
    minHeight: 320,
    justifyContent: 'center',
  },
  micPulse: {
    alignSelf: 'center',
    width: 120,
    height: 120,
    borderRadius: 60,
    backgroundColor: colors.accent,
    alignItems: 'center',
    justifyContent: 'center',
  },
  listeningRow: { flexDirection: 'row', alignItems: 'center', gap: space.sm },
  fab: {
    position: 'absolute',
    right: space.lg,
    bottom: space.lg,
    width: 88,
    height: 88,
    borderRadius: 44,
    backgroundColor: colors.accent,
    alignItems: 'center',
    justifyContent: 'center',
    elevation: 6,
    shadowColor: '#000',
    shadowOpacity: 0.25,
    shadowRadius: 8,
    shadowOffset: { width: 0, height: 4 },
  },
});
