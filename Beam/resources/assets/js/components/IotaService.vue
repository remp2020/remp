<script type="text/javascript">
    import Axios from "axios"
    import EventHub from "./EventHub"

    export default {
        name: 'iota-service',
        props: {
            articleIds: {
                type: Array,
                required: true,
            },
            conversionMinuteRanges: {
                type: Array,
                default: function() {
                    return [
                        {"minutes": 60, "label": "1h"},
//                        {"minutes": 60*6, "label": "6h"},
//                        {"minutes": 60*24, "label": "24h"},
                    ];
                },
            },
            pageviewMinuteRanges: {
                type: Array,
                default: function() {
                    return [
                        {"minutes": 15, "label": "15m"},
                        {"minutes": 60, "label": "1h"},
                        {"minutes": 60*4, "label": "4h"},
                    ];
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
            this.fetchCommerceStats(now);
            this.fetchPageviewStats(now);
            this.fetchTitleVariantStats(now);
        },
        methods: {
            fetchCommerceStats: function(now) {
                for (let range of this.conversionMinuteRanges) {
                    let d = new Date(now.getTime());
                    d.setMinutes(d.getMinutes() - range.minutes);

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
                }
            },

            fetchPageviewStats: function(now) {
                for (let range of this.pageviewMinuteRanges) {
                    let d = new Date(now.getTime());
                    d.setMinutes(d.getMinutes() - range.minutes);

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
                for (let range of this.pageviewMinuteRanges) {
                    let d = new Date(now.getTime());
                    d.setMinutes(d.getMinutes() - range.minutes);

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
                                if (parseInt(group["count"]) === 0) {
                                    continue;
                                }

                                const titleVariant = group["tags"]["title_variant"]
                                const articleId = group["tags"]["article_id"]
                                if (!counts[articleId]) {
                                    counts[articleId] = {};
                                }
                                counts[articleId][titleVariant] = group["count"]
                            }
                            EventHub.$emit('content-title-variants-changed', range, counts)

                        })
                        .catch(function (error) {
                            console.warn(error);
                        });
                }
            },
        },


    }
</script>
