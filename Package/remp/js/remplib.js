export default {

    beamToken: null,

    segmentProviderCacheKey: "segment_provider_cache",

    userId: null,

    browserId: null,

    cacheThreshold: 15 * 60000, // 15 minutes

    rempSessionIDKey: "remp_session_id",

    cookieDomain: null,

    rempPageviewIDKey: "remp_pageview_id",

    rempPageviewID: null,

    getUserId: function() {
        return this.userId;
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

    extend: function() {
        let a, b, c, f, l, g = arguments[0] || {}, k = 1, v = arguments.length, n = !1;
        "boolean" === typeof g && (n = g,
            g = arguments[1] || {},
            k = 2);
        "object" === typeof g || d.isFunction(g) || (g = {});
        v === k && (g = this,
            --k);
        for (; k < v; k++)
            if (null != (a = arguments[k]))
                for (b in a)
                    c = g[b],
                        f = a[b],
                    g !== f && (n && f && (d.isPlainObject(f) || (l = d.isArray(f))) ? (l ? (l = !1,
                        c = c && d.isArray(c) ? c : []) : c = c && d.isPlainObject(c) ? c : {},
                        g[b] = d.extend(n, c, f)) : void 0 !== f && (g[b] = f));
        return g
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
}
