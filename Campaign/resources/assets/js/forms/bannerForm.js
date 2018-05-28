import Transformer from '../components/_transformer.js';
import BannerForm from '../components/BannerForm.vue';

remplib = typeof(remplib) === 'undefined' ? {} : remplib;

(function() {

    'use strict';

    remplib.bannerForm = {

        bind: (el, banner) => {
            // failsave, one of the options has to be selected...
            banner.dimensions = banner.dimensions || Object.keys(banner.dimensionOptions)[0];
            banner.textAlign = banner.textAlign || Object.keys(banner.alignmentOptions)[0];
            banner.position = banner.position || Object.keys(banner.positionOptions)[0];

            return new Vue({
                el: el,
                render: h => h(BannerForm, {
                    props: Transformer.transformKeys(banner)
                }),
            });
        }

    }

})();
