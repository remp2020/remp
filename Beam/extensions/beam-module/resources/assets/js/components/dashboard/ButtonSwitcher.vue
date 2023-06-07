<style>
    div.btn-group {
        margin-right: 10px;
        margin-bottom: 5px;
    }
</style>

<template>
    <div>
        <div class="btn-group btn-group-sm" :class="classes" role="group" v-for="group in intervalGroups">
            <button v-for="option in intervalOptions[group]"
                    type="button"
                    class="btn"
                    :class="[option.selected ? 'btn-info' : 'btn-default']"
                    @click="setSelectedInterval(option)">
                {{option.text}}
            </button>
        </div>
    </div>
</template>

<script>
    let props = {
        options: {
            type: Array,
            required: true
        },
        value: {
            type: String,
            required: true
        },
        classes: {
            type: Array,
            default: () => []
        }
    }

    export default {
        name: "ButtonSwitcher",
        props: props,
        data() {
            return {
                intervalOptions: {},
                intervalGroups: []
            }
        },
        created() {
            let that = this
            this.options.forEach(function (item) {
                if (!item.group) {
                    item.group = 0
                }
                if (!(item.group in that.intervalOptions)) {
                    Vue.set(that.intervalOptions, item.group, [])
                }
                that.intervalOptions[item.group].push({
                    text: item.text,
                    value: item.value,
                    selected: that.value === item.value
                })
            })

            this.intervalGroups = Object.keys(this.intervalOptions).sort()
        },
        methods: {
            setSelectedInterval(option) {
                let that = this
                Object.values(this.intervalOptions).forEach(function (items) {
                    items.forEach(function (item) {
                        item.selected = option.value === item.value
                    })
                })
                that.$emit('input', option.value)
            }
        }
    }
</script>
