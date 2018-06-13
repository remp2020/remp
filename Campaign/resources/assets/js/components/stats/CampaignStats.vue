<template>
    <div id="campaign-stats-wrap">
        <div class="row">
            <div id="campaign-singles-grid" class="clearfix" data-columns>
                <single-value
                    :title="'Clicks'"
                    :loading="loading"
                    :error="error"
                    :count="clickCount"
                ></single-value>

                <single-value
                    :title="'Started payments'"
                    :loading="loading"
                    :error="error"
                    :count="startedPayments"
                ></single-value>

                <single-value
                    :title="'Finished payments'"
                    :loading="loading"
                    :error="error"
                    :count="finishedPayments"
                ></single-value>

                <single-value
                    :title="'Earned'"
                    :loading="loading"
                    :error="error"
                    :count="earned"
                ></single-value>
            </div>
        </div>

        <chart
            :name="'campaign-stats-chart'"
            :title="'Campaign'"
            :height="450"
            :loading="loading"
            :error="error"
            :chartData="histogramData"
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
                loading: false,
                error: "",

                clickCount: 0,
                startedPayments: 0,
                finishedPayments: 0,
                earned: 0,
                histogramData: {}
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
                vm.error = "";
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
                        vm.clickCount = resp.click_count.count;
                        vm.startedPayments = resp.payment_count.count;
                        vm.finishedPayments = resp.purchase_count.count;
                        vm.earned = resp.purchase_sum.sum;
                        vm.histogramData = resp.histogram;

                        vm.loading = false;
                    },
                    error(xhr, status, error) {
                        vm.loading = false;
                        vm.error = error;
                    }
                })
            }
        }

    }
</script>
