<template>
  <div class="ri-settings" id="campaign-debugger" ref="debugger">
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
      Campaign debugger
    </div>
    <div class="form ri-settings__group" style="padding-top: 0" ref="debuggerForm">
      <p class="ri-settings__group__title">
        Debug parameters
        <HelpIconWithTooltip text="Specify which campaign to debug. If campaign public ID is set, only this campaign will be display." />
      </p>
      <input v-model="form.key" type="text" placeholder="Debug key">
      <input v-model="form.campaignPublicId" placeholder="Campaign public ID">
      <input v-model="form.userId" placeholder="User ID">
      <input v-model="form.referer" placeholder="Referer">
      <button @click="setAndReload">Set and reload campaigns</button>
    </div>
    <div class="ri-settings__group" style="padding-top: 0" v-if="(errors.length + messages.length) > 0 ">
      <p class="ri-settings__group__title">
        Showtime result
      </p>
      <div v-if="errors.length > 0">
        <ul>
          <li style="color:red" v-for="error in errors">{{ error }}</li>
        </ul>
      </div>
      <div v-if="messages.length > 0">
        <ul>
          <li style="color:green" v-for="msg in messages">{{ msg }}</li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script type="text/javascript">

import interact from 'interactjs';
import HelpIconWithTooltip from "./HelpIconWithTooltip";

let debuggerPosition = {x: 0, y: 0}

export default {
  components: {HelpIconWithTooltip},
  created() {
    const debuggerKey = localStorage.getItem('campaign-debugger-key');
    if (debuggerKey) {
      this.form.key = debuggerKey;
    }

    const storedPosition = localStorage.getItem("campaign-debugger-position");
    if (storedPosition) {
      debuggerPosition = JSON.parse(storedPosition);
    }
  },
  mounted() {
    const userId = window.remplib.getUserId();
    if (userId) {
      this.form.userId = userId;
    }

    window.addEventListener("campaign_showtime_response", (e) => {
      this.processShowtimeResponse(e.detail);
    });

    this.$refs.debugger.style.transform =
      `translate(${debuggerPosition.x}px, ${debuggerPosition.y}px)`;

    interact(this.$refs.debugger).draggable({
      allowFrom: '.ri-settings__header',
      // keep the element within the area of it's parent

      listeners: {
        move(event) {
          debuggerPosition.x += event.dx;
          debuggerPosition.y += event.dy;

          event.target.style.transform =
            `translate(${debuggerPosition.x}px, ${debuggerPosition.y}px)`;

          localStorage.setItem(
            "campaign-debugger-position",
            JSON.stringify(debuggerPosition)
          );
        },
      }
    });
  },
  data() {
    return {
      form: {
        key: null,
        userId: null,
        campaignPublicId: null,
        referer: null,
      },
      errors: [],
      messages: [],
    };
  },
  methods: {
    processShowtimeResponse(response) {
      this.errors = [];
      this.messages = [];
      if (response.evaluationMessages && response.evaluationMessages.length > 0) {
        this.messages = response.evaluationMessages;
        return;
      }

      if (this.form.key && this.form.campaignPublicId) {
        if (response.data.length > 0) {
          this.messages.push("Campaign [" + this.form.campaignPublicId + "] successfully displayed");
        } else if (response.suppressedBanners.length > 0) {
          for (const b of response.suppressedBanners) {
            this.errors.push("Campaign [" + b.campaign_public_id +
              "] was suppressed by campaign [" + b.suppressed_by_campaign_public_id + "], because they occupy the same position");
          }
        } else {
          this.errors.push("Campaign [" + this.form.campaignPublicId + "] returned no data");
        }
      }
    },
    setAndReload() {
      localStorage.setItem("campaign-debugger-key", this.form.key);
      let rempConfig = window.remplib.getConfig();
      rempConfig.campaign.debug = this.form;
      remplib.campaign.init(rempConfig);
    }
  },
}
</script>

<style lang="scss" scoped>
.ri-settings {
  font-family: sans-serif;
  font-weight: 400;
  background-color: white;
  box-shadow: 5px 9px 30px 0 rgba(168, 173, 187, 0.6);
  border-radius: 5px;
  width: 260px;
  position: fixed;
  top: 0;
  left: 0;
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
      margin-bottom: 12px;
      margin-top: 12px;
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

  button {
    align-items: center;
    border: 0;
    text-align: center;
    width: 100%;
    line-height: 30px;
    background: #3874d0;
    color: white;
    font-size: 15px;
    cursor: pointer;
  }

  ul {
    padding: 0 0 0 20px;
    margin: 0;
  }
  ul li {
    font-size: 0.8rem;
  }

  input {
    width: 100%;
    display: block;
    padding: 10px;
    margin-bottom: 10px;
    border: 1px solid #ccc;
    border-radius: 0px;
    box-sizing: border-box;
  }

  input::placeholder {
    opacity: 0.7;
  }

}
</style>
