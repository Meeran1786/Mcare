import * as Notifications from 'expo-notifications';
import { AndroidImportance } from 'expo-notifications';
import { Platform } from 'react-native';

import { api } from '@/api/mcare';
import { setupReminderChannel } from '@/reminders/alarms';

Notifications.setNotificationHandler({
  handleNotification: async () => ({
    shouldPlaySound: true,
    shouldSetBadge: true,
    shouldShowBanner: true,
    shouldShowList: true,
  }),
});

/**
 * Asks for notification permission (needed for the phone's own medicine
 * alarms on both platforms) and registers for the backend's Firebase pushes,
 * which now carry order updates. Medicine reminders ring locally instead: the
 * token is saved with `local_reminders: true`, so `php artisan reminder:send`
 * skips this phone.
 *
 * Android: the device token from expo-notifications is the FCM token.
 * iOS: the backend sends through FCM, so iOS needs the Firebase SDK to swap the
 * APNs token for an FCM token. Until that is added, iOS gets alarms but no
 * order pushes.
 */
export async function registerForPush(): Promise<void> {
  if (Platform.OS === 'web') return;
  await setupReminderChannel();
  const { granted } = await Notifications.requestPermissionsAsync();
  if (!granted || Platform.OS !== 'android') return;
  await Notifications.setNotificationChannelAsync('default', {
    name: 'Mcare',
    importance: AndroidImportance.MAX,
    sound: 'default',
    vibrationPattern: [0, 400, 200, 400],
  });
  try {
    const token = await Notifications.getDevicePushTokenAsync();
    await api.saveFcmToken(String(token.data), Platform.OS);
  } catch {
    // No google-services.json in this build, or no network: try again next launch.
  }
}
