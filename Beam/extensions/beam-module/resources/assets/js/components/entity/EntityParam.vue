<template>
    <tr class="entity-param-row" :class="{ 'deleted-param': isDeleted }" :title="isDeleted ? 'Deleted' : ''">
        <td>
            <input type="text" class="form-control" :name="'params[' + index + '][name]'" v-model="name" :disabled="isDeleted">

            <input v-if="!isDeleted" type="hidden" :name="'params[' + index + '][id]'" :value="id">
        </td>

        <td class="table-td-type">
            <v-select
                id="param_type"
                :name="'params[' + index + '][type]'"
                v-model="type"
                :options.sync="$parent.paramTypes"
                :disabled="isDeleted"
            ></v-select>
        </td>

        <td class="table-td-button">
            <span v-if="$parent.params.length > 1"
                  class="btn btn-sm palette-Red bg waves-effect"
                  :class="{ hidden: isDeleted }"
                  @click="removeParam($event, index)">
                <i class="zmdi zmdi-minus-square"></i>
            </span>
        </td>
    </tr>
</template>

<script>
    import vSelect from "@remp/js-commons/js/components/vSelect";

    export default {
        components: {
            vSelect
        },
        props: {
            param: Object,
            index: Number
        },
        mounted() {
            this.id = this.param.id;
            this.type = this.param.type;
            this.name = this.param.name;
            this.isDeleted = this.param.deleted_at !== null;
        },
        data() {
            return {
                id: null,
                name: null,
                type: null,
                isDeleted: false
            }
        },
        methods: {
            removeParam(event, index) {
                event.preventDefault();

                // remove not existing param
                if (this.id === null) {
                    this.$parent.params.splice(index, 1);
                    return;
                }

                // remove existing (saved) param
                this.$parent.params_to_delete.push(this.id);
                this.isDeleted = true;
            },
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

    .entity-param-row > td {
        min-height: 62px;
    }
</style>
