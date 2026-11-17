# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/). Instead of change type headers, we use module names.

## [Unreleased]

### [Sso]

- **BREAKING**: `/auth/login` and `/auth/google` reject an untrusted `successUrl` or `errorUrl` with `400` and don't issue the token.
  - If your application isn't one of the `REMP_*_ADDR` (e.g. a custom application using REMP SSO), add its origin to `SSO_ALLOWED_REDIRECT_ORIGINS`.
- **BREAKING**: `/auth/google` requires `successUrl` and `errorUrl`; it no longer falls back to `/`.
  - If you link to `/auth/google` directly, link to `/auth/login?successUrl=...&errorUrl=...` instead, or pass both parameters.
- **BREAKING**: `/auth/logout` redirects back only to a trusted previous page (the `Referer`, or the last visited SSO page if there's none); otherwise it redirects to the SSO error page.
  - If your application sets `Referrer-Policy: no-referrer`, use a policy sending at least the origin (e.g. `strict-origin-when-cross-origin`), so the user gets back to the application after logout.
- **BREAKING**: Google login routes (`/auth/google`, `/auth/google/callback`) are registered only when `google` is enabled in `auth.sso_providers`.
  - If you use Google login (including `DEFAULT_SSO_PROVIDER=google`), keep `google` in `auth.sso_providers`. If you cache routes (`php artisan route:cache`), rebuild the cache after changing `auth.sso_providers`.
- **IMPORTANT**: SSO redirects only to trusted origins, because the login success redirect carries a JWT. Trusted origins are read from the new `SSO_ALLOWED_REDIRECT_ORIGINS` (comma-separated `scheme://host[:port]`). If it's empty, `REMP_BEAM_ADDR`, `REMP_CAMPAIGN_ADDR`, `REMP_MAILER_ADDR` and `REMP_SSO_ADDR` are used. Invalid entries are ignored and logged; if none of them is valid, all redirects are rejected.
  - Before upgrading, set `SSO_ALLOWED_REDIRECT_ORIGINS` to the addresses of your REMP applications exactly as browsers see them (scheme, host and port), or make sure `REMP_*_ADDR` match them. Otherwise login to the unlisted application fails.
- Logout from SSO administration (`/auth/logout-web`) always continues to the SSO dashboard, where the JWT is invalidated, instead of going back to the `Referer`.
- Google login verifies the OAuth `state` parameter and redirects to `errorUrl` if the verification fails or Google rejects the authorization code, or to the SSO error page if the login session expired. The session ID is regenerated after login and the session is invalidated on logout (`/auth/logout`).

## Archive

- [v5.3](./changelogs/CHANGELOG-v5.3.md)
- [v5.2](./changelogs/CHANGELOG-v5.2.md)
- [v5.1](./changelogs/CHANGELOG-v5.1.md)
- [v5.0](./changelogs/CHANGELOG-v5.0.md)
- [v4.3](./changelogs/CHANGELOG-v4.3.md)
- [v4.2](./changelogs/CHANGELOG-v4.2.md)
- [v4.1](./changelogs/CHANGELOG-v4.1.md)
- [v4.0](./changelogs/CHANGELOG-v4.0.md)
- [v3.11](./changelogs/CHANGELOG-v3.11.md)
- [v3.10](./changelogs/CHANGELOG-v3.10.md)
- [v3.9](./changelogs/CHANGELOG-v3.9.md)
- [v3.8](./changelogs/CHANGELOG-v3.8.md)
- [v3.7](./changelogs/CHANGELOG-v3.7.md)
- [v3.6](./changelogs/CHANGELOG-v3.6.md)
- [v3.5](./changelogs/CHANGELOG-v3.5.md)
- [v3.4](./changelogs/CHANGELOG-v3.4.md)
- [v3.3](./changelogs/CHANGELOG-v3.3.md)
- [v3.2](./changelogs/CHANGELOG-v3.2.md)
- [v3.1](./changelogs/CHANGELOG-v3.1.md)
- [v3.0](./changelogs/CHANGELOG-v3.0.md)
- [v2.2](./changelogs/CHANGELOG-v2.2.md)
- [v2.1](./changelogs/CHANGELOG-v2.1.md)
- [v2.0](./changelogs/CHANGELOG-v2.0.md)
- [v1.2](./changelogs/CHANGELOG-v1.2.md)
- [v1.1](./changelogs/CHANGELOG-v1.1.md)
- [v1.0](./changelogs/CHANGELOG-v1.0.md)
- [v0.*](./changelogs/CHANGELOG-v0.md)

---

[Beam]: https://github.com/remp2020/remp/tree/master/Beam
[Campaign]: https://github.com/remp2020/remp/tree/master/Campaign
[Mailer]: https://github.com/remp2020/remp/tree/master/Mailer
[Sso]: https://github.com/remp2020/remp/tree/master/Sso
[Segments]: https://github.com/remp2020/remp/tree/master/Beam/go/cmd/segments
[Tracker]: https://github.com/remp2020/remp/tree/master/Beam/go/cmd/tracker

[Unreleased]: https://github.com/remp2020/remp/compare/5.3.0...master
