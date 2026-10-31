import { router } from 'expo-router';

import { LanguagePicker } from '@/components/language-picker';
import { Screen } from '@/components/ui';

export default function ChangeLanguageScreen() {
  return (
    <Screen>
      <LanguagePicker onPicked={() => router.back()} />
    </Screen>
  );
}
