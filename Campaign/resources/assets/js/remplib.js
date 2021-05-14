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
                    window.dispatchEvent(
                        new CustomEvent("remp:showtimeReady")
                    );
                    return;
                }

                // deprecated
                window.dispatchEvent(new CustomEvent("campaign_showtime", {
                    detail: result.providerData,
                }));

                let promises = [];
                for (let exec = result.data || [], c = 0; c < exec.length; c++) {
                    let fn = new Function('resolve', exec[c]);
                    promises.push(
                        new Promise(function (resolve, reject) {
                            try {
                                fn(resolve);
                            } catch (u) {
                                console.error("remplib: campaign showtime error:", u)
                                reject("campaign showtime error: " + u)
                            }
                        }, 500)
                    );
                }

                Promise.all(promises).then((res) => {
                    window.dispatchEvent(
                        new CustomEvent("remp:showtimeReady")
                    );
                }).catch(() => {
                    window.dispatchEvent(
                        new CustomEvent("remp:showtimeError")
                    );
                });;

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

            if (window.opener && window.location.hash === '#bannerPicker') {
                remplib.loadScript(this.url + '/assets/lib/js/bannerSelector.js');
            }

            // clean anything that's already obsolete
            let campaigns = this.getCampaigns();
            campaigns = this.cleanup(campaigns);
            if (campaigns) {
                remplib.setToStorage(this.campaignsStorageKey, this.minifyStoredData(campaigns));
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

        cleanup: function(campaigns) {
            if (typeof campaigns !== "object") {
                // data probably not migrated yet; indicate no cleanup with "null"
                return null;
            }

            for (let cid in campaigns) {
                if (!campaigns.hasOwnProperty(cid)) {
                    continue;
                }

                if (typeof campaigns[cid] === "undefined") {
                    delete campaigns[cid];
                    continue;
                }

                // cannot determine age, probably way too old to keep
                if (!campaigns[cid].hasOwnProperty('updatedAt')) {
                    delete campaigns[cid];
                    continue;
                }

                // delete everything without update in the last month
                let threshold = new Date();
                threshold.setMonth(threshold.getMonth()-1);
                let updatedAt = new Date(campaigns[cid]['updatedAt']);
                if (updatedAt < threshold) {
                    delete campaigns[cid];
                    continue;
                }

                delete campaigns[cid]['createdAt']; // field is no longer used
            }
            return campaigns;
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
            let campaigns = {};
            try {
                campaigns = this.unminifyStoredData(remplib.getFromStorage(this.campaignsStorageKey)) || {};
            } catch (e) {
                return campaigns;
            }

            if (typeof campaigns !== "object") {
                try {
                    // Due to the storage migration issues, the original value from localStorage could get into the cookie.
                    // Instead of raw value, the cookie contained wrapper object used only in local_storage storage:
                    //
                    //  "{\"version\":1,\"createdAt\":\"2020-10-20T08:35:30.367Z\",\"updatedAt\":\"2020-10-20T08:35:30.367Z\",\"values\":{\"1da1b9e4-109f-496f-9337-f03c1f28a85d\":{\"seen\":0,\"count\":1,\"createdAt\":\"2020-10-20T08:35:30.367Z\",\"updatedAt\":\"2020-10-20T08:35:30.367Z\"}}}"
                    campaigns = JSON.parse(campaigns)['values'];
                } catch (e) {
                    console.warn("REMPLIB:", "unexpected type of campaigns:", typeof campaigns, campaigns);
                }
            }

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

            campaigns = remplib.campaign.cleanup(campaigns);
            remplib.setToStorage(this.campaignsStorageKey, this.minifyStoredData(campaigns));
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

            remplib.setToStorage(this.campaignsStorageKey, this.minifyStoredData(campaigns));
        },

        getCampaignsSession: function() {
            let campaignsSession = this.unminifyStoredData(remplib.getFromStorage(this.campaignsSessionStorageKey)) || {};

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

            remplib.setToStorage(this.campaignsSessionStorageKey, this.minifyStoredData(campaignsSession));
        },
        minifyStoredData: function (data) {
            for (const id in data) {
                this.renameKey(data[id], 'seen', 'sn');
                this.renameKey(data[id], 'count', 'ct');
                this.renameKey(data[id], 'variantId', 'vi');
                this.renameKey(data[id], 'bannerId', 'bi');
                this.renameKey(data[id], 'updatedAt', 'ut');

                if (data[id].hasOwnProperty('ut')) {
                    const unixTime = new Date(data[id].ut).getTime();
                    data[id].ut = Math.floor(unixTime / 1000);
                }
            }

            return JSON.stringify(data);
        },
        unminifyStoredData: function (data) {
            data = JSON.parse(data);
            for (const id in data) {
                this.renameKey(data[id], 'sn', 'seen');
                this.renameKey(data[id], 'ct', 'count');
                this.renameKey(data[id], 'vi', 'variantId');
                this.renameKey(data[id], 'bi', 'bannerId');

                if (data[id].hasOwnProperty('ut')) {
                    const date = new Date();
                    date.setTime(data[id].ut * 1000);
                    data[id].ut = date;
                }
                this.renameKey(data[id], 'ut', 'updatedAt');
            }

            return data;
        },
        renameKey: function (data, oldKey, newKey) {
            if (data.hasOwnProperty(oldKey)) {
                data[newKey] = data[oldKey];
                delete data[oldKey];
            }
        },
    };

    prodlib.campaign._ = mocklib.campaign._ || [];
    remplib = prodlib.extend(mocklib, prodlib);
    remplib.bootstrap(remplib.campaign);

})(remplib);
