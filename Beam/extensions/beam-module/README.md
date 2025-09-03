# Beam

Beam admin provides a way to display real time usage stats on your website, aggregated article/author/conversion data and allows you to create user segments based on the tracked data.

### Technical description

##### Dashboard

Dashboard provides you with realtime data about your website's traffic. This data include:

* Number of users currently active on your website (based on measured activity in the last 10 minutes)
* Chart of pageviews split by source medium (e.g. email, search engine, internal traffic..) compared with the data
  from previous period (e.g. today vs this day last week).
* List of currently visited articles with extra properties as number of total visits, conversions and time people spent
  reading the article.

All of these statistics are provided realtime and updated every couple of seconds automatically.

For dashboard to work correctly, you need to:

* Include tracking [JS snippet](#javascript-snippet) on your website (this will populate today's chart)
* Push article metadata to Beam via [article tracking API](#post-apiarticlesupsert)
* Enable *timespent* tracking within this snippet (`rempConfig.tracker.timeSpent = { "enabled": true}` ).

Note: *Timespent* tracking is not mandatory. Dashboard can utilize regular pageview data instead to calculate number of concurrents. If you don't plan to track timespent, please see [this Telegraf configuration options](https://github.com/remp2020/remp/blob/master/Docker/telegraf/telegraf.conf#L78) so you can switch to pageview-based concurrents instead.

Dashboard allows you to display detail of each article with pageview-related chart containing histogram of visits
split by traffic source medium (same as in the main dashboard) and also displays article-related events.

###### Source medium labels

Optionally, you can configure labels for source mediums of your pageviews.
Labels will be shown in dashboard traffic (and article detail traffic) instead of real tracked values.

Currently, you can do it directly by editing database table `referer_medium_labels`.

##### Accounts

Accounts represent an access point to Beam data. Currently it's not utilized in any way, but in the future you'll be able
to configure access to data based on allowed *accounts* per each user.

As of now, it's OK to create one single account for your organization (e.g. with the name of your newspaper).

##### Properties

Property is a unit that you want to track (website, subapplication, mobile app). Account can have multiple properties.
In the future, Beam will allow to data for selected property.

Each *property* has a *token* that needs to be used within JS snippet (`rempConfig.token`).

##### Segments

Beam is able to provide behavioral segments based on the events tracked to the system by JS snippet and by backend APIs.

###### Regular segments

Regular segments can be created via web UI. Segment builder tool allows you to select multiple conditions based on which
the users **and browsers** (implying that also anonymous users are segmentable) will be checked against specific segment.

Segment builder tool always displays only options based on events that were already tracked. If you want to create
a segment with some specific condition, there should be at least one event in the system matching the condition.

###### Author segments

If some of your readers are interested only in specific of authors, Beam can identify such users and generate
*authors segments*. Each author segment contain users, which are returning back and reading mostly the author
that the segment belongs to.

As calculation of these segments it's computation intensive operation, author segments are computed on backend
by running `php artisan segments:compute-author-segments`.

Each segment contains users and browsers assigned to it according to criteria, which can be adjusted in
Beam admin settings page (`/author-segments/configuration`).

The command is not run by default (therefore no author segments exist). One has to run it manually when needed or
schedule it to recompute segments periodically. We recommend to schedule computation of segment every night and we
included Cron snippet within [Scheduled events](#author-segments-computation) section

###### Entities

Entities are experimental feature allowing you to define and then track objects into Beam. It's designed to help you
eventually create segments based on data in your system - by pushing the necessary data to Beam first.

Let's explain the feature on the example of tracking and generating segments based on delivery of print to your users.

To start using entities, first run `EntitySeeder` to populate necessary items into the database.

```bash
php artisan db:seed --class=EntitySeeder
```

Then, visit `/entities` and define what's structure of your entities. In our example, this new entity could be called
`print_delivery` and the parent entity `user` (as we want to track it for each user). Each entity has to belong
to some other entity - either to default `user` entity or to one of your entities.

We could add a couple of parameters for this entity:

* `delivery_date` (datetime) indicating when the delivery was attempted
* `successful` (boolean) indicating if the delivery was successful or not
* `issue_code` (string) indicating reference to issue that you attempted to deliver

Once saved, this entity will be propagated to your Tracker API and you can start tracking your entities. Tracker API
will also validate your payload so you don't accidentally push invalid entity.

You can test tracking new `print_delivery` by running following command (change `property_token` to yours):

```bash
curl -X POST \
  http://tracker.beam.remp.press/track/entity \
  -H 'Content-Type: application/json' \
  -d '{
  "entity_def": {
    "data": {
      "delivery_date": "2018-06-05T06:03:05Z",
      "issue_code": "20180603_DAILY"
    },
    "id": "1",
    "parent_id": "1122",
    "name": "print_delivery"
  },
  "system": {
    "property_token": "1a8feb16-3e30-4f9b-bf74-20037ea8505a",
    "time": "2018-06-05T06:03:05Z"
  }
}'
```

The description of API can be found in Tracker's `swagger.json`.

As of today, entities are not linked to Segment builder UI and are only stored to Elasticsearch for later use. As they're
experimental feature, the APIs might change in the future and it's not recommended for production use.

##### Newsletters

When Beam is configured to work with Mailer (`REMP_MAILER_ADDR` is populated), Beam allows you to configure automatic
generation of newsletters. Content of this email would be based on articles selected by Beam automatically also with the
option to personalize the email for each user separately.

When you want to add newsletter, following is required:

* *Name.* Name of this newsletter. Used only within newsletter listing in Beam admin.
* *Segment.* Target segment of users who should receive the newsletter. List of segments is provided by Mailer and may
  differ from the list of available segments directly in Beam (as Mailer might have registered other segment providers).
* *Generator.* Mailer generator to be used to generate emails. The generator in Mailer has to be registered with
  `best_performing_articles` key. By default Mailer provides such generator combined with very basic template.

    ```neon
    services:
        generator:
            class: Remp\MailerModule\Generators\GeneratorFactory
            setup:
                - registerGenerator('best_performing_articles', 'Best performing articles', \Remp\MailerModule\Generators\GenericBestPerformingArticlesGenerator())
    ```

* *Mail type.* Newly created email has to belong to specific mail type (newsletter) so Mailer can check whether user
  is subscribed to receive this email or not. You should select which newsletter this is.
* *Criterion.* What should be used as a criteria for selecting (and ordering) articles for newsletter (e.g. number
  of pageviews, amount of time spent reading the article)
* *Timespan.* What should be the time range for selected *criterion*. This should be similar or equal to your recurrence
  so you can send best articles in the last 24 hours (timespan) every 24 hours (recurrence).
* *How many articles.* How many articles should be selected into the email.
* *Personalized content.* Whether all users should receive the same newsletter or whether everyone should receive newsletter
  with articles they haven't read yet.
* *Email subject.* What's subject of newsletter (e.g. Dennik N - Daily newsletter).
* *Email from.* Who's the sender of newsletter (e.g. info@example.com)
* *Start date.* When the email should be sent.
* *Recurrence.* If the email should be recurring and how often it should be sent.

Once configured, you should run (and schedule) email sending command:

```
php artisan newsletters:send
```

##### Articles

Beam provides pageview-related and conversion-related stats for articles. To be able to display this data, Beam needs
you to push Article and Conversion metadata first. Please see [Article tracking](#post-apiarticlesupsert) and
[Conversion tracking](#post-apiconversionsupsert) sections to see definition of API calls you should request from your CMS/CRM.

If you have your [Laravel scheduler](#laravel-scheduler) enabled and you track your pageviews via
[JS snippet](#javascript-snippet), you can now display statistics for articles:

* *Conversion stats.* Displaying number of conversions reported for given article, amount earned and averages. All
  filterable by authors and sections. You can filter data based on publish date of articles and conversion dates.
* *Pageview stats.* Displating number of pageviews and time spent reading for given article (if being tracked) and related
  averages. All filterable by authors and sections. You can filter data based on publish date of articles.

##### Conversions

Conversions section provides information about single conversions that you tracked via
[Conversion tracking](#post-apiconversionsupsert) and lets you filter conversions based on authors and sections.

Section also includes experimental *User path* feature which attempts to extract aggregate of events that occur before
the conversion happens. In its current state it only provides counts and share of events that happen right before
the payment.

To start using the feature, please run (and schedule) following command to create aggregates first:

```bash
php artisan conversions:aggregate-events
```

##### Authors

This section provides similar view as [Articles](#articles) section does - with the difference that here you can see
aggregated data for each author. When you select a specific author, Beam displays article statistics for given author.

You can filter the data based on publish date of articles.

##### Visitors

Visitors section provides aggregate view on where your visitors are coming from and from what devices. Section allows
you to filter the results based on date range of targeted visit and based on subscription status - so you can
differentiate where your subscribers are coming from and where you non-paying users are coming from.

* *Devices.* Section allows you to see hardware-related statistics of your visitors (with visit counts) based on
  browsers and specific devices that were extracted from user agents of your visitors.
* *Sources.* Section allows you to see referer-based statistics and see absolute numbers for given source (host) or
  medium (e.g. search, internal traffic, etc.)

### API Documentation

Beam itself serves as a tool for tracking the data and displaying them in a nice fashion. Beam admin has couple of APIs
for providing metadata for Articles and Conversions that need to be stored persistently in MySQL

*Note: All Elasticsearch data should be always treated as non-persistent and backed up if necessary.*

All examples use `http://beam.remp.press` as a base domain. Please change the host to the one you use
before executing the examples.

All examples use `XXX` as a default value for authorization token, please replace it with the
real token API token that can be acquired in the REMP SSO.

All requests should contain (and be compliant) with the follow HTTP headers.

```
Content-Type: application/json
Accept: application/json
Authorization: Bearer REMP_SSO_API_TOKEN
```

API responses can contain following HTTP codes:

| Value | Description |
| --- | --- |
| 200 OK | Successful response, default value |
| 400 Bad Request | Invalid request (missing required parameters) |
| 403 Forbidden | The authorization failed (provided token was not valid) |
| 404 Not found | Referenced resource wasn't found |

If possible, the response includes `application/json` encoded payload with message explaining
the error further.

---

##### POST `/api/articles/upsert` ([DEPRECATED - click here for v2](#post-apiv2articlesupsert))

Your CMS should track all article-related changes to Beam so Beam knows about the article, who's the author and to which
sections it belongs to. Once the article data is available to Beam, system starts to link various statistics that
you've tracked with your JS snippet for given article (e.g. pageviews, time spent, reading progress) and
data related to Beam (e.g. A/B testing of titles).

##### *Headers:*

| Name | Value | Required | Description |
| --- |---| --- | --- |
| Authorization | Bearer *String* | yes | API token. |
| Content-Type | application/json | yes |  |
| Accept | application/json | yes |  |

##### *Body:*

```json5
{
    "articles": [
        {
            "external_id": "74565321", // String; Required; ID of article in your CMS,
            "property_uuid": "7855a8d9-d445-4dc0-8414-dbd7dfd326f9", // String; Required; Beam property token, you can get it in Beam admin - Properties,
            "title": "10 things you need to know", // String; Required; Primary title of the article,
            "titles": { // Optional; If A/B test of titles is used, you can track the titles here
                "A": "10 things you need to know", // Title of variant being tracked with key "A"
                "B": "10 things everyone hides from you" // Title of variant being tracked with key "B"
            },
            "url": "http://example.com/74565321", // Public and valid URL of the article,
            "content_type": "blog", // String; Optional; Content type of the article. Default value "article" used if not provided.
            "authors": [ // Optional
                "Jon Snow" // Name of the author
            ],
            "sections": [ // Optional
                "Opinions" // Name of the section
            ],
            "published_at": "2018-06-05T06:03:05Z" // RFC3339 formatted datetime
        }
    ]
}
```

##### *Examples:*

<details>
<summary>curl</summary>

```shell
curl -X POST \
  http://beam.remp.press/api/articles/upsert \
  -H 'Accept: application/json' \
  -H 'Authorization: Bearer XXX' \
  -H 'Content-Type: application/json' \
  -d '{
    "articles": [
        {
            "external_id": "74565321",
            "property_uuid": "1a8feb16-3e30-4f9b-bf74-20037ea8505a",
            "title": "10 things you need to know",
            "titles": {
                "A": "10 things you need to know",
                "B": "10 things everyone hides from you"
            },
            "url": "http://example.com/74565321",
            "content_type": "blog",
            "authors": [
                "Jon Snow"
            ],
            "sections": [
                "Opinions"
            ],
            "tags": [
                "Elections 2020"
            ],
            "published_at": "2018-06-05T06:03:05Z"
        }
    ]
}'
```

</details>

<details>
<summary>raw PHP</summary>

```php
$payload = [
    "articles" => [
        [
            "external_id" => "74565321",
            "property_uuid" => "1a8feb16-3e30-4f9b-bf74-20037ea8505a",
            "title" => "10 things you need to know",
            "titles" => [
                "A" => "10 things you need to know",
                "B" => "10 things everyone hides from you"
            ],
            "url" => "http://example.com/74565321",
            "content_type" => "blog",
            "authors" => [
                "Jon Snow"
            ],
            "sections" => [
                "Opinions"
            ],
            "tags" => [
                "Elections 2020"
            ],
            "published_at" => "2018-06-05T06:03:05Z",
        ]
    ]
];
$jsonPayload = json_encode($payload);
$context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: type=application/json\r\n"
                . "Accept: application/json\r\n"
                . "Content-Length: " . strlen($jsonPayload) . "\r\n"
                . "Authorization: Bearer XXX",
            'content' => $jsonPayload,
        ]
    ]
);
$response = file_get_contents("http://beam.remp.press/api/articles/upsert ", false, $context);
// process response (raw JSON string)
```

</details>

##### *Response:*

```json5
{
    "data": [
        {
            "id": 123902,
            "external_id": "74565321",
            "property_uuid": "1a8feb16-3e30-4f9b-bf74-20037ea8505a",
            "title": "10 things you need to know",
            "url": "http://example.com/74565321",
            "content_type": "blog",
            "image_url": null,
            "published_at": "2018-06-05 06:03:05",
            "pageviews_all": 0,
            "pageviews_signed_in": 0,
            "pageviews_subscribers": 0,
            "timespent_all": 0,
            "timespent_signed_in": 0,
            "timespent_subscribers": 0,
            "created_at": "2019-05-17 11:43:04",
            "updated_at": "2019-05-17 11:43:04",
            "authors": [
                {
                    "name": "Jon Snow",
                    "created_at": "2019-05-17 11:43:04",
                    "updated_at": "2019-05-17 11:43:04"
                }
            ],
            "sections": [
                {
                    "name": "Opinions",
                    "created_at": "2019-05-17 11:43:04",
                    "updated_at": "2019-05-17 11:43:04"
                }
            ],
            "tags": [
                {
                    "name": "Elections 2020",
                    "created_at": "2019-05-17 11:43:04",
                    "updated_at": "2019-05-17 11:43:04"
                }
            ],
        }
    ]
}
```

Any create/update matching is based on the article's `external_id`. You're free to update the article as many times
as you want.

---

##### POST `/api/v2/articles/upsert`

Your CMS should track all article-related changes to Beam so Beam knows about the article, who's the author and to which sections it belongs to. Once the article data is available to Beam, system starts to link various statistics that you've tracked with your JS snippet for given article (e.g. pageviews, time spent, reading progress) and data related to Beam (e.g. A/B testing of titles).

##### *Headers:*

| Name | Value | Required | Description |
| --- |---| --- | --- |
| Authorization | Bearer *String* | yes | API token. |
| Content-Type | application/json | yes |  |
| Accept | application/json | yes |  |

##### *Body:*

```json5
{
    "articles": [
        {
            "external_id": "74565321", // String; Required; ID of article in your CMS,
            "property_uuid": "7855a8d9-d445-4dc0-8414-dbd7dfd326f9", // String; Required; Beam property token, you can get it in Beam admin - Properties,
            "title": "10 things you need to know", // String; Required; Primary title of the article,
            "titles": { // Optional; If A/B test of titles is used, you can track the titles here
                "A": "10 things you need to know", // Title of variant being tracked with key "A"
                "B": "10 things everyone hides from you" // Title of variant being tracked with key "B"
            },
            "url": "http://example.com/74565321", // Public and valid URL of the article,
            "content_type": "blog", // String; Optional; Content type of the article. Default value "article" used if not provided.
            "authors": [ // Optional
                {
                    "external_id": "1", // String; Required; External id of the author
                    "name": "Jon Snow" // String; Required; Name of the author
                }
            ],
            "sections": [ // Optional
                {
                    "external_id": "1", // String; Required; External id of the section
                    "name": "Opinions" // String; Required; Name of the section
                }
            ],
            "tags": [ // Optional
                {
                    "external_id": "1", // String; Required; External id of the tag
                    "name": "Elections 2020", // String; Required; Name of the tag,
                    "categories": [ // Optional
                        {
                            "external_id": "1", // String; Required; External id of the tag category
                            "name": "USA" // String; Required; Name of the tag category
                        }
                    ]
                }
            ],
            "published_at": "2018-06-05T06:03:05Z" // RFC3339 formatted datetime
        }
    ]
}
```

##### *Examples:*

<details>
<summary>curl</summary>

```shell
curl -X POST \
  http://beam.remp.press/api/v2/articles/upsert \
  -H 'Accept: application/json' \
  -H 'Authorization: Bearer XXX' \
  -H 'Content-Type: application/json' \
  -d '{
    "articles": [
        {
            "external_id": "74565321",
            "property_uuid": "1a8feb16-3e30-4f9b-bf74-20037ea8505a",
            "title": "10 things you need to know",
            "titles": {
                "A": "10 things you need to know",
                "B": "10 things everyone hides from you"
            },
            "url": "http://example.com/74565321",
            "content_type": "blog",
            "authors": [
                {
                    "external_id": "1",
                    "name": "Jon Snow"
                }
            ],
            "sections": [
                {
                    "external_id": "1",
                    "name": "Opinions"
                }
            ],
            "tags": [
                {
                    "external_id": "1",
                    "name": "Elections 2020",
                    "categories": [
                        {
                            "external_id": "1",
                            "name": "USA"
                        }
                    ]
                }
            ],
            "published_at": "2018-06-05T06:03:05Z"
        }
    ]
}'
```

</details>

<details>
<summary>raw PHP</summary>

```php
$payload = [
    "articles" => [
        [
            "external_id" => "74565321",
            "property_uuid" => "1a8feb16-3e30-4f9b-bf74-20037ea8505a",
            "title" => "10 things you need to know",
            "titles" => [
                "A" => "10 things you need to know",
                "B" => "10 things everyone hides from you"
            ],
            "url" => "http://example.com/74565321",
            "content_type" => "blog",
            "authors" => [
                [
                    "external_id" => "1",
                    "name" => "Jon Snow"
                ]
            ],
            "sections" => [
                [
                    "external_id" => "1",
                    "name" => "Opinions"
                ]
            ],
            "tags" => [
                [
                    "external_id" => "1",
                    "name" => "Elections 2020" ,
                    "categories" => [
                        [
                            "external_id" => "1",
                            "name" => "USA"
                        ]
                    ]
                ]
            ],
            "published_at" => "2018-06-05T06:03:05Z",
        ]
    ]
];
$jsonPayload = json_encode($payload);
$context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: type=application/json\r\n"
                . "Accept: application/json\r\n"
                . "Content-Length: " . strlen($jsonPayload) . "\r\n"
                . "Authorization: Bearer XXX",
            'content' => $jsonPayload,
        ]
    ]
);
$response = file_get_contents("http://beam.remp.press/api/v2/articles/upsert ", false, $context);
// process response (raw JSON string)
```

</details>

##### *Response:*

```json5
{
    "data": [
        {
            "id": 123902,
            "external_id": "74565321",
            "property_uuid": "1a8feb16-3e30-4f9b-bf74-20037ea8505a",
            "title": "10 things you need to know",
            "url": "http://example.com/74565321",
            "content_type": "blog",
            "image_url": null,
            "published_at": "2018-06-05 06:03:05",
            "pageviews_all": 0,
            "pageviews_signed_in": 0,
            "pageviews_subscribers": 0,
            "timespent_all": 0,
            "timespent_signed_in": 0,
            "timespent_subscribers": 0,
            "created_at": "2019-05-17 11:43:04",
            "updated_at": "2019-05-17 11:43:04",
            "authors": [
                {
                    "external_id": "1",
                    "name": "Jon Snow",
                    "created_at": "2019-05-17 11:43:04",
                    "updated_at": "2019-05-17 11:43:04"
                }
            ],
            "sections": [
                {
                    "external_id": "1",
                    "name": "Opinions",
                    "created_at": "2019-05-17 11:43:04",
                    "updated_at": "2019-05-17 11:43:04"
                }
            ],
            "tags": [
                {
                    "external_id": "1",
                    "name": "Elections 2020",
                    "created_at": "2019-05-17 11:43:04",
                    "updated_at": "2019-05-17 11:43:04",
                    "tag_categories": [
                        {
                            "external_id": "1",
                            "name": "USA"
                        }
                    ]
                }
            ],
        }
    ]
}
```

Any create/update matching is based on the article's `external_id`. You're free to update the article as many times as you want.

---

##### POST `/api/articles/read`

List already read articles based on filter.

##### *Headers:*

| Name | Value | Required | Description |
| --- |---| --- | --- |
| Authorization | Bearer *String* | yes | API token. |
| Content-Type | application/json | yes |  |
| Accept | application/json | yes |  |

##### *Body:*

```json5
{
    "user_id": "5", // String; Required if browser_id not set; ID of user in your CMS,
    "browser_id": "qwerty123", // String; Required if user_id not set; ID of browser that made article pageview,
    "from": "2021-06-05T06:03:05Z", // RFC3339-based time from which to take pageviews
    "to": "2021-07-05T06:03:05Z" // RFC3339-based time to which to take pageviews
}
```

##### *Examples:*

<details>
<summary>curl</summary>

```shell
curl -X POST \
  http://beam.remp.press/api/articles/read \
  -H 'Accept: application/json' \
  -H 'Authorization: Bearer XXX' \
  -H 'Content-Type: application/json' \
  -d '{
    "user_id": "5",
    "browser_id": "qwerty123",
    "from": "2021-06-05T06:03:05Z",
    "to": "2021-07-05T06:03:05Z"
}'
```

</details>

<details>
<summary>raw PHP</summary>

```php
$payload = [
    "user_id" => "5",
    "browser_id" => "qwerty123",
    "from" => "2021-06-05T06:03:05Z",
    "to" => "2021-07-05T06:03:05Z"
];
$jsonPayload = json_encode($payload);
$context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: type=application/json\r\n"
                . "Accept: application/json\r\n"
                . "Content-Length: " . strlen($jsonPayload) . "\r\n"
                . "Authorization: Bearer XXX",
            'content' => $jsonPayload,
        ]
    ]
);
$response = file_get_contents("http://beam.remp.press/api/articles/read ", false, $context);
// process response (raw JSON string)
```

</details>

##### *Response:*

```json5
[
    {
        "article_id": "2546241",
        "browser_id": "qwerty123",
        "time": "2021-06-10T06:03:05Z",
        "user_id": "5"
    },
    {
        "article_id": "2551289",
        "browser_id": "qwerty123",
        "time": "2021-06-09T06:03:05Z",
        "user_id": "5"
    }
]
```

---

##### POST `api/conversions/upsert`

Beam admin provides statistics about article/author performance. One of the metrics used are conversions.
This endpoint stores minimal conversion data. Extended data should be additionally
tracked via Tracker API (see `/track/commerce` definition in Tracker's `swagger.json` file).

##### *Headers:*

| Name | Value | Required | Description |
| --- |---| --- | --- |
| Authorization | Bearer *String* | yes | API token. |
| Content-Type | application/json | yes |  |
| Accept | application/json | yes |  |

##### *Body:*

```json5
{
    "conversions": [
        {
            "article_external_id": "74565321", // String; Required; ID of article in your CMS,
            "transaction_id": "8743320112", // String; Required; ID of transaction (unique for each transaction),
            "amount": 17.99, // Numeric; Required; Nominal amount of the transaction, e.g. 10.0
            "currency": "EUR", // String; Required; Currency of the transaction, e.g. ",EUR"
            "paid_at": "2018-06-05T12:03:05Z", // String; Required; RFC3339 formatted datetime with date of the transaction,
            "user_id": "74412" // Optional; Identifier of user who made a transaction
        }
    ]
}
```

##### *Examples*:

<details>
<summary>curl</summary>

```shell
curl -X POST \
  http://beam.remp.press/api/conversions/upsert \
  -H 'Accept: application/json' \
  -H 'Authorization: Bearer XXX' \
  -H 'Content-Type: application/json' \
  -d '{
    "conversions": [
        {
            "article_external_id": "74565321",
            "transaction_id": "8743320112",
            "amount": 17.99,
            "currency": "EUR",
            "paid_at": "2018-06-05T12:03:05Z",
            "user_id": "74412"
        }
    ]
}'
```

</details>

<details>
<summary>raw PHP</summary>

```php
$payload = [
    "conversions" => [
        [
            "article_external_id" => "74565321",
            "transaction_id" => "8743320112",
            "amount" => 17.99,
            "currency" => "EUR",
            "paid_at" => "2018-06-05T12:03:05Z",
            "user_id" => "74412"
        ]
    ]
];
$jsonPayload = json_encode($payload);
$context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: type=application/json\r\n"
                . "Accept: application/json\r\n"
                . "Content-Length: " . strlen($jsonPayload) . "\r\n"
                . "Authorization: Bearer XXX",
            'content' => $jsonPayload,
        ]
    ]
);
$response = file_get_contents("http://beam.remp.press/api/conversions/upsert ", false, $context);
// process response (raw JSON string)
```

</details>

##### *Response:*

```json5
{
    "data": [
        {
            "id": 67297,
            "article_id": 123902,
            "user_id": "74412",
            "amount": 17.99,
            "currency": "EUR",
            "paid_at": "2018-06-05 12:03:05",
            "transaction_id": "8743320112",
            "events_aggregated": true,
            "created_at": "2019-05-17 11:47:18",
            "updated_at": "2019-05-17 12:00:21"
        }
    ]
}
```

---

##### POST `api/articles/top`

Beam admin provides statistics about article performance. This endpoint return top articles by pageviews.
You can filter articles by content type, sections, authors, tags or tag categories.

##### *Headers:*

| Name | Value | Required | Description |
| --- |---| --- | --- |
| Authorization | Bearer *String* | yes | API token. |
| Content-Type | application/json | yes |  |
| Accept | application/json | yes |  |

##### *Body:*

```json5
{
	"from": "2020-08-10T08:09:18+00:00", // RFC3339-based start time from which to take pageviews to this today
	"limit": 3, // limit how many top articles this endpoint returns
	"content_type": "article", // String; OPTIONAL; filters articles by content_type
  "published_from": "2020-08-10T08:09:18+00:00", // RFC3339-based time; OPTIONAL; filter articles according theirs publishing date
	"sections": { // OPTIONAL; filters from which sections take articles (use either external_id or name arrays, not both)
		"external_id": ["Section external id"], // String; section external IDs
		"name": ["Section title"] // String; section names
	},
	"authors": { // OPTIONAL; filters from which authors take articles (use either external_id or name arrays, not both)
		"external_id": ["author external id"], // String; author external IDs
		"name": ["author name"] // String; author names
	},
	"tags": { // OPTIONAL; filters articles with tags (use either external_id or name arrays, not both)
		"external_id": ["tag external id"], // String; tag external IDs
		"name": ["tag name"] // String; tag names
	},
	"tag_categories": { // OPTIONAL; filters articles with tag categories (use either external_id or name arrays, not both)
		"external_id": ["tag category external id"], // String; tag category external IDs
		"name": ["tag category name"] // String; tag category names
	}
}
```

##### *Examples*:

<details>
<summary>curl</summary>

```shell
curl --location --request POST 'http://beam.remp.press/api/articles/top' \
--header 'Content-Type: application/json' \
--header 'Accept: application/json' \
--header 'Authorization: Bearer XXX' \
--data-raw '{
	"from": "2020-08-10T08:09:18+00:00",
	"limit": 3,
	"content_type": "article",
	"sections": {
		"external_id": ["1"]
	},
	"authors": {
		"external_id": ["123"]
	},
	"tags": {
		"external_id": ["10"]
	},
	"tag_categories": {
		"external_id": ["1"]
	}
}'
```

</details>

<details>
<summary>raw PHP</summary>

```php
$payload = [
	"from" => "2020-08-10T08:09:18+00:00",
	"limit" => 3,
	"content_type" => "article",
	"sections" => [
		"name" => ["Blog"]
	],
	"authors" => [
		"name" => ["John Doe"]
	],
	"tags" => [
		"name" => ["News"]
	],
	"tag_categories" => [
		"name" => ["Europe"]
	]
];
$jsonPayload = json_encode($payload);
$context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: type=application/json\r\n"
                . "Accept: application/json\r\n"
                . "Content-Length: " . strlen($jsonPayload) . "\r\n"
                . "Authorization: Bearer XXX",
            'content' => $jsonPayload,
        ]
    ]
);
$response = file_get_contents("http://beam.remp.press/api/articles/top", false, $context);
// process response (raw JSON string)
```

</details>

##### *Response:*

```json5
[
    {
        "external_id": "1411843",
        "pageviews": 274
    },
    {
        "external_id": "1443988",
        "pageviews": 150
    },
    {
        "external_id": "1362607",
        "pageviews": 45
    }
]
```

---

##### POST `api/v2/articles/top`

Beam admin provides statistics about article performance. This endpoint return top articles by pageviews.
You can filter articles by content type, sections, authors, tags or tag categories.

You can combine multiple filters for each filter category. Filters in and between categories are joined with `AND`, values in filter are joined with `OR`.

##### *Headers:*

| Name | Value | Required | Description |
| --- |---| --- | --- |
| Authorization | Bearer *String* | yes | API token. |
| Content-Type | application/json | yes |  |
| Accept | application/json | yes |  |

##### *Body:*

```json5
{
	"from": "2020-08-10T08:09:18+00:00", // RFC3339-based start time from which to take pageviews
	"to": "2020-08-17T08:09:18+00:00", // RFC3339-based end time to which to take pageviews (if missing, today is used)
	"published_from": "2020-07-01T00:00:00+00:00", // RFC3339-based start time after which should article included in results be published
	"published_to": "2020-07-31T23:59:59+00:00", // RFC3339-based end time to which should article included in results be published (if missing, today is used)
	"limit": 3, // limit how many top articles this endpoint returns
	"content_type": "article", // String; OPTIONAL; filters articles by content_type
	"sections": [ // OPTIONAL; filters from which sections take articles (use either external_id or name arrays, not both); filters joined with AND
		{"external_ids": ["Section external ids"]}, // String; section external IDs; values joined with OR
		{"names": ["Section titles"]} // String; section names; joined with OR; values joined with OR
	],
	"authors": [ // OPTIONAL; filters from which authors take articles (use either external_id or name arrays, not both); filters joined with AND
		{"external_ids": ["author external ids"]}, // String; author external IDs; values joined with OR
		{"names": ["author names"]} // String; author names; joined with OR; values joined with OR
	],
	"tags": [ // OPTIONAL; filters articles with tags (use either external_id or name arrays, not both); filters joined with AND
		{"external_ids": ["tag external ids"]}, // String; tag external IDs; values joined with OR
		{"names": ["tag names"]} // String; tag names; values joined with OR
	],
	"tag_categories": [ // OPTIONAL; filters articles with tag categories (use either external_id or name arrays, not both); filters joined with AND
		{"external_ids": ["tag category external ids"]}, // String; tag category external IDs; values joined with OR
		{"names": ["tag category names"]} // String; tag category names; values joined with OR
	]
}
```

##### *Examples*:

<details>
<summary>curl</summary>

```shell
curl --location --request POST 'http://beam.remp.press/api/v2/articles/top' \
--header 'Content-Type: application/json' \
--header 'Accept: application/json' \
--header 'Authorization: Bearer XXX' \
--data-raw '{
	"from": "2020-08-10T08:09:18+00:00",
	"to": "2020-08-17T08:09:18+00:00",
	"published_from": "2020-07-01T00:00:00+00:00",
	"published_to": "2020-07-31T23:59:59+00:00",
	"limit": 3,
	"content_type": "article",
	"sections": [
		{"external_ids": ["1", "2"]},
		{"names": ["World"]}
	],
	"authors": [
		{"external_ids": ["123"]}
	],
	"tags": [
		{"external_ids": ["10"]}
	],
	"tag_categories": [
		{"external_ids": ["1"]}
	]
}'
```

</details>

<details>
<summary>raw PHP</summary>

```php
$payload = [
	// pageviews from single week
	"from" => "2020-08-10T08:09:18+00:00",
	"to" => "2020-08-17T08:09:18+00:00",
	// of articles published previous month
	"published_from" => "2020-07-01T00:00:00+00:00",
	"published_to" => "2020-07-31T23:59:59+00:00",
	"limit" => 3,
	"content_type" => "article",
	"sections" => [
		["names" => ["Blog"]]
	],
	"authors" => [
		["names" => ["John Doe"]]
	],
	"tags" => [
		["names" => ["News"]]
	],
	"tag_categories" => [
		["names" => ["Europe"]]
	]
];
$jsonPayload = json_encode($payload);
$context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: type=application/json\r\n"
                . "Accept: application/json\r\n"
                . "Content-Length: " . strlen($jsonPayload) . "\r\n"
                . "Authorization: Bearer XXX",
            'content' => $jsonPayload,
        ]
    ]
);
$response = file_get_contents("http://beam.remp.press/api/v2/articles/top", false, $context);
// process response (raw JSON string)
```

</details>

##### *Response:*

```json5
[
    {
        "external_id": "1411843",
        "pageviews": 274
    },
    {
        "external_id": "1443988",
        "pageviews": 150
    },
    {
        "external_id": "1362607",
        "pageviews": 45
    }
]
```

---

##### POST `api/authors/top`

Beam admin provides statistics about author performance. This endpoint return top authors by pageviews.
You can filter authors by content type, sections, tags or tag categories.

##### *Headers:*

| Name | Value | Required | Description |
| --- |---| --- | --- |
| Authorization | Bearer *String* | yes | API token. |
| Content-Type | application/json | yes |  |
| Accept | application/json | yes |  |

##### *Body:*

```json5
{
	"from": "2020-08-10T08:14:09+00:00", // RFC3339-based start datetime from which to take pageviews to this today
	"limit": 3, // limit how many top authors this endpoint returns
	"content_type": "article", // String; OPTIONAL; filters articles by content_type
	"sections": { // OPTIONAL; filters from which sections take articles (use either external_id or name arrays, not both)
		"external_id": ["Section external id"], // String; section external IDs
		"name": ["Section title"] // String; section names
	},
	"tags": { // OPTIONAL; filters articles with tags (use either external_id or name arrays, not both)
		"external_id": ["Tag external id"], // String; tag external IDs
		"name": ["Tag title"] // String; tag names
	},
	"tag_categories": { // OPTIONAL; filters articles with tag categories (use either external_id or name arrays, not both)
		"external_id": ["tag category external id"], // String; tag category external IDs
		"name": ["tag category name"] // String; tag category names
	}
}
```

##### *Examples*:

<details>
<summary>curl</summary>

```shell
curl --location --request POST 'http://beam.remp.press/api/authors/top' \
--header 'Content-Type: application/json' \
--header 'Accept: application/json' \
--header 'Authorization: Bearer XXX' \
--data-raw '{
	"from": "2020-08-10T08:14:09+00:00",
	"limit": 3,
	"content_type": "article",
	"sections": {
	    "external_id": ["22"]
	},
	"tags": {
	    "external_id": ["10"]
	},
	"tag_categories": {
		"external_id": ["1"]
	}
}'
```

</details>

<details>
<summary>raw PHP</summary>

```php
$payload = [
	"from" => "2020-08-10T08:14:09+00:00",
	"limit" => 3,
	"content_type" => "article",
	"sections" => [
		"name" => ["Blog"]
	],
	"tags" => [
		"name" => ["News"]
	],
	"tag_categories" => [
		"name" => ["Europe"]
	]
];
$jsonPayload = json_encode($payload);
$context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: type=application/json\r\n"
                . "Accept: application/json\r\n"
                . "Content-Length: " . strlen($jsonPayload) . "\r\n"
                . "Authorization: Bearer XXX",
            'content' => $jsonPayload,
        ]
    ]
);
$response = file_get_contents("http://beam.remp.press/api/authors/top", false, $context);
// process response (raw JSON string)
```

</details>

##### *Response:*

```json5
[
    {
        "external_id": 100,
        "name": "Example Author",
        "pageviews": 23000
    }
]
```

---

##### POST `api/v2/authors/top`

Beam admin provides statistics about author performance. This endpoint return top authors by pageviews.
You can filter authors by content type, sections, tags or tag categories.

You can combine multiple filters for each filter category. Filters in and between categories are joined with `AND`, values in filter are joined with `OR`.

##### *Headers:*

| Name | Value | Required | Description |
| --- |---| --- | --- |
| Authorization | Bearer *String* | yes | API token. |
| Content-Type | application/json | yes |  |
| Accept | application/json | yes |  |

##### *Body:*

```json5
{
	"from": "2020-08-10T08:14:09+00:00", // RFC3339-based start datetime from which to take pageviews to this today 
	"limit": 3, // limit how many top authors this endpoint returns
	"content_type": "article", // String; OPTIONAL; filters articles by content_type
	"sections": [ // OPTIONAL; filters from which sections take articles (use either external_id or name arrays, not both); filters joined with AND
		{"external_ids": ["Section external ids"]}, // String; section external IDs; values joined with OR
		{"names": ["Section titles"]} // String; section names; values joined with OR
	],
	"tags": [ // OPTIONAL; filters articles with tags (use either external_id or name arrays, not both); filters joined with AND
		{"external_ids": ["Tag external ids"]}, // String; tag external IDs; values joined with OR
		{"names": ["Tag titles"]} // String; tag names; values joined with OR
	],
	"tag_categories": [ // OPTIONAL; filters articles with tag categories (use either external_id or name arrays, not both); filters joined with AND
		{"external_ids": ["tag category external ids"]}, // String; tag category external IDs; values joined with OR 
		{"names": ["tag category names"]} // String; tag category names; values joined with OR
	]
}
```

##### *Examples*:

<details>
<summary>curl</summary>

```shell
curl --location --request POST 'http://beam.remp.press/api/v2/authors/top' \
--header 'Content-Type: application/json' \
--header 'Accept: application/json' \
--header 'Authorization: Bearer XXX' \
--data-raw '{
	"from": "2020-08-10T08:14:09+00:00",
	"limit": 3,
	"content_type": "article",
	"sections": [
		{"external_ids": ["22"]}
	],
	"tags": [
		{"external_ids": ["10"]}
	],
	"tag_categories": [
		{"external_ids": ["1"]}
	]
}'
```

</details>

<details>
<summary>raw PHP</summary>

```php
$payload = [
	"from" => "2020-08-10T08:14:09+00:00",
	"limit" => 3,
	"content_type" => "article",
	"sections" => [
		["names" => ["Blog"]]
	],
	"tags" => [
		["names" => ["News"]]
	],
	"tag_categories" => [
		["names" => ["Europe"]]
	]
];
$jsonPayload = json_encode($payload);
$context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: type=application/json\r\n"
                . "Accept: application/json\r\n"
                . "Content-Length: " . strlen($jsonPayload) . "\r\n"
                . "Authorization: Bearer XXX",
            'content' => $jsonPayload,
        ]
    ]
);
$response = file_get_contents("http://beam.remp.press/api/v2/authors/top", false, $context);
// process response (raw JSON string)
```

</details>

##### *Response:*

```json5
[
    {
        "external_id": 100,
        "name": "Example Author",
        "pageviews": 23000
    }
]
```

---

##### POST `api/tags/top`

Beam admin provides statistics about tag performance. This endpoint return top post tags by pageviews.
You can filter tags by content type, sections, authors or tag categories.

##### *Headers:*

| Name | Value | Required | Description |
| --- |---| --- | --- |
| Authorization | Bearer *String* | yes | API token. |
| Content-Type | application/json | yes |  |
| Accept | application/json | yes |  |

##### *Body:*

```json5
{
	"from": "2020-08-10T08:14:09+00:00", // RFC3339-based start datetime from which to take pageviews to this today
	"limit": 3, // limit how many top tags this endpoint returns
	"content_type": "article", // String; OPTIONAL; filters articles by content_type
	"sections": { // OPTIONAL; filters from which sections take articles (use either external_id or name arrays, not both)
		"external_id": ["Section external id"], // String; section external IDs
		"name": ["Section title"] // String; section external_id
	},
	"authors": { // OPTIONAL; filters from which authors take articles (use either external_id or name arrays, not both)
		"external_id": ["author external id"], // String; section external IDs
		"name": ["author name"] // String; section external_id
	},
	"tag_categories": { // OPTIONAL; filters articles with tag categories (use either external_id or name arrays, not both)
		"external_id": ["tag category external id"], // String; tag category external IDs
		"name": ["tag category name"] // String; tag category names
	}
}
```

##### *Examples*:

<details>
<summary>curl</summary>

```shell
curl --location --request POST 'http://beam.remp.press/api/tags/top' \
--header 'Content-Type: application/json' \
--header 'Accept: application/json' \
--header 'Authorization: Bearer XXX' \
--data-raw '{
	"from": "2020-08-10T08:14:09+00:00",
	"limit": 3,
	"content_type": "article",
	"sections": {
		"external_id": ["1"]
	},
	"authors": {
		"external_id": ["123"]
	},
	"tag_categories": {
		"external_id": ["1"]
	}
}'
```

</details>

<details>
<summary>raw PHP</summary>

```php
$payload = [
	"from" => "2020-08-10T08:14:09+00:00",
	"limit" => 3,
	"content_type" => "article",
	"sections" => [
		"name" => ["Blog"]
	],
	"authors" => [
		"name" => ["John Doe"]
	],
	"tag_categories" => [
		"name" => ["Europe"]
	]
];
$jsonPayload = json_encode($payload);
$context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: type=application/json\r\n"
                . "Accept: application/json\r\n"
                . "Content-Length: " . strlen($jsonPayload) . "\r\n"
                . "Authorization: Bearer XXX",
            'content' => $jsonPayload,
        ]
    ]
);
$response = file_get_contents("http://beam.remp.press/api/tags/top", false, $context);
// process response (raw JSON string)
```

</details>

##### *Response:*

```json5
[
    {
        "name": "ekonomika",
        "pageviews": 4000,
        "external_id": "1"
    },
    {
        "name": "covid19",
        "pageviews": 3000,
        "external_id": "2"
    },
    {
        "name": "brexit",
        "pageviews": 2000,
        "external_id": "3"
    }
]
```

---

##### POST `api/v2/tags/top`

Beam admin provides statistics about tag performance. This endpoint return top post tags by pageviews.
You can filter tags by content type, sections, authors or tag categories.

You can combine multiple filters for each filter category. Filters in and between categories are joined with `AND`, values in filter are joined with `OR`.

##### *Headers:*

| Name | Value | Required | Description |
| --- |---| --- | --- |
| Authorization | Bearer *String* | yes | API token. |
| Content-Type | application/json | yes |  |
| Accept | application/json | yes |  |

##### *Body:*

```json5
{
	"from": "2020-08-10T08:14:09+00:00", // RFC3339-based start datetime from which to take pageviews to this today 
	"limit": 3, // limit how many top tags this endpoint returns
	"content_type": "article", // String; OPTIONAL; filters articles by content_type
	"sections": [ // OPTIONAL; filters from which sections take articles (use either external_id or name arrays, not both); filters joined with AND
		{"external_ids": ["Section external ids"]}, // String; section external IDs; values joined with OR
		{"names": ["Section titles"]} // String; section external_id; values joined with OR
	],
	"authors": [ // OPTIONAL; filters from which authors take articles (use either external_id or name arrays, not both); filters joined with AND
		{"external_ids": ["author external ids"]}, // String; section external IDs; values joined with OR
		{"names": ["author names"]} // String; section external_id; values joined with OR
	],
	"tag_categories": [ // OPTIONAL; filters articles with tag categories (use either external_id or name arrays, not both); filters joined with AND
		{"external_ids": ["tag category external ids"]}, // String; tag category external IDs; values joined with OR 
		{"names": ["tag category names"]} // String; tag category names; values joined with OR
	]
}
```

##### *Examples*:

<details>
<summary>curl</summary>

```shell
curl --location --request POST 'http://beam.remp.press/api/v2/tags/top' \
--header 'Content-Type: application/json' \
--header 'Accept: application/json' \
--header 'Authorization: Bearer XXX' \
--data-raw '{
	"from": "2020-08-10T08:14:09+00:00",
	"limit": 3,
	"content_type": "article",
	"sections": [
		{"external_ids": ["1"]}
	],
	"authors": [
		{"external_ids": ["123"]}
	],
	"tag_categories": [
		{"external_ids": ["1"]}
	]
}'
```

</details>

<details>
<summary>raw PHP</summary>

```php
$payload = [
	"from" => "2020-08-10T08:14:09+00:00",
	"limit" => 3,
	"content_type" => "article",
	"sections" => [
		["names" => ["Blog"]]
	],
	"authors" => [
		["names" => ["John Doe"]]
	],
	"tag_categories" => [
		["names" => ["Europe"]]
	]
];
$jsonPayload = json_encode($payload);
$context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: type=application/json\r\n"
                . "Accept: application/json\r\n"
                . "Content-Length: " . strlen($jsonPayload) . "\r\n"
                . "Authorization: Bearer XXX",
            'content' => $jsonPayload,
        ]
    ]
);
$response = file_get_contents("http://beam.remp.press/api/v2/tags/top", false, $context);
// process response (raw JSON string)
```

</details>

##### *Response:*

```json5
[
    {
        "name": "ekonomika",
        "pageviews": 4000,
        "external_id": "1"
    },
    {
        "name": "covid19",
        "pageviews": 3000,
        "external_id": "2"
    },
    {
        "name": "brexit",
        "pageviews": 2000,
        "external_id": "3"
    }
]
```

---

##### POST `api/pageviews/histogram`

Beam admin provides pageviews histogram for articles satisfying the filter for days in date range.
You can filter articles by content type, sections, authors, tags or tag categories.

You can combine multiple filters for each filter category. Filters in and between categories are joined with `AND`, values in filter are joined with `OR`.

##### *Headers:*

| Name | Value | Required | Description |
| --- |---| --- | --- |
| Authorization | Bearer *String* | yes | API token. |
| Content-Type | application/json | yes |  |
| Accept | application/json | yes |  |

##### *Body:*

```json5
{
	"from": "2020-08-10", // date from which take pageviews including
	"to": "2020-08-12", // date to which take pageviews
	"content_type": "article", // String; OPTIONAL; filters articles by content_type
	"sections": [ // OPTIONAL; filters from which sections take articles (use either external_id or name arrays, not both); filters joined with AND
		{"external_ids": ["Section external ids"]}, // String; section external IDs; values joined with OR
		{"names": ["Section titles"]} // String; section names; joined with OR; values joined with OR
	],
	"authors": [ // OPTIONAL; filters from which authors take articles (use either external_id or name arrays, not both); filters joined with AND
		{"external_ids": ["author external ids"]}, // String; author external IDs; values joined with OR
		{"names": ["author names"]} // String; author names; joined with OR; values joined with OR
	],
	"tags": [ // OPTIONAL; filters articles with tags (use either external_id or name arrays, not both); filters joined with AND
		{"external_ids": ["tag external ids"]}, // String; tag external IDs; values joined with OR
		{"names": ["tag names"]} // String; tag names; values joined with OR
	],
	"tag_categories": [ // OPTIONAL; filters articles with tag categories (use either external_id or name arrays, not both); filters joined with AND
		{"external_ids": ["tag category external ids"]}, // String; tag category external IDs; values joined with OR
		{"names": ["tag category names"]} // String; tag category names; values joined with OR
	]
}
```

##### *Examples*:

<details>
<summary>curl</summary>

```shell
curl --location --request POST 'http://beam.remp.press/api/pageviews/histogram' \
--header 'Content-Type: application/json' \
--header 'Accept: application/json' \
--header 'Authorization: Bearer XXX' \
--data-raw '{
	"from": "2020-08-10",
	"to": "2020-08-12",
	"content_type": "article",
	"sections": [
		{"external_ids": ["1", "2"]},
		{"names": ["World"]}
	],
	"authors": [
		{"external_ids": ["123"]}
	],
	"tags": [
		{"external_ids": ["10"]}
	],
	"tag_categories": [
		{"external_ids": ["1"]}
	]
}'
```

</details>

<details>
<summary>raw PHP</summary>

```php
$payload = [
	"from" => "2020-08-10",
	"to" => "2020-08-12",
	"content_type" => "article",
	"sections" => [
		["names" => ["Blog"]]
	],
	"authors" => [
		["names" => ["John Doe"]]
	],
	"tags" => [
		["names" => ["News"]]
	],
	"tag_categories" => [
		["names" => ["Europe"]]
	]
];
$jsonPayload = json_encode($payload);
$context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: type=application/json\r\n"
                . "Accept: application/json\r\n"
                . "Content-Length: " . strlen($jsonPayload) . "\r\n"
                . "Authorization: Bearer XXX",
            'content' => $jsonPayload,
        ]
    ]
);
$response = file_get_contents("http://beam.remp.press/api/pageviews/histogram", false, $context);
// process response (raw JSON string)
```

</details>

##### *Response:*

```json5
[
    {
        "date": "2020-08-10",
        "pageviews": 274
    },
    {
        "date": "2020-08-11",
        "pageviews": 150
    }
]
```

---

##### GET `/api/articles`

Returns list of articles specified by ids or external ids.

##### *Headers:*

| Name | Value | Required | Description |
| --- |---| --- | --- |
| Authorization | Bearer *String* | yes | API token. |
| Accept | application/json | yes |  |

##### *Parameters:*
| Name | Value | Required | Description |
| --- |---| --- | --- |
| ids | String | no | Article's IDs separated by comma. |
| external_ids | String | no | Article's external IDs separated by comma. |
| per_page | Integer | no |  Number of items displayed per page. |
| published_from | String | no | RFC3339 datetime used to filter articles by published_at property. |
| published_to | String | no | RFC3339 datetime used to filter articles by published_at property. |
| content_type | String | no | Content type of article (eg. article, blog; set by caller when upserting article) |

##### *Examples:*

<details>
<summary>curl</summary>

```shell
curl -X GET \
  http://beam.remp.press/api/articles?external_ids=123,231 \
  -H 'Accept: application/json' \
  -H 'Authorization: Bearer XXX'
```

or filter by `publishet_from` & `published_to`:

```shell
curl -X GET \
  http://beam.remp.press/api/articles?published_from=2018-01-01T00:00:00Z&published_to=2018-02-01T00:00:00Z \
  -H 'Accept: application/json' \
  -H 'Authorization: Bearer XXX'
```

</details>

<details>
<summary>raw PHP</summary>

```php
$context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "Accept: application/json\r\n"
                . "Authorization: Bearer XXX",
        ]
    ]
);
$response = file_get_contents("http://beam.remp.press/api/articles?external_ids=123,231", false, $context);
// process response (raw JSON string)
```

</details>

##### *Response:*

```json5
{
  "data": [
    {
      "id": 123,
      "external_id": "123",
      "property_uuid": "a6c80469-464d-4118-830c-0448494ada86",
      "title": "Test article title",
      "url": "http:\/\/example.com\/test-article\/",
      "content_type": "article",
      "image_url": "http:\/\/example.com\/test-article\/image.jpg",
      "published_at": "2021-04-13 09:26:10",
      "pageviews_all": 7505,
      "pageviews_signed_in": 4492,
      "pageviews_subscribers": 4029,
      "timespent_all": 900715,
      "timespent_signed_in": 789643,
      "timespent_subscribers": 758675,
      "created_at": "2021-04-13 09:26:13",
      "updated_at": "2021-04-14 06:27:13"
    }
  ]
}
```

---

##### GET `/api/authors`

Returns list of authors.

##### *Headers:*

| Name | Value | Required | Description |
| --- |---| --- | --- |
| Authorization | Bearer *String* | yes | API token. |
| Accept | application/json | yes |  |

##### *Parameters:*
| Name | Value | Required | Description |
| --- |---| --- | --- |
| per_page | Integer | no |  Number of items displayed per page. |

##### *Examples:*

<details>
<summary>curl</summary>

```shell
curl -X GET \
  http://beam.remp.press/api/authors \
  -H 'Accept: application/json' \
  -H 'Authorization: Bearer XXX' \
```

</details>

<details>
<summary>raw PHP</summary>

```php
$context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' =>  "Accept: application/json\r\n"
                . "Authorization: Bearer XXX",
        ]
    ]
);
$response = file_get_contents("http://beam.remp.press/api/authors", false, $context);
// process response (raw JSON string)
```

</details>

##### *Response:*

```json5
{
    "data": [
        {
            "external_id": "ext-1",
            "name": "Mr. Damian White",
            "created_at": "2020-08-14T12:18:37.000000Z",
            "updated_at": "2020-08-14T12:18:37.000000Z"
        },
        {
            "external_id": "ext-2",
            "name": "Mac Schroeder",
            "created_at": "2020-08-14T12:18:37.000000Z",
            "updated_at": "2020-08-14T12:18:37.000000Z"
        },
        // ...
    ]
}
```

---

##### GET `/api/conversions`

Returns list of conversions.

##### *Headers:*

| Name | Value | Required | Description |
| --- |---| --- | --- |
| Authorization | Bearer *String* | yes | API token. |
| Accept | application/json | yes |  |

##### *Parameters:*
| Name | Value | Required | Description |
| --- |---| --- | --- |
| conversion_from | String | no | RFC3339 datetime from which will be conversions filtered. |
| conversion_to | String | no | RFC3339 datetime to which will be conversions filtered. |
| article_published_from | String | no | RFC3339 datetime of from which will be articles of conversions filtered. |
| article_published_to | String | no | RFC3339 datetime to which will be articles of conversions filtered. |
| article_content_type | String | no | Content type of article (eg. article, blog; set by caller when upserting article) |
| per_page | Integer | no |  Number of items displayed per page. |

##### *Examples:*

<details>
<summary>curl</summary>

```shell
curl -X GET \
  http://beam.remp.press/api/conversions?conversion_from=2018-06-05T06:03:05Z&article_published_from=2018-01-01T00:00:00Z \
  -H 'Accept: application/json' \
  -H 'Authorization: Bearer XXX' \
```

</details>

<details>
<summary>raw PHP</summary>

```php
$context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' =>  "Accept: application/json\r\n"
                . "Authorization: Bearer XXX",
        ]
    ]
);
$response = file_get_contents("http://beam.remp.press/api/conversions?conversion_from=2018-06-05T06:03:05Z&article_published_from=2018-01-01T00:00:00Z", false, $context);
// process response (raw JSON string)
```

</details>

##### *Response:*

```json5
{
    "data": [
        {
            "id": 1441,
            "article_id": 114,
            "user_id": null,
            "amount": 25,
            "currency": "EUR",
            "paid_at": "2020-11-07T06:30:53.000000Z",
            "transaction_id": "08ad9d77-bbaf-31d2-9db1-04225ded1e72",
            "events_aggregated": true,
            "source_processed": true,
            "created_at": "2020-11-09T09:57:37.000000Z",
            "updated_at": "2020-11-09T09:57:37.000000Z",
            "article_external_id": "cd14dfb5-3af2-3a73-b6ab-78b9dca2e975"
        },
        //....
    ]
}
```

---

##### GET `/api/sections`

Returns list of sections.

##### *Headers:*

| Name | Value | Required | Description |
| --- |---| --- | --- |
| Authorization | Bearer *String* | yes | API token. |
| Accept | application/json | yes |  |

##### *Parameters:*
| Name | Value | Required | Description |
| --- |---| --- | --- |
| per_page | Integer | no |  Number of items displayed per page. |

##### *Examples:*

<details>
<summary>curl</summary>

```shell
curl -X GET \
  http://beam.remp.press/api/sections \
  -H 'Accept: application/json' \
  -H 'Authorization: Bearer XXX' \
```

</details>

<details>
<summary>raw PHP</summary>

```php
$context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' =>  "Accept: application/json\r\n"
                . "Authorization: Bearer XXX",
        ]
    ]
);
$response = file_get_contents("http://beam.remp.press/api/sections", false, $context);
// process response (raw JSON string)
```

</details>

##### *Response:*

```json5
{
    "data": [
        {
            "external_id": null,
            "name": "reinger",
            "created_at": "2020-08-14T12:18:36.000000Z",
            "updated_at": "2020-08-14T12:18:36.000000Z"
        },
        {
            "external_id": null,
            "name": "blick",
            "created_at": "2020-08-14T12:18:36.000000Z",
            "updated_at": "2020-08-14T12:18:36.000000Z"
        }
        // ...
    ]
}
```

---

##### GET `/api/tags`

Returns list of tags.

##### *Headers:*

| Name | Value | Required | Description |
| --- |---| --- | --- |
| Authorization | Bearer *String* | yes | API token. |
| Accept | application/json | yes |  |

##### *Parameters:*
| Name | Value | Required | Description |
| --- |---| --- | --- |
| per_page | Integer | no |  Number of items displayed per page. |

##### *Examples:*

<details>
<summary>curl</summary>

```shell
curl -X GET \
  http://beam.remp.press/api/tags \
  -H 'Accept: application/json' \
  -H 'Authorization: Bearer XXX' \
```

</details>

<details>
<summary>raw PHP</summary>

```php
$context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' =>  "Accept: application/json\r\n"
                . "Authorization: Bearer XXX",
        ]
    ]
);
$response = file_get_contents("http://beam.remp.press/api/tags", false, $context);
// process response (raw JSON string)
```

</details>

##### *Response:*

```json5
{
    "data": [
        {
            "external_id": null,
            "name": "reinger",
            "created_at": "2020-08-14T12:18:36.000000Z",
            "updated_at": "2020-08-14T12:18:36.000000Z"
        },
        {
            "external_id": null,
            "name": "blick",
            "created_at": "2020-08-14T12:18:36.000000Z",
            "updated_at": "2020-08-14T12:18:36.000000Z"
        }
        // ...
    ]
}
```
