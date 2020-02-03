# Beam

Beam consist of Beam web admin and two separate API microservices for tracking the data and
reading/aggregating them.

* Beam Admin
* Tracker API
* Segments/Journal API

## Beam Admin (Laravel)

Beam Admin serves as a tool for configuration of sites, properties and segments. It's the place to see
the stats based on the tracked events and configuration of user segments.

When the backend is ready, don't forget to create `.env` file (use documented `.env.example` as boilerplate),
install dependencies and run DB migrations:

```bash
# 1. Download PHP dependencies
composer install

# 2. Download JS/HTML dependencies
yarn install

# !. use extra switch if your system doesn't support symlinks (Windows; can be enabled)
yarn install --no-bin-links

# 3. Generate assets
yarn run all-dev // or any other alternative defined within package.json

# 4. Run migrations
php artisan migrate

# 5. Generate app key
php artisan key:generate

# 6. Run seeders (optional)
php artisan db:seed
```

### Dependencies

- PHP ^7.1.3
- MySQL ^5.7
- Redis ^3.2
- Segments API (see #segments-go)

After clean installation Beam Admin and Segments API would throw errors because the underlying database wouldn't have inidices for tracked events created. Docker installation handles this for you, but if you use manual installation, please run the following set of commands against your Elasticsearch instance.

```bash
curl -XPUT -H "Content-Type: application/json" elasticsearch:9200/commerce -d '{"mappings": {"_doc": {"properties": {"revenue": {"type": "double"}}}}}'
curl -XPUT -H "Content-Type: application/json" elasticsearch:9200/events -d '{"mappings": {"_doc": {}}}'
curl -XPUT -H "Content-Type: application/json" elasticsearch:9200/pageviews -d '{"mappings": {"_doc": {}}}'
curl -XPUT -H "Content-Type: application/json" elasticsearch:9200/pageviews_time_spent -d '{"mappings": {"_doc": {}}}'
curl -XPUT -H "Content-Type: application/json" elasticsearch:9200/pageviews_progress -d '{"mappings": {"_doc": {}}}'
curl -XPUT -H "Content-Type: application/json" elasticsearch:9200/concurrents_by_browser -d '{"mappings": {"_doc": {}}}'
curl -XPUT -H "Content-Type: application/json" elasticsearch:9200/entities -d '{"mappings": {"_doc": {}}}'
```

*These commands need to be run just once. Every further execution would result in BadRequest returned by Elasticsearch that inidices or document types are already present.*

### Technical feature description

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

Note: *Timespent* tracking is not mandatory dashboard can utilize regular pageview data instead to calculate number of concurrents. If you don't plan to track timespent, please see [this Telegraf configuration options](https://github.com/remp2020/remp/blob/master/Docker/telegraf/telegraf.conf#L78) so you can switch to pageview-based concurrents instead.

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

We could add couple of parameters for this entity:

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

##### POST `/api/articles/upsert`

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

### Scheduled events

#### Laravel scheduler

For application to function properly you need to add Laravel's schedule running into your crontab:

```
* * * * * php artisan schedule:run >> storage/logs/schedule_run.log 2>&1
```

Laravel's scheduler currently includes:

* *pageviews:aggregate-load*: Reads pageview/load data from journal and stores aggregated data
* *pageviews:aggregate-timespent*: Reads pageview/timespent data from journal and stores aggregated data
* *pageviews:loyal-visitors*: Determines number of articles read by top 10% of readers and creates segment based on it
* *pageviews:process-sessions*: Reads and parses session referers tracked within Beam

#### Author segments computation

To compute members of author segments (see [Author segments](#author-segments) section to read more), schedule following command.
As it's computationally intensive, we recommend running it nightly.

```
5 3 * * * php artisan segments:compute-author-segments >> storage/logs/segments_compute-author-segments.log 2>&1
```

#### Newsletter sending

If there are any newsletters configured to be sent (see [Newsletters section](#newsletters), you should schedule sending
worker which periodically checks whether there's something to send or not. This feature requires Mailer to be configured
and running and is completely optional.

```
* * * * * php artisan newsletters:send >> storage/logs/newsletters_send.log 2>&1
```

## [Tracker](go/cmd/tracker) (Go)

Beam Tracker serves as a tool for tracking events via API. Once it's built and running, endpoints can be
discovered by accessing `/swagger.json` path.

Tracker pushes events to Kafka in Influx format into `beam_events` topic. For all `/track/event` calls,
Tracker also pushes raw JSON event to its own topic based on the event *category* and *action*.

For example for payload

```
{
  "category": "foo",
  "action": "bar"
  // ...
}
```

the event would be stored within `foo_bar` topic so everyone can subscribe to it.

Tracker allows you to track three types of events:

* *Pageview events.* These are mainly used for Beam admin realtime dashboard and data aggregations used
in automatic segment generation or visit stats.
* *Commerce events.* These are an enhancement of [Conversion tracking](#conversion-tracking) which allow you
to track more extensive data including UTM parameters, reference to sales funnels and much more. **These data
are required for A/B test statistics used in Campaign and Mailer**.
* *Generic events.* You can track any kind of generic event in your system. All events can be later used
to build user segments.

### Tracker integration with CMS/CRM

It's highly recommended to use Tracker API as much as possible, at least for *pageview* and *commerce* events.
You should integrate *commerce* event tracking from your CRM by calling `/track/commerce` on different steps
of payment life-cycle:

* *Checkout*. User is at the checkout page, ready to confirm the order.
* *Payment*. User is being redirected to the payment gateway (e.g. Paypal).
* *Purchase*. The purchase was successful.
* *Refund*. User requested the refund which was fulfilled.

See the available endpoints with full JSON structure at `/swagger.json` path of running Tracker API.

#### Javascript Snippet

Any pageview-related data should be tracked from within the browser. Beam provides JS library and snippet
for tracking these kind of data.

Include following snippet into your pages and update `rempConfig` object as needed.

```javascript
(function(win, doc) {
    function mock(fn) {
        return function() {
            this._.push([fn, arguments])
        }
    }
    function load(url) {
        var script = doc.createElement("script");
        script.type = "text/javascript";
        script.async = true;
        script.src = url;
        doc.getElementsByTagName("head")[0].appendChild(script);
    }
    win.remplib = win.remplib || {};
    var mockFuncs = {
        "campaign": "init",
        "tracker": "init trackEvent trackPageview trackCommerce",
        "iota": "init"
    };

    Object.keys(mockFuncs).forEach(function (key) {
        if (!win.remplib[key]) {
            var fn, i, funcs = mockFuncs[key].split(" ");
            win.remplib[key] = {_: []};

            for (i = 0; i < funcs.length; i++) {
                fn = funcs[i];
                win.remplib[key][fn] = mock(fn);
            }
        }
    });
    
    // change URL to location of BEAM remplib.js
    load("http://beam.remp.press/assets/lib/js/remplib.js");
})(window, document);

// configuration
var rempConfig = {
    // UUIDv4 based REMP BEAM token of appropriate property
    // (see BEAM Admin -> Properties)
    token: String,
    
    // optional, identification of logged user
    userId: String,
    
    // optional, flag whether user is currently subscribed to the displayed content 
    userSubscribed: Boolean,
    
    // optional, this is by default generated by remplib.js library and you don't need to override it
    browserId: String,
    
    // optional, controls where cookies (UTM parameters of visit) are stored
    cookieDomain: ".remp.press",
    
    // optional, article details if pageview is on the article
    article: {
        id: String, // required, ID of article in your CMS
        author_id: String, // optional, name of the author
        category: String, // optional, category/section of the article
        locked: Boolean, // optional, flag whether content was locked at the time of visit for this pageview
        tags: [String, String, String], // optional, any tags associated with the article
        elementFn: Function // callback returning DOM element containing article content
    },
            
    // required, Tracker API specific options          
    tracker: {
        // required, URL location of BEAM Tracker
        url: "http://tracker.beam.remp.press",
        
        // optional time spent measuring (disabled by default)
        // if enabled, tracks time spent on current page
        timeSpent: {
            enabled: Boolean, // if enabled, tracks time spent on the webpage
            interval: Number, // optional, frequency of sending tracked progress in seconds (default value 5; interval is progressive and gets prolonged in time)
        },
        
        // optional, achieved scroll depth tracking (disabled by default)
        readingProgress: {
            enabled: Boolean, // if enabled, tracks achieved scroll depth
            interval: Number // optional, frequency of sending tracked progress in seconds (default value 5)
        },
        
        // optional, allows to specify custom referer medium  
        // this value overrides implicit referer medium computed from Referer header by tracker 
        refererMedium: "push_notification"
    },
};
remplib.tracker.init(rempConfig);
```

##### Pageview tracking

All pageviews are tracked automatically when `remplib.tracker.init()` is called. Pageview is tracked only once immediately
after the initialization.

##### Timespent tracking

Optionally you can enable timespent tracking, which tracks amount of second user spent on a website. Tracking is done
periodically and on the page unload (when user leaves the page). Timespent is linked to the pageview data by internal
`remp_pageview_id` field which can be found in the Elasticsearch data.

Tracking period is not configurable right now. It starts on tick every 5 seconds and raises logarithmically longer
the user is on the website.

You can enable the tracking by setting `rempConfig.tracker.timeSpentEnabled = true`.

##### Reading progress tracking

Optionally you can enable reading progress tracking, which tracks how far the user read the webpage. Tracking is done
periodially and on the page unload (when user leaves the page). Reading progress is linked to the pageview data by
internal `remp_pageview_id` field which can be found in the Elasticsearch data.

Reading progress period is configurable and defaults to update to server every 5 seconds. The minimum period to track
is 1 second, but it's safe to raise it to more than 5 seconds as *unload* event tracks the progress anyway.

You can enable the tracking by setting `rempConfig.tracker.readingProgress = { enabled: true }`

##### JS tracking interface

Note: The *source* object is referenced as a parameter in the following API calls. Here's the list of parameters
that might be appended to target URLs within REMP tools (Campaign banner, emails) and that need to be tracked
in the functions bellow to properly track conversions against created campaigns.

The expected value is always as follows (all are optional):

```
{
  "utm_medium": String,
  "utm_campaign": String,
  "utm_source": String,
  "utm_content": String,
  "banner_variant": String
}
```

If the *source* is not provided, JS library tries to load the values from local storage which were stored last time
they appeared in the visited URL.

Here's the list of supported tracking methods:

* `remplib.tracker.trackEvent(category, action, tags, fields, source)`: tracks generic events to Beam
    * `category`: Category of event (e.g. `"spring promo"`).
    * `action`: Actual event name (e.g. `"click"`).
    * `tags`: Extra metadata you want to track with event (e.g. `{foo: bar}`).
    * `fields`: Extra metadata you want to track with event (e.g. `{foo: bar}`)).
    * `source`: Object with utm parameters (e.g. `{ utm_campaign: "foo" }`). 
    
* `remplib.tracker.trackCheckout(funnelId)`: tracks checkout commerce event - indicating that user is summarizing the order 
    * `funnelId`: Reference to funnel bringing user to checkout page. You can use IDs if your system contains referencable
    funnels or string keys otherwise. If your system doesn't support funnels and you don't need to differentiate them,
    use `"default"`.
    
* `remplib.tracker.trackCheckoutWithSource: function(funnelId, article, source)`: tracks checkout commerce event with custom article source - indicating that user is summarizing his order 
	* `funnelId`: Reference to funnel bringing user to checkout page. You can use IDs if your system contains
	referencable funnels or string keys otherwise. If your system doesn't support funnels and you don't need
	to differentiate them and `default` is a recommended value.
    * `article`: Object with info about current article (it is safe to reuse `remplib.tracker.article` 
    if you don't want to make any changes).
		```
		{
			id: String, // required, ID of article in your CMS
			author_id: String, // optional, name of the author
			category: String, // optional, category/section of the article
			locked: Boolean, // optional, flag whether content was locked at the time of visit for this pageview
			tags: [String, String, String], // optional, any tags associated with the article
		}
		```
	
    * `source`: Object with utm parameters (e.g. `{ utm_campaign: "foo" }`).
    
* `remplib.tracker.trackPayment(transactionId, amount, currency, productIds)`: tracks commerce payment event - indicating
that the payment has started (user was redirected to payment gateway)
    * `transactionId`: Reference to transaction (should be unique for every payment; e.g. `"778453213"`) 
    * `amount`: Numeric amount (e.g. `18.99`)
    * `currency`: String currency (e.g. `EUR`)
    * `productIds`: List of purchased products (e.g. `["product_1"]`)
    
* `remplib.tracker.trackPaymentWithSource: function(transactionId, amount, currency, productIds, article, source)`: 
tracks commerce payment event with custom article and source - indicating that the payment has started
(user was redirected to payment gateway)

* `remplib.tracker.trackPurchase(transactionId, amount, currency, productIds)`: tracks commerce purchase event - 
indicating that the payment was successful

* `remplib.tracker.trackPurchaseWithSource: function(transactionId, amount, currency, productIds, article, source)`:
tracks commerce purchase event with custom article and source - indicating that the payment was successful

* `remplib.tracker.trackRefund(transactionId, amount, currency, productIds)`: tracks commerce refund event -
indicating that confirmed payment (one that had *purchase* event) was refunded

* `remplib.tracker.trackRefundWithSource(transactionId, amount, currency, productIds, article, source)`: tracks commerce refund event with custom article and source - indicating that confirmed payment (one that had *purchase* event) was refunded

#### Build Dependencies

- Go ^1.8

#### Run Dependencies

- Kafka ^0.10
- Zookeeper ^3.4
- MySQL ^5.7
    
## [Segments](go/cmd/segments) (Go)

Beam Segments serves as a read-only API for getting information about segments and users of these segments.
API provides listing and aggregation endpoints for data tracked via Tracker API.

Endpoints can be discovered by accessing `/swagger.json` path.

The API endpoints are primarily used by REMP services, but you're free to explore the API and use the data
in your own extensions/implementation.

#### Build Dependencies

- Go ^1.8

#### Dependencies

- Elastic ^6.2
- MySQL ^5.7

## [Telegraf](../Docker/telegraf)

Influx Telegraf is a backend service for moving data tracked by Tracker out of Kafka to Elastic. It needs
to be ready as Segments service is dependent on data pushed to Elastic by Telegraf.

We use forked version of Telegraf as we needed to implement custom plugin to insert data to Elastic.
You can find the repository in [remp2020/telegraf](https://github.com/remp2020/telegraf). You can either
build the whole telegraf based on README instructions or run the pre-build binaries.

To download pre-built binaries, head to [remp2020/telegraf/releases](https://github.com/remp2020/telegraf/releases).

You can then run the telegraf by running the binary and passing the path to config file. For example:

```
/home/user/telegraf --config /home/user/workspace/remp/Docker/telegraf/telegraf.conf
```

#### Build Dependencies

- Go ^1.8

## [Kafka](../Docker/kafka)

All tracked events are being pushed to Kafka for asynchronous processing. Kafka should be configured in Tracker's
`.env` file so the events are getting piped properly.

For further installation and configuration information, please head to the official documentation page.

## Iota (on-site reporting)

We've built a tool able to display article-based statistic directly on-site for your editors.
Currently we support:

- Revenue-based statistics
- Event-based statistics (primarily for reporting A/B test data)

To initialize, put `iota` property into the `rempConfig` variable which was defined
in the [JS snippet](#javascript-snippet) earlier:

```javascript
var rempConfig = typeof(rempConfig) === 'undefined' ? {} : rempConfig;
rempConfig.iota = {
    // required: URL of BEAM segments API
    url: String,
    // required: selector matching all "article" elements on site you want to be reported
    articleSelector: String,
    // required: callback for articleId extraction out of matched element
    idCallback: Function, // function (matchedElement) {}
    // optional: callback for selecting element where the stats will be placed;
    // if not present, stats are appended as next sibling to matchedElement
    // stats are positioned absolutely, so they need a relative parent
    targetElementCallback: Function, // function (matchedElement) {}
    // optional: HTTP headers to be used in API calls 
    httpHeaders: Object
};

if (remplib.iota.init(t), !document.getElementById("remplib-iota-loader")) {
    var e = document.createElement("script");
    // change URL to location of BEAM iota.js
    e.src = "http://beam.remp.press/assets/iota/js/iota.js", e.id = "remplib-iota-loader", document.body.appendChild(e)
}
```

You can place the script directly on-site, or create a bookmarklet and execute it on-demand.

## Segment guidelines

### The highest loyalty visitors

Each website has its own distribution of visitors based on the served content. Due to this,
it's impossible to create segments which could target this group in a generic fashion.

We've prepared a command, that will process 30 days of tracked pageviews data and will generate
a segment which will include top 10% of users based on the number of articles read. To run it,
please execute `pageviews:loyal-visitors` command.

It's highly recommended to run the command during the low-traffic period of the day, as its
computationally intensive. Also it's recommended to run it only when you really have 30 days
of the data tracked.

### Visitors with low probability of conversion

Similar to previous section, we're not able to generate generic segments that would be 100%
accurate. Thanks to industry knowledge and tracked data we're able to provide set of segments
to target visitors with low conversion probability which you can test and find which one
suits your visitor base the best. 

Segments are generated during the installation of Beam and can be generated also manually via
running `db:seed` command. Recommended segments contain users matching these criteria:

* First website pageview
* One pageview within 90 days
* One pageview within 30 days (additionally could be swapped with 2-5, 6-10pvs and 11+ pageviews)
* Never started the checkout process (additionally could be swapped with 2-5 checkouts but no purchase)
* First article pageview within 30 days (aditionally could be swapped with 2-3 and 4+ article pageviews)

## Known issues

### Some internal API calls are not going through

#### What probably happened

Event groups, categories and actions can contain words like _pageviews_, _banners_, _track_ which are sometimes
blocked by ad/track blockers. As they're being used for loading the statistics, URL being accessed can sometimes
look like this:

`https://beam.remp.press/api/journal/pageviews/categories/pageview/actions`


#### How to solve

Add filter to your blocker which allows these calls.

_Example of uBlock Origin / AdBlockPlus syntax filter_ if your BEAM domain is `beam.remp.press`:

```
! REMP - allow BEAM journal API calls with blocked words (eg 'pageviews', 'banners', ...)
@@||beam.remp.press/api/journal/*$xmlhttprequest,domain=beam.remp.press
```

### Elasticsearch shards are getting too big

There are two possible solutions for this:

* Keep the number of shards and remove old data that you don't need anymore. You can use `es2csv` tool for backing up
data to CSV files first. Downside is that removing data from index is computationally intensive operation.
* Split indices in the background and hide them behind aliases. 

If storage is not an issue for you, we recommend splitting the indices. Our implementation supports aliases and they
can be used both for reading and writing to indices. If you expect your data to be big from the beginning, it's good
idea to start with split indices and aliases right away.

To start using split indices and aliases, there's one condition that you need to maintain. Segments API reads data
from prepared indices:

  * `pageviews`
  * `commerce`
  * `events`
  * `entities`
  * `concurrents_by_browser`
  
If you start using aliases, it's up to you to maintain that there's always an alias with these names pointing to indices
where the data is stored - reading aliases can point to multiple indices at the same time.

##### Telegraf changes

Telegraf by default writes directly to indices listed above. We recommend to write to separate alias (e.g. `pageviews_write`)
which internally points to date-based index `pageviews_write-2019.05.07-000001`. You can change write index
in Telegraf configuration (`telegraf.conf`) by changing `index_name` directive.

Once you write to alias, schedule call to `_rollover` API periodically.

*You can read more about rollovers at [Elastic rollover documentation](https://www.elastic.co/guide/en/elasticsearch/reference/6.2/indices-rollover-index.html)
and more about aliases at [Elastic aliases documentation](https://www.elastic.co/guide/en/elasticsearch/reference/6.2/docs-reindex.html).*

##### Elasticsearch changes

* Create date-based index:

    ```
    curl -s -X PUT localhost:9200/%3Cpageviews-%7Bnow%2Fd%7D-000001%3E
    ```

    The response should be similar to this:
    
    ```
    {"acknowledged":true,"shards_acknowledged":true,"index":"pageviews-2019.05.17-000001"}
    ```

* Add aliases to this newly created index so Segments API can read from it and Telegraf can write to it:

    ```
    curl -X POST \
      http://localhost:9200/_aliases \
      -H 'Content-Type: application/json' \
      -d '{
        "actions" : [
            { "add" : { "index" : "pageviews-2019.05.17-000001", "alias" : "pageviews" } },
            { "add" : { "index" : "pageviews-2019.05.17-000001", "alias" : "pageviews_write" } }
        ]
    }'
    ```
    
    And confirm the aliases are there:
    
    ```
    curl -s localhost:9200/_cat/aliases
    ```
    
    ```
    pageviews       pageviews-2019.05.17-000001 - - -
    pageviews_write pageviews-2019.05.17-000001 - - -
    ```

* If you configured Telegraf to use `index_name = pageviews_write`, you should see new events being poured to this
`pageviews-2019.05.17-000001` index and also you should be able to see all tracked events by reading `pageviews` alias.

* Schedule the `_rollover` call to be run periodically against write alias (`pageviews_write`). Once the conditions
are met, Elasticsearch will create new index.

    ```
    curl -X POST \
      http://localhost:9200/pageviews_write/_rollover \
      -H 'Content-Type: application/json' \
      -d '{
      "conditions" : {
        "max_size": "5gb"
      }
    }'
    ```
    ```
    {
        "old_index": "pageviews-2019.05.17-000001",
        "new_index": "pageviews-2019.05.17-000002",
        "rolled_over": true,
        "dry_run": false,
        "acknowledged": true,
        "shards_acknowledged": true,
        "conditions": {
            "[max_size: 5gb]": true
        }
    }
    ```
    
* Now check the existing aliases again.

    ```
    curl -s localhost:9200/_cat/aliases | grep pageviews
    ```
    
    ```
    pageviews       pageviews-2019.05.17-000001 - - -
    pageviews_write pageviews-2019.05.17-000002 - - -
    ```
    
    See that `pageviews_write` index was rolled over with alias and Telegraf is now inserting data to newly created
    index. However `pageviews` alias doesn't know about the newly created index yet, so we need to add it:
    
    ```
    curl -X POST \
      http://localhost:9200/_aliases \
      -H 'Content-Type: application/json' \
      -d '{
        "actions" : [
            { "add" : { "index" : "pageviews-2019.05.17-000002", "alias" : "pageviews" } }
        ]
    }'
    ```
    
    Listing of aliases now looks OK:
    
    ```
    pageviews       pageviews-2019.05.17-000001 - - -
    pageviews       pageviews-2019.05.17-000002 - - -
    pageviews_write pageviews-2019.05.17-000002 - - -
    ```
    
Once configured and scheduled, rollovers will be happening automatically for you. When rollover happens, you need to
create an alias for this rolled over index so also "reading" alias (e.g. `pageviews`) knows about it.