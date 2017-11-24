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

        campaignsSessionStorageKey: "campaigns_session",

        /* JSONP START */

        showtime: {
            name: "campaigns/showtime",
            jsonpParameter: "data",
            prepareData: function() {
                return {
                    "userId": remplib.getUserId(),
                    "signedIn": remplib.signedIn,
                    "url": window.location.href,
                    "campaignsSeen": remplib.campaign.getCampaignsSeen(),
                    "cache": remplib.getFromStorage(remplib.segmentProviderCacheKey, true),
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

            if (typeof config.campaign.variables !== 'undefined') {
                if (typeof config.campaign.variables !== 'object') {
                    throw "remplib: configuration variables invalid (object required): "+config.campaign.variables
                }
                this.variables = config.campaign.variables;
            }

            // global
            if (typeof config.userId !== 'undefined' && config.userId !== null) {
                remplib.userId = config.userId;
            }
            if (typeof config.signedIn !== 'undefined') {
                if (typeof config.signedIn !== 'boolean') {
                    throw "remplib: configuration signedIn invalid (boolean required): "+config.signedIn
                }
                remplib.signedIn = config.signedIn;
            }
        },

        run: function() {
            this.request(this.showtime);
        },

        request: function(def) {
            let params = {};
            params[def.jsonpParameter] = JSON.stringify(def.prepareData());

            this.get(this.url + "/" + def.name, params, function (data) {
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

        // used to store seen campaign from campaign / banner view
        storeCampaign: function(campaignId) {
            let now = new Date();
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
