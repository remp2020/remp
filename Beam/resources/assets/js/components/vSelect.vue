<template>
    <select :multiple="multiple" class="selectpicker" :data-live-search="livesearch">
        <option :value="option.value || option" v-for="option in options">
            {{ option.label || option.value || option }}
        </option>
    </select>
</template>

<script>
    let props = [
        'options',
        'value',
        'multiple',
        'livesearch',
    ];

    export default {
        name: "v-select",
        props: props,
        mounted: function () {
            let vm = this;
            $(this.$el).selectpicker('val', this.value !== null ? this.value : null);
            $(this.$el).on('changed.bs.select', function () {
                vm.$emit('input', $(this).val());
            });
            props.forEach((prop) => {
                this[prop.slice(1)] = this[prop];
            });
        },
        updated : function () {
            $(this.$el).selectpicker('refresh');
        },
        destroyed : function () {
            $(this.$el).selectpicker('destroy');
        }
    }
</script>
