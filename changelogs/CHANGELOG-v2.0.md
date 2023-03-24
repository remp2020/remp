## [2.0.0] - 2023-03-24

This version bumps minimal version of required dependencies and expects that both `mail:migrate-mail-logs-and-conversions` and `mail:migrate-user-subscriptions-and-variants` were already executed. Update to this version only if you meet the criteria.

### Project

- **BREAKING**: Raised minimal version of PHP to v8.1. remp/remp#2091
- **BREAKING**: Raised minimal version of Node.js to v18. Older versions are already after its end-of-life and not supported anymore, `v16` ends its life in couple of months. remp/remp#2091
- **IMPORTANT**: Updated configuration of Docker Compose to use non-root users. remp/remp#2091
  - To make sure you use the same user/group within the docker images as in the host machine, follow these steps:
    1. Find out what is the `UID` and `GID` of your user:
       ```
       id -u # UID
       id -g # GID
       whoami # UNAME
       ```

    2. Create new `.env` file in the root of `remp` project (based on the `.env.example`):
       ```
       UID=1000
       GID=1000
       UNAME=docker
       ```

    3. Transfer owner of generated files created by previous version of image (owned by `root` user) to user who will use them from now on:
       ```
       sudo chown -R 1000:1000 Beam Campaign Mailer Package Sso
       ```
       If you changed the default `UID` / `GID` to something different, use that in the `chown` command.

    4. Rebuild the docker images, clear caches, and start them again:
       ```
       docker compose stop
       docker compose build beam sso campaign mailer
       docker-compose up -d
       ```

### [Beam]

- Fixed possible performance issues if bigger amount of aggregation data need to be compressed. remp/remp#1246

### [Campaign]

- Fixed issue with enforced Javascript in Variable form. remp/remp#1256
  - We unintentionally started to enforce JS in Variables without realizing that _any_ value can be stored there. We keep the syntax highlighting for now, but it's not enforced.

### [Mailer]

- **BREAKING**: Added support for external module routes (`/<module>/<presenter>/<action>`). remp/remp#1220
  - This new route map changed the default routes and breaks anything linking to the Mailer directly; primarily bookmarks. APIs are not affected by this change.
- **DEPRECATED**: Deprecated method `LogsRepository::filterAlreadySent` in favor of `LogsRepository::filterAlreadySentV2`. remp/remp#1242
- **IMPORTANT**: Changed primary key from `int` to `bigint` for `autologin_tokens` table. remp/remp#1187
  - This migration is a two-step process that requires your manual action - running `mail:migrate-autologin-tokens` in the off-peak hours. Since some tables are very exposed and cannot be locked for more than a couple of seconds, we decided to migrate the data into the new table manually and keep the old and new table in sync. Based on the amount of your data, the migration can anywhere from couple of minutes to hours.
  - Check `Database tables migration` section in `mailer-module` README file for more information.
- Removed `php-amqplib/php-amqplib` from the direct Mailer dependencies. remp/remp#1244
- Changed `<p>` tag formatting in generators. remp#remp1215
  - Generators used to remove `<p>` tags from input to then create new `<p>` tags and then add desired styling.
  - Now `<p>` tags are not removed but just changed to desired styling.
- Fixed possible performance issue when sending emails. remp/remp#1242
  - The check executed in the `mail:worker` command didn't perform well under certain DB settings and caused unnecessary hold-ups.
- Fixed `worker:mail` healthcheck not correctly working if worker was occupied with big batch. remp/remp#1240
- Added support for include and exclude segments in mail jobs. Now you can select multiple include and exclude segments for mail job. remp/remp#1216
- Added log `user_id` to `mail_logs` in mail Sender. remp/remp#1188
- Fixed `CreateNewMailLogsAndMailConversionsTable` migration to add `user_id` column and index if database table is empty. remp/remp#1188

---

[1.2.0]: https://github.com/remp2020/remp/compare/1.2.0...2.0.0

[Beam]: https://github.com/remp2020/remp/tree/master/Beam
[Campaign]: https://github.com/remp2020/remp/tree/master/Campaign
[Mailer]: https://github.com/remp2020/remp/tree/master/Mailer
[Sso]: https://github.com/remp2020/remp/tree/master/Sso
[Segments]: https://github.com/remp2020/remp/tree/master/Beam/go/cmd/segments
[Tracker]: https://github.com/remp2020/remp/tree/master/Beam/go/cmd/tracker