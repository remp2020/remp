<template>
    <div class="card">
        <div class="card-body">
            <dashboard-graph ref="histogram"
                            :url="timeHistogramUrl"
                            :url-new="timeHistogramUrlNew"
                            :concurrents="totalConcurrents"
                            :mobile-concurrents-percentage="mobileConcurrentsPercentage"
                            :external-events="externalEvents"
                            :show-interval7-days="options.article_traffic_graph_show_interval_7d"
                            :show-interval30-days="options.article_traffic_graph_show_interval_30d"
            >

                <!-- Selector for property tokens, required only in public dashboard -->
                <template v-if="accountPropertyTokens" v-slot:additional-settings>
                        <form style="width: 200px; display: inline-block; margin-left: 20px" method="post" :action="propertiesSwitchUrl">
                            <input type="hidden" name="_token" :value="csrfToken" v-if="csrfToken">
                            <select name="token" class="selectpicker" onchange="javascript:this.form.submit()">
                                <template v-for="account in accountPropertyTokens">
                                    <optgroup v-if="account.name != null" :label="account.name">
                                        <option v-for="token in account.tokens"
                                                :selected="token.selected"
                                                :value="token.uuid">{{token.name}}</option>
                                    </optgroup>
                                    <template v-else>
                                        <option v-for="token in account.tokens"
                                                :selected="token.selected"
                                                :value="token.uuid">{{token.name}}</option>
                                    </template>
                                </template>
                            </select>
                        </form>
                </template>

            </dashboard-graph>

            <div class="row" style="padding-top: 10px">
                <div class="col-md-12">
                    <div class="table-responsive">
                        <table class="concurrents-table table">
                            <thead>
                                <tr>
                                    <th style="width: 5%; text-align: center">
                                        <i data-toggle="tooltip"
                                           data-original-title="Concurrents"
                                           class="icon-header zmdi zmdi-accounts zmdi-hc-fw"></i>
                                    </th>
                                    <th style="text-align: left">Article</th>
                                    <th id="pageviews-chart-column" style="text-align: center">
                                        <i data-toggle="tooltip"
                                           data-original-title="Pageviews trend"
                                           class="icon-header zmdi zmdi-trending-up zmdi-hc-fw"></i>
                                        <span style="position: relative; bottom: 2px; text-transform: lowercase">24 hours</span>
                                    </th>
                                    <th style="width: 7%; text-align: center">
                                        <i data-toggle="tooltip"
                                           data-original-title="Engaged Time"
                                           class="icon-header zmdi zmdi-time zmdi-hc-fw"></i>
                                    </th>
                                    <th style="width: 7%; text-align: center">
                                        <span data-toggle="tooltip"
                                              :data-original-title="conversionRateDescription"
                                              class="icon-header">%</span>
                                    </th>
                                    <th style="width: 7%; text-align: center">
                                        <i data-toggle="tooltip"
                                           data-original-title="Total conversions"
                                           class="icon-header zmdi zmdi-money zmdi-hc-fw"></i>
                                    </th>
                                    <th style="width: 7%; text-align: center">
                                        <i data-toggle="tooltip"
                                           data-original-title="Unique browsers"
                                           class="icon-header zmdi zmdi-globe zmdi-hc-fw"></i>
                                    </th>
                                </tr>
                            </thead>
                            <tbody name="table-row" is="transition-group">
                                <tr v-for="article in articles" v-bind:key="article.landing_page ? article.title : article.external_article_id">
                                    <td style="text-align: right"><span class="concurrents-count">
                                            <animated-integer :value="article.count"></animated-integer>
                                        </span>
                                    </td>
                                    <td style="min-width: 200px" class="ellipsis">
                                        <template v-if="article.landing_page">
                                            <span class="c-black">{{article.title}}</span>
                                        </template>
                                        <template v-else>
                                            <span v-if="article.has_title_test"
                                                  class="ab-test"
                                                  title="Title A/B test">A/B</span>

                                            <span v-if="article.has_image_test"
                                                  class="ab-test image"
                                                  title="Image A/B test">IMG A/B</span>

                                            <a class="c-black" :href="article.url">{{article.title}}</a>
                                            <br />
                                            <small>{{ article.published_at | relativeDate }}</small>
                                        </template>
                                    </td>
                                    <td v-if="!article.landing_page && article.chartData" :id="`sparkline-${article.external_article_id}`">
                                        <sparkline-chart :chart-container-id="`sparkline-${article.external_article_id}`" :chart-data="article.chartData"></sparkline-chart>
                                    </td>
                                    <td v-else></td>
                                    <td style="text-align: center">
                                        {{ article.avg_timespent_string || '' }}
                                    </td>
                                    <td style="text-align: center" v-if="!article.landing_page && article.conversion_rate"
                                        :class="article.conversion_rate_color">
                                        {{ article.conversion_rate }}
                                    </td>
                                    <td v-else></td>
                                    <td style="text-align: center" v-if="!article.landing_page && article.conversions_count"
                                        :class="article.conversions_count_color">
                                        {{ article.conversions_count | formatNumber }}
                                    </td>
                                    <td v-else></td>
                                    <td style="text-align: right">
                                        {{ article.unique_browsers_count | formatNumber }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped type="text/css">
    .table-row-move {
        transition: transform 1s;
    }
    .concurrents-count {
        font-size: 16px;
    }

    abbr {
        border: none;
    }

    .icon-header {
        font-size: 18px;
    }

    td.low-color {
        background-color: #EEFBF3;
    }

    td.medium-color {
        background-color: #D3F4E0;
    }

    td.high-color {
        background-color: #9BE6BA;
    }

    td.no-color {
        background-color: #fff;
    }

    .ellipsis {
        max-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .concurrents-table td, .concurrents-table th {
        padding: 6px;
    }

    span.ab-test {
        display: inline-block;
        border-radius: 2px;
        background: #ff180c;
        padding: 1px 2px ;
        font-size: 8px;
        color: #fff;
        vertical-align: top;
        margin-top: 3px;
        cursor: pointer;
    }

    span.ab-test.image {
        background: #28f16f;
    }

    @media (max-width: 500px) {
        #pageviews-chart-column {
            min-width: 50px;
        }
    }

    @media (min-width: 501px) {
        #pageviews-chart-column {
            width: 12%;
        }
    }

</style>

<script>
    import AnimatedInteger from './AnimatedInteger.vue'
    import Options from './Options.vue'
    import axios from 'axios'
    import DashboardGraph from "./DashboardGraph";
    import SparklineChart from "./SparklineChart";

    let props = {
        articlesUrl: {
            type: String,
            required: true
        },
        timeHistogramUrl: {
            type: String,
            required: true
        },
        // Just for testing of new graph
        // TODO remove once testing is done
        timeHistogramUrlNew: {
            type: String,
            required: true
        },
        options: {
            type: Object,
            required: false
        },
        accountPropertyTokens: {
            type: Object,
            required: false
        },
        csrfToken: {
            type: String,
            required: false
        },
        conversionRateMultiplier: {
            type: Number,
            required: false
        },
        externalEvents: {
            type: Array,
            default: () => [],
        }
    }

    const REFRESH_DATA_TIMEOUT_MS = 20000
    let loadDataTimer = null

    function conversionsCountColor(count, options) {
        if (!options) {
            return 'no-color'
        }

        count = parseInt(count)
        if (count > options['conversions_count_threshold_high']) {
            return 'high-color'
        } else if (count > options['conversions_count_threshold_medium']) {
            return 'medium-color'
        } else if (count > options['conversions_count_threshold_low']) {
            return 'low-color'
        } else {
            return 'no-color'
        }
    }

    function conversionRateColor(rate, options) {
        if (!options) {
            return 'no-color'
        }

        rate = parseFloat(rate)
        if (rate > options['conversion_rate_threshold_high']) {
            return 'high-color'
        } else if (rate > options['conversion_rate_threshold_medium']) {
            return 'medium-color'
        } else if (rate > options['conversion_rate_threshold_low']) {
            return 'low-color'
        } else {
            return 'no-color'
        }
    }

    export default {
        name: "dashboard-root",
        components: {SparklineChart, DashboardGraph, AnimatedInteger, Options },
        props: props,
        created() {
            document.addEventListener('visibilitychange', this.visibilityChanged)
            this.loadData()
            loadDataTimer = setInterval(this.loadData, REFRESH_DATA_TIMEOUT_MS)

            this.conversionRateDescription = "Conversion rate = Conversions/Unique visitors"
            if (this.conversionRateMultiplier) {
                this.conversionRateDescription = "Conversion rate = (Conversions/Unique visitors) x " + this.conversionRateMultiplier.toString()
            }
        },
        beforeDestroy() {
            document.removeEventListener('visibilitychange', this.visibilityChanged)
        },
        destroyed() {
            clearInterval(loadDataTimer)
        },
        data() {
            return {
                articles: null,
                totalConcurrents: 0,
                mobileConcurrentsPercentage: 0,
                propertiesSwitchUrl: route('properties.switch'),
                conversionRateDescription: "",
                error: null,
                loading: false,
            }
        },
        computed: {
            settings() {
                return this.$store.state.settings
            }
        },
        watch: {
            settings(value) {
                this.reload()
            }
        },
        methods: {
            visibilityChanged(event) {
                if (document.visibilityState === 'visible') {
                    this.reload()
                    this.$refs["histogram"].reload()
                }
            },
            reload() {
                clearInterval(loadDataTimer)
                this.loadData()
                loadDataTimer = setInterval(this.loadData, REFRESH_DATA_TIMEOUT_MS)
            },
            loadData() {
                if (this.loading) {
                    return
                }

                this.loading = true
                let that = this
                axios
                    .post(this.articlesUrl, {
                        settings: this.settings
                    })
                    .then(function(response){
                        that.loading = false
                        that.articles = response.data.articles.map(function(item){
                            item.conversion_rate_color = conversionRateColor(item.conversion_rate, that.options)
                            item.conversions_count_color = conversionsCountColor(item.conversions_count, that.options)
                            return item
                        })
                        that.totalConcurrents = response.data.totalConcurrents
                        that.mobileConcurrentsPercentage = response.data.mobileConcurrentsPercentage
                    })
                    .catch(error => {
                        this.error = error
                        this.loading = false;
                    })
            },
        },
        filters: {
            relativeDate(value) {
                if (!value) return ''
                return moment(value).locale('en').fromNow()
            }
        }
    }
</script>


