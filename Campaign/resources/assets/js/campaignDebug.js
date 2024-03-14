import Vue from "vue";
import CampaignDebugger from  "./components/CampaignDebugger.vue"

(function init() {
  if (document.getElementById("campaign-debugger")) {
    // already loaded
    return;
  }

  document.querySelector('body')
      .insertAdjacentHTML('afterbegin', '<div id="debugger-app">');

  var app = new Vue({
    el: "#debugger-app",
    template: '<CampaignDebugger/>',
    components: { CampaignDebugger },
  });
})();




