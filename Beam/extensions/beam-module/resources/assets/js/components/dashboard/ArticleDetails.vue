<template>
    <div id="chartContainer" >
        <div class="card card-chart">
            <div class="card-header">
                <h2>Article Traffic Graph</h2>
            </div>
            <div class="card-body card-padding">
                <page-loads-graph
                        :show-data-source-switcher="true"
                        :default-graph-data-source="defaultGraphDataSource"
                        :event-options="[{text: 'Conversions', value: 'conversions', checked: true}, {text: 'Title changes', value: 'title_changes', checked: false}]"
                        :external-events="externalEvents"
                        :url="url">
                </page-loads-graph>
            </div>
        </div>

        <div v-if="hasTitleVariants" class="card card-chart">
            <div class="card-header">
                <h2>Title Variants A/B test
                </h2>
            </div>
            <div class="card-body card-padding">
                <page-loads-graph
                        :stacked="false"
                        :url-params="{type:'title'}"
                        :url="variantsUrl">
                </page-loads-graph>
            </div>
        </div>

        <div v-if="hasImageVariants" class="card card-chart">
            <div class="card-header">
                <h2>Image Variants A/B test
                </h2>
            </div>
            <div class="card-body card-padding">
                <page-loads-graph
                        :stacked="false"
                        :url-params="{type:'image'}"
                        :url="variantsUrl">
                </page-loads-graph>
            </div>
        </div>
    </div>

</template>

<script>
    import PageLoadsGraph from './PageLoadsGraph.vue'

    const props = {
        url: {
            type: String,
            required: true
        },
        variantsUrl: {
            type: String,
            required: true
        },
        hasTitleVariants: {
            type: Boolean,
            default: false
        },
        hasImageVariants: {
            type: Boolean,
            default: false
        },
        defaultGraphDataSource: {
            type: String
        },
        externalEvents: {
            type: Array,
            default: () => [],
        }
    }

    export default {
        components: {
            PageLoadsGraph
        },
        name: 'article-chart',
        props: props,
        data() {
            return {
            };
        }
    }
</script>
