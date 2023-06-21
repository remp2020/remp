<template>
    <div class="container">
        <div class="row">
            <div class="col-md-12 col-lg-8">

                <div class="panel-group z-depth-1-top">
                    <div class="panel">
                        <div class="card-header">
                            <h2 class="m-t-0">
                                <div v-if="action == 'edit'">
                                    Edit entity
                                </div>
                                <div v-else>
                                    Create entity
                                </div>

                                <small v-if="name">{{ name }}</small>
                            </h2>
                        </div>
                    </div>
                </div>


                <div class="panel-group z-depth-1-top" id="accordion" role="tablist" aria-multiselectable="false">

                    <div class="panel panel-default">
                        <div class="panel-heading" role="tab" id="headingOne">
                            <h4 class="panel-title">
                                <a role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                    Entity name & settings
                                </a>
                            </h4>
                        </div>

                        <div id="collapseOne" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="headingOne">
                            <div class="panel-body p-b-30 p-l-10 p-r-20">

                                <div class="row">
                                    <div class="col-md-9">

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

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="panel panel-default">
                        <div class="panel-heading" role="tab" id="headingTwo">
                            <h4 class="panel-title">
                                <a role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                    Params
                                </a>
                            </h4>
                        </div>

                        <div id="collapseTwo" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingTwo">
                            <div class="panel-body p-b-30 p-l-10 p-r-20">

                                <table class="table table-striped" v-if="params">
                                    <thead>
                                        <tr>
                                            <th>
                                                Parameter name
                                            </th>
                                            <th>
                                                Type
                                            </th>
                                            <th>&nbsp;</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <entity-param
                                            v-for="(param, index) in params"
                                            :index="index"
                                            :param="param"
                                            :key="param.uid"
                                        ></entity-param>
                                    </tbody>
                                </table>
                                <span class="btn btn-sm palette-Green bg waves-effect pull-right m-t-20" @click="addNewParam()">
                                    <i class="zmdi zmdi-plus-square"></i>&nbsp;
                                    Add Param
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
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
        </div>

        <input v-for="id in params_to_delete" type="hidden" name="params_to_delete[]" :value="id">

        <form-validator v-if="validateUrl" :url="validateUrl"></form-validator>
    </div>
</template>

<script>
    import vSelect from "@remp/js-commons/js/components/vSelect";
    import EntityParam from "./EntityParam";

    let props = [
        "_id",
        "_parent_id",
        "_name",
        "_params",
        "_types",
        "_rootEntities",
        "_validateUrl"
    ];

    export default {
        components: {
            vSelect,
            EntityParam,
            FormValidator
        },
        props: props,
        mounted() {
            props.forEach((prop) => {
                this[prop.slice(1)] = this[prop];
            });

            for (let param in this.params) {
                if (this.params.hasOwnProperty(param)) {
                    this.params[param].uid = this.generateUid();
                }
            }

            if (!this.params.length) {
                this.addNewParam();
            }
        },
        data() {
            return {
                "id": null,
                "parent_id": null,
                "name": null,
                "params": {},
                "params_to_delete": [],
                "types": null,
                "rootEntities": null,
                "validateUrl": null,
                "submitAction": null,
                "activationMode": null,
                "action": null
            };
        },
        computed: {
            references() {
                let options = [];

                if (this.rootEntities) {
                    for (let ii = 0; ii < this.rootEntities.length; ii++) {
                        options.push({
                            "label": this.rootEntities[ii].name,
                            "value": this.rootEntities[ii].id
                        })
                    }
                }

                return options;
            },
            paramTypes() {
                let options = [];

                for (let prop in this.types) {
                    if (this.types.hasOwnProperty(prop)) {
                        options.push({
                            "label": this.types[prop],
                            "value": prop
                        })
                    }
                }

                return options;
            }
        },
        methods: {
            addNewParam() {
                this.params.push({
                    id: null,
                    name: null,
                    type: null,
                    uid: this.generateUid(),
                    deleted_at: null
                });
            },
            generateUid() {
                return Math.random().toString(36).substr(2, 9);
            }
        }
    }
</script>

<style>
    .table-striped > tbody > tr:nth-of-type(odd) .bootstrap-select > .btn-default:before {
        background-color: #f4f4f4;
    }

    .table-td-button {
        width: 71px;
    }

    .table-td-type {
        width: 200px;
    }

    .deleted-param {
        -webkit-opacity: 0.4;
        -moz-opacity: 0.4;
        -ms-opacity: 0.4;
        -o-opacity: 0.4;
        -khtml-opacity: 0.4;
        opacity: 0.4;
        cursor: not-allowed;
    }
</style>
