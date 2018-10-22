<style type="text/css">
    .ri_box, .ri_box * { display: inline-block; margin: 0; padding: 0; border: 0; outline: 0; }
    .ri_box { margin: 8px 0 }
    .ri_box_line, .ri_box_logo svg { display: block; }
    .ri_box { display: block; position: relative; z-index: 999999; line-height: 16px; font-size: 12px; font-weight: normal; background: #ffffff; color: #333333; }
    .ri_box_line { padding: 2px 4px; clear: both; }
    .ri_box_line.low-color { background: #EEFBF3; }
    .ri_box_line.medium-color { background: #D3F4E0; }
    .ri_box_line.high-color { background: #9BE6BA; }
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
            <div class="ri_box_line" :class="conversionsColorClass">
                <div class="ri_box_logo">
                    <svg viewBox="0 0 71.61 70.88"><polygon fill="black" points="35.8 2.74 16.91 13.65 23.2 17.29 35.8 10.02 54.7 20.93 35.8 31.84 35.8 31.84 10.61 17.29 10.61 53.59 16.91 57.23 16.91 28.2 35.8 39.11 35.8 39.11 35.8 39.11 35.8 39.11 61 24.57 61 17.29 35.8 2.74" /><polygon fill="black" points="23.2 53.66 23.2 60.93 35.8 68.14 61 53.59 61 46.32 35.8 60.86 23.2 53.66"/><polygon fill="black" points="35.8 46.28 23.2 39.08 23.2 46.35 35.8 53.55 35.8 53.66 61 39.11 61 31.84 35.8 46.39 35.8 46.28"/></svg>
                </div>
                <div class="ri_box_title">Conversions: </div>
                <div class="ri_box_item">{{ conversions }}</div>
            </div>

            <div class="ri_box_line" :class="conversionRateColorClass">
                <div class="ri_box_title">Conversion rate: </div>
                <div class="ri_box_item">{{ conversionRate }}</div>
            </div>

            <div v-if="sortedPageviewRanges.length > 0" class="ri_box_line">
                <div class="ri_box_title">Readers: </div>
                <div v-for="range in sortedPageviewRanges" class="ri_box_item">
                    <div class="ri_box_key">{{ range.label }}</div>
                    <div class="ri_box_value">
                        {{ pageviewStats[range.label] }}&nbsp;
                    </div>
                </div>
            </div>

            <div v-if="sortedTitleVariants.length > 1" class="ri_box_line">
                <div v-for="variant in sortedTitleVariants" class="ri_box_item">
                    <div class="ri_box_title">Title {{ variant }} (direct)</div>
                    <div v-for="range in sortedTitleVariantRanges">
                        <div class="ri_box_key">{{ range.label }}</div>
                        <div class="ri_box_value">
                            {{ titleVariantStats[variant][range.label] || 0 }}&nbsp;
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="sortedImageVariants.length > 1" class="ri_box_line">
                <div v-for="variant in sortedImageVariants" class="ri_box_item">
                    <div class="ri_box_title">Image {{ variant }} (direct)</div>
                    <div v-for="range in sortedImageVariantRanges">
                        <div class="ri_box_key">{{ range.label }}</div>
                        <div class="ri_box_value">
                            {{ imageVariantStats[variant][range.label] || 0 }}&nbsp;
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script type="text/javascript">
    import EventHub from "./EventHub"
    import { rounding, CONVERSIONS_COLORING_THRESHOLD, CONVERSION_RATE_COLORING_THRESHOLD } from './dashboard/constants.js'

    function colorThresholdToClass(threshold, value) {
        if (value > threshold.high) {
            return 'high-color'
        } else if (value > threshold.medium) {
            return 'medium-color'
        } else if (value > threshold.low) {
            return 'low-color'
        } else {
            return 'no-color'
        }
    }

    export default {
        name: 'iota-article-stats',
        props: {
            articleId: {
                type: String,
                required: true,
            }
        },
        data: () => ({
            conversions: 0,
            totalPageviews: 0,

            pageviewStats: {},
            titleVariantStats: {},
            imageVariantStats: {},

            pageviewRanges: {},
            titleVariantRanges: {},
            imageVariantRanges: {},

            titleVariants: [],
            imageVariants: [],
        }),
        created: function() {
            EventHub.$on("content-conversions-counts-changed", this.updateConversions);
            EventHub.$on("content-pageviews-changed", this.updatePageviewStats);
            EventHub.$on("content-variants-changed", this.updateVariantStats);
        },
        computed: {
            conversionRate() {
                if (this.totalPageviews === 0) {
                    return 0.0
                }
                // Artificially increased 10000x so conversion rate is more readable
                return rounding((this.conversions/this.totalPageviews) * 10000, 2);
            },
            conversionRateColorClass() {
                return colorThresholdToClass(CONVERSION_RATE_COLORING_THRESHOLD, this.conversionRate)
            },
            conversionsColorClass() {
                return colorThresholdToClass(CONVERSIONS_COLORING_THRESHOLD, this.conversions)
            },
            sortedPageviewRanges() {
                return Object.values(this.pageviewRanges).slice().sort((a, b) => a.order - b.order);
            },
            sortedTitleVariantRanges() {
                return Object.values(this.titleVariantRanges).slice().sort((a, b) => a.order - b.order);
            },
            sortedImageVariantRanges() {
                return Object.values(this.imageVariantRanges).slice().sort((a, b) => a.order - b.order);
            },
            sortedTitleVariants() {
                return this.titleVariants.slice().sort((a, b) => a - b);
            },
            sortedImageVariants() {
                return this.imageVariants.slice().sort((a, b) => a - b);
            },
        },
        methods: {
            updateConversions(counts) {
                if (counts === null || !counts[this.articleId]) {
                    this.conversions = 0
                    return;
                }
                this.conversions = counts[this.articleId]
            },
            updatePageviewStats(range, counts) {
                this.$set(this.pageviewRanges, range.label, range);
                if (counts === null || !counts[this.articleId]) {
                    this.$set(this.pageviewStats, range.label, 0);
                    return;
                }
                this.$set(this.pageviewStats, range.label, counts[this.articleId]);

                // Assuming 'Total' label exists
                if (range.label === 'Total') {
                    this.totalPageviews = counts[this.articleId]
                }
            },
            updateVariantStats(variantTypes, range, counts) {
                for (const variantType of variantTypes) {
                    switch (variantType) {
                        case "title_variant":
                            this.updateVariants(this.titleVariantRanges, this.titleVariants, this.titleVariantStats, range, counts[variantType]);
                            break;
                        case "image_variant":
                            this.updateVariants(this.imageVariantRanges, this.imageVariants, this.imageVariantStats, range, counts[variantType]);
                            break;
                    }
                }
            },
            updateVariants(variantRanges, variants, variantStats, range, counts) {
                this.$set(variantRanges, range.label, range);

                for (let variant in counts[this.articleId]) {
                    if (!counts[this.articleId].hasOwnProperty(variant)) {
                        continue;
                    }
                    if (variants.indexOf(variant) === -1) {
                        variants.push(variant);
                    }

                    let val = variantStats[variant] || {};
                    if (counts === null || !counts[this.articleId][variant]) {
                        val[range.label] = 0
                    } else {
                        val[range.label] = counts[this.articleId][variant]
                    }
                    variantStats[variant] = val;
                }
                this.$forceUpdate();
            },
        },
    }
</script>
