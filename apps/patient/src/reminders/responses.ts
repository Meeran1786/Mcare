/**
 * What happens when someone presses a button on a notification:
 * "Taken" logs the dose, "Snooze" rings it again in 10 minutes, and a
 * family member's missed-dose alert opens that person's page.
 */
import * as Notifications from "expo-notifications";
import { router } from "expo-router";
import { useEffect } from "react";
import { Platform } from "react-native";

import { api } from "@/api/mcare";

import { ACTION_SNOOZE, ACTION_TAKEN, snoozeDose } from "./alarms";
import { doseKey } from "./doses";

const handled = new Set<string>();

async function handle(response: Notifications.NotificationResponse) {
    const n = response.notification;
    const id = `${n.request.identifier}|${n.date}|${response.actionIdentifier}`;
    if (handled.has(id)) return;
    handled.add(id);

    const data = (n.request.content.data ?? {}) as Record<string, unknown>;

    if (data.type === "MISSED_DOSE" && data.patient_id) {
        router.push(`/family/${data.patient_id}`);
        return;
    }

    const reminderId = Number(data.reminderId);
    if (!reminderId || !data.rawTime) return;
    const scheduledFor =
        typeof data.scheduledFor === "string"
            ? data.scheduledFor
            : doseKey(new Date(n.date), String(data.rawTime));

    if (response.actionIdentifier === ACTION_SNOOZE) {
        await snoozeDose(n.request.content, scheduledFor);
    } else if (response.actionIdentifier === ACTION_TAKEN) {
        await api
            .logDose(reminderId, scheduledFor, "taken")
            .catch(() => undefined);
    }
    await Notifications.dismissNotificationAsync(n.request.identifier).catch(
        () => undefined,
    );
    router.navigate("/home");
}

/** Mount once, after sign-in. */
export function useDoseResponses() {
    const last = Notifications.useLastNotificationResponse();

    useEffect(() => {
        if (Platform.OS === "web") return;
        const sub = Notifications.addNotificationResponseReceivedListener(
            (r) => {
                handle(r).catch(() => undefined);
            },
        );
        return () => sub.remove();
    }, []);

    useEffect(() => {
        if (last) handle(last).catch(() => undefined);
    }, [last]);
}
