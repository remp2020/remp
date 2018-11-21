<template>
    <span>{{value}}</span>
</template>

<script>
    export default {
        name: "date-formatter",
        props: {
            date: {
                type: String,
                required: true
            },
            format: {
                type: String,
                required: false,
                default: "llll"
            }
        },
        data() {
            return {
                value: moment(this.date).format(this.format)
            }
        }
    }
</script>


