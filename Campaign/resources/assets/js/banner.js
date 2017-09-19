import Vue from 'vue';
import HtmlPreview from './components/previews/Html.vue';

window.remplib = window.remplib || {};

(function() {

    'use strict';

    remplib.banner = {

        fromModel: (model) => {
            let banner = {
                name: model['name'] || null,
                targetUrl: model['target_url'] || null,
                transition: model['transition'] || null,
                displayType: model['display_type'] || 'overlay',
                // overlay
                position: model['position'] || null,
                closeable: model['closeable'] || null,
                displayDelay: model['display_delay'] || 0,
                closeTimeout: model['close_timeout'] || null,
                // inline
                targetSelector: model['target_selector'] || null
            };

            if (banner.template === 'medium_rectangle') {
                banner.mediumRectangleHeaderText = model['medium_rectangle_template']['header_text'] || "";
                banner.mediumRectangleMainText = model['medium_rectangle_template']['main_text'] || "";
                banner.mediumRectangleButtonText = model['medium_rectangle_template']['button_text'] || "";
                banner.mediumRectangleBackgroundColor = model['medium_rectangle_template']['background_color'] || null;
            }

            if (banner.template === 'html') {
                banner.htmlBackgroundColor = model['html_template']['background_color'] || null;
                banner.htmlTextColor = model['html_template']['text_color'] || null;
                banner.htmlFontSize = model['html_template']['font_size'] || null;
                banner.htmlTextAlign = model['html_template']['text_align'] || null;
                banner.htmlText = model['html_template']['text'] || null;
                banner.htmlDimensions = model['html_template']['dimensions'] || null;
            }

            return banner;
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