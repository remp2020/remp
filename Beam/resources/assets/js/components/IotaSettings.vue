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
    top: 4px;
    left: 4px;
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
      Metrics settings
    </div>
    <div class="ri-settings__group">
      <p class="ri-settings__group__title">Device type</p>
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
    <div class="ri-settings__group" v-if="onArticleDetail">
      <p class="ri-settings__group__title">Article version</p>
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
    <div class="ri-settings__group">
      <p class="ri-settings__group__title">Reader type</p>
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
  </div>
</template>

<script type="text/javascript">
import EventHub from "./EventHub";

export default {
  name: "iota-settings",
  props: {
    onArticleDetail: {
      type: Boolean,
      required: true
    }
  },
  data: () => ({
    deviceType: "all",
    articleLocked: "false",
    subscriber: "all"
  }),
  methods: {
    fireEventToRefetchData() {
      EventHub.$emit(
        "metrics-settings-changed",
        this.deviceType,
        this.articleLocked,
        this.subscriber
      );
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
    }
  }
};
</script>
