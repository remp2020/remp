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

            <div class="input-group m-t-30">
                <span class="input-group-addon"><i class="zmdi zmdi-wallpaper"></i></span>
                <div>
                    <div class="row">
                        <div class="col-md-12">
                            <label for="banner_id" class="fg-label">Banner</label>
                        </div>
                        <div class="col-md-12">
                            <select v-model="bannerId" class="selectpicker" data-live-search="true" name="banner_id" id="banner_id">
                                <option v-for="banner in banners" v-bind:value="banner.id">
                                    {{ banner.name }}
                                </option>
                            </select>
                        </div>
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

        <div class="col-md-8">
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
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <tbody>
                            <tr v-for="(segment,i) in segments">
                                <td>{{ segmentMap[segment.code] }}</td>
                                <td class="text-right"><span v-on:click="removeSegment(i)" class="btn btn-sm bg palette-Red waves-effect"><i class="zmdi zmdi-minus-square"></i> Delete</span></td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
</template>

<script type="text/javascript">
    let props = [
        "_name",
        "_segments",
        "_bannerId",
        "_active",
        "_banners",
        "_availableSegments",
        "_addedSegment",
        "_removedSegments",
        "_segmentMap",
        "_eventTypes",
    ];
    export default {
        mounted: function(){
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
                "active": null,

                "banners": null,
                "availableSegments": null,
                "addedSegment": null,
                "removedSegments": [],
                "segmentMap": null,
                "eventTypes": null
            }
        },
        methods: {
            'selectSegment': function() {
                if (typeof this.addedSegment === 'undefined') {
                    return;
                }
                for (let i in this.segments) {
                    if (this.segments[i].id === this.addedSegment.id) {
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