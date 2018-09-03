<template>
    <section id="content">
        <div class="container">

            <div class="c-header">
                <h2>Dashboard XYZ</h2>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <time-histogram :url="timeHistogramUrl"></time-histogram>
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
                                        <th style="width: 40px">Conversions</th>
                                        <th style="width: 40px">Unique browsers</th>
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
                                            {{ article.conversions_count }}
                                        </td>
                                        <td>
                                            {{ article.unique_browsers_count }}
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

<style type="text/css">
    .table-row-move {
        transition: transform 1s;
    }
    .concurrents-count {
        font-size: 16px;
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

    export default {
        name: "dashboard-root",
        components: { AnimatedInteger, TimeHistogram },
        props: props,
        created() {
            this.loadData()
            setInterval(this.loadData, 20000)
        },
        destroyed() {
            clearInterval(this.loadData)
        },
        data() {
            return {
                articles: null
            }
        },
        methods: {
            loadData() {
                axios
                    .get(this.articlesUrl)
                    .then(response => (this.articles = response.data))
            }
        },
        filters: {
            relativeDate(value) {
                if (!value) return ''
                return moment.utc(value).locale('en').fromNow()
            }
        }
    }
</script>


