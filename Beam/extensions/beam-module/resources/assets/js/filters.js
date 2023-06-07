import Vue from "vue"
import Vue2Filters from "vue2-filters";

Vue.filter("formatNumber", function (value) {
    if (!value) return '';
    return Number.parseInt(value).toLocaleString('en')
});

Vue.use(Vue2Filters);