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
                    <input type="hidden" name="active" value="0" />
                    <input v-model="active" value="1" name="active" type="checkbox">
                    <i class="input-helper"></i>
                </label>
            </div>

            <div class="input-group m-t-20">
                <div class="fg-line">
                    <button class="btn btn-info waves-effect" type="submit" name="action" value="save">
                        <i class="zmdi zmdi-check"></i> Save
                    </button>
                    <button class="btn btn-info waves-effect" type="submit" name="action" value="save_close">
                        <i class="zmdi zmdi-mail-send"></i> Save and close
                    </button>
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
                                        <v-select class="col-md-12 p-l-0 p-r-0"
                                                v-model="rule.event_category"
                                                :name="'rules['+i+'][event_category]'"
                                                :value="rule.event_category"
                                                :options.sync="eventCategories"
                                                :dataType="'category'"
                                                :allowCustomValue="true"
                                                :liveSearch="true"
                                        ></v-select>
                                    </div>
                                </div>
                            </div>

                            <div class="input-group m-t-10">
                                <span class="input-group-addon">
                                    <span class="preloader pl-xs" v-if="showEventsLoader">
                                        <svg class="pl-circular" viewBox="25 25 50 50">
                                            <circle class="plc-path" cx="50" cy="50" r="20" />
                                        </svg>
                                    </span>
                                    <i class="zmdi zmdi-badge-check" v-if="showEventsInput"></i>
                                </span>
                                <div class="row">
                                    <div class="col-md-12">
                                        <label class="fg-label">Event action</label>
                                    </div>
                                    <div class="col-md-12">
                                        <v-select class="col-md-12 p-l-0 p-r-0"
                                                v-model="rule.event_action"
                                                :name="'rules['+i+'][event_action]'"
                                                :value="rule.event_action"
                                                :options.sync="eventActions[rule.event_category]"
                                                :dataType="'event'"
                                                :disabled="!showEventsInput"
                                                :allowCustomValue="true"
                                                :liveSearch="true"
                                        ></v-select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="input-group m-t-20">
                                        <span class="input-group-addon"><i class="zmdi zmdi-swap-vertical-circle"></i></span>
                                        <div class="fg-line">
                                            <label class="fg-label">Operator</label>
                                            <v-select class="col-md-12 p-l-0 p-r-0"
                                                    v-model="rule.operator"
                                                    :name="'rules['+i+'][operator]'"
                                                    :value="rule.operator"
                                                    :options.sync="operators"
                                                    :required="true"
                                            ></v-select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="input-group m-t-20">
                                        <span class="input-group-addon"><i class="zmdi zmdi-n-1-square"></i></span>
                                        <div class="fg-line">
                                            <label class="fg-label">Count</label>
                                            <input v-model="rule.count" :name="'rules['+i+'][count]'" placeholder="e.g. 5" class="form-control fg-input" title="count" type="number" required />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="input-group m-t-10">
                                <span class="input-group-addon"><i class="zmdi zmdi-time-interval"></i></span>
                                <div class="fg-line">
                                    <label class="fg-label">Timespan in minutes</label>
                                    <input v-model="rule.timespan" :name="'rules['+i+'][timespan]'" placeholder="e.g. 1440" class="form-control fg-input" title="timespan" type="number" required>
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
                                <div v-for="(flag,j) in eventFlags[eventGroup(rule.event_category)]" class="row">
                                    <div class="col-md-5">
                                        <input v-model="rule.flags[flagIdx(rule, flag)].key" disabled="disabled" class="form-control fg-input" title="field key" type="text" style="background-color: transparent"/>
                                        <input v-model="rule.flags[flagIdx(rule, flag)].key" :name="'rules['+i+'][flags]['+j+'][key]'" type="hidden" />
                                    </div>
                                    <div class="col-md-5">
                                        <v-select class="col-md-12 p-l-0 p-r-0"
                                                v-model="rule.flags[flagIdx(rule, flag)].value"
                                                :name="'rules['+i+'][flags]['+j+'][value]'"
                                                :value="rule.flags[flagIdx(rule, flag)].value"
                                                :options.sync="flagOptions"
                                                :liveSearch="false"
                                                :dataType="'flag'"
                                                :title="flagOptions[0].label"
                                        ></v-select>
                                    </div>
                                </div>
                                <div v-for="(field,j) in rule.fields" class="row">
                                    <div class="col-md-5">
                                        <input v-model="field.key" :name="'rules['+i+'][fields]['+j+'][key]'" placeholder="e.g. campaign" class="form-control fg-input" title="field key" type="text" />
                                    </div>
                                    <div class="col-md-5">
                                        <input v-model="field.value" :name="'rules['+i+'][fields]['+j+'][value]'" placeholder="e.g. christmas" class="form-control fg-input" title="field value" type="text" />
                                    </div>
                                    <div class="col-md-2 p-0" v-if="j > 0 || field.key || field.value">
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
    let vSelect = require("remp/js/components/vSelect.vue");

    const props = [
        "_name",
        "_code",
        "_active",
        "_rules",
        "_eventCategories",
        "_eventActions",
    ];

    export default {
        name: 'segment-form',
        components: { vSelect },
        props: props,
        created: function() {
            let self = this;
            props.forEach((prop) => {
                this[prop.slice(1)] = this[prop];
            });

            this.fetchFlags();

            // init actions dropdown for existing category selections
            let categories = [];
            for (let i=0; i < this.rules.length; i++) {
                if (this.rules[i].event_category !== null) {
                    categories.push(self.rules[i].event_category);
                }
            }
            for (let group in this.eventCategories) {
                if (!this.eventCategories.hasOwnProperty(group)) {
                    continue;
                }
                for (let category of this.eventCategories[group]) {
                    this.fetchActions(group, category);
                }
            }

            this.$on('vselect-changed', function(data){
                if (data.type === 'category') {
                    if (data.group === undefined) {
                        data.group = 'events'; // default for custom values
                    }
                    this.fetchActions(data.group, data.value);
                    this.fetchFlags(data.group);
                }
            });
        },
        data: () => ({
            "name": null,
            "code": null,
            "active": null,
            "rules": [],
            "removedRules": [],
            "eventCategories": [],
            "eventActions": {},
            "eventFlags": {},
            "showEventsLoader": false,
            "showEventsInput": false,
            "operators": [
                {"value": "<", "sublabel": "<", "label": "At most"},
                {"value": "<=", "sublabel": "<=", "label": "At most or exactly"},
                {"value": "=", "sublabel": "=", "label": "Exactly"},
                {"value": ">", "sublabel": ">", "label": "At least"},
                {"value": ">=", "sublabel": ">=", "label": "At least or exactly"},
            ],
            "flagOptions": [
                {"label": "No filter", "value": ""},
                {"label": "Yes", "value": "1"},
                {"label": "No", "value": "0"},
            ],
        }),
        methods: {
            addRule: function() {
                this.rules.push({
                    id: null,
                    count: null,
                    timespan: null,
                    event_action: null,
                    event_category: null,
                    operator: null,
                    fields: [{
                        key: null,
                        value: null
                    }],
                    flags: [{
                        key: "article",
                        value: null,
                    }],
                });
            },
            addField: function(ruleIndex) {
                this.rules[ruleIndex].fields.push({
                    key: null,
                    value: null
                })
            },
            removeRule: function(index) {
                this.removedRules.push(this.rules[index].id);
                this.rules.splice(index, 1)
            },
            removeField: function(ruleIndex, fieldIndex) {
                let fields = this.rules[ruleIndex].fields;
                fields.splice(fieldIndex, 1);
                if (fields.length === 0) {
                    this.addField(ruleIndex);
                }
            },
            flagIdx: function(rule, flagKey) {
                for (let i=0; i<rule.flags.length; i++) {
                    if (rule.flags[i].key === flagKey) return i;
                }
                rule.flags.push({
                    key: flagKey,
                    value: null,
                });
                return rule.flags.length - 1;
            },
            eventGroup: function(category) {
                for (let group in this.eventCategories) {
                    if (!this.eventCategories.hasOwnProperty(group)) {
                        continue;
                    }
                    if (this.eventCategories[group].indexOf(category) !== -1) {
                        return group;
                    }
                }
            },
            fetchActions: function(group, category) {
                let self = this;
                self.showEventsLoader = true;
                self.showEventsInput = false;
                $.get('/api/journal/' + group + '/categories/' + category + '/actions', ( data ) => {
                    self.eventActions[category] = data;
                    self.showEventsLoader = false;
                    self.showEventsInput = true;
                })
            },
            fetchFlags: function() {
                let self = this;
                $.get('/api/journal/flags', ( data ) => {
                    self.eventFlags = data;
                })
            },
        },
    }
</script>
