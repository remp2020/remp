<style type="text/css">
    .remp-iota-template {
        background-color: #efefef;
        color: black;
        padding: 10px;
        margin:5px 0 10px;
    }
    .remp-iota-template-counts {
        display: inline-block;
    }
</style>

<template>
    <div class="remp-iota-template">
        <div v-if="sortedPageviewRanges.length > 0" class="remp-iota-template-category">
            <div class="remp-iota-template-category-title">REMP pageview stats:</div>
            <div class="remp-iota-template-category-stats">
                <div v-for="range in sortedPageviewRanges">
                    {{ range }}h: {{ pageviewStats[range].toFixed(2) }}
                </div>
            </div>
        </div>

        <div v-if="sortedRevenueRanges.length > 0" class="remp-iota-template-category">
            <div class="remp-iota-template-category-title">REMP revenue stats:</div>
            <div class="remp-iota-template-category-stats">
                <div v-for="range in sortedRevenueRanges">
                    {{ range }}h: {{ revenueStats[range].toFixed(2) }} &euro;
                    <div class="remp-iota-template-counts" v-if="countStats[range]">({{ countStats[range] }})</div>
                </div>
            </div>
        </div>
    </div>
</template>

<script type="text/javascript">
    import Axios from "axios"
    import EventHub from "./EventHub"

    export default {
        name: 'conversion-template',
        props: {
            articleId: {
                type: String,
                required: true,
            }
        },
        data: () => ({
            revenueStats: {},
            countStats: {},
            pageviewStats: {},

            revenueRanges: [],
            pageviewRanges: [],
        }),
        created: function() {
            EventHub.$on("content-conversions-revenue-changed", this.updateRevenueStats)
            EventHub.$on("content-conversions-counts-changed", this.updateCountStats)
            EventHub.$on("content-pageviews-changed", this.updatePageviewStats)
        },
        computed: {
            sortedRevenueRanges: function() {
                return this.revenueRanges.slice().sort((a, b) => a - b);
            },
            sortedPageviewRanges: function() {
                return this.pageviewRanges.slice().sort((a, b) => a - b);
            }
        },
        methods: {
            updateRevenueStats: function(hourRange, sums) {
                this.revenueRanges.push(hourRange);
                if (sums === null || !sums[this.articleId]) {
                    this.$set(this.revenueStats, hourRange, 0);
                    return;
                }
                this.$set(this.revenueStats, hourRange, sums[this.articleId]);
            },
            updateCountStats: function(hourRange, counts) {
                if (counts === null || !counts[this.articleId]) {
                    this.$set(this.countStats, hourRange, 0);
                    return;
                }
                this.$set(this.countStats, hourRange, counts[this.articleId]);
            },
            updatePageviewStats: function(hourRange, counts) {
                if (counts === null || !counts[this.articleId]) {
                    this.$set(this.pageviewStats, hourRange, 0);
                    return;
                }
                this.$set(this.pageviewStats, hourRange, counts[this.articleId]);
            },
        },


    }
</script>
