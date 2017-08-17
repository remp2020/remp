<template>
    <select :data-type="dataType" :multiple="multiple" class="selectpicker" :data-live-search="livesearch" :disabled="disabled">
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
        'dataType',
        'disabled',
    ];

    export default {
        name: "v-select",
        props: props,
        mounted: function () {
            let vm = this;
            $(this.$el).selectpicker('val', this.value !== null ? this.value : null);
            $(this.$el).on('changed.bs.select', function () {
                let val = $(this).val();
                vm.$parent.$emit("select-changed", {
                    type: vm.dataType,
                    value: val,
                });
                vm.$emit('input', val);
            });
            props.forEach((prop) => {
                this[prop.slice(1)] = this[prop];
            });
        },
        updated : function () {
            $(this.$el).selectpicker('refresh');
            this.$emit('updated')
        },
        destroyed : function () {
            $(this.$el).selectpicker('destroy');
        }
    }
</script>
