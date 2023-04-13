# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/). Instead of change type headers, we use module names.

## [Unreleased]

### [Beam]

- Added an optimizations to speed up the statistics of articles on dashboard. remp/remp#1250
- Optimized `/api/articles` API call to eager load necessary relationships. remp/remp#1254
- Optimized `/articles/conversions` view to use article pageviews to calculate conversion rate instead of unique browsers from Journal API which can be slow. This may change the resulting values in conversion rate column. remp/remp#1253
- Added an optimizations for dashboard view to reduce obsolete database calls and speed up the filtering by property. remp/remp#1251
  - Added optional `env` configurations `ARTICLE_TRAFFIC_GRAPH_SHOW_INTERVAL_7D` and `ARTICLE_TRAFFIC_GRAPH_SHOW_INTERVAL_30D` to disable possibility of choosing longer time intervals in the article histogram.

## Archive

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

[Unreleased]: https://github.com/remp2020/remp/compare/2.0.0...master

