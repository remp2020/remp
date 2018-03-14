<style type="text/css">
    @import url('https://fonts.googleapis.com/css?family=Noto+Sans');
    @import url('../../../sass/transitions.scss');

    .short-message-preview-close {
        position: absolute;
        top: 0px;
        right: 5px;
        font-size: 16px;
        text-decoration: none;
    }

    .short-message-preview-close.hidden {
        display: none;
    }

    .short-message-preview-link {
        text-decoration: none;
        overflow: hidden;
    }

    .short-message-preview-box {
        font-family: Noto Sans, sans-serif;
        white-space: pre-line;
        overflow: hidden;
        position: relative;
        padding: 0 20px;
        text-align: center;
        display: flex;
        box-sizing: border-box;
        flex-direction: column;
        justify-content: space-around;
        align-items: center;
    }

    .short-message-main {
        font-size: 16px;
        word-wrap: break-word;
        padding: 10px;
    }
</style>

<template>
    <a v-bind:href="$parent.url" v-on:click="$parent.clicked" v-if="isVisible" class="short-message-preview-link" v-bind:style="[
        linkStyles,
        _position
    ]">
        <transition appear v-bind:name="transition">
            <div class="short-message-preview-box" v-bind:style="[boxStyles]">
                <a class="short-message-preview-close" title="Close banner" href="javascript://" v-bind:class="[{hidden: !closeable}]" v-on:click.stop="$parent.closed" v-bind:style="closeStyles">&times;</a>
                <div class="short-message-main" v-html="$parent.injectVars(text)"></div>
            </div>
        </transition>
    </a>
</template>

<script>
    export default {
        name: 'short-message-preview',
        props: [
            "positionOptions",
            "alignmentOptions",

            "backgroundColor",
            "textColor",
            "text",

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
                    position: position,
                    zIndex: zIndex,
                }
            },
            boxStyles: function() {
                return {
                    backgroundColor: this.backgroundColor,
                    color: this.textColor,
                    minWidth: '50px',
                    minHeight: '25px',
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
