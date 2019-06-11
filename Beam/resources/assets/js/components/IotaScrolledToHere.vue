<style lang="scss">
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
  &__mouse-icon {
    height: 20px;
    margin-right: 20px;
  }
}
</style>

<template>
  <div class="ri-scrolled-to-here">
    <svg
      enable-background="new -0.3 -0.6 19 27"
      version="1.1"
      viewBox="-0.3 -0.6 19 27"
      xml:space="preserve"
      xmlns="http://www.w3.org/2000/svg"
      xmlns:xlink="http://www.w3.org/1999/xlink"
      class="ri-scrolled-to-here__mouse-icon"
    >
      <defs></defs>
      <path
        clip-rule="evenodd"
        d="M11,6.6v2.6c0,1-0.9,1.8-2,1.8c-1.1,0-2-0.8-2-1.8V6.6  c0-1,0.9-1.8,2-1.8C10.1,4.9,11,5.7,11,6.6z M9,25.9c-5,0-9-4-9-9v-8C0,4.3,3.5,0.5,8,0v3.2C6.8,3.6,6,4.8,6,6.1v3.7  c0,1.7,1.3,3,3,3s3-1.4,3-3V6.1c0-1.3-0.8-2.4-2-2.9V0c4.5,0.5,8,4.3,8,8.9v8C18,21.9,14,25.9,9,25.9z"
        fill="#fff"
        fill-rule="evenodd"
      ></path>
    </svg>
    <span class="ri-scrolled-to-here__caption">
      <strong>{{ scrolledToHerePercent }}%</strong> of desktop users scrolled to here
    </span>
  </div>
</template>

<script type="text/javascript">
import EventHub from "./EventHub";

export default {
  name: "iota-scrolled-to-here",
  data: () => ({
    totalReaders: 0,
    readersWhoScrolledUpUntilThisPoint: 0,
    histogram: []
  }),
  created: function() {
    EventHub.$on("read-progress-data-changed", this.receiveReadProgressData);
    window.addEventListener(
      "scroll_progress",
      this.recalculateRemainingReaders
    );
  },
  computed: {
    scrolledToHerePercent() {
      return Math.round(
        (this.readersWhoScrolledUpUntilThisPoint * 100) / this.totalReaders
      );
    }
  },
  methods: {
    receiveReadProgressData(totalReaders, histogram) {
      this.totalReaders = totalReaders;
      this.histogram = histogram;
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
    }
  }
};
</script>
