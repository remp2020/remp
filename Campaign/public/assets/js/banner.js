Campaign = typeof(Campaign) === 'undefined' ? {} : Campaign;

(function($){

    'use strict';

    Campaign.banner = {

        data: {
            name: null,
            text: null,
            position: null,
            dimensions: null,
            targetUrl: null,
            textColor: null,
            backgroundColor: null,
            textAlignment: null,
            fontSize: null
        },

        $form: null,

        positions: [],

        dimensions: [],

        alignments: [],

        init: function ($form, positions, dimensions, alignments) {
            this.$form = $form;

            this.positions = positions;
            this.dimensions = dimensions;
            this.alignments = alignments;

            this.$form.my({
                data: this.data,
                ui:{
                    '[name="name"]': {
                        bind: "name"
                    },
                    '[name="dimensions"]': {
                        bind: "dimensions"
                    },
                    '[name="text"]': {
                        bind: "text"
                    },
                    '[name="position"]': {
                        bind: "position"
                    },
                    '[name="target_url"]': {
                        bind: "targetUrl"
                    },
                    '[name="text_color"]': {
                        bind: "textColor",
                        events: ["change.my", "farbtastic.change.my"]
                    },
                    '[name="background_color"]': {
                        bind: "backgroundColor",
                        events: ["change.my", "farbtastic.change.my"]
                    },
                    '[name="text_align"]': {
                        bind: "textAlignment"
                    },
                    '[name="font_size"]': {
                        bind: "fontSize"
                    }
                }
            }, this.data).on("change.my", Campaign.banner.preview.debounce(5)).my("redraw")
        },

        preview: function () {
            Campaign.banner.applyText();
            Campaign.banner.applyPosition();
            Campaign.banner.applySize();
            Campaign.banner.applyColor();
            Campaign.banner.applyBackgroundColor();
            Campaign.banner.applyAlignment();
            Campaign.banner.applyFontSize();
        },

        applyText: function () {
            this.$form.find('.preview-text').html(Campaign.banner.data.text);
        },

        applyPosition: function () {
            var $box = Campaign.banner.$form.find('.preview-box');
            if (!(Campaign.banner.data.position in Campaign.banner.positions)) {
                console.error("selected position [" + Campaign.banner.data.size + "] was not initialized");
                return;
            }

            $box
                .removeAttr("style")
                .css(Campaign.banner.positions[Campaign.banner.data.position].style);
        },

        applySize: function () {
            var $box = Campaign.banner.$form.find('.preview-box');
            if (!(Campaign.banner.data.dimensions in Campaign.banner.dimensions)) {
                console.error("selected dimensions [" + Campaign.banner.data.dimensions + "] was not initialized");
                return;
            }
            var dimensions = Campaign.banner.dimensions[Campaign.banner.data.dimensions];
            $box.css({
                width: dimensions.width,
                height: dimensions.height
            });
        },

        applyColor: function () {
            this.$form.find('.preview-text').css({color: this.data.textColor});
        },

        applyBackgroundColor: function () {
            this.$form.find('.preview-box').css({backgroundColor: this.data.backgroundColor});
        },

        applyAlignment: function () {
            var $box = Campaign.banner.$form.find('.preview-box');
            if (!(Campaign.banner.data.textAlignment in Campaign.banner.alignments)) {
                console.error("selected alignment [" + Campaign.banner.data.textAlignment + "] was not initialized");
                return;
            }
            var alignment = Campaign.banner.alignments[Campaign.banner.data.textAlignment];
            $box.css(alignment.style);
        },

        applyFontSize: function () {
            this.$form.find('.preview-text').css({fontSize: this.data.fontSize + "px"});
        }

    }

})(jQuery);