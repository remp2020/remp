<style type="text/css">
    .ri_box, .ri_box * { display: inline-block; margin: 0; padding: 0; border: 0; outline: 0; }
    .ri_box { margin: 8px 0 }
    .ri_box_line, .ri_box_logo svg { display: block; }
    .ri_box { display: block; position: relative; z-index: 999999; line-height: 16px; font-size: 12px; font-weight: normal; background: #ffffff; color: #333333; }
    .ri_box_line { padding: 2px 4px; clear: both; background: #eeeeee; }
    .ri_box_line:nth-child(even) { background: transparent; }
    .ri_box_small, .ri_box_key { font-size: 10px; font-weight: bold; color: #666666; }
    .ri_box_title { font-weight: bold; color: #000000; }
    .ri_box_logo { vertical-align: top; }
    .ri_box_logo svg { width: 16px; height: 16px; }
    .ri_box *[title] { border-bottom: 1px #cccccc dotted; }
    .ri_box_green { color: #009900; }
    .ri_box_red { color: #990000; }
    .ri_box_blue { color: #000099; }
    .ri_box_blue { color: #000099; }
    .ri_box__collapsed .ri_box__collapsed_show, .ri_box__expanded .ri_box__expanded_show { display: block; }
    .ri_box__collapsed .ri_box__collapsed_hide, .ri_box__expanded .ri_box__expanded_hide  { display: none; }
</style>

<template>
    <div>
        <div class="ri_box ri_box__expanded" :data-article-id="articleId">
            <div v-if="sortedRevenueRanges.length > 0" class="ri_box_line">
                <div class="ri_box_logo">
                    <svg viewBox="0 0 71.61 70.88"><polygon fill="black" points="35.8 2.74 16.91 13.65 23.2 17.29 35.8 10.02 54.7 20.93 35.8 31.84 35.8 31.84 10.61 17.29 10.61 53.59 16.91 57.23 16.91 28.2 35.8 39.11 35.8 39.11 35.8 39.11 35.8 39.11 61 24.57 61 17.29 35.8 2.74" /><polygon fill="black" points="23.2 53.66 23.2 60.93 35.8 68.14 61 53.59 61 46.32 35.8 60.86 23.2 53.66"/><polygon fill="black" points="35.8 46.28 23.2 39.08 23.2 46.35 35.8 53.55 35.8 53.66 61 39.11 61 31.84 35.8 46.39 35.8 46.28"/></svg>
                </div>
                <div class="ri_box_title">Revenue</div>
                <div v-for="range in sortedRevenueRanges" class="ri_box_item">
                    <div class="ri_box_key">{{ range }}H</div>
                    <div class="ri_box_value">
                        {{ revenueStats[range].toFixed(2) }}&euro;
                        <span v-if="countStats[range]" class="ri_box_small">({{ countStats[range] }})</span>&nbsp; 
                    </div>
                </div>
            </div>

            <div v-if="sortedPageviewRanges.length > 0" class="ri_box_line">
                <div class="ri_box_title">Pageviews</div>
                <div v-for="range in sortedPageviewRanges" class="ri_box_item">
                    <div class="ri_box_key">{{ range }}H</div>
                    <div class="ri_box_value">
                        {{ pageviewStats[range] }}&nbsp;
                    </div>
                </div>
            </div>

            <div v-if="sortedTitleVariants.length > 0" class="ri_box_line">
                <div v-for="variant in sortedTitleVariants" class="ri_box_item">
                    <div class="ri_box_title">Title {{ variant }}</div>
                    <div v-for="(count,range) in titleVariantStats[variant]">
                        <div class="ri_box_key">{{ range }}H</div>
                        <div class="ri_box_value">
                            {{ count }}&nbsp;
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</template>

<script type="text/javascript">
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
            titleVariantStats: {},

            revenueRanges: [],
            pageviewRanges: [],
            titleVariants: [],
        }),
        created: function() {
            EventHub.$on("content-conversions-revenue-changed", this.updateRevenueStats)
            EventHub.$on("content-conversions-counts-changed", this.updateCountStats)
            EventHub.$on("content-pageviews-changed", this.updatePageviewStats)
            EventHub.$on("content-title-variants-changed", this.updateTitleVariantStats)
        },
        computed: {
            sortedRevenueRanges: function() {
                return this.revenueRanges.slice().sort((a, b) => a - b);
            },
            sortedPageviewRanges: function() {
                return this.pageviewRanges.slice().sort((a, b) => a - b);
            },
            sortedTitleVariants: function() {
                return this.titleVariants.slice().sort((a, b) => a - b);
            },
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
                this.pageviewRanges.push(hourRange);
                if (counts === null || !counts[this.articleId]) {
                    this.$set(this.pageviewStats, hourRange, 0);
                    return;
                }
                this.$set(this.pageviewStats, hourRange, counts[this.articleId]);
            },
            updateTitleVariantStats: function(hourRange, variant, counts) {
                if (variant === "") {
                    // no A/B test data were tracked, ignoring this section
                    return;
                }

                if (this.titleVariants.indexOf(variant) === -1) {
                    this.titleVariants.push(variant);
                }

                let val = this.titleVariantStats[variant] || {};
                if (counts === null || !counts[this.articleId]) {
                    val[hourRange] = 0
                } else {
                    val[hourRange] = counts[this.articleId]
                }
                this.titleVariantStats[variant] = val;
                this.$forceUpdate();
            },
        },


    }
</script>
