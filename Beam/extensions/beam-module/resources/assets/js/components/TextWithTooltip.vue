<template>
    <span class="text-with-tooltip" @mouseenter="show = true" @mouseleave="show = false">
        <span ref="content">
            <slot/>
        </span>
        <transition name="fade">
            <div v-show="show" ref="tooltip" class="text-with-tooltip__tooltip" :style="{ width: `${contentWidth}px` }">
                <span ref="tooltipContent" class="tooltip-content card">
                    <slot name="tooltip"/>
                </span>
                <span ref="arrow" class="tooltip-arrow"></span>
            </div>
        </transition>
    </span>
</template>

<style scoped lang="scss">
.text-with-tooltip {
    position: relative;
    border-bottom: 2px dashed #8d8d8d;

    &__tooltip {
        font-size: 12px;
        line-height: 16px;
        flex: 1 0 auto;
        position: absolute;
        max-width: 500px;
        z-index: 9000;
        padding-top: 10px;
        top: 100%;
        left: 0;

        @media(max-width: 500px) {
            max-width: calc(100vw - 20px);
        }

        .tooltip-content {
            background: #737373;
            color: white;
            width: 100%;
            overflow: hidden;
            display: inline-block;
            padding: 8px 10px;
            text-overflow: ellipsis;
        }

        .tooltip-arrow {
            position: absolute;
            bottom: calc(100% - 10px);
            left: 10%;
            margin-left: -5px;
            border-width: 5px;
            border-style: solid;
            border-color: transparent transparent #737373 transparent;
        }
    }

    :deep(a) {
        color: #00c5e7;

        &:hover {
            color: #00a2bd;
        }
    }
}

.fade-enter-active, .fade-leave-active {
    transition: opacity .32s;
}
.fade-enter, .fade-leave-to {
    opacity: 0;
}
</style>

<script>
export default {
    name: 'TextWithTooltip',
    data() {
        return {
            show: false,
            contentWidth: 0,
            tooltipLeft: 0
        }
    },
    watch: {
      show() {
          if (this.show) {
              this.contentWidth = this.getContentWidth()
              this.refreshPosition()
          }
      }
    },
    mounted() {
        this.contentWidth = this.getContentWidth()
    },
    methods: {
        getContentWidth() {
            const span = $('<span style="display:none; font-size: 12px;"></span>')
            span.html(this.$refs.tooltip.innerHTML)
            span.appendTo(document.body)
            const contentWidth = Math.ceil(span.width())
            span.remove()

            return contentWidth + 20
        },

        refreshPosition() {
            const offsetLeft = this.$refs.content.getBoundingClientRect().x
            const tooltipWidth = $(this.$refs.tooltip).width()
            this.tooltipLeft = Math.min(0, document.body.clientWidth - 10 - (offsetLeft + tooltipWidth))
            this.$refs.tooltip.style.left = `${this.tooltipLeft}px`

            this.refreshCaret()
        },

        refreshCaret() {
            const contentWidth = $(this.$refs.content).width()
            $(this.$refs.arrow).css('left', `${(contentWidth / 2) + Math.abs(this.tooltipLeft)}px`)
        }
    }
}
</script>
