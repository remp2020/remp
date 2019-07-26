<style type="text/css">
    @import url('https://fonts.googleapis.com/css?family=Noto+Sans');
    @import url('../../../sass/transitions.scss');

    .medium-rectangle-preview-close {
        position: absolute;
        top: 5px;
        right: 10px;
        font-size: 16px;
        padding: 5px;
        text-decoration: none;
    }

    .medium-rectangle-preview-close > span {
        position: relative;
        top: -2px;
        font-size: 12px;
    }

    .medium-rectangle-preview-close.hidden {
        display: none;
    }

    .medium-rectangle-preview-link {
        text-decoration: none;
        overflow: hidden;
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
        box-sizing: border-box;
    }

    .medium-rectangle-header {
        word-wrap: break-word;
        height: 1em;
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
        cursor: pointer;
    }
</style>

<template>
    <a v-bind:href="$parent.url" v-on="$parent.url ? { click: $parent.clicked } : {}" v-if="isVisible" class="medium-rectangle-preview-link" v-bind:style="[
        linkStyles,
        _position
    ]">
        <transition appear v-bind:name="transition">
            <div class="medium-rectangle-preview-box" v-bind:style="[boxStyles]">
                <a class="medium-rectangle-preview-close" title="Close banner" href="javascript://" v-bind:class="[{hidden: !closeable}]" v-on:click.stop="$parent.closed" v-bind:style="[closeStyles]"><span>{{ closeText }}</span>&nbsp;&times;</a>
                <div class="medium-rectangle-header" v-html="$parent.injectVars(headerText)"></div>
                <div class="medium-rectangle-main" v-html="$parent.injectVars(mainText)"></div>
                <div class="medium-rectangle-button" v-if="buttonText.length > 0" v-on:click="$parent.clicked($event, !$parent.url)" v-html="$parent.injectVars(buttonText)" v-bind:style="[buttonStyles]"></div>
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
            "offsetVertical",
            "offsetHorizontal",
            "targetUrl",
            "closeable",
            "closeText",
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
        computed: {
            _position: function() {
                if (!this.$parent.customPositioned()) {
                    return {};
                }

                if (this.positionOptions[this.position]) {
                    var styles = this.positionOptions[this.position].style;

                    for (var ii in styles) {
                        styles[ii] = ((ii == 'top' || ii == 'bottom') ? this.offsetVertical : this.offsetHorizontal) + 'px'
                    }

                    return styles;
                }

                return {};
            },
            _headerText: function() {
                if (headerText !== null && headerText.length > 0) {
                    return headerText;
                }
                return '';
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
                    minWidth: this.width || '100px',
                    maxWidth: this.width || '370px',
                    minHeight: this.height || '250px',
                    maxHeight: this.height || '370px',
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
