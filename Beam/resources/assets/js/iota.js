import Remplib from '@remp/js-commons/js/remplib';
import Vue from 'vue';
import IotaTemplate from './components/IotaTemplate';
import IotaService from './components/IotaService';
import IotaScrolledToHere from './components/IotaScrolledToHere';
import IotaSettings from './components/IotaSettings';
import IotaHistogram from './components/IotaHistogram';

remplib = typeof remplib === 'undefined' ? {} : remplib;

(function (mocklib) {
    'use strict';

    let prodlib = Remplib;

    prodlib.iota = {
        _: [],

        // required: URL of Beam segments API
        url: null,

        // URL of Beam configuration settings
        configUrl: null,

        // required: selector matching all "article" elements on site you want to be reported
        articleSelector: null,

        // required: callback for articleId extraction out of matched element
        idCallback: null,

        // optional: callback for selecting element where the stats will be placed as next sibling; if not present, stats are appended as next sibling to matchedElement
        targetElementCallback: null,

        // optional: HTTP headers to be used in API calls
        httpHeaders: {},

        // optional: article information object, should be populated only on article pageview
        article: {
            id: null
        },

        init: function (config) {
            if (typeof config.iota !== 'object') {
                throw 'remplib: configuration tracker invalid or missing: ' +
                config.iota;
            }

            if (typeof config.iota.url !== 'string') {
                throw 'remplib: configuration iota.url invalid or missing: ' +
                config.iota.url;
            }
            this.url = config.iota.url;

            if (typeof config.iota.configUrl === 'string') {
                this.configUrl = config.iota.configUrl;
            }

            if (typeof config.iota.articleSelector !== 'string') {
                throw 'remplib: configuration iota.articleSelector invalid or missing: ' +
                config.iota.articleSelector;
            }
            this.articleSelector = config.iota.articleSelector;

            if (typeof config.iota.idCallback !== 'function') {
                throw 'remplib: configuration iota.idCallback invalid or missing: ' +
                config.iota.idCallback;
            }
            this.idCallback = config.iota.idCallback;

            if (typeof config.iota.targetElementCallback !== 'undefined') {
                if (!config.iota.targetElementCallback instanceof Function) {
                    throw 'remplib: configuration iota.targetElementCallback invalid: ' +
                    config.iota.targetElementCallback;
                }
                this.targetElementCallback = config.iota.targetElementCallback;
            }

            if (typeof config.iota.httpHeaders !== 'undefined') {
                if (!config.iota.httpHeaders instanceof Object) {
                    throw 'remplib: configuration iota.httpHeaders invalid: ' +
                    config.iota.httpHeaders;
                }
                this.httpHeaders = config.iota.httpHeaders;
            }

            if (config.article && typeof config.article === 'object') {
                if (
                    typeof config.article.id === 'undefined' ||
                    config.article.id === null
                ) {
                    throw 'remplib: configuration tracker.article.id invalid or missing: ' +
                    config.article.id;
                }
                this.article.id = config.article.id;
            } else {
                this.article = null;
            }
        },

        run: function () {
            const weAreOnArticleDetail = !!(this.article && this.article.id);
            let articleIds = [];

            // initialize IotaTemplate component
            for (let elem of document.querySelectorAll(this.articleSelector)) {
                const iotaElemContainer = document.createElement('div');

                if (this.targetElementCallback) {
                    let targetElement = this.targetElementCallback(elem);
                    if (!targetElement) {
                        continue;
                    }
                    targetElement.parentNode.insertBefore(
                        iotaElemContainer,
                        targetElement.nextSibling
                    );
                } else {
                    elem.parentNode.insertBefore(iotaElemContainer, elem.nextSibling);
                }

                let aid = this.idCallback(elem);
                if (!aid) {
                    continue;
                }

                articleIds.push(aid);
                let vm = new (Vue.extend(IotaTemplate))({
                    propsData: {
                        articleId: aid,
                        baseUrl: this.url
                    }
                });
                vm.$mount(iotaElemContainer);
            }

            // initialize IotaService component
            const iotaContainer = document.createElement('div');
            document.body.appendChild(iotaContainer);
            let vm = new (Vue.extend(IotaService))({
                propsData: {
                    articleIds: articleIds,
                    articleDetailId: weAreOnArticleDetail ? this.article.id : null,
                    baseUrl: this.url,
                    configUrl: this.configUrl,
                    httpHeaders: this.httpHeaders,
                    onArticleDetail: weAreOnArticleDetail
                }
            });
            vm.$mount(iotaContainer);

            // initialize IotaScrolledToHere component
            const iotaScrolledToHereContainer = document.createElement('div');
            document.body.appendChild(iotaScrolledToHereContainer);
            vm = new (Vue.extend(IotaScrolledToHere))();
            vm.$mount(iotaScrolledToHereContainer);

            // initialize IotaHistogram component
            const iotaHistogramContainer = document.createElement('div');
            document.body.appendChild(iotaHistogramContainer);
            vm = new (Vue.extend(IotaHistogram))();
            vm.$mount(iotaHistogramContainer);

            // initialize IotaSettings component
            const iotaSettingsContainer = document.createElement('div');
            document.body.appendChild(iotaSettingsContainer);
            vm = new (Vue.extend(IotaSettings))({
                propsData: {
                    onArticleDetail: weAreOnArticleDetail
                }
            });
            vm.$mount(iotaSettingsContainer);
        },

        reset: function () {

        }
    };

    prodlib.iota._ = mocklib.iota._ || [];
    remplib = prodlib.extend(mocklib, prodlib);
    remplib.bootstrap(remplib.iota);
})(remplib);
