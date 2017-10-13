<style type="text/css">
    @import url('https://fonts.googleapis.com/css?family=Noto+Sans');
    @import url('../../../css/transitions.css');

    .bar-preview-close {
        position: absolute;
        top: 5px;
        right: 10px;
        font-size: 15px;
        padding: 5px;
        text-decoration: none;
    }

    .bar-preview-close.hidden {
        display: none;
    }

    .bar-preview-link {
        text-decoration: none;
        overflow: hidden;
        z-index: 0;
        width: 100%;
    }

    .bar-preview-box {
        font-family: Noto Sans, sans-serif;
        white-space: pre-line;
        display: flex;
        overflow: hidden;
        position: relative;
        padding: 0 40px;
        width: 100%;
        height: 68px;
        justify-content: space-between;
        align-items: center;
    }

    .bar-main {
        font-size: 17px;
        word-wrap: break-word;
    }

    .bar-button {
        border-radius: 15px;
        padding: 7px 30px;
        word-wrap: break-word;
        font-size: 14px;
    }
</style>

<template>
    <a v-bind:href="url" v-on:click="clicked" v-if="isVisible" class="bar-preview-link" v-bind:style="[
        linkStyles,
        _position
    ]">
        <transition appear v-bind:name="transition">
            <div class="bar-preview-box" v-bind:style="[boxStyles]">
                <a class="bar-preview-close" href="javascript://" v-bind:class="[{hidden: !closeable || displayType !== 'overlay'}]" v-on:click="closed" v-bind:style="closeStyles">&#x1f5d9;</a>
                <div class="bar-main" v-html="mainText"></div>
                <div class="bar-button" v-if="buttonText.length > 0" v-html="buttonText" v-bind:style="[buttonStyles]"></div>
            </div>
        </transition>
    </a>
</template>

<script>
    export default {
        name: 'bar-preview',
        props: [
            "positionOptions",
            "alignmentOptions",

            "backgroundColor",
            "buttonBackgroundColor",
            "textColor",
            "buttonTextColor",
            "headerText",
            "mainText",
            "buttonText",

            "show",
            "transition",
            "position",
            "targetUrl",
            "closeable",
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
                    "utm_source": "remp_campaign",
                    "utm_medium": this.displayType,
                    "utm_campaign": this.campaignUuid,
                    "utm_content": this.uuid
                });
                this.closeTracked = true;
                this.visible = false;
            },
            clicked: function() {
                if (this.clickTracked) {
                    return true;
                }
                this.trackEvent("banner", "click", {
                    "utm_source": "remp_campaign",
                    "utm_medium": this.displayType,
                    "utm_campaign": this.campaignUuid,
                    "utm_content": this.uuid
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
            _position: function() {
                if (!this.customPositioned()) {
                    return {};
                }
                if (!this.positionOptions[this.position]) {
                    return {};
                }
                let styles = this.positionOptions[this.position].style;
                // if there's custom offset set, we want to remove it for bar so it's either on the top or bottom of page without any paddings
                for (let style in styles) {
                    if (!styles.hasOwnProperty(style)) {
                        continue;
                    }
                    styles[style] = 0;
                }
                return styles;
            },
            linkStyles: function() {
                let position = this.displayType === 'overlay' ? 'absolute' : 'relative';
                if (typeof this.forcedPosition !== 'undefined') {
                    position = this.forcedPosition;
                }
                return {
                    position: position,
                }},
            boxStyles: function() {
                return {
                    backgroundColor: this.backgroundColor,
                    color: this.textColor,
                }},
            buttonStyles: function() {
                return {
                    color: this.buttonTextColor,
                    backgroundColor: this.buttonBackgroundColor,
                }
            },
            closeStyles: function() {
                return {
                    color: 'white',

                }},
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