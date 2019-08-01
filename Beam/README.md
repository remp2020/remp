# Beam

Beam consist of Beam web admin and two separate API microservices for tracking the data and
reading/aggregating them.

* Admin
  * [Integration with CMS/CRM](#admin-integration-with-cmscrm)
* Tracker API
  * [Integration with CMS/CRM](#tracker-integration-with-cmscrm)
* Segments/Journal API

## Admin (Laravel)

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

# 6. Create elastic structure for campaign stats
curl -XPUT -H "Content-Type: application/json" elastic:9200/commerce -d '{"mappings": {"_doc": {"properties": {"revenue": {"type": "double"}}}}}' 
curl -XPUT -H "Content-Type: application/json" elastic:9200/events -d '{"mappings": {"_doc": {}}}'
curl -XPUT -H "Content-Type: application/json" elastic:9200/pageviews -d '{"mappings": {"_doc": {}}}'
curl -XPUT -H "Content-Type: application/json" elastic:9200/pageviews_time_spent -d '{"mappings": {"_doc": {}}}'
curl -XPUT -H "Content-Type: application/json" elastic:9200/pageviews_progress -d '{"mappings": {"_doc": {}}}'
curl -XPUT -H "Content-Type: application/json" elastic:9200/concurrents_by_browser -d '{"mappings": {"_doc": {}}}'
curl -XPUT -H "Content-Type: application/json" elastic:9200/entities -d '{"mappings": {"_doc": {}}}'

# 7. Run seeders (optional)
php artisan db:seed


```

### Dependencies

- PHP ^7.1.3
- MySQL ^5.7
- Redis ^3.2
- Segments API (see #segments-go)

### Admin integration with CMS/CRM

Beam itself serves as a tool for tracking the data and displaying them in a nice fashion. 

All requests should contain (and be compliant) with the follow HTTP headers. In the default configuration you
should use `API_TOKEN` generated in SSO web administration. 

```
Content-Type: application/json
Accept: application/json
Authorization: Bearer API_TOKEN
```

##### Article tracking

`POST /api/articles/upsert`

Your CMS should track all article-related changes to Beam. The data includes only the basic information
about article so Beam could link other statistics to the article/author (e.g. conversion, pageviews) and
data related to Beam (e.g. A/B testing of titles).

```
{
    "articles": [Article]
}

Article = {
    "external_id": String, // Required; ID of article in your CMS
    "property_uuid": String, // Required; Beam property token, you can get it in Beam admin - Properties
    "title": String, // Required; Primary title of the article
    "titles": [ // Optional; If A/B test of titles is used, you can track the titles here
        String, // Title of variant "A"
        String // Title of variant "B"
    ],
    "url": String // Public URL of the article
    "authors": [
        String // Name of the author
    ],
    "sections": [
        String // Name of the section
    ],
    "published_at": String // RFC3339 formatted datetime  
}
```

Any create/update matching is based on the article's `external_id`. You're free to update
the article as many times as you want.

##### Conversion tracking

`POST api/conversions/upsert`

Beam admin provides statistics about article/author performance. One of the metrics used are conversions.
This endpoint stores minimal conversion data. Extended data should be additionally tracked via Tracker API.

```
{
    "conversions": [Conversion]
}

Conversion = {
    "article_external_id": String, // Required; ID of article in your CMS
    "transaction_id": String, // Required; ID of transaction (unique for each transaction)
    "amount": Number, // Required; Nominal amount of the transaction, e.g. 10.0 
    "currency": String, // Required; Currency of the transaction, e.g. "EUR"
    "paid_at": String, // Required; RFC3339 formatted datetime with date of the transaction
    "user_id": String // Optional; Identifier of user who made a transaction
}
```

### REMP-connected features

#### Automatic newsletters

When Beam is deployed alongside with Mailer, you can configure automatic newsletters to be sent periodically.

You can leverage data tracked to Beam to automatically select articles and personalize content of emails for
each user separately.

You are able to configure:

* *Mail type*. The target mail type (effectively newsletter) to be used.
* *Mailer generator*. Generator is an implementation which receives dynamic data (from Beam) and generates
email content based on that. The [default generator implementation](https://github.com/remp2020/remp/blob/master/Mailer/app/models/Generators/GenericBestPerformingArticlesGenerator.php)
is already provided by Mailer, you can always register [your own generator](https://github.com/remp2020/remp/blob/master/Mailer/app/models/Generators/DennikNBestPerformingArticlesGenerator.php)
if you need it.
* *Segment*. Target segment of users.
* *Criteria*. Criteria based on which the articles to the newsletter are selected
(publish timespan, how many articles, most read/most paid/longest read)
* *Personalized contend*. Flag, whether email content should be personalized for each user (people will
only get articles they haven't read yet)
* *Scheduling*. When and how often to send a newsletter.

### Schedule

For application to function properly you need to add Laravel's schedule running into your crontab:

```
* * * * * php artisan schedule:run >> storage/logs/schedule.log 2>&1
```

Laravel's scheduler currently includes:

* *pageviews:aggregate-load*: Reads pageview/load data from journal and stores aggregated data
* *pageviews:aggregate-timespent*: Reads pageview/timespent data from journal and stores aggregated data
* *pageviews:loyal-visitors*: Determines number of articles read by top 10% of readers and creates segment based on it
* *pageviews:process-sessions*: Reads and parses session referers tracked within Beam


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

##### Javascript Snippet

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
            
    // required, Tracker API specific options          
    tracker: {
        // required, URL location of BEAM Tracker
        url: "http://tracker.beam.remp.press",
        
        // optional, article details if pageview is on the article
        article: {
            id: String, // required, ID of article in your CMS
            author_id: String, // optional, name of the author
            category: String, // optional, category/section of the article
            locked: Boolean, // optional, flag whether content was locked at the time of visit for this pageview
            tags: [String, String, String] // optional, any tags associated with the article
        },
        
        // optional time spent measuring (default value `false`)
        // if enabled, tracks time spent on current page
        timeSpentEnabled: true,
        
        // optional, achieved scroll depth tracking (default value `false`)
        readingProgress: {
            enabled: Boolean, // if enabled, tracks achieved scroll depth
            interval: Number // optional, frequency of sending tracked progress in seconds (default value 5)
        }
    },
};
remplib.tracker.init(rempConfig);
```

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
    // optional: callback for selecting element where the stats will be placed as next sibling; if not present, stats are appended as next sibling to matchedElement
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

### Authors segments

If some of your readers are interested only in couple of authors, Beam can identify such users and generate
"authors segments". Each author segment contain users, which are returning back and reading mostly the author
that the segment belongs to. 

Authors segments are computed for each author separately using Artisan command:

`php artisan segments:compute-author-segments`

Each segment contains users and browsers assigned to it according to criteria, which can be adjusted in
Beam admin settings page (`/settings`).

The command is not run by default (therefore no author segments exist), one has to run it manually or
schedule it to recompute segments periodically (recommended). 

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
