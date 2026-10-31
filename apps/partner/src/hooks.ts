import { useFocusEffect } from 'expo-router';
import { useCallback, useState } from 'react';

/** Loads data when the screen opens (and again on reload()). */
export function useLoad<T>(load: () => Promise<T>) {
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
    }, [tick]),
  );

  return { data, error, reload: () => setTick((n) => n + 1) };
}
