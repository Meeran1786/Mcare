import { createContext, type ReactNode, useCallback, useContext, useEffect, useMemo, useState } from 'react';

import { setAppLanguage, setAuthToken, setUnauthorizedHandler } from '@/api/client';
import { api, type User } from '@/api/mcare';
import { registerForPush } from '@/push';
import { deviceLanguage, getLanguage, type LangCode, type Language, translate } from '@/i18n';

import { storage } from './storage';

const TOKEN_KEY = 'mcare.token';
const LANG_KEY = 'mcare.lang';

interface Session {
  ready: boolean;
  token: string | null;
  user: User | null;
  /** True once the person has picked a language on this phone */
  languageChosen: boolean;
  lang: Language;
  t: (key: Parameters<typeof translate>[1], vars?: Record<string, string | number>) => string;
  setLanguage: (code: LangCode) => Promise<void>;
  signIn: (token: string, user: User) => Promise<void>;
  setUser: (user: User) => void;
  signOut: () => Promise<void>;
}

const SessionContext = createContext<Session | null>(null);

export function SessionProvider({ children }: { children: ReactNode }) {
  const [ready, setReady] = useState(false);
  const [token, setToken] = useState<string | null>(null);
  const [user, setUser] = useState<User | null>(null);
  const [langCode, setLangCode] = useState<LangCode>(deviceLanguage());
  const [languageChosen, setLanguageChosen] = useState(false);

  const signOut = useCallback(async () => {
    await api.logout();
    setAuthToken(null);
    setToken(null);
    setUser(null);
    await storage.remove(TOKEN_KEY);
  }, []);

  useEffect(() => {
    setUnauthorizedHandler(() => {
      setAuthToken(null);
      setToken(null);
      setUser(null);
      storage.remove(TOKEN_KEY);
    });
    (async () => {
      const [savedToken, savedLang] = await Promise.all([
        storage.get(TOKEN_KEY),
        storage.get(LANG_KEY),
      ]);
      if (savedLang) {
        setLangCode(getLanguage(savedLang).code);
        setLanguageChosen(true);
      }
      if (savedToken) {
        setAuthToken(savedToken);
        setToken(savedToken);
        api.profile().then(setUser).catch(() => undefined);
        api.reminders().catch(() => undefined); // reschedules the phone's alarms
        registerForPush().catch(() => undefined);
      }
      setReady(true);
    })();
    return () => setUnauthorizedHandler(null);
  }, []);

  useEffect(() => setAppLanguage(langCode), [langCode]);

  const value = useMemo<Session>(() => {
    const lang = getLanguage(langCode);
    return {
      ready,
      token,
      user,
      languageChosen,
      lang,
      t: (key, vars) => translate(lang, key, vars),
      setLanguage: async (code) => {
        setLangCode(code);
        setLanguageChosen(true);
        await storage.set(LANG_KEY, code);
        if (token) {
          api.setLanguage(code).catch(() => undefined);
          api.reminders().catch(() => undefined); // alarm text in the new language
        }
      },
      signIn: async (newToken, newUser) => {
        setAuthToken(newToken);
        setToken(newToken);
        setUser(newUser);
        await storage.set(TOKEN_KEY, newToken);
        api.setLanguage(langCode).catch(() => undefined);
      },
      setUser,
      signOut,
    };
  }, [ready, token, user, langCode, languageChosen, signOut]);

  return <SessionContext.Provider value={value}>{children}</SessionContext.Provider>;
}

export function useSession(): Session {
  const ctx = useContext(SessionContext);
  if (!ctx) throw new Error('useSession must be used inside SessionProvider');
  return ctx;
}
