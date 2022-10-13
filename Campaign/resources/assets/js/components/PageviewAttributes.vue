<template>
    <div class="m-l-20">
        <p>
            Campaign will only be displayed if pageview matches <strong>all</strong> of the attributes below.
            See <a href="https://github.com/remp2020/remp/blob/master/Campaign/README.md#javascript-snippet" target="_blank">Javascript snippet of README</a> on how to amend your CMS Javascript integration to make this work.
        </p>
        <input type="hidden" name="pageview_attributes">
        <table class="table " v-if="pageviewAttrs.length">
            <tbody>
            <tr v-for="(attribute, index) in pageviewAttrs" :key="index">
                <td class="name-input-td">
                    <input type="text"
                           :name="'pageview_attributes[' + index + '][name]'"
                           class="form-control"
                           :value="attribute.name"
                           placeholder="Attribute name"
                           data-attr-item="name"
                           @change="(e) => updatePageviewAttribute(e, attribute)">
                </td>
                <td class="operator-select-td">
                    <input type="hidden"
                           :name="'pageview_attributes[' + index + '][operator]'"
                           :value="attribute.operator">
                    <select :name="'pageview_attributes[' + index + '][operator]'"
                            class="form-control text-center selectpicker"
                            data-attr-item="operator"
                            :value="attribute.operator"
                            @change="(e) => updatePageviewAttribute(e, attribute)">
                        <option class="text-center" value="=" selected>is</option>
                        <option class="text-center" value="!=">is not</option>
                    </select>
                </td>
                <td class="value-input-td">
                    <input type="text"
                           :name="'pageview_attributes[' + index + '][value]'"
                           class="form-control"
                           :value="attribute.value"
                           placeholder="Attribute value"
                           data-attr-item="value"
                           @change="(e) => updatePageviewAttribute(e, attribute)">
                </td>
                <td>
                    <button class="btn dark palette-Red-300 bg waves-effect pull-right btn-remove" @click="removePageviewAttribute($event, index)">
                        <i class="zmdi zmdi-minus-circle"></i>
                    </button>
                </td>
            </tr>
            </tbody>
        </table>

        <button class="btn dark palette-White bg waves-effect m-t-10" @click="addEmptyPageviewAttribute($event)">
            <i class="zmdi zmdi-plus-circle palette-Green-300 text"></i> Add attribute
        </button>
    </div>
</template>

<style scoped>
    .operator-select-td {
        min-width: 75px;
    }
</style>

<script type="text/javascript">
    export default {
        props: {
            pageviewAttributes: {
                default: function () {
                    return [
                        {
                            name: "",
                            operator: "=",
                            val: "",
                        }
                    ]
                }
            }
        },
        data() {
            return {
                pageviewAttrs: []
            };
        },
        created: function () {
            this.pageviewAttrs = this.pageviewAttributes;
        },
        watch: {
            pageviewAttrs: function () {
                this.updatePageviewAttributes();
            }
        },
        methods: {
            addEmptyPageviewAttribute: function (event) {
                this.pageviewAttrs.splice(this.pageviewAttrs.length, 0, {
                    name: "",
                    operator: "=",
                    value: ""
                });

                setTimeout(function () {
                    $('.selectpicker').selectpicker();
                }, 1);

                if (event) {
                    event.preventDefault();
                }
            },
            removePageviewAttribute: function (event, index) {
                this.pageviewAttrs.splice(index, 1);

                if (event) {
                    event.preventDefault();
                }
            },
            updatePageviewAttribute: function(e, item) {
                item[e.currentTarget.getAttribute('data-attr-item')] = e.target.value;
            },
            updatePageviewAttributes: function () {
                this.$emit('pageviewAttributesModified', this.pageviewAttrs);
            }
        }
    }
</script>
