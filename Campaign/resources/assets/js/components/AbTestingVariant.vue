<template>
    <tr>
        <!-- variant color box -->
        <td class="table-td-color" :class="['color-' + index]"><div></div></td>

        <td class="table-td-actions">
          <a v-if="index != $parent.variants.length - 1 && bannerUrl" :href="bannerUrl" class="btn-xs palette-Grey-200 bg waves-effect">
            <i class="zmdi zmdi-palette-Cyan zmdi-eye"></i> Show
          </a>
        </td>

        <!-- variant select -->
        <td class="table-td-banner">
            <input type="hidden" :name="'variants[' + index + '][banner_id]'" :value="variant.banner_id">

            <v-select id="variant_id"
                :name="'variants[' + index + '][banner_id]'"
                :value="variant.banner_id"
                :title="'No alternative'"
                :options.sync="$parent.variantOptions"
                :dataType="index.toString()"
                v-if="index != $parent.variants.length - 1 && index != 0"
            ></v-select>

            <div v-if="index == $parent.variants.length -1">Control Group</div>

            <div v-if="index == 0" title="This banner can be changed only in previous step.">
              {{ $parent.getVariantOptionByValue($parent.bannerId).label }}
            </div>
        </td>

        <!-- proportion value -->
        <td style="text-align: right; min-width: 90px;">
            <input type="number" min="0" max="100" class="ab-testing-input form-control" :class="['ab-testing-input-' + index]" :name="'variants[' + index + '][proportion]'" :value="variant.proportion" @change="$parent.handleInputUpdate($event, index)" :id="'ab-testing-input-' + index">&nbsp;&nbsp;%
        </td>

        <!-- remove variant button -->
        <td class="table-td-button">
            <button v-if="variant.control_group != 1  && index != 0" @click="$parent.removeVariant($event, index, variant.id)" class="btn btn-danger">
                <i class="zmdi zmdi-minus-circle"></i>
            </button>
        </td>

        <!-- add variant button -->
        <td class="table-td-button">
            <input type="hidden" :name="'variants[' + index + '][id]'" :value="variant.id">
            <input type="hidden" :name="'variants[' + index + '][control_group]'" :value="variant.control_group">
            <input type="hidden" :name="'variants[' + index + '][weight]'" :value="index + 1">

            <button v-if="index == $parent.variants.length - 2" class="btn btn-success pull-right" @click="$parent.addEmptyVariant($event)">
                <i class="zmdi zmdi-plus-circle"></i>
            </button>
        </td>

    </tr>
</template>

<script type="text/javascript">
    import vSelect from "@remp/js-commons/js/components/vSelect";

    export default {
        components: {
            vSelect,
        },
        props: [
            "variant",
            "index",
            "banner_id",
        ],
        data() {
            return {
                selectedBannerId: null,
            };
        },
        created: function() {
            this.selectedBannerId = this.variant.banner_id;
            this.$on('vselect-changed', function(data){
              if (data.type !== this.index.toString()) {
                return;
              }
              this.selectedBannerId = parseInt(data.value);
            });
        },
        mounted: function () {
            this.renderProportionInputValues();
        },
        computed: {
            bannerUrl: function() {
              if (!this.selectedBannerId) {
                  return;
              }
              return "/banners/" + this.selectedBannerId;
            },
        },
        methods: {
            renderProportionInputValues: function () {
                let parentVariants = this.$parent.variants;

                for(let ii = 0; ii < parentVariants.length; ii++) {
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

