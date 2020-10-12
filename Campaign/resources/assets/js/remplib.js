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
                    "cache": JSON.parse(localStorage.getItem(remplib.segmentProviderCacheKey)),
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

        init: function(config, selfCheckFunc) {
            if (selfCheckFunc !== undefined) {
                selfCheckFunc("before campaign.init()");
            }

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

            if (typeof config.storage === 'string') {
                if (['cookie', 'local_storage'].indexOf(config.storage) === -1) {
                    console.warn('Not supported storage type `' + config.storage + '` in configuration. Setting storage type to `local_storage`');
                } else {
                    remplib.storage = config.storage;
                }
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

            if (window.opener && window.location.hash === '#bannerPicker') {
                remplib.loadScript(this.url + '/assets/lib/js/bannerSelector.js');
            }

            if (selfCheckFunc !== undefined) {
                selfCheckFunc("after campaign.init()");
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
            for (let campaignId in campaigns) {
                delete campaigns[campaignId].createdAt;
                delete campaigns[campaignId].updatedAt;
            }
            return campaigns;
        },

        getCampaignsSessionsForShowtime: function() {
            const campaignsSession = this.getCampaignsSession();
            // remove unnecessary variables to save characters in GET request
            for (let campaignId in campaignsSession) {
                delete campaignsSession[campaignId].createdAt;
                delete campaignsSession[campaignId].updatedAt;
            }
            return campaignsSession;
        },

        // store persistent and session campaign details, called from banner view (when banner is shown)
        handleBannerDisplayed: function(campaignId, bannerId, variantId) {
            this.storePersistentCampaignData(campaignId, bannerId, variantId);
            this.storeSessionCampaignData(campaignId);
        },

        getCampaigns: function() {
            let campaigns = JSON.parse(remplib.getFromStorage(this.campaignsStorageKey)) || {};

            // migrations on campaigns values
            for (let campaignId in campaigns) {
                if (!campaigns[campaignId].hasOwnProperty('seen')) {
                    campaigns[campaignId].seen = 0;
                }
                if (!campaigns[campaignId].hasOwnProperty('count')) {
                    campaigns[campaignId].count = 0;
                }
            }

            return campaigns;
        },

        storePersistentCampaignData: function(campaignId, bannerId, variantId) {
            let campaigns = this.getCampaigns();

            const now = new Date();

            if (!campaigns.hasOwnProperty(campaignId)) {
                campaigns[campaignId] = {
                    "bannerId": bannerId,
                    "variantId": variantId,
                    "seen": 0,
                    "count": 0,
                    "updatedAt": now,
                }
            }

            // always set the new value in case user doesn't have all object properties saved
            campaigns[campaignId].bannerId = bannerId;
            campaigns[campaignId].variantId = variantId;
            campaigns[campaignId].updatedAt = now;
            campaigns[campaignId].seen++;

            remplib.setToStorage(this.campaignsStorageKey, JSON.stringify(campaigns));
        },

        incrementPageviewCountForCampaigns: function (activeCampaignIds)  {
            let campaigns = this.getCampaigns();
            const now = new Date();

            if (!campaigns) {
                campaigns = {};
            }

            if (activeCampaignIds) {
                for (const campaignId of activeCampaignIds) {
                    if (!campaigns.hasOwnProperty(campaignId)) {
                        // bannerId and variantID will be added later in storeCampaigns()
                        campaigns[campaignId] = {
                            "seen": 0,
                            "count": 0,
                            "updatedAt": now,
                        }
                    }

                    campaigns[campaignId].count++;
                    campaigns[campaignId].updatedAt = now;
                }
            }

            remplib.setToStorage(this.campaignsStorageKey, JSON.stringify(campaigns));
        },

        getCampaignsSession: function() {
            let campaignsSession = JSON.parse(remplib.getFromStorage(this.campaignsSessionStorageKey)) || {};

            // migrations on campaigns values
            for (let campaignId in campaignsSession) {
                if (!campaignsSession[campaignId].hasOwnProperty('seen')) {
                    campaignsSession[campaignId].seen = 0;
                }
            }
            return campaignsSession;
        },

        storeSessionCampaignData: function(campaignId) {
            let campaignsSession = this.getCampaignsSession();

            const now = new Date();

            if (!campaignsSession) {
                campaignsSession = {};
            }

            if (!campaignsSession.hasOwnProperty(campaignId)) {
                campaignsSession[campaignId] = {
                    "seen": 0,
                    "updatedAt": now,
                }
            }

            campaignsSession[campaignId].updatedAt = now;
            campaignsSession[campaignId].seen++;

            remplib.setToStorage(this.campaignsSessionStorageKey, JSON.stringify(campaignsSession));
        },
    };

    prodlib.campaign._ = mocklib.campaign._ || [];
    remplib = prodlib.extend(mocklib, prodlib);
    remplib.bootstrap(remplib.campaign);

})(remplib);
