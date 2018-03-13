<style type="text/css">
    @import url('https://fonts.googleapis.com/css?family=Noto+Sans');
    @import url('../../../sass/transitions.scss');

    .bar-preview-close {
        position: absolute;
        top: -5px;
        right: 0;
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
        width: 100%;
    }

    .bar-preview-box {
        font-family: Noto Sans, sans-serif;
        white-space: pre-line;
        display: flex;
        overflow: hidden;
        position: relative;
        padding: 0 18px;
        width: 100%;
        min-height: 68px;
        justify-content: space-between;
        align-items: center;
        box-sizing: border-box;
    }

    .bar-main {
        font-size: 17px;
        word-wrap: break-word;
        padding-right: 5px;
    }

    .bar-button {
        border-radius: 15px;
        padding: 7px 30px;
        word-wrap: break-word;
        font-size: 14px;
        text-align: center;
    }

    @media (max-width: 640px) {
        .bar-preview-box {
            flex-direction: column;
            padding: 9px;
        }
        .bar-main {
            text-align: center;
            margin-bottom: 9px;
        }
        .bar-button {
            padding: 5px 15px;
        }
    }
</style>

<template>
    <a v-bind:href="$parent.url" v-on:click="$parent.clicked" v-if="isVisible" class="bar-preview-link" v-bind:style="[
        linkStyles,
        _position
    ]">
        <transition appear v-bind:name="transition">
            <div class="bar-preview-box" v-bind:style="[boxStyles]">
                <a class="bar-preview-close" title="Close banner" href="javascript://" v-bind:class="[{hidden: !closeable}]" v-on:click.stop="$parent.closed" v-bind:style="closeStyles">&times;</a>
                <div class="bar-main" v-html="$parent.injectVars(mainText)"></div>
                <div class="bar-button" v-if="buttonText.length > 0" v-html="$parent.injectVars(buttonText)" v-bind:style="[buttonStyles]"></div>
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
                let position, zIndex;
                if (this.displayType === 'overlay') {
                    position = 'fixed';
                    zIndex = 9999;
                } else {
                    position = 'relative'
                }
                if (typeof this.forcedPosition !== 'undefined') {
                    position = this.forcedPosition;
                }
                return {
                    position: position,
                    zIndex: zIndex,
                }
            },
            boxStyles: function() {
                return {
                    backgroundColor: this.backgroundColor,
                    color: this.textColor,
                }
            },
            buttonStyles: function() {
                return {
                    color: this.buttonTextColor,
                    backgroundColor: this.buttonBackgroundColor,
                }
            },
            closeStyles: function() {
                return {
					color: this.textColor,
                }
            },
            isVisible: function() {
                return this.show && this.visible;
            },
        },
    }
</script>
