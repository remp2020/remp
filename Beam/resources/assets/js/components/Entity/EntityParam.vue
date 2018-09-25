<template>
    <tr>
        <td>
            <input type="text" class="form-control" :name="'schema[' + index + '][name]'" v-model="name">
        </td><!-- name -->

        <td class="table-td-type">
            <v-select
                id="param_type"
                :name="'schema[' + index + '][type]'"
                v-model="type"
                :options.sync="$parent.paramTypes"
            ></v-select>
        </td><!-- type -->

        <td class="table-td-enum-input">
            <vue-tags-input
                v-model="enumOption"
                :tags="enumOptions"
                :placeholder="'Add option'"
                @tags-changed="newTags => enumOptions = newTags"
            />

            <input v-for="val in enumOptions" type="hidden" :name="'schema[' + index + '][enum][]'" :value="val.text">
        </td><!-- enum -->

        <td class="table-td-button">
            <span class="btn btn-sm palette-Red bg waves-effect m-t-10" @click="$parent.removeParam($event, index, name)">
                <i class="zmdi zmdi-minus-square"></i>
            </span>
        </td><!-- remove -->

        <td class="table-td-button">
            <span class="btn btn-sm palette-Green bg waves-effect m-t-10" @click="$parent.addNewParam()" v-if="index == $parent.params.length - 1" >
                <i class="zmdi zmdi-plus-square"></i>
            </span>
        </td><!-- remove -->
    </tr>
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
            param: Object,
            index: Number
        },
        mounted() {
            this.type = this.param.type;
            this.name = this.param.name;
            this.enumOptions = [];

            if (this.param.enum) {
                for (let ii = 0; ii < this.param.enum.length; ii++) {
                    this.enumOptions.push({
                        text: this.param.enum[ii]
                    })
                }
            }
        },
        data() {
            return {
                name: null,
                type: null,
                enumOption: '',
                enumOptions: []
            }
        }
    }
</script>

<style scoped>
    .form-control {
        background: none;
    }

    .bootstrap-select > .btn-default:before {
        background-color: rgba(255, 255, 255, 0);
    }

    .input-group {
        width: 100%;
    }

    .table-td-enum-input >>> .vue-tags-input {
        background: none;
    }

    .table-td-enum-input >>> .new-tag-input {
        font-size: 13px;
        background: none;
    }

    .table-td-enum-input >>> .tag {
        background-color: #00bcd4;
    }
    
    .table-td-enum-input >>> .tag > .content {
        font-size: 13px;
    }

    .table-td-enum-input >>> .input {
        border: none;
        border-bottom: 1px solid #e0e0e0 !important;
        padding: 0;
        background: none;
    }

    .table-td-enum-input >>> .new-tag-input-wrapper {
        margin-left: 0;
        margin-right: 0;
        padding-right: 0;
    }

    .table-td-enum-input >>> .new-tag-input-wrapper > input::placeholder {
        color: #999;
    }
</style>
