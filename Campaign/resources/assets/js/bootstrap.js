const global = require('global');

// vendor libraries we need to use outside of JS files
import Vue from 'vue';
global.Vue = Vue;

global.$ = global.jQuery = require('jquery');

global.moment = require('moment');
global.autosize = require('autosize');
global.Waves = require('node-waves');
global.salvattore = require("salvattore");
global.noUiSlider = require("nouislider/distribute/nouislider.js");
global.Chart = require("chart.js");

global.SmartRangeSelector = require("@remp/js-commons/js/components/SmartRangeSelector.vue").default;
global.Toggle = require("@remp/js-commons/js/components/Toggle.vue").default;
global.CampaignStatsRoot = require("./components/CampaignStatsRoot.vue").default;
global.CampaignComparison = require("./components/CampaignComparison.vue").default;

global.$.ajaxSetup({
    headers:
        { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
});

require('bootstrap');
require('bootstrap-select');
require('bootstrap-notify');
require('bootstrap-sweetalert');

require('datatables.net');
require('datatables.net-rowgroup');
require('datatables.net-responsive');

require('eonasdan-bootstrap-datetimepicker');
require('jquery-placeholder');
require('./farbtastic');
