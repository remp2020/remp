import Vue from 'vue';
import BannerPreview from './components/BannerPreview.vue';
import {registerStripHtmlFilter} from "./vueFilters";

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
                offsetVertical: model['offset_vertical'] || 0,
                offsetHorizontal: model['offset_horizontal'] || 0,
                closeText: model['close_text'] || null,
                closeable: model['closeable'] || null,
                displayDelay: model['display_delay'] || 0,
                closeTimeout: model['close_timeout'] || null,
                // inline
                targetSelector: model['target_selector'] || null,
                variant: model['variant_uuid'],
                adminPreview: false,
                js: model['js'] || null,
                jsIncludes: model['js_includes'] || null,
                cssIncludes: model['css_includes'] || null,
                manualEventsTracking: model['manual_events_tracking'] || false
            };

            if (banner.template === 'medium_rectangle') {
                banner.mediumRectangleTemplate = {
                    id: model['medium_rectangle_template']['id'] || null,
                    headerText: model['medium_rectangle_template']['header_text'] || "",
                    mainText: model['medium_rectangle_template']['main_text'] || "",
                    buttonText: model['medium_rectangle_template']['button_text'] || "",
                    width: model['medium_rectangle_template']['width'] || null,
                    height: model['medium_rectangle_template']['height'] || null,
                    colorScheme: model['medium_rectangle_template']['color_scheme'] || null,
                }
            }

            if (banner.template === 'overlay_rectangle') {
                banner.overlayRectangleTemplate = {
                    id: model['overlay_rectangle_template']['id'] || null,
                    headerText: model['overlay_rectangle_template']['header_text'] || "",
                    mainText: model['overlay_rectangle_template']['main_text'] || "",
                    buttonText: model['overlay_rectangle_template']['button_text'] || "",
                    width: model['overlay_rectangle_template']['width'] || null,
                    height: model['overlay_rectangle_template']['height'] || null,
                    imageLink:  model['overlay_rectangle_template']['image_link'] || null,
                    colorScheme: model['overlay_rectangle_template']['color_scheme'] || null,
                }
            }

            if (banner.template === 'overlay_two_buttons_signature'){
                banner.overlayTwoButtonsSignatureTemplate = {
                    id: model['overlay_two_buttons_signature_template']['id'] || null,
                    textBefore: model['overlay_two_buttons_signature_template']['text_before'] || null,
                    textAfter: model['overlay_two_buttons_signature_template']['text_after'] || null,
                    textBtnPrimary: model['overlay_two_buttons_signature_template']['text_btn_primary'] || "",
                    textBtnPrimaryMinor: model['overlay_two_buttons_signature_template']['text_btn_primary_minor'] || null,
                    textBtnSecondary: model['overlay_two_buttons_signature_template']['text_btn_secondary'] || null,
                    textBtnSecondaryMinor: model['overlay_two_buttons_signature_template']['text_btn_secondary_minor'] || null,
                    targetUrlSecondary: model['overlay_two_buttons_signature_template']['target_url_secondary'] || null,
                    signatureImageUrl: model['overlay_two_buttons_signature_template']['signature_image_url'] || null,
                    textSignature: model['overlay_two_buttons_signature_template']['text_signature'] || null,
                }
            }

            if (banner.template === 'bar') {
                banner.barTemplate = {
                    id: model['bar_template']['id'] || null,
                    mainText: model['bar_template']['main_text'] || "",
                    buttonText: model['bar_template']['button_text'] || "",
                    colorScheme: model['bar_template']['color_scheme'] || null,
                }
            }

            if (banner.template === 'collapsible_bar') {
                banner.collapsibleBarTemplate = {
                    id: model['collapsible_bar_template']['id'] || null,
                    mainText: model['collapsible_bar_template']['main_text'] || "",
                    headerText: model['collapsible_bar_template']['header_text'] || "",
                    collapseText: model['collapsible_bar_template']['collapse_text'] || "",
                    expandText: model['collapsible_bar_template']['expand_text'] || "",
                    buttonText: model['collapsible_bar_template']['button_text'] || "",
                    initialState: model['collapsible_bar_template']['initial_state'] || "expanded",
                    forceInitialState: model['collapsible_bar_template']['force_initial_state'] || false,
                    colorScheme: model['collapsible_bar_template']['color_scheme'] || null,
                }
            }

            if (banner.template === 'html') {
                banner.htmlTemplate = {
                    id: model['html_template']['id'] || null,
                    backgroundColor: model['html_template']['background_color'] || null,
                    textColor: model['html_template']['text_color'] || null,
                    fontSize: model['html_template']['font_size'] || null,
                    textAlign: model['html_template']['text_align'] || null,
                    text: model['html_template']['text'] || null,
                    css: model['html_template']['css'] || null,
                    dimensions: model['html_template']['dimensions'] || null,
                }
            }

            if (banner.template === 'html_overlay') {
                banner.htmlOverlayTemplate = {
                    id: model['html_overlay_template']['id'] || null,
                    backgroundColor: model['html_overlay_template']['background_color'] || null,
                    textColor: model['html_overlay_template']['text_color'] || null,
                    fontSize: model['html_overlay_template']['font_size'] || null,
                    textAlign: model['html_overlay_template']['text_align'] || null,
                    text: model['html_overlay_template']['text'] || null,
                    css: model['html_overlay_template']['css'] || null,
                }
            }

            if (banner.template === 'short_message') {
                banner.shortMessageTemplate = {
                    id: model['short_message_template']['id'] || null,
                    text: model['short_message_template']['text'] || null,
                    colorScheme: model['short_message_template']['color_scheme'] || null,
                }
            }

            if (banner.template === 'newsletter_rectangle' ) {
                banner.newsletterRectangleTemplate = {
                    id: model['newsletter_rectangle_template']['id'] || null,
                    newsletterId: model['newsletter_rectangle_template']['newsletter_id'] || null,
                    btnSubmit: model['newsletter_rectangle_template']['btn_submit'] || null,
                    title: model['newsletter_rectangle_template']['title'] || null,
                    text: model['newsletter_rectangle_template']['text'] || null,
                    success: model['newsletter_rectangle_template']['success'] || null,
                    failure: model['newsletter_rectangle_template']['failure'] || null,
                    width: model['newsletter_rectangle_template']['width'] || null,
                    height: model['newsletter_rectangle_template']['height'] || null,
                    terms: model['newsletter_rectangle_template']['terms'] || null,
                    endpoint: model['newsletter_rectangle_template']['endpoint'] || null,
                    useXhr: model['newsletter_rectangle_template']['use_xhr'] || null,
                    requestBody: model['newsletter_rectangle_template']['request_body'] || null,
                    requestHeaders: model['newsletter_rectangle_template']['request_headers'] || null,
                    paramsTransposition: model['newsletter_rectangle_template']['params_transposition'] || null,
                    paramsExtra: model['newsletter_rectangle_template']['params_extra'] || null,
                    rempMailerAddr: model['newsletter_rectangle_template']['remp_mailer_addr'] || null,
                    colorScheme: model['newsletter_rectangle_template']['color_scheme'] || null,
                }
            }

            return banner;
        },

        parseUserData: (banner, userData) => {
            if (userData == null) {
                return banner;
            }

            if (banner.template === 'collapsible_bar') {
                if ('collapsed' in userData && !banner.collapsibleBarTemplate.forceInitialState) {
                    banner.collapsibleBarTemplate.initialState = userData.collapsed ? 'collapsed' : 'expanded';
                }
            }

            return banner;
        },

        bindPreview: function(el, banner) {
            registerStripHtmlFilter(Vue);

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
