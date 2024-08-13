<template>
  <span class="ri-help-icon-with-tooltip">
      <span v-bind:title="text">
        <svg height="12" width="12" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg">
          <path
            d="M6.88 11.06V13h2.35v-1.94zM8 16A8 8 0 1 1 8 0a8 8 0 0 1 0 16zM5.57 3.51C4.586 4.043 4.063 4.948 4 6.224h2.28c0-.372.128-.73.383-1.075s.687-.517 1.298-.517c.62 0 1.048.14 1.282.42.234.28.35.59.35.93 0 .295-.105.566-.315.812l-.457.397-.576.383c-.568.376-.92.708-1.058.997-.136.289-.22.811-.252 1.568h2.13c.005-.358.04-.622.103-.792.1-.27.302-.504.607-.706l.56-.369c.568-.376.952-.685 1.152-.927.342-.398.513-.889.513-1.47 0-.95-.393-1.666-1.18-2.15C10.035 3.242 9.048 3 7.859 3c-.905 0-1.667.17-2.288.51zm0 0"
            fill="#666"
            fill-rule="evenodd"
          />
        </svg>
      </span>
  </span>
</template>

<script type="text/javascript">
export default {
  name: "help-icon-with-tooltip",
  props: {
    text: {
      type: String,
      required: true
    }
  },
};
</script>
