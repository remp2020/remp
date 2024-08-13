<template>
    <div class="well">
        <div class="row">
            <div class="col-md-10">
                <chart
                    :name="'campaign-stats-chart'"
                    :title="name"
                    :edit-link="editLink"
                    :height="500"
                    :loading="loading"
                    :error="error"
                    :chartData="histogramData"
                ></chart>
            </div>

            <div class="col-md-2">
                <div class="row">
                    <div id="campaign-singles-grid" class="clearfix" data-columns>

                        <single-value
                            :title="'Clicks'"
                            :loading="loading"
                            :error="error"
                            :value="clickCount"
                        ></single-value>

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
                            :title="'Started payments'"
                            :loading="loading"
                            :error="error"
                            :value="startedPayments"
                        ></single-value>

                        <single-value
                            :title="'Finished payments'"
                            :loading="loading"
                            :error="error"
                            :value="finishedPayments"
                        ></single-value>

                        <multiple-values
                                :title="'Earned'"
                                :loading="loading"
                                :error="error"
                                :values="earned"
                        ></multiple-values>

                    </div>
                </div><!-- .row -->
            </div><!-- .col -->
        </div><!-- .row -->
    </div><!-- .well -->
</template>

<script>
    import SingleValue from './SingleValue'
    import Chart from './Chart'
    import MultipleValues from "./MultipleValues";

    export default {
        components: {
            MultipleValues,
            SingleValue,
            Chart
        },
        props: {
            name: {
                type: String,
                required: true
            },
            editLink: {
                type: String,
                required: true
            },
            data: {
                type: Object,
                required: true
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
        data() {
            return {
                clickCount: 0,
                startedPayments: 0,
                finishedPayments: 0,
                earned: [],
                histogramData: {},
                ctr: 0,
                conversions: 0,
            }
        },
        watch: {
            data(data) {
                this.clickCount = data.click_count;
                this.startedPayments = data.payment_count;
                this.finishedPayments = data.purchase_count;

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
