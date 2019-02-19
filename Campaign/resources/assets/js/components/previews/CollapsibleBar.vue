<style type="text/css">
    @import url('https://fonts.googleapis.com/css?family=Noto+Sans');
    @import url('../../../sass/transitions.scss');

    .collapsible-bar-wrap {
        position: fixed;
        bottom: 0;
        left: 0;
        width: 100%;
    }

    #banner-preview .collapsible-bar-wrap {
        position: absolute;
    }

    .collapsible-bar-preview-close {
        position: absolute;
        top: -5px;
        right: 0;
        font-size: 15px;
        padding: 5px;
        text-decoration: none;
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

    .collapsible-bar-header {
        text-align: center;
        padding: 5px 0;
        border-top: 1px solid #111;
        background-color: #fff;
    }

    .collapsible-bar-toggle {
        text-transform: uppercase;
        color: #000;
        float: right;
        padding-right: 10px;
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
    <div class="collapsible-bar-wrap">
        <div class="collapsible-bar-header">
            {{ collapseText }}

            <div class="collapsible-bar-toggle" @click="toggle()">
                Collapse
                <span>&#9660;</span>
            </div>
        </div>

        <a v-bind:href="$parent.url"
           v-on="$parent.url ? { click: $parent.clicked } : {}"
           class="collapsible-bar-preview-link">

            <transition appear v-bind:name="transition">
                <div class="collapsible-bar-preview-box" v-bind:style="[ boxStyles ]">

                    <div class="collapsible-bar-main"
                         v-html="$parent.injectVars(mainText)"></div>

                    <div class="collapsible-bar-button"
                         v-if="buttonText.length > 0"
                         v-on:click="$parent.clicked($event, !$parent.url)"
                         v-html="$parent.injectVars(buttonText)"
                         v-bind:style="[buttonStyles]"></div>

                </div>
            </transition>
        </a>
    </div>
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
            "collapseText",
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
                closeTracked: false,
                clickTracked: false,
            }
        },
        computed: {
            linkStyles() {
                let zIndex;

                if (this.displayType === 'overlay') {
                    zIndex = 9999;
                }

                return {
                    zIndex: zIndex
                 }
            },
            boxStyles() {
                return {
                    backgroundColor: this.backgroundColor,
                    color: this.textColor,
                }
            },
            buttonStyles() {
                return {
                    color: this.buttonTextColor,
                    backgroundColor: this.buttonBackgroundColor,
                }
            },
            closeStyles() {
                return {
					color: this.textColor,
                }
            }
        },
        methods: {
            toggle() {
                $('.collapsible-bar-preview-box').slideToggle();
                console.log('toggle')
            }
        }
    }
</script>
