import { router } from "expo-router";
import { useState } from "react";
import { Alert, Share, View } from "react-native";

import { api, type FamilyPerson } from "@/api/mcare";
import { BigButton, Card, ErrorText, Field, Screen, T } from "@/components/ui";
import { useLoad } from "@/hooks";
import { useSession } from "@/state/session";
import { colors, space } from "@/theme";

function Person({
    p,
    onOpen,
    onRemove,
}: {
    p: FamilyPerson;
    onOpen?: () => void;
    onRemove: () => void;
}) {
    const { t } = useSession();
    return (
        <Card>
            <T variant="heading">{p.name || `+91 ${p.mobile_number}`}</T>
            {p.name ? <T variant="small">+91 {p.mobile_number}</T> : null}
            <View style={{ gap: space.sm, marginTop: space.sm }}>
                {onOpen && (
                    <BigButton
                        label={t("familySeeMedicines")}
                        icon="pill"
                        onPress={onOpen}
                    />
                )}
                <BigButton
                    label={t("familyRemove")}
                    icon="account-remove"
                    kind="secondary"
                    onPress={onRemove}
                />
            </View>
        </Card>
    );
}

export default function FamilyScreen() {
    const { t } = useSession();
    const { data, reload, error } = useLoad(api.family);
    const [invite, setInvite] = useState<string | null>(null);
    const [code, setCode] = useState("");
    const [codeError, setCodeError] = useState<string | null>(null);
    const [busy, setBusy] = useState(false);

    const createInvite = () => {
        setBusy(true);
        api.familyInvite()
            .then((r) => setInvite(r.code))
            .catch(() => Alert.alert(t("somethingWrong")))
            .finally(() => setBusy(false));
    };

    const accept = () => {
        if (!/^\d{6}$/.test(code)) {
            setCodeError(t("familyCodeInvalid"));
            return;
        }
        setBusy(true);
        setCodeError(null);
        api.familyAccept(code)
            .then((r) => {
                setCode("");
                reload();
                router.push(`/family/${r.patient_id}`);
            })
            .catch(() => setCodeError(t("familyCodeInvalid")))
            .finally(() => setBusy(false));
    };

    const remove = (p: FamilyPerson) =>
        Alert.alert(
            t("familyRemoveQ", { name: p.name || p.mobile_number }),
            "",
            [
                { text: t("cancel"), style: "cancel" },
                {
                    text: t("familyRemove"),
                    style: "destructive",
                    onPress: () => api.familyRemove(p.id).then(reload),
                },
            ],
        );

    return (
        <Screen>
            <T>{t("familyIntro")}</T>
            {error && <ErrorText>{t("somethingWrong")}</ErrorText>}

            <T variant="heading">{t("familyMyHelpers")}</T>
            {data?.my_caregivers.length === 0 && (
                <T variant="small">{t("familyNoHelpers")}</T>
            )}
            {data?.my_caregivers.map((p) => (
                <Person key={p.id} p={p} onRemove={() => remove(p)} />
            ))}

            {invite ? (
                <Card>
                    <T>{t("familyCodeHint")}</T>
                    <T variant="title" center color={colors.primaryDark}>
                        {invite.split("").join(" ")}
                    </T>
                    <BigButton
                        label={t("familyShareCode")}
                        icon="share-variant"
                        onPress={() =>
                            Share.share({
                                message: t("familyShareText", { code: invite }),
                            })
                        }
                    />
                    <BigButton
                        label={t("refresh")}
                        icon="refresh"
                        kind="secondary"
                        onPress={() => {
                            setInvite(null);
                            reload();
                        }}
                    />
                </Card>
            ) : (
                <BigButton
                    label={t("familyAdd")}
                    icon="account-plus"
                    loading={busy}
                    onPress={createInvite}
                />
            )}

            <T variant="heading">{t("familyILookAfter")}</T>
            {data?.i_care_for.map((p) => (
                <Person
                    key={p.id}
                    p={p}
                    onOpen={() => router.push(`/family/${p.patient_id}`)}
                    onRemove={() => remove(p)}
                />
            ))}
            <Card>
                <Field
                    label={t("familyEnterCode")}
                    value={code}
                    onChangeText={(v) =>
                        setCode(v.replace(/\D/g, "").slice(0, 6))
                    }
                    keyboardType="number-pad"
                    maxLength={6}
                />
                {codeError && <ErrorText>{codeError}</ErrorText>}
                <BigButton
                    label={t("familyJoin")}
                    icon="check"
                    kind="secondary"
                    loading={busy}
                    onPress={accept}
                />
            </Card>
        </Screen>
    );
}
