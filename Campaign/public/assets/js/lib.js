Campaign = typeof(Campaign) === 'undefined' ? {} : Campaign;

(function(config) {

    'use strict';

    Campaign.lib = {

        userId: null,

        init: function(rempConfig) {
            if (typeof rempConfig === 'undefined') {
                console.warn("Unable to initialize REMP Campaign JS lib, rempConfig is missing");
            }

            if (typeof rempConfig["userId"] !== "undefined") {
                this.userId = rempConfig["userId"]
            }
        },

        run: function() {
            this.loadScript("http://rempcampaign.local/campaigns/showtime?userId=" + this.userId)
        },

        loadScript: function (src, callback) {
            var s = document.createElement('script');
            s.src = src;
            s.async = true;
            s.onreadystatechange = s.onload = function() {
                if (typeof callback !== 'undefined' && !callback.done && (!s.readyState || /loaded|complete/.test(s.readyState))) {
                    callback.done = true;
                    callback();
                }
            };
            document.querySelector('head').appendChild(s);
        },

        loadStyle: function (src, callback) {
            var l = document.createElement('link');
            l.href = src;
            l.rel = "stylesheet";
            l.onreadystatechange = l.onload = function() {
                if (typeof callback !== 'undefined' && !callback.done && (!l.readyState || /loaded|complete/.test(l.readyState))) {
                    callback.done = true;
                    callback();
                }
            };
            document.querySelector('head').appendChild(l);
        }

    };

    Campaign.lib.init(config);
    Campaign.lib.run();

})(rempCampaign);