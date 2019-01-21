<template>
    <div>
        <div class="card-header">
            <h2>Compared campaigns<small></small></h2>
        </div>
        <div class="card-body card-padding">

            <div class="row m-t-20 m-b-20">
                <div class="col-md-4">
                    <model-list-select :list="campaignsNotCompared"
                                       v-model="campaignToAdd"
                                       option-value="id"
                                       option-text="name"
                                       placeholder="Select campaign">
                    </model-list-select>
                </div>
                <div class="col-md-2">
                    <button @click="addToComparison" class="btn palette-Cyan bg waves-effect">Add to comparison</button>
                </div>
            </div>

            <div style="position: relative" class="table-responsive">
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
                            <th></th>
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
                            <th>
                                <span class="actions">
                                    <button class="btn btn-sm palette-Cyan bg waves-effect"
                                            @click="remove(campaign.deleteUrl)"
                                            title="Remove campaign">
                                        <i class="zmdi zmdi-palette-Cyan zmdi-close"></i>
                                    </button>
                                </span>
                            </th>
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
    import { ModelListSelect } from 'vue-search-select'

    const props = {
        baseUrl: {
            type: String,
            required: true,
        }
    }

    export default {
        components: {
            Loader, ModelListSelect
        },
        name: 'campaign-comparison-root',
        props: props,
        data: () => ({
            campaigns: null,
            campaignsNotCompared: [],
            campaignToAdd: null,
            addUrl: null,
            loading: false,
            error: null,
        }),
        mounted() {
            this.loadData()
        },
        methods: {
            processLoadingResponse(data) {
                this.loading = false
                this.campaigns = data.campaigns
                this.campaignsNotCompared = data.campaignsNotCompared
                this.addUrl = data.addUrl
            },
            addToComparison() {
                if (!this.addUrl) {
                    return
                }
                let url = this.addUrl.replace('CAMPAIGN_ID', this.campaignToAdd)
                this.loading = true
                axios
                    .put(url)
                    .then(response => axios.get(this.baseUrl))
                    .then(response => {
                        this.processLoadingResponse(response.data)
                    })
                    .catch(error => {
                        this.error = error
                        this.loading = false;
                    })

            },
            loadData() {
                this.loading = true
                axios
                    .get(this.baseUrl)
                    .then(response => {
                        this.processLoadingResponse(response.data)
                    })
                    .catch(error => {
                        this.error = error
                        this.loading = false;
                    })
            },
            remove(url) {
                this.loading = true
                axios
                    .delete(url)
                    .then(response => axios.get(this.baseUrl))
                    .then(response => {
                        this.processLoadingResponse(response.data)
                    })
                    .catch(error => {
                        this.error = error
                        this.loading = false;
                    })
            }
        }
    }
</script>
