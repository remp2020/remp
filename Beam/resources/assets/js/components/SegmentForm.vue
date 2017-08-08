<style type="text/css">
    .input-group-addon {
        vertical-align: sub;
    }
</style>

<template>
    <div class="row">
        <div class="col-md-4">
            <h4>Settings</h4>

            <div class="input-group fg-float m-t-30">
                <span class="input-group-addon"><i class="zmdi zmdi-account"></i></span>
                <div class="fg-line">
                    <label for="name" class="fg-label">Name</label>
                    <input v-model="name" class="form-control fg-input" name="name" id="name" type="text">
                </div>
            </div>

            <div class="input-group fg-float m-t-30">
                <span class="input-group-addon"><i class="zmdi zmdi-account"></i></span>
                <div class="fg-line">
                    <label for="code" class="fg-label">Code</label>
                    <input v-model="code" class="form-control fg-input" name="code" id="code" type="text">
                </div>
            </div>

            <div class="input-group fg-float m-t-30 checkbox">
                <label class="m-l-15">
                    Active
                    <input v-model="active" value="1" name="active" type="checkbox">
                    <i class="input-helper"></i>
                </label>
            </div>

            <div class="input-group m-t-20">
                <div class="fg-line">
                    <button class="btn btn-info waves-effect" type="submit"><i class="zmdi zmdi-mail-send"></i> Save</button>
                </div>
            </div>

        </div>

        <div class="col-md-7 col-md-offset-1">
            <h4>
                Display rules
                <span v-on:click="addRule" class="btn btn-info waves-effect"><i class="zmdi zmdi-plus-square"></i> Add rule</span>
            </h4>

            <input v-model="removedRules" name="removedRules[]" type="hidden" required />

            <div class="row m-t-10">
                <div class="col-md-6" v-for="(rule,i) in rules">
                    <div class="card z-depth-2">
                        <div class="card-body card-padding-sm">
                            <input v-model="rule.id" :name="'rules['+i+'][id]'" type="hidden" required>

                            <div class="input-group m-t-10">
                                <span class="input-group-addon"><i class="zmdi zmdi-badge-check"></i></span>
                                <div class="row">
                                    <div class="col-md-12">
                                        <label class="fg-label">Event category</label>
                                    </div>
                                    <div class="col-md-12">
                                        <v-select v-model="rule.event_category"
                                                  v-bind:name="'rules['+i+'][event_category]'"
                                                  v-bind:value="rule.event_category"
                                                  class="col-md-12 p-l-0 p-r-0"
                                                  v-bind:options="eventCategories"
                                        ></v-select>
                                    </div>
                                </div>
                            </div>

                            <div class="input-group m-t-10">
                                <span class="input-group-addon"><i class="zmdi zmdi-badge-check"></i></span>
                                <div class="row">
                                    <div class="col-md-12">
                                        <label class="fg-label">Event name</label>
                                    </div>
                                    <div class="col-md-12">
                                        <v-select v-model="rule.event_name"
                                                  v-bind:name="'rules['+i+'][event_name]'"
                                                  v-bind:value="rule.event_name"
                                                  class="col-md-12 p-l-0 p-r-0"
                                                  v-bind:options="eventNames[rule.event_category]"
                                        ></v-select>
                                    </div>
                                </div>
                            </div>

                            <div class="input-group m-t-20">
                                <span class="input-group-addon"><i class="zmdi zmdi-refresh"></i></span>
                                <div class="fg-line">
                                    <label class="fg-label">Count</label>
                                    <input v-model="rule.count" :name="'rules['+i+'][count]'" placeholder="e.g. 5" class="form-control fg-input" title="count" type="text" required />
                                </div>
                            </div>

                            <div class="input-group m-t-10">
                                <span class="input-group-addon"><i class="zmdi zmdi-time-interval"></i></span>
                                <div class="fg-line">
                                    <label class="fg-label">Timespan</label>
                                    <input v-model="rule.timespan" :name="'rules['+i+'][timespan]'" placeholder="e.g. 1440 (minutes)" class="form-control fg-input"title="timespan" type="number">
                                </div>
                            </div>

                            <div class="input-group m-t-10">
                                <span class="input-group-addon"><i class="zmdi zmdi-filter-list"></i></span>
                                <div class="row">
                                    <div class="col-md-5">
                                        <label class="fg-label">Field key</label>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="fg-label">Field value</label>
                                    </div>
                                </div>
                                <div v-for="(field,j) in rule.fields" class="row">
                                    <div class="col-md-5">
                                        <input v-model="field.key" :name="'rules['+i+'][fields]['+j+'][key]'" placeholder="e.g. campaign" class="form-control fg-input" title="field key" type="text" />
                                    </div>
                                    <div class="col-md-5">
                                        <input v-model="field.value" :name="'rules['+i+'][fields]['+j+'][value]'" placeholder="e.g. christmas" class="form-control fg-input" title="field value" type="text" />
                                    </div>
                                    <div class="col-md-2 p-0" v-if="j > 0 || (field.key || field.value)">
                                        <span v-on:click="removeField(i,j)" class="btn btn-sm palette-Red bg waves-effect"><i class="zmdi zmdi-minus-square"></i></span>
                                    </div>
                                </div>

                                <div class="m-t-10">
                                    <span v-on:click="addField(i)" class="btn btn-sm btn-info bg waves-effect m-t-10"><i class="zmdi zmdi-plus-square"></i> Add field</span>
                                </div>
                            </div>

                            <div class="text-right m-t-20">
                                <span v-on:click="removeRule(i)" class="btn btn-sm palette-Red bg waves-effect m-t-10"><i class="zmdi zmdi-minus-square"></i> Remove rule</span>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</template>

<script>
    const props = [
        "_name",
        "_code",
        "_active",
        "_rules",
        "_eventCategories",
        "_eventNames",
    ];

    export default {
        name: 'segment-form',
        props: props,
        mounted: function(){
            let self = this;
            props.forEach((prop) => {
                this[prop.slice(1)] = this[prop];
            });
        },
        data: () => ({
            "name": null,
            "code": null,
            "active": null,
            "rules": [],
            "removedRules": [],
            "eventCategories": [],
            "eventNames": null,
        }),
        methods: {
            addRule: function () {
                this.rules.push({
                    id: null,
                    count: null,
                    timespan: null,
                    event: null,
                    category: null,
                    fields: [{
                        key: null,
                        value: null
                    }]
                });
            },
            addField: function (ruleIndex) {
                this.rules[ruleIndex].fields.push({
                    key: null,
                    value: null
                })
            },
            removeRule: function (index) {
                this.removedRules.push(this.rules[index].id);
                this.rules.splice(index, 1)
            },
            removeField: function (ruleIndex, fieldIndex) {
                let fields = this.rules[ruleIndex].fields;
                fields.splice(fieldIndex, 1);
                if (fields.length === 0) {
                    this.addField(ruleIndex);
                }
            }
        },
    }
</script>