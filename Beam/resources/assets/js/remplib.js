import Remplib from 'remp/js/remplib'
import Hash from 'fnv1a'

remplib = typeof(remplib) === 'undefined' ? {} : remplib;

(function(mocklib) {

    'use strict';

    let prodlib = Remplib;

    prodlib.tracker = {

        url: null,

        social: null,

        campaign_id: null,

        _: [],

        article: {
            id: null,
            author_id: null,
            category: null,
            locked: null,
            tags: [],
            variants: {},
        },

        uriParams: {},

        segmentProvider: "remp_segment",

        eventRulesMapKey: "beam_event_rules_map",

        overridableFieldsKey: "overridable_fields",

        flagsKey: "flags",

        timeSpentEnabled: false,

        totalTimeSpent: 0,

        partialTimeSpent: 0,

        timeSpentActive: false,

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
            if (typeof config.userSubscribed !== 'undefined' && config.userSubscribed !== null) {
                remplib.userSubscribed = config.userSubscribed;
            }
            if (typeof config.browserId !== 'undefined' && config.browserId !== null) {
                remplib.browserId = config.browser;
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
                if (typeof config.tracker.article.variants !== 'undefined') {
                    this.article.variants = config.tracker.article.variants;
                }
                if (typeof config.tracker.article.locked !== 'undefined') {
                    this.article.locked = config.tracker.article.locked;
                }
            } else {
                this.article = null;
            }

            if (typeof config.cookieDomain === 'string') {
                remplib.cookieDomain = config.cookieDomain;
            }

            this.parseUriParams();

            if (typeof config.tracker.timeSpentEnabled === 'boolean') {
                this.timeSpentEnabled = config.tracker.timeSpentEnabled;
            }

            window.addEventListener("campaign_showtime", this.syncSegmentRulesCache);
            window.addEventListener("campaign_showtime", this.syncEventRulesMap);
            window.addEventListener("campaign_showtime", this.syncOverridableFields);
            window.addEventListener("campaign_showtime", this.syncFlags);
            window.addEventListener("beam_event", this.incrementSegmentRulesCache);
        },

        syncSegmentRulesCache: function(e) {
            if (!e.detail.hasOwnProperty(remplib.tracker.segmentProvider)) {
                return;
            }
            if (!e.detail[remplib.tracker.segmentProvider].hasOwnProperty("cache")) {
                return;
            }
            let segmentProviderCache = remplib.getFromStorage(remplib.segmentProviderCacheKey, true);
            if (!segmentProviderCache) {
                segmentProviderCache = {};
            }
            segmentProviderCache[remplib.tracker.segmentProvider] = e.detail[remplib.tracker.segmentProvider]["cache"]
            localStorage.setItem(remplib.segmentProviderCacheKey, JSON.stringify(segmentProviderCache));
        },

        syncEventRulesMap: function(e) {
            if (!e.detail.hasOwnProperty(remplib.tracker.segmentProvider)) {
                return;
            }
            if (!e.detail[remplib.tracker.segmentProvider].hasOwnProperty("event_rules")) {
                return;
            }
            localStorage.setItem(remplib.tracker.eventRulesMapKey, JSON.stringify(e.detail[remplib.tracker.segmentProvider]["event_rules"]));
        },

        syncOverridableFields: function(e) {
            if (!e.detail.hasOwnProperty(remplib.tracker.segmentProvider)) {
                return;
            }
            if (!e.detail[remplib.tracker.segmentProvider].hasOwnProperty("overridable_fields")) {
                return;
            }
            localStorage.setItem(remplib.tracker.overridableFieldsKey, JSON.stringify(e.detail[remplib.tracker.segmentProvider]["overridable_fields"]));
        },

        syncFlags: function(e) {
            if (!e.detail.hasOwnProperty(remplib.tracker.segmentProvider)) {
                return;
            }
            if (!e.detail[remplib.tracker.segmentProvider].hasOwnProperty("flags")) {
                return;
            }
            localStorage.setItem(remplib.tracker.flagsKey, JSON.stringify(e.detail[remplib.tracker.segmentProvider]["flags"]));
        },

        incrementSegmentRulesCache: function(event) {
            let cache = remplib.getFromStorage(remplib.segmentProviderCacheKey, true);
            if (!cache || !cache.hasOwnProperty(remplib.tracker.segmentProvider)) {
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
                const flagsValid = remplib.tracker.validateFlags(ruleId);
                if (!flagsValid) {
                    continue;
                }

                const of = remplib.getFromStorage(remplib.tracker.overridableFieldsKey, true) || [];
                if (!of.hasOwnProperty(ruleId)) {
                    console.warn("remplib: missing overridable fields for rule " + ruleId);
                    continue;
                }

                const cacheKey = remplib.tracker.segmentRuleKey(ruleId, of[ruleId], params);
                if (!cache[remplib.tracker.segmentProvider].hasOwnProperty(cacheKey)) {
                    continue;
                }
                cache[remplib.tracker.segmentProvider][cacheKey]["c"] += 1;
            }
            localStorage.setItem(remplib.segmentProviderCacheKey, JSON.stringify(cache));

        },

        // checks if all flags are matched against provided config
        validateFlags: function(ruleId) {
            let flags = remplib.getFromStorage(remplib.tracker.flagsKey, true) || [];
            if (!flags.hasOwnProperty(ruleId)) {
                console.warn("remplib: missing flags for rule " + ruleId);
                return false;
            }

            for (let f of Object.keys(flags[ruleId])) {
                // duplicating flag handling from track/pageviews API call
                if (f === '_article') {
                    if (flags[ruleId][f] === '1' && !remplib.tracker.article) {
                        return false;
                    }
                    if (flags[ruleId][f] === '0' && remplib.tracker.article) {
                        return false;
                    }
                }
            }
            return true;
        },

        // this function needs to work the same way as SegmentRule.getCacheKey on the segments backend.
        segmentRuleKey: function(ruleId, overridableFields, params) {
            let k = ruleId.toString();
            overridableFields = overridableFields.sort();
            for (let f of overridableFields) {
                k += "_" + (params['tags'][f] || params['fields'][f] || '');
            }
            return Hash(k);
        },

        run: function() {
            this.trackPageview();

            if (this.timeSpentEnabled === true) {
                this.bindTickEvents();
                this.tickTime();
            }
        },

        trackEvent: function(category, action, tags, fields, value) {
            var params = {
                "category": category,
                "action": action,
                "tags": tags || {},
                "fields": fields || {},
                "value": value
            };
            params = this.addSystemUserParams(params);
            this.post(this.url + "/track/event", params);
            this.dispatchEvent(category, action, params);
        },

        trackPageview: function() {
            var params = {
                "article": this.article,
                "action": "load",
            };
            params = this.addSystemUserParams(params);
            this.post(this.url + "/track/pageview", params);
            this.dispatchEvent("pageview", "load", params);
        },

        trackTimespent: function(closed = false) {
            // PRE ES2015 safeguard
            closed = (typeof closed === 'boolean') ? closed : false;

            // send 0 time spent in case it is unload event
            if (this.partialTimeSpent === 0 && closed === false) {
                return;
            }

            let params = {
                "article": this.article,
                "action": "timespent",
                "timespent": {
                    "seconds": this.partialTimeSpent,
                    "unload": closed,
                }
            };
            params = this.addSystemUserParams(params);
            params = this.timespentParamsCleanup(params);

            this.partialTimeSpent = 0; // start counting from 0 again

            this.post(this.url + "/track/pageview", params);
            this.dispatchEvent("pageview", "timespent", params);
        },

        trackCheckout: function(funnelId) {
            var params = {
                "step": "checkout",
                "article": this.article,
                "checkout": {
                    "funnel_id": funnelId
                }
            };
            params = this.addSystemUserParams(params);
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
            params = this.addSystemUserParams(params);
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
            params = this.addSystemUserParams(params);
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
            params = this.addSystemUserParams(params);
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

        addSystemUserParams: function(params) {
            const d = new Date();
            params["system"] = {"property_token": this.beamToken, "time": d.toISOString()};
            params["user"] = {
                "id": remplib.getUserId(),
                "browser_id": remplib.getBrowserId(),
                "subscriber": remplib.isUserSubscriber(),
                "url":  window.location.href,
                "referer": document.referrer,
                "user_agent": window.navigator.userAgent,
                "source": {
                    "ref": this.getRefSource(),
                    "social": this.getSocialSource(),
                    "utm_source": this.getParam("utm_source"),
                    "utm_medium": this.getParam("utm_medium"),
                    "utm_campaign": this.getParam("utm_campaign"),
                    "utm_content": this.getParam("utm_content")
                }
            };
            params["user"][remplib.rempSessionIDKey] = remplib.getRempSessionID();
            params["user"][remplib.rempPageviewIDKey] = remplib.getRempPageviewID();

            var cleanup = function(obj) {
                Object.keys(obj).forEach(function(key) {
                    if (obj[key] && typeof obj[key] === 'object') cleanup(obj[key])
                    else if (obj[key] === null) delete obj[key]
                });
            };
            cleanup(params);
            return params
        },

        timespentParamsCleanup: function(params) {
            delete params.user.url;
            delete params.user.referer;
            delete params.user.user_agent;
            delete params.user.source.utm_source;
            delete params.user.source.utm_medium;
            delete params.user.source.utm_campaign;
            delete params.user.source.utm_content;
            return params;
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
                "createdAt": now,
                "updatedAt": now,
            };
            localStorage.setItem(storageKey, JSON.stringify(item));
            return item.value;
        },

        getRefSource: function() {
            var source = null;
            if (document.referrer === "" || document.referrer.match(/^https?:\/\/([^\/]+\.)? + window.location.hostname + (\/|$)/i)) {
                source = "direct";
            }
            return source;
        },

        getParam: function(key) {
            if (typeof this.uriParams[key] === 'undefined') {
                return remplib.getFromStorage(key, false, true);
            }

            const now = new Date();
            const item = {
                "version": 1,
                "value": this.uriParams[key],
                "createdAt": now,
                "updatedAt": now,
            };
            remplib.setToStorage(key, item);

            return item.value;
        },

        parseUriParams: function(){
            var query = window.location.search.substring(1);
            var vars = query.split('&');
            for (var i = 0; i < vars.length; i++) {
                var pair = vars[i].split('=');
                this.uriParams[decodeURIComponent(pair[0])] = decodeURIComponent(pair[1]);
            }
        },

        tickTime: function () {
            if (this.timeSpentActive) {
                this.totalTimeSpent++;
                this.partialTimeSpent++;
                this.scheduledSend();
            }
            setTimeout('remplib.tracker.tickTime()', 1000);
        },

        tickStart: function () {
            if (!this.timeSpentEnabled) {
                return;
            }
            this.timeSpentActive = true;
        },

        tickStop: function () {
            this.timeSpentActive = false;
        },

        bindTickEvents: function() {
            // listen to events to start tracking time
            document.addEventListener('focus', function () { remplib.tracker.tickStart(); });
            document.addEventListener('focusin', function () { remplib.tracker.tickStart(); });
            document.addEventListener('scroll', function () { remplib.tracker.tickStart(); });
            document.addEventListener('keyup', function () { remplib.tracker.tickStart(); });
            document.addEventListener('mousemove', function () { remplib.tracker.tickStart(); });
            document.addEventListener('click', function () { remplib.tracker.tickStart(); });
            document.addEventListener('touchstart', function () { remplib.tracker.tickStart(); });


            // listen to events to stop tracking time
            document.addEventListener('blur', function () { remplib.tracker.tickStop(); });
            document.addEventListener('focusout', function () { remplib.tracker.tickStop(); });

            this.bindPageVisibilityEvents();

            // send data when leaving page
            window.addEventListener("beforeunload", function () { remplib.tracker.trackTimespent(true); });
        },

        bindPageVisibilityEvents: function() {
            // Switch to visibilityState after we decide to drop IE10- support
            // - https://caniuse.com/#search=visibilityState

            // Source: https://developer.mozilla.org/en-US/docs/Web/API/Page_Visibility_API
            // Set the name of the hidden property and the change event for visibility
            let hidden, visibilityChange;
            if (typeof document.hidden !== "undefined") { // Opera 12.10 and Firefox 18 and later support
                hidden = "hidden";
                visibilityChange = "visibilitychange";
            } else if (typeof document.msHidden !== "undefined") {
                hidden = "msHidden";
                visibilityChange = "msvisibilitychange";
            } else if (typeof document.webkitHidden !== "undefined") {
                hidden = "webkitHidden";
                visibilityChange = "webkitvisibilitychange";
            }

            document.addEventListener(visibilityChange, function () {
                if (document[hidden]) {
                    remplib.tracker.tickStop();
                } else {
                    remplib.tracker.tickStart();
                }
            }, false);
        },

        scheduledSend: function() {
            let logInterval = Math.round(0.3*(Math.sqrt(this.totalTimeSpent))) * 5;
            if (0 === this.totalTimeSpent % logInterval) {
                this.trackTimespent();
            }
        }
    };

    prodlib.tracker._ = mocklib.tracker._ || [];
    remplib = prodlib.extend(mocklib, prodlib);
    remplib.bootstrap(remplib.tracker);

})(remplib);
