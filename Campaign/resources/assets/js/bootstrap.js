var global = require('global');

require('datatables.net');
require('datatables.net-rowgroup');
require('datatables.net-responsive');

global.$ = global.jQuery = require('jquery');

require('bootstrap');
require('bootstrap-select');
require('bootstrap-notify');

require('eonasdan-bootstrap-datetimepicker');
require('jquery-placeholder');
require('./farbtastic');

global.autosize = require('autosize');

global.Vue = require('vue');

global.moment = require('moment');

global.salvattore = require("salvattore");

global.Chart = require("chart.js");

global.Waves = require("node-waves");

global.noUiSlider = require("nouislider/distribute/nouislider.js");

global.SmartRangeSelector = require("remp/js/components/SmartRangeSelector.vue").default;
global.Toggle = require("remp/js/components/Toggle.vue").default;
global.CampaignStatsRoot = require("./components/CampaignStatsRoot.vue").default;
global.CampaignComparison = require("./components/CampaignComparison.vue").default;

global.$.ajaxSetup({
    headers:
        { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
});