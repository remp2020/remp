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
        display: inline-block;
        overflow: hidden;
        position: relative
    }
    .html-preview-text {
        word-break: break-all;
        vertical-align: middle;
        padding: 5px 10px;
        display: flex;
        box-sizing: border-box;
        height: 100%;
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
    <a class="html-preview-link" v-bind:href="$parent.url" v-on:click="$parent.clicked" v-if="isVisible" v-bind:style="[
        linkStyles,
        _position,
        dimensionOptions[dimensions]
    ]">
        <transition appear v-bind:name="transition">
            <div class="html-preview-box" v-bind:style="[
                boxStyles,
                dimensionOptions[dimensions],
                _textAlign,
                customBoxStyles
            ]">
                <a class="html-preview-close" title="Close banner" href="javascript://" v-bind:class="[{hidden: !closeable}]" v-on:click.stop="$parent.closed" v-bind:style="closeStyles">&times;</a>
                <p v-html="$parent.injectVars(text)" class="html-preview-text" v-bind:style="[_textAlign, textStyles]"></p>
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
