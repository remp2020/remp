<style type="text/css" scoped>
@import url('../../../sass/transitions.scss');

.collapsible-bar-wrap {
    bottom: 0;
    left: 0;
    width: 100%;
    overflow: hidden;
}

#banner-preview .collapsible-bar-wrap {
    position: absolute !important;
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
    overflow: hidden;
    cursor: pointer;
}

.collapsible-bar-preview-box {
    white-space: pre-line;
    display: flex;
    overflow: hidden;
    position: relative;
    padding-top: 10px;
    padding-bottom: 10px;
    padding-bottom: calc(10px + constant(safe-area-inset-bottom));
    padding-bottom: calc(10px + env(safe-area-inset-bottom));
    padding-left: 18px;
    padding-right: 18px;
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
    text-decoration: none;
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
    position: relative;
    min-height: 31px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #fff;
    font-size: 14px;
    color: #5e5e5e;
}

.collapsible-bar-title {
    margin-bottom: 3px;
}

.collapsible-bar-toggle {
    position: absolute;
    height: 100%;
    top: 0;
    right: 0;
    text-transform: uppercase;
    color: #000;
    padding-right: 15px;
    cursor: pointer;
    font-size: 12px;
    display: flex;
    flex-direction: row;
    align-items: stretch;
}

.collapsible-bar-toggle-button {
    display: flex;
    gap: 4px;
    align-items: center;
}

.collapsible-bar-body {
    display: block;
    height: auto;
    -webkit-transition: height 0.7s;
    -moz-transition: height 0.7s;
    -ms-transition: height 0.7s;
    -o-transition: height 0.7s;
    transition: height 0.7s;
}

@media (max-width: 640px) {
    .collapsible-bar-preview-box {
        flex-direction: column;
        padding-left: 9px;
        padding-right: 9px;
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
    <div class="collapsible-bar-wrap"
         role="alert"
         v-bind:style="[containerStyles]">
        <div class="collapsible-bar-header sans-serif">
            <span class="collapsible-bar-title">
                {{ headerText }}
            </span>

            <div class="collapsible-bar-toggle">
                <div role="button"
                     tabindex="0"
                     v-on:click="handleCollapse()"
                     v-on:keydown.enter.space="handleCollapse()"
                     v-bind:class="{'collapsible-bar-toggle-button': true, 'collapsible-bar-toggle-expand': collapsed, 'collapsible-bar-toggle-collapse': !collapsed}"
                     v-bind:aria-expanded="collapsed ? 'false' : 'true'"
                     v-bind:aria-label="collapsed ? expandText : collapseText"
                >
                    {{ collapsed ? expandText : collapseText }}
                    <span>{{ collapsed ? '&#9650;' : '&#9660;'}}</span>
                </div>
            </div>
        </div>

        <slide-up-down :active="!collapsed" :duration="600">
            <div id="collapsible-bar-body" class="collapsible-bar-body" v-bind:aria-hidden="collapsed ? 'true' : 'false'">
                <div v-on:click="click" id="collapsible-bar-preview-link" class="collapsible-bar-preview-link">
                    <transition appear name="slide">
                        <div class="collapsible-bar-preview-box sans-serif" v-bind:style="[ boxStyles ]">

                            <a class="collapsible-bar-main"
                                ref="mainLink"
                                v-bind:href="$parent.url"
                                v-bind:style="[mainTextStyles]"
                                v-bind:aria-label="$parent.injectSnippets(mainText) + (buttonText.length > 0 ? ', ' + $parent.injectSnippets(buttonText) : '') | strip_html"
                                v-on:click.stop="$parent.clicked($event, !$parent.url)"
                                v-on:keydown.enter.space="$parent.clicked($event, !$parent.url)"
                                v-html="$parent.injectSnippets(mainText)"
                            ></a>

                            <button class="collapsible-bar-button" aria-hidden="true"
                                v-if="buttonText.length > 0"
                                v-on:click.stop="click"
                                v-on:keydown.enter.space="click"
                                v-html="$parent.injectSnippets(buttonText)"
                                v-bind:style="[buttonStyles]"
                            ></button>
                        </div>
                    </transition>
                </div>
            </div>
        </slide-up-down>


    </div>
</template>

<script>
import SlideUpDown from 'vue-slide-up-down'

export default {
    name: 'collapsible-bar-preview',
    components: {
        SlideUpDown
    },
    props: [
        "headerText",
        "mainText",
        "collapseText",
        "expandText",
        "buttonText",
        "displayType",
        "colorScheme",

        "show",
        "transition",
        "targetUrl",
        "uuid",
        "campaignUuid",
        "initialState",
    ],
    data: function () {
        return {
            bannerHeight: 0,
            collapsed: true,
            closeTracked: false,
            clickTracked: false,
        }
    },
    mounted() {
        if (this.initialState === 'expanded') {
            this.collapsed = false;
        } else {
            this.collapsed = true;
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
                backgroundColor: this.colorScheme.backgroundColor,
                color: this.colorScheme.textColor,
            }
        },
        mainTextStyles: function () {
            return {
                color: this.colorScheme.textColor,
            }
        },
        buttonStyles() {
            return {
                color: this.colorScheme.buttonTextColor,
                backgroundColor: this.colorScheme.buttonBackgroundColor,
            }
        },
        closeStyles() {
            return {
                color: this.colorScheme.textColor,
            }
        },
        containerStyles: function () {
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
        }
    },
    methods: {
        handleCollapse: function () {
            this.collapsed = !this.collapsed;
            this.$parent.collapsed(this.collapsed)
        },
        click: function () {
            this.$refs.mainLink.click();
        },
    }
}
</script>
