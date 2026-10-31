import { createContext, type ReactNode, useCallback, useContext, useEffect, useMemo, useState } from 'react';

import { setAppLanguage, setAuthToken, setUnauthorizedHandler } from '@/api/client';
import { api, type PharmacyDetails, type User } from '@/api/partner';
import { deviceLanguage, getLanguage, type LangCode, type Language, type StringKey, translate } from '@/i18n';
import { registerForPush } from '@/push';

import { type Stage, stageOf } from '@/logic/stage';

import { storage } from './storage';

const TOKEN_KEY = 'mcarepartner.token';
const LANG_KEY = 'mcarepartner.lang';

interface Session {
  ready: boolean;
  token: string | null;
  languageChosen: boolean;
  lang: Language;
  t: (key: StringKey, vars?: Record<string, string | number>) => string;
  setLanguage: (code: LangCode) => Promise<void>;
  /** undefined while loading, null when not registered yet */
  pharmacy: PharmacyDetails | null | undefined;
  owner: Partial<User> | null;
  stage: Stage;
  refreshPharmacy: () => Promise<void>;
  setPharmacy: (p: PharmacyDetails) => void;
  signIn: (token: string) => Promise<void>;
  signOut: () => Promise<void>;
}

const SessionContext = createContext<Session | null>(null);

export function SessionProvider({ children }: { children: ReactNode }) {
  const [ready, setReady] = useState(false);
  const [token, setToken] = useState<string | null>(null);
  const [langCode, setLangCode] = useState<LangCode>(deviceLanguage());
  const [languageChosen, setLanguageChosen] = useState(false);
  const [pharmacy, setPharmacyState] = useState<PharmacyDetails | null | undefined>(undefined);
  const [owner, setOwner] = useState<Partial<User> | null>(null);
  const [loadFailed, setLoadFailed] = useState(false);

  const clear = useCallback(() => {
    setAuthToken(null);
    setToken(null);
    setPharmacyState(undefined);
    setOwner(null);
    storage.remove(TOKEN_KEY);
  }, []);

  const refreshPharmacy = useCallback(async () => {
    setLoadFailed(false);
    try {
      const res = await api.pharmacy();
      setPharmacyState(res?.pharmacy ?? null);
      setOwner(res?.user ?? null);
    } catch {
      setLoadFailed(true);
    }
  }, []);

  useEffect(() => {
    setUnauthorizedHandler(clear);
    (async () => {
      const [savedToken, savedLang] = await Promise.all([storage.get(TOKEN_KEY), storage.get(LANG_KEY)]);
      if (savedLang) {
        setLangCode(getLanguage(savedLang).code);
        setLanguageChosen(true);
      }
      if (savedToken) {
        setAuthToken(savedToken);
        setToken(savedToken);
        refreshPharmacy();
        registerForPush().catch(() => undefined);
      }
      setReady(true);
    })();
    return () => setUnauthorizedHandler(null);
  }, [clear, refreshPharmacy]);

  useEffect(() => setAppLanguage(langCode), [langCode]);

  const value = useMemo<Session>(() => {
    const lang = getLanguage(langCode);
    return {
      ready,
      token,
      languageChosen,
      lang,
      t: (key, vars) => translate(lang, key, vars),
      setLanguage: async (code) => {
        setLangCode(code);
        setLanguageChosen(true);
        await storage.set(LANG_KEY, code);
        if (token) api.setLanguage(code).catch(() => undefined);
      },
      pharmacy,
      owner,
      stage: loadFailed ? 'error' : stageOf(pharmacy),
      refreshPharmacy,
      setPharmacy: setPharmacyState,
      signIn: async (newToken) => {
        setAuthToken(newToken);
        setToken(newToken);
        await storage.set(TOKEN_KEY, newToken);
        api.setLanguage(langCode).catch(() => undefined);
        await refreshPharmacy();
        registerForPush().catch(() => undefined);
      },
      signOut: async () => {
        await api.logout();
        clear();
      },
    };
  }, [ready, token, languageChosen, langCode, pharmacy, owner, loadFailed, refreshPharmacy, clear]);

  return <SessionContext.Provider value={value}>{children}</SessionContext.Provider>;
}

export function useSession(): Session {
  const ctx = useContext(SessionContext);
  if (!ctx) throw new Error('useSession must be used inside SessionProvider');
  return ctx;
}
