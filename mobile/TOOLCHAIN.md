# Flutter Toolchain Record

The mobile project is built with the stable Flutter SDK `3.47.1` and Dart `3.13.1`, installed from the official Flutter Linux release archive on 23 August 2026.

The official Flutter SDK archive identifies the stable channel as the recommended release channel for new and production applications. Source: <https://docs.flutter.dev/install/archive>.

The local environment can run Flutter analysis, tests, and web validation. Android SDK tooling is not installed in this sandbox, so a signed Android APK/AAB remains a release-environment step. The generated project includes Android, iOS, web, and Linux targets for downstream device builds.

## Temporary preview note

Flutter's debug `web-server` target may remain on its bootstrap loader when opened through a temporary proxy because it expects a browser debugging connection. The mobile preview is therefore served from the verified `build/web` production bundle through `tooling/preview-server.mjs`. Browser verification on 24 August 2026 confirmed that the production preview renders the Arabic RTL Zewal login interface.
