@php
    /* @var $campaign \App\Campaign */
@endphp

@push('head')
<link href="/assets/vendor/bootstrap-select/dist/css/bootstrap-select.min.css" rel="stylesheet">
<script src="/assets/vendor/bootstrap-select/dist/js/bootstrap-select.min.js"></script>
@endpush

<style type="text/css">
    .card label {
        font-size: 11px;
    }
</style>

<template id="campaign-form-template">
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

            <div class="input-group m-t-30">
                <span class="input-group-addon"><i class="zmdi zmdi-wallpaper"></i></span>
                <div>
                    <div class="row">
                        <div class="col-md-12">
                            <label for="banner_id" class="fg-label">Banner</label>
                        </div>
                        <div class="col-md-12">
                            <select v-model="bannerId" class="selectpicker" data-live-search="true" name="banner_id">
                                <option v-for="banner in banners" v-bind:value="banner.id">
                                    @{{ banner.name }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="input-group m-t-30">
                <span class="input-group-addon"><i class="zmdi zmdi-accounts-list"></i></span>
                <div class="row">
                    <div class="col-md-12">
                        <label for="segment_id" class="fg-label">Segment</label>
                    </div>
                    <div class="col-md-12">
                        <select v-model="segmentId" class="selectpicker" data-live-search="true" name="segment_id">
                            <optgroup v-for="(list,label) in segments" v-bind:label="label">
                                <option v-for="(name,code) in list" v-bind:value="code">
                                    @{{ name }}
                                </option>
                            </optgroup>
                        </select>
                    </div>
                </div>
            </div>

            <div class="input-group fg-float m-t-30 checkbox">
                <label class="m-l-15">
                    Activate
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
            <h4>Display rules</h4>

            <div v-on:click="addRule" class="btn btn-info waves-effect"><i class="zmdi zmdi-mail-send"></i> Add rule</div>

            <input v-model="removedRules" name="removedRules[]" type="hidden" required />

            <div class="row m-t-10">
                <div class="col-md-6" v-for="(rule,i) in rules">
                    <div class="card">
                        <div class="card-body card-padding-sm">
                            <input v-model="rule.id" :name="'rules['+i+'][id]'" type="hidden" required>

                            <div class="input-group ">
                                <span class="input-group-addon"><i class="zmdi zmdi-refresh"></i></span>
                                <div class="fg-line">
                                    <label for="count" class="fg-label">Count</label>
                                    <input v-model="rule.count" :name="'rules['+i+'][count]'"  class="form-control fg-input" title="count" type="text" required />
                                </div>
                            </div>

                            <div class="input-group m-t-10">
                                <span class="input-group-addon"><i class="zmdi zmdi-badge-check"></i></span>
                                <div class="row">
                                    <div class="col-md-12">
                                        <label for="segment_id" class="fg-label">Event</label>
                                    </div>
                                    <div class="col-md-12">
                                        <v-select v-model="rule.event" :name="'rules['+i+'][event]'" :value="rule.event" class="col-md-12 p-l-0 p-r-0" :options="eventTypes"></v-select>
                                    </div>
                                </div>
                            </div>

                            <div class="input-group m-t-10">
                                <span class="input-group-addon"><i class="zmdi zmdi-time-interval"></i></span>
                                <div class="fg-line">
                                    <label for="count" class="fg-label">Timespan</label>
                                    <input v-model="rule.timespan" :name="'rules['+i+'][timespan]'" class="form-control fg-input"title="timespan" type="number">
                                </div>
                            </div>

                            <div class="text-right">
                                <span v-on:click="removeRule(i)" class="btn btn-sm palette-Red bg waves-effect m-t-10"><i class="zmdi zmdi-mail-send"></i> Remove</span>
                            </div>

                        </div>
                    </div>
                </div>
            </div>


        </div>
    </div>
</template>