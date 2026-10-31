import { useFocusEffect } from 'expo-router';
import { useCallback, useState } from 'react';

import { useVoice } from '@/voice/VoiceAssistant';

/** Loads data when the screen opens, when voice changes something, and on reload(). */
export function useLoad<T>(load: () => Promise<T>) {
  const { version } = useVoice();
  const [data, setData] = useState<T | null>(null);
  const [error, setError] = useState(false);
  const [tick, setTick] = useState(0);

  useFocusEffect(
    useCallback(() => {
      let live = true;
      setError(false);
      load()
        .then((d) => live && setData(d))
        .catch(() => live && setError(true));
      return () => {
        live = false;
      };
      // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [version, tick]),
  );

  return { data, error, reload: () => setTick((n) => n + 1) };
}
