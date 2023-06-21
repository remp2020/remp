<style lang="scss" scoped>
// previous color values, just a little bit darker
$colors: (
  no-color: darken(#eef7fe, 2%),
  low-color: darken(#eefbf3, 2%),
  medium-color: darken(#d3f4e0, 2%),
  high-color: darken(#9be6ba, 2%)
);

.ri-metrics {
  font-family: sans-serif;
  font-weight: 400;
  position: relative;
  &__inline-metric {
    position: absolute;
    z-index: 999999;
    width: 100%;
    left: 0;
    right: 0;
    bottom: 5px;
    height: 2px;
    &__bubble {
      font-size: 12px;
      padding: 5px 10px;
      background-color: inherit;
      color: rgba(0, 0, 0, 0.5);
      position: absolute;
      bottom: 0px;
      right: calc(100% - 15px);
      text-align: center;
      border-radius: 20px;
      border-bottom-right-radius: 0;
      display: flex;
      align-items: center;
      flex-direction: column;
      &:hover {
        cursor: pointer;
      }
      &__concurrents {
        min-width: 14px;
        padding-bottom: 2px;
        margin-bottom: 2px;
        border-bottom: 1px solid #d1e9fc;
      }
      &__conversions {
        display: flex;
        font-weight: 600;
        &--with-ab {
          margin-top: 3px;
        }
      }
      &__ab {
        padding: 2px;
        background: #e24242;
        color: white;
        border-radius: 3px;
        margin-right: 5px;
        font-size: 9px;
        font-weight: 400;
      }
    }
  }

  &__detail {
    background-color: white;
    box-shadow: 5px 9px 30px 0 rgba(168, 173, 187, 0.6);
    position: absolute;
    z-index: 9999999;
    width: 300px;
    top: calc(100% - 5px);
    left: 15px;
    border-radius: 5px;

    &__header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 10px 15px;
      background: #00000008;
      &__title {
        font-size: 16px;
        svg {
          width: 15px;
          position: relative;
          top: 2px;
          margin-right: 5px;
        }
      }
      &__close {
        font-size: 20px;
        cursor: pointer;
      }
    }

    &__tabs {
      display: flex;
      justify-content: space-around;
      align-items: center;
      margin-bottom: 20px;
      &__item {
        font-size: 14px;
        color: #616060;
        border-top: 2px solid #eaeaea;
        flex: 1;
        text-align: center;
        padding: 10px 15px;
        cursor: pointer;
        transition: all 0.2s ease-in-out;
        &--active {
          color: #2872d8;
          border-color: #2872d8;
        }
        &--disabled {
          opacity: 0.3;
          cursor: not-allowed;
        }
      }
    }

    &__performance,
    &__ab {
      display: flex;
      align-items: center;
      justify-content: space-between;
      text-align: center;
      flex-wrap: wrap;
      &__item {
        width: 50%;
        margin-bottom: 30px;
        &__number {
          color: #2872d8;
          font-size: 30px;
        }
        &__caption {
          color: #9c9c9c;
          font-size: 12px;
        }
      }
    }

    &__ab {
      border-bottom: 1px solid #eaeaea;
      margin-bottom: 15px;
      &-wrapper div:last-child .ri-metrics__detail__ab {
        border-bottom: none;
      }
      &__item {
        margin-bottom: 15px;
      }
      &-title {
        text-align: center;
        font-size: 13px;
        color: #2d2d2d;
      }
    }

    &__beam-link {
      display: block;
      text-align: center;
      background-color: #f7f7f7;
      padding: 10px 0;
      font-size: 11px;
      color: #616060;
      transition: all 0.2s ease-in-out;
      &:hover {
        background-color: #eaeaea;
      }
      svg {
        fill: #616060;
        height: 11px;
        position: relative;
        top: 1px;
        left: 2px;
      }
    }
  }

  &__detail-animation-enter-active {
    animation: ri-metrics__detail-animation-in 0.1s;
  }
  &__detail-animation-leave-active {
    animation: ri-metrics__detail-animation-in 0s reverse;
  }
  @keyframes ri-metrics__detail-animation-in {
    0% {
      transform: scale(0);
      opacity: 0;
      transform-origin: left top;
    }
    100% {
      transform: scale(1);
      opacity: 1;
      transform-origin: left top;
    }
  }

  @each $name, $hex in $colors {
    &__#{$name}.ri-metrics__inline-metric {
      background-color: darken($hex, 4%);
    }
    // &__#{$name}.ri-metrics__detail__performance__item
    //   .ri-metrics__detail__performance__item__number {
    //   color: $hex;
    // }

    &__#{$name} .ri-metrics__inline-metric__bubble {
      background-color: $hex;
      border: 1px solid darken($hex, 4%);
      box-shadow: -1px 1px 3px $hex;
      &__concurrents {
        border-bottom: 1px solid darken($hex, 6%);
      }
    }
  }
}
</style>

<template>
  <div class="ri-metrics">
    <div class="ri-metrics__inline-metric" :class="conversionsColorClass">
      <div @click="toggleMetricsDetail" class="ri-metrics__inline-metric__bubble">
        <div class="ri-metrics__inline-metric__bubble__concurrents">
          <AnimatedInteger :value="concurrents" />
        </div>
        <div
          :class="{'ri-metrics__inline-metric__bubble__conversions': true, 'ri-metrics__inline-metric__bubble__conversions--with-ab': hasABTests}"
        >
          <span class="ri-metrics__inline-metric__bubble__ab" v-if="hasABTests">A/B</span>
          <AnimatedInteger :value="conversions" />
        </div>
      </div>
    </div>
    <transition name="ri-metrics__detail-animation">
      <div v-if="metricsDetailVisible" class="ri-metrics__detail">
        <div class="ri-metrics__detail__header">
          <div class="ri-metrics__detail__header__title">
            <svg viewBox="0 0 71.61 70.88">
              <polygon
                fill="black"
                points="35.8 2.74 16.91 13.65 23.2 17.29 35.8 10.02 54.7 20.93 35.8 31.84 35.8 31.84 10.61 17.29 10.61 53.59 16.91 57.23 16.91 28.2 35.8 39.11 35.8 39.11 35.8 39.11 35.8 39.11 61 24.57 61 17.29 35.8 2.74"
              />
              <polygon
                fill="black"
                points="23.2 53.66 23.2 60.93 35.8 68.14 61 53.59 61 46.32 35.8 60.86 23.2 53.66"
              />
              <polygon
                fill="black"
                points="35.8 46.28 23.2 39.08 23.2 46.35 35.8 53.55 35.8 53.66 61 39.11 61 31.84 35.8 46.39 35.8 46.28"
              />
            </svg>
            Article Overview
          </div>
          <div class="ri-metrics__detail__header__close" @click="hideMetricsDetail">&times;</div>
        </div>
        <div class="ri-metrics__detail__tabs">
          <div
            class="ri-metrics__detail__tabs__item"
            @click="metricsDetailActiveTab = PERFORMANCE_TAB"
            :class="{'ri-metrics__detail__tabs__item--active': metricsDetailActiveTab === PERFORMANCE_TAB}"
          >Performance</div>
          <div
            class="ri-metrics__detail__tabs__item"
            @click="hasABTests ? metricsDetailActiveTab = AB_TESTS_TAB : null"
            :class="{'ri-metrics__detail__tabs__item--active': metricsDetailActiveTab === AB_TESTS_TAB, 'ri-metrics__detail__tabs__item--disabled': !hasABTests}"
          >A/B Tests</div>
        </div>

        <div
          class="ri-metrics__detail__performance-wrapper"
          v-if="metricsDetailActiveTab === PERFORMANCE_TAB"
        >
          <div class="ri-metrics__detail__performance">
            <div class="ri-metrics__detail__performance__item" :class="conversionsColorClass">
              <div class="ri-metrics__detail__performance__item__number">{{ conversions }}</div>
              <div class="ri-metrics__detail__performance__item__caption">Conversions</div>
            </div>

            <div class="ri-metrics__detail__performance__item" :class="conversionRateColorClass">
              <div class="ri-metrics__detail__performance__item__number">{{ conversionRate }}</div>
              <div class="ri-metrics__detail__performance__item__caption">Conversion rate</div>
            </div>
          </div>

          <div class="ri-metrics__detail__performance">
            <div class="ri-metrics__detail__performance__item">
              <div class="ri-metrics__detail__performance__item__number">
                <AnimatedInteger :value="concurrents" />
              </div>
              <div class="ri-metrics__detail__performance__item__caption">Readers Concurrent</div>
            </div>
            <div
              class="ri-metrics__detail__performance__item"
              v-for="range in sortedPageviewRanges"
              :key="range.label"
            >
              <div class="ri-metrics__detail__performance__item__number">
                <AnimatedInteger :value="pageviewStats[range.label]" />
              </div>
              <div class="ri-metrics__detail__performance__item__caption">Readers {{ range.label }}</div>
            </div>
          </div>
        </div>

        <div class="ri-metrics__detail__ab-wrapper" v-if="metricsDetailActiveTab === AB_TESTS_TAB">
          <div v-for="variant in sortedTitleVariants" :key="'title'+variant">
            <div class="ri-metrics__detail__ab-title">Title {{ variant }} (direct)</div>
            <div class="ri-metrics__detail__ab">
              <div
                class="ri-metrics__detail__ab__item"
                v-for="range in sortedTitleVariantRanges"
                :key="'title'+range.label"
              >
                <div class="ri-metrics__detail__ab__item__number">
                  <AnimatedInteger :value="titleVariantStats[variant][range.label] || 0" />
                </div>
                <div class="ri-metrics__detail__ab__item__caption">Readers {{ range.label }}</div>
              </div>
            </div>
          </div>

          <div v-for="variant in sortedImageVariants" :key="'image'+variant">
            <div class="ri-metrics__detail__ab-title">Image {{ variant }} (direct)</div>
            <div class="ri-metrics__detail__ab">
              <div
                class="ri-metrics__detail__ab__item"
                v-for="range in sortedImageVariantRanges"
                :key="'image'+range.label"
              >
                <div class="ri-metrics__detail__ab__item__number">
                  <AnimatedInteger :value="imageVariantStats[variant][range.label] || 0" />
                </div>
                <div class="ri-metrics__detail__ab__item__caption">Readers {{ range.label }}</div>
              </div>
            </div>
          </div>
        </div>
        <a
          :href="`${baseUrl}/article?external_id=${articleId}`"
          target="_blank"
          class="ri-metrics__detail__beam-link"
        >
          <svg
            xmlns="http://www.w3.org/2000/svg"
            x="0px"
            y="0px"
            width="24"
            height="24"
            viewBox="0 0 24 24"
          >
            <path
              d="M 5 3 C 3.9069372 3 3 3.9069372 3 5 L 3 19 C 3 20.093063 3.9069372 21 5 21 L 19 21 C 20.093063 21 21 20.093063 21 19 L 21 12 L 19 12 L 19 19 L 5 19 L 5 5 L 12 5 L 12 3 L 5 3 z M 14 3 L 14 5 L 17.585938 5 L 8.2929688 14.292969 L 9.7070312 15.707031 L 19 6.4140625 L 19 10 L 21 10 L 21 3 L 14 3 z"
            />
          </svg>
          BEAM article detail
        </a>
      </div>
    </transition>
  </div>
</template>

<script type="text/javascript">
import EventHub from "./EventHub";
import AnimatedInteger from "./dashboard/AnimatedInteger";
import {
  rounding,
  CONVERSIONS_COLORING_THRESHOLD,
  CONVERSION_RATE_COLORING_THRESHOLD
} from "./dashboard/constants.js";

function colorThresholdToClass(threshold, value) {
  if (value > threshold.high) {
    return "ri-metrics__high-color";
  } else if (value > threshold.medium) {
    return "ri-metrics__medium-color";
  } else if (value > threshold.low) {
    return "ri-metrics__low-color";
  } else {
    return "ri-metrics__no-color";
  }
}

export default {
  name: "iota-article-stats",
  props: {
    articleId: {
      type: String,
      required: true
    },
    baseUrl: {
      type: String,
      required: true
    }
  },
  components: { AnimatedInteger },
  data: () => ({
    conversions: 0,
    totalPageviews: 0,
    concurrents: 0,

    pageviewStats: {},
    titleVariantStats: {},
    imageVariantStats: {},

    pageviewRanges: {},
    titleVariantRanges: {},
    imageVariantRanges: {},

    titleVariants: [],
    imageVariants: [],
    config: null,

    metricsDetailVisible: false,
    metricsDetailActiveTab: "performance",
    PERFORMANCE_TAB: "performance",
    AB_TESTS_TAB: "abTests"
  }),
  created: function() {
    EventHub.$on("content-conversions-counts-changed", this.updateConversions);
    EventHub.$on("content-pageviews-changed", this.updatePageviewStats);
    EventHub.$on("content-concurrents-changed", this.updateConcurrentsStats);
    EventHub.$on("content-variants-changed", this.updateVariantStats);
    EventHub.$on("config-changed", this.configChanged);
    EventHub.$on("opening-metrics-detail", this.hideMetricsDetail);
  },
  computed: {
    conversionRate() {
      if (this.totalPageviews === 0) {
        return 0.0;
      }
      // Artificially increased 10000x so conversion rate is more readable
      return rounding((this.conversions / this.totalPageviews) * 10000, 2);
    },
    conversionRateColoring() {
      if (this.config) {
        return {
          low: this.config.conversion_rate_threshold_low,
          medium: this.config.conversion_rate_threshold_medium,
          high: this.config.conversion_rate_threshold_high
        };
      } else {
        return CONVERSION_RATE_COLORING_THRESHOLD;
      }
    },
    conversionsColoring() {
      if (this.config) {
        return {
          low: this.config.conversions_count_threshold_low,
          medium: this.config.conversions_count_threshold_medium,
          high: this.config.conversions_count_threshold_high
        };
      } else {
        return CONVERSIONS_COLORING_THRESHOLD;
      }
    },
    conversionRateColorClass() {
      return colorThresholdToClass(
        this.conversionRateColoring,
        this.conversionRate
      );
    },
    conversionsColorClass() {
      return colorThresholdToClass(this.conversionsColoring, this.conversions);
    },
    sortedPageviewRanges() {
      return Object.values(this.pageviewRanges)
        .slice()
        .sort((a, b) => a.order - b.order);
    },
    sortedTitleVariantRanges() {
      return Object.values(this.titleVariantRanges)
        .slice()
        .sort((a, b) => a.order - b.order);
    },
    sortedImageVariantRanges() {
      return Object.values(this.imageVariantRanges)
        .slice()
        .sort((a, b) => a.order - b.order);
    },
    sortedTitleVariants() {
      return this.titleVariants.slice().sort((a, b) => a - b);
    },
    sortedImageVariants() {
      return this.imageVariants.slice().sort((a, b) => a - b);
    },
    hasABTests() {
      return (
        this.sortedTitleVariants.length > 1 ||
        this.sortedImageVariants.length > 1
      );
    }
  },
  methods: {
    toggleMetricsDetail() {
      if (this.metricsDetailVisible === false) {
        EventHub.$emit("opening-metrics-detail");
      }

      this.metricsDetailVisible = !this.metricsDetailVisible;
    },
    hideMetricsDetail() {
      this.metricsDetailVisible = false;
    },
    showMetricsDetail() {
      EventHub.$emit("opening-metrics-detail");
      this.metricsDetailVisible = true;
    },
    configChanged(config) {
      this.config = config;
    },
    updateConversions(counts) {
      if (counts === null || !counts[this.articleId]) {
        this.conversions = 0;
        return;
      }
      this.conversions = counts[this.articleId];
    },
    updatePageviewStats(range, counts) {
      this.$set(this.pageviewRanges, range.label, range);
      if (counts === null || !counts[this.articleId]) {
        this.$set(this.pageviewStats, range.label, 0);
        return;
      }
      this.$set(this.pageviewStats, range.label, counts[this.articleId]);

      // Assuming 'Total' label exists
      if (range.label === "Total") {
        this.totalPageviews = counts[this.articleId];
      }
    },
    updateConcurrentsStats(data) {
      const foundItem = data.find(item => item.external_id === this.articleId);
      this.concurrents = foundItem ? foundItem.count : 0;
    },
    updateVariantStats(variantTypes, range, counts) {
      for (const variantType of variantTypes) {
        switch (variantType) {
          case "title_variant":
            this.updateVariants(
              this.titleVariantRanges,
              this.titleVariants,
              this.titleVariantStats,
              range,
              counts[variantType]
            );
            break;
          case "image_variant":
            this.updateVariants(
              this.imageVariantRanges,
              this.imageVariants,
              this.imageVariantStats,
              range,
              counts[variantType]
            );
            break;
        }
      }
    },
    updateVariants(variantRanges, variants, variantStats, range, counts) {
      this.$set(variantRanges, range.label, range);

      if (!counts) return;

      for (let variant in counts[this.articleId]) {
        if (!counts[this.articleId].hasOwnProperty(variant)) {
          continue;
        }
        if (variants.indexOf(variant) === -1) {
          variants.push(variant);
        }

        let val = variantStats[variant] || {};
        if (counts === null || !counts[this.articleId][variant]) {
          val[range.label] = 0;
        } else {
          val[range.label] = counts[this.articleId][variant];
        }
        variantStats[variant] = val;
      }
      this.$forceUpdate();
    }
  }
};
</script>
