<template>
    <div>
        <div class="card-header">
            <h2>Compared campaigns<small></small></h2>
        </div>
        <div class="card-body card-padding">

            <div class="row m-t-20 m-b-20">
                <div class="col-md-4">
                    <v-select
                            v-model="campaignToAdd"
                            :options.sync="campaignsNotCompared"
                            option-value="id"
                            option-text="name"
                            placeholder="Select campaign"
                            title="Select campaign">
                    </v-select>
                </div>
                <div class="col-md-8 pull-left">
                    <button @click="add" class="btn palette-Cyan bg waves-effect m-r-5">Add to comparison</button>
                    <button @click="addAll" class="btn palette-Cyan bg waves-effect m-r-5">Add all campaigns</button>
                    <button @click="removeAll" class="btn palette-Red bg waves-effect">Remove all</button>
                </div>
            </div>

            <div style="position: relative" class="table-responsive">
                <loader :is-centered="false" :yes="loading"></loader>

                <table v-if="!noCampaigns" class="table table-striped">
                    <thead>
                        <tr>
                            <th @click="sort('name')" :class="sortingClass('name')">Campaign</th>
                            <th @click="sort('clicks')" :class="sortingClass('clicks')">Clicks</th>
                            <th @click="sort('ctr')" :class="sortingClass('ctr')">Click-through rate (CTR)</th>
                            <th @click="sort('conversions')" :class="sortingClass('conversions')">Conversions</th>
                            <th @click="sort('startedPayments')" :class="sortingClass('startedPayments')">Started payments</th>
                            <th @click="sort('finishedFayments')" :class="sortingClass('finishedFayments')">Finished payments</th>
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
                                {{ campaign.stats.click_count }}
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
                                {{ campaign.stats.payment_count}}
                            </td>
                            <td>
                                {{ campaign.stats.purchase_count }}
                            </td>
                            <td>
                                <span v-for="(sum, currency) in campaign.stats.purchase_sums">
                                {{ sum | round(2) }} {{ currency }}
                                </span>
                            </td>
                            <th>
                                <span class="actions">
                                    <button class="btn btn-sm palette-Cyan bg waves-effect"
                                            @click="remove(campaign.id)"
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

<script>
    import axios from 'axios'
    import Loader from '@remp/js-commons/js/components/Loader'
    import vSelect from '@remp/js-commons/js/components/vSelect.vue'

    export default {
        components: {
            vSelect, Loader
        },
        name: 'campaign-comparison',
        props: {},
        data: () => ({
            campaigns: [],
            campaignsNotCompared: [],
            campaignToAdd: null,
            loading: false,
            error: null,
            sorting: {
                by: null,
                asc: true
            }
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
                    }
                }
                if (this.sorting.by) {
                    this.campaigns.sort(switchSorting(this.sorting.by, this.sorting.asc ? 1 : -1))
                }
            },
            loadDataAfter(axiosPromise) {
                axiosPromise
                    .then(() => axios.get(route('comparison.json')))
                    .then(response => {
                        this.loading = false
                        this.campaigns = response.data.campaigns
                        this.campaignsNotCompared = response.data.campaignsNotCompared
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
            add() {
                if (!this.campaignToAdd) {
                    return
                }
                this.loading = true
                this.loadDataAfter(axios.put(route('comparison.remove', this.campaignToAdd)))
            },
            addAll() {
                this.loading = true
                this.loadDataAfter(axios.post(route('comparison.addAll')))
            },
            remove(campaignId) {
                this.loading = true
                this.loadDataAfter(axios.delete(route('comparison.remove', campaignId)))
            },
            removeAll() {
                this.loading = true
                this.loadDataAfter(axios.post(route('comparison.removeAll')))
            }
        }
    }
</script>
