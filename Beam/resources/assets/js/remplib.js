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

        article: null,

        refererMedium: null,

        uriParams: {},

        segmentProvider: "remp_segment",

        eventRulesMapKey: "beam_event_rules_map",

        overridableFieldsKey: "overridable_fields",

        flagsKey: "flags",

        timeSpentEnabled: false,

        utmBackwardCompatibilityEnabled: true, // Deprecated, will be set to false in the future

        timeSpentInterval: 5, // seconds

        cookiesEnabled: null,

        websocketsSupported: null,

        totalTimeSpent: 0,

        timeSpentActive: false,

        progressTrackingEnabled: false,

        progressTrackingInterval: 5,

        trackedProgress: [],

        maxPageProgressAchieved: 0,

        initialized: false,

        init: function(config, selfCheckFunc) {
            if (selfCheckFunc !== undefined) {
                selfCheckFunc("before tracker.init()");
            }

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
            if (config.subscriptionIds instanceof Array) {
                remplib.subscriptionIds = config.subscriptionIds;
            }
            if (typeof config.browserId !== 'undefined' && config.browserId !== null) {
                remplib.browserId = config.browserId;
            }

            this.setArticle(config.article);

            let refererMediumType = typeof config.tracker.refererMedium;
            // "explicit_referer_medium" config option is deprecated and was renamed to "refererMedium", checking both for compatibility reasons
            let explicitRefererMediumType = typeof config.tracker.explicit_referer_medium;
            if (refererMediumType !== 'undefined') {
                if (refererMediumType !== 'string') {
                    console.warn("Referer medium has to be string, " + refererMediumType + " provided instead")
                } else {
                    this.refererMedium = config.tracker.refererMedium;
                }
            } else if (explicitRefererMediumType !== 'undefined') {
                console.warn("Tracker option 'explicit_referer_medium' is deprecated, please use 'refererMedium' instead")
                if (explicitRefererMediumType !== 'string') {
                    console.warn("Referer medium has to be string, " + refererMediumType + " provided instead")
                } else {
                    this.refererMedium = config.tracker.explicit_referer_medium;
                }
            }

            if (typeof config.cookieDomain === 'string') {
                remplib.cookieDomain = config.cookieDomain;
            }

            if (typeof config.storage === 'string') {
                if (['cookie', 'local_storage'].indexOf(config.storage) === -1) {
                    console.warn('remplib: storage type `' + config.storage + '` is not supported, falling back to `local_storage`');
                } else {
                    remplib.storage = config.storage;
                }
            }
            if (typeof window.localStorage !== 'object' || localStorage === null) {
                console.warn('remplib: local storage is not available in this browser, falling back to `cookie`');
                remplib.storage = 'cookie';
            }

            if (typeof config.storageExpiration === 'object') {
                if (config.storageExpiration.default) {
                    remplib.storageExpiration.default = config.storageExpiration.default;
                }
                if (config.storageExpiration.keys) {
                    remplib.storageExpiration.keys = {
                        ...remplib.storageExpiration.keys,
                        ...config.storageExpiration.keys
                    };
                }
            }

            this.parseUriParams();

            this.checkCookiesEnabled();

            this.checkWebsocketsSupport();

            // deprecated, kept for legacy implementations
            if (typeof config.tracker.timeSpentEnabled === 'boolean') {
                this.timeSpentEnabled = config.tracker.timeSpentEnabled;
            }
            if (typeof config.tracker.timeSpent === 'object') {
                if (typeof config.tracker.timeSpent.enabled === 'boolean') {
                    this.timeSpentEnabled = config.tracker.timeSpent.enabled;
                }
                if (typeof config.tracker.timeSpent.interval === 'number') {
                    this.timeSpentInterval = config.tracker.timeSpent.interval;
                }
            }

            if (typeof config.tracker.utmBackwardCompatibilityEnabled === 'boolean') {
                this.utmBackwardCompatibilityEnabled = config.tracker.utmBackwardCompatibilityEnabled;
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
                if (this.progressTrackingEnabled) {
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
            }

            if (selfCheckFunc !== undefined) {
                selfCheckFunc("after tracker.init()");
            }

            this.initialized = true;
        },

        setArticle: function(article) {
            if (typeof article === 'object') {
                if (typeof article.id === 'undefined' || article.id === null) {
                    throw "remplib: tracker.article.id invalid or missing: " + article.id
                }

                this.article = {
                    id: article.id,
                    author_id: null,
                    category: null,
                    locked: null,
                    tags: [],
                    variants: {},
                    elementFn: function() { return null },
                };

                if (typeof article.campaign_id !== 'undefined') {
                    this.article.campaign_id = article.campaign_id;
                }
                if (typeof article.author_id !== 'undefined') {
                    this.article.author_id = article.author_id;
                }
                if (typeof article.category !== 'undefined') {
                    this.article.category = article.category;
                }
                if (article.tags instanceof Array) {
                    this.article.tags = article.tags;
                }
                if (typeof article.variants !== 'undefined') {
                    this.article.variants = article.variants;
                }
                if (typeof article.locked !== 'undefined') {
                    this.article.locked = article.locked;
                }
                if (typeof article.elementFn !== 'undefined') {
                    this.article.elementFn = article.elementFn
                }
            }
        },

        syncSegmentRulesCache: function(e) {
            if (!e.detail.hasOwnProperty(remplib.tracker.segmentProvider)) {
                return;
            }
            if (!e.detail[remplib.tracker.segmentProvider].hasOwnProperty("cache")) {
                return;
            }
            let segmentProviderCache = localStorage.getItem(remplib.segmentProviderCacheKey);
            if (segmentProviderCache) {
                segmentProviderCache = JSON.parse(segmentProviderCache);
            }
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
            let cache = localStorage.getItem(remplib.segmentProviderCacheKey);
            if (cache) {
                cache = JSON.parse(cache);
            }
            if (!cache || !cache.hasOwnProperty(remplib.tracker.segmentProvider)) {
                return;
            }
            let eventRules = localStorage.getItem(remplib.tracker.eventRulesMapKey);
            if (eventRules) {
                eventRules = JSON.parse(eventRules);
            }
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

                let overridableFields = localStorage.getItem(remplib.tracker.overridableFieldsKey);
                if (overridableFields) {
                    overridableFields = JSON.parse(overridableFields);
                } else {
                    overridableFields = [];
                }
                if (!overridableFields.hasOwnProperty(ruleId)) {
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
            let flags = localStorage.getItem(remplib.tracker.flagsKey);
            if (flags) {
                flags = JSON.parse(flags);
            } else {
                flags = [];
            }

            if (!flags.hasOwnProperty(ruleId)) {
                console.warn("remplib: missing flags for rule " + ruleId);
                return false;
            }

            for (let f of Object.keys(flags[ruleId])) {
                // duplicating flag handling from track/pageviews API call
                if (f === 'is_article') {
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

        trackCheckout: function(funnelId, includeStorageParams = false) {
            var params = {
                "step": "checkout",
                "article": this.article,
                "checkout": {
                    "funnel_id": funnelId
                },
                "remp_commerce_id": remplib.uuidv4(),
                "commerce_session_id": remplib.generateCommerceSessionID(),
            };
            params = this.addSystemUserParams(params, includeStorageParams);
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
                "commerce_session_id": remplib.generateCommerceSessionID(),
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
                "commerce_session_id": remplib.getCommerceSessionID(),
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
                "commerce_session_id": remplib.getCommerceSessionID(),
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
                "commerce_session_id": remplib.getCommerceSessionID(),
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
                "commerce_session_id": remplib.getCommerceSessionID(),
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
                "commerce_session_id": remplib.getCommerceSessionID(),
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
                "remp_commerce_id": remplib.uuidv4(),
                "commerce_session_id": remplib.getCommerceSessionID(),
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
        addSystemUserParams: function(params, includeStorageParams = false) {
            const d = new Date();
            params["system"] = {"property_token": this.beamToken, "time": d.toISOString()};

            // utm_ replaced with rtm_ (but utm_ support kept due to the backward compatibility)
            let source = null;

            if (this.utmBackwardCompatibilityEnabled) {
                source = {
                    "rtm_source": this.getParam("rtm_source", includeStorageParams) !== null ? this.getParam("rtm_source", includeStorageParams) : this.getParam("utm_source", includeStorageParams),
                    "rtm_medium": this.getParam("rtm_medium", includeStorageParams) !== null ? this.getParam("rtm_medium", includeStorageParams) : this.getParam("utm_medium", includeStorageParams),
                    "rtm_campaign": this.getParam("rtm_campaign", includeStorageParams) !== null ? this.getParam("rtm_campaign", includeStorageParams) : this.getParam("utm_campaign", includeStorageParams),
                    "rtm_content": this.getParam("rtm_content", includeStorageParams) !== null ? this.getParam("rtm_content", includeStorageParams) : this.getParam("utm_content", includeStorageParams),
                    "rtm_variant": this.getParam("rtm_variant", includeStorageParams) !== null ? this.getParam("rtm_variant", includeStorageParams) : this.getParam("banner_variant", includeStorageParams),
                };
            } else {
                source = {
                    "rtm_source": this.getParam("rtm_source", includeStorageParams),
                    "rtm_medium": this.getParam("rtm_medium", includeStorageParams),
                    "rtm_campaign": this.getParam("rtm_campaign", includeStorageParams),
                    "rtm_content": this.getParam("rtm_content", includeStorageParams),
                    "rtm_variant": this.getParam("rtm_variant", includeStorageParams),
                };
            }

            params["user"] = {
                "id": remplib.getUserId(),
                "browser_id": remplib.getBrowserId(),
                "subscriber": remplib.isUserSubscriber(),
                "subscription_ids": remplib.getSubscriptionIds(),
                "url": window.location.href,
                "referer": document.referrer,
                "user_agent": window.navigator.userAgent,
                "adblock": remplib.usingAdblock,
                "window_height": window.outerHeight || document.documentElement.clientHeight,
                "window_width": window.outerWidth || document.documentElement.clientWidth,
                "cookies": remplib.tracker.cookiesEnabled,
                "websockets": remplib.tracker.websocketsSupported,
                "source": source
            };
            params["user"][remplib.rempSessionIDKey] = remplib.getRempSessionID();
            params["user"][remplib.rempPageviewIDKey] = remplib.getRempPageviewID();

            if (this.refererMedium) {
                params["user"]["explicit_referer_medium"] = this.refererMedium;
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
            delete params.user.source.rtm_source;
            delete params.user.source.rtm_medium;
            delete params.user.source.rtm_campaign;
            delete params.user.source.rtm_content;
            delete params.user.source.rtm_variant;
            return params;
        },

        getParam: function(key, includeStorage = false) {
            // retrieve storage value since it also extends value's lifetime in storage
            let storageValue = remplib.getFromStorage(key);

            if (includeStorage && typeof this.uriParams[key] === 'undefined') {
                return storageValue;
            }

            let value = this.uriParams[key];
            if (value === undefined) {
                value = null;
            }
            return value;
        },

        parseUriParams: function() {
            var query = window.location.search.substring(1);
            if (!query) {
                return;
            }
            
            var vars = query.split('&');

            for (var i = 0; i < vars.length; i++) {
                let pair = vars[i].split('=');
                let key = decodeURIComponent(pair[0]);
                this.uriParams[key] = decodeURIComponent(pair[1]);

                remplib.setToStorage(key, this.uriParams[key]);
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
            let logInterval = Math.round(0.3 * (Math.sqrt(this.totalTimeSpent))) * this.timeSpentInterval;
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

            if (remplib.tracker.article && remplib.tracker.article.elementFn) {
                const article = remplib.tracker.article.elementFn();
                if (article) {
                    const root = remplib.tracker.getRootElement();
                    const scrollTop = window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0;

                    payload.articleScrollRatio = Math.min(1, Math.max(0,
                        (scrollTop + root.clientHeight - remplib.tracker.getElementOffsetTop(article)) / article.scrollHeight
                    ));
                } else {
                    console.warn("remplib: Disabling article tracking, article.elementFn() did not return any element.");
                    remplib.tracker.article.elementFn = null;
                }
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
