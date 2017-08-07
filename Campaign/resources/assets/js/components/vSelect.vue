<template>
    <select :multiple="multiple" class="selectpicker" :data-live-search="livesearch">
        <option :value="option[opValue]" v-for="option in options">@{{ option[opLabel] }}</option>
    </select>
</template>

<script type="text/javascript">
    export default {
        props : [
            "options",
            "opLabel",
            "opValue",
            "value",
            "multiple",
            "livesearch",
        ],
        mounted : function () {
            let vm = this;
            $(vm.$el).selectpicker("val", vm.value !== null ? vm.value : null);
            $(vm.$el).on("changed.bs.select", function () {
                vm.$emit("input", $(vm).val());
            });
        },
        updated : function () {
            $(this.$el).selectpicker("refresh");
        },
        destroyed : function () {
            $(this.$el).selectpicker("destroy");
        }
    }
</script>