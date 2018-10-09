<template>
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <time-histogram ref="histogram"
                                :url="timeHistogramUrl"
                                :concurrents="totalConcurrents"
                ></time-histogram>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h2>Most read articles</h2>
                    </div>
                    <div class="card-body table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th style="width: 40px">Concurrents</th>
                                    <th style="text-align: left">Article</th>
                                    <th style="width: 40px">Engaged Time</th>
                                    <th style="">
                                        <abbr title="(Conversions/Unique visitors) x 10000">
                                            Conversions rate
                                        </abbr>
                                    </th>
                                    <th>Total conversions</th>
                                    <th style="width: 40px; text-align: right">Unique browsers</th>
                                </tr>
                            </thead>
                            <tbody name="table-row" is="transition-group">
                                <tr v-for="article in articles" v-bind:key="article.external_article_id">
                                    <td><span class="concurrents-count">
                                            <animated-integer :value="article.count"></animated-integer>
                                        </span>
                                    </td>
                                    <td>
                                        <template v-if="article.landing_page">
                                            <span class="c-black">{{article.title}}</span>
                                        </template>
                                        <template v-else>
                                            <a class="c-black" :href="article.url">{{article.title}}</a>
                                            <br />
                                            <small>{{ article.published_at | relativeDate }}</small>
                                        </template>
                                    </td>
                                    <td>
                                        {{ article.avg_timespent_string || '' }}
                                    </td>
                                    <td v-if="!article.landing_page && article.conversion_rate"
                                        :class="article.conversion_rate_color">
                                        {{ article.conversion_rate }}
                                    </td>
                                    <td v-else></td>
                                    <td v-if="!article.landing_page && article.conversions_count"
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
</style>

<script>
    import AnimatedInteger from './AnimatedInteger.vue'
    import TimeHistogram from './TimeHistogram.vue'
    import axios from 'axios'

    let props = {
        articlesUrl: {
            type: String,
            required: true
        },
        timeHistogramUrl: {
            type: String,
            required: true
        }
    }

    const REFRESH_DATA_TIMEOUT_MS = 7000
    let loadDataTimer = null

    // empirically defined values
    function conversionsCountColor(count) {
        count = parseInt(count)
        if (count > 13) {
            return 'high-color'
        } else if (count > 8) {
            return 'medium-color'
        } else if (count > 3) {
            return 'low-color'
        } else {
            return 'no-color'
        }
    }

    // empirically defined values
    function conversionRateColor(rate) {
        rate = parseFloat(rate)
        if (rate > 7.0) {
            return 'high-color'
        } else if (rate > 5.0) {
            return 'medium-color'
        } else if (rate > 3.0) {
            return 'low-color'
        } else {
            return 'no-color'
        }
    }

    export default {
        name: "dashboard-root",
        components: { AnimatedInteger, TimeHistogram },
        props: props,
        created() {
            document.addEventListener('visibilitychange', this.visibilityChanged)
            this.loadData()
            loadDataTimer = setInterval(this.loadData, REFRESH_DATA_TIMEOUT_MS)
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
                totalConcurrents: 0
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
                let that = this
                axios
                    .get(this.articlesUrl)
                    .then(function(response){
                        that.articles = response.data.top20.map(function(item){
                            item.conversion_rate_color = conversionRateColor(item.conversion_rate)
                            item.conversions_count_color = conversionsCountColor(item.conversions_count)
                            return item
                        })
                        that.totalConcurrents = response.data.totalConcurrents
                    })
            }
        },
        filters: {
            relativeDate(value) {
                if (!value) return ''
                return moment(value).locale('en').fromNow()
            }
        }
    }
</script>


