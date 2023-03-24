<style type="text/css">
@import url('../../../sass/transitions.scss');

.bar-preview-close {
    order: 2;
    align-self: flex-start;
    white-space: nowrap;
    text-transform: uppercase;
    font-size: 14px;
    margin: 0 5px;
    text-decoration: none;
}

.bar-preview-close.hidden {
    display: none;
}

.bar-button-margin {
    margin-right: 25px;
}

.bar-wrap {
    width: 100%;
    overflow: hidden;
}

.bar-header {
    font-size: 14px;
    color: #5e5e5e;
    min-height: 35px;
    justify-content: flex-end;
    display: none;
}

.bar-close {
    text-transform: uppercase;
    color: #000;
    padding: 0 20px;
    cursor: pointer;
    display: flex;
    justify-content: center;
    align-items: center;
}

.bar-preview-link {
    text-decoration: none;
    overflow: hidden;
    width: 100%;
}

.bar-preview-box {
    white-space: pre-line;
    display: flex;
    overflow: hidden;
    position: relative;
    padding: 5px 18px;
    width: 100%;
    min-height: 68px;
    justify-content: space-between;
    align-items: center;
    box-sizing: border-box;
}

.bar-main {
    font-size: 17px;
    word-wrap: break-word;
    padding-right: 5px;
    margin-top: 5px;
    margin-bottom: 5px;
}

.bar-button {
    border-radius: 15px;
    padding: 7px 30px;
    word-wrap: break-word;
    font-size: 14px;
    text-align: center;
    cursor: pointer;
    white-space: nowrap;
}

.bar-button-wrap {
    order: 1;
    margin-left: auto;
}

@media (max-width: 640px) {
    .bar-preview-box {
        flex-wrap: wrap;
        padding: 9px 20px;
    }

    .bar-main {
        text-align: center;
        margin-bottom: 9px;
        padding: 0 25px;
        flex: 1;
    }

    .bar-button {
        padding: 5px 15px;
        width: fit-content;
        margin: auto;
    }

    .bar-button-wrap {
        order: 2;
        flex-basis: 100%;
    }

    .bar-preview-close {
        order: 1;
    }
}

@media (max-width: 500px) {
    .bar-close-text-filled-bar {
        display: flex;
    }

    .bar-close-text-filled-button {
        display: none;
    }

    .bar-main {
        padding: 0;
    }
}
</style>

<template>
  <div class="bar-wrap"
       v-bind:style="[
        linkStyles,
        _position,
        boxStyles
    ]"
       v-if="isVisible">
    <div class="bar-header sans-serif"
         v-if="closeable"
         v-bind:class="{'bar-close-text-filled-bar': closeText}">
      <div class="bar-close"
           v-on:click.stop="$parent.closed"
           v-bind:style="closeStyles">
          <div>
              {{ closeText }}
              <span style="font-size: 18px">&#215;</span>
          </div>

      </div>
    </div>

    <a v-bind:href="$parent.url" v-on="$parent.url ? { click: $parent.clicked } : {}"
       class="bar-preview-link">
        <transition appear v-bind:name="transition">
            <div class="bar-preview-box sans-serif" v-bind:style="[boxStyles]">
                <a class="bar-preview-close" title="Close banner" href="javascript://"
                   v-bind:class="[{hidden: !closeable, 'bar-close-text-filled-button': closeText}]"
                   v-on:click.stop="$parent.closed"
                   v-bind:style="closeStyles">
                    <span>{{ closeText }} <span style="font-size: 18px">&#215;</span></span>
                </a>
                <div class="bar-main" v-html="$parent.injectSnippets(mainText)"></div>
                <div class="bar-button-wrap">
                    <div class="bar-button"
                         v-if="buttonText.length > 0"
                         v-bind:class="{'bar-button-margin': closeable}"
                         v-on:click="$parent.clicked($event, !$parent.url)"
                         v-html="$parent.injectSnippets(buttonText)"
                         v-bind:style="[buttonStyles]"></div>
                </div>
            </div>
        </transition>
    </a>
  </div>
</template>

<script>
export default {
    name: 'bar-preview',
    props: [
        "positionOptions",
        "alignmentOptions",

        "backgroundColor",
        "buttonBackgroundColor",
        "textColor",
        "buttonTextColor",
        "headerText",
        "mainText",
        "buttonText",

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
                let styles = this.positionOptions[this.position].style;

                for (let pos in styles) {
                    if (!styles.hasOwnProperty(pos)) {
                        continue;
                    }
                    styles[pos] = ((pos === 'top' || pos === 'bottom') ? this.offsetVertical : this.offsetHorizontal) + 'px'
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
            }
        },
        buttonStyles: function () {
            return {
                color: this.buttonTextColor,
                backgroundColor: this.buttonBackgroundColor,
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
