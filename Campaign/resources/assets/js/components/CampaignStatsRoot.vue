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

            <campaign-stats
                :url="'/campaigns/' + id + '/stats'"
                :from="from"
                :to="to"
            ></campaign-stats>

            <variant-stats
                v-for="variant in variants"
                :key="variant.id"

                :variant="variant"
                :url="'/campaigns/variant/' + variant.id + '/stats'"
                :from="from"
                :to="to"
            ></variant-stats>

        </div>
    </section>

</template>

<script>
    import CampaignStats from './stats/CampaignStats'
    import VariantStats from './stats/VariantStats'
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
            CampaignStats,
            VariantStats
        },
        props: props
    }
</script>


