<template>
    <div class="m-t-20">

        <div v-if="loading" class="preloader pls-purple">
            <svg class="pl-circular" viewBox="25 25 50 50">
                <circle class="plc-path" cx="50" cy="50" r="20"></circle>
            </svg>
        </div>

        <template v-if="!loading && stats">
            <h4>Statistics</h4>

            <p v-if="emptyStats">No events found for selected filter.</p>
            <template v-else>
                <h5>Last {{stats.lastEvents.limit}} events:</h5>
                <ul>
                    <li v-for="item in stats.lastEvents.absoluteCounts">
                        {{item.name|capitalize}}: {{(item.count/stats.lastEvents.total)*100|roundNumber}} %
                    </li>
                </ul>

            </template>

            <template v-if="stats.pageviewEvents.length > 0">
                <h5>Pageviews</h5>
                <ul>
                    <li v-for="item in stats.pageviewEvents">
                        <b>State: </b>{{item.locked|choices('Locked article', 'Unlocked article')}}/{{item.signed_in|choices('Signed In', 'Unsigned')}},
                        <b>avg. timespent:</b> {{item.timespent_avg|roundNumber}} s
                        (total: {{item.group_count}})
                    </li>
                </ul>
            </template>

            <template v-if="stats.commerceEvents.length > 0">
                <h5>Commerce events</h5>
                <ul>
                    <li v-for="item in stats.commerceEvents">
                        {{item.step|capitalize}}, funnel ID: {{item.funnel_id}} (total: {{item.group_count}})
                    </li>
                </ul>
            </template>

            <template v-if="stats.generalEvents.length > 0">
                <h5>Other events</h5>
                <ul>
                    <li v-for="item in stats.generalEvents">
                        {{item.action|capitalize}}:{{item.category|capitalize}} (total: {{item.group_count}})
                    </li>
                </ul>
            </template>
        </template>
    </div>
</template>

<script>
    Vue.filter('roundNumber', n => parseFloat(n).toFixed(2))
    Vue.filter('choices', (condition, a1, a2) => condition ? a1 : a2)

    export default {
        name: "user-path",
        props: {
            loading: {
                type: Boolean,
                default: false
            },
            error: {
                type: String,
                default: null
            },
            stats: {
                type: Object,
                required: true
            }
        },
        computed: {
            emptyStats() {
                return this.stats.pageviewEvents.length === 0 &&
                    this.stats.commerceEvents.length === 0 &&
                    this.stats.generalEvents.length === 0
            }

        }
    }
</script>


