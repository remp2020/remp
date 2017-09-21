import Vue from 'vue';
import HtmlPreview from './components/previews/Html.vue';
import MediumRectanglePreview from './components/previews/MediumRectangle.vue';

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
                template: model['template'] || null,
                // overlay
                position: model['position'] || null,
                closeable: model['closeable'] || null,
                displayDelay: model['display_delay'] || 0,
                closeTimeout: model['close_timeout'] || null,
                // inline
                targetSelector: model['target_selector'] || null
            };

            if (banner.template === 'medium_rectangle') {
                banner.headerText = model['medium_rectangle_template']['header_text'] || "";
                banner.mainText = model['medium_rectangle_template']['main_text'] || "";
                banner.buttonText = model['medium_rectangle_template']['button_text'] || "";
                banner.backgroundColor = model['medium_rectangle_template']['background_color'] || null;
                banner.textColor = model['medium_rectangle_template']['text_color'] || null;
                banner.buttonBackgroundColor = model['medium_rectangle_template']['button_background_color'] || null;
                banner.buttonTextColor = model['medium_rectangle_template']['button_text_color'] || null;
            }

            if (banner.template === 'html') {
                banner.backgroundColor = model['html_template']['background_color'] || null;
                banner.textColor = model['html_template']['text_color'] || null;
                banner.fontSize = model['html_template']['font_size'] || null;
                banner.textAlign = model['html_template']['text_align'] || null;
                banner.text = model['html_template']['text'] || null;
                banner.dimensions = model['html_template']['dimensions'] || null;
            }

            return banner;
        },

        bindPreview: function(el, banner) {
            let preview;
            switch (banner.template) {
                case "medium_rectangle":
                    preview = MediumRectanglePreview;
                    break;
                case "html":
                    preview = HtmlPreview;
                    break;
            }
            return new Vue({
                el: el,
                data: banner,
                render: h => h(preview, {
                    props: banner
                }),
            })
        },

    }

})();