<template>
    <div class="row">

        <div class="col-md-4">
            <h4>Settings</h4>

            <div class="input-group fg-float m-t-30">
                <span class="input-group-addon"><i class="zmdi zmdi-account"></i></span>
                <div class="fg-line">
                    <label for="name" class="fg-label">Name</label>
                    <input v-model="name" class="form-control fg-input" name="name" id="name" type="text">
                    <input type="hidden" v-model="id" name="id">
                </div>
            </div>

            <div class="input-group fg-float m-t-30">
                <span class="input-group-addon"><i class="zmdi zmdi-account"></i></span>
                <div class="fg-line">
                    <label for="parent_id" class="control-label">Parent entity</label>
                    <v-select
                        id="parent_id"
                        :name="'parent_id'"
                        :value="parent_id"
                        :options.sync="references"
                    ></v-select>
                </div>
            </div>

            <div class="input-group m-t-20">
                <div class="fg-line">
                    <input type="hidden" name="action" :value="submitAction">

                    <button class="btn btn-info waves-effect" type="submit" name="action" value="save" @click="submitAction = 'save'">
                        <i class="zmdi zmdi-check"></i> Save
                    </button>
                    <button class="btn btn-info waves-effect" type="submit" name="action" value="save_close" @click="submitAction = 'save_close'">
                        <i class="zmdi zmdi-mail-send"></i> Save and close
                    </button>
                </div>
            </div>
        </div>

        <div class="col-md-7 col-md-offset-1">
            <h4 class="m-b-20">
                Entity properties
                <span class="btn btn-info waves-effect" @click="addNewProperty"><i class="zmdi zmdi-plus-square"></i> Add property</span>
            </h4>

            <div class="row m-t-10" v-if="properties">
                <entity-property
                    v-for="(prop, index) in this.properties"
                    :key="index"
                    :prop="prop"
                    :index="index"
                ></entity-property>
            </div>

        </div>

        <form-validator v-if="validateUrl" :url="validateUrl"></form-validator>
    </div>
</template>

<script>
    import vSelect from "remp/js/components/vSelect";
    import EntityProperty from "./EntityProperty";

    let props = [
        "_id",
        "_parent_id",
        "_name",
        "_schema",
        "_entities",
        "_validateUrl"
    ];

    export default {
        components: {
            vSelect,
            EntityProperty,
            FormValidator
        },
        props: props,
        mounted() {
            props.forEach((prop) => {
                this[prop.slice(1)] = this[prop];
            });

            for (let prop in this.schema.properties) {
                if (this.schema.properties.hasOwnProperty(prop)) {
                    this.schema.properties[prop].name = prop;
                    this.properties.push(this.schema.properties[prop]);
                }
            }
            // console.log(this.schema)
            this.requiredProperties = this.schema.required;
        },
        data() {
            return {
                "id": null,
                "parent_id": null,
                "name": null,
                "schema": null,
                "properties": [],
                "requiredProperties": [],
                "entities": null,
                "validateUrl": null,
                "submitAction": null,
                "property_types": [
                    {
                        "label": "String",
                        "value": "string"
                    },
                    {
                        "label": "Number",
                        "value": "number"
                    },
                    {
                        "label": "Integer",
                        "value": "integer"
                    },
                    {
                        "label": "Boolean",
                        "value": "boolean"
                    }
                ],
                "format_map": {
                    "string": [
                        {
                            "label": "DateTime (RFC3339)",
                            "value": "date-time"
                        },
                        {
                            "label": "E-mail",
                            "value": "date-time"
                        },
                        {
                            "label": "Hostname",
                            "value": "hostname"
                        },
                        {
                            "label": "URI",
                            "value": "uri"
                        }
                    ],

                }
            };
        },
        computed: {
            references() {
                let options = [];

                if (this.entities) {
                    for (let ii = 0; ii < this.entities.length; ii++) {
                        options.push({
                            "label": this.entities[ii].name,
                            "value": this.entities[ii].id
                        })
                    }
                }

                return options;
            }
        },
        methods: {
            removeProperty(index) {
                this.properties.splice(index, 1);
            },
            addNewProperty() {
                this.properties.push({
                    name: null,
                    type: null
                });
            }
        }
    }
</script>
