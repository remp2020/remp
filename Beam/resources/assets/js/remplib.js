remplib = typeof(remplib) === 'undefined' ? {} : remplib;

(function(mocklib) {

    'use strict';

    var prodlib = (function() {

        return {

            beamToken: null,

            userId: null,

            tracker: {

                url: null,

                _: [],

                callbackIterator: 0,

                initIterator: 0,

                article: {
                    id: null,
                    campaign_id: null,
                    author_id: null,
                    category: null,
                    tags: [],
                },

                init: function(config) {
                    if (typeof config.token !== 'string') {
                        throw "remplib: configuration token invalid or missing: "+config.token
                    }
                    if (typeof config.tracker !== 'object') {
                        throw "remplib: configuration tracker invalid or missing: "+config.tracker
                    }
                    if (typeof config.tracker.url !== 'string') {
                        throw "remplib: configuration tracker.url invalid or missing: "+config.tracker.url
                    }

                    this.url = config.tracker.url;
                    this.beamToken = config.token;
                    if (typeof config.userId !== 'undefined' && config.userId !== null) {
                        this.userId = config.userId;
                    }

                    if (typeof config.article === 'object') {
                        if (typeof config.article.id === 'undefined' || config.article.id === null) {
                            throw "remplib: configuration tracker.article.id invalid or missing: "+config.article.id
                        }
                        this.article.id = config.article.id;
                        if (typeof config.article.campaign_id !== 'undefined') {
                            this.article.campaign_id = config.article.campaign_id;
                        }
                        if (typeof config.article.author_id !== 'undefined') {
                            this.article.author_id = config.article.author_id;
                        }
                        if (typeof config.article.category !== 'undefined') {
                            this.article.category = config.article.category;
                        }
                        if (config.article.tags instanceof Array) {
                            this.article.tags = config.article.tags;
                        }
                    }
                },

                run: function() {
                    if (this.article.id !== null) {
                        remplib.trackPageview();
                    }
                },

                trackEvent: function(category, action, fields, value) {
                    var params = {
                        "category": category,
                        "action": action,
                        "fields": fields,
                        "value": value
                    };
                    this.post(this.url + "/track/event", params)
                },

                trackPageview: function() {
                    var params = {
                        "article": this.article,
                    };
                    this.post(this.url + "/track/pageview", params)
                },

                trackCommerce: function() {
                    // not implemented
                },

                post: function (path, params) {
                    params = this.addParams(params);
                    let xmlhttp = new XMLHttpRequest();
                    xmlhttp.open("POST", path);
                    xmlhttp.setRequestHeader("Content-Type", "application/json");
                    xmlhttp.send(JSON.stringify(params));
                },

                addParams: function(params) {
                    var d = new Date();
                    params["system"] = {"property_token": this.beamToken, "time": d.toISOString()};
                    params["user"] = {"id": this.userId, "url":  window.location.href, "user_agent": navigator.userAgent};
                    return params
                },

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
        };
    }());

    prodlib.tracker._ = mocklib.tracker._ || [];
    remplib = prodlib.extend(mocklib, prodlib);
    remplib.bootstrap(remplib.tracker);

})(remplib);