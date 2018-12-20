import Vue2Filters from 'vue2-filters'
import Vuex from 'vuex'
import "./filters"

global.$ = global.jQuery = require('jquery');

global.Vue = require('vue');
Vue.use(Vue2Filters)
Vue.use(Vuex)

global.moment = require('moment');

$.ajaxSetup({
    headers:
        { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
});

global.SmartRangeSelector = require("remp/js/components/SmartRangeSelector.vue");
global.DateTimePicker = require("remp/js/components/DateTimePickerWrapper.vue");
global.DateFormatter = require("remp/js/components/DateFormatter.vue");
global.RecurrenceSelector = require("./components/RecurrenceSelector.vue");
global.RuleOcurrences = require("./components/RuleOcurrences.vue");
global.FormValidator = require("remp/js/components/FormValidator");
global.DashboardRoot = require("./components/dashboard/DashboardRoot.vue");
global.ArticleHistogram = require("./components/dashboard/ArticleHistogram.vue");
global.UserPath = require("./components/userpath/UserPath.vue");
global.DashboardStore = require("./components/dashboard/store.js").default