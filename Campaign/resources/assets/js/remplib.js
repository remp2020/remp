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

        /* JSONP START */

        showtime: {
            name: "campaigns/showtime",
            jsonpParameter: "data",
            prepareData: function() {
                return {
                    "userId": remplib.getUserId(),
                    "signedIn": remplib.signedIn,
                    "url": window.location.href
                }
            },
            processResponse: function(result) {
                if (!result["success"]) {
                    return;
                }
                for (var exec = result.data, c = 0; c < result.data.length; c++) {
                    try {
                        var fn = new Function(exec[c]);
                        setTimeout(fn(), 0);
                    } catch (u) {
                        console.error("campaign showtime error:", u)
                    }
                }
            }
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
            var params = {};
            params[def.jsonpParameter] = JSON.stringify(def.prepareData());

            this.get(this.url + "/" + def.name, params, function (data) {
                def.processResponse && def.processResponse(data);
            }, function() {
                def.processError && def.processError();
            });
        },

        get: function(url, params, success, error) {
            var query = "?";
            var cb = "rempcampaign_callback_json" + this.callbackIterator++;

            for (var item in params)
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
    };

    prodlib.campaign._ = mocklib.campaign._ || [];
    remplib = prodlib.extend(mocklib, prodlib);
    remplib.bootstrap(remplib.campaign);

})(remplib);