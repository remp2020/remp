# Beam

## Admin (Laravel)

Beam Admin serves as a tool for configuration of sites, properties and segments. It's the place to get tracking snippets
and manage metadata about your websites.

When the backend is ready, don't forget to install dependencies and run DB migrations:

```bash
# 1. Download PHP dependencies
composer install

# 2. Download JS/HTML dependencies
yarn install

# !. use extra switch if your system doesn't support symlinks (Windows; can be enabled)
yarn install --no-bin-links

# 3. Generate assets
yarn run dev // or any other alternative defined within package.json

# 4. Run migrations
php artisan migrate
```

#### Dependencies

- PHP 7.1
- MySQL 5.7
- Redis 3.2

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
<script type="text/javascript">
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
        load("http://beam.remp.press/assets/vendor/js/remplib.js");
    })(window, document);

    var rempConfig = {
        // UUIDv4 based REMP BEAM token of appropriate property
        // (see BEAM Admin -> Properties)
        token: String,
        
        // optional
        userId: String,
        
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
                tags: [String, String, String] // optional
            },
        },
        
        segments: {
            // optional URL of BEAM segments API; required if Iota is used
            url: "http://segments.beam.remp.press"
        },
        
        iota: {
            // required: selector matching all "article" elements on site you want to be reported
            articleSelector: String,
            // required: callback for articleId extraction out of matched element
            idCallback: Function, // function (matchedElement) {}
            // optional: callback for selecting element where the stats will be placed as next sibling; if not present, stats are appended as next sibling to matchedElement
            targetElementCallback: Function, // function (matchedElement) {}
            // optional: HTTP headers to be used in API calls 
            httpHeaders: Object
        }
    };
    remplib.tracker.init(rempConfig);
</script>

```

## Iota (on-site reporting)

We've built a tool able to display article-based statistic directly on-site for your editors.
Currently we support:

- Revenue-based statistics
- Event-based statistics (primarily for reporting A/B test data)

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
