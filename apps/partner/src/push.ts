import * as Notifications from 'expo-notifications';
import { AndroidImportance } from 'expo-notifications';
import { Platform } from 'react-native';

import { api } from '@/api/partner';

Notifications.setNotificationHandler({
  handleNotification: async () => ({
    shouldPlaySound: true,
    shouldSetBadge: true,
    shouldShowBanner: true,
    shouldShowList: true,
  }),
});

/**
 * Registers for the backend's Firebase pushes ("new prescription", "patient
 * confirmed delivery"). On Android the device token from expo-notifications is
 * the FCM token. iOS needs the Firebase SDK to swap its APNs token for an FCM
 * token, which is a follow-up.
 */
export async function registerForPush(): Promise<void> {
  if (Platform.OS === 'web') return;
  const { granted } = await Notifications.requestPermissionsAsync();
  if (!granted || Platform.OS !== 'android') return;
  // The backend sends new-prescription pushes to pharmacies on admin_channel_v2.
  for (const id of ['default', 'admin_channel_v2']) {
    await Notifications.setNotificationChannelAsync(id, {
      name: id === 'default' ? 'Mcare+ Partner' : 'New orders',
      importance: AndroidImportance.MAX,
      sound: 'default',
      vibrationPattern: [0, 500, 250, 500],
    });
  }
  try {
    const token = await Notifications.getDevicePushTokenAsync();
    await api.saveFcmToken(String(token.data), Platform.OS);
  } catch {
    // No google-services.json in this build, or no network: try again next launch.
  }
}
