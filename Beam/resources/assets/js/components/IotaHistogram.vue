<style lang="scss" scoped>
.ri-histogram {
  font-family: sans-serif;
  font-weight: 400;
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  height: 100%;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  width: 25%;
  z-index: 999999;
  transition: all 0.3s ease-in-out, z-index 0s ease-in-out;
  &__item {
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    color: rgb(84, 84, 84);
    font-size: 13px;
    background-color: #b1c5e8;
    height: 100%;
    margin: 2px 0;
    padding: 5px;
    box-sizing: content-box;
    transition: all 0.3s ease-in-out;
    &--max {
      background-color: #e24242;
      color: white;
    }
  }

  &:not(.ri-histogram--opened) {
    width: 5px;
    opacity: 0.5;
    z-index: 0;
    transition: all 0.15s ease-in-out;
    .ri-histogram__item {
      width: 100% !important;
    }
    .ri-histogram__item__number {
      display: none;
    }
  }
}
</style>

<template>
  <div
    :class="{'ri-histogram': true, 'ri-histogram--opened': histogramVisible}"
    @mouseenter="histogramHovered = true"
    @mouseleave="histogramHovered = false"
  >
    <div
      v-for="item in histogram"
      :key="item.bucket_key"
      :style="{width: `${calculatePercentageFromMax(item.value)}%`}"
      :class="{'ri-histogram__item--max': item.value === maxLeavedReadersInChunk, 'ri-histogram__item': true }"
    >
      <span class="ri-histogram__item__number">{{ item.value }}</span>
    </div>
  </div>
</template>

<script type="text/javascript">
import EventHub from "./EventHub";

export default {
  name: "iota-histogram",
  data: () => ({
    histogramPermanentlyVisibleOption: false,
    histogramHovered: false,
    totalReaders: 0,
    histogram: []
  }),
  created: function() {
    document.documentElement.style.position = "relative";
    EventHub.$on("read-progress-data-changed", this.receiveReadProgressData);
    EventHub.$on(
      "read-progress-histogram-toggle",
      this.toggleHistogramVisibility
    );
  },
  computed: {
    maxLeavedReadersInChunk() {
      return this.histogram.reduce(
        (max, current) => (current.value > max ? current.value : max),
        0
      );
    },
    histogramVisible() {
      return this.histogramPermanentlyVisibleOption || this.histogramHovered;
    }
  },
  methods: {
    receiveReadProgressData(totalReaders, histogram) {
      this.totalReaders = totalReaders;
      this.histogram = histogram;
    },
    calculatePercentageFromMax(value) {
      return (value * 100) / this.maxLeavedReadersInChunk;
    },
    toggleHistogramVisibility() {
      this.histogramPermanentlyVisibleOption = !this
        .histogramPermanentlyVisibleOption;
    }
  }
};
</script>
