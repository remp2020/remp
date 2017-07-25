remplib = typeof(remplib) === 'undefined' ? {} : remplib;

(function() {

    'use strict';

    remplib.banner = {

        bannerForm: null,

        fromModel: function (model) {
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

        bindPreview: function(banner, boxStyles) {
            return Vue.component('banner-preview', {
                template: '' +
'<a v-bind:href="targetUrl" v-if="show" v-bind:style="[' +
            'linkStyles,' +
            'positionOptions[position].style,' +
            'dimensionOptions[dimensions]' +
        ']">' +
    '<transition appear v-bind:name="transition">' +
        '<div class="preview-box" v-bind:style="[' +
            'boxStyles,' +
            'dimensionOptions[dimensions],' +
            'customBoxStyles' +
        ']">'+
            '<a class="preview-close" href="javascript://" v-bind:class="[{hidden: !closeable}]" v-on:click="show = false" v-bind:style="closeStyles">&#x2716;</a>' +
            '<p class="preview-text" v-bind:style="[' +
                'alignmentOptions[textAlign].style,' +
                'textStyles' +
            ']">{{ text }}</p>' +
        '</div>' +
    '</transition>'+
'</a>',
                data: function() {
                    return banner;
                },
                computed: {
                    linkStyles: function() {
                        return {
                            textDecoration: 'none',
                            position: 'absolute'
                        }
                    },
                    textStyles: function() {
                        return {
                            color: banner.textColor,
                            fontSize: banner.fontSize + "px",
                            display: 'table-cell',
                            wordBreak: 'break-all',
                            verticalAlign: 'middle',
                            padding: '5px 10px'
                        };
                    },
                    boxStyles: function() {
                        return {
                            backgroundColor: banner.backgroundColor,
                            fontFamily: 'Noto Sans, sans-serif',
                            color: 'white',
                            whiteSpace: 'pre-line',
                            display: 'table',
                            overflow: 'hidden',
                            position: 'relative'
                        }
                    },
                    closeStyles: function() {
                        return {
                            color: banner.textColor,
                            position: 'absolute',
                            top: '5px',
                            right: '10px',
                            fontSize: '15px',
                            padding: '5px'
                        }
                    },
                    customBoxStyles: function() {
                        return boxStyles;
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

})();