<template>
    <section id="content">
        <div class="container">

            <campaign-stats
                :name="name"
                :edit-link="editLink"
                :data="campaignData"
                :error="error"
                :loading="loading"
            ></campaign-stats>

            <campaign-stats-results
                v-if="!error && loaded"
                :variants="variants"
                :variant-banner-links="variantBannerLinks"
                :data="variantsData"
                :campaignData="campaignData"
            ></campaign-stats-results>

            <variant-stats
                v-for="variant in variantsList"
                :key="variant.uuid"

                :variant="variant"
                :variant-banner-link="variantBannerLinks[variant.uuid] || null"
                :variant-banner-text="variantBannerTexts[variant.uuid] || null"
                :data="variantsData[variant.uuid]"
                :error="error"
                :loading="loading"
            ></variant-stats>

            <h3 class="deleted-variants-title">Deleted variants</h3>

            <variant-stats
                v-for="variant in deletedVariantsList"
                :key="variant.uuid"

                :variant="variant"
                :variant-banner-link="variantBannerLinks[variant.uuid] || null"
                :variant-banner-text="variantBannerTexts[variant.uuid] || null"
                :data="variantsData[variant.uuid]"
                :error="error"
                :loading="loading"
            ></variant-stats>

        </div>
    </section>

</template>

<script>
    import CampaignStats from './stats/CampaignStats'
    import VariantStats from './stats/VariantStats'
    import CampaignStatsResults from './stats/CampaignStatsResults'

    let props = {
        url: {
            type: String,
            required: true
        },
        name: {
            type: String,
            required: true,
        },
        editLink: {
            type: String,
            required: true,
        },
        variants: {
            type: Array,
            required: true
        },
        variantBannerLinks: {
            type: Object,
            required: true
        },
        variantBannerTexts: {
            type: Object,
            required: true
        },
        from: {
            type: String,
            required: true
        },
        to: {
            type: String,
            required: true
        },
        timezone: {
            type: String,
            required: true
        }
    };

    export default {
        components: {
            CampaignStats,
            VariantStats,
            CampaignStatsResults
        },
        props: props,
        data() {
            return {
                variantsList: [],
                deletedVariantsList: [],
                campaignData: {},
                variantsData: {},
                loading: true,
                loaded: false,
                error: ""
            }
        },
        mounted() {
            this.prepareVariantLists().then(() => {
                this.loadData();
            })
        },
        watch: {
            from() {
                this.loadData()
            },
            to() {
                this.loadData()
            }
        },
        methods: {
            prepareVariantLists() {
                return new Promise((resolve) => {
                    for (let ii = 0; ii < this.variants.length; ii++) {
                        if (this.variants[ii].deleted_at) {
                            this.deletedVariantsList.push(this.variants[ii]);
                            continue;
                        }

                        this.variantsList.push(this.variants[ii]);
                    }

                    resolve();
                });
            },
            loadData() {
                let vm = this;
                vm.error = "";
                vm.loading = true;

                $.ajax({
                    method: 'POST',
                    url: vm.url,
                    data: {
                        from: vm.from,
                        to: vm.to,
                        tz: vm.timezone,
                        _token: document.head.querySelector("[name=csrf-token]").content
                    },
                    dataType: 'JSON',
                    success(resp) {
                        vm.variantsData = resp.variants;
                        vm.campaignData = resp.campaign;

                        vm.loading = false;
                        vm.loaded = true;
                    },
                    error(xhr, status, error) {
                        vm.loading = false;
                        let body = JSON.parse(xhr.responseText);
                        vm.error = body.message;
                    }
                })
            }
        }
    }
</script>

<style>
    .size-1of4 {
        width: 25%;
    }

    #campaign-singles-grid[data-columns]::before {
        content: '1 .column.size-1of1';
    }

    #variant-singles-grid[data-columns]::before {
        content: '2 .column.size-1of2';
    }

    .card-ctr .card-body,
    .card-ctr .card-header {
        font-weight: bold;
    }
    .card-conversions .card-body,
    .card-conversions .card-header {
        font-weight: bold;
    }
    .card-earned .card-body,
    .card-earned .card-header {
        font-weight: bold;
    }

    .deleted-variants-title {
        margin-top: 1.6em;
        margin-bottom: 0.5em;
    }
</style>
