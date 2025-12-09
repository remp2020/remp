import Remplib from '@remp/js-commons/js/remplib'

remplib = typeof(remplib) === 'undefined' ? {} : remplib;

class Campaign {

    static initialized = false;

    static callbackIterator = 0;

    _ = [];

    url = null;

    variables = {};

    pageviewAttributes = {};

    campaignsStorageKey = "campaigns";

    campaignsSessionStorageKey = "campaigns_session";

    showtimeExperiment = true;

    bannerUrlParams = {};

    debug = null;

    /* JSONP START */

    showtime = {
        name: function() {
            if (remplib.campaign.showtimeExperiment) {
                return "vendor/campaign/showtime.php";
            }
            return "campaigns/showtime";
        },
        jsonpParameter: "data",
        prepareData: function() {
            const data = {
                "version": 1,
                "userId": remplib.getUserId(),
                "browserId": remplib.getBrowserId(),
                "language": navigator.language,
                "url": window.location.href,
                "referer": document.referrer || null,
                "sessionReferer": remplib.getRempSessionReferer() || null,
                "campaigns": remplib.campaign.getCampaignsForShowtime(),
                "campaignsSession": remplib.campaign.getCampaignsSessionsForShowtime(),
                "cache": JSON.parse(localStorage.getItem(remplib.segmentProviderCacheKey)),
                "userAgent": window.navigator.userAgent,
                "usingAdblock": remplib.usingAdblock,
                "pageviewAttributes": remplib.campaign.pageviewAttributes,
            };

            if (window.location.hash === '#campaignDebug' && remplib.campaign.debug && remplib.campaign.debug.key) {
                data["debug"] = remplib.campaign.debug;
            }

            return data;
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
            window.dispatchEvent(new CustomEvent("campaign_showtime_response", {
                detail: result,
            }));

            let promises = [];
            for (let exec = result.data || [], c = 0; c < exec.length; c++) {
                let fn = new Function('resolve', exec[c]);
                promises.push(
                    new Promise(function (resolve, reject) {
                        try {
                            fn(resolve);
                        } catch (u) {
                            console.error("remplib: campaign showtime error:", u);
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
            });

            remplib.campaign.incrementPageviewCountForCampaigns(result.activeCampaigns);
            remplib.campaign.printSuppressedBanners(result);
        },
    };

    /* JSONP END */

    init(config) {
        Campaign.configure(config)
    }

    static configure(config) {
        if (Campaign.initialized) {
            remplib.campaign = new Campaign();
        }
        remplib.campaign._configure(config);
    }

    _configure(config) {
        if (typeof config.campaign !== 'object') {
            throw "remplib: configuration campaign invalid or missing: "+config.campaign
        }
        if (typeof config.campaign.url !== 'string') {
            throw "remplib: configuration campaign.url invalid or missing: "+config.campaign.url
        }

        remplib.init(config);

        if (config.campaign.debug) {
            this.debug = config.campaign.debug;
        }

        this.url = config.campaign.url;

        for (let pageviewAttribute in config.campaign.pageviewAttributes) {
            if (config.campaign.pageviewAttributes.hasOwnProperty(pageviewAttribute)) {
                let pageviewAttributeValue = config.campaign.pageviewAttributes[pageviewAttribute];
                if (typeof pageviewAttribute !== 'string' && !isArray(pageviewAttributeValue)) {
                    throw "remplib: configuration campaign.pageviewAttributes invalid (supports only strings and array of strings)" + param
                }
            }
        }
        this.pageviewAttributes = config.campaign.pageviewAttributes;

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

        if (window.opener && window.location.hash === '#bannerPicker') {
            remplib.loadScript(this.url + '/assets/lib/js/bannerSelector.js');
        }

        if (window.location.hash === '#campaignDebug') {
            remplib.loadScript(this.url + '/assets/lib/js/campaignDebug.js');
        }

        // configure campaign-based internal storage keys
        remplib.internalStorageKeys[this.campaignsStorageKey] = true;
        remplib.internalStorageKeys[this.campaignsSessionStorageKey] = true;

        // clean anything that's already obsolete
        let campaigns = this.getCampaigns();
        campaigns = this.cleanup(campaigns);
        if (campaigns) {
            remplib.setToStorage(this.campaignsStorageKey, this.minifyStoredData(campaigns));
        }

        if (Campaign.initialized) {
            this._reset();
        }
        Campaign.initialized = true;
    }

    checkInit() {
        return new Promise(function (resolve, reject) {
            var startTime = new Date().getTime();
            var interval = setInterval(function() {
                if (Campaign.initialized) {
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
    }

    cleanup(campaigns) {
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
    }

    run() {
        Promise.all([remplib.checkUsingAdblock(), this.checkInit()]).then((res) => {
            this.request(this.showtime);
        });
    }

    _reset() {
        // clear all displayed banners
        let displayedBanners = document.querySelectorAll('.remp-banner');
        displayedBanners.forEach(function (banner) {
            banner.innerHTML = '';
        });

        this.request(this.showtime);
    }

    request(def) {
        let params = {};
        params[def.jsonpParameter] = JSON.stringify(def.prepareData());

        this.get(this.url + "/" + def.name(), params, function (data) {
            def.processResponse && def.processResponse(data);
        }, function() {
            def.processError && def.processError();
        });
    }

    get(url, params, success, error) {
        // clear previously registered callback to avoid response processing duplication
        if (Campaign.callbackIterator > 0) {
            let previousCb = "rempcampaign_callback_json" + (Campaign.callbackIterator-1);
            window[previousCb] = function() { /* request cancelled */ };
        }

        let cb = "rempcampaign_callback_json" + Campaign.callbackIterator++;
        let query = "?";

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
    }

    getCampaignsForShowtime() {
        let campaigns = this.getCampaigns();
        // remove unnecessary variables to save characters in GET request
        for (let campaignId in campaigns) {
            delete campaigns[campaignId].createdAt;
            delete campaigns[campaignId].updatedAt;
        }
        return campaigns;
    }

    getCampaignsSessionsForShowtime() {
        const campaignsSession = this.getCampaignsSession();
        // remove unnecessary variables to save characters in GET request
        for (let campaignId in campaignsSession) {
            delete campaignsSession[campaignId].createdAt;
            delete campaignsSession[campaignId].updatedAt;
        }
        return campaignsSession;
    }

    // store persistent and session campaign details, called from banner view (when banner is shown)
    handleBannerDisplayed(campaignId, bannerId, variantId, campaignPublicId, bannerPublicId, variantPublicId) {
        this.storePersistentCampaignData(campaignId, bannerId, variantId, campaignPublicId, bannerPublicId, variantPublicId);
        this.storeSessionCampaignData(campaignId, campaignPublicId);
    }

    getCampaigns() {
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
    }

    storePersistentCampaignData(campaignId, bannerId, variantId, campaignPublicId, bannerPublicId, variantPublicId) {
        let campaigns = this.getCampaigns();

        const now = new Date();

        if (!campaigns.hasOwnProperty(campaignPublicId)) {
            campaigns[campaignPublicId] = {
                "bannerId": bannerPublicId,
                "variantId": variantPublicId,
                "seen": 0,
                "count": 0,
                "updatedAt": now,
            }
        }

        if (campaigns.hasOwnProperty(campaignId)) {
            campaigns[campaignPublicId].seen = campaigns[campaignId].seen;
            campaigns[campaignPublicId].count = campaigns[campaignId].count;
            delete(campaigns[campaignId]);
        }

        // always set the new value in case user doesn't have all object properties saved
        campaigns[campaignPublicId].bannerId = bannerPublicId;
        campaigns[campaignPublicId].variantId = variantPublicId;
        campaigns[campaignPublicId].updatedAt = now;
        campaigns[campaignPublicId].seen++;

        campaigns = remplib.campaign.cleanup(campaigns);
        remplib.setToStorage(this.campaignsStorageKey, this.minifyStoredData(campaigns));
    }

    incrementPageviewCountForCampaigns(activeCampaigns)  {
        let campaigns = this.getCampaigns();
        const now = new Date();

        if (!campaigns) {
            campaigns = {};
        }

        if (activeCampaigns) {
            for (const activeCampaign of activeCampaigns) {
                if (!campaigns.hasOwnProperty(activeCampaign.public_id)) {
                    // bannerId and variantID will be added later in storeCampaigns()
                    campaigns[activeCampaign.public_id] = {
                        "seen": 0,
                        "count": 0,
                        "updatedAt": now,
                    }
                }

                if (campaigns.hasOwnProperty(activeCampaign.uuid)) {
                    campaigns[activeCampaign.public_id].seen = campaigns[activeCampaign.uuid].seen;
                    campaigns[activeCampaign.public_id].count = campaigns[activeCampaign.uuid].count;
                    delete(campaigns[activeCampaign.uuid]);
                }

                campaigns[activeCampaign.public_id].count++;
                campaigns[activeCampaign.public_id].updatedAt = now;
            }
        }

        remplib.setToStorage(this.campaignsStorageKey, this.minifyStoredData(campaigns));
    }

    storeCampaignCollapsed(campaignPublicId, isCollapsed) {
        let campaigns = this.getCampaigns();

        const now = new Date();

        if (!campaigns.hasOwnProperty(campaignPublicId)) {
            campaigns[campaignPublicId] = {}
        }

        campaigns[campaignPublicId].updatedAt = now;
        campaigns[campaignPublicId].collapsed = isCollapsed;

        remplib.setToStorage(this.campaignsStorageKey, this.minifyStoredData(campaigns));
    }

    storeCampaignClosed(campaignPublicId) {
        this.storePersistentCampaignClosed(campaignPublicId);
        this.storeSessionCampaignClosed(campaignPublicId);
    }

    storePersistentCampaignClosed(campaignPublicId) {
        let campaigns = this.getCampaigns();

        const now = new Date();

        if (!campaigns.hasOwnProperty(campaignPublicId)) {
            campaigns[campaignPublicId] = {}
        }

        campaigns[campaignPublicId].updatedAt = now;
        campaigns[campaignPublicId].closedAt = Math.floor(now.getTime() / 1000);

        remplib.setToStorage(this.campaignsStorageKey, this.minifyStoredData(campaigns));
    }

    storeSessionCampaignClosed(campaignPublicId) {
        let campaignsSession = this.getCampaignsSession();

        const now = new Date();

        if (!campaignsSession.hasOwnProperty(campaignPublicId)) {
            campaignsSession[campaignPublicId] = {};
        }

        campaignsSession[campaignPublicId].updatedAt = now;
        campaignsSession[campaignPublicId].closedAt = Math.floor(now.getTime() / 1000);

        remplib.setToStorage(this.campaignsSessionStorageKey, this.minifyStoredData(campaignsSession));
    }

    storeCampaignClicked(campaignPublicId) {
        this.storePersistentCampaignClicked(campaignPublicId);
        this.storeSessionCampaignClicked(campaignPublicId);
    }

    storePersistentCampaignClicked(campaignPublicId) {
        let campaigns = this.getCampaigns();

        const now = new Date();

        if (!campaigns.hasOwnProperty(campaignPublicId)) {
            campaigns[campaignPublicId] = {}
        }

        campaigns[campaignPublicId].updatedAt = now;
        campaigns[campaignPublicId].clickedAt = Math.floor(now.getTime() / 1000);

        remplib.setToStorage(this.campaignsStorageKey, this.minifyStoredData(campaigns));
    }

    storeSessionCampaignClicked(campaignPublicId) {
        let campaignsSession = this.getCampaignsSession();

        const now = new Date();

        if (!campaignsSession) {
            campaignsSession = {};
        }

        campaignsSession[campaignPublicId].updatedAt = now;
        campaignsSession[campaignPublicId].clickedAt = Math.floor(now.getTime() / 1000);

        remplib.setToStorage(this.campaignsSessionStorageKey, this.minifyStoredData(campaignsSession));
    }

    getCampaignsSession() {
        let campaignsSession = this.unminifyStoredData(remplib.getFromStorage(this.campaignsSessionStorageKey)) || {};

        // migrations on campaigns values
        for (let campaignId in campaignsSession) {
            if (!campaignsSession[campaignId].hasOwnProperty('seen')) {
                campaignsSession[campaignId].seen = 0;
            }
        }
        return campaignsSession;
    }

    storeSessionCampaignData(campaignId, campaignPublicId) {
        let campaignsSession = this.getCampaignsSession();

        const now = new Date();

        if (!campaignsSession) {
            campaignsSession = {};
        }

        if (!campaignsSession.hasOwnProperty(campaignPublicId)) {
            campaignsSession[campaignPublicId] = {
                "seen": 0,
                "updatedAt": now,
            }
        }

        if (campaignsSession.hasOwnProperty(campaignId)) {
            campaignsSession[campaignPublicId].seen = campaignsSession[campaignId].seen;
            delete(campaignsSession[campaignId]);
        }

        campaignsSession[campaignPublicId].updatedAt = now;
        campaignsSession[campaignPublicId].seen++;

        remplib.setToStorage(this.campaignsSessionStorageKey, this.minifyStoredData(campaignsSession));
    }
    minifyStoredData(data) {
        for (const id in data) {
            this.renameKey(data[id], 'seen', 'sn');
            this.renameKey(data[id], 'count', 'ct');
            this.renameKey(data[id], 'variantId', 'vi');
            this.renameKey(data[id], 'bannerId', 'bi');
            this.renameKey(data[id], 'updatedAt', 'ut');
            this.renameKey(data[id], 'collapsed', 'cp');
            this.renameKey(data[id], 'closedAt', 'cl');
            this.renameKey(data[id], 'clickedAt', 'cc');

            if (data[id].hasOwnProperty('ut')) {
                const unixTime = new Date(data[id].ut).getTime();
                data[id].ut = Math.floor(unixTime / 1000);
            }
        }

        return JSON.stringify(data);
    }
    unminifyStoredData (data) {
        data = JSON.parse(data);
        for (const id in data) {
            this.renameKey(data[id], 'sn', 'seen');
            this.renameKey(data[id], 'ct', 'count');
            this.renameKey(data[id], 'vi', 'variantId');
            this.renameKey(data[id], 'bi', 'bannerId');
            this.renameKey(data[id], 'cp', 'collapsed');
            this.renameKey(data[id], 'cl', 'closedAt');
            this.renameKey(data[id], 'cc', 'clickedAt');

            if (data[id].hasOwnProperty('ut')) {
                const date = new Date();
                date.setTime(data[id].ut * 1000);
                data[id].ut = date;
            }
            this.renameKey(data[id], 'ut', 'updatedAt');
        }

        return data;
    }
    renameKey(data, oldKey, newKey) {
        if (data.hasOwnProperty(oldKey)) {
            data[newKey] = data[oldKey];
            delete data[oldKey];
        }
    }
    printSuppressedBanners(data) {
        if ('suppressedBanners' in data && data.suppressedBanners.length) {
            console.groupCollapsed("remplib: suppressed banners (prioritization)");
            for (let suppressed of data.suppressedBanners) {
                console.info(suppressed)
            }
            console.groupEnd();
        }
    }
}

(function(mocklib) {

    'use strict';

    let prodlib = Remplib;
    prodlib.campaign = new Campaign;

    prodlib.campaign._ = mocklib.campaign._ || [];
    remplib = Remplib.extend(mocklib, prodlib);
    remplib.bootstrap(remplib.campaign);

})(remplib);
