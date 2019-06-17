import Remplib from 'remp/js/remplib';
import Vue from 'vue';
import IotaTemplate from './components/IotaTemplate';
import IotaService from './components/IotaService';
import IotaScrolledToHere from './components/IotaScrolledToHere';
import IotaSettings from './components/IotaSettings';
import IotaHistogram from './components/IotaHistogram';

remplib = typeof remplib === 'undefined' ? {} : remplib;

(function(mocklib) {
  'use strict';

  let prodlib = Remplib;

  prodlib.iota = {
    _: [],

    url: null,

    configUrl: null,

    articleElementFn: function() {
      return null;
    },

    articleSelector: null,

    idCallback: null,

    targetElementCallback: null,

    httpHeaders: {},

    init: function(config) {
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

      if (typeof config.iota.articleElementFn !== 'function') {
        throw 'remplib: configuration iota.articleElementFn invalid or missing: ' +
          config.iota.articleElementFn;
      }
      this.articleElementFn = config.iota.articleElementFn;

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
    },

    run: function() {
      const weAreOnArticleDetail = !!this.articleElementFn();
      let articleIds = [];

      // initialize IotaTemplate component
      if (!weAreOnArticleDetail) {
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
              articleId: aid
            }
          });
          vm.$mount(iotaElemContainer);
        }
      }

      // initialize IotaService component
      const iotaContainer = document.createElement('div');
      document.body.appendChild(iotaContainer);
      let vm = new (Vue.extend(IotaService))({
        propsData: {
          articleIds: articleIds.length ? articleIds : ['1497874'], // TODO: how to add here current article detail ID?
          baseUrl: this.url,
          configUrl: this.configUrl,
          httpHeaders: this.httpHeaders,
          onArticleDetail: weAreOnArticleDetail
        }
      });
      vm.$mount(iotaContainer);

      // initialize IotaScrolledToHere component
      if (weAreOnArticleDetail) {
        const iotaScrolledToHereContainer = document.createElement('div');
        document.body.appendChild(iotaScrolledToHereContainer);
        vm = new (Vue.extend(IotaScrolledToHere))();
        vm.$mount(iotaScrolledToHereContainer);
      }

      // initialize IotaHistogram component
      if (weAreOnArticleDetail) {
        const iotaHistogramContainer = document.createElement('div');
        document.body.appendChild(iotaHistogramContainer);
        vm = new (Vue.extend(IotaHistogram))();
        vm.$mount(iotaHistogramContainer);
      }

      // initialize IotaSettings component
      const iotaSettingsContainer = document.createElement('div');
      document.body.appendChild(iotaSettingsContainer);
      vm = new (Vue.extend(IotaSettings))({
        propsData: {
          onArticleDetail: weAreOnArticleDetail
        }
      });
      vm.$mount(iotaSettingsContainer);
    }
  };

  prodlib.iota._ = mocklib.iota._ || [];
  remplib = prodlib.extend(mocklib, prodlib);
  remplib.bootstrap(remplib.iota);
})(remplib);
