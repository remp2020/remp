<template>
    <div class="container">
        <div class="row">
            <div class="col-md-12 col-lg-8">

                <div class="panel-group z-depth-1-top">
                    <div class="panel">
                        <div class="card-header">
                            <h2 class="m-t-0">
                                <div v-if="action == 'edit'">
                                    Edit campaign
                                </div>
                                <div v-else>
                                    Create campaign
                                </div>

                                <small v-if="name">{{ name }}</small>
                            </h2>
                            <p class="m-t-20 m-b-10">We've developed a single process for freelancers with proven industry expirience to access the best projects from clients that use additional services on Workspace.</p>
                        </div>
                    </div>
                </div>

                <div class="panel-group z-depth-1-top" id="accordion" role="tablist" aria-multiselectable="false">
                    <div class="panel panel-default">
                        <div class="panel-heading" role="tab" id="headingOne">
                            <h4 class="panel-title">
                                <a role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                    Campaign name &amp; primary banner (required)
                                </a>
                            </h4>
                        </div>
                        <div id="collapseOne" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="headingOne">
                            <div class="panel-body p-b-30 p-l-10 p-r-20">

                                <div class="row">
                                    <div class="col-md-6">

                                        <div class="input-group fg-float m-t-30">
                                            <span class="input-group-addon"><i class="zmdi zmdi-file-text"></i></span>
                                            <div class="fg-line">
                                                <label for="name" class="fg-label">Name</label>
                                                <input v-model="name" class="form-control fg-input" name="name" id="name" type="text">
                                            </div>
                                        </div><!-- .input-group -->

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
                                        </div><!-- .input-group -->
                                    </div>
                                </div>

                            </div><!-- .panel-body -->


                        </div>
                    </div><!-- .panel (primary) -->

                    <div class="panel panel-default">
                        <div class="panel-heading" role="tab" id="headingTwo">
                            <h4 class="panel-title">
                                <a role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                    A/B test
                                </a>
                            </h4>
                        </div>
                        <div id="collapseTwo" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingTwo">
                            <div class="panel-body p-b-30 p-l-10 p-r-20">

                                <div class="row">
                                    <div class="col-md-6">

                                        <div class="input-group m-t-20">
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
                                        </div><!-- .input-group -->
                                    </div>
                                </div>
                            </div><!-- .panel-body -->


                        </div>
                    </div><!-- .panel (a/b testing) -->

                    <div class="panel panel-default">
                        <div class="panel-heading" role="tab" id="headingThree">
                            <h4 class="panel-title">
                                <a role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                    Segments - who will see the banner?
                                </a>
                            </h4>
                        </div>
                        <div id="collapseThree" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingThree">
                            <div class="panel-body p-l-10 p-r-20">

                                <div class="row">
                                    <div class="col-md-7">
                                        <p class="m-l-20">User needs to be member of all selected segments for campaign to be shown.</p>

                                        <div class="input-group m-t-30">
                                            <span class="input-group-addon"><i class="zmdi zmdi-account"></i></span>
                                            <div>
                                                <div class="row">
                                                    <div class="col-md-10">
                                                        <label for="signed_in" class="fg-label">User signed-in state</label>
                                                    </div>
                                                    <div class="col-md-10">
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
                                        </div><!-- .input-group -->

                                        <div class="input-group">
                                            <span class="input-group-addon"><i class="zmdi zmdi-accounts-list"></i></span>
                                            <div>
                                                <div class="row">
                                                    <div class="col-md-10">
                                                        <select v-model="addedSegment" title="Select user segments" v-on:change="selectSegment" class="selectpicker" data-live-search="false">
                                                            <optgroup v-for="(list,label) in availableSegments" v-bind:label="label">
                                                                <option v-for="(obj,code) in list" v-bind:value="obj">
                                                                    {{ obj.name }}
                                                                </option>
                                                            </optgroup>
                                                        </select>
                                                    </div>
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
                                            <div class="col-md-10">
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

                            </div><!-- .panel-body -->
                        </div>
                    </div><!-- .panel (segments) -->

                    <div class="panel panel-default">
                        <div class="panel-heading" role="tab" id="headingFour">
                            <h4 class="panel-title">
                                <a role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                                    Banner rules - how often to display?
                                </a>
                            </h4>
                        </div>
                        <div id="collapseFour" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingFour">
                            <div class="panel-body p-l-10 p-r-20">

                                <pageview-rules :pageviewRules="pageviewRules"></pageview-rules>

                                <div class="input-group fg-float m-t-30 checkbox">
                                    <label class="m-l-15">
                                        Display once per session
                                        <input v-model="oncePerSession" value="1" name="once_per_session" type="checkbox">
                                        <i class="input-helper"></i>
                                    </label>
                                </div>

                            </div><!-- .panel-body -->
                        </div>
                    </div><!-- .panel (banner rules) -->

                    <div class="panel panel-default">
                        <div class="panel-heading" role="tab" id="headingFive">
                            <h4 class="panel-title">
                                <a role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseFive" aria-expanded="false" aria-controls="collapseFive">
                                    Geo targeting - which countries?
                                </a>
                            </h4>
                        </div>
                        <div id="collapseFive" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingFive">
                            <div class="panel-body p-b-30 p-l-10 p-r-20">

                                <div class="input-group m-t-20">
                                    <span class="input-group-addon"><i class="zmdi zmdi-wallpaper"></i></span>
                                    <div>
                                        <div class="row">
                                            <div class="col-md-8">
                                                <label for="countries_blacklist" class="fg-label">Whitelist / Blacklist</label>
                                            </div>
                                            <div class="col-md-8">
                                                <v-select v-model="countriesBlacklist"
                                                        id="countries_blacklist"
                                                        :name="'countries_blacklist'"
                                                        :value="countriesBlacklist"
                                                        :options.sync="countriesBlacklistOptions"
                                                >
                                                </v-select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="input-group m-t-30">
                                    <span class="input-group-addon"><i class="zmdi zmdi-wallpaper"></i></span>
                                    <div>
                                        <div class="row">
                                            <div class="col-md-8">
                                                <label for="countries" class="fg-label">Countries</label>
                                            </div>
                                            <div class="col-md-8">
                                                <v-select v-model="countries"
                                                        id="countries"
                                                        :name="'countries[]'"
                                                        :value="countries"
                                                        :options.sync="availableCountries"
                                                        multiple
                                                >
                                                </v-select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div><!-- .panel-body -->
                        </div>
                    </div><!-- .panel (geo targeting) -->

                    <div class="panel panel-default">
                        <div class="panel-heading" role="tab" id="headingSix">
                            <h4 class="panel-title">
                                <a role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseSix" aria-expanded="false" aria-controls="collapseSix">
                                    Devices targeting (mobile/desktop)
                                </a>
                            </h4>
                        </div>
                        <div id="collapseSix" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingSix">
                            <div class="panel-body p-l-10 p-r-20">

                                <div class="input-group fg-float">
                                    <div class="checkbox" v-for="(device) in allDevices" :key="device">
                                    <label class="m-l-15 m-t-15">
                                        Show on {{ device }}
                                        <input :checked="deviceSelected(device)" :value="device" name="devices[]" type="checkbox">
                                        <i class="input-helper"></i>
                                    </label>
                                    </div>
                                </div>

                            </div><!-- .panel-body -->
                        </div>
                    </div><!-- .panel (device targetting) -->

                    <div class="panel panel-default">
                        <div class="panel-heading" role="tab" id="headingSeven">
                            <h4 class="panel-title">
                                <a role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseSeven" aria-expanded="false" aria-controls="collapseSeven">
                                    When to launch
                                </a>
                            </h4>
                        </div>
                        <div id="collapseSeven" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingSeven">
                            <div class="panel-body p-l-10 p-r-20">

                                <div>
                                    <div class="row">
                                        <div class="col-md-6 p-l-30">
                                            <ul class="tab-nav" role="tablist" data-tab-color="teal">
                                                <li v-on:click="activationMode='activate-now'" v-bind:class="{active: activationMode === 'activate-now'}">
                                                    <a href="#schedule-now" role="tab" data-toggle="tab" aria-expanded="true">Activate now</a>
                                                </li>
                                                <li v-on:click="activationMode='activate-schedule'" v-bind:class="{active: activationMode === 'activate-schedule'}">
                                                    <a href="#schedule-plan" role="tab" data-toggle="tab" aria-expanded="false">Add new schedule</a>
                                                </li>
                                            </ul>
                                            <div class="m-t-15">
                                                <div class="tab-content p-0">
                                                    <div role="tabpanel" v-bind:class="[{active: activationMode === 'activate-now'}, 'tab-pane']" id="schedule-now">
                                                        <div class="input-group fg-float m-t-30 checkbox">
                                                            <label class="m-l-15">
                                                                Active
                                                                <input v-model="active" value="1" name="active" type="checkbox">
                                                                <i class="input-helper"></i>
                                                                <small class="help-block">Activating campaign will create new schedule if there is none running.<br>Deactivating campaign will disable all running and planned schedules.</small>
                                                            </label>
                                                        </div>
                                                    </div>

                                                    <div role="tabpanel" v-bind:class="[{active: activationMode === 'activate-schedule'}, 'tab-pane']" id="schedule-schedule">
                                                        <div class="form-group col-md-6">
                                                            <small class="help-block">Planning new schedule activates campaign.</small>
                                                            <div class="input-group">
                                                                <span class="input-group-addon"><i class="zmdi zmdi-timer"></i></span>
                                                                <div class="dtp-container fg-line">
                                                                    <label for="new_schedule_start_time_frontend" class="fg-label">Start time</label>
                                                                    <input class="form-control date-time-picker" name="new_schedule_start_time_frontend" type="datetime" id="new_schedule_start_time_frontend">
                                                                </div>
                                                                <input name="new_schedule_start_time" type="hidden">
                                                            </div>

                                                            <div class="input-group">
                                                                <span class="input-group-addon"><i class="zmdi zmdi-timer-off"></i></span>
                                                                <div class="dtp-container fg-line">
                                                                    <label for="new_schedule_end_time_frontend" class="fg-label">End time</label>
                                                                    <input class="form-control date-time-picker" name="new_schedule_end_time_frontend" type="datetime" id="new_schedule_end_time_frontend">
                                                                </div>
                                                                <input name="new_schedule_end_time" type="hidden">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <input type="hidden" name="activation_mode" v-bind:value="activationMode" />
                                        </div>
                                    </div>
                                </div>

                            </div><!-- .panel-body -->
                        </div>
                    </div><!-- .panel (schedule) -->
                </div><!-- .panel-group -->
            </div>
        </div>

        <div class="row">
            <div class="col-md-12 col-lg-8">
                <div class="input-group m-t-20 m-b-30">
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
        </div>
    </div>
</template>

<script type="text/javascript">
    import vSelect from "remp/js/components/vSelect";
    import PageviewRules from "./templates/PageviewRules";

    let props = [
        "_name",
        "_segments",
        "_bannerId",
        "_altBannerId",
        "_signedIn",
        "_oncePerSession",
        "_active",
        "_countries",
        "_countriesBlacklist",
        "_allDevices",
        "_selectedDevices",

        "_banners",
        "_availableSegments",
        "_pageviewRules",
        "_addedSegment",
        "_removedSegments",
        "_segmentMap",
        "_eventTypes",
        "_availableCountries",
        "_countriesBlacklistOptions",

        "_activationMode",
        "_action"
    ];
    export default {
        components: {
            vSelect,
            PageviewRules
        },
        created: function(){
            let self = this;
            props.forEach((prop) => {
                this[prop.slice(1)] = this[prop];
            });
        },
        mounted: function() {
            var $startTimeFE = $("#new_schedule_start_time_frontend");
            var $startTime = $('input[name="new_schedule_start_time"]');
            var $endTimeFE = $("#new_schedule_end_time_frontend");
            var $endTime = $('input[name="new_schedule_end_time"]');

            $startTimeFE.on('dp.change', function() {
                var st = $(this).data("DateTimePicker").date();
                var et = $endTimeFE.data("DateTimePicker").date();
                if (st && et && st.unix() > et.unix()) {
                    $endTimeFE.data("DateTimePicker").date(st);
                }
            });
            $endTimeFE.on("dp.change", function (e) {
                var st = $startTimeFE.data("DateTimePicker").date();
                var et = $(this).data("DateTimePicker").date();
                if (st && et && et.unix() < st.unix()) {
                    $startTimeFE.data("DateTimePicker").date(et);
                }
            }).datetimepicker({useCurrent: false});

            $('form').on('submit', function() {
                var st = $startTimeFE.data("DateTimePicker").date();
                $startTime.val(st ? st.toISOString() : null);
                var et = $endTimeFE.data("DateTimePicker").date();
                $endTime.val(et ? et.toISOString() : null);
                return true;
            })
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
                "countries": [],
                "countriesBlacklist": null,
                "allDevices": null,
                "selectedDevices": null,

                "banners": null,
                "availableSegments": null,
                "addedSegment": null,
                "removedSegments": [],
                "segmentMap": null,
                "eventTypes": null,
                "pageviewRules": [],
                "availableCountries": null,
                "countriesBlacklistOptions": null,

                "activationMode": null,
                "action": null
            }
        },
        computed: {
            bannerOptions: function() {
                let result = [];
                for (let banner of this.banners) {
                    result.push({
                        "label": banner.name,
                        "value": banner.id,
                    })
                }
                return result;
            },
            altBannerOptions: function() {
                //same as bannerOptions, just add null element (alternative banner is nullable)
                let result = [];
                result.push({
                    "label": "No alternative",
                    "value": null,
                })
                return result.concat(this.bannerOptions);
            },
            signedInOptions: function() {
                return [
                    {"label": "Everyone", "value": null},
                    {"label": "Only signed in", "value": true},
                    {"label": "Only anonymous ", "value": false},
                ];
            }
        },
        methods: {
            deviceSelected: function (device) {
                if (this.selectedDevices.indexOf(device) != -1) {
                    return true;
                }

                return false;
            },
            selectSegment: function() {
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
            removeSegment: function(index) {
                let toRemove = this.segments[index];
                this.segments.splice(index, 1);
                this.removedSegments.push(toRemove.id);
            }
        }
    }
</script>
