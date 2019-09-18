<style lang="scss" scoped>
.ri-scrolled-to-here {
  font-family: sans-serif;
  font-weight: 400;
  font-size: 13px;
  color: white;
  background-color: #3974d1;
  position: fixed;
  bottom: 20px;
  left: 0;
  padding: 10px 20px;
  display: flex;
  align-items: center;
  z-index: 9999999;
  &:hover {
    cursor: pointer;
  }
}
</style>

<template>
  <div class="ri-scrolled-to-here" @click="toggleHistogramVisibility">
    <IotaScrolledToHereDeviceIcon :device="deviceType" />
    <span class="ri-scrolled-to-here__caption">
      <strong>{{ scrolledToHerePercent }}%</strong>
      of {{ deviceType.toLowerCase() }} users scrolled to here
    </span>
  </div>
</template>

<script type="text/javascript">
import EventHub from "./EventHub";
import IotaScrolledToHereDeviceIcon from "./IotaScrolledToHereDeviceIcon";

export default {
  name: "iota-scrolled-to-here",
  data: () => ({
    totalReaders: 0,
    readersWhoScrolledUpUntilThisPoint: 0,
    histogram: [],
    deviceType: "all"
  }),
  components: {
    IotaScrolledToHereDeviceIcon
  },
  created: function() {
    EventHub.$on("read-progress-data-changed", this.receiveReadProgressData);
    EventHub.$on("metrics-settings-changed", this.receiveDifferentDevice);
    window.addEventListener(
      "scroll_progress",
      this.recalculateRemainingReaders
    );
  },
  computed: {
    scrolledToHerePercent() {
      if (
        this.readersWhoScrolledUpUntilThisPoint === 0 &&
        this.totalReaders === 0
      ) {
        return 0;
      }
      return Math.round(
        (this.readersWhoScrolledUpUntilThisPoint * 100) / this.totalReaders
      );
    }
  },
  methods: {
    receiveDifferentDevice(deviceType, articleLocked, subscriber) {
      this.deviceType = deviceType;
    },
    receiveReadProgressData(totalReaders, histogram) {
      this.totalReaders = totalReaders;
      this.histogram = histogram;
      window.scrollBy(0, 1); // kind of hacky way to force refiring of scroll_progress event
    },
    recalculateRemainingReaders(scrollProgressEvent) {
      const readersWhoLeftUpUntilThisPoint = this.histogram.reduce(
        (counter, current) =>
          current.bucket_key <= scrollProgressEvent.detail.pageScrollRatio
            ? counter + current.value
            : counter,
        0
      );

      this.readersWhoScrolledUpUntilThisPoint =
        this.totalReaders - readersWhoLeftUpUntilThisPoint;
    },
    toggleHistogramVisibility() {
      EventHub.$emit("read-progress-histogram-toggle");
    }
  }
};
</script>
