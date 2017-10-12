remplib = typeof(remplib) === 'undefined' ? {} : remplib;

(function(mocklib) {

    'use strict';

    var prodlib = (function() {

        return {

            beamToken: null,

            userId: null,

            signedIn: false,

            campaign: {

                _: [],

                callbackIterator: 0,

                initIterator: 0,

                url: null,

                /* JSONP START */

                showtime: {
                    name: "campaigns/showtime",
                    jsonpParameter: "data",
                    prepareData: function() {
                        return {
                            "beamToken": remplib.beamToken,
                            "userId": remplib.getUserId(),
                            "signedIn": remplib.signedIn,
                            "url": window.location.href
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

                init: function(config) {
                    if (typeof config.token !== 'string') {
                        throw "remplib: configuration token invalid or missing: "+config.token
                    }
                    if (typeof config.campaign !== 'object') {
                        throw "remplib: configuration campaign invalid or missing: "+config.campaign
                    }
                    if (typeof config.campaign.url !== 'string') {
                        throw "remplib: configuration campaign.url invalid or missing: "+config.campaign.url
                    }

                    this.url = config.campaign.url;

                    // global
                    remplib.beamToken = config.token;
                    if (typeof config.userId !== 'undefined' && config.userId !== null) {
                        remplib.userId = config.userId;
                    }
                    if (typeof config.signedIn !== 'undefined') {
                        if (typeof config.signedIn !== 'boolean') {
                            throw "remplib: configuration signedIn invalid (boolean required): "+config.signedIn
                        }
                        remplib.signedIn = config.signedIn;
                    }
                },

                run: function() {
                    this.request(this.showtime);
                },

                request: function(def) {
                    var params = {};
                    params[def.jsonpParameter] = JSON.stringify(def.prepareData());

                    this.get(this.url + "/" + def.name, params, function (data) {
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

                    remplib.loadScript(url + query + "callback=" + cb)
                },
            },

            getUserId: function() {
                if (this.userId) {
                    return this.userId;
                }
                var storageKey = "anon_id";
                var anonId = this.getFromStorage(storageKey, true);
                if (anonId) {
                    return anonId;
                }
                anonId = remplib.uuidv4();
                var now = new Date();
                var item = {
                    "version": 1,
                    "value": anonId,
                    "createdAt": now.getTime(),
                    "updatedAt": now.getTime(),
                };
                localStorage.setItem(storageKey, JSON.stringify(item));
                return anonId;
            },

            uuidv4: function() {
                var format = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx';
                if (!window.crypto) {
                    return format.replace(/[xy]/g, function(c) {
                        var r = Math.random()*16|0, v = c == 'x' ? r : (r&0x3|0x8);
                        return v.toString(16);
                    });
                }

                var nums = window.crypto.getRandomValues(new Uint8ClampedArray(format.split(/[xy]/).length - 1));
                var pointer = 0;
                return format.replace(/[xy]/g, function(c) {
                    var r = nums[pointer++] % 16,
                        v = (c === 'x') ? r : (r&0x3|0x8);
                    return v.toString(16);
                });
            },

            getFromStorage: function(key, bypassThreshold) {
                var now = new Date();
                var data = localStorage.getItem(key);
                if (data === null) {
                    return null;
                }

                var item = JSON.parse(data);
                var threshold = new Date(now.getTime() - this.cacheThreshold * 60000);
                if (!bypassThreshold && (new Date(item.updatedAt)).getTime() < threshold.getTime()) {
                    localStorage.removeItem(key);
                    return null;
                }

                item.updatedAt = now;
                localStorage.setItem(key, JSON.stringify(item));
                return item.value;
            },

            extend: function() {
                var a, b, c, f, l, g = arguments[0] || {}, k = 1, v = arguments.length, n = !1;
                "boolean" === typeof g && (n = g,
                    g = arguments[1] || {},
                    k = 2);
                "object" === typeof g || d.isFunction(g) || (g = {});
                v === k && (g = this,
                    --k);
                for (; k < v; k++)
                    if (null != (a = arguments[k]))
                        for (b in a)
                            c = g[b],
                                f = a[b],
                            g !== f && (n && f && (d.isPlainObject(f) || (l = d.isArray(f))) ? (l ? (l = !1,
                                c = c && d.isArray(c) ? c : []) : c = c && d.isPlainObject(c) ? c : {},
                                g[b] = d.extend(n, c, f)) : void 0 !== f && (g[b] = f));
                return g
            },

            bootstrap: function(self) {
                for (var i=0; i < self._.length; i++) {
                    var cb = self._[i];
                    setTimeout((function() {
                        var cbf = cb[0];
                        var cbargs = cb[1];
                        return function() {
                            if (cbf !== "run") {
                                self[cbf].apply(self, cbargs);
                            }
                            self.initIterator++;
                            if (self.initIterator === self._.length) {
                                self.run();
                            }
                        }
                    })(), 0);
                }
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
    }());

    prodlib.campaign._ = mocklib.campaign._ || [];
    remplib = prodlib.extend(mocklib, prodlib);
    remplib.bootstrap(remplib.campaign);

})(remplib);