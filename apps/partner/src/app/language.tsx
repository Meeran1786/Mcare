import { router } from 'expo-router';
import { Image } from 'react-native';

import { LanguagePicker } from '@/components/language-picker';
import { Screen, T } from '@/components/ui';
import { LANGUAGES } from '@/i18n';

export default function LanguageScreen() {
  return (
    <Screen>
      <Image source={require('@/assets/images/icon.png')} style={{ width: 120, height: 120, alignSelf: 'center' }} />
      {/* Shown in every language, since we do not know theirs yet. */}
      <T variant="title" center>
        {LANGUAGES.map((l) => l.strings.chooseLanguage).join('\n')}
      </T>
      <LanguagePicker onPicked={() => router.replace('/login')} />
    </Screen>
  );
}
