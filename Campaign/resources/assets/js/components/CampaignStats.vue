<template>
    <section id="content">
        <div class="container">
            <div id="c-grid" class="clearfix" data-columns>
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


