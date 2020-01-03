<template>
    <div class="well">
        <div class="card card-table">
            <div class="card-header">
                <h3>Summary</h3>
            </div>
            <div class="card-body card-padding">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Variant</th>
                                <th>Proportion</th>
                                <th>Shows</th>
                                <th>Clicks</th>
                                <th>Click-rate win probability</th>
                                <th>Click-through rate (CTR)</th>
                                <th>Conversions (purchases)</th>
                                <th>Conversion-rate win probability</th>
                                <th>Earned</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="variant in variantsData" :key="variant.uuid" :class="{ 'deleted-variant': variant.deleted_at }">
                            <td>
                                <i v-if="variant.deleted_at"
                                   class="zmdi zmdi-close-circle-o"
                                   title="This variant was deleted.">&nbsp;</i>

                                <a v-if="variant.link" :href="variant.link">{{ variant.name }}</a>
                                <span v-else>{{ variant.name }}</span>
                            </td>
                            <td>
                                {{ variant.proportion }}%
                            </td>
                            <td>
                                {{ variant.shows }}
                            </td>
                            <td>
                                {{ variant.clicks }}
                            </td>
                            <td>
                                <template v-if="variant.click_probability !== undefined">
                                    <strong>{{ variant.click_probability * 100 | round(2) }}%</strong>
                                </template>
                                <template v-else>
                                    -
                                </template>
                            </td>
                            <td>
                                <strong>
                                    {{ variant.ctr | round(2) }}%
                                </strong>
                            </td>
                            <td>
                                <strong>
                                    {{ variant.purchases }}
                                    ({{ variant.conversions | round(4) }}%)
                                </strong>
                            </td>
                            <td>
                                <template v-if="variant.purchase_probability !== undefined">
                                    <strong>{{ variant.purchase_probability * 100 | round(2) }}%</strong>
                                </template>
                                <template v-else>
                                    -
                                </template>
                            </td>
                            <td>
                                <strong v-for="(sum, currency) in variant.earned" :key="currency">
                                    {{ sum | round(2) }} {{ currency }}
                                </strong>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</template>

<script>
    export default {
        props: {
            variants: {
                type: Array,
                required: true,
            },
            variantBannerLinks: {
                type: Object,
                required: true,
            },
            data: {
                type: Object,
                required: true,
            },
            campaignData: {
                type: Object,
                required: true
            }
        },
        data() {
            return {
                variantsData: []
            }
        },
        mounted() {
            this.prepareVariants()
        },
        watch: {
            data() {
                this.prepareVariants()
            }
        },
        methods: {
            prepareVariants() {
                this.variantsData = [];

                for (let ii = 0; ii < this.variants.length; ii++) {
                    let variant = this.variants[ii];
                    let data = this.data[variant.uuid];

                    let prepared = {
                        name: "Control Group",
                        proportion: variant.proportion,
                        clicks: data.click_count,
                        shows: data.show_count,
                        earned: data.purchase_sums,
                        purchases: data.purchase_count,
                        ctr: data.ctr,
                        conversions: data.conversions,
                        deleted_at: variant.deleted_at,
                        click_probability: data.click_probability,
                        purchase_probability: data.purchase_probability,
                    };

                    if (variant.banner !==  null) {
                        prepared.name = variant.banner.name;
                        prepared.link = this.variantBannerLinks[variant.uuid] || null;
                    }

                    this.variantsData.push(prepared);
                }

                this.variantsData.push({
                    name: 'TOTAL',
                    proportion: 100,
                    clicks: this.campaignData.click_count,
                    shows: this.campaignData.show_count,
                    earned: this.campaignData.purchase_sums,
                    purchases: this.campaignData.purchase_count,
                    ctr: this.campaignData.ctr,
                    conversions: this.campaignData.conversions,
                });
            }
        }
    }
</script>


<style scoped>
    h3 {
        margin-top: 0;
    }

    table {
        font-size: 14px;
    }

    .card-table {
        margin-bottom: 0;
        overflow: scroll;
    }

    .deleted-variant {
        color: #999;
    }

    .deleted-variant .zmdi-close-circle-o {
        color: rgba(255, 0, 0, 0.67);
    }

    .deleted-variant a {
        color: rgba(0, 121, 143, 0.58);
    }
</style>