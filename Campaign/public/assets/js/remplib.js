remplib = typeof(remplib) === 'undefined' ? {} : remplib;

(function() {

    'use strict';

    remplib = {

        token: null,

        userId: null,

        callbackIterator: 0,

        initIterator: 0,

        target: "http://rempcampaign.local",

        /* JSONP START */

        showtime: {
            name: "campaigns/showtime",
            jsonpParameter: "data",
            prepareData: function() {
                return {
                    "userId": remplib.userId
                }
            },
            processResponse: function(result) {
                if (!result["success"]) {
                    return;
                }
                for (var exec = result.data, c = 0; c < result.data.length; c++) {
                    try {
                        var fn = new Function(exec[c]);
                        setTimeout(fn(), 0);
                    } catch (u) {
                        console.error("campaign showtime error:", u)
                    }
                }
            }
        },

        /* JSONP END */

        init: function() {
            if (!remp._ instanceof Array) {
                return;
            }
            var self = this;
            for (var i=0; i < remp._.length; i++) {
                var cb = remp._[i];
                setTimeout((function() {
                    var cbf = cb[0];
                    var cbargs = cb[1];
                    return function() {
                        remplib[cbf].apply(self, cbargs)
                        self.initIterator++;
                        if (self.initIterator === remp._.length) {
                            self.run();
                        }
                    }
                })(), 0);
            }
        },

        run: function() {
            remplib.request(remplib.showtime);
        },

        identify: function(userId) {
            this.userId = userId;
        },

        request: function(def) {
            var params = {};
            params[def.jsonpParameter] = JSON.stringify(def.prepareData());

            this.get(this.target + "/" + def.name, params, function (data) {
                def.processResponse && def.processResponse(data);
            }, function() {
                def.processError && def.processError();
            });
        },

        get: function(url, params, success, error) {
            var query = "?";
            var cb = "rempcampaign_callback_json" + this.callbackIterator++;

            for (var item in params)
                params.hasOwnProperty(item) && (query += encodeURIComponent(item) + "=" + encodeURIComponent(params[item]) + "&");

            window[cb] = function(data) {
                success(data);
                try {
                    delete window[cb]
                } catch (_) {}
                window[cb] = null
            };

            this.loadScript(url + query + "callback=" + cb)
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
            document.getElementsByTagName('head')[0].appendChild(s);
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
            document.getElementsByTagName('head')[0].appendChild(l);
        }

    };

    remplib.init();
})();