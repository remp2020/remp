## [3.3] - 2023-10-03

### [Beam]

- Added gender identification in article title image and added gender balance info to article detail. remp/remp#1274

### [BeamModule]

- Added `article.show.info` widget group placeholder. remp/remp#1274
  - You can implement your own widget using `arrilot/laravel-widgets` package and display it at provided placeholder in the view.
- Added JS tracker parameters `canonicalUrl` and `referer`, allowing overriding URL and referer that are being tracked. remp/remp#1297

### [Campaign]

- Added visual changes for the overlay two buttons banner - minor button texts are now on separate line.
- Added ability to target campaign by user system language. remp/remp#1283
- Added suppressed banners listing to JS console if prioritization is enabled (for easier debugging). remp/remp#1295

### [Mailer]

- **BREAKING** Changed that `X-Mailer-Template-Params` mail header is no longer sent to `SmtpMailer`, as it may have contained sensitive information. remp/remp#1296
  - If this header is still required by your implementation, you need to implement a custom `SmtpMailer`.
- Added string error code to the Subscribe APIs to differentiate between different 404 scenarios. remp/web#2263
- Fixed Mailer segment provider users acquiring. Provided segment code needs to be processed before fetching users from database. remp/mnt#114
- Fixed New template generator form - broken sorting value `after`. If `after` was selected, select box was not shown. remp/helpdesk#2073
- Added command `crm:validate-emails` to validate all email addresses for users in a given time period. remp/remp#1026
  - You can enable this command in your `config.neon` if you already defined `crmClient` service:
  ```
  services:
    console:
        setup:
            # Enable only if "crmClient" service is available
            - add(Remp\MailerModule\Commands\ValidateCrmEmailsCommand())
  ```
  This command directly replaces `Remp\MailerModule\Hermes\ValidateCrmEmailHandler` handled, which is not necessary if the command is used.
---

[3.3]: https://github.com/remp2020/remp/compare/3.2.0...3.3.0

[Beam]: https://github.com/remp2020/remp/tree/master/Beam
[Campaign]: https://github.com/remp2020/remp/tree/master/Campaign
[Mailer]: https://github.com/remp2020/remp/tree/master/Mailer
[Sso]: https://github.com/remp2020/remp/tree/master/Sso
[Segments]: https://github.com/remp2020/remp/tree/master/Beam/go/cmd/segments
[Tracker]: https://github.com/remp2020/remp/tree/master/Beam/go/cmd/tracker
