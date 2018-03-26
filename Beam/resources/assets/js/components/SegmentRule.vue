<template>
    <div class="col-md-6">
        <div class="card z-depth-2">
            <div class="card-body card-padding-sm">
                <input :value="id" :name="'rules['+index+'][id]'" type="hidden" required>

                <div class="input-group m-t-10">
                    <span class="input-group-addon"><i class="zmdi zmdi-badge-check"></i></span>
                    <div class="row">
                        <div class="col-md-12">
                            <label class="fg-label">Event category</label>
                        </div>
                        <div class="col-md-12">
                            <v-select class="col-md-12 p-l-0 p-r-0"
                                    v-model="mutEventCategory"
                                    :name="'rules['+index+'][event_category]'"
                                    :value="mutEventCategory"
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
                                    v-model="mutEventAction"
                                    :name="'rules['+index+'][event_action]'"
                                    :value="mutEventAction"
                                    :options.sync="eventActions[mutEventCategory]"
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
                                        :name="'rules['+index+'][operator]'"
                                        :value="operator"
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
                                <input :value="count" :name="'rules['+index+'][count]'" placeholder="e.g. 5" class="form-control fg-input" title="count" type="number" required />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="input-group m-t-10">
                    <span class="input-group-addon"><i class="zmdi zmdi-time-interval"></i></span>
                    <div class="fg-line">
                        <label class="fg-label">Timespan</label>
                        <input type="hidden" :name="'rules['+index+'][timespan]'" v-model="mutTimespan">
                        <input v-model="timespanUserFormatted" placeholder="e.g. 3d 1h 4m" class="form-control fg-input" title="timespan" type="text" required>
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
                    <div v-for="(flag,j) in eventFlags[eventGroup(mutEventCategory)]" class="row">
                        <div class="col-md-5">
                            <input v-model="mutFlags[flagIdx(flag)].key" disabled="disabled" class="form-control fg-input" title="field key" type="text" style="background-color: transparent"/>
                            <input v-model="mutFlags[flagIdx(flag)].key" :name="'rules['+index+'][flags]['+j+'][key]'" type="hidden" />
                        </div>
                        <div class="col-md-5">
                            <v-select class="col-md-12 p-l-0 p-r-0"
                                    v-model="mutFlags[flagIdx(flag)].value"
                                    :name="'rules['+index+'][flags]['+j+'][value]'"
                                    :value="mutFlags[flagIdx(flag)].value"
                                    :options.sync="flagOptions"
                                    :liveSearch="false"
                                    :dataType="'flag'"
                                    :title="flagOptions[0].label"
                            ></v-select>
                        </div>
                    </div>
                    <div v-for="(field,j) in mutFields" class="row">
                        <div class="col-md-5">
                            <input v-model="field.key" :name="'rules['+index+'][fields]['+j+'][key]'" placeholder="e.g. campaign" class="form-control fg-input" title="field key" type="text" />
                        </div>
                        <div class="col-md-5">
                            <input v-model="field.value" :name="'rules['+index+'][fields]['+j+'][value]'" placeholder="e.g. christmas" class="form-control fg-input" title="field value" type="text" />
                        </div>
                        <div class="col-md-2 p-0" v-if="j > 0 || field.key || field.value">
                            <span v-on:click="removeField(index,j)" class="btn btn-sm palette-Red bg waves-effect"><i class="zmdi zmdi-minus-square"></i></span>
                        </div>
                    </div>

                    <div class="m-t-10">
                        <span v-on:click="addField(index)" class="btn btn-sm btn-info bg waves-effect m-t-10"><i class="zmdi zmdi-plus-square"></i> Add field</span>
                    </div>
                </div>

                <div class="text-right m-t-20">
                    <span v-on:click="removeRule(index)" class="btn btn-sm palette-Red bg waves-effect m-t-10"><i class="zmdi zmdi-minus-square"></i> Remove rule</span>
                </div>

            </div>
        </div>
    </div>
</template>

<script>
    var vSelect = require("remp/js/components/vSelect.vue");

    export default {
        name: "segment-rule",
        components: { vSelect },
        props: [
            "id",
            "count",
            "timespan",
            "eventAction",
            "eventCategories",
            "eventCategory",
            "operator",
            "fields",
            "flags",
            "index"
        ],
        data: function () {
            return {
                "mutFlags": this.flags,
                "mutFields": this.fields,
                "mutTimespan": this.timespan,
                "mutEventAction": this.eventAction, 
                "mutEventCategory": this.eventCategory,
                "timespanUserFormatted": null,

                "showEventsLoader": false,
                "showEventsInput": false,
                "eventActions": {},
                "eventFlags": {},

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
                ]
            }
        },
        watch: {
            timespanUserFormatted: function (val) {
                var groups = /(?:(\d+)d)?\s*(?:(\d+)h)?\s*(?:(\d+)m)?/.exec(val);

                var days = groups[1];
                var hours = groups[2];
                var minutes = groups[3];

                var timespan = moment.duration({
                    days: days,
                    hours: hours,
                    minutes: minutes
                })

                this.mutTimespan = parseInt(timespan.asMinutes());
            }
        },
        created: function () {
            this.fetchFlags();
            this.initTimespan();

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
        methods: {
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
            flagIdx: function(flagKey) {
                for (let i=0; i<this.mutFlags.length; i++) {
                    if (this.mutFlags[i].key === flagKey) return i;
                }
                this.mutFlags.push({
                    key: flagKey,
                    value: null,
                });
                return this.mutFlags.length - 1;
            },
            removeRule: function(index) {
                this.$parent.removedRules.push(this.id);
                this.$parent.rules.splice(index, 1)
            },
            addField: function() {
                this.fields.push({
                    key: null,
                    value: null
                })
            },
            removeField: function(fieldIndex) {
                this.fields.splice(fieldIndex, 1);
                if (this.fields.length === 0) {
                    this.addField();
                }
            },
            initTimespan: function () {
                var timespan = moment.duration(this.mutTimespan, "minutes"),
                    timespanStr = "",
                    additionalDays = 0;


                if (timespan.years()) {
                    additionalDays += timespan.years()*365;
                }

                if (timespan.months()) {
                    additionalDays += timespan.months()*31;
                }

                if (timespan.days() || additionalDays) {
                    timespanStr += (additionalDays + timespan.days()) + "d ";
                }

                if (timespan.hours()) {
                    timespanStr += " " + timespan.hours() + "h ";
                }

                if (timespan.minutes()) {
                    timespanStr += " " + timespan.minutes() + "m ";
                }

                this.timespanUserFormatted = timespanStr.trim();
            }
        }
    }
</script>


