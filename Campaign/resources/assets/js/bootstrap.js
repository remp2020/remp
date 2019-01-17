global.$ = global.jQuery = require('jquery');
global.Vue = require('vue');
global.moment = require('moment');

$.ajaxSetup({
    headers:
        { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
});

global.Toggle = require("remp/js/components/Toggle.vue");
global.noUiSlider = require("nouislider/distribute/nouislider.js")

global.SmartRangeSelector = require("remp/js/components/SmartRangeSelector.vue");

global.CampaignStatsRoot = require("./components/CampaignStatsRoot.vue");

global.CampaignComparisonRoot = require("./components/CampaignComparisonRoot.vue");

global.salvattore = require("salvattore");
