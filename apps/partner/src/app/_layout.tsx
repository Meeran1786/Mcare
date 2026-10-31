import { Stack } from 'expo-router';
import * as SplashScreen from 'expo-splash-screen';
import { StatusBar } from 'expo-status-bar';
import { useEffect } from 'react';

import { SessionProvider, useSession } from '@/state/session';
import { colors, font } from '@/theme';

SplashScreen.preventAutoHideAsync();

function RootStack() {
  const { ready, token, languageChosen, stage, t } = useSession();

  useEffect(() => {
    if (ready) SplashScreen.hideAsync();
  }, [ready]);
  if (!ready) return null;

  const signedIn = !!token;
  return (
    <Stack
      screenOptions={{
        headerStyle: { backgroundColor: colors.background },
        headerTitleStyle: { fontSize: font.heading, fontWeight: '700' },
        headerTintColor: colors.primaryDark,
        contentStyle: { backgroundColor: colors.background },
      }}>
      <Stack.Screen name="index" options={{ headerShown: false }} />
      <Stack.Protected guard={!languageChosen}>
        <Stack.Screen name="language" options={{ headerShown: false }} />
      </Stack.Protected>
      <Stack.Protected guard={languageChosen && !signedIn}>
        <Stack.Screen name="login" options={{ headerShown: false }} />
        <Stack.Screen name="otp" options={{ title: '' }} />
      </Stack.Protected>

      {/* Signing up: register the pharmacy, upload documents, wait for approval. */}
      <Stack.Protected guard={signedIn && stage === 'register'}>
        <Stack.Screen name="register" options={{ title: t('registerTitle') }} />
      </Stack.Protected>
      <Stack.Protected guard={signedIn && (stage === 'documents' || stage === 'pending')}>
        <Stack.Screen name="pending" options={{ headerShown: false }} />
        <Stack.Screen name="documents" options={{ title: t('documentsTitle') }} />
      </Stack.Protected>

      <Stack.Protected guard={signedIn && stage === 'ready'}>
        <Stack.Screen name="(tabs)" options={{ headerShown: false }} />
        <Stack.Screen name="prescription/[id]" options={{ title: t('prescriptionImage') }} />
        <Stack.Screen name="order/[id]" options={{ title: '' }} />
        <Stack.Screen name="history" options={{ title: t('history') }} />
        <Stack.Screen name="notifications" options={{ title: t('notifications') }} />
      </Stack.Protected>

      <Stack.Protected guard={signedIn}>
        <Stack.Screen name="help" options={{ title: t('help') }} />
        <Stack.Screen name="change-language" options={{ title: t('language') }} />
      </Stack.Protected>
    </Stack>
  );
}

export default function RootLayout() {
  return (
    <SessionProvider>
      <StatusBar style="dark" />
      <RootStack />
    </SessionProvider>
  );
}
