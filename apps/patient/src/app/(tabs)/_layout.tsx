import { MaterialCommunityIcons } from '@expo/vector-icons';
import { Redirect, usePathname } from 'expo-router';
import { Tabs } from 'expo-router/tabs';
import { View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import type { IconName } from '@/components/ui';
import { useSession } from '@/state/session';
import { colors } from '@/theme';
import { MicButton } from '@/voice/VoiceAssistant';

function icon(name: IconName) {
  return function TabIcon({ color }: { color: unknown }) {
    return <MaterialCommunityIcons name={name} size={32} color={color as string} />;
  };
}

export default function TabLayout() {
  const { t, user } = useSession();
  // Home already has a large speak button, so the floating one would only cover content.
  const onHome = usePathname() === '/home';
  // Leave room for the iPhone home indicator below the tabs.
  const bottomInset = useSafeAreaInsets().bottom;
  const tabHeight = 84 + bottomInset;
  if (user && !user.name) return <Redirect href="/name" />;

  return (
    <View style={{ flex: 1 }}>
      <Tabs
        screenOptions={{
          headerShown: false,
          tabBarActiveTintColor: colors.primary,
          tabBarInactiveTintColor: colors.muted,
          tabBarStyle: { height: tabHeight, paddingTop: 8, paddingBottom: 12 + bottomInset },
          tabBarLabelStyle: { fontSize: 15, fontWeight: '600' },
        }}>
        <Tabs.Screen name="home" options={{ title: t('tabHome'), tabBarIcon: icon('home-heart') }} />
        <Tabs.Screen name="reminders" options={{ title: t('tabReminders'), tabBarIcon: icon('alarm') }} />
        <Tabs.Screen name="orders" options={{ title: t('tabOrders'), tabBarIcon: icon('package-variant') }} />
        <Tabs.Screen name="profile" options={{ title: t('tabProfile'), tabBarIcon: icon('account-circle') }} />
      </Tabs>
      {!onHome && (
        <View pointerEvents="box-none" style={{ position: 'absolute', left: 0, right: 0, bottom: tabHeight, top: 0 }}>
          <MicButton />
        </View>
      )}
    </View>
  );
}
