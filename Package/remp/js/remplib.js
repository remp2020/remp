export default {

    beamToken: null,

    segmentProviderCacheKey: "segment_provider_cache",

    userId: null,

    userSubscribed: null,

    browserId: null,

    cacheThreshold: 15 * 60000, // 15 minutes

    rempSessionIDKey: "remp_session_id",

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
        let browserId = this.getFromStorage(storageKey, true, true);
        if (browserId) {
            return browserId;
        }

        // this section is added for historical reasons and migration of value
        // it will be removed within next couple of weeks
        let deprecatedStorageKey = "anon_id";
        let anonId = this.getFromStorage(deprecatedStorageKey, true, true);
        if (anonId) {
            browserId = anonId;
            this.removeFromStorage(deprecatedStorageKey);
        }

        if (!browserId) {
            browserId = remplib.uuidv4();
        }

        let now = new Date();
        let item = {
            "version": 1,
            "value": browserId,
            "createdAt": now,
            "updatedAt": now,
        };
        this.setToStorage(storageKey, item);
        return browserId;
    },

    getRempSessionID: function() {
        const storageKey = this.rempSessionIDKey;
        let rempSessionID = this.getFromStorage(storageKey, false, true);
        if (rempSessionID) {
            return rempSessionID;
        }
        rempSessionID = remplib.uuidv4();
        const now = new Date();
        let item = {
            "version": 1,
            "value": rempSessionID,
            "createdAt": now,
            "updatedAt": now,
        };
        this.setToStorage(storageKey, item);
        return rempSessionID;
    },

    getRempPageviewID: function() {
        if (this.rempPageviewID) {
            return this.rempPageviewID;
        }
        this.rempPageviewID = remplib.uuidv4();
        return this.rempPageviewID;
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

    setToStorage: function(key, item) {
        localStorage.setItem(key, JSON.stringify(item));

        // clone value of item also to cookie
        let value = item;
        if (item.hasOwnProperty('value')) {
            value = item.value;
        }
        const now = new Date();
        let cookieExp = new Date();
        cookieExp.setTime(now.getTime() + this.cacheThreshold);
        const expires = "; expires=" + cookieExp.toUTCString();
        let domain = "";
        if (this.cookieDomain !== null) {
            domain = "; domain=" + this.cookieDomain;
        }
        document.cookie = key + "=" + value + expires + "; path=/"+ domain + ";";
    },

    /**
     * Tries to retrieve key's value from localStorage
     * Side effect of this function is an extension of key's lifetime in the storage
     * @param key
     * @param bypassThreshold
     * @param storeToCookie
     * @returns {*}
     */
    getFromStorage: function(key, bypassThreshold, storeToCookie) {
        let now = new Date();
        let data = localStorage.getItem(key);
        if (data === null) {
            return null;
        }

        let item = JSON.parse(data);
        let threshold = new Date(now.getTime() - this.cacheThreshold);
        if (!bypassThreshold && (new Date(item.updatedAt)).getTime() < threshold.getTime()) {
            localStorage.removeItem(key);
            return null;
        }

        if (item.hasOwnProperty("updatedAt")) {
            item.updatedAt = now;
        }

        if (storeToCookie) {
            this.setToStorage(key, item);
        } else {
            localStorage.setItem(key, JSON.stringify(item));
        }

        if (item.hasOwnProperty('value')) {
            return item.value;
        }
        return item;
    },

    removeFromStorage: function(key) {
        localStorage.removeItem(key);

        let cookieExp = new Date();
        const expires = "; expires=" + cookieExp.toUTCString();
        let domain = "";
        if (this.cookieDomain !== null) {
            domain = "; domain=" + this.cookieDomain;
        }
        document.cookie = key + "=" + expires + "; path=/"+ domain + ";";
    },

    // jquery extend
    extend: function () {
        var options, name, src, copy, copyIsArray, clone,
            target = arguments[0] || {},
            i = 1,
            length = arguments.length,
            deep = false;

        // Handle a deep copy situation
        if (typeof target === "boolean") {
            deep = target;

            // Skip the boolean and the target
            target = arguments[i] || {};
            i++;
        }

        // Handle case when target is a string or something (possible in deep copy)
        if (typeof target !== "object" && !isFunction(target)) {
            target = {};
        }

        // Extend jQuery itself if only one argument is passed
        if (i === length) {
            target = this;
            i--;
        }

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

                    // Recurse if we're merging plain objects or arrays
                    if (deep && copy && (jQuery.isPlainObject(copy) ||
                        (copyIsArray = Array.isArray(copy)))) {
                        src = target[name];

                        // Ensure proper type for the source value
                        if (copyIsArray && !Array.isArray(src)) {
                            clone = [];
                        } else if (!copyIsArray && !jQuery.isPlainObject(src)) {
                            clone = {};
                        } else {
                            clone = src;
                        }
                        copyIsArray = false;

                        // Never move original objects, clone them
                        target[name] = jQuery.extend(deep, clone, copy);

                        // Don't bring in undefined values
                    } else if (copy !== undefined) {
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
