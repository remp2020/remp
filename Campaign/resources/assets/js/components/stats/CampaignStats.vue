<template>
    <div id="campaign-stats-wrap">
        <div class="row">
            <div id="campaign-singles-grid" class="clearfix" data-columns>
                <single-value
                    :title="'Clicks'"
                    :loading="loading"

                    ref="clickCount"
                ></single-value>

                <single-value
                    :title="'Started payments'"
                    :loading="loading"

                    ref="paymentCount"
                ></single-value>

                <single-value
                    :title="'Finished payments'"
                    :loading="loading"

                    ref="purchaseCount"
                ></single-value>

                <single-value
                    :title="'Earned'"
                    :loading="loading"

                    ref="purchaseSum"
                ></single-value>
            </div>
        </div>

        <chart
            :name="'campaign-stats-chart'"
            :title="'Campaign'"
            :height="450"
            :loading="loading"

            ref="histogram"
        ></chart>
    </div>
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
        data() {
            return {
                loading: false
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
                var vm = this;
                vm.loading = true;

                $.ajax({
                    method: 'POST',
                    url: vm.url,
                    data: {
                        from: vm.from,
                        to: vm.to,
                        chartWidth: $('#campaign-stats-wrap').width(),
                        _token: document.head.querySelector("[name=csrf-token]").content
                    },
                    dataType: 'JSON',
                    success(resp, status) {
                        vm.$refs.clickCount.handleResult(resp.click_count);
                        vm.$refs.paymentCount.handleResult(resp.payment_count);
                        vm.$refs.purchaseCount.handleResult(resp.purchase_count);
                        vm.$refs.purchaseSum.handleResult(resp.purchase_sum);
                        vm.$refs.histogram.handleResult(resp.histogram);

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
