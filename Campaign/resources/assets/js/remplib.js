import Remplib from 'remp/js/remplib'

remplib = typeof(remplib) === 'undefined' ? {} : remplib;

(function(mocklib) {

    'use strict';

    let prodlib = Remplib;

    prodlib.campaign = {

        _: [],

        callbackIterator: 0,

        initIterator: 0,

        url: null,

        variables: {},

        campaignsStorageKey: "campaigns",

        pageviewCountStorageKey: "pageview_count",

        campaignsSessionStorageKey: "campaigns_session",

        showtimeExperiment: false,

        initialized: false,

        bannerUrlParams: {},

        /* JSONP START */

        showtime: {
            name: function() {
                if (remplib.campaign.showtimeExperiment) {
                    return "showtime.php";
                }
                return "campaigns/showtime";
            },
            jsonpParameter: "data",
            prepareData: function() {
                return {
                    "userId": remplib.getUserId(),
                    "browserId": remplib.getBrowserId(),
                    "url": window.location.href,
                    "referer": document.referrer || null,
                    "campaignsSeen": remplib.campaign.getCampaignsSeen(),
                    "campaignsBanners": remplib.campaign.getCampaignsBanners(),
                    "cache": remplib.getFromStorage(remplib.segmentProviderCacheKey, true),
                    "pageviewCount": remplib.getFromStorage(remplib.campaign.pageviewCountStorageKey),
                    "userAgent": window.navigator.userAgent,
                    "usingAdblock": remplib.usingAdblock
                }

            },
            processResponse: function(result) {
                if (!result["success"]) {
                    return;
                }
                for (let exec = result.data || [], c = 0; c < exec.length; c++) {
                    try {
                        let fn = new Function(exec[c]);
                        setTimeout(fn(), 0);
                    } catch (u) {
                        console.error("campaign showtime error:", u)
                    }
                }
                let event = new CustomEvent("campaign_showtime", {
                    detail: result.providerData,
                });
                window.dispatchEvent(event);
            },
        },

        /* JSONP END */

        init: function(config) {
            if (typeof config.campaign !== 'object') {
                throw "remplib: configuration campaign invalid or missing: "+config.campaign
            }
            if (typeof config.campaign.url !== 'string') {
                throw "remplib: configuration campaign.url invalid or missing: "+config.campaign.url
            }
            this.url = config.campaign.url;

            for (let param in config.campaign.bannerUrlParams) {
                if (config.campaign.bannerUrlParams.hasOwnProperty(param) && typeof (config.campaign.bannerUrlParams[param]) !== "function") {
                    throw "remplib: configuration campaign.bannerUrlParams invalid (callback required) for param: " + param
                }
            }
            this.bannerUrlParams = config.campaign.bannerUrlParams || {};

            if (typeof config.campaign.variables !== 'undefined') {
                if (typeof config.campaign.variables !== 'object') {
                    throw "remplib: configuration variables invalid (object required): "+config.campaign.variables
                }
                this.variables = config.campaign.variables;
            }

            if (typeof config.campaign.showtimeExperiment !== 'undefined') {
                this.showtimeExperiment = config.campaign.showtimeExperiment;
            }

            // global
            if (typeof config.userId !== 'undefined' && config.userId !== null) {
                remplib.userId = config.userId;
            }
            if (typeof config.userSubscribed !== 'undefined' && config.userSubscribed !== null) {
                remplib.userSubscribed = config.userSubscribed;
            }

            if (typeof config.cookieDomain === 'string') {
                remplib.cookieDomain = config.cookieDomain;
            }

            this.incrementPageviewCount();

            if (window.opener && window.location.hash === '#bannerPicker') {
                remplib.loadScript(this.url + '/assets/lib/js/bannerSelector.js');
            }

            this.initialized = true;
        },

        checkInit: function() {
            var that = this;
            return new Promise(function (resolve, reject) {
                var startTime = new Date().getTime();
                var interval = setInterval(function() {
                    if (that.initialized) {
                        clearInterval(interval);
                        return resolve(true);
                    }

                    // After 5 seconds, stop checking
                    if (new Date().getTime() - startTime > 5000) {
                        clearInterval(interval);
                        reject("Campaign library was not initialized within 5 seconds");
                    }
                }, 50);
            });
        },

        run: function() {
            Promise.all([remplib.checkUsingAdblock(), this.checkInit()]).then((res) => {
                this.request(this.showtime);
            });
        },

        request: function(def) {
            let params = {};
            params[def.jsonpParameter] = JSON.stringify(def.prepareData());

            this.get(this.url + "/" + def.name(), params, function (data) {
                def.processResponse && def.processResponse(data);
            }, function() {
                def.processError && def.processError();
            });
        },

        get: function(url, params, success, error) {
            let query = "?";
            let cb = "rempcampaign_callback_json" + this.callbackIterator++;

            for (let item in params)
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

        getCampaignsSeen: function() {
            let campaigns = remplib.getFromStorage(this.campaignsSessionStorageKey, false);
            if (typeof campaigns !== "undefined" && campaigns !== null && campaigns.seen) {
                return campaigns.seen;
            }
            return null;
        },

        getCampaignsBanners: function() {
            let campaigns = remplib.getFromStorage(this.campaignsStorageKey, true);
            if (typeof campaigns !== "undefined" && campaigns !== null && campaigns.values) {
                return campaigns.values;
            }
            return null;
        },

        incrementPageviewCount: function ()  {
            var pageviewCount = remplib.getFromStorage(this.pageviewCountStorageKey);

            if (pageviewCount) {
                remplib.setToStorage(this.pageviewCountStorageKey, pageviewCount+1);
            } else {
                remplib.setToStorage(this.pageviewCountStorageKey, 1);
            }
        },

        // used to store campaign details, called from banner view
        storeCampaignDetails: function(campaignId, bannerId, variantId) {
            this.storeCampaigns(campaignId, bannerId, variantId);
            this.storeCampaignsSession(campaignId);
        },


        // store persistent campaign details
        storeCampaigns: function(campaignId, bannerId, variantId) {
            const now = new Date();
            let campaigns = remplib.getFromStorage(this.campaignsStorageKey, true);

            if (typeof campaigns === "undefined" ||  campaigns === null) {
                campaigns = {
                    "version": 1,
                    "createdAt": now,
                    "updatedAt": now,
                    "values": {},
                }
            }

            if (!campaigns.hasOwnProperty('values')) {
                campaigns.values = {};
            }

            // always set the new value in case user doesn't have all object properties cached
            campaigns.values[campaignId] = {
                "bannerId": bannerId,
                "variantId": variantId,
            };

            localStorage.setItem(this.campaignsStorageKey, JSON.stringify(campaigns));
        },

        // store session campaign details
        storeCampaignsSession: function(campaignId) {
            const now = new Date();
            let campaigns = remplib.getFromStorage(this.campaignsSessionStorageKey, false);

            if(typeof campaigns === "undefined" || campaigns === null) {
                campaigns = {
                    "version": 1,
                    "createdAt": now,
                    "updatedAt": now,
                    "seen": [],
                }
            }

            let flag = false;
            for (let i = 0, len = campaigns.seen.length; i < len; i++) {
                if (campaigns.seen[i].campaignId === campaignId) {
                    campaigns.seen[i].updatedAt = now;
                    campaigns.seen[i].count++;
                    flag = true;
                    break;
                }
            }

            if (!flag) {
                campaigns.seen.push({
                    "campaignId": campaignId,
                    "createdAt": now,
                    "updatedAt": now,
                    "count": 1,
                });
            }

            localStorage.setItem(this.campaignsSessionStorageKey, JSON.stringify(campaigns));
        },
    };

    prodlib.campaign._ = mocklib.campaign._ || [];
    remplib = prodlib.extend(mocklib, prodlib);
    remplib.bootstrap(remplib.campaign);

})(remplib);
