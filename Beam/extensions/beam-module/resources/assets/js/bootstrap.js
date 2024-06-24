const global = require('global');

// vendor libraries we need to use outside of JS files
global.$ = global.jQuery = require('jquery');

import Vue from 'vue';
global.Vue = Vue;

global.moment = require('moment');
global.autosize = require('autosize');
global.Waves = require('node-waves');

// components used within blade templates
global.RuleOcurrences = require("./components/RuleOcurrences.vue").default;
global.RecurrenceSelector = require("./components/RecurrenceSelector.vue").default;
global.DashboardRoot = require("./components/dashboard/DashboardRoot.vue").default;
global.ArticleDetails = require("./components/dashboard/ArticleDetails.vue").default;
global.UserPath = require("./components/userpath/UserPath.vue").default;
global.ConversionsSankeyDiagram = require("./components/userpath/ConversionsSankeyDiagram.vue").default;
global.DashboardStore = require("./components/dashboard/store.js").default;

global.SmartRangeSelector = require("@remp/js-commons/js/components/SmartRangeSelector.vue").default;
global.vSelect = require("@remp/js-commons/js/components/vSelect.vue").default;
global.DateFormatter = require("@remp/js-commons/js/components/DateFormatter.vue").default;
global.FormValidator = require("@remp/js-commons/js/components/FormValidator").default;

global.$.ajaxSetup({
    headers:
        { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
});

require('bootstrap');
require('bootstrap-select');
require('bootstrap-notify');

require('datatables.net');
require('datatables.net-rowgroup');
require('datatables.net-responsive');

require('datatables.net-buttons/js/dataTables.buttons.min');
require('datatables.net-buttons/js/buttons.colVis.min');
require('datatables.net-buttons/js/buttons.flash.min');
require('datatables.net-buttons/js/buttons.html5.min');
require('datatables.net-buttons/js/buttons.print.min');

require('eonasdan-bootstrap-datetimepicker');
require('jquery-placeholder');

require("vuex");
require("./filters");
