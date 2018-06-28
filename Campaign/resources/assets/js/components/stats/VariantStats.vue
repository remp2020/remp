<style>
    [data-single-value-id="click-through-rate-ctr"] .card-body {
        font-weight: bold;
    }
</style>

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
                            :title="'Click-through rate (CTR)'"
                            :unit="'%'"
                            :loading="loading"
                            :error="error"
                            :value="ctr"
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
                            :title="'Finished payments'"
                            :loading="loading"
                            :error="error"
                            :value="finishedPaymentsCount"
                        ></single-value>

                        <single-value
                            :title="'Earned'"
                            :loading="loading"
                            :error="error"
                            :value="earnedSum"
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
                showsCount: 0,
                startedPaymentsCount: 0,
                finishedPaymentsCount: 0,
                earnedSum: 0,
                ctr: 0,
                histogramData: {},
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
                        vm.showsCount = resp.show_count.count;
                        vm.startedPaymentsCount = resp.payment_count.count;
                        vm.finishedPaymentsCount = resp.purchase_count.count;
                        vm.earnedSum = resp.purchase_sum.sum;
                        vm.histogramData = resp.histogram;

                        if (vm.clickCount !== 0 && vm.showsCount !== 0) {
                            vm.ctr = (vm.clickCount / vm.showsCount) * 100;
                        }

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
