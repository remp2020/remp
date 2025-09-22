## [4.2.0] - 2025-09-22

### [Beam]

- **DEPRECATED**: Commands for rollovers and data retention are being sunset in favor of Elasticsearch's ILM. remp/remp#1419
    - Configure your indices to use ILM policies, see base init script for Docker image [here](https://github.com/remp2020/remp/blob/master/Docker/elasticsearch/create-indexes.sh).
- Updated init script for Elasticsearch Docker image to use Index Lifecycle Management (ILM). remp/remp#1419
- Added parameters `published_from` and `published_to` into API call `/api/v2/articles/top` to filter returned articles by `published_at` datetime. remp/respekt#441
- Added parameters `article_published_from` and `article_published_to` into API call `/api/conversions` to filter returned conversions by article's `published_at` datetime. remp/respekt#441
- Fixed conversion filtering in the Articles - Conversions section; the sum and average fields always worked with all article conversions and ignored the time-based filter. remp/remp#1431
- Added parameters `published_from` and `published_to` into API call `/api/articles` to filter articles by `published_at` property. remp/respekt#442
- Added option to ignore content types and authors from Beam's newsletters (either via command options or env). remp/respekt#378
- Added parameter `content_type` to `/api/articles` and `article_content_type` to `/api/conversions` (filters response by `articles.content_type`). remp/respekt#441
- Fixed `/api/v2/articles/top` to return correct pageviews count if datetime parameter `to` is used. remp/respekt#441

### [Campaign]

- Optimized size of `showtime.php` response by trimming unused snippets where possible. remp/remp#1428
- Fixed HTML overlay banner not hiding overlay when the banner is closed. remp/remp#1424
- Fixed call on undefined object index during banner's closing remp/helpdesk#3773

### [Mailer]

- Fixed mail resend when Mailgun throws RuntimeException. remp/remp#1427
- Added option to use external metadata processors for external domains to `ArticleUrlParserGenerator`. remp/novydenik#1457
- Changed wordpress article link design in newsletters `TemplatesTrait::getArticleLinkTemplateFunction`. remp/remp#1433

[4.2.0]: https://github.com/remp2020/remp/compare/4.1.0...4.2.0

[Beam]: https://github.com/remp2020/remp/tree/master/Beam
[Campaign]: https://github.com/remp2020/remp/tree/master/Campaign
[Mailer]: https://github.com/remp2020/remp/tree/master/Mailer
[Sso]: https://github.com/remp2020/remp/tree/master/Sso
[Segments]: https://github.com/remp2020/remp/tree/master/Beam/go/cmd/segments
[Tracker]: https://github.com/remp2020/remp/tree/master/Beam/go/cmd/tracker
