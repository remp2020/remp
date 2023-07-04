<style type="text/css">
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
    display: block;
    text-decoration: none;
    overflow: hidden;
    width: 100%;
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
    background-color: #fff;
    font-size: 14px;
    color: #5e5e5e;
    min-height: 31px;
}

.collapsible-bar-toggle {
    position: absolute;
    top: 7px;
    right: 0;
    text-transform: uppercase;
    color: #000;
    padding-right: 15px;
    cursor: pointer;
    font-size: 12px;
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
         v-bind:style="[containerStyles]">
        <div class="collapsible-bar-header sans-serif">
            {{ headerText }}

            <div class="collapsible-bar-toggle">
                <div v-if="collapsed" @click="handleCollapse()" class="collapsible-bar-toggle-expand">
                    {{ expandText }}
                    <span>&#9650;</span>
                </div>

                <div v-if="!collapsed" @click="handleCollapse()" class="collapsible-bar-toggle-collapse">
                    {{ collapseText }}
                    <span>&#9660;</span>
                </div>
            </div>
        </div>

        <slide-up-down :active="!collapsed" :duration="600">
            <div id="collapsible-bar-body" class="collapsible-bar-body">
                <a v-bind:href="$parent.url"
                   v-on="$parent.url ? { click: $parent.clicked } : {}"
                   id="collapsible-bar-preview-link"
                   class="collapsible-bar-preview-link">

                    <transition appear name="slide">
                        <div class="collapsible-bar-preview-box sans-serif" v-bind:style="[ boxStyles ]">

                            <div class="collapsible-bar-main"
                                 v-html="$parent.injectSnippets(mainText)"></div>

                            <div class="collapsible-bar-button"
                                 v-if="buttonText.length > 0"
                                 v-on:click="$parent.clicked($event, !$parent.url)"
                                 v-html="$parent.injectSnippets(buttonText)"
                                 v-bind:style="[buttonStyles]"></div>

                        </div>
                    </transition>
                </a>
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
        "backgroundColor",
        "buttonBackgroundColor",
        "textColor",
        "buttonTextColor",
        "headerText",
        "mainText",
        "headerText",
        "collapseText",
        "expandText",
        "buttonText",
        "displayType",

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
        }
    }
}
</script>
