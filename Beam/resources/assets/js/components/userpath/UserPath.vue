<template>
    <div class="m-t-20">
        <template v-if="stats">
            <h4>Statistics</h4>

            <h5>Pageviews</h5>
            <ul>
                <li v-for="item in stats.pageviewEvents">
                    <b>State: </b>{{item.locked|choices('Locked article', 'Unlocked article')}}/{{item.signed_in|choices('Signed In', 'Unsigned')}},
                    <b>avg. timespent:</b> {{item.timespent_avg|roundNumber}} s
                    (total: {{item.group_count}})

                </li>
            </ul>

            <h5>Commerce events</h5>
            <ul>
                <li v-for="item in stats.commerceEvents">
                    {{item.step|capitalize}}, funnel ID: {{item.funnel_id}} (total: {{item.group_count}})
                </li>
            </ul>

            <h5>Other events</h5>
            <ul>
                <li v-for="item in stats.generalEvents">
                    {{item.action|capitalize}}:{{item.category|capitalize}} (total: {{item.group_count}})
                </li>
            </ul>
        </template>
    </div>
</template>

<script>
    Vue.filter('roundNumber', n => parseFloat(n).toFixed(2))
    Vue.filter('choices', (statement, a1, a2) => statement ? a1 : a2)

    export default {
        name: "user-path",
        props: {
            loading: {
                type: Boolean,
                default: false
            },
            stats: {
                required: true
            }
        },
        data() {
            return {
//                tweeningValue: 0
            }
        },
        methods: {

        }
    }
</script>


