import { MaterialCommunityIcons } from '@expo/vector-icons';
import { Tabs } from 'expo-router/tabs';

import type { IconName } from '@/components/ui';
import { useSession } from '@/state/session';
import { colors } from '@/theme';

function icon(name: IconName) {
  return function TabIcon({ color }: { color: unknown }) {
    return <MaterialCommunityIcons name={name} size={28} color={color as string} />;
  };
}

export default function TabLayout() {
  const { t } = useSession();
  return (
    <Tabs
      screenOptions={{
        headerShown: false,
        tabBarActiveTintColor: colors.primary,
        tabBarInactiveTintColor: colors.muted,
        tabBarStyle: { height: 76, paddingTop: 6, paddingBottom: 10 },
        tabBarLabelStyle: { fontSize: 14, fontWeight: '600' },
      }}>
      <Tabs.Screen name="requests" options={{ title: t('tabRequests'), tabBarIcon: icon('file-document-multiple') }} />
      <Tabs.Screen name="orders" options={{ title: t('tabOrders'), tabBarIcon: icon('package-variant-closed') }} />
      <Tabs.Screen name="earnings" options={{ title: t('tabEarnings'), tabBarIcon: icon('cash-multiple') }} />
      <Tabs.Screen name="profile" options={{ title: t('tabProfile'), tabBarIcon: icon('store') }} />
    </Tabs>
  );
}
