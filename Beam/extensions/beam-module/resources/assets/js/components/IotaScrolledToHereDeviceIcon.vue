<style lang="scss" scoped>
.ri-scrolled-to-here {
  &__mouse-icon {
    height: 20px;
    margin-right: 20px;
  }
  &__touch-icon {
    height: 20px;
    margin-right: 18px;
    margin-left: -3px;
  }
}
</style>

<template>
  <div>
    <svg
      viewBox="-0.3 -0.6 19 27"
      xmlns="http://www.w3.org/2000/svg"
      class="ri-scrolled-to-here__mouse-icon"
      v-if="device === 'all' || device === 'Computer'"
    >
      <path
        clip-rule="evenodd"
        d="M11 6.6v2.6c0 1-.9 1.8-2 1.8s-2-.8-2-1.8V6.6c0-1 .9-1.8 2-1.8 1.1.1 2 .9 2 1.8zM9 25.9c-5 0-9-4-9-9v-8C0 4.3 3.5.5 8 0v3.2c-1.2.4-2 1.6-2 2.9v3.7c0 1.7 1.3 3 3 3s3-1.4 3-3V6.1c0-1.3-.8-2.4-2-2.9V0c4.5.5 8 4.3 8 8.9v8c0 5-4 9-9 9z"
        fill="#fff"
        fill-rule="evenodd"
      />
    </svg>
    <svg
      viewBox="0 0 512 512"
      class="ri-scrolled-to-here__touch-icon"
      v-if="device === 'Phone' || device === 'Tablet'"
      xmlns="http://www.w3.org/2000/svg"
    >
      <g id="Layer_2">
        <path
          fill="#fff"
          d="M382.77 258.23h-85.41v-89.22c0-27.92-22.64-50.56-50.56-50.56s-50.56 22.64-50.56 50.56v157.87l-35.69-29.91c-21.4-17.93-53.29-15.13-71.22 6.27-17.94 21.4-15.13 53.29 6.27 71.23l100.63 84.34 11.57 9.69h226.66V309.93c.01-28.55-23.13-51.7-51.69-51.7z"
        />
        <path
          fill="#fff"
          d="M130.89 226.41c1.82 0 3.67-.31 5.48-.97 8.3-3.03 12.58-12.21 9.55-20.51-4.33-11.86-6.52-24.35-6.52-37.11 0-59.73 48.59-108.32 108.32-108.32s108.32 48.59 108.32 108.32c0 10.69-1.55 21.24-4.6 31.36-2.55 8.46 2.23 17.39 10.69 19.94 8.46 2.56 17.39-2.23 19.94-10.69 3.96-13.12 5.97-26.79 5.97-40.61 0-77.37-62.95-140.32-140.32-140.32-77.37 0-140.32 62.95-140.32 140.32 0 16.51 2.84 32.68 8.46 48.07 2.36 6.49 8.5 10.52 15.03 10.52z"
        />
      </g>
    </svg>
  </div>
</template>

<script type="text/javascript">
export default {
  name: "iota-scrolled-to-here-device-icon",
  props: {
    device: {
      required: true,
      validator: val => ["all", "Computer", "Phone", "Tablet"].includes(val)
    }
  }
};
</script>
