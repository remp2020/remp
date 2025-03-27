<template>
    <div class="container">
        <div class="row">
            <div class="col-md-12 col-lg-8">

                <div class="panel-group z-depth-1-top">
                    <div class="panel">
                        <div class="card-header clearfix">
                            <h2 class="m-t-0 pull-left">
                                <div v-if="action == 'edit'">
                                    Edit campaign
                                </div>
                                <div v-else>
                                    Create campaign
                                </div>

                                <small v-if="name">{{ name }}</small>
                            </h2>
                            <div class="actions">
                                <a v-if="showLink" :href="showLink" class="btn palette-Cyan bg waves-effect">
                                    <i class="zmdi zmdi-palette-Cyan zmdi-eye"></i> Show
                                </a>
                                <a v-if="statsLink" :href="statsLink" class="btn btn palette-Cyan bg waves-effect">
                                    <i class="zmdi zmdi-palette-Cyan zmdi-chart"></i> Stats
                                </a>
                                <a v-if="copyLink" :href="copyLink" class="btn palette-Cyan bg waves-effect">
                                    <i class="zmdi zmdi-palette-Cyan zmdi-copy"></i> Copy
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="panel-group z-depth-1-top" id="accordion" role="tablist" aria-multiselectable="false">
                    <div class="panel panel-default">
                        <div class="panel-heading" role="tab" id="headingCampaign">
                            <h4 class="panel-title">
                                <a role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseCampaign" aria-expanded="true" aria-controls="collapseCampaign" :class="{ green: highlightNameCollapse }">
                                    Campaign name &amp; primary banner (required)
                                </a>
                            </h4>
                        </div>
                        <div id="collapseCampaign" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="headingCampaign">
                            <div class="panel-body p-b-30 p-l-10 p-r-20">

                                <div class="row">
                                    <div class="col-md-12">

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
                        <div class="panel-heading" role="tab" id="headingTests">
                            <h4 class="panel-title">
                                <a role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseTests" aria-expanded="false" aria-controls="collapseTests" :class="{ green: highlightABTestingCollapse }">
                                    A/B test
                                </a>
                            </h4>
                        </div>
                        <div id="collapseTests" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingTests">
                            <div class="panel-body p-b-30 p-l-10 p-r-20">
                                <ab-testing
                                    v-if="showABTestingComponent"
                                    :variants="variants"
                                    :variantOptions="variantOptions"
                                    :bannerId="bannerId"
                                ></ab-testing>
                                <div v-else class="ab-testing-not-available">
                                    To allow A/B testing you have to set primary banner in previous tab.
                                </div>
                            </div><!-- .panel-body -->
                        </div>
                    </div><!-- .panel (a/b testing) -->

                    <div class="panel panel-default">
                        <div class="panel-heading" role="tab" id="headingUserProperties">
                            <h4 class="panel-title">
                                <a role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseUserProperties" aria-expanded="false" aria-controls="collapseUserProperties" :class="{ green: highlightUserAttributesCollapse }">
                                    User attributes
                                </a>
                            </h4>
                        </div>
                        <div id="collapseUserProperties" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingUserProperties">
                            <div class="panel-body p-l-10 p-r-20">
                                <div class="row">
                                    <div class="col-md-8">
                                        <p class="m-l-20">User needs to be authenticated or not to see the campaign</p>

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
                                                        <small class="help-block">To use this filter, you have to be setting <code>userId: String</code> within your REMP tracking code.</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div><!-- .input-group -->

                                        <div class="input-group m-t-30">
                                            <span class="input-group-addon"><i class="zmdi zmdi-account"></i></span>
                                            <div>
                                                <div class="row">
                                                    <div class="col-md-12">
                                                        <label for="usingAdblock" class="fg-label">User ad-blocking state</label>
                                                    </div>
                                                    <div class="col-md-12">
                                                        <v-select v-model="usingAdblock"
                                                                  id="usingAdblock"
                                                                  :name="'using_adblock'"
                                                                  :value="usingAdblock"
                                                                  :options.sync="adBlockingOptions"
                                                                  :title="'Everyone'"
                                                        ></v-select>
                                                        <small class="help-block">
                                                            To use this filter, you have to be setting <code>usingAdblock: Boolean</code> within your REMP tracking code.
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div><!-- .input-group -->

                                    </div>
                                </div>

                            </div><!-- .panel-body -->
                        </div>
                    </div><!-- .panel (user state) -->

                    <div class="panel panel-default">
                        <div class="panel-heading" role="tab" id="headingPageviewAttributes">
                            <h4 class="panel-title">
                                <a role="button" data-toggle="collapse" data-parent="#accordion" href="#collapsePageviewAttributes" aria-expanded="false" aria-controls="collapsePageviewAttributes" :class="{ green: highlightPageviewAttributesCollapse }">
                                    Pageview attributes
                                </a>
                            </h4>
                        </div>
                        <div id="collapsePageviewAttributes" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingUserProperties">
                            <div class="panel-body p-l-10 p-r-20">
                                <div class="row">
                                    <div class="col-md-8">
                                        <pageview-attributes
                                            :pageviewAttributes="pageviewAttributes"
                                            @pageviewAttributesModified="updatePageviewAttributes"
                                        ></pageview-attributes>
                                    </div>
                                </div>

                            </div><!-- .panel-body -->
                        </div>
                    </div><!-- .panel (pageview attributes) -->

                    <div class="panel panel-default">
                        <div class="panel-heading" role="tab" id="headingInclusiveSegments">
                            <h4 class="panel-title">
                                <a role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseInclusiveSegments" aria-expanded="false" aria-controls="collapseInclusiveSegments" :class="{ green: highlightSegmentsCollapse }">
                                    Segments - who will see the banner?
                                </a>
                            </h4>
                        </div>
                        <div id="collapseInclusiveSegments" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingInclusiveSegments">
                            <div class="panel-body p-l-10 p-r-20">
                                <div class="row">
                                    <div class="col-md-12">
                                        <p class="m-l-20">
                                            User <strong>needs to be member of ALL</strong> of these segments in order to see the campaign:
                                        </p>

                                        <div class="input-group">
                                            <span class="input-group-addon"><i class="zmdi zmdi-accounts-list"></i></span>
                                            <div>
                                                <div v-if="Object.keys(availableSegments).length" class="row">
                                                    <div class="col-md-12">
                                                        <select v-model="addedSegment" title="Segments to see the campaign" v-on:change="setSegmentAsInclusive" class="selectpicker" data-live-search="true" data-max-options="1">
                                                            <optgroup v-for="(list,label) in availableSegments" v-bind:label="label">
                                                                <option v-for="(obj,code) in list" v-bind:value="obj">
                                                                    {{ obj.name }}
                                                                </option>
                                                            </optgroup>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div v-else class="panel panel-default">
                                                    <div class="panel-body app-info p-10">
                                                        <p>No segments are available for selection. This might be because you don't have any segment providers configured.</p>
                                                        <p class="m-b-0 p-0">If you want to use segments, configure your environment to use existing Segment Providers or <a href="https://github.com/remp2020/remp/tree/master/Campaign#segment-integration">implement your own integration</a>.</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div v-for="(id,i) in removedSegments">
                                            <input type="hidden" name="removed_segments[]" v-model="removedSegments[i]" />
                                        </div>

                                        <div class="row m-t-10 m-l-30">
                                            <div class="col-md-12">
                                                <div class="row m-b-10" v-for="(segment,i) in segments" v-if="segment.inclusive == undefined || segment.inclusive" style="line-height: 25px">
                                                    <div class="col-md-12 text-left">
                                                        <div class="pull-left m-r-10">
                                                            <span v-on:click="removeSegment(i)" class="btn btn-sm bg palette-Red waves-effect p-5 remove-segment" style="font-size:1em">&times;</span>
                                                        </div> {{ segmentMap[segment.code] }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-12" style="margin-top:15px">
                                        <p class="m-l-20">
                                            User <strong>CANNOT be member of ANY</strong> of these segments in order to see the campaign:
                                        </p>

                                        <div class="input-group">
                                            <span class="input-group-addon"><i class="zmdi zmdi-accounts-list"></i></span>
                                            <div>
                                                <div v-if="Object.keys(availableSegments).length" class="row">
                                                    <div class="col-md-12">
                                                        <select v-model="addedSegment" title="Segments that should not see the campaign" v-on:change="setSegmentAsExclusive" class="selectpicker" data-live-search="true" data-max-options="1">
                                                            <optgroup v-for="(list,label) in availableSegments" v-bind:label="label">
                                                                <option v-for="(obj,code) in list" v-bind:value="obj">
                                                                    {{ obj.name }}
                                                                </option>
                                                            </optgroup>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div v-else class="panel panel-default">
                                                    <div class="panel-body app-info p-10">
                                                        <p>No segments are available for selection. This might be because you don't have any segment providers configured.</p>
                                                        <p class="m-b-0 p-0">If you want to use segments, configure your environment to use existing Segment Providers or <a href="https://github.com/remp2020/remp/tree/master/Campaign#segment-integration">implement your own integration</a>.</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div v-for="(id,i) in removedSegments">
                                            <input type="hidden" name="removed_segments[]" v-model="removedSegments[i]" />
                                        </div>

                                        <div class="row m-t-10 m-l-30">
                                            <div class="col-md-12">
                                                <div class="row m-b-10" v-for="(segment,i) in segments" v-if="segment.inclusive != undefined && !segment.inclusive" style="line-height: 25px">
                                                    <div class="col-md-12 text-left">
                                                        {{ segmentMap[segment.code] }}
                                                        <div class="pull-left m-r-20">
                                                            <span v-on:click="removeSegment(i)" class="btn btn-sm bg palette-Red waves-effect p-5 remove-segment" style="font-size:1em">&times;</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div><!-- .panel-body -->
                        </div>
                        <div v-for="(segment,i) in segments">
                            <input type="hidden" v-bind:name="'segments['+i+'][id]'" v-model="segment.id" />
                            <input type="hidden" v-bind:name="'segments['+i+'][code]'" v-model="segment.code" />
                            <input type="hidden" v-bind:name="'segments['+i+'][provider]'" v-model="segment.provider" />
                            <input type="hidden" v-bind:name="'segments['+i+'][inclusive]'" v-model="segment.inclusive" />
                        </div>
                    </div><!-- .panel (segments) -->

                    <div class="panel panel-default panel-whereToDisplay">
                        <div class="panel-heading" role="tab" id="headingWhereToDisplay">
                            <h4 class="panel-title">
                                <a role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseWhereToDisplay" aria-expanded="false" aria-controls="collapseWhereToDisplay" :class="{ green: highlightWhereToCollapse }">
                                    Where to display?
                                </a>
                            </h4>
                        </div>
                        <div id="collapseWhereToDisplay" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingWhereToDisplay">
                            <div class="panel-body p-l-10 p-r-20">
                                <div class="row">
                                    <div class="col-md-8 col-sm-12">
                                        <url-rules
                                            label="URL"
                                            hint="Rule is matched if pageview URL contains one of these strings. Filter does not support any wildcards."
                                            id="url_filter"
                                            title="URL filter"
                                            filter-name="url_filter"
                                            patterns-name="url_patterns"
                                            :urlFilterTypes="urlFilterTypes"
                                            :urlFilter="urlFilter"
                                            :urlPatterns="urlPatterns"
                                        ></url-rules>
                                    </div>
                                </div>
                            </div><!-- .panel-body -->
                            <div class="panel-body p-l-10 p-r-20">
                                <div class="row">
                                    <div class="col-md-8 col-sm-12">
                                        <url-rules
                                            label="Traffic source"
                                            hint="Rule is matched if traffic source contains one of these strings. Filter does not support any wildcards. Session source is effectively referer of the first pageview of the visit."
                                            id="source_filter"
                                            title="Source filter"
                                            filter-name="source_filter"
                                            patterns-name="source_patterns"
                                            :urlFilterTypes="sourceFilterTypes"
                                            :urlFilter="sourceFilter"
                                            :urlPatterns="sourcePatterns"
                                        ></url-rules>
                                    </div>
                                </div>
                            </div><!-- .panel-body -->
                        </div>
                    </div><!-- .panel (segments) -->

                    <div class="panel panel-default">
                        <div class="panel-heading" role="tab" id="headingBannerRules">
                            <h4 class="panel-title">
                                <a role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseBannerRules" aria-expanded="false" aria-controls="collapseBannerRules" :class="{ green: highlightBannerRulesCollapse }">
                                    Banner rules - how often to display?
                                </a>
                            </h4>
                        </div>
                        <div id="collapseBannerRules" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingBannerRules">
                            <div class="panel-body p-l-10 p-r-20">
                                <pageview-rules :pageviewRules="pageviewRules" :oncePerSession="oncePerSession" :prioritizeBannersSamePosition="prioritizeBannersSamePosition" @pageviewRulesModified="updatePageviewRules"></pageview-rules>
                            </div><!-- .panel-body -->
                        </div>
                    </div><!-- .panel (banner rules) -->

                    <div class="panel panel-default">
                        <div class="panel-heading" role="tab" id="headingGeoTargeting">
                            <h4 class="panel-title">
                                <a role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseGeoTargeting" aria-expanded="false" aria-controls="collapseGeoTargeting" :class="{ green: highlightCountriesCollapse }">
                                    Geo targeting - which countries?
                                </a>
                            </h4>
                        </div>
                        <div id="collapseGeoTargeting" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingGeoTargeting">
                            <div class="panel-body p-b-30 p-l-10 p-r-20">

                                <div class="input-group m-t-20">
                                    <span class="input-group-addon"><i class="zmdi zmdi-wallpaper"></i></span>
                                    <div>
                                        <div class="row">
                                            <div class="col-md-8 col-sm-12">
                                                <label for="countries_blacklist" class="fg-label">Whitelist / Blacklist</label>
                                            </div>
                                            <div class="col-md-8 col-sm-12">
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
                                            <div class="col-md-8 col-sm-12">
                                                <select v-model="addedCountry" title="Select countries" v-on:change="selectCountry" class="selectpicker" data-live-search="true" data-max-options="1">
                                                    <option v-for="(obj,iso_code) in availableCountries" :value="obj">
                                                        {{ obj.name }}
                                                    </option>
                                                </select>
                                            </div>
                                        </div><!-- .row -->

                                    </div>
                                </div>

                                <div class="row m-t-20 m-l-30" v-if="countries.length">
                                    <div class="col-md-10">
                                        <small>Selected countries</small>
                                    </div>
                                </div>

                                <div v-for="country in countries">
                                    <input type="hidden" name="countries[]" v-model="country.iso_code" />
                                </div>

                                <div class="row m-t-10 m-l-30">
                                    <div class="col-md-12">
                                        <div class="row m-b-10" v-for="(country,i) in countries" style="line-height: 25px">
                                            <div class="col-md-12 text-left">
                                                {{ country.name }}
                                                <div class="pull-left m-r-20">
                                                    <span v-on:click="removeCountry(i)" class="btn btn-sm bg palette-Red waves-effect p-5 remove-segment">&times;</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                              <div class="input-group m-t-30">
                                <span class="input-group-addon"><i class="zmdi zmdi-wallpaper"></i></span>
                                <div>
                                  <div class="row">
                                    <div class="col-md-8 col-sm-12">
                                      <label for="countries_blacklist" class="fg-label">Languages</label>
                                    </div>
                                    <div class="col-md-8 col-sm-12">
                                      <v-select v-model="languages"
                                                id="languages"
                                                :name="'languages[]'"
                                                :options.sync="availableLanguages"
                                                :multiple="true"
                                      ></v-select>
                                    </div>

                                    <div class="col-md-8 col-sm-12">
                                      <small class="help-block">Allows campaign for all languages, if no language is selected.</small>
                                    </div>
                                  </div><!-- .row -->

                                </div>
                              </div>

                            </div><!-- .panel-body -->
                        </div>
                    </div><!-- .panel (geo targeting) -->

                    <div class="panel panel-default">
                        <div class="panel-heading" role="tab" id="headingDevicesTargeting">
                            <h4 class="panel-title">
                                <a role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseDevicesTargeting" aria-expanded="false" aria-controls="collapseDevicesTargeting" :class="{ green: highlightDevicesCollapse }">
                                    Devices targeting
                                </a>
                            </h4>
                        </div>
                        <div id="collapseDevicesTargeting" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingDevicesTargeting">
                            <div class="panel-body p-l-10 p-r-20">

                                <div class="input-group fg-float">
                                    <div class="checkbox" v-for="(device) in allDevices" :key="device">
                                    <label class="m-l-15 m-t-15">
                                        Show on {{ device }}
                                        <input :checked="deviceSelected(device)" :value="device" name="devices[]" type="checkbox" @change="handleToggleSelectDevice(device)">
                                        <i class="input-helper"></i>
                                    </label>
                                    </div>
                                </div>

                                <div class="input-group m-t-20">
                                    <span class="input-group-addon"><i class="zmdi zmdi-devices"></i></span>
                                    <div>
                                        <div class="row">
                                            <div class="col-md-8 col-sm-12">
                                                <label for="countries_blacklist" class="fg-label">Operating system</label>
                                            </div>
                                            <div class="col-md-8 col-sm-12">
                                                <v-select v-model="selectedOperatingSystems"
                                                          id="operating_systems"
                                                          :name="'operating_systems[]'"
                                                          :value="selectedOperatingSystems"
                                                          :options.sync="availableOperatingSystems"
                                                          :multiple="true"
                                                >
                                                </v-select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div><!-- .panel-body -->
                        </div>
                    </div><!-- .panel (device targetting) -->

                    <div class="panel panel-default">
                        <div class="panel-heading" role="tab" id="headingWhenToLaunch">
                            <h4 class="panel-title">
                                <a role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseWhenToLaunch" aria-expanded="false" aria-controls="collapseWhenToLaunch" :class="{ green: isScheduled }">
                                    When to launch
                                </a>
                            </h4>
                        </div>
                        <div id="collapseWhenToLaunch" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingWhenToLaunch">
                            <div class="panel-body p-l-10 p-r-20">

                                <div>
                                    <div class="row">
                                        <div class="col-md-12 p-l-30">
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
                                                        <div class="form-group col-md-9">
                                                            <small class="help-block">Schedule new campaign run. This does not affect existing schedules.</small>
                                                            <div class="input-group">
                                                                <span class="input-group-addon"><i class="zmdi zmdi-timer"></i></span>
                                                                <div class="dtp-container fg-line">
                                                                    <label for="new_schedule_start_time_frontend" class="fg-label">Start time</label>
                                                                    <input class="form-control date-time-picker" name="new_schedule_start_time_frontend" type="datetime" id="new_schedule_start_time_frontend">
                                                                </div>
                                                                <input name="new_schedule_start_time" type="hidden" v-model="startTime">
                                                            </div>

                                                            <div class="input-group">
                                                                <span class="input-group-addon"><i class="zmdi zmdi-timer-off"></i></span>
                                                                <div class="dtp-container fg-line">
                                                                    <label for="new_schedule_end_time_frontend" class="fg-label">End time</label>
                                                                    <input class="form-control date-time-picker" name="new_schedule_end_time_frontend" type="datetime" id="new_schedule_end_time_frontend">
                                                                </div>
                                                                <input name="new_schedule_end_time" type="hidden" v-model="endTime">
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
                        <input type="hidden" name="action" :value="submitAction">

                        <button class="btn btn-info waves-effect" type="submit" @click="submitAction = 'save'">
                            <i class="zmdi zmdi-check"></i> Save
                        </button>
                        <button class="btn btn-info waves-effect" type="submit" @click="submitAction = 'save_close'">
                            <i class="zmdi zmdi-mail-send"></i> Save and close
                        </button>
                    </div>
                </div>
            </div>
        </div>


        <form-validator :url="validateUrl"></form-validator>
    </div>
</template>

<script type="text/javascript">
    import vSelect from "@remp/js-commons/js/components/vSelect";
    import PageviewRules from "./PageviewRules";
    import FormValidator from "@remp/js-commons/js/components/FormValidator";
    import AbTesting from "./AbTesting";
    import UrlRules from "./UrlRules";
    import PageviewAttributes from "./PageviewAttributes";

    let props = [
        "_name",
        "_segments",
        "_bannerId",
        "_variants",
        "_signedIn",
        "_usingAdblock",
        "_oncePerSession",
        "_active",
        "_countries",
        "_languages",
        "_countriesBlacklist",
        "_allDevices",
        "_availableOperatingSystems",
        "_selectedDevices",
        "_selectedOperatingSystems",
        "_validateUrl",
        "_urlFilterTypes",
        "_sourceFilterTypes",
        "_urlFilter",
        "_urlPatterns",
        "_sourceFilter",
        "_sourcePatterns",
        "_statsLink",
        "_showLink",
        "_copyLink",
        "_editLink",
        "_prioritizeBannersSamePosition",

        "_banners",
        "_availableSegments",
        "_pageviewRules",
        "_addedSegment",
        "_removedSegments",
        "_segmentMap",
        "_eventTypes",
        "_availableCountries",
        "_availableLanguages",
        "_countriesBlacklistOptions",
        "_pageviewAttributes",

        "_activationMode",
        "_action"
    ];
    export default {
        components: {
            vSelect,
            AbTesting,
            FormValidator,
            PageviewRules,
            UrlRules,
            PageviewAttributes
        },
        created: function(){
            let self = this;

            props.forEach((prop) => {
                this[prop.slice(1)] = this[prop];
            });
        },
        mounted: function() {
            let $startTimeFE = $("#new_schedule_start_time_frontend");
            let $endTimeFE = $("#new_schedule_end_time_frontend");
            let self = this;

            $startTimeFE.on('dp.change', function() {
                let st = $(this).data("DateTimePicker").date();
                let et = $endTimeFE.data("DateTimePicker").date();
                if (st && et && st.unix() > et.unix()) {
                    $endTimeFE.data("DateTimePicker").date(st);
                }
                self.startTime = st ? st.toISOString() : null;
            });

            $endTimeFE.on("dp.change", function (e) {
                let st = $startTimeFE.data("DateTimePicker").date();
                let et = $(this).data("DateTimePicker").date();
                if (st && et && et.unix() < st.unix()) {
                    $startTimeFE.data("DateTimePicker").date(et);
                }
                self.endTime = et ? et.toISOString() : null;
            }).datetimepicker({useCurrent: false});

            if (this.bannerId) {
                this.showABTestingComponent = true;
            }

            for (let ii = 0; ii < this.countries.length; ii++) {
                this.countries[ii] = this.availableCountries[this.countries[ii]];
            }
        },
        props: props,
        data: function() {
            return {
                "name": null,
                "segments": [],
                "bannerId": null,
                "variants": null,
                "removedVariants": null,
                "showABTestingComponent": false,
                "signedIn": null,
                "usingAdblock": null,
                "oncePerSession": null,
                "active": null,
                "countries": [],
                "addedCountry": null,
                "countriesBlacklist": null,
                "allDevices": null,
                "availableOperatingSystems": null,
                "selectedDevices": null,
                "selectedOperatingSystems": null,
                "validateUrl": null,
                "submitAction": null,
                "urlFilterTypes": null,
                "sourceFilterTypes": null,
                "urlFilter": null,
                "urlPatterns": null,
                "sourceFilter": null,
                "sourcePatterns": null,
                "prioritizeBannersSamePosition": false,

                "banners": null,
                "availableSegments": null,
                "addedSegment": null,
                "removedSegments": [],
                "segmentMap": null,
                "eventTypes": null,
                "pageviewRules": {},
                "availableCountries": null,
                "languages": null,
                "countriesBlacklistOptions": null,
                "pageviewAttributes": [],

                "startTime": null,
                "endTime": null,
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
            variantOptions: function() {
                //same as bannerOptions, just add null element (alternative banner is nullable)
                let result = [];
                result.push({
                    "label": "No alternative",
                    "value": null,
                });

                return result.concat(this.bannerOptions);
            },
            signedInOptions: function() {
                return [
                    {"label": "Everyone", "value": null},
                    {"label": "Only signed in", "value": true},
                    {"label": "Only anonymous ", "value": false},
                ];
            },
            adBlockingOptions: function () {
                return [
                    {"label": "Everyone", "value": null},
                    {"label": "Only with adblock", "value": true},
                    {"label": "Only without adblock", "value": false}
                ];
            },
            pageviewRulesNotDefault: function () {
                if (this.pageviewRules.display_banner !== 'always' || this.pageviewRules.display_times) {
                    return true;
                }
                return false;
            },
            isScheduled: function () {
                if ((this.active && this.activationMode === 'activate-now') ||
                    (this.startTime != null && this.endTime != null && this.activationMode === 'activate-schedule')
                ) {
                    return true;
                }

                return false;
            },
            highlightNameCollapse: function() {
                return (this.name || this.bannerId);
            },
            highlightSegmentsCollapse: function () {
                return this.segments.length;
            },
            highlightUserAttributesCollapse: function() {
                return this.signedIn || this.usingAdblock;
            },
            highlightWhereToCollapse: function () {
                return (this.urlFilter !== 'everywhere' || this.sourceFilter !== 'everywhere');
            },
            highlightBannerRulesCollapse: function () {
                return (this.pageviewRulesNotDefault || this.oncePerSession === true);
            },
            highlightCountriesCollapse: function () {
                return (this.countries && this.countries.length);
            },
            highlightDevicesCollapse: function () {
                return (this.selectedDevices.length < this.allDevices.length) || this.selectedOperatingSystems.length > 0;
            },
            highlightABTestingCollapse: function () {
                return (this.variants.length > 2);
            },
            highlightPageviewAttributesCollapse: function () {
                return (this.pageviewAttributes.length > 0);
            }
        },
        methods: {
            handleToggleSelectDevice: function (device) {
                if (this.deviceSelected(device)) {
                    this.selectedDevices.splice(
                        this.selectedDevices.indexOf(device), 1
                    );
                    return;
                }

                this.selectedDevices.push(device);
            },
            deviceSelected: function (device) {
                if (this.selectedDevices.indexOf(device) !== -1) {
                    return true;
                }

                return false;
            },
            selectSegment: function(isToInclude) {
                if (typeof this.addedSegment === 'undefined') {
                    return;
                }
                for (let i in this.segments) {
                    if (this.segments[i].code === this.addedSegment.code) {
                        return;
                    }
                }
                this.addedSegment.inclusive = isToInclude;
                this.segments.push(this.addedSegment);
            },
            setSegmentAsInclusive: function() {
                this.selectSegment(true);
            },
            setSegmentAsExclusive: function() {
                this.selectSegment(false);
            },
            removeSegment: function(index) {
                let toRemove = this.segments[index];
                this.segments.splice(index, 1);
                this.removedSegments.push(toRemove.id);
            },
            selectCountry: function() {
                if (typeof this.addedCountry === 'undefined') {
                    return;
                }
                for (let i in this.countries) {
                    if (this.countries[i].iso_code === this.addedCountry.iso_code) {
                        return;
                    }
                }
                this.countries.push(this.addedCountry);
            },
            removeCountry: function(index) {
                this.countries.splice(index, 1);
            },
            updatePageviewRules: function (pageviewRules) {
                this.pageviewRules = pageviewRules.rules;
                this.oncePerSession = pageviewRules.oncePerSession;
            },
            updatePageviewAttributes: function (pageviewAttributes) {
                this.pageviewAttributes = pageviewAttributes;
            },
        },
        watch: {
            bannerId: function () {
                if (this.bannerId) {
                    this.showABTestingComponent = true;
                } else {
                    this.showABTestingComponent = false;
                }
            }
        }
    }
</script>


<style scoped>
    .remove-segment {
        font-size: 1.5em;
        line-height: 0.5;
        padding-bottom: 7px !important;
    }

    .ab-testing-not-available {
        text-align: center;
        font-size: 16px;
        margin-top: 20px;
    }

    @media(max-width: 768px) {
        .panel-whereToDisplay .panel-body {
            padding-left: 25px !important;
        }
    }
</style>
