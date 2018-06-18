<template>
    <div class="variant-stats well">
        <div class="row">
            <div class="col-sm-12 col-md-8 variant-chart-wrap">
                <chart
                    :name="'variant-stats-chart-' + variant.id"
                    :title="'Variant: ' + variant.variant + ' <small>(' + variant.proportion + '%)</small>'"
                    :height="430"
                    :loading="loading"
                    :error="error"
                    :chartData="histogramData"
                ></chart>
            </div>
            <div class="col-sm-12 col-md-4">
                <div class="row">
                    <div id="variant-stats-grid" class="clearfix" data-columns>

                        <single-value
                                :title="'Shows'"
                                :loading="loading"
                                :error="error"
                                :count="showsCount"
                        ></single-value>

                        <single-value
                                :title="'Shows'"
                                :subtitle="'normalized'"
                                :loading="loading"
                                :error="error"
                                :count="showsCountNormalized"
                                :infoText="normalizedValueInfo"
                        ></single-value>

                        <single-value
                            :title="'Clicks'"
                            :loading="loading"
                            :error="error"
                            :count="clickCount"
                        ></single-value>

                        <single-value
                            :title="'Clicks'"
                            :subtitle="'normalized'"
                            :loading="loading"
                            :error="error"
                            :count="clickCountNormalized"
                            :infoText="normalizedValueInfo"
                        ></single-value>

                        <single-value
                            :title="'Started payments'"
                            :loading="loading"
                            :error="error"
                            :count="startedPaymentsCount"
                        ></single-value>

                        <single-value
                            :title="'Started payments'"
                            :subtitle="'normalized'"
                            :loading="loading"
                            :error="error"
                            :count="startedPaymentsCountNormalized"
                            :infoText="normalizedValueInfo"
                        ></single-value>

                        <single-value
                            :title="'Finished payments'"
                            :loading="loading"
                            :error="error"
                            :count="finishedPaymentsCount"
                        ></single-value>

                        <single-value
                            :title="'Finished payments'"
                            :subtitle="'normalized'"
                            :loading="loading"
                            :error="error"
                            :count="finishedPaymentsCountNormalized"
                            :infoText="normalizedValueInfo"
                        ></single-value>

                        <single-value
                            :title="'Earned'"
                            :loading="loading"
                            :error="error"
                            :count="earnedSum"
                        ></single-value>

                        <single-value
                            :title="'Earned'"
                            :subtitle="'normalized'"
                            :loading="loading"
                            :error="error"
                            :count="earnedSumNormalized"
                            :infoText="normalizedValueInfo"
                        ></single-value>

                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import SingleValue from './SingleValue'
    import Chart from './Chart'

    export default {
        props: {
            variant: {
                type: Object,
                required: true
            },
            url: {
                type: String,
                required: true
            },
            from: {
                type: String,
                required: true
            },
            to: {
                type: String,
                required: true
            }
        },
        components: {
            SingleValue,
            Chart
        },
        data() {
            return {
                loading: false,
                error: "",

                clickCount: 0,
                clickCountNormalized: 0,
                showsCount: 0,
                showsCountNormalized: 0,
                startedPaymentsCount: 0,
                startedPaymentsCountNormalized: 0,
                finishedPaymentsCount: 0,
                finishedPaymentsCountNormalized: 0,
                earnedSum: 0,
                earnedSumNormalized: 0,
                histogramData: {},

                normalizedValueInfo: "This value was calculated from: original value, proportion and variants count."
            }
        },
        mounted() {
            this.load()
        },
        watch: {
            from() {
                this.load()
            },
            to() {
                this.load()
            }
        },
        methods: {
            load() {
                let vm = this;
                vm.error = "";
                vm.loading = true;

                $.ajax({
                    method: 'POST',
                    url: vm.url,
                    data: {
                        from: vm.from,
                        to: vm.to,
                        chartWidth: $('.variant-chart-wrap').first().width(),
                        _token: document.head.querySelector("[name=csrf-token]").content
                    },
                    dataType: 'JSON',
                    success(resp, status) {

                        vm.clickCount = resp.click_count.count;
                        vm.clickCountNormalized = resp.click_count_normalized.count;
                        vm.showsCount = resp.show_count.count;
                        vm.showsCountNormalized = resp.show_count_normalized.count;
                        vm.startedPaymentsCount = resp.payment_count.count;
                        vm.startedPaymentsCountNormalized = resp.payment_count_normalized.count;
                        vm.finishedPaymentsCount = resp.purchase_count.count;
                        vm.finishedPaymentsCountNormalized = resp.purchase_count_normalized.count;
                        vm.earnedSum = resp.purchase_sum.sum;
                        vm.earnedSumNormalized = resp.purchase_sum_normalized.sum;
                        vm.histogramData = resp.histogram;

                        vm.loading = false;
                    },
                    error(xhr, status, error) {
                        vm.loading = false;
                        let body = JSON.parse(xhr.responseText);
                        vm.error = body.message;
                    }
                })
            }
        }
    }
</script>
