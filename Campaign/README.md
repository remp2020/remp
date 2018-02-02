# Campaign

## Admin (Laravel)

Campaign Admin serves as a tool for configuration of banners and campaigns. It's the place for UI generation of banners
and definition of how and to whom display Campaigns. 

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

### Dependencies

- PHP 7.1
- MySQL 5.7
- Redis ^3.2

### Schedule

For application to function properly you need to add Laravel's schedule running into your crontab:

```
* * * * * php artisan schedule:run >> storage/logs/schedule.log 2>&1
```

Laravel's scheduler currently includes:

*CacheSegmentJob*:

- Triggered hourly and forced to refresh.

### Queue

For application to function properly, you also need to have Laravel's queue worker running as a daemon. Please follow the
official documentation's [guidelines](https://laravel.com/docs/5.4/queues#running-the-queue-worker).

```bash
php artisan queue:work
```

Laravel's queue currently includes

*CacheSegmentJob*: 

- Triggered when campaign is activated. 
- Trigerred when cached data got invalidated and need to be fetched again.

If the data are still valid, job doesn't refresh them.


## Javascript snippet

Include following snippet into the page to process campaigns and display banners. Update `rempConfig` object as needed.

Note: If you want to automatically track banner events to BEAM Tracker, add also `tracker` property to `rempConfig` object. See [BEAM README](../Beam/README.md) for details.
              
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
        if (!win.remplib.campaign) {
            var fn, i, funcs = "init".split(" ");
            win.remplib.campaign = {_: []};
            for (i = 0; i < funcs.length; i++) {
                fn = funcs[i];
                win.remplib.campaign[fn] = mock(fn);
            }
        }
        // change URL to location of CAMPAIGN remplib.js
        load("http://campaign.remp.press/assets/lib/js/remplib.js");
    })(window, document);

    var rempConfig = {
        // UUIDv4 based REMP BEAM token of appropriate property
        // (see BEAM Admin -> Properties)
        // required if you're using REMP segments
        token: String,
        
        // optional
        userId: String,
        
        // optional
        browserId: String,
        
        // signedIn indicates if user is signed in
        // userId must be provided if signedIn is set
        // optional
        signedIn: Boolean,
              
        // optional, controls where are cookies stored
        cookieDomain: ".remp.press",

        
        // required
        campaign: {
            // required URL location of REMP CAMPAIGN
            url: "http://campaign.remp.press",
            
            variables: {
                // variables replace template placeholders in banners,
                // e.g. {{ email }} -> foo@example.com
                //
                // the callback doesn't pass any parameters, it's required for convenience and just-in-time evaluation
                //
                // missing variable is translated to empty string
                email: {
                    value: function() {
                        return "foo@example.com"
                    }
                }
            }
        }
        
        // if you want to automatically track banner events to BEAM Tracker,
        // add also rempConfig.tracker property
        //
        // see REMP BEAM README.md
        
    };
    remplib.campaign.init(rempConfig);
</script>
```
