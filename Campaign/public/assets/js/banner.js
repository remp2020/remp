Campaign = typeof(Campaign) === 'undefined' ? {} : Campaign;

(function($){

    'use strict';

    Campaign.banner = {

        bannerForm: null,

        fromModel: function (model) {
            return {
                name: model['name'] || null,
                dimensions: model['dimensions'] || null,
                text: model['text'] || null,
                textAlign: model['text_align'] || null,
                fontSize: model['font_size'] || null,
                targetUrl: model['target_url'] || null,
                textColor: model['text_color'] || null,
                backgroundColor: model['background_color'] || null,
                position: model['position'] || null,
                transition: model['transition'] || null
            }
        },

        bindPreview: function(banner) {
            return Vue.component('banner-preview', {
                template: '#banner-preview-template',
                data: function() {
                    return banner;
                },
                computed: {
                    textStyles: function() {
                        return {
                            color: banner.textColor,
                            fontSize: banner.fontSize + "px"
                        };
                    },
                    boxStyles: function() {
                        return {
                            backgroundColor: banner.backgroundColor
                        }
                    }
                }
            });
        },

        bindForm: function (banner) {
            // failsave, one of the options has to be selected...
            banner.dimensions = banner.dimensions || Object.keys(banner.dimensionOptions)[0];
            banner.textAlign = banner.textAlign || Object.keys(banner.alignmentOptions)[0];
            banner.position = banner.position || Object.keys(banner.positionOptions)[0];

            Vue.component('banner-form', {
                template: '#banner-form-template',
                data: function() {
                    return banner;
                },
                components: {
                    // <my-component> will only be available in parent's template
                    'banner-preview': this.bindPreview(banner)
                },
                watch: {
                    'textColor': function(val, oldVal){
                        console.log(val, oldVal);
                    },
                    'transition': function () {
                        var self = this;
                        setTimeout(function() { self.show = false }, 100);
                        setTimeout(function() { self.show = true }, 800);
                    }
                }
            });

            return new Vue({
                el: '#banner-form'
            });
        }

    }

})(jQuery);