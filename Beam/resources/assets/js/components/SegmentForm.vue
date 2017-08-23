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
                                                  v-bind:options.sync="eventCategories"
                                                  v-bind:dataType="'category'"
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
                                        <v-select v-model="rule.event_action"
                                                  v-bind:name="'rules['+i+'][event_action]'"
                                                  v-bind:value="rule.event_action"
                                                  class="col-md-12 p-l-0 p-r-0"
                                                  v-bind:options.sync="eventActions[rule.event_category]"
                                                  v-bind:dataType="'event'"
                                                  v-bind:disabled="!showEventsInput"
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
    import vSelect from "./vSelect.vue";

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
            this.$on('select-changed', function(data){
                if (data.type !== 'category') {
                    return;
                }
                this.fetchActions(data.value);
            });
        },
        mounted: function(){
            let self = this;
            props.forEach((prop) => {
                this[prop.slice(1)] = this[prop];
            });

            let categories = [];
            for (let i=0; i < this.rules.length; i++) {
                if (this.rules[i].event_category !== null) {
                    categories.push(self.rules[i].event_category);
                }
            }
            this.loadActions(categories);
        },
        data: () => ({
            "name": null,
            "code": null,
            "active": null,
            "rules": [],
            "removedRules": [],
            "eventCategories": [],
            "loadingActions": {},
            "eventActions": null,
            "showEventsLoader": false,
            "showEventsInput": false,
        }),
        methods: {
            addRule: function() {
                this.rules.push({
                    id: null,
                    count: null,
                    timespan: null,
                    event_action: null,
                    event_category: null,
                    fields: [{
                        key: null,
                        value: null
                    }]
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
            loadActions: function(categories) {
                let vm = this;
                let ready = [];
                categories.forEach((category) => {
                    vm.fetchActions(category, function(data) {
                        vm.loadingActions[category] = data;
                        ready.push(category);
                        if (ready.length === categories.length) {
                            vm.eventActions = vm.loadingActions;
                            vm.showEventsLoader = false;
                            vm.showEventsInput = true;
                        }
                    })
                });
            },
            fetchActions: function(category, cb) {
                let self = this;
                self.showEventsLoader = true;
                self.showEventsInput = false;
                $.get('/api/journal/' + category + '/actions', ( data ) => {
                    if (typeof cb !== 'undefined') {
                        cb(data);
                    } else {
                        self.eventActions[category] = data;
                        self.showEventsLoader = false;
                        self.showEventsInput = true;
                    }
                })
            }
        },
    }
</script>