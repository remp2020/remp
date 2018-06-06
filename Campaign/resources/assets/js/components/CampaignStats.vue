<template>
    <section id="content">
        <div class="container">
            <div id="c-grid" class="clearfix" data-columns>
                <card :name="'campaign-shows-card'" :id="id" :variants="this.variants" :type="'shows'" :title="'Shows'"></card>
                <card :name="'campaign-clicks-card'" :id="id" :variants="this.variants" :type="'clicks'" :title="'Clicks'"></card>
            </div>
        </div>
    </section>

</template>

<script>
    import SingleValue from './stats/SingleValue'
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
        }
    };

    export default {
        components: {
            SingleValue,
            Card
        },
        props: props
    }
</script>


