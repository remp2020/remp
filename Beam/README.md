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

- PHP ^8.2
- MySQL ^8.0
- Redis ^6.2
- Node.js >=18
- Segments API (see #segments-go)

After clean installation Beam Admin and Segments API would throw errors because the underlying database wouldn't have inidices for tracked events created. Docker installation handles this for you, but if you use manual installation, please run the following set of commands against your Elasticsearch instance.

```bash
curl -XPUT -H "Content-Type: application/json" elasticsearch:9200/commerce -d '{"mappings": {"properties": {"revenue": {"type": "double"}}}}'
curl -XPUT -H "Content-Type: application/json" elasticsearch:9200/events -d '{"mappings": {}}'
curl -XPUT -H "Content-Type: application/json" elasticsearch:9200/pageviews -d '{"mappings": {}}'
curl -XPUT -H "Content-Type: application/json" elasticsearch:9200/pageviews_time_spent -d '{"mappings": {}}'
curl -XPUT -H "Content-Type: application/json" elasticsearch:9200/pageviews_progress -d '{"mappings": {}}'
curl -XPUT -H "Content-Type: application/json" elasticsearch:9200/concurrents_by_browser -d '{"mappings": {}}'
curl -XPUT -H "Content-Type: application/json" elasticsearch:9200/entities -d '{"mappings": {}}'
```

*These commands need to be run just once. Every further execution would result in BadRequest returned by Elasticsearch that inidices or document types are already present.*

#### Redis Sentinel

Application supports Redis to be configured with the Sentinel cluster. In order to enable the integration, see `.env.example` file and `REDIS_SENTINEL_SERVICE` variable.

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

Tracker pushes events to a message broker (by default Kafka or Google Pub/Sub) in Influx format into `beam_events` topic. For all `/track/event` calls,
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

### Message Broker (Kafka or Google Cloud Pub/Sub)

By default, Tracker uses Kafka as a message broker to push messages into data storage.

Alternatively, Tracker can be configured to use Google Cloud Pub/Sub service. It has some advantages, such as you don't need to maintain your own Kafka server. To configure, please follow the steps:

In `.env`  configuration file, fill out the variables:

```neon
TRACKER_BROKER_IMPL=pubsub # instead of 'kafka' 

# Your Pub/Sub project ID
TRACKER_PUBSUB_PROJECT_ID=

# Pub/Sub topic ID
TRACKER_PUBSUB_TOPIC_ID=

# If tracker is NOT running in Google Cloud Environment
# (where credentials are passed automatically if service account is attached),
# please specify path to the service account JSON keys.
GOOGLE_APPLICATION_CREDENTIALS="/path/to/keys/file.json"
```

To work with Pub/Sub service, you need to create a Google service account. Please follow [the documentation](https://cloud.google.com/iam/docs/creating-managing-service-accounts) for further instruction. 
After you create the service account, make sure you assign  **Pub/Sub Publisher** and  **Pub/Sub Viewer** permissions to the account, since these are required by the Tracker.

Last, configure Telegraf instance to consume messages from Pub/Sub. Please see the [Telegraf section](#replacing-kafka-with-pubsub) for more information. 

_Note: Telegraf also requires Google service account to read data from a Pub/Sub subscription. 
If you use the same service account for both Tracker and Telegraf, assign **Pub/Sub Subscriber** permission to the aforementioned service account._  

### Tracker integration with CMS/CRM

It's highly recommended to use Tracker API as much as possible, at least for *pageview* and *commerce* events.
You should integrate *commerce* event tracking from your CRM by calling `/track/commerce` on different steps
of payment life-cycle:

* *Checkout*. User is at the checkout page, ready to confirm the order.
* *Payment*. User is being redirected to the payment gateway (e.g. Paypal).
* *Purchase*. The purchase was successful.
* *Refund*. User requested the refund which was fulfilled.

JS library provided by Beam is sending identifier `commerce_session_id` for every step and it's used to identify
unique payment process. To reach the data consistency between commerce events from JS library and CRM backend it's necessary
to send this parameter.

See the available endpoints with full JSON structure at `/swagger.json` path of running Tracker API.

#### Javascript Snippet

Any pageview-related data should be tracked from within the browser. Beam provides JS library and snippet
for tracking these kind of data.

Include following snippet into your pages and update `rempConfig` object as needed.

**Note:** make sure your HTML document working with the snippet has correctly defined character encoding. Character encoding can be specified using `meta` tag, e.g. `<meta charset="UTF-8" />`.


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

    // optional, IDs of currently active subscriptions granting user access
    subscriptionIds: [String, String, String],

    // optional, this is by default generated by remplib.js library and you don't need to override it
    browserId: String,

    // optional, controls where cookies (UTM parameters of visit) are stored
    cookieDomain: ".remp.press",

    // optional, controls which type of storage should be used by default (`local_storage` or `cookie`)
    // default is `local_storage`
    storage: "local_storage",

    // optional, specifies how long to store specific keys in storage (in minutes)
    storageExpiration: {
        // default value (in minutes) for all storage keys if not overriden in `keys`
        "default": 15,
        // specific pairs of key name and life length in minutes
        "keys": {
            "browser_id": 1051200, // 2 years in minutes
            "campaigns": 1051200
        }
    },

    // optional, article details if pageview is on the article
    article: {
        id: String, // required, ID of article in your CMS
        author_id: String, // optional, name of the author
        category: String, // optional, category/section of the article
        locked: Boolean, // optional, flag whether content was locked at the time of visit for this pageview
        contentType: String, // optional, type of the content, 'article' if not specified or 'feed'
        tags: [String, String, String], // optional, any tags associated with the article
        elementFn: Function // callback returning DOM element containing article content
    },
  
    // optional, tracking of impressions of selected items within single page
    // "impression" is counted when given item/element is visible on a page for given minimal amount of time
    // impressions tracking works for dynamically added elements too
    // this feature is useful e.g. for tracking seen article titles on index page 
    impressions: {
      enabled: Boolean, // required
      itemMinVisibleDuration: Number, // optional, number of milliseconds required for items to be counted as seen (by default 2000 ms) 
      observe: [ // required, list of configurations, each config object specifies what to observe and how to track it
        {
          // either 'type' or 'itemElementTypeFn' is required
          type: String, // type under which items are being tracked (e.g. "article"). This can be arbitrary string, it's used for labeling of stored data.
          itemElementTypeFn: Function, // type can also be retrieved using fn on observed item element

          // either or 'block' or 'blockFn' is required
          block: String, // block under which items are being tracked (e.g. "main-feed" or "sidebar"). This can be arbitrary string, it's used for labeling of stored data.
          blockFn: String, // block can be also retrieved using fn - it receives a single parameter, this "observe" configuration

          // optional, JS selector of container where items are stored. 
          // This is used for detection of dynamically added items. 
          // It's recommended to specify this, because it makes detection more efficient (otherwise whole document is being observed). 
          // If container is not found, tracking of its items is disabled.
          containerQuerySelector: String,
          
          itemElementIdFn: Function, // optional, function to return item unique id - by default element ID, is used  ('el => el.id')
          itemMinVisibleDuration: Number, // optional, overriding main 'itemMinVisibleDuration' for specific element 
        },
        // more configuration blocks can be specified here...
      ],
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
        refererMedium: "push_notification",
      
        // optional, allows forcing URL that is being tracked 
        // this value overrides both tracked URL (from window.location.href) 
        // and tracked canonical URL (from <link> tag)
        canonicalUrl: "https://something.example/article",
      
        // optional, overrides referer loaded from document.referrer
        referer: "https://google.com"
    }
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

##### Impressions tracking

Optionally, you can also enable impressions tracking feature. 

In our context, "impression" is equivalent of pageview of a single element within a page. Impression is counted when given item/element is visible (at least 50% of it) to user for given minimal amount of time. 

Impression tracking is useful e.g. for tracking dynamically added elements on a page, such as article titles or news feed.

Example:

Let's have HTML similar to this, where article titles are dynamically added to articles feed:

```html
<div id="articles-feed">
  <div id="article-1" class="article">Some title</div>
  <div id="article-2" class="article">Other title</div>
  <!--  more articles ... -->
</div>
```


To enable impression tracking for this articles feed, configure Beam JS tracker like this:
```js
var rempConfig = {
    // .. rest of the configuration  
  
    impressions: {
        enabled: true,
        observe: [
        {
          type: "article",
          block: "articles-feed", 
          itemsQuerySelector: ".article",
          containerQuerySelector: "#articles-feed",
          itemElementIdFn: el => el.id,
        },
      ]
    }
}
```


  

##### Single-page applications

If you use single-page application and need to reinitialize JS library after it's been loaded:

1. Update the `rempConfig` variable to reflect the navigation changes.
2. Call `remplib.tracker.init(rempConfig)` again to reinitialize the JS tracking state. New pageview will be tracked automatically and timespent/progress tracking will be reset.

##### JS tracking interface

Note: The *source* object is referenced as a parameter in the following API calls. Here's the list of parameters
that might be appended to target URLs within REMP tools (Campaign banner, emails) and that need to be tracked
in the functions bellow to properly track conversions against created campaigns.

The expected value is always as follows (all are optional):

```
{
  "rtm_medium": String,
  "rtm_campaign": String,
  "rtm_source": String,
  "rtm_content": String,
  "rtm_variant": String
}
```

_**Warning:** Previously, `utm_` prefix was used instead of `rtm_` (and `banner_variant` instead of `rtm_variant`), but was replaced to avoid collision with other tracking software. However, `utm_` parameters are still loaded if no `rtm_` parameters are found. This is deprecated behaviour and will be turned off in the future.
To turn it off manually, add the following setting: `rempConfig.tracker.utmBackwardCompatibilityEnabled = false`._


If the *source* is not provided, JS library tries to load the values from the visited URL.

Information about *article* is sent by default if has been provided within the initialization. You are able to change it before the tracking with function `remplib.tracker.setArticle({id: ...})`.

Here's the list of supported tracking methods:

* `remplib.tracker.trackEvent(category, action, tags, fields, source)`: tracks generic events to Beam
    * `category`: Category of event (e.g. `"spring promo"`).
    * `action`: Actual event name (e.g. `"click"`).
    * `tags`: Extra metadata you want to track with event (e.g. `{foo: bar}`).
    * `fields`: Extra metadata you want to track with event (e.g. `{foo: bar}`)).
    * `source`: Object with utm parameters (e.g. `{ utm_campaign: "foo" }`).

* `remplib.tracker.trackCheckout(funnelId, includeStorageParams)`: tracks checkout commerce event - indicating that user is summarizing the order
    * `funnelId`: Reference to funnel bringing user to checkout page. You can use IDs if your system contains referencable
    funnels or string keys otherwise. If your system doesn't support funnels and you don't need to differentiate them,
    use `"default"`.
    * `includeStorageParams`: Optional boolean flag indicates whether `source` params from local storage should be used.

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

* `remplib.tracker.trackPayment(transactionId, amount, currency, productIds, funnelId)`: tracks commerce payment event - indicating
that the payment has started (user was redirected to payment gateway)
    * `transactionId`: Reference to transaction (should be unique for every payment; e.g. `"778453213"`)
    * `amount`: Numeric amount (e.g. `18.99`)
    * `currency`: String currency (e.g. `EUR`)
    * `productIds`: List of purchased products (e.g. `["product_1"]`)
    * `funnelId`: Optional reference to funnel. You can use IDs if your system contains
      referencable funnels or string keys otherwise. 

* `remplib.tracker.trackPaymentWithSource: function(transactionId, amount, currency, productIds, article, source, funnelId)`:
tracks commerce payment event with custom article and source - indicating that the payment has started
(user was redirected to payment gateway)

* `remplib.tracker.trackPurchase(transactionId, amount, currency, productIds, funnelId)`: tracks commerce purchase event -
indicating that the payment was successful

* `remplib.tracker.trackPurchaseWithSource: function(transactionId, amount, currency, productIds, article, source, funnelId)`:
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

- Elastic ^7.5
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

#### Replacing Kafka with Pub/Sub

By default, Telegraf consumes messages from Kafka. In addition to Kafka, Remp also supports Pub/Sub message broker. 
To replace the implementation, uncomment `[[inputs.cloud_pubsub]]` section in the Telegraf configuration  file(`telegraf.conf`) and set up required variables accordingly. 

One of the variables (`credentials_file`) requires you to point to a JSON file containing Google service account keys.
To create a Google service account, consult [the documentation](https://cloud.google.com/iam/docs/creating-managing-service-accounts).
In addition, make sure the service account has **Pub/Sub Subscriber** permission assigned to the particular subscription (variable `subscription`), since it's required for consuming messages.

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

* Schedule the `service:elastic-write-alias-rollover` command to be run periodically against write alias (`pageviews_write`). Once the conditions
are met, Elasticsearch will create new index. Options:
  * `--host`: address to your Elastic instance
  * `--write_alias`: name of the write alias you use
  * `--read-alias`: name of the read alias you use
  * `--max-size`: trigger condition - index reaches a certain size
  * `--max-age`: trigger condition - maximum elapsed time from index creation is reached
  * `--max-primary-shard-size`: trigger condition - largest primary shard in the index reaches a certain size

```
php artisan service:elastic-write-alias-rollover --host=http://localhost:9200 --write-alias=pageviews_write --read-alias=pageviews --max-size=5gb
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

## Healthcheck

Route `http://beam.remp.press/health` provides health check for database, Redis, storage and logging.

Returns:

- **200 OK** and JSON with list of services _(with status "OK")_.
- **500 Internal Server Error** and JSON with description of problem. E.g.:

    ```
    {
      "status":"PROBLEM",
      "log":{
        "status":"PROBLEM",
        "message":"Could not write to log file",
        "context": // error or thrown exception...
      //...
    }
    ```
