<template>
    <div>
        <div class="row">
            <div :class="allowCustomValue ? 'col-xs-10' : 'col-xs-12'">
                <select v-if="typeof options === 'object' && options.length === undefined" :title="title || 'Please select'" :name="name" :data-type="dataType" :multiple="multiple" class="selectpicker" :data-live-search="liveSearch" :disabled="disabled" :required="required">
                    <optgroup v-for="(group, label) in options" :label="label">
                        <option v-for="option in group" :data-subtext="option.sublabel || ''" :value="textValue(option)" >
                            {{ textLabel(option) }}
                        </option>
                    </optgroup>
                </select>
                <select v-else :title="title || 'Please select'" :name="name" :data-type="dataType" :multiple="multiple" class="selectpicker" :data-live-search="liveSearch" :disabled="disabled" :required="required">
                    <option v-for="option in options" :data-subtext="option.sublabel || ''" :value="textValue(option)" >
                        {{ textLabel(option) }}
                    </option>
                </select>
                <input v-if="allowCustomValue" v-on:blur="customValueUpdated" v-show="customInput" v-model="customValue" :disabled="!this.customInput" :name="name" placeholder="e.g. my-event" title="Custom value" type="text" :required="required" class="form-control fg-input">
            </div>
            <div v-if="allowCustomValue" class="col-xs-2">
                <button type="button" :disabled="this.optionsEmpty()" v-on:click="customInput = !customInput" :class="[{'palette-Blue-Grey bg': customInput}, {'btn-default': !customInput}, 'btn', 'waves-effect']">
                    <i class="zmdi zmdi-hc-lg zmdi-edit"></i>
                </button>
            </div>
        </div>
    </div>
</template>

<script>
    let props = {
        'name': String,
        'options': [Array, Object],
        'value': [Array, Boolean, Number, String],
        'multiple': Boolean,
        'title': String,
        'liveSearch': {
            'type': Boolean,
            'default': true,
        },
        'dataType': String,
        'disabled': Boolean,
        'allowCustomValue': {
            'type': Boolean,
            'default': false,
        },
        'required': {
            'type': Boolean,
            'default': false,
        }
    };

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
                // noinspection JSPotentiallyInvalidUsageOfThis
                let group = this.options[this.selectedIndex].parentNode.label;
                vm.emitValueChanged(val, group);
            });

            // init default value
            if (this.value !== null) {
                $select.selectpicker('val', String(this.value));
                if (this.allowCustomValue) {
                    this.customValue = this.value;
                    if (!this.inOptions(this.value)) {
                        this.customInput = true;
                    }
                }
            }
            if (this.allowCustomValue && this.options instanceof Array && this.options.length === 0) {
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
                let val = null;
                if (this.customInput) {
                    if (this.inOptions(this.customValue)) {
                        this.customInput = false;
                    }
                    val = this.customValue;
                } else if (this.inOptions(this.value)) {
                    val = this.value;
                } else {
                    val = this.options[0];
                }

                $select.selectpicker('val', val);
                this.$emit('input', val);
                $select.selectpicker('refresh');
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
                if (this.isGroup(this.options)) {
                    for (let idx in this.options) {
                        if (!this.options.hasOwnProperty(idx)) {
                            continue;
                        }
                        for (let item of this.options[idx]) if (item === value) return true;
                    }
                } else {
                    for (let item of this.options) if (item === value) return true;
                }
                return false;
            },
            isGroup: function(options) {
                return typeof options === 'object' && options.length === undefined;
            },
            customValueUpdated: function() {
                this.emitValueChanged(this.customValue);
            },
            emitValueChanged: function(value, group) {
                this.$parent.$emit("vselect-changed", {
                    type: this.dataType,
                    group: group,
                    value: value,
                });
                this.$emit('input', value);
            },
            textValue: function(option) {
                if (option.value !== undefined) {
                    return option.value;
                }
                return option;
            },
            textLabel: function(option) {
                if (option.label !== undefined) {
                    return option.label;
                }
                return this.textValue(option);
            }
        }
    }
</script>
