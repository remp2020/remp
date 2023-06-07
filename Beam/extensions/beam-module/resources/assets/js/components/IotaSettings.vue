<style lang="scss" scoped>
.ri-settings {
  font-family: sans-serif;
  font-weight: 400;
  background-color: white;
  box-shadow: 5px 9px 30px 0 rgba(168, 173, 187, 0.6);
  border-radius: 5px;
  width: 170px;
  position: fixed;
  right: 30px;
  top: 70px;
  z-index: 99999999;
  &__header {
    display: flex;
    align-items: center;
    padding: 10px 15px;
    background: #3874d0;
    color: white;
    font-size: 15px;
    svg {
      width: 17px;
      margin-right: 10px;
    }
  }
  &__group {
    border-bottom: 1px solid #eaeaea;
    padding: 15px;
    font-size: 14px;
    &__title {
      color: #9c9c9c;
      font-size: 12px;
      margin-bottom: 13px;
    }
  }
  &__input-wrapper {
    margin-bottom: 15px;
    &:last-child {
      margin-bottom: 0;
    }
  }

  // styled radio buttons from internet
  [type="radio"]:checked,
  [type="radio"]:not(:checked) {
    position: absolute;
    left: -9999px;
  }
  [type="radio"]:checked + label,
  [type="radio"]:not(:checked) + label {
    position: relative;
    padding-left: 28px;
    cursor: pointer;
    line-height: 20px;
    display: inline-block;
    color: #666;
  }
  [type="radio"]:checked + label:before,
  [type="radio"]:not(:checked) + label:before {
    content: "";
    position: absolute;
    left: 0;
    top: 0;
    width: 18px;
    height: 18px;
    border: 1px solid #ddd;
    border-radius: 100%;
    background: #fff;
  }
  [type="radio"]:checked + label:after,
  [type="radio"]:not(:checked) + label:after {
    content: "";
    width: 12px;
    height: 12px;
    background: #3974d1;
    position: absolute;
    top: 3px;
    left: 3px;
    border-radius: 100%;
    -webkit-transition: all 0.2s ease;
    transition: all 0.2s ease;
  }
  [type="radio"]:not(:checked) + label:after {
    opacity: 0;
    -webkit-transform: scale(0);
    transform: scale(0);
  }
  [type="radio"]:checked + label:after {
    opacity: 1;
    -webkit-transform: scale(1);
    transform: scale(1);
  }
  &__toggle {
    display: block;
    text-align: center;
    background-color: #f7f7f7;
    width: 100%;
    padding: 10px 0;
    font-size: 12px;
    color: #616060;
    transition: all 0.2s ease-in-out;
    &:hover {
      background-color: #eaeaea;
    }
    svg {
      fill: #616060;
      height: 9px;
      &.collapsed {
        transform: rotate(180deg);
      }
    }
  }
  &__concurrents {
    padding: 0px 15px 5px 15px;
    color: #666;
    svg {
      fill: #666;
      position: relative;
      top: 5px;
      margin-right: 10px;
    }
    &__page {
      color: #3874d0;
    }
    &__all {
      font-size: 11px;
    }
  }
}
</style>

<template>
  <div class="ri-settings">
    <div class="ri-settings__header">
      <svg viewBox="0 0 71.61 70.88">
        <polygon
          fill="white"
          points="35.8 2.74 16.91 13.65 23.2 17.29 35.8 10.02 54.7 20.93 35.8 31.84 35.8 31.84 10.61 17.29 10.61 53.59 16.91 57.23 16.91 28.2 35.8 39.11 35.8 39.11 35.8 39.11 35.8 39.11 61 24.57 61 17.29 35.8 2.74"
        />
        <polygon
          fill="white"
          points="23.2 53.66 23.2 60.93 35.8 68.14 61 53.59 61 46.32 35.8 60.86 23.2 53.66"
        />
        <polygon
          fill="white"
          points="35.8 46.28 23.2 39.08 23.2 46.35 35.8 53.55 35.8 53.66 61 39.11 61 31.84 35.8 46.39 35.8 46.28"
        />
      </svg>
      Beam metrics
    </div>
    <div class="ri-settings__concurrents">
      <svg
        xmlns="http://www.w3.org/2000/svg"
        x="0px"
        y="0px"
        width="24"
        height="24"
        viewBox="0 0 24 24"
      >
        <path
          d="M 12 4 A 3.5 3.5 0 0 0 8.5 7.5 A 3.5 3.5 0 0 0 12 11 A 3.5 3.5 0 0 0 15.5 7.5 A 3.5 3.5 0 0 0 12 4 z M 4.5 7 A 2.5 2.5 0 0 0 2 9.5 A 2.5 2.5 0 0 0 4.5 12 A 2.5 2.5 0 0 0 7 9.5 A 2.5 2.5 0 0 0 4.5 7 z M 19.5 7 A 2.5 2.5 0 0 0 17 9.5 A 2.5 2.5 0 0 0 19.5 12 A 2.5 2.5 0 0 0 22 9.5 A 2.5 2.5 0 0 0 19.5 7 z M 12 13 C 9.664 13 5 14.173 5 16.5 L 5 19 C 5 19.552 5.448 20 6 20 L 18 20 C 18.552 20 19 19.552 19 19 L 19 16.5 C 19 14.173 14.336 13 12 13 z M 3.8984375 14.052734 C 2.3174375 14.296734 0 15.389078 0 16.705078 L 0 19 C 0 19.552 0.448 20 1 20 L 3 20 L 3 16.5 C 3 15.539 3.3454375 14.732734 3.8984375 14.052734 z M 20.101562 14.052734 C 20.654563 14.732734 21 15.539 21 16.5 L 21 20 L 23 20 C 23.552 20 24 19.552 24 19 L 24 16.705078 C 24 15.389078 21.682562 14.296734 20.101562 14.052734 z"
        />
      </svg>
      <span class="ri-settings__concurrents__page">
        <AnimatedInteger :value="pageConcurrents" />
      </span>
      <span class="ri-settings__concurrents__all">
        /
        <AnimatedInteger :value="allConcurrents" />
      </span>
    </div>
    <div class="ri-settings__body" v-if="!collapsed">
      <div class="ri-settings__group">
        <p class="ri-settings__group__title">
          Device type
          <HelpIconWithTooltip text="Affects reading progress statistics based on the selected device type." />
        </p>
        <div class="ri-settings__input-wrapper">
          <input
            type="radio"
            id="ri-settings-device-all"
            name="ri-settings-device-group"
            value="all"
            v-model="deviceType"
          />
          <label for="ri-settings-device-all">All</label>
        </div>
        <div class="ri-settings__input-wrapper">
          <input
            type="radio"
            id="ri-settings-device-computer"
            name="ri-settings-device-group"
            value="Computer"
            v-model="deviceType"
          />
          <label for="ri-settings-device-computer">Computer</label>
        </div>
        <div class="ri-settings__input-wrapper">
          <input
            type="radio"
            id="ri-settings-device-tablet"
            name="ri-settings-device-group"
            value="Tablet"
            v-model="deviceType"
          />
          <label for="ri-settings-device-tablet">Tablet</label>
        </div>
        <div class="ri-settings__input-wrapper">
          <input
            type="radio"
            id="ri-settings-device-phone"
            name="ri-settings-device-group"
            value="Phone"
            v-model="deviceType"
          />
          <label for="ri-settings-device-phone">Phone</label>
        </div>
      </div>
      <div class="ri-settings__group">
        <p class="ri-settings__group__title">
          Reader type
          <HelpIconWithTooltip text="Affects reading progress statistics based on reader type." />
        </p>
        <div class="ri-settings__input-wrapper">
          <input
            type="radio"
            id="ri-settings-reader-type-all"
            name="ri-settings-reder-type-group"
            value="all"
            v-model="subscriber"
          />
          <label for="ri-settings-reader-type-all">All</label>
        </div>
        <div class="ri-settings__input-wrapper">
          <input
            type="radio"
            id="ri-settings-reader-type-subscriber"
            name="ri-settings-reder-type-group"
            value="true"
            v-model="subscriber"
          />
          <label for="ri-settings-reader-type-subscriber">Subscriber</label>
        </div>
        <div class="ri-settings__input-wrapper">
          <input
            type="radio"
            id="ri-settings-reader-type-visitor"
            name="ri-settings-reder-type-group"
            value="false"
            v-model="subscriber"
          />
          <label for="ri-settings-reader-type-visitor">Visitor</label>
        </div>
      </div>
      <div class="ri-settings__group" v-if="onArticleDetail">
        <p class="ri-settings__group__title">
          Article version
          <HelpIconWithTooltip text="Affects reading progress statistics. Always select same version that you have opened." />
        </p>
        <div class="ri-settings__input-wrapper">
          <input
            type="radio"
            id="ri-settings-article-version-unlocked"
            name="ri-settings-article-version-group"
            value="false"
            v-model="articleLocked"
          />
          <label for="ri-settings-article-version-unlocked">Whole article</label>
        </div>
        <div class="ri-settings__input-wrapper">
          <input
            type="radio"
            id="ri-settings-article-version-locked"
            name="ri-settings-article-version-group"
            value="true"
            v-model="articleLocked"
          />
          <label for="ri-settings-article-version-locked">Locked article</label>
        </div>
      </div>
      <div class="ri-settings__group" v-if="!onArticleDetail">
        <p class="ri-settings__group__title">
          Timeframe
          <HelpIconWithTooltip text="Affects reading progress statistics by selecting different timeframe window." />
        </p>
        <div class="ri-settings__input-wrapper">
          <input
            type="radio"
            id="ri-settings-timeframe-concurrents"
            name="ri-settings-article-version-group"
            :value="10*60*1000"
            v-model="timeframe"
          />
          <label for="ri-settings-timeframe-concurrents">Concurrents</label>
        </div>
        <div class="ri-settings__input-wrapper">
          <input
            type="radio"
            id="ri-settings-timeframe-1h"
            name="ri-settings-article-version-group"
            :value="60*60*1000"
            v-model="timeframe"
          />
          <label for="ri-settings-timeframe-1h">1 hour</label>
        </div>
        <div class="ri-settings__input-wrapper">
          <input
            type="radio"
            id="ri-settings-timeframe-4h"
            name="ri-settings-article-version-group"
            :value="4*60*60*1000"
            v-model="timeframe"
          />
          <label for="ri-settings-timeframe-4h">4 hours</label>
        </div>
      </div>
    </div>
    <button class="ri-settings__toggle" @click="collapsed = !collapsed">
      <svg
        xmlns="http://www.w3.org/2000/svg"
        x="0px"
        y="0px"
        width="24"
        height="24"
        viewBox="0 0 24 24"
        :class="{'collapsed': collapsed}"
      >
        <path
          d="M 12 4.3886719 C 11.61325 4.3886719 11.226625 4.5006094 10.890625 4.7246094 L 3.7402344 9.4902344 C 3.2772344 9.7992344 3 10.319 3 10.875 C 3 12.204 4.4818906 12.996766 5.5878906 12.259766 L 12 7.984375 L 18.412109 12.259766 C 19.518109 12.996766 21 12.204 21 10.875 C 21 10.318 20.721766 9.7992344 20.259766 9.4902344 L 13.109375 4.7246094 C 12.773375 4.5006094 12.38675 4.3886719 12 4.3886719 z M 12 10.720703 C 11.61325 10.720703 11.226625 10.832641 10.890625 11.056641 L 3.7402344 15.822266 C 3.2772344 16.131266 3 16.651031 3 17.207031 C 3 18.536031 4.4818906 19.328797 5.5878906 18.591797 L 12 14.316406 L 18.412109 18.59375 C 19.518109 19.33075 21 18.536031 21 17.207031 C 21 16.650031 20.721766 16.131266 20.259766 15.822266 L 13.109375 11.056641 C 12.773375 10.832641 12.38675 10.720703 12 10.720703 z"
        />
      </svg>
      {{ collapsed ? 'Show' : 'Hide'}} settings
      <svg
        xmlns="http://www.w3.org/2000/svg"
        x="0px"
        y="0px"
        width="24"
        height="24"
        viewBox="0 0 24 24"
        :class="{'collapsed': collapsed}"
      >
        <path
          d="M 12 4.3886719 C 11.61325 4.3886719 11.226625 4.5006094 10.890625 4.7246094 L 3.7402344 9.4902344 C 3.2772344 9.7992344 3 10.319 3 10.875 C 3 12.204 4.4818906 12.996766 5.5878906 12.259766 L 12 7.984375 L 18.412109 12.259766 C 19.518109 12.996766 21 12.204 21 10.875 C 21 10.318 20.721766 9.7992344 20.259766 9.4902344 L 13.109375 4.7246094 C 12.773375 4.5006094 12.38675 4.3886719 12 4.3886719 z M 12 10.720703 C 11.61325 10.720703 11.226625 10.832641 10.890625 11.056641 L 3.7402344 15.822266 C 3.2772344 16.131266 3 16.651031 3 17.207031 C 3 18.536031 4.4818906 19.328797 5.5878906 18.591797 L 12 14.316406 L 18.412109 18.59375 C 19.518109 19.33075 21 18.536031 21 17.207031 C 21 16.650031 20.721766 16.131266 20.259766 15.822266 L 13.109375 11.056641 C 12.773375 10.832641 12.38675 10.720703 12 10.720703 z"
        />
      </svg>
    </button>
  </div>
</template>

<script type="text/javascript">
import EventHub from "./EventHub";
import AnimatedInteger from "./dashboard/AnimatedInteger";
import HelpIconWithTooltip from "./HelpIconWithTooltip";

export default {
  name: "iota-settings",
  props: {
    onArticleDetail: {
      type: Boolean,
      required: true
    }
  },
  components: { AnimatedInteger, HelpIconWithTooltip },
  data: () => ({
    collapsed: true,
    deviceType: "all",
    articleLocked: "false",
    subscriber: "true",
    timeframe: 10 * 60 * 1000,
    allConcurrents: 0,
    pageConcurrents: 0
  }),
  created: function() {
    EventHub.$on("all-concurrents-changed", this.updateAllConcurrents);
    EventHub.$on("page-concurrents-changed", this.updatePageConcurrents);
  },
  methods: {
    fireEventToRefetchData() {
      EventHub.$emit(
        "metrics-settings-changed",
        this.deviceType,
        this.articleLocked,
        this.subscriber,
        this.timeframe
      );
    },
    updateAllConcurrents(concurrents) {
      this.allConcurrents = concurrents;
    },
    updatePageConcurrents(concurrents) {
      this.pageConcurrents = concurrents;
    }
  },
  watch: {
    deviceType() {
      this.fireEventToRefetchData();
    },
    articleLocked() {
      this.fireEventToRefetchData();
    },
    subscriber() {
      this.fireEventToRefetchData();
    },
    timeframe() {
      this.fireEventToRefetchData();
    }
  }
};
</script>
