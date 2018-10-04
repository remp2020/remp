<template>
    <section id="content">
        <div class="container">

            <div class="c-header">
                <h2>Dashboard</h2>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <time-histogram ref="histogram" :url="timeHistogramUrl"></time-histogram>
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
                                        <td>
                                            <template v-if="!article.landing_page && article.conversion_rate">
                                                {{ article.conversion_rate }}
                                                <br /><small>({{ article.conversions_count }} total)</small>
                                            </template>
                                        </td>
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
    </section>
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
                articles: null
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
                axios
                    .get(this.articlesUrl)
                    .then(response => (this.articles = response.data))
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


