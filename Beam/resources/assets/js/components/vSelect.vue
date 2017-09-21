<template>
    <div>
        <div v-if="allowCustomValue" class="row">
            <div class="col-xs-10">
                <select :name="name" :data-type="dataType" :multiple="multiple" class="selectpicker" :data-live-search="livesearch" :disabled="disabled" title="Please select">
                    <option :value="option.value || option" v-for="option in options">
                        {{ option.label || option.value || option }}
                    </option>
                </select>
                <input v-on:blur="customValueUpdated" v-show="customInput" v-model="customValue" :disabled="!this.customInput" :name="name" placeholder="e.g. my-event" title="Custom value" type="text" required="required" class="form-control fg-input">
            </div>
            <div class="col-xs-2">
                <button type="button" :disabled="this.optionsEmpty()" v-on:click="customInput = !customInput" :class="[{'palette-Blue-Grey bg': customInput}, {'btn-default': !customInput}, 'btn', 'waves-effect']">
                    <i class="zmdi zmdi-hc-lg zmdi-edit"></i>
                </button>
            </div>
        </div>
        <div v-else>
            <select :name="name" :data-type="dataType" :multiple="multiple" class="selectpicker" :data-live-search="livesearch" title="Please select">
                <option v-bind:data-subtext="option.sublabel" :value="option.value || option" v-for="option in options">
                    {{ option.label || option.value || option }}
                </option>
            </select>
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
        'allowCustomValue',
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
            $select.selectpicker();
            $(this.$el).find('select').on('changed.bs.select', function () {
                let val = $(this).val();
                vm.emitValueChanged(val);
            });
            if (this.value !== null) {
                $select.selectpicker('val', this.value !== null ? this.value : null);
                if (this.allowCustomValue) {
                    this.customValue = this.value;
                    if (!this.inOptions(this.value)) {
                        this.customInput = true;
                    }
                }
            }
            if (this.options.length === 0) {
                this.customInput = true;
            }
        },
        updated: function () {
            let $select = $(this.$el).find('select');
            $select.val(this.value);
            $select.selectpicker('refresh');
            this.$emit('updated')
        },
        destroyed: function () {
            $(this.$el).find('select').selectpicker('destroy');
        },
        watch: {
            customInput: function(val) {
                if (!this.allowCustomValue) {
                    return;
                }
                let $select = $(this.$el).find('.selectpicker');
                if (val) {
                    $select.selectpicker('hide');
                    this.customValue = $select.selectpicker('val') || this.customValue;
                } else {
                    $select.selectpicker('show');
                    $select.selectpicker('deselectAll');
                }
            },
            options: function() {
                if (this.optionsEmpty()) {
                    this.customInput = true;
                    return;
                }

                let $select = $(this.$el).find('.selectpicker');
                if (this.options.length === 1) {
                    this.customInput = false;
                    $select.selectpicker('val', this.options[0]);
                }
                if (this.customInput && this.inOptions(this.customValue)) {
                    this.customInput = false;
                    $select.selectpicker('val', this.customValue);
                }
                $select.selectpicker('refresh');
                this.$emit('input', this.options[0]);
            }
        },
        methods: {
            optionsEmpty: function() {
                return typeof this.options === 'undefined' || this.options.length === 0;
            },
            inOptions: function(value) {
                if (this.optionsEmpty()) {
                    return false;
                }
                for (let item of this.options) {
                    if (item === value) {
                        return true;
                    }
                }
                return false;
            },
            customValueUpdated: function() {
                this.emitValueChanged(this.customValue);
            },
            emitValueChanged: function(value) {
                this.$parent.$emit("value-changed", {
                    type: this.dataType,
                    value: value,
                });
                this.$emit('input', value);
            }
        }
    }
</script>
