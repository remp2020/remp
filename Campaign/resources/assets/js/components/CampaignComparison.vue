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
                <div class="col-md-8 pull-left">
                    <button @click="addToComparison" class="btn palette-Cyan bg waves-effect m-r-5">Add to comparison</button>
                    <button @click="addAll" class="btn palette-Cyan bg waves-effect m-r-5">Add all campaigns</button>
                    <button @click="removeAll" class="btn palette-Red bg waves-effect">Remove all</button>
                </div>
            </div>

            <div style="position: relative" class="table-responsive">
                <loader :yes="loading"></loader>

                <table v-if="!noCampaigns" class="table table-striped">
                    <thead>
                        <tr>
                            <th @click="sort('name')" :class="sortingClass('name')">Campaign</th>
                            <th @click="sort('clicks')" :class="sortingClass('clicks')">Clicks</th>
                            <th @click="sort('ctr')" :class="sortingClass('ctr')">Click-through rate (CTR)</th>
                            <th @click="sort('conversions')" :class="sortingClass('conversions')">Conversions</th>
                            <th @click="sort('startedPayments')" :class="sortingClass('startedPayments')">Started payments</th>
                            <th @click="sort('finishedFayments')" :class="sortingClass('finishedFayments')">Finished payments</th>
                            <th @click="sort('earned')" :class="sortingClass('earned')">Earned</th>
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
                                            @click="remove(campaign.removeUrl)"
                                            title="Remove campaign">
                                        <i class="zmdi zmdi-palette-Cyan zmdi-close"></i>
                                    </button>
                                </span>
                            </th>
                        </tr>
                    </tbody>
                </table>

                <p v-if="noCampaigns" style="text-align: center">No campaigns selected</p>
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
            campaigns: [],
            campaignsNotCompared: [],
            campaignToAdd: null,
            addUrl: null,
            addAllUrl: null,
            removeAllUrl: null,
            loading: false,
            error: null,
            sorting: {
                by: null,
                asc: true
            },
        }),
        mounted() {
            this.loadData()
        },
        computed: {
            noCampaigns() {
                return this.campaigns && this.campaigns.length === 0
            }
        },
        methods: {
            sortingClass(attribute) {
                if (this.sorting.by === attribute) {
                    return this.sorting.asc ? 'sorting_asc' : 'sorting_desc'
                }
                return 'sorting'
            },
            sort(attribute) {
                if (attribute) {
                    if (this.sorting.by === attribute) {
                        this.sorting.asc = !this.sorting.asc
                    } else {
                        this.sorting.by = attribute
                        this.sorting.asc = true
                    }
                }
                function switchSorting(attribute, rev) {
                    switch(attribute) {
                        case 'name':
                        default:
                            return (a,b) => rev * a.name.localeCompare(b.name)
                        case 'clicks':
                            return (a,b) => rev * (a.stats.click_count.count - b.stats.click_count.count)
                        case 'ctr':
                            return (a,b) => rev * (a.stats.ctr - b.stats.ctr)
                        case 'conversions':
                            return (a,b) => rev * (a.stats.conversions - b.stats.conversions)
                        case 'startedPayments':
                            return (a,b) => rev * (a.stats.payment_count.count - b.stats.payment_count.count)
                        case 'finishedFayments':
                            return (a,b) => rev * (a.stats.purchase_count.count - b.stats.purchase_count.count)
                        case 'earned':
                            return (a,b) => rev * (a.stats.purchase_sum.sum - b.stats.purchase_sum.sum)
                    }
                }
                if (this.sorting.by) {
                    this.campaigns.sort(switchSorting(this.sorting.by, this.sorting.asc ? 1 : -1))
                }
            },
            loadDataAfter(axiosPromise) {
                axiosPromise
                    .then(() => axios.get(this.baseUrl))
                    .then(response => {
                        let data = response.data
                        this.loading = false
                        this.campaigns = data.campaigns
                        this.campaignsNotCompared = data.campaignsNotCompared
                        this.addUrl = data.addUrl
                        this.addAllUrl = data.addAllUrl
                        this.removeAllUrl = data.removeAllUrl
                        this.sort()
                    })
                    .catch(error => {
                        this.error = error
                        this.loading = false;
                    })
            },
            loadData() {
                this.loading = true
                this.loadDataAfter(Promise.resolve())
            },
            remove(url) {
                this.loading = true
                this.loadDataAfter(axios.delete(url))
            },
            addAll() {
                if (!this.addAllUrl) {
                    return
                }
                this.loading = true
                this.loadDataAfter(axios.post(this.addAllUrl))
            },
            addToComparison() {
                if (!this.addUrl) {
                    return
                }
                this.loading = true
                this.loadDataAfter(axios.put(this.addUrl.replace('CAMPAIGN_ID', this.campaignToAdd)))
            },
            removeAll() {
                if (!this.removeAllUrl) {
                    return
                }
                this.loading = true
                this.loadDataAfter(axios.post(this.removeAllUrl))
            }
        }
    }
</script>
