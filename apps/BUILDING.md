# Putting the apps on a phone

Both apps (`patient` and `partner`) build in the cloud with Expo's EAS service, so you don't need Android Studio or a Mac. The first build of each app takes about 15 minutes.

## One-time setup

1. Create a free account at https://expo.dev/signup.
2. On any computer with Node.js 20 or newer:
   ```bash
   npm install -g eas-cli
   eas login
   ```
3. Link each app to your account (run once per app):
   ```bash
   cd apps/patient && eas init
   cd ../partner && eas init
   ```
   This adds a `projectId` to `app.json`. Commit that change.

4. **Push notifications (optional for a first test):** in each app folder, add the app's Firebase file as a secret:
   ```bash
   eas env:create --name GOOGLE_SERVICES_JSON --type file --value ./google-services.json --environment preview --environment production --visibility secret
   ```
   Without it the app still builds, but it gets no push notifications. Medicine alarms in the patient app still work, because they are scheduled on the phone.

## Test build (installs directly on Android)

```bash
cd apps/patient     # or apps/partner
eas build --platform android --profile preview
```

When it finishes, EAS shows a link and a QR code. Open it on an Android phone and install the APK. You may need to allow "Install unknown apps". The test build has the same package name as the live Play Store app but a different signature, so Android won't install it over the live app. Uninstall the live app first, or use a spare phone.

For iPhone test builds you need an Apple Developer account (99 USD a year). Then run `eas build --platform ios --profile preview` and follow the prompts to register the phone.

## Store builds

```bash
eas build --platform android --profile production   # .aab for Google Play
eas build --platform ios --profile production       # for App Store / TestFlight
```

Google Play only accepts the update if it is signed with the same upload key as the live app. The old developer holds that key. Either get the keystore from them and upload it with `eas credentials`, or ask Google to reset the upload key: Play Console › Test and release › App integrity › App signing › Request upload key reset.

| App | Package / bundle id | Next versionCode |
| --- | --- | --- |
| Mcare+ (patient) | `com.Mcare` | 5 |
| Mcare+ Partner | `com.mcarepartner` | 2 |

Raise `version` and `android.versionCode` in `app.json` for every store release.

## Development build (for working on the code)

```bash
eas build --platform android --profile development
npx expo start
```

Install the development build once. After that it loads the code from your computer, and changes appear without rebuilding.
