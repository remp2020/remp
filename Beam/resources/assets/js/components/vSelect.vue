<template>
    <div>
        <div class="row">
            <div class="col-xs-10">
                <select :name="name" :data-type="dataType" :multiple="multiple" class="selectpicker" :data-live-search="livesearch" :disabled="disabled">
                    <option :value="option.value || option" v-for="option in options">
                        {{ option.label || option.value || option }}
                    </option>
                </select>
                <input v-show="customInput" v-model="customValue" :name="name" placeholder="e.g. my-event" title="Custom value" type="text" required="required" class="form-control fg-input">
            </div>
            <div class="col-xs-2">
                <span v-on:click="customInput = !customInput" :class="[{'palette-Blue-Grey bg': customInput}, {'btn-default': !customInput}, 'btn', 'waves-effect']">
                    <i class="zmdi zmdi-hc-lg zmdi-edit"></i>
                </span>
            </div>
        </div>
    </div>
</template>

<script>
    let props = [
        'name',
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
        data: function() {
            return {
                customInput: false,
                customValue: null,
            }
        },
        mounted: function () {
            let vm = this;
            let $select = $(this.$el).find('select');
            if (this.value !== null) {
                $select.selectpicker('val', this.value !== null ? this.value : null);
                this.customValue = this.value;
            }

            $select.on('changed.bs.select', function () {
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
            $(this.$el).val(this.value);
            $(this.$el).selectpicker('refresh');
            this.$emit('updated')
        },
        destroyed : function () {
            $(this.$el).selectpicker('destroy');
        },
        watch: {
            customInput: function(val) {
                if (val) {
                    $(this.$el).find('.selectpicker').selectpicker('hide');
                } else {
                    $(this.$el).find('.selectpicker').selectpicker('show');
                }
            },
        },
    }
</script>
