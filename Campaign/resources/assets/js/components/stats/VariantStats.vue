<template>
    <div class="row">
        <div class="col-sm-12 col-md-8 variant-chart-wrap">
            <chart
                :name="'variant-stats-chart-' + variant.id"
                :title="'Variant: ' + variant.variant"
                :height="430"

                ref="histogram"
            ></chart>
        </div>
        <div class="col-sm-12 col-md-4">
            <div class="row">
                <div id="variant-stats-grid" class="clearfix" data-columns>

                    <single-value
                        :title="'Clicks'"
                        :loading="loading"

                        ref="clickCount"
                    ></single-value>

                    <single-value
                        :title="'Clicks'"
                        :subtitle="'normalized'"
                        :loading="loading"

                        ref="clickCountNormalized"
                    ></single-value>

                    <single-value
                        :title="'Shows'"
                        :loading="loading"

                        ref="showCount"
                    ></single-value>

                    <single-value
                        :title="'Shows'"
                        :subtitle="'normalized'"
                        :loading="loading"

                        ref="showCountNormalized"
                    ></single-value>

                    <single-value
                        :title="'Started payments'"
                        :loading="loading"

                        ref="startedPayments"
                    ></single-value>

                    <single-value
                        :title="'Started payments'"
                        :subtitle="'normalized'"
                        :loading="loading"

                        ref="startedPaymentsNormalized"
                    ></single-value>

                    <single-value
                        :title="'Finished payments'"
                        :loading="loading"

                        ref="finishedPayments"
                    ></single-value>

                    <single-value
                        :title="'Finished payments'"
                        :subtitle="'normalized'"
                        :loading="loading"

                        ref="finishedPaymentsNormalized"
                    ></single-value>

                    <single-value
                        :title="'Earned'"
                        :loading="loading"

                        ref="earned"
                    ></single-value>

                    <single-value
                        :title="'Earned'"
                        :subtitle="'normalized'"
                        :loading="loading"

                        ref="earnedNormalized"
                    ></single-value>


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
                loading: false
            }
        },
        mounted() {
            this.load()
        },
        methods: {
            load() {
                var vm = this;
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

                        vm.$refs.histogram.handleResult(resp.histogram)
                        vm.$refs.clickCount.handleResult(resp.click_count)
                        vm.$refs.clickCountNormalized.handleResult(resp.click_count_normalized)
                        vm.$refs.showCount.handleResult(resp.show_count)
                        vm.$refs.showCountNormalized.handleResult(resp.show_count_normalized)
                        vm.$refs.startedPayments.handleResult(resp.payment_count)
                        vm.$refs.startedPaymentsNormalized.handleResult(resp.payment_count_normalized)
                        vm.$refs.finishedPayments.handleResult(resp.purchase_count)
                        vm.$refs.finishedPaymentsNormalized.handleResult(resp.purchase_count_normalized)
                        vm.$refs.earned.handleResult(resp.purchase_sum)
                        vm.$refs.earnedNormalized.handleResult(resp.purchase_sum_normalized)

                        vm.loading = false;
                    },
                    error(xhr, status, error) {
                        vm.loading = false;
                    }
                })
            }
        }
    }
</script>
