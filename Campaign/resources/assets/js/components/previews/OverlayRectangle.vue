<style type="text/css">
    @import url('https://fonts.googleapis.com/css?family=Noto+Sans');
    @import url('../../../sass/transitions.scss');

    .overlay-rectangle-preview-close {
        position: absolute;
        top: 5px;
        right: 10px;
        font-size: 16px;
        padding: 5px;
        text-decoration: none;
    }

    .overlay-rectangle-preview-close.hidden {
        display: none;
    }

    .overlay-rectangle-image {
        max-width: 100%;
    }
    
    .overlay-rectangle-overlay {
        position: absolute;
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
    }

    .overlay-rectangle-preview-link {
        position: relative;
        display: block;
        text-decoration: none;
        overflow: hidden;
        -webkit-box-shadow: 0px 0px 20px 5px rgba(0,0,0,0.26);
        -moz-box-shadow: 0px 0px 20px 5px rgba(0,0,0,0.26);
        box-shadow: 0px 0px 20px 5px rgba(0,0,0,0.26);
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
        font-family: Noto Sans, sans-serif;
        white-space: pre-line;
        overflow: hidden;
        position: relative;
        text-align: center;
        display: flex;
        flex-direction: column;
        justify-content: space-around;
        align-items: center;
        box-sizing: border-box;
    }

    .overlay-rectangle-header {
        word-wrap: break-word;
        height: 1em;
        padding: 10px 0;
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
</style>

<template>
    <div class="overlay-rectangle-overlay">
        <a v-bind:href="$parent.url" v-on="$parent.url ? { click: $parent.clicked } : {}" v-if="isVisible" class="overlay-rectangle-preview-link" v-bind:style="[linkStyles, _position]">
            <transition appear v-bind:name="transition">
                <div class="overlay-rectangle-preview-box" v-bind:style="[boxStyles]">
                    <img :src="imageLink" class="overlay-rectangle-image" alt="">
                    <div class="overlay-rectangle-content">
                        <a class="overlay-rectangle-preview-close" title="Close banner" href="javascript://" v-bind:class="[{hidden: !closeable}]" v-on:click.stop="$parent.closed" v-bind:style="closeStyles">&times;</a>
                        <div v-if="headerText" class="overlay-rectangle-header" v-html="$parent.injectVars(headerText)"></div>
                        <div class="overlay-rectangle-main" v-html="$parent.injectVars(mainText)"></div>
                        <div class="overlay-rectangle-button" v-if="buttonText.length > 0" v-on:click="$parent.clicked($event, !$parent.url)" v-html="$parent.injectVars(buttonText)" v-bind:style="[buttonStyles]"></div>
                    </div>
                </div>
            </transition>
        </a>
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
            "displayType",
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
            _position: function () {
                console.log('computed position');
                let $el = $('.overlay-rectangle-preview-link'),
                    width = $el.width(),
                    height = $el.height();

                console.log($el.length);

                console.log('width', width);
                console.log('height', height);

                return {
                    marginLeft: -width/2,
                    marginTop: -height/2,
                }
            },
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
