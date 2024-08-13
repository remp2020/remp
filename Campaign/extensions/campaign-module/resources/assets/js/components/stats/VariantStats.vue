<template>
    <div class="variant-stats well">
        <div class="row">
            <div class="col-sm-12 col-md-8 variant-chart-wrap">
                <chart
                    :name="'variant-stats-chart-' + variant.uuid"
                    :title="chartTitle"
                    :height="400"
                    :loading="loading"
                    :error="error"
                    :chartData="histogramData"
                ></chart>
            </div>
            <div class="col-sm-12 col-md-4">
                <div class="row">
                    <div id="variant-singles-grid" class="clearfix" data-columns>

                        <single-value
                            :title="'CTR'"
                            :unit="'%'"
                            :loading="loading"
                            :error="error"
                            :value="ctr"
                            :infoText="'Click-through rate'"
                        ></single-value>

                        <single-value
                            :title="'Conversions'"
                            :unit="'%'"
                            :loading="loading"
                            :error="error"
                            :value="conversions"
                            :precision="4"
                            :infoText="'Number of purchases / shows.'"
                        ></single-value>

                        <single-value
                            :title="'Shows'"
                            :loading="loading"
                            :error="error"
                            :value="showsCount"
                        ></single-value>

                        <single-value
                            :title="'Clicks'"
                            :loading="loading"
                            :error="error"
                            :value="clickCount"
                        ></single-value>

                        <single-value
                            :title="'Started payments'"
                            :loading="loading"
                            :error="error"
                            :value="startedPaymentsCount"
                        ></single-value>

                        <single-value
                            :title="'Purchases'"
                            :loading="loading"
                            :error="error"
                            :value="finishedPaymentsCount"
                        ></single-value>

                        <multiple-values
                                :title="'Earned'"
                                :loading="loading"
                                :error="error"
                                :values="earned"
                        ></multiple-values>

                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import SingleValue from './SingleValue'
    import MultipleValues from './MultipleValues'
    import Chart from './Chart'

    export default {
        props: {
            variant: {
                type: Object,
                required: true
            },
            variantBannerLink: {
                type: String,
                required: false
            },
            variantBannerText: {
                type: String,
                required: false
            },
            data: {
                type: Object,
                required: true,
                default() {
                    return {};
                }
            },
            loading: {
                type: Boolean,
                required: true
            },
            error: {
                type: String,
                required: true,
                default: ""
            }
        },
        components: {
            SingleValue,
            MultipleValues,
            Chart
        },
        data() {
            return {
                clickCount: 0,
                showsCount: 0,
                startedPaymentsCount: 0,
                finishedPaymentsCount: 0,
                earned: [],
                ctr: 0,
                conversions: 0,
                histogramData: {},
            }
        },
        computed: {
            variantName() {
                if (this.variant.banner !== null) {
                    return this.variant.banner.name;
                }

                return 'Control Group'
            },
            chartTitle() {
                if (this.variantBannerLink) {
                    let title = '';

                    if (this.variant.deleted_at) {
                        title += '<i v-if="variant.deleted_at" class="zmdi zmdi-close-circle-o" style="color: red;" title="This variant was deleted.">&nbsp;</i>';
                    }

                    title += "Variant: <a href=\"" + this.variantBannerLink + "\">" + this.variantName + "</a> <small>(" + this.variant.proportion + "%)</small>";

                    if (this.variantBannerText) {
                        title += "<br/><small><em>" + this.variantBannerText + "</em></small>";
                    }
                    return title;
                }
                return "Variant: " + this.variantName + " <small>(" + this.variant.proportion + "%)</small>";
            },
        },
        watch: {
            data(data) {
                this.clickCount = data.click_count;
                this.showsCount = data.show_count;
                this.startedPaymentsCount = data.payment_count;
                this.finishedPaymentsCount = data.purchase_count;

                let values = [];
                for (const currency of Object.keys(data.purchase_sums)) {
                    let sum = data.purchase_sums[currency];
                    values.push({
                        value: sum,
                        unit: currency
                    });
                }
                this.earned = values;

                this.histogramData = data.histogram;
                this.ctr = data.ctr;
                this.conversions = data.conversions;
            }
        }
    }
</script>
