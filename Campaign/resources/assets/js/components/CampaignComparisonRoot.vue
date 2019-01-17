<template>
    <div>
        <div class="card-header">
            <h2>Compared campaigns<small></small></h2>
        </div>
        <div class="card-body card-padding">
            <div class="table-responsive">
                <loader :yes="loading"></loader>

                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Campaign</th>
                            <th>Clicks</th>
                            <th>Click-through rate (CTR)</th>
                            <th>Conversions</th>
                            <th>Started payments</th>
                            <th>Finished payments</th>
                            <th>Earned</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="campaign in campaigns" :key="campaign.id">
                            <td>
                                {{ campaign.name }}
                            </td>
                            <td>
                                {{ campaign.stats.click_count.count }}
                            </td>
                            <td>
                                <strong>
                                    {{ campaign.stats.ctr | round(2) }}%
                                </strong>
                            </td>
                            <td>
                                <strong>
                                    {{ campaign.stats.conversions | round(4) }}%
                                </strong>
                            </td>
                            <td>
                                {{ campaign.stats.payment_count.count }}
                            </td>
                            <td>
                                {{ campaign.stats.purchase_count.count }}
                            </td>
                            <td>
                                {{ campaign.stats.purchase_sum.sum | round(2) }}€
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<style type="text/css">
</style>

<script>
    import axios from 'axios'
    import Loader from './status/Loader'

    const props = {
        baseUrl: {
            type: String,
            required: true,
        }
    }

    export default {
        components: {
            Loader
        },
        name: 'campaign-comparison-root',
        props: props,
        data: () => ({
            campaigns: null,
            loading: false,
            error: null,
        }),
        mounted() {
            this.loadData()
        },
        methods: {
            loadData() {
                this.loading = true
                axios
                    .get(this.baseUrl)
                    .then(response => {
                        this.loading = false
                        this.campaigns = response.data.campaigns
                    })
                    .catch(error => {
                        this.error = error
                        this.loading = false;
                    })
            },
        }
    }
</script>
