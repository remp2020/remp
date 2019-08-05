import Remplib from 'remp/js/remplib'
import Hash from 'fnv1a'
import { throttle } from 'lodash';

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
            elementFn: function() { return null },
        },

        explicitRefererMedium: null,

        uriParams: {},

        segmentProvider: "remp_segment",

        eventRulesMapKey: "beam_event_rules_map",

        overridableFieldsKey: "overridable_fields",

        flagsKey: "flags",

        timeSpentEnabled: false,

        cookiesEnabled: null,

        websocketsSupported: null,

        totalTimeSpent: 0,

        timeSpentActive: false,

        progressTrackingEnabled: false,

        progressTrackingInterval: 5,

        trackedProgress: [],

        maxPageProgressAchieved: 0,

        initialized: false,

        init: function(config) {
            if (typeof config.token !== 'string') {
                throw "remplib: configuration token invalid or missing: " + config.token
            }
            if (typeof config.tracker !== 'object') {
                throw "remplib: configuration tracker invalid or missing: " + config.tracker
            }
            if (typeof config.tracker.url !== 'string') {
                throw "remplib: configuration tracker.url invalid or missing: " + config.tracker.url
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

            if (typeof config.article === 'object') {
                if (typeof config.article.id === 'undefined' || config.article.id === null) {
                    throw "remplib: configuration tracker.article.id invalid or missing: " + config.article.id
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
                if (typeof config.article.variants !== 'undefined') {
                    this.article.variants = config.article.variants;
                }
                if (typeof config.article.locked !== 'undefined') {
                    this.article.locked = config.article.locked;
                }
                if (typeof config.article.elementFn !== 'undefined') {
                    this.article.elementFn = config.article.elementFn
                }
            } else {
                this.article = null;
            }

            let explicitRefererMediumType = typeof config.tracker.explicit_referer_medium;
            if (explicitRefererMediumType !== 'undefined') {
                if (explicitRefererMediumType !== 'string') {
                    console.warn("remplib: value of tracker.explicit_referer_medium has to be string, instead " + explicitRefererMediumType + " provided")
                } else {
                    this.explicitRefererMedium = config.tracker.explicit_referer_medium;
                }
            }

            if (typeof config.cookieDomain === 'string') {
                remplib.cookieDomain = config.cookieDomain;
            }

            this.parseUriParams();

            this.checkCookiesEnabled();

            this.checkWebsocketsSupport();

            if (typeof config.tracker.timeSpentEnabled === 'boolean') {
                this.timeSpentEnabled = config.tracker.timeSpentEnabled;
            }

            window.addEventListener("campaign_showtime", this.syncSegmentRulesCache);
            window.addEventListener("campaign_showtime", this.syncEventRulesMap);
            window.addEventListener("campaign_showtime", this.syncOverridableFields);
            window.addEventListener("campaign_showtime", this.syncFlags);
            window.addEventListener("beam_event", this.incrementSegmentRulesCache);

            if (typeof config.tracker.readingProgress === 'object') {
                if (typeof config.tracker.readingProgress.enabled === 'boolean') {
                    this.progressTrackingEnabled = config.tracker.readingProgress.enabled;
                }
                if (!this.progressTrackingEnabled) {
                    return;
                }

                if (typeof config.tracker.readingProgress.interval === 'number') {
                    if (config.tracker.readingProgress.interval >= 1) {
                        this.progressTrackingInterval = config.tracker.readingProgress.interval;
                    } else {
                        console.warn("remplib cannot be initialized with readingProgress.interval less than 1, keeping default value (" + this.progressTrackingInterval + ")")
                    }
                }

                setInterval(function() {
                    remplib.tracker.sendTrackedProgress()
                }, (remplib.tracker.progressTrackingInterval * 1000));

                window.addEventListener("scroll", this.scrollProgressEvent);
                window.addEventListener("resize", this.scrollProgressEvent);
                window.addEventListener("scroll_progress", this.trackProgress);
                window.addEventListener("beforeunload", function() {
                    remplib.tracker.sendTrackedProgress(true)
                });
            }

            this.initialized = true;
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

        checkInit: function() {
            var that = this;
            return new Promise(function(resolve, reject) {
                var startTime = new Date().getTime();
                var interval = setInterval(function() {
                    if (that.initialized) {
                        clearInterval(interval);
                        return resolve(true);
                    }

                    // After 5 seconds, stop checking
                    if (new Date().getTime() - startTime > 5000) {
                        clearInterval(interval);
                        reject("Beam library was not initialized within 5 seconds");
                    }
                }, 50);
            });
        },

        run: function() {
            Promise.all([remplib.checkUsingAdblock(), this.checkInit()]).then((res) => {
                this.trackPageview();

                if (this.timeSpentEnabled === true) {
                    this.bindTickEvents();
                    this.tickTime();
                }
            })
        },

        trackEvent: function(category, action, tags, fields, source, value) {
            var params = {
                "category": category,
                "action": action,
                "tags": tags || {},
                "fields": fields || {},
                "value": value,
                "remp_event_id": remplib.uuidv4(),
            };
            params = this.addSystemUserParams(params);

            for (var key in source) {
                if (source.hasOwnProperty(key)) {
                    params["user"]["source"][key] = source[key];
                }
            }

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

            let params = {
                "article": this.article,
                "action": "timespent",
                "timespent": {
                    "seconds": this.totalTimeSpent,
                    "unload": closed,
                }
            };
            params = this.addSystemUserParams(params);
            params = this.timespentParamsCleanup(params);

            this.post(this.url + "/track/pageview", params);
            this.dispatchEvent("pageview", "timespent", params);
        },

        trackCheckout: function(funnelId) {
            var params = {
                "step": "checkout",
                "article": this.article,
                "checkout": {
                    "funnel_id": funnelId
                },
                "remp_commerce_id": remplib.uuidv4(),
            };
            params = this.addSystemUserParams(params);
            this.post(this.url + "/track/commerce", params);
            this.dispatchEvent("commerce", "checkout", params);
        },

        trackCheckoutWithSource: function(funnelId, article, source)
        {
            let params = {
                "step": "checkout",
                "article": article,
                "checkout": {
                    "funnel_id": funnelId
                },
                "user": {
                    "source": source
                },
                "remp_commerce_id": remplib.uuidv4(),
            };

            params = this.addSystemUserParams(params);
            params["user"]["source"] = source;
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
                },
                "remp_commerce_id": remplib.uuidv4(),
            };
            params = this.addSystemUserParams(params);
            this.post(this.url + "/track/commerce", params);
            this.dispatchEvent("commerce", "payment", params);
        },

        trackPaymentWithSource: function(transactionId, amount, currency, productIds, article, source) {
            let params = {
                "step": "payment",
                "article": article,
                "payment": {
                    "transaction_id": transactionId,
                    "revenue": {
                        "amount": amount,
                        "currency": currency
                    },
                    "product_ids": productIds
                },
                "user": {
                    "source": source
                },
                "remp_commerce_id": remplib.uuidv4(),
            };

            params = this.addSystemUserParams(params);
            params["user"]["source"] = source;
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
                },
                "remp_commerce_id": remplib.uuidv4(),
            };
            params = this.addSystemUserParams(params);
            this.post(this.url + "/track/commerce", params);
            this.dispatchEvent("commerce", "purchase", params);
        },

        trackPurchaseWithSource: function(transactionId, amount, currency, productIds, article, source) {
            let params = {
                "step": "purchase",
                "article": article,
                "purchase": {
                    "transaction_id": transactionId,
                    "revenue": {
                        "amount": amount,
                        "currency": currency
                    },
                    "product_ids": productIds
                },
                "user": {
                    "source": source
                },
                "remp_commerce_id": remplib.uuidv4(),
            };

            params = this.addSystemUserParams(params);
            params["user"]["source"] = source;
            this.post(this.url + "/track/commerce", params);
            this.dispatchEvent("commerce", "purchase", params);
        },

        trackRefund: function(transactionId, amount, currency, productIds) {
            var params = {
                "step": "refund",
                "article": this.article,
                "refund": {
                    "transaction_id": transactionId,
                    "revenue": {
                        "amount": amount,
                        "currency": currency
                    },
                    "product_ids": productIds
                },
                "remp_commerce_id": remplib.uuidv4(),
            };
            params = this.addSystemUserParams(params);
            this.post(this.url + "/track/commerce", params);
            this.dispatchEvent("commerce", "refund", params);
        },

        trackRefundWithSource: function(transactionId, amount, currency, productIds, article, source) {
            let params = {
                "step": "refund",
                "article": article,
                "refund": {
                    "transaction_id": transactionId,
                    "revenue": {
                        "amount": amount,
                        "currency": currency
                    },
                    "product_ids": productIds
                },
                "user": {
                    "source": source
                },
                "remp_commerce_id": remplib.uuidv4()
            };
            params = this.addSystemUserParams(params);
            params["user"]["source"] = source;
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

        post: function(path, params) {
            var xmlhttp = new XMLHttpRequest();
            xmlhttp.open("POST", path);
            xmlhttp.onreadystatechange = function(oEvent) {
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

        checkCookiesEnabled: function() {
            document.cookie = "cookietest=1";
            remplib.tracker.cookiesEnabled = document.cookie.indexOf("cookietest=") != -1;

            document.cookie = "cookietest=1; expires=Thu, 01-Jan-1970 00:00:01 GMT";
        },

        checkWebsocketsSupport: function() {
            remplib.tracker.websocketsSupported = 'WebSocket' in window || false;
        },
        addSystemUserParams: function(params) {
            const d = new Date();
            params["system"] = {"property_token": this.beamToken, "time": d.toISOString()};
            params["user"] = {
                "id": remplib.getUserId(),
                "browser_id": remplib.getBrowserId(),
                "subscriber": remplib.isUserSubscriber(),
                "url": window.location.href,
                "referer": document.referrer,
                "user_agent": window.navigator.userAgent,
                "adblock": remplib.usingAdblock,
                "window_height": window.outerHeight || document.documentElement.clientHeight,
                "window_width": window.outerWidth || document.documentElement.clientWidth,
                "cookies": remplib.tracker.cookiesEnabled,
                "websockets": remplib.tracker.websocketsSupported,
                "source": {
                    "utm_source": this.getParam("utm_source"),
                    "utm_medium": this.getParam("utm_medium"),
                    "utm_campaign": this.getParam("utm_campaign"),
                    "utm_content": this.getParam("utm_content"),
                    "banner_variant": this.getParam("banner_variant"),
                }
            };
            params["user"][remplib.rempSessionIDKey] = remplib.getRempSessionID();
            params["user"][remplib.rempPageviewIDKey] = remplib.getRempPageviewID();

            if (this.explicitRefererMedium) {
                params["user"]["explicit_referer_medium"] = this.explicitRefererMedium;
            }

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
            delete params.user.user_agent;
            delete params.user.source.utm_source;
            delete params.user.source.utm_medium;
            delete params.user.source.utm_campaign;
            delete params.user.source.utm_content;
            return params;
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

        parseUriParams: function() {
            var query = window.location.search.substring(1);
            var vars = query.split('&');
            for (var i = 0; i < vars.length; i++) {
                var pair = vars[i].split('=');
                this.uriParams[decodeURIComponent(pair[0])] = decodeURIComponent(pair[1]);
            }
        },

        tickTime: function() {
            if (this.timeSpentActive) {
                this.totalTimeSpent++;
                this.scheduledSend();
            }
            setTimeout('remplib.tracker.tickTime()', 1000);
        },

        tickStart: function() {
            if (!this.timeSpentEnabled) {
                return;
            }
            this.timeSpentActive = true;
        },

        tickStop: function() {
            this.timeSpentActive = false;
        },

        bindTickEvents: function() {
            // listen to events to start tracking time
            document.addEventListener('focus', function() {
                remplib.tracker.tickStart();
            });
            document.addEventListener('focusin', function() {
                remplib.tracker.tickStart();
            });
            document.addEventListener('scroll', function() {
                remplib.tracker.tickStart();
            });
            document.addEventListener('keyup', function() {
                remplib.tracker.tickStart();
            });
            document.addEventListener('mousemove', function() {
                remplib.tracker.tickStart();
            });
            document.addEventListener('click', function() {
                remplib.tracker.tickStart();
            });
            document.addEventListener('touchstart', function() {
                remplib.tracker.tickStart();
            });


            // listen to events to stop tracking time
            document.addEventListener('blur', function() {
                remplib.tracker.tickStop();
            });
            document.addEventListener('focusout', function() {
                remplib.tracker.tickStop();
            });

            this.bindPageVisibilityEvents();

            // send data when leaving page
            window.addEventListener("beforeunload", function() {
                remplib.tracker.trackTimespent(true);
            });
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

            document.addEventListener(visibilityChange, function() {
                if (document[hidden]) {
                    remplib.tracker.tickStop();
                } else {
                    remplib.tracker.tickStart();
                }
            }, false);
        },

        scheduledSend: function() {
            let logInterval = Math.round(0.3 * (Math.sqrt(this.totalTimeSpent))) * 5;
            if (0 === this.totalTimeSpent % logInterval) {
                this.trackTimespent();
            }
        },

        pageProgress: function() {
            const root = remplib.tracker.getRootElement();
            const scrollTop = window.pageYOffset || root.scrollTop || document.body.scrollTop || 0;
            return (scrollTop + root.clientHeight) / root.scrollHeight;
        },

        getRootElement: function() {
            if (document.documentElement.clientHeight === document.documentElement.scrollHeight) {
                // if documentElement has CSS property setting height to 100%, it's affecting client height
                // body is used as a safe fallback in such scenario
                return document.body;
            }
            return document.documentElement;
        },

        getElementOffsetTop: function(el) {
            const rect = el.getBoundingClientRect();
            return rect.top + (window.pageYOffset || document.documentElement.scrollTop)
        },

        scrollProgressEvent: throttle(function() {
            const payload = {
                pageScrollRatio: remplib.tracker.pageProgress(),
                timestamp: new Date(),
            };

            if (remplib.tracker.article) {
                const article = remplib.tracker.article.elementFn();
                const root = remplib.tracker.getRootElement();
                const scrollTop = window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0;

                payload.articleScrollRatio = Math.min(1, Math.max(0,
                    (scrollTop + root.clientHeight - remplib.tracker.getElementOffsetTop(article)) / article.scrollHeight
                ));
            }

            const event = new CustomEvent("scroll_progress", {
                detail: payload,
            });

            window.dispatchEvent(event);
        }, 250),

        trackProgress: function(currentEvent) {
            // maybe do here some sort of fast scrolling check in the future if is needed

            if (currentEvent.detail.pageScrollRatio <= remplib.tracker.maxPageProgressAchieved) {
                return;
            }

            remplib.tracker.maxPageProgressAchieved = currentEvent.detail.pageScrollRatio;

            remplib.tracker.trackedProgress.push(currentEvent);
        },

        sendTrackedProgress: function(isUnloading = false) {
            const lastPosition = remplib.tracker.trackedProgress[remplib.tracker.trackedProgress.length - 1];

            if (!lastPosition) {
                return;
            }

            let params = {
                "action": "progress",
                "article": remplib.tracker.article,
                "progress": {
                    "page_ratio": lastPosition.detail.pageScrollRatio,
                    "unload": isUnloading
                }
            };

            if (typeof lastPosition.detail.articleScrollRatio !== 'undefined') {
                params.progress.article_ratio = lastPosition.detail.articleScrollRatio;
            }

            params = this.addSystemUserParams(params);
            remplib.tracker.post(this.url + "/track/pageview", params);
            remplib.tracker.trackedProgress = [];
        }
    };

    prodlib.tracker._ = mocklib.tracker._ || [];
    remplib = prodlib.extend(mocklib, prodlib);
    remplib.bootstrap(remplib.tracker);

})(remplib);
