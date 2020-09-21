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
                    "version": 1,
                    "userId": remplib.getUserId(),
                    "browserId": remplib.getBrowserId(),
                    "url": window.location.href,
                    "referer": document.referrer || null,
                    "campaigns": remplib.campaign.getCampaignsForShowtime(),
                    "campaignsSession": remplib.campaign.getCampaignsSessionsForShowtime(),
                    "cache": remplib.getFromStorage(remplib.segmentProviderCacheKey, true),
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
                remplib.campaign.incrementPageviewCountForCampaigns(result.activeCampaignIds);
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

        getCampaignsForShowtime: function() {
            let campaigns = this.getCampaigns();
            // remove unnecessary variables to save characters in GET request
            for (let campaignId in campaigns.values) {
                delete campaigns.values[campaignId].createdAt;
                delete campaigns.values[campaignId].updatedAt;
            }
            return campaigns.values;
        },

        getCampaignsSessionsForShowtime: function() {
            const campaignsSession = this.getCampaignsSession();
            // remove unnecessary variables to save characters in GET request
            for (let campaignId in campaignsSession.values) {
                delete campaignsSession.values[campaignId].createdAt;
                delete campaignsSession.values[campaignId].updatedAt;
            }
            return campaignsSession.values;
        },

        // store persistent and session campaign details, called from banner view (when banner is shown)
        handleBannerDisplayed: function(campaignId, bannerId, variantId) {
            this.storePersistentCampaignData(campaignId, bannerId, variantId);
            this.storeSessionCampaignData(campaignId);
        },

        getCampaigns: function() {
            let campaigns = remplib.getFromStorage(this.campaignsStorageKey, true);

            if (typeof campaigns === "undefined" ||  campaigns === null) {
                const now = new Date();
                campaigns = {
                    "version": 1,
                    "createdAt": now,
                    "updatedAt": now,
                    "values": {},
                }
            }

            // migrations from old versions, some values may be missing in users' local storage
            if (!campaigns.hasOwnProperty('values')) {
                campaigns.values = {};
            }

            // migrations on campaigns values
            for (let campaignId in campaigns.values) {
                if (!campaigns.values[campaignId].hasOwnProperty('seen')) {
                    campaigns.values[campaignId].seen = 0;
                }
                if (!campaigns.values[campaignId].hasOwnProperty('count')) {
                    campaigns.values[campaignId].count = 0;
                }
            }

            return campaigns;
        },

        storePersistentCampaignData: function(campaignId, bannerId, variantId) {
            let campaigns = this.getCampaigns();

            const now = new Date();

            if (!campaigns.values.hasOwnProperty(campaignId)) {
                campaigns.values[campaignId] = {
                    "bannerId": bannerId,
                    "variantId": variantId,
                    "seen": 0,
                    "count": 0,
                    "createdAt": now,
                    "updatedAt": now,
                }
            }

            // always set the new value in case user doesn't have all object properties saved
            campaigns.values[campaignId].bannerId = bannerId;
            campaigns.values[campaignId].variantId = variantId;
            campaigns.values[campaignId].updatedAt = now;
            campaigns.values[campaignId].seen++;

            localStorage.setItem(this.campaignsStorageKey, JSON.stringify(campaigns));
        },

        incrementPageviewCountForCampaigns: function (activeCampaignIds)  {
            let campaigns = this.getCampaigns();
            const now = new Date();

            if (activeCampaignIds) {
                for (const campaignId of activeCampaignIds) {
                    if (!campaigns.values.hasOwnProperty(campaignId)) {
                        // bannerId and variantID will be added later in storeCampaigns()
                        campaigns.values[campaignId] = {
                            "seen": 0,
                            "count": 0,
                            "createdAt": now,
                            "updatedAt": now,
                        }
                    }

                    campaigns.values[campaignId].count++;
                    campaigns.values[campaignId].updatedAt = now;
                }
            }

            localStorage.setItem(this.campaignsStorageKey, JSON.stringify(campaigns));
        },

        getCampaignsSession: function() {
            let campaignsSession = remplib.getFromStorage(this.campaignsSessionStorageKey, false);

            if(typeof campaignsSession === "undefined" || campaignsSession === null) {
                const now = new Date();
                campaignsSession = {
                    "version": 1,
                    "createdAt": now,
                    "updatedAt": now,
                    "values": {},
                }
            }
            // migrations from old versions, some values may be missing in users' local storage
            if (!campaignsSession.hasOwnProperty('values')) {
                campaignsSession.values = {};
            }
            // migrations on campaigns values
            for (let campaignId in campaignsSession.values) {
                if (!campaignsSession.values[campaignId].hasOwnProperty('seen')) {
                    campaignsSession.values[campaignId].seen = 0;
                }
            }
            return campaignsSession;
        },

        storeSessionCampaignData: function(campaignId) {
            let campaignsSession = this.getCampaignsSession();

            const now = new Date();

            if (!campaignsSession.values.hasOwnProperty(campaignId)) {
                campaignsSession.values[campaignId] = {
                    "seen": 0,
                    "createdAt": now,
                    "updatedAt": now,
                }
            }

            campaignsSession.values[campaignId].updatedAt = now;
            campaignsSession.values[campaignId].seen++;
            campaignsSession.updatedAt = new Date();

            localStorage.setItem(this.campaignsSessionStorageKey, JSON.stringify(campaignsSession));
        },
    };

    prodlib.campaign._ = mocklib.campaign._ || [];
    remplib = prodlib.extend(mocklib, prodlib);
    remplib.bootstrap(remplib.campaign);

})(remplib);
