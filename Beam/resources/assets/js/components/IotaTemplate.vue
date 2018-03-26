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
                    <div class="ri_box_key">{{ range.label }}</div>
                    <div class="ri_box_value">
                        {{ revenueStats[range.minutes].toFixed(2) }}&euro;
                        <span v-if="countStats[range.minutes]" class="ri_box_small">({{ countStats[range.minutes] }})</span>&nbsp;
                    </div>
                </div>
            </div>

            <div v-if="sortedPageviewRanges.length > 0" class="ri_box_line">
                <div class="ri_box_title">Pageviews</div>
                <div v-for="range in sortedPageviewRanges" class="ri_box_item">
                    <div class="ri_box_key">{{ range.label }}</div>
                    <div class="ri_box_value">
                        {{ pageviewStats[range.minutes] }}&nbsp;
                    </div>
                </div>
            </div>

            <div v-if="sortedTitleVariants.length > 0" class="ri_box_line">
                <div v-for="variant in sortedTitleVariants" class="ri_box_item">
                    <div class="ri_box_title">Title {{ variant }} (direct)</div>
                    <div v-for="range in sortedTitleVariantRanges">
                        <div class="ri_box_key">{{ range.label }}</div>
                        <div class="ri_box_value">
                            {{ titleVariantStats[variant][range.minutes] || 0 }}&nbsp;
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="sortedImageVariants.length > 0" class="ri_box_line">
                <div v-for="variant in sortedImageVariants" class="ri_box_item">
                    <div class="ri_box_title">Image {{ variant }} (direct)</div>
                    <div v-for="range in sortedImageVariantRanges">
                        <div class="ri_box_key">{{ range.label }}</div>
                        <div class="ri_box_value">
                            {{ imageVariantStats[variant][range.minutes] || 0 }}&nbsp;
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="sortedLockVariants.length > 0" class="ri_box_line">
                <div v-for="variant in sortedLockVariants" class="ri_box_item">
                    <div class="ri_box_title">Image {{ variant }} (direct)</div>
                    <div v-for="range in sortedLockVariantRanges">
                        <div class="ri_box_key">{{ range.label }}</div>
                        <div class="ri_box_value">
                            {{ lockVariantStats[variant][range.minutes] || 0 }}&nbsp;
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
        name: 'iota-article-stats',
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
            imageVariantStats: {},
            lockVariantStats: {},

            revenueRanges: {},
            pageviewRanges: {},
            titleVariantRanges: {},
            imageVariantRanges: {},
            lockVariantRanges: {},

            titleVariants: [],
            imageVariants: [],
            lockVariants: [],
        }),
        created: function() {
            EventHub.$on("content-conversions-revenue-changed", this.updateRevenueStats);
            EventHub.$on("content-conversions-counts-changed", this.updateCountStats);
            EventHub.$on("content-pageviews-changed", this.updatePageviewStats);
            EventHub.$on("content-variants-changed", this.updateVariantStats);
        },
        computed: {
            sortedRevenueRanges: function() {
                return Object.values(this.revenueRanges).slice().sort((a, b) => a.minutes - b.minutes);
            },
            sortedPageviewRanges: function() {
                return Object.values(this.pageviewRanges).slice().sort((a, b) => a.minutes - b.minutes);
            },
            sortedTitleVariantRanges: function() {
                return Object.values(this.titleVariantRanges).slice().sort((a, b) => a.minutes - b.minutes);
            },
            sortedImageVariantRanges: function() {
                return Object.values(this.imageVariantRanges).slice().sort((a, b) => a.minutes - b.minutes);
            },
            sortedLockVariantRanges: function() {
                return Object.values(this.lockVariantRanges).slice().sort((a, b) => a.minutes - b.minutes);
            },
            sortedTitleVariants: function() {
                return this.titleVariants.slice().sort((a, b) => a - b);
            },
            sortedImageVariants: function() {
                return this.imageVariants.slice().sort((a, b) => a - b);
            },
            sortedLockVariants: function() {
                return this.lockVariants.slice().sort((a, b) => a - b);
            },
        },
        methods: {
            updateRevenueStats: function(range, sums) {
                this.$set(this.revenueRanges, range.minutes, range);
                if (sums === null || !sums[this.articleId]) {
                    this.$set(this.revenueStats, range.minutes, 0);
                    return;
                }
                this.$set(this.revenueStats, range.minutes, sums[this.articleId]);
            },
            updateCountStats: function(range, counts) {
                if (counts === null || !counts[this.articleId]) {
                    this.$set(this.countStats, range.minutes, 0);
                    return;
                }
                this.$set(this.countStats, range.minutes, counts[this.articleId]);
            },
            updatePageviewStats: function(range, counts) {
                this.$set(this.pageviewRanges, range.minutes, range);
                if (counts === null || !counts[this.articleId]) {
                    this.$set(this.pageviewStats, range.minutes, 0);
                    return;
                }
                this.$set(this.pageviewStats, range.minutes, counts[this.articleId]);
            },
            updateVariantStats: function(variantTypes, range, counts) {
                for (const variantType of variantTypes) {
                    switch (variantType) {
                        case "title_variant":
                            return this.updateVariants(this.titleVariantRanges, this.titleVariants, this.titleVariantStats, range, counts[variantType]);
                        case "image_variant":
                            return this.updateVariants(this.imageVariantRanges, this.imageVariants, this.imageVariantStats, range, counts[variantType]);
                        case "lock_variant":
                            return this.updateVariants(this.lockVariantRanges, this.lockVariants, this.lockVariantStats, range, counts[variantType]);
                    }
                }
            },
            updateVariants: function(variantRanges, variants, variantStats, range, counts) {
                this.$set(variantRanges, range.minutes, range);

                for (let variant in counts[this.articleId]) {
                    if (!counts[this.articleId].hasOwnProperty(variant)) {
                        continue;
                    }
                    if (variants.indexOf(variant) === -1) {
                        variants.push(variant);
                    }

                    let val = variantStats[variant] || {};
                    if (counts === null || !counts[this.articleId][variant]) {
                        val[range.minutes] = 0
                    } else {
                        val[range.minutes] = counts[this.articleId][variant]
                    }
                    variantStats[variant] = val;
                }
                this.$forceUpdate();
            },
        },


    }
</script>
