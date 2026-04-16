import Vue from "vue"
import Vue2Filters from "vue2-filters";

Vue.filter("formatNumber", function (value) {
    if (!value) return '';
    return Number.parseInt(value).toLocaleString('en')
});

Vue.filter('roundNumber', n => parseFloat(n).toFixed(2));

Vue.filter('choices', (condition, a1, a2) => condition ? a1 : a2);

Vue.use(Vue2Filters);