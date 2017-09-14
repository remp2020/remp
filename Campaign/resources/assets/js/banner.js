import Vue from 'vue';
import HtmlPreview from './components/previews/Html.vue';

window.remplib = window.remplib || {};

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
                transition: model['transition'] || null,
                displayType: model['display_type'] || 'overlay',
                // overlay
                position: model['position'] || null,
                closeable: model['closeable'] || null,
                displayDelay: model['display_delay'] || 0,
                closeTimeout: model['close_timeout'] || null,
                // inline
                targetSelector: model['target_selector'] || null
            }
        },

        bindPreview: function(el, banner, boxStyles) {
            return new Vue({
                el: el,
                data: banner,
                render: h => h(HtmlPreview, {
                    props: banner
                }),
            })
        },

    }

})();