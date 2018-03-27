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
                <segment-rule v-for="(rule, index) in rules"
                    :index="index"
                    :id="rule.id"
                    :key="rule.id"
                    :count="rule.count"
                    :timespan="rule.timespan"
                    :eventCategories="eventCategories"
                    :eventCategory="rule.event_category"
                    :eventAction="rule.event_action"
                    :operator="rule.operator"
                    :fields="rule.fields"
                    :flags="rule.flags"   
                >
                </segment-rule>
            </div>

        </div>
    </div>
</template>

<script>
    var SegmentRule = require("./SegmentRule");

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
        components: { SegmentRule },
        props: props,
        created: function() {
            let self = this;
            props.forEach((prop) => {
                this[prop.slice(1)] = this[prop];
            });

            // init actions dropdown for existing category selections
            let categories = [];
            for (let i=0; i < this.rules.length; i++) {
                if (this.rules[i].event_category !== null) {
                    categories.push(self.rules[i].event_category);
                }
            }

            this.$on('removeRule', function(data) {
                this.removedRules.push(data.id);
                this.rules.splice(data.index, 1)
            });
        },
        data: () => ({
            "name": null,
            "code": null,
            "active": null,
            "rules": [],
            "removedRules": []
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
            }
        }
    }
</script>
