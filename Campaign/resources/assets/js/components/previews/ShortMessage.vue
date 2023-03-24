<style type="text/css">
@import url('../../../sass/transitions.scss');

.short-message-preview-close {
    position: absolute;
    display: block;
    top: 0px;
    right: 0px;
    width: 40px;
    height: 40px;
    text-decoration: none;
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
    text-decoration: none;
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
}

.short-message-preview-box.short-message-closable {
    padding-right: 40px;
}

</style>

<template>
    <a v-bind:href="$parent.url" v-on="$parent.url ? { click: $parent.clicked } : {}" v-if="isVisible"
       class="short-message-preview-link" v-bind:style="[
        linkStyles,
        _position
    ]">
        <transition appear v-bind:name="transition">
            <div class="short-message-preview-box sans-serif"
                 v-bind:style="[boxStyles]"
                 v-bind:class="{'short-message-closable': closeable}"
            >
                <a class="short-message-preview-close"
                   title="Close banner"
                   href="javascript://"
                   v-bind:class="[{hidden: !closeable}]"
                   v-on:click.stop="$parent.closed"
                   v-bind:style="closeStyles"></a>
                <div class="short-message-main" v-html="$parent.injectSnippets(text)"></div>
            </div>
        </transition>
    </a>
</template>

<script>
export default {
    name: 'short-message-preview',
    props: [
        "positionOptions",
        "alignmentOptions",

        "backgroundColor",
        "textColor",
        "text",

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
                backgroundColor: this.backgroundColor,
                color: this.textColor,
                minWidth: '50px',
                minHeight: '25px',
            }
        },
        closeStyles: function () {
            return {
                color: this.textColor,
            }
        },
        isVisible: function () {
            return this.show && this.visible;
        },
    },
}
</script>
