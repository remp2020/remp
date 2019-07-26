<style type="text/css">
    @import url('https://fonts.googleapis.com/css?family=Noto+Sans');
    @import url('../../../sass/transitions.scss');

    .overlay-rectangle-preview-close {
        position: absolute;
        top: 0;
        right: 5px;
        font-size: 14px;
        line-height: 18px;
        text-decoration: none;
        color: #000;
        padding-top: 3px;
    }

    .preview-admin-close {
        position: fixed;
        top: 14px;
        right: 30px;
        font-size: 14px;
        line-height: 18px;
        text-decoration: none;
        color: #ff2e00;
        background-color: #fff;
        padding: 2px;
        z-index: 100000;
    }

    .overlay-rectangle-preview-close.hidden {
        display: none;
    }

    .overlay-rectangle-image {
        width: 100%;
        min-width: 300px;
        background-position: center center;
        background-size: cover;
        overflow: hidden;
        max-height: 400px;
    }

    .overlay-rectangle-image > img {
        position: relative;
        display: block;
        max-width: 100%;
    }
    
    .overlay-rectangle-overlay {
        position: fixed;
        font-family: Noto Sans, sans-serif;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.6);

        display: -webkit-box;
        display: -moz-box;
        display: -ms-flexbox;
        display: -webkit-flex;
        display: flex;
        align-items: center;
        justify-content: center;

        z-index: 9999;
    }

    #banner-preview .overlay-rectangle-overlay {
        position: absolute;
    }

    .overlay-rectangle-preview-link {
        position: relative;
        display: block;
        text-decoration: none;
        overflow: hidden;
    }

    .overlay-rectangle-content {
        position: relative;
        width: 100%;
        padding: 20px;
        text-align: center;
        justify-content: space-around;
        display: flex;
        flex-direction: column;
        align-items: center;
        box-sizing: border-box;
    }

    .overlay-rectangle-preview-box {
        white-space: pre-line;
        overflow: hidden;
        position: relative;
        text-align: center;
        display: flex;
        flex-direction: column;
        flex-wrap: nowrap;
        justify-content: flex-start;
        align-items: center;
        box-sizing: border-box;
    }

    .overlay-rectangle-header {
        word-wrap: break-word;
        height: 1em;
        padding: 0;
        margin-bottom: 10px;
    }

    .overlay-rectangle-main {
        font-size: 26px;
        word-wrap: break-word;
    }

    .overlay-rectangle-button {
        width: 70%;
        border-radius: 15px;
        padding: 5px;
        word-wrap: break-word;
        font-size: 16px;
        cursor: pointer;
        margin-top: 10px;
    }

    .overlay-rectangle-wrap {
        position: relative;
        padding: 5px;
        background: #fff;
        -webkit-box-shadow: 0px 0px 20px 5px rgba(0,0,0,0.26);
        -moz-box-shadow: 0px 0px 20px 5px rgba(0,0,0,0.26);
        box-shadow: 0px 0px 20px 5px rgba(0,0,0,0.26);
    }

    .overlay-rectangle-wrap.closeable {
        padding-top: 25px;
    }
</style>

<template>
    <div>
    <a href="#"
       class="preview-admin-close"
       v-on:click.stop="$parent.closed"
       v-if="isVisible && !closeable && adminPreview">CLOSE BANNER</a>
    <transition appear name="fade">

        <div class="overlay-rectangle-overlay" v-if="isVisible">
            <transition appear v-bind:name="transition">
                <div class="overlay-rectangle-wrap" :class="{ closeable: closeable }">

                    <a class="overlay-rectangle-preview-close"
                       title="Close banner"
                       href="javascript://"
                       v-bind:class="[{hidden: !closeable}]"
                       v-on:click.stop="$parent.closed"
                       v-bind:style="closeStyles"><small>{{ closeText }}</small> &times;</a>

                    <a v-bind:href="$parent.url"
                       v-on="$parent.url ? { click: $parent.clicked } : {}"
                       class="overlay-rectangle-preview-link"
                       v-bind:style="[linkStyles]">
                            <div class="overlay-rectangle-preview-box" v-bind:style="[boxStyles]">
                                <div v-if="imageLink"
                                     class="overlay-rectangle-image">
                                    <img :src="imageLink" alt="">
                                </div>

                                <div class="overlay-rectangle-content" v-if="headerText || buttonText || mainText">
                                    <div v-if="headerText"
                                         v-html="$parent.injectVars(headerText)"
                                         class="overlay-rectangle-header"></div>

                                    <div class="overlay-rectangle-main"
                                         v-html="$parent.injectVars(mainText)"></div>

                                    <div class="overlay-rectangle-button"
                                         v-if="buttonText.length > 0"
                                         v-on:click="$parent.clicked($event, !$parent.url)"
                                         v-html="$parent.injectVars(buttonText)"
                                         v-bind:style="[buttonStyles]"></div>
                                </div>
                            </div>
                    </a>

                </div>
            </transition>
        </div>
    </transition>
    </div>
</template>

<script>
    export default {
        name: 'overlay-rectangle-preview',
        props: [
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

            "imageLink",

            "show",
            "transition",
            "offsetVertical",
            "offsetHorizontal",
            "targetUrl",
            "closeable",
            "closeText",
            "displayType",
            "uuid",
            "campaignUuid",

            "adminPreview"
        ],
        data: function() {
            return {
                visible: true,
                closeTracked: false,
                clickTracked: false,
            }
        },
        computed: {
            _headerText: function() {
                if (headerText !== null && headerText.length > 0) {
                    return headerText;
                }
                return '';
            },
            linkStyles: function() {
                let zIndex;
                if (this.displayType === 'overlay') {
                    zIndex = 9999;
                }

                return {
                    zIndex: zIndex,
                }
            },
            boxStyles: function() {
                return {
                    backgroundColor: this.backgroundColor,
                    color: this.textColor,
                    minWidth: this.width || '100px',
                    maxWidth: this.width || '300px',
                    minHeight: this.height || '100px',
                    maxHeight: this.height || '600px',
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
                    color: this.closeTextColor,
                }
            },
            isVisible: function() {
                return this.show && this.visible;
            },
        },
    }
</script>
