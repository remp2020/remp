<style type="text/css">
    @import url('https://fonts.googleapis.com/css?family=Noto+Sans');
    @import url('../../../css/transitions.css');

    .preview-close.hidden {
        display: none;
    }
    .preview-image {
        opacity: 0.3;
    }
    .preview-box {
        position: absolute;
    }
</style>

<template>
    <a v-bind:href="url" v-on:click="clicked" v-if="isVisible" v-bind:style="[
        linkStyles,
        _position,
        dimensionOptions[dimensions]
    ]">
        <transition appear v-bind:name="transition">
            <div class="preview-box" v-bind:style="[
                boxStyles,
                dimensionOptions[dimensions],
                _textAlign,
                customBoxStyles
            ]">
                <a class="preview-close" href="javascript://" v-bind:class="[{hidden: !closeable || displayType !== 'overlay'}]" v-on:click="closed" v-bind:style="closeStyles">&#x1f5d9;</a>
                <p v-html="text" class="preview-text" v-bind:style="[_textAlign, textStyles]"></p>
            </div>
        </transition>
    </a>
</template>

<script>
    export default {
        name: 'html-template-preview',
        props: [
            "positionOptions",
            "dimensionOptions",
            "alignmentOptions",
            "textAlign",
            "transition",
            "position",
            "dimensions",
            "show",
            "textColor",
            "fontSize",
            "backgroundColor",
            "targetUrl",
            "closeable",
            "text",
            "displayType",
            "forcedPosition",
            "uuid",
            "campaignUuid"
        ],
        data: function() {
            return {
                visible: true,
                closeTracked: false,
                clickTracked: false,
            }
        },
        methods: {
            customPositioned: function() {
                if (this.displayType === 'overlay') {
                    return true;
                }
                if (this.forcedPosition !== undefined && this.forcedPosition === 'absolute') {
                    return true;
                }
                return false;
            },
            closed: function() {
                if (this.closeTracked) {
                    return true;
                }
                this.trackEvent("banner", "close", {
                    "banner_id": this.uuid,
                    "campaign_id": this.campaignUuid,
                });
                this.closeTracked = true;
                this.visible = false;
            },
            clicked: function() {
                if (this.clickTracked) {
                    return true;
                }
                this.trackEvent("banner", "click", {
                    "banner_id": this.uuid,
                    "campaign_id": this.campaignUuid,
                });
                this.clickTracked = true;
                return true;
            },
            trackEvent: function(category, action, fields) {
                if (typeof remplib.tracker === 'undefined') {
                    return;
                }
                remplib.tracker.trackEvent(category, action, fields);
            },
        },
        computed: {
            _textAlign: function() {
                return this.alignmentOptions[this.textAlign] ? this.alignmentOptions[this.textAlign].style : {};
            },
            _position: function() {
                if (!this.customPositioned()) {
                    return {};
                }
                return this.positionOptions[this.position] ? this.positionOptions[this.position].style : {};
            },
            linkStyles: function() {
                let position = this.displayType === 'overlay' ? 'absolute' : 'relative';
                if (typeof this.forcedPosition !== 'undefined') {
                    position = this.forcedPosition;
                }
                return {
                    textDecoration: 'none',
                    position: position,
                    overflow: 'hidden',
                    zIndex: 0,
                }},
            textStyles: function() {
                return {
                    color: this.textColor,
                    fontSize: this.fontSize + "px",
                    wordBreak: 'break-all',
                    verticalAlign: 'middle',
                    padding: '5px 10px',
                    display: 'flex',
                    height: '100%',
                    alignItems: 'center',
                }
            },
            boxStyles: function() {
                return {
                    backgroundColor: this.backgroundColor,
                    fontFamily: 'Noto Sans, sans-serif',
                    color: 'white',
                    whiteSpace: 'pre-line',
                    display: 'inline-block',
                    overflow: 'hidden',
                    position: 'relative'
                }},
            closeStyles: function() {
                return {
                    color: this.textColor,
                    position: 'absolute',
                    top: '5px',
                    right: '10px',
                    fontSize: '15px',
                    padding: '5px',
                    textDecoration: 'none',
                }},
            customBoxStyles: function() {
                return {}
            },
            isVisible: function() {
                return this.show && this.visible;
            },
            url: function() {
                if (this.targetUrl === null) {
                    return null;
                }
                let separator = this.targetUrl.indexOf("?") === -1 ? "?" : "&";
                let url =  this.targetUrl + separator + "utm_source=remp_campaign" +
                    "&utm_medium=" + encodeURIComponent(this.displayType);
                if (this.campaignUuid) {
                    url += "&utm_campaign=" + encodeURIComponent(this.campaignUuid);
                }
                if (this.uuid) {
                    url += "&utm_content=" + encodeURIComponent(this.uuid);
                }
                return url;
            },
        },
    }
</script>