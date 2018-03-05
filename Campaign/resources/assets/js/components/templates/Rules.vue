<template>
    <div class="row">
        <div class="col-md-12">
            <label class="fg-label">Pageview rules</label>
        </div>
        <div class="col-md-10">
            <div class="row" v-for="(pageviewRule, i) in pageviewRules">
                <div class="col-md-4">
                    <v-select v-model="pageviewRule.rule"
                            id="rule"
                            :name="'pageview_rules[' + i + '][rule]'"
                            :value="pageviewRule.rule"
                            :options.sync="pageviewRulesOptions"
                            :title="'Rule'"
                    ></v-select>
                </div>
                <div class="col-md-2">
                    <input v-model="pageviewRule.num" placeholder="n-th" class="form-control fg-input" :name="'pageview_rules[' + i + '][num]'" id="num" type="text">
                </div>
                <div class="col-md-4">
                    <span style="line-height: 31px;">request</span>
                </div>

                <div class="col-md-2">
                    <span class="btn btn-sm palette-Red bg waves-effect" v-on:click="removeRule(i)"><i class="zmdi zmdi-minus-square"></i></span>
                </div>

            </div>
        </div>


        <div class="col-md-2">
            <span class="btn btn-sm palette-Green bg waves-effect" v-on:click.prevent="addRule"><i class="zmdi zmdi-plus-circle"></i></span>
        </div>
    </div>
</template>

<script type="text/javascript">
    import vSelect from "remp/js/components/vSelect.vue";

    export default {
        components: {
            vSelect
        },
        props: [
            "pageviewRules"
        ],
        data() {
            return {
                pageviewRulesOptions: [
                    {"label": "Every", "value": "every"},
                    {"label": "Since", "value": "since"},
                    {"label": "Before", "value": "before"}
                ]
            };
        },
        methods: {
            addRule() {
                this.$parent.pageviewRules.push({
                    num: null,
                    rule: null
                })
            },
            removeRule(i) {
                this.$parent.pageviewRules.splice(i, 1);
            }
        }
    }
</script>
