<template>
    <section id="content">
        <div class="container">

            <div class="c-header">
                <h2>Dashboard</h2>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h2>Most read articles</h2>
                        </div>
                        <div class="card-body table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th style="width: 40px">Concurrents</th>
                                        <th>Article</th>
                                    </tr>
                                </thead>
                                <tbody name="table-row" is="transition-group">
                                    <tr v-for="article in articles" v-bind:key="article.article_id">
                                        <td><span class="concurrents-count">{{article.count}}</span></td>
                                        <td>
                                            <span class="c-black">{{article.title}}</span>
                                            <br />
                                            <template v-if="article.landing_page">
                                            </template>
                                            <template v-else>
                                                <small>{{ article.published_at | relativeDate }}</small>
                                            </template>
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
    import axios from 'axios'

    let props = {
        articlesUrl: {
            type: String,
            required: true
        }
    }

    export default {
        name: "dashboard-root",
        components: {  },
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


