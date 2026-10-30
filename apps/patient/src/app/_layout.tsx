import { Stack } from 'expo-router';
import * as SplashScreen from 'expo-splash-screen';
import { StatusBar } from 'expo-status-bar';
import { useEffect } from 'react';

import { SessionProvider, useSession } from '@/state/session';
import { colors, font } from '@/theme';
import { VoiceProvider } from '@/voice/VoiceAssistant';

SplashScreen.preventAutoHideAsync();

function RootStack() {
  const { ready, token, languageChosen, t } = useSession();

  useEffect(() => {
    if (ready) SplashScreen.hideAsync();
  }, [ready]);
  if (!ready) return null;

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
      <Stack.Protected guard={languageChosen && !token}>
        <Stack.Screen name="login" options={{ headerShown: false }} />
        <Stack.Screen name="otp" options={{ title: '' }} />
      </Stack.Protected>
      <Stack.Protected guard={!!token}>
        <Stack.Screen name="(tabs)" options={{ headerShown: false }} />
        <Stack.Screen name="name" options={{ headerShown: false }} />
        <Stack.Screen name="reminder/[id]" options={{ title: t('addReminder') }} />
        <Stack.Screen name="upload" options={{ title: t('uploadTitle') }} />
        <Stack.Screen name="notifications" options={{ title: t('notifications') }} />
        <Stack.Screen name="change-language" options={{ title: t('language') }} />
      </Stack.Protected>
    </Stack>
  );
}

export default function RootLayout() {
  return (
    <SessionProvider>
      <VoiceProvider>
        <StatusBar style="dark" />
        <RootStack />
      </VoiceProvider>
    </SessionProvider>
  );
}
