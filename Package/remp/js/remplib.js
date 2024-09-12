export default {

    beamToken: null,

    segmentProviderCacheKey: "segment_provider_cache",

    userId: null,

    userSubscribed: null,

    subscriptionIds: [],

    browserId: null,

    rempSessionIDKey: "remp_session_id",

    rempSessionRefererKey: "remp_session_referer",

    commerceSessionIDKey: "commerce_session_id",

    storage: "local_storage", // "cookie", "local_storage"

    internalStorageKeys: null,

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

    config: null,

    init: function(config) {
        remplib.config = config;

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
        if (!this.localStorageIsAvailable()) {
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

        remplib.internalStorageKeys = remplib.internalStorageKeys || {};

        remplib.internalStorageKeys["browser_id"] = true;
        remplib.internalStorageKeys[remplib.rempSessionIDKey] = true;
        remplib.internalStorageKeys[remplib.rempPageviewIDKey] = true;
        remplib.internalStorageKeys[remplib.commerceSessionIDKey] = true;
        remplib.internalStorageKeys[remplib.rempSessionRefererKey] = true;
        remplib.internalStorageKeys["rtm_source"] = true;
        remplib.internalStorageKeys["rtm_medium"] = true;
        remplib.internalStorageKeys["rtm_campaign"] = true;
        remplib.internalStorageKeys["rtm_content"] = true;
        remplib.internalStorageKeys["rtm_variant"] = true;
    },

    getConfig: function() {
        return this.config;
    },

    getUserId: function() {
        return this.userId;
    },

    isUserSubscriber: function() {
        return this.userSubscribed;
    },

    getSubscriptionIds: function() {
        return this.subscriptionIds;
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

        // store referer for this session
        this.setToStorage(this.rempSessionRefererKey, document.referrer)

        return rempSessionID;
    },

    getRempSessionReferer: function() {
        // just making sure the session was initialized before reading session referer
        this.getRempSessionID();

        return this.getFromStorage(this.rempSessionRefererKey)
    },

    getRempPageviewID: function() {
        if (this.rempPageviewID) {
            return this.rempPageviewID;
        }
        this.rempPageviewID = remplib.uuidv4();
        return this.rempPageviewID;
    },

    resetRempPageviewID: function() {
        this.rempPageviewID = remplib.uuidv4();
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
        if (typeof remplib.internalStorageKeys[key] === 'undefined' && typeof remplib.storageExpiration['keys'][key] === 'undefined') {
            console.warn('remplib: Ignoring attempt to store "' + key + '" to the storage. Only internal keys and keys with explicit rempConfig.storageExpiration are allowed.');
            return;
        }
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
            value = this._getFromLocalStorage(key, now, expireInMinutes);
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
        if (this.storage === 'cookie' && this.localStorageIsAvailable()) {
            value = this._getFromLocalStorage(key, now, expireInMinutes);
        }

        return value;
    },

    // Recommended check that localStorage is available AND usable
    // https://developer.mozilla.org/en-US/docs/Web/API/Web_Storage_API/Using_the_Web_Storage_API#testing_for_availability
    localStorageIsAvailable: function() {
        var storage;
        try {
            storage = window['localStorage'];
            var x = '__storage_test__';
            storage.setItem(x, x);
            storage.removeItem(x);
            return true;
        }
        catch(e) {
            return e instanceof DOMException && (
                // everything except Firefox
                e.code === 22 ||
                // Firefox
                e.code === 1014 ||
                // test name field too, because code might not be present
                // everything except Firefox
                e.name === 'QuotaExceededError' ||
                // Firefox
                e.name === 'NS_ERROR_DOM_QUOTA_REACHED') &&
                // acknowledge QuotaExceededError only if there's something already stored
                (storage && storage.length !== 0);
        }
    },

    // private method
    _getFromLocalStorage: function(key, now, expireInMinutes) {
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
            '; path=/; SameSite=Lax'
        ].join('');
    },

    removeCookie: function(name) {
        document.cookie = [
            name, '=; expires=Thu, 01-Jan-1970 00:00:01 GMT; path=/; domain=; SameSite=Lax',
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
        while (app._.length) {
            let cb = app._.shift();
            let cbf = cb[0];
            let cbargs = cb[1];

            if (cbf !== "run") {
                app[cbf].apply(app, cbargs);
            }
        }
        app.run();
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
