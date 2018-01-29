<script type="text/javascript">
    import Axios from "axios"
    import EventHub from "./EventHub"

    export default {
        name: 'conversion-service',
        props: {
            articleIds: {
                type: Array,
                required: true,
            },
            hourRanges: {
                type: Array,
                default: function() {
                    return [1,4,12];
                },
            },
            baseUrl: {
                type: String,
                required: true,
            },
            httpHeaders: {
                type: Object,
                default: function() {
                    return {};
                },
            },
        },
        created: function() {
            let vm = this;
            Object.keys(this.httpHeaders).forEach(function (name) {
                Axios.defaults.headers.common[name] = vm.httpHeaders[name];
            });
            Axios.defaults.headers.common['Content-Type'] = 'application/json';

            let now = new Date();
            this.fetchArticleStats(now);
            this.fetchTitleVariantStats(now);
        },
        methods: {
            fetchArticleStats: function(now) {
                for (let range of this.hourRanges) {
                    let d = new Date(now.getTime());
                    d.setHours(d.getHours() - range);

                    const payload = {
                        "time_after": d.toISOString(),
                        "filter_by": [
                            {
                                "tag": "article_id",
                                "values": this.articleIds,
                            },
                        ],
                        "group_by": [
                            "article_id",
                        ],
                    }

                    Axios.post(this.baseUrl + '/journal/commerce/steps/purchase/sum', payload)
                        .then(function (response) {
                            let sums = {}
                            for (const group of response.data) {
                                sums[group["tags"]["article_id"]] = group["sum"]
                            }
                            EventHub.$emit('content-conversions-revenue-changed', range, sums)
                        })
                        .catch(function (error) {
                            console.warn(error);
                        });

                    Axios.post(this.baseUrl + '/journal/commerce/steps/purchase/count', payload)
                        .then(function (response) {
                            let counts = {}
                            for (const group of response.data) {
                                counts[group["tags"]["article_id"]] = group["count"]
                            }
                            EventHub.$emit('content-conversions-counts-changed', range, counts)
                        })
                        .catch(function (error) {
                            console.warn(error);
                        });

                    Axios.post(this.baseUrl + '/journal/pageviews/actions/load/count', payload)
                        .then(function (response) {
                            let counts = {}
                            for (const group of response.data) {
                                counts[group["tags"]["article_id"]] = group["count"]
                            }
                            EventHub.$emit('content-pageviews-changed', range, counts)
                        })
                        .catch(function (error) {
                            console.warn(error);
                        });
                }
            },

            fetchTitleVariantStats: function(now) {
                for (let range of this.hourRanges) {
                    let d = new Date(now.getTime());
                    d.setHours(d.getHours() - range);

                    const titleVariantPayload = {
                        "time_after": d.toISOString(),
                        "filter_by": [
                            {
                                "tag": "article_id",
                                "values": this.articleIds,
                            },
                        ],
                        "group_by": [
                            "article_id",
                            "social",
                            "title_variant",
                        ],
                    };

                    Axios.post(this.baseUrl + '/journal/pageviews/actions/load/count', titleVariantPayload)
                        .then(function (response) {
                            let counts = {}
                            for (const group of response.data) {
                                if (group["tags"]["social"] !== "") {
                                    // social networks always get variant A due to the nature of replacing
                                    // we include only direct pageviews into the A/B test
                                    continue
                                }

                                const titleVariant = group["tags"]["title_variant"]
                                const articleId = group["tags"]["article_id"]
                                if (!counts[titleVariant]) {
                                    counts[titleVariant] = {}
                                }
                                counts[titleVariant][articleId] = group["count"]
                            }
                            Object.keys(counts).forEach(function(variant) {
                                EventHub.$emit('content-title-variants-changed', range, variant, counts[variant])
                            });

                        })
                        .catch(function (error) {
                            console.warn(error);
                        });
                }
            },
        },


    }
</script>
