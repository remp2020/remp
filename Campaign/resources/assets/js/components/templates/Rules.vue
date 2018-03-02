<style>
	
</style>

<template>
    <div class="row">
        <div class="col-md-12">
            <label class="fg-label">Additional rules</label>
        </div>
        <div class="col-md-10">
	        <div class="row" v-for="(additionalRule, i) in additionalRules">
		        <div class="col-md-4">
		            <v-select v-model="additionalRule.rule"
		                    id="rule"
		                    :name="'additional_rules[' + i + '][rule]'"
		                    :value="additionalRule.rule"
		                    :options.sync="additionalRulesOptions"
		                    :title="'Rule'"
		            ></v-select>
		            <!-- <small class="help-block">To use this filter, you have to be setting <code>signedIn: Boolean</code> within your REMP tracking code.</small> -->
		        </div>
		        <div class="col-md-2">
		            <input v-model="additionalRule.num" placeholder="n-th" class="form-control fg-input" :name="'additional_rules[' + i + '][num]'" id="num" type="text">
		        </div>
		        <div class="col-md-4">
		            <span style="line-height: 31px;">request</span>
		        </div>
	        </div>
        </div>


        <div class="col-md-2">
        	<a href="" v-on:click.prevent="addRule"><i class="zmdi zmdi-plus-circle"></i></a>
        </div>
    </div>
</template>

<script type="text/javascript">
	import vSelect from "remp/js/components/vSelect.vue";

	let props = [
		"additionalRules"
	];

    export default {
        components: {
            vSelect
        },
        props: props,
        data: function() {
        	return {};
        },
        methods: {
        	addRule: function () {
    			this.$parent.additionalRules.push({
    				num: null,
    				rule: null
    			})
        	}
        },
        computed: {
            "additionalRulesOptions": function() {
                return [
                    {"label": "Every", "value": "every"},
                    {"label": "Since", "value": "since"},
                    {"label": "Before", "value": "before"},
                    {"label": "Till", "value": "till"}
                ];
            }
        }
    }
</script>
