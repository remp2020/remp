<style type="text/css" scoped>
@import url('../../../sass/transitions.scss');

.html-overlay-rectangle-preview-close {
    position: absolute;
    display: block;
    top: 0;
    right: 0;
    text-decoration: none;
    font-size: 11px;
    font-weight: bold;
    text-transform: uppercase;
    min-width: 40px;
    height: 40px;
    letter-spacing: 0.05em;
    line-height: 40px;
    padding-right: 3px;
    text-align: right;
    color: #000;
    cursor: pointer;
}

a.html-overlay-rectangle-preview-close::after {
    content: "\00a0\00d7\00a0";
    font-size: 24px;
    vertical-align: sub;
    font-weight: normal;
    line-height: 40px;
    display: inline-block;
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

.html-overlay-rectangle-image > img {
    position: relative;
    display: block;
    max-width: 100%;
}

.html-overlay-rectangle-overlay {
    position: fixed;
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
    z-index: 100000;
}

#banner-preview .html-overlay-rectangle-overlay {
    position: absolute;
}

.html-overlay-rectangle-preview-link {
    position: relative;
    display: block;
    overflow: hidden;
}

.html-overlay-rectangle-preview-link.clickable {
    cursor: pointer;
}

.html-overlay-rectangle-content {
    position: relative;
    width: 100%;
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

.html-overlay-rectangle-main {
    font-size: 26px;
    word-wrap: break-word;
}

.html-overlay-rectangle-wrap {
    position: relative;
    padding: 5px;
    background: #fff;
    -webkit-box-shadow: 0px 0px 20px 5px rgba(0, 0, 0, 0.26);
    -moz-box-shadow: 0px 0px 20px 5px rgba(0, 0, 0, 0.26);
    box-shadow: 0px 0px 20px 5px rgba(0, 0, 0, 0.26);
}

.html-overlay-rectangle-wrap.closeable {
    padding-top: 40px;
}
</style>

<template>
    <div role="dialog">
        <a href="#"
           class="preview-admin-close"
           v-on:click.stop="$parent.closed"
           v-on:keydown.enter.space="$parent.closed"
           v-if="isVisible && !closeable && adminPreview">CLOSE BANNER</a>
        <transition appear name="fade">
            <div v-if="isVisible" class="html-overlay-rectangle-overlay sans-serif">
                <transition appear v-bind:name="transition">
                    <div
                        class="html-overlay-rectangle-wrap"
                        role="button"
                        tabindex="0"
                        v-bind:data-href="this.$parent.url"
                        v-bind:class="{ closeable: closeable }"
                        v-on:click.stop="click"
                        v-on:keydown.enter.space="click"
                    >
                        <a class="html-overlay-rectangle-preview-close" tabindex="0"
                           v-bind:class="[{hidden: !closeable}]"
                           v-bind:style="closeStyles"
                           v-bind:title="closeText || 'Close banner'"
                           v-bind:aria-label="closeText || 'Close banner'"
                           v-on:click.stop="$parent.closed"
                           v-on:keydown.enter.space.stop="$parent.closed"
                        ><small>{{ closeText }}</small></a>

                        <div
                            v-bind:style="[linkStyles]"
                            v-bind:class="{'html-overlay-rectangle-preview-link': true, 'clickable': !!$parent.url}"
                        >
                            <div class="html-overlay-rectangle-preview-box" v-bind:style="[boxStyles]">
                                <div class="html-overlay-rectangle-content" v-if="text">
                                    <div class="html-overlay-rectangle-main"
                                         v-html="$parent.injectSnippets(text)"
                                         v-bind:style="[_textAlign, textStyles]"
                                    ></div>
                                </div>
                            </div>
                        </div>
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
        "textAlign",
        "fontSize",
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
    data: function () {
        return {
            visible: true,
            closeTracked: false,
            clickTracked: false,
        }
    },
    computed: {
        _textAlign: function () {
            return this.alignmentOptions[this.textAlign] ? this.alignmentOptions[this.textAlign].style : {};
        },
        linkStyles: function () {
            let zIndex;
            if (this.displayType === 'overlay') {
                zIndex = 9999;
            }

            return {
                zIndex: zIndex,
            }
        },
        boxStyles: function () {
            return {
                backgroundColor: this.backgroundColor,
                color: this.textColor,
                minWidth: this.width || '0px',
                maxWidth: this.width || '800px',
                minHeight: this.height || '0px',
                maxHeight: this.height || 'auto',
            }
        },
        textStyles: function () {
            return {
                color: this.textColor,
                fontSize: this.fontSize + "px",
            }
        },
        closeStyles: function () {
            return {
                color: this.buttonBackgroundColor,
            }
        },
        isVisible: function () {
            return this.show && this.visible;
        },
    },
    mounted: function () {
        let styles = this.css ? this.css.replace(/\r?\n|\r/gm, " ") : '',
            head = document.head || document.getElementsByTagName('head')[0],
            style = document.createElement('style');

        head.appendChild(style);

        style.type = 'text/css';
        if (style.styleSheet) {
            // This is required for IE8 and below.
            style.styleSheet.cssText = this.$parent.injectSnippets(styles);
        } else {
            style.appendChild(document.createTextNode(this.$parent.injectSnippets(styles)));
        }
    },
    methods: {
        click: function (event) {
            if (!this.$parent.url) {
                return;
            }

            this.$parent.clicked(event);
            window.location.href = this.$parent.url;
        }
    }
}
</script>
