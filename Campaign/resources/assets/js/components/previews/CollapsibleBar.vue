<style type="text/css">
    @import url('https://fonts.googleapis.com/css?family=Noto+Sans');
    @import url('../../../sass/transitions.scss');

    .collapsible-bar-preview-close {
        position: absolute;
        top: -5px;
        right: 0;
        font-size: 15px;
        padding: 5px;
        text-decoration: none;
    }

    .collapsible-bar-preview-close.hidden {
        display: none;
    }

    .collapsible-bar-preview-link {
        text-decoration: none;
        overflow: hidden;
        width: 100%;
    }

    .collapsible-bar-preview-box {
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

    .collapsible-bar-main {
        font-size: 17px;
        word-wrap: break-word;
        padding-right: 5px;
    }

    .collapsible-bar-button {
        border-radius: 15px;
        padding: 7px 30px;
        word-wrap: break-word;
        font-size: 14px;
        text-align: center;
        cursor: pointer;
    }

    @media (max-width: 640px) {
        .collapsible-bar-preview-box {
            flex-direction: column;
            padding: 9px;
        }
        .collapsible-bar-main {
            text-align: center;
            margin-bottom: 9px;
        }
        .collapsible-bar-button {
            padding: 5px 15px;
        }
    }
</style>

<template>
    <a v-bind:href="$parent.url" v-on="$parent.url ? { click: $parent.clicked } : {}" v-if="isVisible" class="collapsible-bar-preview-link" v-bind:style="[
        linkStyles,
        _position
    ]">
        <transition appear v-bind:name="transition">
            <div class="collapsible-bar-preview-box" v-bind:style="[boxStyles]">
                <a class="collapsible-bar-preview-close" title="Close banner" href="javascript://" v-bind:class="[{hidden: !closeable}]" v-on:click.stop="$parent.closed" v-bind:style="closeStyles">&times;</a>
                <div class="collapsible-bar-main" v-html="$parent.injectVars(mainText)"></div>
                <div class="collapsible-bar-button" v-if="buttonText.length > 0" v-on:click="$parent.clicked($event, !$parent.url)" v-html="$parent.injectVars(buttonText)" v-bind:style="[buttonStyles]"></div>
            </div>
        </transition>
    </a>
</template>

<script>
    export default {
        name: 'collapsible-bar-preview',
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
            "offsetVertical",
            "offsetHorizontal",
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
        computed: {
            _position: function() {
                if (!this.$parent.customPositioned()) {
                    return {};
                }

                if (this.positionOptions[this.position]) {
                    let styles = this.positionOptions[this.position].style;

                    for (let pos in styles) {
                        if (!styles.hasOwnProperty(pos)) {
                            continue;
                        }
                        styles[pos] = ((pos === 'top' || pos === 'bottom') ? this.offsetVertical : this.offsetHorizontal) + 'px'
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
