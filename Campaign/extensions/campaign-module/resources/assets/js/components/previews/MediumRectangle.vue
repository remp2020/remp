<style type="text/css" scoped>
@import url('../../../sass/transitions.scss');

.medium-rectangle-preview-close {
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
    cursor: pointer;
}

a.medium-rectangle-preview-close::after {
    content: "\00a0\00d7\00a0";
    font-size: 24px;
    vertical-align: sub;
    font-weight: normal;
    line-height: 40px;
    display: inline-block;
}

.medium-rectangle-preview-close.hidden {
    display: none;
}

.medium-rectangle-preview-wrap {
    cursor: pointer;
    overflow: hidden;
}

.medium-rectangle-preview-box {
    white-space: pre-line;
    overflow: hidden;
    position: relative;
    padding: 0 20px;
    text-align: center;
    display: flex;
    flex-direction: column;
    justify-content: space-around;
    align-items: center;
    box-sizing: border-box;
}

.medium-rectangle-header {
    word-wrap: break-word;
    height: 1em;
}

.medium-rectangle-main {
    font-size: 26px;
    word-wrap: break-word;
    text-decoration: none;
}

.medium-rectangle-button {
    width: 70%;
    border-radius: 15px;
    padding: 5px;
    word-wrap: break-word;
    font-size: 16px;
    cursor: pointer;
}
</style>

<template>
    <div class="medium-rectangle-preview-wrap"
         role="alert"
         v-if="isVisible"
         v-bind:style="[linkStyles, _position]"
    >
        <transition appear v-bind:name="transition">
            <div class="medium-rectangle-preview-box sans-serif"
                 v-bind:style="[boxStyles]"
                 v-on:click="click"
                 v-on:keydown.enter.space="click"
            >
                <a class="medium-rectangle-preview-close" href="javascript://" tabindex="0"
                   v-bind:class="[{hidden: !closeable}]"
                   v-bind:style="[closeStyles]"
                   v-bind:title="closeText || 'Close banner'"
                   v-bind:aria-label="closeText || 'Close banner'"
                   v-on:click.stop="$parent.closed"
                   v-on:keydown.enter.space="$parent.closed"
                ><span>{{ closeText }}</span></a>

                <div class="medium-rectangle-header" v-html="$parent.injectSnippets(headerText)"></div>

                <a class="medium-rectangle-main"
                   ref="mainLink"
                   v-html="$parent.injectSnippets(mainText)"
                   v-bind:href="$parent.url"
                   v-bind:style="[mainTextStyles]"
                   v-bind:aria-label="$parent.injectSnippets(mainText) + (buttonText.length > 0 ? ', ' + $parent.injectSnippets(buttonText) : '') | strip_html"
                   v-on:click.stop="$parent.clicked($event, !$parent.url)"
                   v-on:keydown.enter.space.stop="$parent.clicked($event, !$parent.url)"
                ></a>

                <div class="medium-rectangle-button" aria-hidden="true"
                     v-if="buttonText.length > 0"
                     v-html="$parent.injectSnippets(buttonText)"
                     v-bind:style="[buttonStyles]"
                     v-on:click.stop="click"
                     v-on:keydown.enter.space.stop="click"></div>
            </div>
        </transition>
    </div>
</template>

<script>
export default {
    name: 'medium-rectangle-preview',
    props: [
        "positionOptions",
        "alignmentOptions",

        "colorScheme",
        "headerText",
        "mainText",
        "buttonText",
        "width",
        "height",

        "show",
        "transition",
        "position",
        "offsetVertical",
        "offsetHorizontal",
        "targetUrl",
        "closeable",
        "closeText",
        "displayType",
        "forcedPosition",
        "uuid",
        "campaignUuid"
    ],
    data: function () {
        return {
            visible: true,
            closeTracked: false,
            clickTracked: false,
        }
    },
    computed: {
        _position: function () {
            if (!this.$parent.customPositioned()) {
                return {};
            }

            if (this.positionOptions[this.position]) {
                var styles = this.positionOptions[this.position].style;

                for (var ii in styles) {
                    styles[ii] = ((ii == 'top' || ii == 'bottom') ? this.offsetVertical : this.offsetHorizontal) + 'px'
                }

                return styles;
            }

            return {};
        },
        _headerText: function () {
            if (headerText !== null && headerText.length > 0) {
                return headerText;
            }
            return '';
        },
        linkStyles: function () {
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
        boxStyles: function () {
            return {
                backgroundColor: this.colorScheme.backgroundColor,
                color: this.colorScheme.textColor,
                minWidth: this.width || '100px',
                maxWidth: this.width || '370px',
                minHeight: this.height || '250px',
                maxHeight: this.height || '370px',
            }
        },
        mainTextStyles: function () {
            return {
                color: this.colorScheme.textColor,
            }
        },
        buttonStyles: function () {
            return {
                color: this.colorScheme.buttonTextColor,
                backgroundColor: this.colorScheme.buttonBackgroundColor,
            }
        },
        closeStyles: function () {
            return {
                color: this.colorScheme.textColor,
            }
        },
        isVisible: function () {
            return this.show && this.visible;
        },
    },
    "methods": {
        click: function () {
            this.$refs.mainLink.click();
        }
    },
}
</script>
