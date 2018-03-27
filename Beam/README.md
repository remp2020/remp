# Beam

## Admin (Laravel)

Beam Admin serves as a tool for configuration of sites, properties and segments. It's the place to get tracking snippets
and manage metadata about your websites.

When the backend is ready, don't forget to create `.env` file (use `.env.example` as boilerplate), install dependencies and run DB migrations:

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

- PHP ^7.1
- MySQL ^5.7
- Redis ^3.2

### Schedule

For application to function properly you need to add Laravel's schedule running into your crontab:

```
* * * * * php artisan schedule:run >> storage/logs/schedule.log 2>&1
```

Laravel's scheduler currently includes:

* *aggregate:pageview-load*: aggregates article-based pageview data
* *aggregate:pageview-timespent*: aggregates article-based timespent data

## [Tracker](go/cmd/tracker) (Go)

Beam Tracker serves as a tool for tracking events via API. Endpoints can be discovered via generated `/swagger.json`.

Tracker pushes events to Kafka in Influx format into `beam_events` topic. For all `/track/event` calls, Tracker also
pushes raw JSON event to its own topic based on the event *category* and *action*.

For example for payload

```json
{
  "category": "foo",
  "action": "bar"
  // ...
}
```

the event would be stored within `foo_bar` topic so everyone can subscribe to it.

#### Dependencies

- Go ^1.8
- Kafka ^0.10
- Zookeeper ^3.4
- MySQL ^5.7
    
## [Segments](go/cmd/segments) (Go)

Beam Segments serves as a read-only API for getting information about segments and users of these segments.
Endpoints can be discovered via generated `/swagger.json`.

#### Dependencies

- Go ^1.8
- InfluxDB ^1.2
- MySQL ^5.7

## [Telegraf](../Docker/telegraf)

Influx Telegraf is a backend service for moving data out of Kafka to InfluxDB. It needs to be ready as Segments are
dependent on Influx-based data pushed by Telegraf.

## [Kafka](../Docker/kafka)

All tracked events are also pushed to Kafka.

## Javascript Snippet

Include following snippet into the page to track events. Update `rempConfig` object as needed.

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
    }

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

var rempConfig = {
    // UUIDv4 based REMP BEAM token of appropriate property
    // (see BEAM Admin -> Properties)
    token: String,
    
    // optional
    userId: String,
    
    // optional, flag whether user is a subscriber
    userSubscribed: Boolean,
    
    // optional
    browserId: String,
    
    // optional, controls where are cookies stored
    cookieDomain: ".remp.press",
            
    tracker: {
        // required URL location of BEAM Tracker
        url: "http://tracker.beam.remp.press",
        
        // optional article details
        article: {
            id: String, // optional
            author_id: String, // optional
            category: String, // optional
            locked: Boolean, // optional, flag whether content was locked
            tags: [String, String, String] // optional
        },
        
        // optional time spent measuring (default value `false`)
        // if enabled, tracks time spent on current page
        timeSpentEnabled: true,
    },
};
remplib.tracker.init(rempConfig);
```

## Iota (on-site reporting)

We've built a tool able to display article-based statistic directly on-site for your editors.
Currently we support:

- Revenue-based statistics
- Event-based statistics (primarily for reporting A/B test data)

To initialize, put `iota` property into the `rempConfig` variable:

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

## Known issues

### Some internal API calls are not going through

#### What probably happened

Event groups, categories and actions can contain words like _pageviews_, _banners_, _track_ blocked by ad / track blockers. And they are loaded for segment's rules via API call. URL can look like this _(group == pageviews; category == pageview)_:

`https://beam.remp.press/api/journal/pageviews/categories/pageview/actions`


#### How to solve

Add filter to your blocker which allows these calls.

_Example of uBlock Origin / AdBlockPlus syntax filter_ if your BEAM domain is `beam.remp.press`:

```
! REMP - allow BEAM journal API calls with blocked words (eg 'pageviews', 'banners', ...)
@@||beam.remp.press/api/journal/*$xmlhttprequest,domain=beam.remp.press
```
