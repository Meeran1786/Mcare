import * as Notifications from 'expo-notifications';
import { AndroidImportance } from 'expo-notifications';
import { Platform } from 'react-native';

import { api } from '@/api/mcare';

Notifications.setNotificationHandler({
  handleNotification: async () => ({
    shouldPlaySound: true,
    shouldSetBadge: true,
    shouldShowBanner: true,
    shouldShowList: true,
  }),
});

/**
 * Registers this phone for the backend's Firebase pushes (order updates and
 * medicine reminders, sent by `php artisan reminder:send`).
 *
 * Android: the device token from expo-notifications is the FCM token.
 * iOS: the backend sends through FCM, so iOS needs the Firebase SDK to swap the
 * APNs token for an FCM token. Until that is added, iOS skips registration.
 */
export async function registerForPush(): Promise<void> {
  if (Platform.OS !== 'android') return;
  await Notifications.setNotificationChannelAsync('default', {
    name: 'Mcare',
    importance: AndroidImportance.MAX,
    sound: 'default',
    vibrationPattern: [0, 400, 200, 400],
  });
  const { granted } = await Notifications.requestPermissionsAsync();
  if (!granted) return;
  try {
    const token = await Notifications.getDevicePushTokenAsync();
    await api.saveFcmToken(String(token.data), Platform.OS);
  } catch {
    // No google-services.json in this build, or no network: try again next launch.
  }
}
