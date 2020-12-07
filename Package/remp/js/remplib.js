export default {

    beamToken: null,

    segmentProviderCacheKey: "segment_provider_cache",

    userId: null,

    userSubscribed: null,

    browserId: null,

    rempSessionIDKey: "remp_session_id",

    commerceSessionIDKey: "commerce_session_id",

    storage: "local_storage", // "cookie", "local_storage"

    storageExpiration: {
        "default": 15,
        "keys": {
            "browser_id": 1051200, // 2 years in minutes
            "campaigns": 1051200,
        }
    },

    cookieDomain: null,

    rempPageviewIDKey: "remp_pageview_id",

    rempPageviewID: null,

    usingAdblock: null,

    getUserId: function() {
        return this.userId;
    },

    isUserSubscriber: function() {
        return this.userSubscribed;
    },

    getBrowserId: function() {
        if (this.browserId) {
            return this.browserId;
        }

        let storageKey = "browser_id";
        let browserId = this.getFromStorage(storageKey);
        if (browserId) {
            return browserId;
        }

        if (!browserId) {
            browserId = remplib.uuidv4();
        }

        this.setToStorage(storageKey, browserId);
        return browserId;
    },

    getRempSessionID: function() {
        const storageKey = this.rempSessionIDKey;
        let rempSessionID = this.getFromStorage(storageKey);
        if (rempSessionID) {
            return rempSessionID;
        }
        rempSessionID = remplib.uuidv4();
        this.setToStorage(storageKey, rempSessionID);
        return rempSessionID;
    },

    getRempPageviewID: function() {
        if (this.rempPageviewID) {
            return this.rempPageviewID;
        }
        this.rempPageviewID = remplib.uuidv4();
        return this.rempPageviewID;
    },

    getCommerceSessionID: function() {
        let commerceSessionID = this.getFromStorage(this.commerceSessionIDKey);
        if (commerceSessionID) {
            return commerceSessionID;
        }

        throw "remplib: commerce_session_id not found. It has to be generated in the checkout step.";
    },

    generateCommerceSessionID: function() {
        let commerceSessionIDKey = remplib.uuidv4();
        this.setToStorage(this.commerceSessionIDKey, commerceSessionIDKey);

        return commerceSessionIDKey;
    },

    uuidv4: function() {
        let format = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx';
        if (!window.crypto) {
            return format.replace(/[xy]/g, function(c) {
                let r = Math.random()*16|0, v = c == 'x' ? r : (r&0x3|0x8);
                return v.toString(16);
            });
        }

        let nums = window.crypto.getRandomValues(new Uint8ClampedArray(format.split(/[xy]/).length - 1));
        let pointer = 0;
        return format.replace(/[xy]/g, function(c) {
            let r = nums[pointer++] % 16,
                v = (c === 'x') ? r : (r&0x3|0x8);
            return v.toString(16);
        });
    },

    lifetimeInMinutes: function(key) {
        if (this.storageExpiration['keys'][key]) {
            return this.storageExpiration['keys'][key];
        }
        return this.storageExpiration['default'];
    },

    setToStorage: function(key, value) {
        let now = new Date();
        let serializedItem = JSON.stringify({
            "version": 1,
            "value": value,
            "updatedAt": now
        });

        if (this.storage === 'local_storage') {
            localStorage.setItem(key, serializedItem);
        }
        let expireAt = new Date(now.getTime() + (this.lifetimeInMinutes(key) * 60000));
        this.storeCookie(key, value, expireAt);
    },

    /**
     * Tries to retrieve key's value from storage (local/cookie)
     * Side effect of this function is an extension of key's lifetime in the storage
     * @param key
     * @returns {*}
     */
    getFromStorage: function(key) {
        let now = new Date();

        let expireInMinutes = this.storageExpiration['default'];
        if (this.storageExpiration['keys'][key]) {
            expireInMinutes = this.storageExpiration['keys'][key];
        }

        let value = null;
        if (this.storage === 'local_storage') {
            value = this.getFromLocalStorage(key, now, expireInMinutes);
            if (value) {
                return value;
            }
        }

        // universal fallback
        value = this.getCookie(key);
        if (value) {
            let expireAt = new Date(now.getTime() + expireInMinutes * 60000);
            this.storeCookie(key, value, expireAt);
            return value;
        }

        // Migration from local storage to cookie (e.g. campaigns_session). We got here because we didn't find value
        // in the cookie. It might be present in the local_storage.
        if (this.storage === 'cookie') {
            value = this.getFromLocalStorage(key, now, expireInMinutes);
        }

        return value;
    },

    getFromLocalStorage: function(key, now, expireInMinutes) {
        let data = localStorage.getItem(key);
        if (!data) {
            return null;
        }

        let item = null;
        try {
            item = JSON.parse(data);
            if (!item.hasOwnProperty("updatedAt") || !item.hasOwnProperty('version')) {
                // This might not be our value, or it's just not migrated yet. Returning what we've found.
                return data;
            }
        } catch (e) {
            // There wasn't a JSON, definitely not our value. Returning what we've found.
            return data;
        }

        let itemExpirationDate = new Date(new Date(item.updatedAt).getTime() + (expireInMinutes * 60000));
        if (itemExpirationDate < now) {
            this.removeFromStorage(key);
            return null;
        }

        let value = null;
        if (item.hasOwnProperty('value')) {
            value = item.value;
        } else if (item.hasOwnProperty('values')) {
            // inconsistency failsave :(
            value = item.values;
        }

        if (typeof value === "object") {
            // To be compatible with cookie storage, we only accept strings as values. This is backwards
            // compatibility action to maintain existing objects.
            value = JSON.stringify(value);
        }

        // SIDE-EFFECT: update "updatedAt" to extend local storage expiration
        item.updatedAt = now;
        localStorage.setItem(key, JSON.stringify(item));

        // SIDE-EFFECT: update fallback cookie expiration
        let expireAt = new Date(now.getTime() + (this.lifetimeInMinutes(key) * 60000));
        this.storeCookie(key, value, expireAt);

        return value;
    },

    removeFromStorage: function(key) {
        localStorage.removeItem(key);
        this.removeCookie(key);
    },

    getCookie: function(name) {
        let result = document.cookie.match(new RegExp(name + '=([^;]+)'));
        if (!result) {
            return null;
        }
        return result[1];
    },

    storeCookie: function(name, value, expires) {
        if (!value || value.length === 0) {
            return;
        }
        document.cookie = [
            name, '=', value,
            '; expires=', expires.toUTCString(),
            '; domain=', this.cookieDomain,
            '; path=/;'
        ].join('');
    },

    removeCookie: function(name) {
        document.cookie = [
            name, '=; expires=Thu, 01-Jan-1970 00:00:01 GMT; path=/; domain=',
            this.cookieDomain
        ].join('');
    },

    // simplified jquery extend (without deep copy, not copying null values)
    extend: function () {
        var options, name, copy,
            target = arguments[0] || {},
            i = 1,
            length = arguments.length;

        for (; i < length; i++) {

            // Only deal with non-null/undefined values
            if ((options = arguments[i]) != null) {

                // Extend the base object
                for (name in options) {
                    copy = options[name];

                    // Prevent Object.prototype pollution
                    // Prevent never-ending loop
                    if (name === "__proto__" || target === copy) {
                        continue;
                    }
                    // Don't bring in undefined or null values
                    if (copy !== undefined && copy !== null) {
                        target[name] = copy;
                    }
                }
            }
        }

        // Return the modified object
        return target;
    },

    bootstrap: function(app) {
        this.applyPolyfills();
        if (this.isBot(navigator.userAgent)) {
            return;
        }
        if (typeof app._ === 'undefined' || app._.length === 0) {
            app.run();
            return;
        }
        for (let i=0; i < app._.length; i++) {
            let cb = app._[i];
            setTimeout((function() {
                let cbf = cb[0];
                let cbargs = cb[1];
                return function() {
                    if (cbf !== "run") {
                        app[cbf].apply(app, cbargs);
                    }
                    app.initIterator = app.initIterator+1 || 1;
                    if (app.initIterator === app._.length) {
                        app.run();
                    }
                }
            })(), 0);
        }
    },

    applyPolyfills: function() {
        // CustomEvent constructor for IE
        if ( typeof window.CustomEvent === "function" ) return false;
        function CustomEvent ( event, params ) {
            params = params || { bubbles: false, cancelable: false, detail: undefined };
            let evt = document.createEvent( 'CustomEvent' );
            evt.initCustomEvent( event, params.bubbles, params.cancelable, params.detail );
            return evt;
        }
        CustomEvent.prototype = window.Event.prototype;
        window.CustomEvent = CustomEvent;

        // support addEventListener for IE8
        if (typeof Element.prototype.addEventListener === 'undefined') {
            Element.prototype.addEventListener = function (e, callback) {
                e = 'on' + e;
                return this.attachEvent(e, callback);
            };
        }
    },

    loadScript: function (src, callback) {
        let s = document.createElement('script');
        s.src = src;
        s.async = true;
        s.onreadystatechange = s.onload = function() {
            if (typeof callback !== 'undefined' && !callback.done && (!s.readyState || /loaded|complete/.test(s.readyState))) {
                callback.done = true;
                callback();
            }
        };
        document.getElementsByTagName('head')[0].appendChild(s);
    },

    loadStyle: function (src, callback) {
        let l = document.createElement('link');
        l.href = src;
        l.rel = "stylesheet";
        l.onreadystatechange = l.onload = function() {
            if (typeof callback !== 'undefined' && !callback.done && (!l.readyState || /loaded|complete/.test(l.readyState))) {
                callback.done = true;
                callback();
            }
        };
        document.getElementsByTagName('head')[0].appendChild(l);
    },

    isBot: function(userAgent) {
        return userAgent.match(/bot|crawl|slurp|spider|mediapartners/i);
    },

    checkUsingAdblock: function () {
        return new Promise(function (resolve) {
            var testAd = document.createElement('div');
            testAd.innerHTML = '&nbsp;';
            testAd.setAttribute('class', 'pub_300x250 pub_300x250m pub_728x90 text-ad textAd text_ad text_ads text-ads text-ad-links');
            testAd.setAttribute('style', 'width: 1px !important; height: 1px !important; position: absolute !important; left: -10000px !important; top: -1000px');
            document.body.appendChild(testAd);
            window.setTimeout(function() {
                remplib.usingAdblock = testAd.offsetHeight === 0 || false;
                testAd.remove();
                resolve(remplib.usingAdblock);
            }, 100);
        });
    },
}
