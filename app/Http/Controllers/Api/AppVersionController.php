<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\Request;

class AppVersionController extends Controller
{
    public function show(Request $request)
    {
        $settings = AppSetting::firstOrCreate([]);
        $platform = strtolower($request->query('platform', 'android'));
        $requestedApp = strtolower($request->query('app', ''));
        $currentVersion = $this->cleanVersion($request->query('current_version'));

        if (in_array($requestedApp, ['user', 'vendor'], true) && $currentVersion) {
            $settings = $this->saveInitialVersionIfMissing($settings, $requestedApp, $currentVersion);
        }

        return response()->json([
            'success' => true,
            'userApp' => $this->appPayload($settings, 'user', $platform, $requestedApp === 'user' ? $currentVersion : null),
            'partnerApp' => $this->appPayload($settings, 'vendor', $platform, $requestedApp === 'vendor' ? $currentVersion : null),
        ]);
    }

    private function appPayload(?AppSetting $settings, string $prefix, string $platform, ?string $currentVersion): array
    {
        $playStore = $settings?->{$prefix . '_play_store'};
        $appStore = $settings?->{$prefix . '_app_store'};
        $latestVersion = $settings?->{$prefix . '_latest_version'};
        $requiresUpdate = $currentVersion && $latestVersion
            ? version_compare($currentVersion, $latestVersion, '<')
            : false;

        return [
            'latestVersion' => $latestVersion,
            'currentVersion' => $currentVersion,
            'requiresUpdate' => $requiresUpdate,
            'forceUpdate' => (bool) ($settings?->{$prefix . '_force_update'} ?? false),
            'updateUrl' => $this->storeUrl($platform, $playStore, $appStore),
            'playStoreUrl' => $playStore,
            'appStoreUrl' => $appStore,
        ];
    }

    private function storeUrl(string $platform, ?string $playStore, ?string $appStore): ?string
    {
        if ($platform === 'ios') {
            return $appStore ?: $playStore;
        }

        return $playStore ?: $appStore;
    }

    private function saveInitialVersionIfMissing(AppSetting $settings, string $prefix, string $currentVersion): AppSetting
    {
        $column = $prefix . '_latest_version';

        if (!$settings->{$column}) {
            $settings->update([$column => $currentVersion]);
            $settings->refresh();
        }

        return $settings;
    }

    private function cleanVersion($version): ?string
    {
        $version = trim((string) $version);

        if ($version === '' || !preg_match('/^\d+(\.\d+){0,3}$/', $version)) {
            return null;
        }

        return $version;
    }
}
