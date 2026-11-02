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

For iPhones, see the next section.

## iPhone builds

You need an **Apple Developer Program** membership (99 USD a year, https://developer.apple.com/programs/enroll/). Enrol as an organisation if the apps should show the company name on the App Store. A Mac is not needed: EAS builds and signs in the cloud.

1. **Check the bundle IDs.** The apps use `com.Mcare` (patient) and `com.mcarepartner` (pharmacy). If the old developer ever published an iPhone version under their own Apple account, those IDs belong to that account. Then either get the apps transferred to yours (App Store Connect › App › App Information › Transfer App) or pick new IDs in `app.json` › `ios.bundleIdentifier` and in the server's `.env` (`APNS_PATIENT_BUNDLE`, `APNS_PARTNER_BUNDLE`).

2. **Register test iPhones** (once per phone). Open the link this prints on each iPhone:
   ```bash
   eas device:create
   ```

3. **Test build** (installs straight onto the registered iPhones):
   ```bash
   cd apps/patient     # or apps/partner
   eas build --platform ios --profile preview
   ```
   Sign in with your Apple ID when asked, and let EAS create the certificates and profiles. When the build finishes, open its link on the iPhone and install it. Then turn on Settings › Privacy & Security › Developer Mode, if the iPhone asks.

4. **Push notifications on iPhone.** The server sends iPhone pushes straight to Apple, so iPhones don't need the Firebase file.
   - Apple Developer › Certificates, IDs & Profiles › Keys › **+**: tick "Apple Push Notifications service (APNs)", download the `.p8` file. Note the **Key ID**, and the **Team ID** shown at the top right. One key works for both apps.
   - On the server, put the file at `/var/www/mcare/storage/app/apns.p8` and add to `.env`:
     ```
     APNS_KEY_ID=<Key ID>
     APNS_TEAM_ID=<Team ID>
     ```
     Then run `php artisan optimize:clear`. The key is a secret: never commit it or send it in chat.
   - Medicine alarms do not need any of this, because the phone schedules them itself.

5. **TestFlight and App Store:**
   ```bash
   eas build --platform ios --profile production
   eas submit --platform ios
   ```
   The first submit creates the app in App Store Connect. Add testers in TestFlight, or fill in the store listing and send it for review.

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

Raise `version` and `android.versionCode` in `app.json` for every store release, and `ios.buildNumber` for every App Store upload (it starts at 1).

## Development build (for working on the code)

```bash
eas build --platform android --profile development
npx expo start
```

Install the development build once. After that it loads the code from your computer, and changes appear without rebuilding.
