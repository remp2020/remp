<template>
    <tr>

        <!-- variant color box -->
        <td class="table-td-color" :class="['color-' + index]"><div></div></td>

        <!-- variant name -->
        <td class="table-td-name">
            <input type="hidden" :name="'variants[' + index + '][name]'" v-model="variant.name">
            <input class="form-control" type="text" :name="'variants[' + index + '][name]'" v-model="variant.name" :disabled="variant.control_group == 1">
        </td>

        <!-- variant select -->
        <td class="table-td-banner">
            <input type="hidden" :name="'variants[' + index + '][banner_id]'" :value="variant.banner_id">

            <v-select id="variant_id"
                    :name="'variants[' + index + '][banner_id]'"
                    :value="variant.banner_id"
                    :title="'No alternative'"
                    :options.sync="$parent.variantOptions"
                    v-if="index != $parent.variants.length - 1 && index != 0"
            ></v-select>

            <span v-if="index == 0" title="This banner can be changed only in previous step.">{{ $parent.variantOptions[$parent.variants[index].banner_id].label }}</span>
        </td>

        <!-- proportion value -->
        <td style="text-align: right;">
            <input type="number" class="ab-testing-input form-control" :class="['ab-testing-input-' + index]" :name="'variants[' + index + '][proportion]'" :value="variant.val" @change="$parent.handleInputUpdate($event, index)" :id="'ab-testing-input-' + index">&nbsp;&nbsp;%
        </td>

        <!-- remove variant button -->
        <td class="table-td-button">
            <button v-if="variant.control_group != 1" @click="$parent.removeVariant($event, index, variant.id)" class="btn btn-danger">
                <i class="zmdi zmdi-minus-circle"></i>
            </button>
        </td>

        <!-- add variant button -->
        <td class="table-td-button">
            <input type="hidden" :name="'variants[' + index + '][id]'" :value="variant.id">
            <input type="hidden" :name="'variants[' + index + '][control_group]'" :value="variant.control_group">
            <input type="hidden" :name="'variants[' + index + '][weight]'" :value="index + 1">

            <button v-if="index == $parent.variants.length - 2" class="btn btn-success pull-right" @click="$parent.addEmptyVariant($event, index)">
                <i class="zmdi zmdi-plus-circle"></i>
            </button>
        </td>

    </tr>
</template>

<script type="text/javascript">
    import vSelect from "remp/js/components/vSelect";

    export default {
        components: {
            vSelect,
        },
        props: [
            "variant",
            "index",
            "banner_id",
        ],
        mounted: function () {
            this.renderProportionInputValues();
        },
        methods: {
            renderProportionInputValues: function () {
                var parentVariants = this.$parent.variants;

                for(var ii = 0; ii < parentVariants.length; ii++) {
                    $('#ab-testing-input-' + ii).val(parentVariants[ii].proportion)
                }
            },
        },
        watch: {
            variant: {
                handler: function () {
                    setTimeout(() => {
                        this.renderProportionInputValues();
                    }, 1);
                },
                deep: true,
            },
        }
    }
</script>

