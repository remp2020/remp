## [2.2] - 2023-05-25

### [Mailer]

- **BREAKING**: Changed behavior when subscribing `mail_type` with variants (`mail_type.is_multi_variant = true`) **without specified variant**. remp/crm#2723
  - If `mail_type.default_variant_id` is set, such variant is subscribed _(this didn't change)_.
  - **BREAKING**: If `mail_type.default_variant_id` is not set, all variants are subscribed.
- **BREAKING**: Added `context` parameter to `IReplace::replace()` interface method. remp/remp#1102
  - Use context to pass additional information to replacers.
  - If you have own implementation of `IReplace` interface, you should add new `context` parameter to `replace` method.
- Added list of subscribed variants to `/api/v1/users/subscribe` API response (parameter `subscribed_variants`). remp/crm#2723
- Fixed broken master search, which was hitting deprecated search URL. remp/remp#1265
- Added clicked links tracking in sent emails. remp/remp#1102
  - Added `RtmClickReplace` to add `rtm_click` query parameter with computed link hash to email links. Hash is then used to identify clicked link in email.
  - Added table with link clicks count to `/template/show` page.
  - You can enable/disable email link clicks tracking in Settings with Mail click tracker toggle.
  - Do not forget to seed your database with new config (Run `make install` after every update).
- Added `url` parameter with clicked URL to `mailgun-event` Hermes event in `v2/MailgunEventsHandler`. remp/remp#1102
- **IMPORTANT**: Fixed inconsistent behavior of missing translations for layouts and snippets which didn't default to the primary locale variant. remp/remp#1260
  - This fix might affect you if you are using multiple locales in Mailer, and if you rely on the behavior that empty snippet/layout is included for locales which were not filled.
  - Our intention (also communicated by the UI) was always the same: Use the translation if it's present, or **fallback to the default language if it is not**. Unfortunately layouts and snippets always created empty translations and used them instead. That was a bug. If you want to preserve this behavior, you need to explictly save _something_ into the translation (e.g. space or HTML comment).

---

[2.2]: https://github.com/remp2020/remp/compare/2.1.0...2.2.0

[Beam]: https://github.com/remp2020/remp/tree/master/Beam
[Campaign]: https://github.com/remp2020/remp/tree/master/Campaign
[Mailer]: https://github.com/remp2020/remp/tree/master/Mailer
[Sso]: https://github.com/remp2020/remp/tree/master/Sso
[Segments]: https://github.com/remp2020/remp/tree/master/Beam/go/cmd/segments
[Tracker]: https://github.com/remp2020/remp/tree/master/Beam/go/cmd/tracker
