<template>
    <div class="col-md-6">
        <div class="card z-depth-2">
            <div class="card-body card-padding-sm">
                <div class="input-group">
                    <div class="row">
                        <div class="col-md-12">
                            <label class="fg-label">Name *</label>
                        </div>
                        <div class="col-md-12">
                            <input type="text" class="form-control" :name="'properties[' + index + '][name]'" v-model="name">
                        </div>
                    </div>
                </div>

                <div class="input-group m-t-10">
                    <div class="row">
                        <div class="col-md-12">
                            <label class="fg-label">Type *</label>
                        </div>
                        <div class="col-md-12">
                            <v-select
                                id="property_type"
                                :name="'properties[' + index + '][type]'"
                                v-model="type"
                                :options.sync="$parent.property_types"
                            ></v-select>
                        </div>
                    </div>
                </div>

                <div class="input-group m-t-10" v-if="$parent.format_map[type]">
                    <div class="row">
                        <div class="col-md-12">
                            <label class="fg-label">Format</label>
                        </div>
                        <div class="col-md-12">
                            <v-select
                                id="property_format"
                                class="cosijak"
                                :name="'properties[' + index + '][format]'"
                                v-model="format"
                                :options.sync="$parent.format_map[type]"
                            ></v-select>
                        </div>
                    </div>
                </div>

                <div class="input-group m-t-10">
                    <div class="row">
                        <div class="col-md-12">
                            <label class="fg-label">Enum</label>
                        </div>
                        <div class="col-md-12">
                            <vue-tags-input
                                v-model="enumOption"
                                :tags="enumOptions"
                                @tags-changed="newTags => enumOptions = newTags"
                            />

                            <input v-for="val in enumOptions" type="hidden" :name="'properties[' + index + '][enum][]'" :value="val.text">
                        </div>
                    </div>
                </div>

                <div class="input-group" v-if="type === 'string'">
                    <div class="row">
                        <div class="col-md-12">
                            <label class="fg-label">Pattern (regular expression)</label>
                        </div>
                        <div class="col-md-12">
                            <input type="text" class="form-control" :name="'properties[' + index + '][pattern]'" :value="prop.pattern">
                        </div>
                    </div>
                </div>

                <div class="input-group m-t-10">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="checkbox m-b-15">
                                <label>
                                    <input type="checkbox" :value="prop.name" name="required_properties[]" :checked="$parent.requiredProperties.indexOf(prop.name) !== -1">
                                    <i class="input-helper"></i>
                                    Required
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-right m-t-20">
                    <span class="btn btn-sm palette-Red bg waves-effect m-t-10" @click="$parent.removeProperty(index)">
                        <i class="zmdi zmdi-minus-square"></i> Remove
                    </span>
                </div>

            </div>
        </div>
    </div>
</template>

<script>
    import vSelect from "remp/js/components/vSelect";
    import VueTagsInput from '@johmun/vue-tags-input';

    export default {
        components: {
            vSelect,
            VueTagsInput
        },
        props: {
            prop: Object,
            index: Number
        },
        mounted() {
            this.type = this.prop.type;
            this.format = this.prop.format;
            this.name = this.prop.name;
            this.enumOptions = [];

            for (let ii = 0; ii < this.prop.enum.length; ii++) {
                this.enumOptions.push({
                    text: this.prop.enum[ii]
                })
            } 
        },
        data() {
            return {
                name: null,
                type: null,
                format: null,
                enumOption: '',
                enumOptions: []
            }
        }
    }
</script>

<style scoped>
    .input-group {
        width: 100%;
    }
</style>
