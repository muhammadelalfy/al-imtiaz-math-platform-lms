# Zewal Flutter Mobile Release Guide

## API environments

The app receives API addresses at build time. URLs are not stored in source code, and production accepts HTTPS only.

| Build target | Required values | Example command |
| --- | --- | --- |
| Local development | `ZEWAL_ENV=development`, `ZEWAL_DEVELOPMENT_API_URL` | `flutter run --dart-define=ZEWAL_ENV=development --dart-define=ZEWAL_DEVELOPMENT_API_URL=http://10.0.2.2:8000/api` |
| Staging | `ZEWAL_ENV=staging`, `ZEWAL_STAGING_API_URL` | `flutter build apk --debug --dart-define=ZEWAL_ENV=staging --dart-define=ZEWAL_STAGING_API_URL=https://staging.example.com/api` |
| Production | `ZEWAL_ENV=production`, `ZEWAL_PRODUCTION_API_URL` | `flutter build appbundle --release --dart-define=ZEWAL_ENV=production --dart-define=ZEWAL_PRODUCTION_API_URL=https://app.example.com/api` |

`ZEWAL_API_URL` is an optional one-off override for an explicitly configured preview build. Never embed tokens, signing passwords, or private hostnames in a command committed to the repository.

## Android upload signing

The Android module uses `android/key.properties` only when it exists. This file and all keystores are ignored by Git. A local release build requires:

```properties
storePassword=...
keyPassword=...
keyAlias=upload
storeFile=upload-keystore.jks
```

The GitHub Actions release job is deliberately manual and runs only when the repository variable `MOBILE_RELEASE_ENABLED` is set to `true` and the dispatch input requests an AAB. Configure these GitHub Actions secrets:

| Secret | Purpose |
| --- | --- |
| `ANDROID_KEYSTORE_BASE64` | Base64-encoded upload keystore (`.jks`) |
| `ANDROID_KEY_ALIAS` | Upload key alias |
| `ANDROID_KEY_PASSWORD` | Upload key password |
| `ANDROID_STORE_PASSWORD` | Keystore password |
| `ZEWAL_PRODUCTION_API_URL` | HTTPS Laravel API base URL for the production app |

The workflow decodes the keystore only in the ephemeral runner, generates `android/key.properties` only for that run, builds the signed `.aab`, and uploads it as a private workflow artifact. It does not publish to Play Console automatically.
