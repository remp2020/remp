<style type="text/css" scoped>
@import url('../../../sass/transitions.scss');

.short-message-preview-wrap {
    cursor: pointer;
}

.short-message-preview-close {
    position: absolute;
    display: block;
    top: 0px;
    right: 0px;
    width: 40px;
    height: 40px;
    text-decoration: none;
    cursor: pointer;
}

a.short-message-preview-close::after {
    content: "\00d7";
    font-size: 24px;
    font-weight: normal;
    display: block;
    width: 40px;
    height: 40px;
    line-height: 40px;
    text-align: center;
}

.short-message-preview-close.hidden {
    display: none;
}

.short-message-preview-link {
    cursor: pointer;
    overflow: hidden;
}

.short-message-preview-box {
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
    text-decoration: none;
}

.short-message-preview-box.short-message-closable {
    padding-right: 40px;
}

</style>

<template>
    <div
        class="short-message-preview-wrap"
        role="alert"
        v-if="isVisible"
        v-on:click="click"
        v-bind:style="[linkStyles, _position]"
    >
        <transition appear v-bind:name="transition">
            <div class="short-message-preview-box sans-serif"
                 v-bind:style="[boxStyles]"
                 v-bind:class="{'short-message-closable': closeable}"
            >
                <a class="short-message-preview-close"
                   v-bind:class="[{hidden: !closeable}]"
                   role="button"
                   tabindex="0"
                   v-bind:aria-label="closeText || 'Close banner'"
                   v-bind:title="closeText || 'Close banner'"
                   v-bind:style="closeStyles"
                   v-on:click.stop="$parent.closed"
                   v-on:keydown.enter.space="$parent.closed"></a>

                <a class="short-message-main"
                   ref="mainLink"
                   v-html="$parent.injectSnippets(text)"
                   v-bind:style="[mainTextStyles]"
                   v-bind:href="$parent.url"
                   v-on:click.stop="$parent.clicked($event, !$parent.url)"
                   v-on:keydown.enter.space="$parent.clicked($event, !$parent.url)"
                ></a>
            </div>
        </transition>
    </div>
</template>

<script>
export default {
    name: 'short-message-preview',
    props: [
        "positionOptions",
        "alignmentOptions",

        "colorScheme",
        "text",
        "closeText",

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
                minWidth: '50px',
                minHeight: '25px',
            }
        },
        mainTextStyles: function () {
            return {
                color: this.colorScheme.textColor,
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
    methods: {
        click: function () {
            this.$refs.mainLink.click();
        }
    }
}
</script>
