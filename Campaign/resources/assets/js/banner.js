import Vue from 'vue';
import BannerPreview from './components/BannerPreview.vue';

remplib = typeof(remplib) === 'undefined' ? {} : remplib;

(function() {

    'use strict';

    remplib.banner = {

        fromModel: (model) => {
            return {
                name: model['name'] || null,
                dimensions: model['dimensions'] || null,
                text: model['text'] || null,
                textAlign: model['text_align'] || null,
                fontSize: model['font_size'] || 18,
                targetUrl: model['target_url'] || null,
                textColor: model['text_color'] || null,
                backgroundColor: model['background_color'] || null,
                position: model['position'] || null,
                transition: model['transition'] || null,
                closeable: model['closeable'] || null,
                displayDelay: model['display_delay'] || 0,
                closeTimeout: model['close_timeout'] || null
            }
        },

        bindPreview: function(el, banner, boxStyles) {
            return new Vue({
                el: el,
                data: banner,
                render: h => h(BannerPreview, {
                    props: banner
                }),
            })
        },

    }

})();