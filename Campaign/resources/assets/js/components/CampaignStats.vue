<template>
    <section id="content">
        <div class="container">
            <chart
                :name="'campaign-stats-chart'"
                :id="id"
                :url="'/campaigns/' + id + '/stats/histogram'"
                :title="'Campaign'"
                :from="from"
                :to="to"
            ></chart>

            <chart
                v-for="variant in variants"
                :key="variant.id"
                :name="'variant-stats-chart-' + variant.id"
                :url="'/campaigns/stats/variant/' + variant.id + '/histogram'"
                :title="'Variant: ' + variant.variant"
                :from="from"
                :to="to"
            ></chart>



            <!--div id="c-grid" class="clearfix" data-columns>
                <card
                    :name="'campaign-shows-card'"
                    :id="id"
                    :variants="this.variants"
                    :type="'show'"
                    :title="'Shows'"
                    :interval="'1h'"
                ></card>

                <card
                    :name="'campaign-clicks-card'"
                    :id="id"
                    :variants="this.variants"
                    :type="'click'"
                    :title="'Clicks'"
                    :interval="'1h'"
                ></card>
            </div-->
        </div>
    </section>

</template>

<script>
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
            Chart
        },
        props: props
    }
</script>


