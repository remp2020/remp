<style type="text/css">
    @import url('https://fonts.googleapis.com/css?family=Noto+Sans');
    @import url('../../../css/transitions.css');

    .preview-close.hidden {
        display: none;
    }

    .preview-image {
        opacity: 0.3;
        max-width: 100%;
        height: auto;
    }

    .medium-rectangle-preview-link {
        text-decoration: none;
        overflow: hidden;
        z-index: 0;
    }

    .medium-rectangle-preview-box {
        font-family: Noto Sans, sans-serif;
        white-space: pre-line;
        display: inline-block;
        overflow: hidden;
        position: relative;
        padding: 0 20px;
        min-width: 100px;
        max-width: 370px;
        min-height: 250px;
        max-height: 370px;
        text-align: center;
    }

    .medium-rectangle-header {
        margin-top: 35px;
        margin-bottom:-20px;
        word-wrap: break-word;
    }

    .medium-rectangle-main {
        font-size: 26px;
        margin-top: 45px;
        word-wrap: break-word;
    }

    .medium-rectangle-button {
        background-color: rgb(28, 23, 51);
        position: absolute;
        bottom: 20px;
        left: 15%;
        width: 70%;
        border-radius: 15px;
        padding: 2px;
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
                <div class="medium-rectangle-header" v-html="headerText"></div>
                <div class="medium-rectangle-main" v-html="mainText"></div>
                <div class="medium-rectangle-button" v-if="buttonText.length > 0" v-html="buttonText" v-bind:style="[buttonStyles]"></div>
            </div>
        </transition>
    </a>
</template>

<script>
    export default {
        name: 'medium-rectangle-template-preview',
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
                    position: 'absolute',
                    top: '5px',
                    right: '10px',
                    fontSize: '15px',
                    padding: '5px',
                    textDecoration: 'none',
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