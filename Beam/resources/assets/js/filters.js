import Vue from "vue"

Vue.filter("formatNumber", function (value) {
    if (!value) return ''
    return Number.parseInt(value).toLocaleString('en')
})