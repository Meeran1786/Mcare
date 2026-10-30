import { StyleSheet, View } from 'react-native';

import { LANGUAGES, type LangCode } from '@/i18n';
import { useSession } from '@/state/session';
import { space } from '@/theme';

import { BigButton } from './ui';

export function LanguagePicker({ onPicked }: { onPicked?: (code: LangCode) => void }) {
  const { lang, setLanguage } = useSession();
  return (
    <View style={styles.grid}>
      {LANGUAGES.map((l) => (
        <BigButton
          key={l.code}
          label={l.name}
          kind={l.code === lang.code ? 'primary' : 'secondary'}
          style={styles.item}
          onPress={async () => {
            await setLanguage(l.code);
            onPicked?.(l.code);
          }}
        />
      ))}
    </View>
  );
}

const styles = StyleSheet.create({
  grid: { flexDirection: 'row', flexWrap: 'wrap', gap: space.sm },
  item: { flexBasis: '47%', flexGrow: 1 },
});
