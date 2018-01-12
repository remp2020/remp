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
                    return [1,4,24];
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

            this.fetchArticleStats(this.articleIds);
        },
        methods: {
            fetchArticleStats: function(articleIds) {
                for (let range of this.hourRanges) {
                    this.request(range, articleIds);
                }
            },
            
            request: function(hourRange, articleIds) {
                let d = new Date();
                d.setHours(d.getHours() - hourRange);

                const payload = {
                    "time_after": d.toISOString(),
                    "filter_by": [
                        {
                            "tag": "article_id",
                            "values": articleIds,
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
                        EventHub.$emit('content-conversions-revenue-changed', hourRange, sums)
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
                        EventHub.$emit('content-conversions-counts-changed', hourRange, counts)
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
                        EventHub.$emit('content-pageviews-changed', hourRange, counts)
                    })
                    .catch(function (error) {
                        console.warn(error);
                    });
            },
        },


    }
</script>
