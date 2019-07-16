<style type="text/css">
    @import url('https://fonts.googleapis.com/css?family=Noto+Sans');
    @import url('../../../sass/transitions.scss');

    .html-overlay-rectangle-preview-close {
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

    .html-overlay-rectangle-preview-close.hidden {
        display: none;
    }

    .html-overlay-rectangle-image {
        width: 100%;
        min-width: 300px;
        background-position: center center;
        background-size: cover;
        overflow: hidden;
        max-height: 400px;
    }

    .html-overlay-rectangle-image > img {
        position: relative;
        display: block;
        max-width: 100%;
    }
    
    .html-overlay-rectangle-overlay {
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

    #banner-preview .html-overlay-rectangle-overlay {
        position: absolute;
    }

    .html-overlay-rectangle-preview-link {
        position: relative;
        display: block;
        text-decoration: none;
        overflow: hidden;
    }

    .html-overlay-rectangle-content {
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

    .html-overlay-rectangle-preview-box {
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

    .html-overlay-rectangle-header {
        word-wrap: break-word;
        height: 1em;
        padding: 0;
        margin-bottom: 10px;
    }

    .html-overlay-rectangle-main {
        font-size: 26px;
        word-wrap: break-word;
    }

    .html-overlay-rectangle-button {
        width: 70%;
        border-radius: 15px;
        padding: 5px;
        word-wrap: break-word;
        font-size: 16px;
        cursor: pointer;
        margin-top: 10px;
    }

    .html-overlay-rectangle-wrap {
        position: relative;
        padding: 5px;
        background: #fff;
        -webkit-box-shadow: 0px 0px 20px 5px rgba(0,0,0,0.26);
        -moz-box-shadow: 0px 0px 20px 5px rgba(0,0,0,0.26);
        box-shadow: 0px 0px 20px 5px rgba(0,0,0,0.26);
    }

    .html-overlay-rectangle-wrap.closeable {
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

        <div class="html-overlay-rectangle-overlay" v-if="isVisible">
            <transition appear v-bind:name="transition">
                <div class="html-overlay-rectangle-wrap" :class="{ closeable: closeable }">

                    <a class="html-overlay-rectangle-preview-close"
                       title="Close banner"
                       href="javascript://"
                       v-bind:class="[{hidden: !closeable}]"
                       v-on:click.stop="$parent.closed"
                       v-bind:style="closeStyles"><small>{{ closeText }}</small> &times;</a>

                    <a v-bind:href="$parent.url"
                       v-on="$parent.url ? { click: $parent.clicked } : {}"
                       class="html-overlay-rectangle-preview-link"
                       v-bind:style="[linkStyles]">
                            <div class="html-overlay-rectangle-preview-box" v-bind:style="[boxStyles]">
                                <div class="html-overlay-rectangle-content" v-if="text">
                                    <div class="html-overlay-rectangle-main"
                                         v-html="$parent.injectVars(text)"></div>
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
        name: 'html-overlay-rectangle-preview',
        props: [
            "alignmentOptions",

            "backgroundColor",
            "buttonBackgroundColor",
            "textColor",
            "text",
            "width",
            "height",
            "css",

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
            closeStyles: function() {
                return {
                    color: this.buttonBackgroundColor,
                }
            },
            isVisible: function() {
                return this.show && this.visible;
            },
        },
        mounted: function () {
            let styles = this.css ? this.css.replace(/\r?\n|\r/gm," ") : '',
                head = document.head || document.getElementsByTagName('head')[0],
                style = document.createElement('style');

            head.appendChild(style);

            console.log(styles);

            style.type = 'text/css';
            if (style.styleSheet){
                // This is required for IE8 and below.
                style.styleSheet.cssText = styles;
            } else {
                style.appendChild(document.createTextNode(styles));
            }
        },
    }
</script>
