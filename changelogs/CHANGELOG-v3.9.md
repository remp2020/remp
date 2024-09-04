## [3.9] - 2024-09-04

### [Beam]

- **IMPORTANT** Removed section Visitors. remp/remp#1349
    - Related data (tables `session_devices` & `session_referers`) are persisted in DB until the next major update.
    - New data are not processed (command `pageviews:process-sessions` was removed).
- **IMPORTANT** Removed section Google Analytics Reporting. remp/remp#1349
    - Data were loaded from discontinued version of Google Analytics.
- [Tracker] Fixed PubSub implementation of message broker to avoid unnecessary log records. remp/remp#1372

### [Campaign]

- **BREAKING**: Changed internals of the application causing cached/generated files not to work anymore.
    - After the release, please run `php artisan optimize:clear` on each server the app is deployed to. This applies also to the development environment after you checkout the latest version. If you deploy to the clean environment, you can skip this step.
    - After the release, please run `php artisan campaigns:refresh-cache` command at least once to refresh the Redis cache.
- **IMPORTANT**: Changed `/showtime.php` path to `/vendor/campaign/showtime.php` in `remplib.js` remp/remp#1287
    - Make sure this new path is accessible on your running installations.
- Extracted campaign to separate campaign module, that will be includable by the skeleton app in the future. remp/remp#1287
- Added copy buttons to the campaign edit and detail pages. remp/remp#1369

### [Mailer]

- **IMPORTANT**: Changed setting of Symfony Console to stop quietly catch exceptions. remp/remp#1364
    - Changed setting of `Symfony\Component\Console\Application->setCatchExceptions()` to false.
    - We don't want Symfony to catch these errors (and only show them in the command's output). We want all exceptions of the application to bubble up to Tracy.
    - If you want to keep previous behaviour, you can override it within your `config.local.neon` by calling `setCatchExceptions(true)` within `setup` directive of `console` service.
      ```neon
      console:
        setup:
          - setCatchExceptions(true)
      ```
- Fixed default sender in template form - update it to default of newsletter list when newsletter list is selected. remp/respekt#220
- Added functionality to duplicate newsletter lists with the possibility to copy subscribers. remp/remp#1363
- Set `opened` and `clicked` columns in `TemplatePresenter` template listing, to not orderable in favor of more precise numbers. remp/remp#611
- Fixed description of auto subscribe toggle when editing newsletter list. remp/remp#1366
    - If auto subscribe is enabled:
        - When adding newsletter list, all existing users are subscribed to this new newsletter list (see [\Remp\MailerModule\Hermes\ListCreatedHandler](https://github.com/remp2020/mailer-module/blob/b63effb11421cd3582dc0280e6e5bf293223b3b2/src/Hermes/ListCreatedHandler.php#L48)).
        - When editing newsletter list, only new users will be subscribed to edited newsletter list.
- Added article url support to DailyMinuteGenerator. remp/remp#1370
- Added ability to export sent emails stats in `ListPresenter`. remp/remp#1362
- Fixed Delete User API response not being actually as empty as HTTP code states. remp/remp#1378

---

[3.9]: https://github.com/remp2020/remp/compare/3.8.0...3.9.0

[Beam]: https://github.com/remp2020/remp/tree/master/Beam
[Campaign]: https://github.com/remp2020/remp/tree/master/Campaign
[Mailer]: https://github.com/remp2020/remp/tree/master/Mailer
[Sso]: https://github.com/remp2020/remp/tree/master/Sso
[Segments]: https://github.com/remp2020/remp/tree/master/Beam/go/cmd/segments
[Tracker]: https://github.com/remp2020/remp/tree/master/Beam/go/cmd/tracker
