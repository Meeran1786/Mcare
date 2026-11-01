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
 * Registers for the backend's pushes ("new prescription", "patient confirmed
 * delivery"). On Android the device token from expo-notifications is the FCM
 * token. On iOS it is Apple's own push token, which the backend recognises and
 * sends straight to Apple (app/Services/ApnsService.php).
 */
export async function registerForPush(): Promise<void> {
  if (Platform.OS === 'web') return;
  const { granted } = await Notifications.requestPermissionsAsync();
  if (!granted) return;
  // The backend sends new-prescription pushes to pharmacies on admin_channel_v2.
  if (Platform.OS === 'android') {
    for (const id of ['default', 'admin_channel_v2']) {
      await Notifications.setNotificationChannelAsync(id, {
        name: id === 'default' ? 'Mcare+ Partner' : 'New orders',
        importance: AndroidImportance.MAX,
        sound: 'default',
        vibrationPattern: [0, 500, 250, 500],
      });
    }
  }
  try {
    const token = await Notifications.getDevicePushTokenAsync();
    await api.saveFcmToken(String(token.data), Platform.OS);
  } catch {
    // No google-services.json in this build, or no network: try again next launch.
  }
}
