<template>
    <div class="well">
        <div class="row">
            <div class="col-md-10">
                <chart
                    :name="'campaign-stats-chart'"
                    :title="name"
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

                        <single-value
                            :title="'Earned'"
                            :loading="loading"
                            :error="error"
                            :value="earned"
                            :unit="'€'"
                        ></single-value>

                    </div>
                </div><!-- .row -->
            </div><!-- .col -->
        </div><!-- .row -->
    </div><!-- .well -->
</template>

<script>
    import SingleValue from './SingleValue'
    import Chart from './Chart'

    export default {
        components: {
            SingleValue,
            Chart
        },
        props: {
            name: {
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
                earned: 0,
                histogramData: {},
                ctr: 0,
                conversions: 0,
            }
        },
        watch: {
            data(data) {
                this.clickCount = data.click_count.count;
                this.startedPayments = data.payment_count.count;
                this.finishedPayments = data.purchase_count.count;
                this.earned = data.purchase_sum.sum;
                this.histogramData = data.histogram;
                this.ctr = data.ctr;
                this.conversions = data.conversions;
            }
        }
    }
</script>
