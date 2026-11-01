import { MaterialCommunityIcons } from '@expo/vector-icons';
import { Pressable, StyleSheet, View } from 'react-native';

import { Card, type IconName, T } from '@/components/ui';
import type { StringKey } from '@/i18n';
import { formatTime } from '@/i18n';
import { useSession } from '@/state/session';
import { colors, space, TOUCH } from '@/theme';

export type DoseRow = {
    key: string;
    medicine: string | null;
    hhmm: string;
    state: 'taken' | 'skipped' | 'missed' | 'due' | 'upcoming';
};

const LOOK: Record<DoseRow['state'], { icon: IconName; color: string; label: StringKey }> = {
    taken: {
        icon: 'check-circle',
        color: colors.success,
        label: 'doseStateTaken',
    },
    skipped: {
        icon: 'close-circle-outline',
        color: colors.muted,
        label: 'doseStateSkipped',
    },
    missed: {
        icon: 'alert-circle',
        color: colors.danger,
        label: 'doseStateMissed',
    },
    due: {
        icon: 'clock-alert-outline',
        color: colors.accent,
        label: 'doseStateDue',
    },
    upcoming: {
        icon: 'clock-outline',
        color: colors.muted,
        label: 'doseStateUpcoming',
    },
};

/** Today's doses. With onTaken, doses not yet marked get a big "I took it" button. */
export function DoseList({ rows, onTaken, busy }: { rows: DoseRow[]; onTaken?: (key: string) => void; busy?: string | null }) {
    const { t, lang } = useSession();
    return (
        <Card>
            <T variant="heading">{t('todaysMedicines')}</T>
            {rows.map((d) => {
                const look = LOOK[d.state];
                const canMark = onTaken && (d.state === 'due' || d.state === 'missed');
                return (
                    <View key={d.key} style={styles.row}>
                        <MaterialCommunityIcons name={look.icon} size={36} color={look.color} />
                        <View style={{ flex: 1 }}>
                            <T>{d.medicine || t('yourMedicine')}</T>
                            <T variant="small">
                                {formatTime(lang, d.hhmm)} · {t(look.label)}
                            </T>
                        </View>
                        {canMark && (
                            <Pressable
                                accessibilityRole="button"
                                disabled={busy === d.key}
                                onPress={() => onTaken(d.key)}
                                style={({ pressed }) => [
                                    styles.taken,
                                    (pressed || busy === d.key) && {
                                        opacity: 0.7,
                                    },
                                ]}>
                                <T color={colors.onPrimary}>{t('doseTaken')}</T>
                            </Pressable>
                        )}
                    </View>
                );
            })}
        </Card>
    );
}

const styles = StyleSheet.create({
    row: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: space.md,
        paddingVertical: space.sm,
    },
    taken: {
        backgroundColor: colors.success,
        minHeight: TOUCH,
        paddingHorizontal: space.md,
        borderRadius: 14,
        justifyContent: 'center',
    },
});
