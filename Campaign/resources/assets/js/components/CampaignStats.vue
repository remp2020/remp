<style>
    .size-1of4 { width: 25%; }

    #campaign-singles-grid[data-columns]::before {
        content: '4 .column.size-1of4';
    }

    #variant-stats-grid[data-columns]::before {
        content: '2 .column.size-1of2';
    }
</style>

<template>
    <section id="content">
        <div class="container">

            <div class="row">
                <div id="campaign-singles-grid" class="clearfix" data-columns>
                    <single-value
                        :url="'/campaigns/' + id + '/stats/click/count'"
                        :title="'Clicks'"
                        :from="from"
                        :to="to"
                    ></single-value>

                    <single-value
                        :url="'/campaigns/' + id + '/payment/stats/step/payment/count'"
                        :title="'Started payments'"
                        :from="from"
                        :to="to"
                    ></single-value>

                    <single-value
                        :url="'/campaigns/' + id + '/payment/stats/step/purchase/count'"
                        :title="'Finished payments'"
                        :from="from"
                        :to="to"
                    ></single-value>

                    <single-value
                        :url="'/campaigns/' + id + '/payment/stats/step/purchase/sum'"
                        :title="'Earned'"
                        :from="from"
                        :to="to"
                    ></single-value>
                </div>
            </div>


            <chart
                :url="'/campaigns/' + id + '/stats/histogram'"
                :name="'campaign-stats-chart'"
                :title="'Campaign'"
                :height="450"
                :from="from"
                :to="to"
            ></chart>


            <div class="row" v-for="variant in variants" :key="variant.id">
                <div class="col-sm-12 col-md-8">
                    <chart
                        :variant="variant"
                        :url="'/campaigns/stats/variant/' + variant.id + '/histogram'"
                        :name="'variant-stats-chart-' + variant.id"
                        :title="'Variant: ' + variant.variant"
                        :height="430"
                        :from="from"
                        :to="to"
                    ></chart>
                </div>
                <div class="col-sm-12 col-md-4">
                    <div id="variant-stats-grid" class="clearfix" data-columns>

                        <single-value
                            :url="'/campaigns/stats/variant/' + variant.id + '/click/count'"
                            :title="'Clicks'"
                            :from="from"
                            :to="to"
                        ></single-value>

                        <single-value
                            :url="'/campaigns/stats/variant/' + variant.id + '/click/count'"
                            :title="'Clicks'"
                            :subtitle="'normalized'"
                            :normalized="true"
                            :from="from"
                            :to="to"
                        ></single-value>

                        <single-value
                            :url="'/campaigns/stats/variant/' + variant.id + '/show/count'"
                            :title="'Shows'"
                            :from="from"
                            :to="to"
                        ></single-value>

                        <single-value
                            :url="'/campaigns/stats/variant/' + variant.id + '/show/count'"
                            :title="'Shows'"
                            :subtitle="'normalized'"
                            :normalized="true"
                            :from="from"
                            :to="to"
                        ></single-value>

                        <single-value
                            :url="'/campaigns/stats/variant/' + variant.id + '/payment/step/payment/count'"
                            :title="'Started payments'"
                            :from="from"
                            :to="to"
                        ></single-value>

                        <single-value
                            :url="'/campaigns/stats/variant/' + variant.id + '/payment/step/payment/count'"
                            :title="'Started payments'"
                            :subtitle="'normalized'"
                            :normalized="true"
                            :from="from"
                            :to="to"
                        ></single-value>

                        <single-value
                            :url="'/campaigns/stats/variant/' + variant.id + '/payment/step/purchase/count'"
                            :title="'Finished payments'"
                            :from="from"
                            :to="to"
                        ></single-value>

                        <single-value
                            :url="'/campaigns/stats/variant/' + variant.id + '/payment/step/purchase/count'"
                            :title="'Finished payments'"
                            :subtitle="'normalized'"
                            :normalized="true"
                            :from="from"
                            :to="to"
                        ></single-value>

                        <single-value
                            :url="'/campaigns/stats/variant/' + variant.id + '/payment/step/purchase/sum'"
                            :title="'Earned'"
                            :from="from"
                            :to="to"
                        ></single-value>

                        <single-value
                            :url="'/campaigns/stats/variant/' + variant.id + '/payment/step/purchase/sum'"
                            :title="'Earned'"
                            :subtitle="'normalized'"
                            :normalized="true"
                            :from="from"
                            :to="to"
                        ></single-value>


                    </div>
                </div>
            </div>

        </div>
    </section>

</template>

<script>
    import SingleValue from './stats/SingleValue'
    import Chart from './stats/Chart'
    import Card from './stats/Card'

    let props = {
        id: {
            type: Number,
            required: true
        },
        name: {
            type: String,
            required: true,
        },
        variants: {
            type: Array,
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
    };

    export default {
        components: {
            Card,
            Chart,
            SingleValue
        },
        props: props
    }
</script>


