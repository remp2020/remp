remplib = typeof(remplib) === 'undefined' ? {} : remplib;

(function(mocklib) {

    'use strict';

    var prodlib = (function() {

        return {

            beamToken: null,

            userId: null,

            tracker: {

                url: null,

                social: null,

                campaign_id: null,

                _: [],

                callbackIterator: 0,

                initIterator: 0,

                article: {
                    id: null,
                    author_id: null,
                    category: null,
                    tags: []
                },

                uriParams: {},

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

                    if (typeof config.tracker.article === 'object') {
                        if (typeof config.tracker.article.id === 'undefined' || config.tracker.article.id === null) {
                            throw "remplib: configuration tracker.article.id invalid or missing: "+config.tracker.article.id
                        }
                        this.article.id = config.tracker.article.id;
                        if (typeof config.tracker.article.campaign_id !== 'undefined') {
                            this.article.campaign_id = config.tracker.article.campaign_id;
                        }
                        if (typeof config.tracker.article.author_id !== 'undefined') {
                            this.article.author_id = config.tracker.article.author_id;
                        }
                        if (typeof config.tracker.article.category !== 'undefined') {
                            this.article.category = config.tracker.article.category;
                        }
                        if (config.tracker.article.tags instanceof Array) {
                            this.article.tags = config.tracker.article.tags;
                        }
                    }

                    this.parseUriParams();
                },

                run: function() {
                    if (this.article.id !== null) {
                        this.trackPageview();
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
                        "article": this.article
                    };
                    this.post(this.url + "/track/pageview", params)
                },

                trackCommerce: function() {
                    // not implemented
                },

                post: function (path, params) {
                    params = this.addParams(params);
                    var xmlhttp = new XMLHttpRequest();
                    xmlhttp.open("POST", path);
                    xmlhttp.setRequestHeader("Content-Type", "application/json");
                    xmlhttp.send(JSON.stringify(params));
                },

                addParams: function(params) {
                    var d = new Date();
                    params["system"] = {"property_token": this.beamToken, "time": d.toISOString()};
                    params["user"] = {"id": this.userId, "url":  window.location.href, "user_agent": navigator.userAgent};

                    if (document.referrer.match(/^https?:\/\/([^\/]+\.)?facebook\.com(\/|$)/i)) {
                        params["social"] = "facebook";
                    } else if (document.referrer.match(/^https?:\/\/([^\/]+\.)?twitter\.com(\/|$)/i)) {
                        params["social"] = "twitter";
                    } else if (document.referrer.match(/^https?:\/\/([^\/]+\.)?reddit\.com(\/|$)/i)) {
                        params["social"] = "reddit";
                    }

                    if (typeof this.uriParams["utm_source"] !== 'undefined') {
                        params["utm_source"] = this.uriParams["utm_source"];
                        params["utm_medium"] = this.uriParams["utm_medium"];
                        params["utm_campaign"] = this.uriParams["utm_campaign"];
                        params["utm_content"] = this.uriParams["utm_content"];
                    }

                    return params
                },

                parseUriParams: function(){
                    var query = window.location.search.substring(1);
                    var vars = query.split('&');
                    for (var i = 0; i < vars.length; i++) {
                        var pair = vars[i].split('=');
                        this.uriParams[decodeURIComponent(pair[0])] = decodeURIComponent(pair[1]);
                    }
                }
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
            }
        };
    }());

    prodlib.tracker._ = mocklib.tracker._ || [];
    remplib = prodlib.extend(mocklib, prodlib);
    remplib.bootstrap(remplib.tracker);

})(remplib);