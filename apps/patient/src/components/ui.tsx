import { MaterialCommunityIcons } from '@expo/vector-icons';
import type { ComponentProps, ReactNode } from 'react';
import {
  ActivityIndicator,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  type TextInputProps,
  View,
  type ViewStyle,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { useSession } from '@/state/session';
import { colors, font, space, TOUCH } from '@/theme';

export type IconName = ComponentProps<typeof MaterialCommunityIcons>['name'];

export function Screen({ children, scroll = true }: { children: ReactNode; scroll?: boolean }) {
  const body = scroll ? (
    <ScrollView contentContainerStyle={styles.content} keyboardShouldPersistTaps="handled">
      {children}
    </ScrollView>
  ) : (
    <View style={[styles.content, { flex: 1 }]}>{children}</View>
  );
  return (
    <SafeAreaView style={styles.screen} edges={['top', 'left', 'right']}>
      {body}
    </SafeAreaView>
  );
}

/** Text that follows the chosen language's direction (Urdu is right-to-left). */
export function T({
  children,
  variant = 'body',
  color,
  center,
}: {
  children: ReactNode;
  variant?: 'title' | 'heading' | 'body' | 'small';
  color?: string;
  center?: boolean;
}) {
  const { lang } = useSession();
  return (
    <Text
      style={[
        styles[variant],
        { color: color ?? (variant === 'small' ? colors.muted : colors.text) },
        { writingDirection: lang.rtl ? 'rtl' : 'ltr', textAlign: center ? 'center' : lang.rtl ? 'right' : 'left' },
      ]}>
      {children}
    </Text>
  );
}

export function BigButton({
  label,
  icon,
  onPress,
  kind = 'primary',
  loading,
  disabled,
  style,
}: {
  label: string;
  icon?: IconName;
  onPress: () => void;
  kind?: 'primary' | 'secondary' | 'danger';
  loading?: boolean;
  disabled?: boolean;
  style?: ViewStyle;
}) {
  const bg = kind === 'primary' ? colors.primary : kind === 'danger' ? colors.danger : colors.card;
  const fg = kind === 'secondary' ? colors.primaryDark : colors.onPrimary;
  return (
    <Pressable
      accessibilityRole="button"
      accessibilityLabel={label}
      disabled={disabled || loading}
      onPress={onPress}
      style={({ pressed }) => [
        styles.button,
        { backgroundColor: bg, opacity: disabled ? 0.5 : pressed ? 0.85 : 1 },
        kind === 'secondary' && styles.buttonOutline,
        style,
      ]}>
      {loading ? (
        <ActivityIndicator color={fg} />
      ) : (
        <>
          {icon && <MaterialCommunityIcons name={icon} size={30} color={fg} />}
          <Text style={[styles.buttonText, { color: fg }]}>{label}</Text>
        </>
      )}
    </Pressable>
  );
}

export function Card({ children, style }: { children: ReactNode; style?: ViewStyle }) {
  return <View style={[styles.card, style]}>{children}</View>;
}

export function Field(props: TextInputProps & { label: string }) {
  const { label, style, ...rest } = props;
  return (
    <View style={{ gap: space.xs }}>
      <T variant="small">{label}</T>
      <TextInput placeholderTextColor={colors.muted} style={[styles.input, style]} {...rest} />
    </View>
  );
}

export function ErrorText({ children }: { children: ReactNode }) {
  return children ? <T color={colors.danger}>{children}</T> : null;
}

export function Loading() {
  return (
    <View style={{ padding: space.xl, alignItems: 'center' }}>
      <ActivityIndicator size="large" color={colors.primary} />
    </View>
  );
}

const styles = StyleSheet.create({
  screen: { flex: 1, backgroundColor: colors.background },
  content: { padding: space.lg, gap: space.md, paddingBottom: 140 },
  title: { fontSize: font.title, fontWeight: '700', lineHeight: font.title * 1.3 },
  heading: { fontSize: font.heading, fontWeight: '700', lineHeight: font.heading * 1.35 },
  body: { fontSize: font.body, lineHeight: font.body * 1.45 },
  small: { fontSize: font.small, lineHeight: font.small * 1.45 },
  button: {
    minHeight: TOUCH,
    borderRadius: 16,
    paddingHorizontal: space.lg,
    paddingVertical: space.sm,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: space.sm,
  },
  buttonOutline: { borderWidth: 2, borderColor: colors.primary },
  buttonText: { fontSize: font.body, fontWeight: '700', flexShrink: 1, textAlign: 'center' },
  card: {
    backgroundColor: colors.card,
    borderRadius: 18,
    padding: space.md,
    gap: space.sm,
    borderWidth: 1,
    borderColor: colors.border,
  },
  input: {
    minHeight: TOUCH,
    borderWidth: 2,
    borderColor: colors.border,
    borderRadius: 14,
    paddingHorizontal: space.md,
    fontSize: font.heading,
    backgroundColor: colors.card,
    color: colors.text,
  },
});
