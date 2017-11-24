import Remplib from 'remp/js/remplib'

remplib = typeof(remplib) === 'undefined' ? {} : remplib;

(function(mocklib) {

    'use strict';

    let prodlib = Remplib;

    prodlib.tracker = {

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

        segmentProviderKey: "remp_segment",

        eventRulesMapKey: "event_rules_map",

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

            // global
            this.beamToken = config.token;
            if (typeof config.userId !== 'undefined' && config.userId !== null) {
                remplib.userId = config.userId;
            }
            if (typeof config.signedIn !== 'undefined') {
                if (typeof config.signedIn !== 'boolean') {
                    throw "remplib: configuration signedIn invalid (boolean required): "+config.signedIn
                }
                if (config.signedIn && remplib.userId === null) {
                    throw "remplib: cannot set signedIn flag when no userId was provided"
                }
                remplib.signedIn = config.signedIn;
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
            } else {
                this.article = null;
            }

            this.parseUriParams();

            window.addEventListener("campaign_showtime", this.syncSegmentRulesCache);
            window.addEventListener("campaign_showtime", this.syncEventRulesMap);
            window.addEventListener("beam_event", this.incrementSegmentRulesCache);
        },

        syncSegmentRulesCache: function(e) {
            if (!e.detail.hasOwnProperty(remplib.tracker.segmentProviderKey)) {
                return;
            }
            if (!e.detail[remplib.tracker.segmentProviderKey].hasOwnProperty("cache")) {
                return;
            }
            localStorage.setItem(remplib.segmentProviderCacheKey, JSON.stringify(e.detail[remplib.tracker.segmentProviderKey]["cache"]));
        },

        syncEventRulesMap: function(e) {
            if (!e.detail.hasOwnProperty(remplib.tracker.segmentProviderKey)) {
                return;
            }
            if (!e.detail[remplib.tracker.segmentProviderKey].hasOwnProperty("event_rules")) {
                return;
            }
            localStorage.setItem(remplib.tracker.eventRulesMapKey, JSON.stringify(e.detail[remplib.tracker.segmentProviderKey]["event_rules"]));
        },

        incrementSegmentRulesCache: function(event) {
            let cache = remplib.getFromStorage(remplib.segmentProviderCacheKey, true);
            if (!cache) {
                return;
            }
            let eventRules = remplib.getFromStorage(remplib.tracker.eventRulesMapKey, true);
            if (!eventRules) {
                return;
            }

            // check whether some rule uses triggered event
            let params = event.detail;
            let key = params["_category"] + "/" + params["_action"];
            if (!eventRules.hasOwnProperty(key)) {
                return;
            }

            // increment counts where applicable
            for (let ruleId of eventRules[key]) {
                if (!cache.hasOwnProperty(ruleId)) {
                    continue;
                }
                cache[ruleId]["c"] += 1;
            }
            localStorage.setItem(remplib.segmentProviderCacheKey, JSON.stringify(cache));

        },

        run: function() {
            this.trackPageview();
        },

        trackEvent: function(category, action, tags, fields, value) {
            var params = {
                "category": category,
                "action": action,
                "tags": tags || {},
                "fields": fields || {},
                "value": value
            };
            this.post(this.url + "/track/event", params);
            this.dispatchEvent(category, action, params);
        },

        trackPageview: function() {
            var params = {
                "article": this.article,
            };
            this.post(this.url + "/track/pageview", params);
            this.dispatchEvent("pageview", "show", params);
        },

        trackCheckout: function(funnelId) {
            var params = {
                "step": "checkout",
                "article": this.article,
                "checkout": {
                    "funnel_id": funnelId
                }
            };
            this.post(this.url + "/track/commerce", params);
            this.dispatchEvent("commerce", "checkout", params);
        },

        trackPayment: function(transactionId, amount, currency, productIds) {
            var params = {
                "step": "payment",
                "article": this.article,
                "payment": {
                    "transaction_id": transactionId,
                    "revenue": {
                        "amount": amount,
                        "currency": currency
                    },
                    "product_ids": productIds
                }
            };
            this.post(this.url + "/track/commerce", params);
            this.dispatchEvent("commerce", "payment", params);
        },

        trackPurchase: function(transactionId, amount, currency, productIds) {
            var params = {
                "step": "purchase",
                "article": this.article,
                "purchase": {
                    "transaction_id": transactionId,
                    "revenue": {
                        "amount": amount,
                        "currency": currency
                    },
                    "product_ids": productIds
                }
            };
            this.post(this.url + "/track/commerce", params);
            this.dispatchEvent("commerce", "purchase", params);
        },

        trackRefund: function(transactionId, amount, currency, productIds) {
            var params = {
                "step": "refund",
                "article": this.article,
                "refund": {
                    "amount": transactionId,
                    "revenue": {
                        "amount": amount,
                        "currency": currency
                    },
                    "product_ids": productIds
                }
            };
            this.post(this.url + "/track/commerce", params);
            this.dispatchEvent("commerce", "refund", params);
        },

        dispatchEvent: function(category, action, params) {
            params["_category"] = category;
            params["_action"] = action;
            let event = new CustomEvent("beam_event", {
                detail: params,
            });
            window.dispatchEvent(event);
        },

        post: function (path, params) {
            params = this.addParams(params);
            var xmlhttp = new XMLHttpRequest();
            xmlhttp.open("POST", path);
            xmlhttp.onreadystatechange = function (oEvent) {
                if (xmlhttp.readyState !== 4) {
                    return;
                }
                if (xmlhttp.status >= 400) {
                    console.error("remplib", JSON.parse(xmlhttp.responseText));
                }
            };
            xmlhttp.setRequestHeader("Content-Type", "application/json");
            xmlhttp.send(JSON.stringify(params));
        },

        addParams: function(params) {
            var d = new Date();
            params["system"] = {"property_token": this.beamToken, "time": d.toISOString()};
            params["user"] = {
                "id": remplib.getUserId(),
                "signed_in": remplib.signedIn,
                "url":  window.location.href,
                "source": {
                    "social": this.getSocialSource(),
                    "utm_source": this.getParam("utm_source"),
                    "utm_medium": this.getParam("utm_medium"),
                    "utm_campaign": this.getParam("utm_campaign"),
                    "utm_content": this.getParam("utm_content")
                }
            };
            var cleanup = function(obj) {
                Object.keys(obj).forEach(function(key) {
                    if (obj[key] && typeof obj[key] === 'object') cleanup(obj[key])
                    else if (obj[key] === null) delete obj[key]
                });
            };
            cleanup(params);
            return params
        },

        getSocialSource: function() {
            var source = null;
            if (document.referrer.match(/^https?:\/\/([^\/]+\.)?facebook\.com(\/|$)/i)) {
                source = "facebook";
            } else if (document.referrer.match(/^https?:\/\/([^\/]+\.)?twitter\.com(\/|$)/i)) {
                source = "twitter";
            } else if (document.referrer.match(/^https?:\/\/([^\/]+\.)?reddit\.com(\/|$)/i)) {
                source = "reddit";
            }

            var storageKey = "social_source";
            if (source === null) {
                return remplib.getFromStorage(storageKey);
            }

            var now = new Date();
            var item = {
                "version": 1,
                "value": source,
                "createdAt": now.getTime(),
                "updatedAt": now.getTime(),
            };
            localStorage.setItem(storageKey, JSON.stringify(item));
            return item.value;
        },

        getParam: function(key) {
            if (typeof this.uriParams[key] === 'undefined') {
                return remplib.getFromStorage(key);
            }

            var now = new Date();
            var item = {
                "version": 1,
                "value": this.uriParams[key],
                "createdAt": now.getTime(),
                "updatedAt": now.getTime(),
            };
            localStorage.setItem(key, JSON.stringify(item));
            return item.value;
        },

        parseUriParams: function(){
            var query = window.location.search.substring(1);
            var vars = query.split('&');
            for (var i = 0; i < vars.length; i++) {
                var pair = vars[i].split('=');
                this.uriParams[decodeURIComponent(pair[0])] = decodeURIComponent(pair[1]);
            }
        }
    };

    prodlib.tracker._ = mocklib.tracker._ || [];
    remplib = prodlib.extend(mocklib, prodlib);
    remplib.bootstrap(remplib.tracker);

})(remplib);