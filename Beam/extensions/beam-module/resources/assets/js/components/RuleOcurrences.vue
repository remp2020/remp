<template>
    <ul>
        <li v-for="item in formattedOcurrences">{{ item }}</li>
    </ul>
</template>

<script type="text/javascript">
    import RRule from 'rrule'

    export default {
        name: "RuleOcurrences",
        props: {
            rrule: {
                type: String
            }
        },
        computed: {
            formattedOcurrences() {
                if (this.rrule === null){
                    return []
                }
                let rule = RRule.fromString(this.rrule)
                // Max 10 occurrences
                return rule.between(new Date(), new Date(2099, 1, 1), true, (d, index) => {
                    return index <= 10
                }).map(item => moment(item).format('LLLL'))
            }
        },
    }
</script>