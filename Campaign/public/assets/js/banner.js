Campaign = typeof(Campaign) === 'undefined' ? {} : Campaign;

(function($){

    'use strict';

    Campaign.banner = {

        positions: [],

        dimensions: [],

        alignments: [],

        bannerForm: null,

        init: function (positions, dimensions, alignments) {
            this.positions = positions;
            this.dimensions = dimensions;
            this.alignments = alignments;
        },

        bindForm: function (bannerData) {
            Vue.component('banner-form', {
                template: '#banner-form-template',
                data: function() {
                    return bannerData;
                },
                computed: {
                    textStyles: function() {
                        return {
                            color: bannerData.textColor,
                            fontSize: bannerData.fontSize + "px"
                        };
                    },
                    boxStyles: function() {
                        return {
                            backgroundColor: bannerData.backgroundColor
                        }
                    }
                },
                watch: {
                    'textColor': function(val, oldVal){
                        console.log(val, oldVal);
                    }
                }
            });

            this.bannerForm = new Vue({
                el: '#banner-form'
            });
        }

    }

})(jQuery);