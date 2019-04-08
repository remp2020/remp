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

global.autosize = require('autosize');

global.Vue = require('vue');

global.moment = require('moment');

Vue.use(require("vuex"));
require("./filters");

global.RuleOcurrences = require("./components/RuleOcurrences.vue").default;
global.RecurrenceSelector = require("./components/RecurrenceSelector.vue").default;
global.DashboardRoot = require("./components/dashboard/DashboardRoot.vue").default;
global.ArticleHistogram = require("./components/dashboard/ArticleHistogram.vue").default;
global.UserPath = require("./components/userpath/UserPath.vue").default;
global.GoogleAnalyticsReportingHistogram = require("./components/dashboard/GoogleAnalyticsReportingHistogram.vue").default;
global.DashboardStore = require("./components/dashboard/store.js").default;

global.SmartRangeSelector = require("remp/js/components/SmartRangeSelector.vue").default;
global.DateFormatter = require("remp/js/components/DateFormatter.vue").default;
global.FormValidator = require("remp/js/components/FormValidator").default;

global.$.ajaxSetup({
    headers:
        { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
});