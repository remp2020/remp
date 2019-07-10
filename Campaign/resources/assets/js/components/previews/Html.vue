<style type="text/css">
    @import url('https://fonts.googleapis.com/css?family=Noto+Sans');
    @import url('../../../sass/transitions.scss');

    .html-preview-link {
        text-decoration: none;
        overflow: hidden;
    }
    .html-preview-close.hidden {
        display: none;
    }
    .html-preview-box {
        font-family: Noto Sans, sans-serif;
        color: white;
        white-space: pre-line;
        display: block;
        overflow: hidden;
        position: relative;
        text-align: center;
    }
    .html-preview-text {
        vertical-align: middle;
        padding: 5px 10px;
        display: inline-block;
        box-sizing: border-box;
        height: 100%;
        width: 100%;
        align-items: center;
    }
    .html-preview-close {
        position: absolute;
        top: 5px;
        right: 10px;
        font-size: 15px;
        padding: 5px;
        text-decoration: none;
    }
</style>

<template>
    <a class="html-preview-link" v-bind:href="$parent.url" v-on="$parent.url ? { click: $parent.clicked } : {}" v-if="isVisible" v-bind:style="[
        linkStyles,
        _position,
        dimensionOptions[dimensions]
    ]">
        <transition appear v-bind:name="transition">
            <div class="html-preview-box" v-bind:style="[
                boxStyles,
                dimensionOptions[dimensions],
                customBoxStyles
            ]">
                <a class="html-preview-close" title="Close banner" href="javascript://" v-bind:class="[{hidden: !closeable}]" v-on:click.stop="$parent.closed" v-bind:style="closeStyles"><small>{{ closeText }}</small> &times;</a>
                <div v-html="$parent.injectVars(text)" class="html-preview-text" v-bind:style="[_textAlign, textStyles]"></div>
            </div>
        </transition>
    </a>
</template>

<script>
    export default {
        name: 'html-preview',
        props: [
            "positionOptions",
            "dimensionOptions",
            "alignmentOptions",
            "textAlign",
            "transition",
            "position",
            "offsetVertical",
            "offsetHorizontal",
            "dimensions",
            "show",
            "textColor",
            "fontSize",
            "backgroundColor",
            "targetUrl",
            "closeable",
            "closeText",
            "text",
            "css",
            "js",
            "includes",
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
        mounted: function () {
            let styles = this.css ? this.css.replace(/\r?\n|\r/gm," ") : '',
                head = document.head || document.getElementsByTagName('head')[0],
                style = document.createElement('style');

            head.appendChild(style);

            style.type = 'text/css';
            if (style.styleSheet){
                // This is required for IE8 and below.
                style.styleSheet.cssText = styles;
            } else {
                style.appendChild(document.createTextNode(styles));
            }
        },
        computed: {
            _textAlign: function() {
                return this.alignmentOptions[this.textAlign] ? this.alignmentOptions[this.textAlign].style : {};
            },
            _position: function() {
                if (!this.$parent.customPositioned()) {
                    return {};
                }

                if (this.positionOptions[this.position]) {
                    let styles = this.positionOptions[this.position].style;

                    for (let ii in styles) {
                        styles[ii] = ((ii == 'top' || ii == 'bottom') ? this.offsetVertical : this.offsetHorizontal) + 'px'
                    }

                    return styles;
                }

                return {};
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
                    textDecoration: 'none',
                    position: position,
                    overflow: 'hidden',
                    zIndex: zIndex,
                }
            },
            textStyles: function() {
                return {
                    color: this.textColor,
                    fontSize: this.fontSize + "px",
                }
            },
            boxStyles: function() {
                return {
                    backgroundColor: this.backgroundColor,
                }
            },
            closeStyles: function() {
                return {
                    color: this.textColor,
                }
            },
            customBoxStyles: function() {
                return {}
            },
            isVisible: function() {
                return this.show && this.visible;
            },
        },
    }
</script>
