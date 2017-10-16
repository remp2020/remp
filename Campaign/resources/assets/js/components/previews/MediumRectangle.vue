<style type="text/css">
    @import url('https://fonts.googleapis.com/css?family=Noto+Sans');
    @import url('../../../css/transitions.css');

    .medium-rectangle-preview-close {
        position: absolute;
        top: 5px;
        right: 10px;
        font-size: 15px;
        padding: 5px;
        text-decoration: none;
    }

    .medium-rectangle-preview-close.hidden {
        display: none;
    }

    .medium-rectangle-preview-link {
        text-decoration: none;
        overflow: hidden;
        z-index: 9999;
    }

    .medium-rectangle-preview-box {
        font-family: Noto Sans, sans-serif;
        white-space: pre-line;
        overflow: hidden;
        position: relative;
        padding: 0 20px;
        text-align: center;
        display: flex;
        flex-direction: column;
        justify-content: space-around;
        align-items: center;
    }

    .medium-rectangle-header {
        word-wrap: break-word;
    }

    .medium-rectangle-main {
        font-size: 26px;
        word-wrap: break-word;
    }

    .medium-rectangle-button {
        width: 70%;
        border-radius: 15px;
        padding: 5px;
        word-wrap: break-word;
        font-size: 16px;
    }
</style>

<template>
    <a v-bind:href="url" v-on:click="clicked" v-if="isVisible" class="medium-rectangle-preview-link" v-bind:style="[
        linkStyles,
        _position
    ]">
        <transition appear v-bind:name="transition">
            <div class="medium-rectangle-preview-box" v-bind:style="[boxStyles]">
                <a class="medium-rectangle-preview-close" href="javascript://" v-bind:class="[{hidden: !closeable || displayType !== 'overlay'}]" v-on:click="closed" v-bind:style="closeStyles">&#x1f5d9;</a>
                <div v-if="headerText.length > 0" class="medium-rectangle-header" v-html="headerText"></div>
                <div class="medium-rectangle-main" v-html="mainText"></div>
                <div class="medium-rectangle-button" v-if="buttonText.length > 0" v-html="buttonText" v-bind:style="[buttonStyles]"></div>
            </div>
        </transition>
    </a>
</template>

<script>
    export default {
        name: 'medium-rectangle-preview',
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
            "width",
            "height",

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
                return this.positionOptions[this.position] ? this.positionOptions[this.position].style : {};
            },
            linkStyles: function() {
                let position = this.displayType === 'overlay' ? 'fixed' : 'relative';
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
                    minWidth: this.width || '100px',
                    maxWidth: this.width || '370px',
                    minHeight: this.height || '250px',
                    maxHeight: this.height || '370px',
                }},
            buttonStyles: function() {
                return {
                    color: this.buttonTextColor,
                    backgroundColor: this.buttonBackgroundColor,
                }
            },
            closeStyles: function() {
                return {
                    color: this.textColor,
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