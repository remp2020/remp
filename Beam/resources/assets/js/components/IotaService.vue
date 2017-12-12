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
            fetchArticleStats: function(ids) {
                let queryIds = '';
                for (let aid of ids) {
                    queryIds = queryIds + '&ids=' + aid;
                }
                for (let range of this.hourRanges) {
                    this.request(range, queryIds);
                }
            },
            

            request: function(hourRange, queryIds) {
                let vm = this
                let d = new Date();
                d.setHours(d.getHours() - hourRange);

                Axios.get(this.baseUrl + '/journal/commerce/purchase/sum?time_after=' + d.toISOString() + '&filter_by=articles' + queryIds + '&group=1')
                    .then(function (response) {
                        EventHub.$emit('content-conversions-revenue-changed', hourRange, response.data.sums)
                    })
                    .catch(function (error) {
                        console.log(error);
                    });

                Axios.get(this.baseUrl + '/journal/commerce/purchase/count?time_after=' + d.toISOString() + '&filter_by=articles' + queryIds + '&group=1')
                    .then(function (response) {
                        EventHub.$emit('content-conversions-counts-changed', hourRange, response.data.counts)
                    })
                    .catch(function (error) {
                        console.log(error);
                    });

//                Axios.get(this.baseUrl + '/journal/pageviews/count?time_after=' + d.toISOString() + '&filter_by=articles' + queryIds + '&group=1')
//                    .then(function (response) {
//                        EventHub.$emit('content-pageviews-changed', hourRange, response.data.counts)
//                    })
//                    .catch(function (error) {
//                        console.log(error);
//                    });
            },
        },


    }
</script>
