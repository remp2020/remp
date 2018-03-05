<template>
    <div class="row">
        <div class="col-md-4">
            <h4>Settings</h4>

            <div class="input-group fg-float m-t-30">
                <span class="input-group-addon"><i class="zmdi zmdi-file-text"></i></span>
                <div class="fg-line">
                    <label for="name" class="fg-label">Name</label>
                    <input v-model="name" class="form-control fg-input" name="name" id="name" type="text">
                </div>
            </div>

            <div class="input-group m-t-30">
                <span class="input-group-addon"><i class="zmdi zmdi-wallpaper"></i></span>
                <div>
                    <div class="row">
                        <div class="col-md-12">
                            <label for="banner_id" class="fg-label">Banner</label>
                        </div>
                        <div class="col-md-12">
                            <v-select v-model="bannerId"
                                      id="banner_id"
                                      :name="'banner_id'"
                                      :value="bannerId"
                                      :options.sync="bannerOptions"
                            ></v-select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="input-group m-t-30">
                <span class="input-group-addon"><i class="zmdi zmdi-wallpaper"></i></span>
                <div>
                    <div class="row">
                        <div class="col-md-12">
                            <label for="alt_banner_id" class="fg-label">Banner B alternative</label>
                        </div>
                        <div class="col-md-12">
                            <v-select v-model="altBannerId"
                                      id="alt_banner_id"
                                      :name="'alt_banner_id'"
                                      :value="altBannerId"
                                      :title="'No alternative'"
                                      :options.sync="altBannerOptions"
                            ></v-select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="input-group m-t-30">
                <span class="input-group-addon"><i class="zmdi zmdi-account"></i></span>
                <div>
                    <div class="row">
                        <div class="col-md-12">
                            <label for="signed_in" class="fg-label">User signed-in state</label>
                        </div>
                        <div class="col-md-12">
                            <v-select v-model="signedIn"
                                    id="signed_in"
                                    :name="'signed_in'"
                                    :value="signedIn"
                                    :options.sync="signedInOptions"
                                    :title="'Everyone'"
                            ></v-select>
                            <small class="help-block">To use this filter, you have to be setting <code>signedIn: Boolean</code> within your REMP tracking code.</small>
                        </div>
                    </div>
                </div>
            </div>


            <div class="input-group m-t-30">
                <span class="input-group-addon"><i class="zmdi zmdi-account"></i></span>
                <div>
                    <rules :pageviewRules="pageviewRules"></rules>   
                </div>
            </div>

            <div class="input-group fg-float m-t-30 checkbox">
                <label class="m-l-15">
                    Display once per session
                    <input v-model="oncePerSession" value="1" name="once_per_session" type="checkbox">
                    <i class="input-helper"></i>
                </label>
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

        <div class="col-md-6">
            <h4>Segments</h4>

            <div class="row">
                <div class="col-md-12">
                    <p>User needs to be member of all selected segments for campaign to be shown.</p>
                </div>
            </div>

            <div class="input-group">
                <span class="input-group-addon"><i class="zmdi zmdi-accounts-list"></i></span>
                <div class="row">
                    <div class="col-md-12">
                        <select v-model="addedSegment" title="Select user segments" v-on:change="selectSegment" class="selectpicker col-md-8" data-live-search="true">
                            <optgroup v-for="(list,label) in availableSegments" v-bind:label="label">
                                <option v-for="(obj,code) in list" v-bind:value="obj">
                                    {{ obj.name }}
                                </option>
                            </optgroup>
                        </select>
                    </div>
                </div>
            </div>

            <div v-for="(segment,i) in segments">
                <input type="hidden" v-bind:name="'segments['+i+'][id]'" v-model="segment.id" />
                <input type="hidden" v-bind:name="'segments['+i+'][code]'" v-model="segment.code" />
                <input type="hidden" v-bind:name="'segments['+i+'][provider]'" v-model="segment.provider" />
            </div>
            <div v-for="(id,i) in removedSegments">
                <input type="hidden" name="removedSegments[]" v-model="removedSegments[i]" />
            </div>

            <div class="row m-t-20">
                <div class="col-md-8">
                    <div class="row m-b-10" v-for="(segment,i) in segments" style="line-height: 25px">
                        <div class="col-md-12 text-right">
                            {{ segmentMap[segment.code] }}
                            <div class="pull-right m-l-20">
                                <span v-on:click="removeSegment(i)" class="btn btn-sm bg palette-Red waves-effect"><i class="zmdi zmdi-minus-square"></i> Delete</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script type="text/javascript">
    import vSelect from "remp/js/components/vSelect.vue";
    import Rules from "./templates/Rules";

    let props = [
        "_name",
        "_segments",
        "_bannerId",
        "_altBannerId",
        "_signedIn",
        "_oncePerSession",
        "_active",
        "_banners",
        "_availableSegments",
        "_pageviewRules",
        "_addedSegment",
        "_removedSegments",
        "_segmentMap",
        "_eventTypes",
    ];
    export default {
        components: {
            vSelect,
            Rules
        },
        created: function(){
            let self = this;
            props.forEach((prop) => {
                this[prop.slice(1)] = this[prop];
            });
        },
        props: props,
        data: function() {
            return {
                "name": null,
                "segments": [],
                "bannerId": null,
                "altBannerId": null,
                "signedIn": null,
                "oncePerSession": null,
                "active": null,

                "banners": null,
                "availableSegments": null,
                "addedSegment": null,
                "removedSegments": [],
                "segmentMap": null,
                "eventTypes": null,
                "pageviewRules": [
                    {
                        "rule": null,
                        "num": null
                    }
                ]
            }
        },
        computed: {
            "bannerOptions": function() {
                let result = [];
                for (let banner of this.banners) {
                    result.push({
                        "label": banner.name,
                        "value": banner.id,
                    })
                }
                return result;
            },
            "altBannerOptions": function() {
                //same as bannerOptions, just add null element (alternative banner is nullable)
                let result = [];
                result.push({
                    "label": "No alternative",
                    "value": null,
                })
                return result.concat(this.bannerOptions);
            },
            "signedInOptions": function() {
                return [
                    {"label": "Everyone", "value": null},
                    {"label": "Only signed in", "value": true},
                    {"label": "Only anonymous ", "value": false},
                ];
            }
        },
        methods: {
            'selectSegment': function() {
                if (typeof this.addedSegment === 'undefined') {
                    return;
                }
                for (let i in this.segments) {
                    if (this.segments[i].code === this.addedSegment.code) {
                        return;
                    }
                }
                this.segments.push(this.addedSegment);
            },
            'removeSegment': function(index) {
                let toRemove = this.segments[index];
                this.segments.splice(index, 1);
                this.removedSegments.push(toRemove.id);
            }
        }
    }
</script>
