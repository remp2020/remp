<style type="text/css" scoped>
@import url('../../../sass/transitions.scss');

.bar-preview-close {
    order: 2;
    align-self: flex-start;
    white-space: nowrap;
    text-transform: uppercase;
    font-size: 14px;
    margin: 0 5px;
    text-decoration: none;
    cursor: pointer;
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
    overflow: hidden;
    cursor: pointer;
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
    text-decoration: none;
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
    display: flex;
    align-items: center;
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
       role="alert"
       v-bind:style="[linkStyles, _position, boxStyles]"
       v-if="isVisible">
    <div class="bar-header sans-serif"
         v-if="closeable"
         v-bind:class="{'bar-close-text-filled-bar': closeText}">
      <div class="bar-close"
           v-on:click.stop="$parent.closed"
           v-bind:style="closeStyles"
           v-bind:title="closeText || 'Close banner'"
           v-bind:aria-label="closeText || 'Close banner'"
      >
          <div>
              {{ closeText }}
              <span style="font-size: 18px">&#215;</span>
          </div>

      </div>
    </div>

    <div v-on:click="click" class="bar-preview-link">
        <transition appear v-bind:name="transition">
            <div class="bar-preview-box sans-serif" v-bind:style="[boxStyles]">
                <a class="bar-preview-close"
                   tabindex="0"
                   v-on:click.stop="$parent.closed"
                   v-on:keydown.enter.space="$parent.closed"
                   v-bind:class="[{hidden: !closeable, 'bar-close-text-filled-button': closeText}]"
                   v-bind:style="closeStyles"
                   v-bind:title="closeText || 'Close banner'"
                   v-bind:aria-label="closeText || 'Close banner'"
                >
                    <span>{{ closeText }} <span style="font-size: 18px">&#215;</span></span>
                </a>

                <a class="bar-main"
                   ref="mainLink"
                   v-html="$parent.injectSnippets(mainText)"
                   v-bind:href="$parent.url"
                   v-bind:style="[mainTextStyles]"
                   v-bind:aria-label="$parent.injectSnippets(mainText) + (buttonText.length > 0 ? ', ' + $parent.injectSnippets(buttonText) : '') | strip_html"
                   v-on:click.stop="$parent.clicked($event, !$parent.url)"
                   v-on:keydown.enter.space="$parent.clicked($event, !$parent.url)"
                ></a>

                <div class="bar-button-wrap">
                    <button class="bar-button"
                        v-if="buttonText.length > 0"
                        v-bind:class="{'bar-button-margin': closeable}"
                        v-on:click.stop="click"
                        v-on:keydown.enter.space="click"
                        v-html="$parent.injectSnippets(buttonText)"
                        v-bind:style="[buttonStyles]"></button>
                </div>
            </div>
        </transition>
    </div>
  </div>
</template>

<script>
export default {
    name: 'bar-preview',
    props: [
        "positionOptions",
        "alignmentOptions",

        "headerText",
        "mainText",
        "buttonText",
        "colorScheme",

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
                backgroundColor: this.colorScheme.backgroundColor,
                color: this.colorScheme.textColor,
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
    methods: {
        click: function () {
            this.$refs.mainLink.click();
        }
    }
}
</script>
