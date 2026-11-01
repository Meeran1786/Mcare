import { Stack, useLocalSearchParams } from "expo-router";
import { Linking } from "react-native";

import { api } from "@/api/mcare";
import { DoseList } from "@/components/dose-list";
import {
    BigButton,
    Card,
    ErrorText,
    Loading,
    Screen,
    T,
} from "@/components/ui";
import { useLoad } from "@/hooks";
import { useSession } from "@/state/session";

/** A family member's view of the person they look after. */
export default function FamilyPersonScreen() {
    const { t } = useSession();
    const { id } = useLocalSearchParams<{ id: string }>();
    const { data, error, reload } = useLoad(() =>
        api.familySummary(Number(id)),
    );

    if (error)
        return (
            <Screen>
                <ErrorText>{t("somethingWrong")}</ErrorText>
                <BigButton label={t("retry")} onPress={reload} />
            </Screen>
        );
    if (!data) return <Loading />;

    const name = data.patient.name || `+91 ${data.patient.mobile_number}`;
    const week = data.last_7_days;

    return (
        <Screen>
            <Stack.Screen options={{ title: name }} />
            {data.today.length > 0 ? (
                <DoseList
                    rows={data.today.map((d) => ({
                        key: `${d.reminder_id}|${d.scheduled_for}`,
                        medicine: d.medicine_name,
                        hhmm: d.scheduled_for.slice(11, 16),
                        state: d.status,
                    }))}
                />
            ) : (
                <T>{t("familyNoDosesToday")}</T>
            )}
            <Card>
                <T variant="heading">{t("familyLast7Days")}</T>
                <T>
                    {t("familyWeek", {
                        taken: week.taken,
                        missed: week.missed,
                        skipped: week.skipped,
                    })}
                </T>
            </Card>
            <BigButton
                label={t("familyCall", { name })}
                icon="phone"
                onPress={() =>
                    Linking.openURL(`tel:+91${data.patient.mobile_number}`)
                }
            />
            <BigButton
                label={t("refresh")}
                icon="refresh"
                kind="secondary"
                onPress={reload}
            />
        </Screen>
    );
}
