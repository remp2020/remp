global.$ = global.jQuery = require('jquery');
global.Vue = require('vue');
global.moment = require('moment');
global.Toggle = require("remp/js/components/Toggle.vue");
global.noUiSlider = require("nouislider/distribute/nouislider.js")

global.SmartRangeSelector = require("./components/SmartRangeSelector.vue");

global.CampaignStats = require("./components/CampaignStats.vue");

require("./chart.js");

global.salvattore = require("salvattore");
