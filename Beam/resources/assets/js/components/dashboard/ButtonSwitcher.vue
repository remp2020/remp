<template>
    <div class="btn-group btn-group-sm" :class="classes" role="group">
        <button v-for="option in intervalOptions"
                type="button"
                class="btn"
                :class="[option.selected ? 'btn-info' : 'btn-default']"
                @click="setSelectedInterval(option)">
            {{option.text}}
        </button>
    </div>
</template>

<script>
    let props = {
        // options should contain array with objects of format
        // {
        //    text: "This week",
        //    value: "value"
        // }
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
            default: []
        }
    };

    export default {
        name: "ButtonSwitcher",
        props: props,
        data() {
            return {
                intervalOptions: [],
            }
        },
        created() {
            let that = this
            this.options.forEach(function (item){
                that.intervalOptions.push({
                    text: item.text,
                    value: item.value,
                    selected: that.value === item.value
                })
            })
        },
        methods: {
            setSelectedInterval(option) {
                let that = this
                this.intervalOptions.forEach(function (item) {
                    item.selected = option.value === item.value
                })
                that.$emit('input', option.value)
            }
        }
    }
</script>