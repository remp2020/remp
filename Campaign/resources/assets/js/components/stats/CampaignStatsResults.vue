<template>
    <div class="well">
        <div class="card card-table">
            <div class="card-header">
                <h3>Summary</h3>
            </div>
            <div class="card-body card-padding">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Variant</th>
                                <th>Proportion</th>
                                <th>Shows</th>
                                <th>Clicks</th>
                                <th>Click-through rate (CTR)</th>
                                <th>Conversions (purchases)</th>
                                <th>Earned</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="variant in variantsData" :key="variant.id">
                            <td>
                                {{ variant.name }}
                            </td>
                            <td>
                                {{ variant.proportion }}%
                            </td>
                            <td>
                                {{ variant.shows }}
                            </td>
                            <td>
                                {{ variant.clicks }}
                            </td>
                            <td>
                                <strong>
                                    {{ variant.ctr | round(2) }}%
                                </strong>
                            </td>
                            <td>
                                <strong>
                                    {{ variant.conversions | round(2) }}%
                                </strong>
                            </td>
                            <td>
                                <strong>
                                    {{ variant.earned | round(2) }}€
                                </strong>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</template>

<script>
    export default {
        props: {
            variants: {
                type: Array,
                required: true
            },
            data: {
                type: Object,
                required: true
            }
        },
        data() {
            return {
                variantsData: []
            }
        },
        mounted() {
            this.prepareVariants()
        },
        watch: {
            data() {
                this.prepareVariants()
            }
        },
        methods: {
            prepareVariants() {
                this.variantsData = [];

                for (let ii = 0; ii < this.variants.length; ii++) {
                    let data = this.data[this.variants[ii].id];

                    let prepared = {
                        name: "Control Group",
                        proportion: this.variants[ii].proportion,
                        clicks: data.click_count.count,
                        shows: data.show_count.count,
                        earned: data.purchase_sum.sum,
                        ctr: data.ctr,
                        conversions: data.conversions,
                    };

                    if (this.variants[ii].banner !==  null) {
                        prepared.name = this.variants[ii].banner.name;
                    }

                    this.variantsData.push(prepared);
                }
            }
        }
    }
</script>


<style scoped>
    h3 {
        margin-top: 0;
    }

    table {
        font-size: 14px;
    }

    .card-table {
        margin-bottom: 0;
    }
</style>