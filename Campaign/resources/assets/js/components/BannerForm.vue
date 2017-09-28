<style type="text/css">
    .cp-value {
        cursor: pointer;
    }
</style>

<template>
    <div class="row">
        <div class="col-md-4">
            <html-template v-if="template == 'html'"
                v-bind:_backgroundColor="htmlTemplate.backgroundColor"
                v-bind:_text="htmlTemplate.text"
                v-bind:_textColor="htmlTemplate.textColor"
                v-bind:_fontSize="htmlTemplate.fontSize"
                v-bind:_textAlign="htmlTemplate.textAlign"
                v-bind:_dimensions="htmlTemplate.dimensions"
                v-bind:alignmentOptions="alignmentOptions"
                v-bind:dimensionOptions="dimensionOptions"
            ></html-template>

            <medium-rectangle-template v-if="template == 'medium_rectangle'"
               v-bind:_headerText="mediumRectangleTemplate.headerText"
               v-bind:_mainText="mediumRectangleTemplate.mainText"
               v-bind:_buttonText="mediumRectangleTemplate.buttonText"
               v-bind:_backgroundColor="mediumRectangleTemplate.backgroundColor"
               v-bind:_textColor="mediumRectangleTemplate.textColor"
               v-bind:_buttonBackgroundColor="mediumRectangleTemplate.buttonBackgroundColor"
               v-bind:_buttonTextColor="mediumRectangleTemplate.buttonTextColor"
            ></medium-rectangle-template>

            <bar-template v-if="template == 'bar'"
               v-bind:_mainText="barTemplate.mainText"
               v-bind:_buttonText="barTemplate.buttonText"
               v-bind:_backgroundColor="barTemplate.backgroundColor"
               v-bind:_textColor="barTemplate.textColor"
               v-bind:_buttonBackgroundColor="barTemplate.buttonBackgroundColor"
               v-bind:_buttonTextColor="barTemplate.buttonTextColor"
            ></bar-template>

            <ul class="tab-nav m-t-30" role="tablist" data-tab-color="teal">
                <li class="active">
                    <a href="#settings" role="tab" data-toggle="tab" aria-expanded="true">Settings</a>
                </li>
            </ul>

            <div class="card m-t-15">
                <div class="tab-content p-0">
                    <div role="tabpanel" class="active tab-pane" id="settings">
                        <div class="card-body card-padding p-l-15">
                            <div class="input-group fg-float">
                                <span class="input-group-addon"><i class="zmdi zmdi-account"></i></span>
                                <div class="fg-line">
                                    <label for="name" class="fg-label">Name</label>
                                    <input v-model="name" class="form-control fg-input" name="name" id="name" type="text">
                                </div>
                            </div>

                            <div class="input-group">
                                <span class="input-group-addon"><i class="zmdi zmdi-swap-alt"></i></span>
                                <div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <label for="transition" class="fg-label">Transition</label>
                                        </div>
                                        <div class="col-md-12">
                                            <select v-model="transition" class="selectpicker" name="transition" id="transition">
                                                <option value="none">None</option>
                                                <option value="fade">Fade</option>
                                                <option value="bounce">Bounce</option>
                                                <option value="shake">Shake</option>
                                                <option value="fade-in-down">Fade in down</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="input-group fg-float">
                                <span class="input-group-addon"><i class="zmdi zmdi-link"></i></span>
                                <div class="fg-line">
                                    <label for="target_url" class="fg-label">Target URL</label>
                                    <input v-model="targetUrl" class="form-control fg-input" name="target_url" type="text" id="target_url">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <ul class="tab-nav m-t-30" role="tablist" data-tab-color="teal">
                <li v-on:click="displayType='overlay'" v-bind:class="{active: displayType == 'overlay'}">
                    <a href="#overlay-banner" role="tab" data-toggle="tab" aria-expanded="true">Overlay Banner</a>
                </li>
                <li v-on:click="displayType='inline'" v-bind:class="{active: displayType == 'inline'}">
                    <a href="#inline-banner" role="tab" data-toggle="tab" aria-expanded="false">Inline Banner</a>
                </li>
            </ul>

            <div class="card m-t-15">
                <div class="tab-content p-0">
                    <div role="tabpanel" v-bind:class="[{active: displayType == 'overlay'}, 'tab-pane']" id="overlay-banner">
                        <div class="card-body card-padding p-l-15">
                            <div class="input-group">
                                <span class="input-group-addon"><i class="zmdi zmdi-photo-size-select-large"></i></span>
                                <div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <label for="position" class="fg-label">Position</label>
                                        </div>
                                        <div class="col-md-12">
                                            <select v-model.lazy="position" class="selectpicker" name="position" id="position">
                                                <option v-for="option in positionOptions" v-bind:value="option.key">
                                                    {{ option.name }}
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="input-group fg-float">
                                <span class="input-group-addon"><i class="zmdi zmdi-timer"></i></span>
                                <div class="fg-line">
                                    <label for="display_delay" class="fg-label">Display delay (milliseconds)</label>
                                    <input v-model="displayDelay" class="form-control fg-input" name="display_delay" type="number" id="display_delay">
                                </div>
                            </div>

                            <div class="input-group fg-float">
                                <span class="input-group-addon"><i class="zmdi zmdi-time-interval"></i></span>
                                <div class="fg-line">
                                    <label for="automatic_close" class="fg-label">Automatic close after (milliseconds)</label>
                                    <input v-model="closeTimeout" class="form-control fg-input" name="close_timeout" type="number" id="automatic_close">
                                </div>
                            </div>

                            <div class="input-group fg-float checkbox">
                                <label class="m-l-15">
                                    Ability to close banner manually
                                    <input v-model="closeable" value="1" name="closeable" type="checkbox">
                                    <i class="input-helper"></i>
                                </label>
                            </div>

                        </div>
                    </div>

                    <div role="tabpanel" v-bind:class="[{active: displayType == 'inline'}, 'tab-pane']" id="inline-banner">
                        <div class="card-body card-padding p-l-15">
                            <div class="input-group fg-float m-t-10">
                                <span class="input-group-addon"><i class="zmdi zmdi-filter-center-focus"></i></span>
                                <div class="fg-line">
                                    <label for="target_selector" class="fg-label">Target element selector</label>
                                    <input v-model="targetSelector" class="form-control fg-input" name="target_selector" type="text" id="target_selector">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="input-group m-t-20">
                <div class="fg-line">
                    <button class="btn btn-info waves-effect" type="submit"><i class="zmdi zmdi-mail-send"></i> Save</button>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <ul class="tab-nav" role="tablist" data-tab-color="teal">
                <li class="active">
                    <a href="#preview" role="tab" data-toggle="tab" aria-expanded="true">Preview</a>
                </li>
                <li class="pull-right">
                    <button type="button" class="btn btn-default" v-on:click="previewShow = !previewShow">Toggle banner</button>
                </li>
            </ul>

            <div class="card m-t-15">
                <div class="tab-content p-0">
                    <div role="tabpanel" class="active tab-pane" id="preview">
                        <div class="card-body" id="banner-preview">
                            <div class="p-relative" style="height: 800px">
                                <banner-preview
                                        :alignmentOptions="alignmentOptions"
                                        :dimensionOptions="dimensionOptions"
                                        :positionOptions="positionOptions"
                                        :show="previewShow"
                                        :template="template"

                                        :mediumRectangleTemplate="mediumRectangleTemplate"
                                        :barTemplate="barTemplate"
                                        :htmlTemplate="htmlTemplate"

                                        :position="position"
                                        :targetUrl="targetUrl"
                                        :closeable="closeable"
                                        :transition="transition"
                                        :displayType="displayType"
                                        :forcedPosition="'absolute'"
                                ></banner-preview>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <input type="hidden" name="display_type" v-bind:value="displayType" />
    </div>
</template>


<script>
    import HtmlTemplate from "./templates/Html.vue"
    import MediumRectangleTemplate from "./templates/MediumRectangle.vue";
    import BarTemplate from "./templates/Bar.vue";
    import BannerPreview from "./BannerPreview.vue";

    const props = [
        "_name",
        "_targetUrl",
        "_position",
        "_transition",
        "_closeable",
        "_displayDelay",
        "_closeTimeout",
        "_targetSelector",
        "_displayType",
        "_template",

        "_mediumRectangleTemplate",
        "_barTemplate",
        "_htmlTemplate",

        "_alignmentOptions",
        "_dimensionOptions",
        "_positionOptions",
    ];

    export default {
        components: {
            HtmlTemplate,
            MediumRectangleTemplate,
            BarTemplate,
            BannerPreview,
        },
        name: 'banner-form',
        props: props,
        created: function() {
            this.$on('values-changed', function(data) {
                for (let item of data) {
                    this[item.key] = item.val;
                }
            });
        },
        mounted: function(){
            props.forEach((prop) => {
                this[prop.slice(1)] = this[prop];
            });
        },
        data: () => ({
            name: null,
            targetUrl: null,
            position: null,
            transition: null,
            closeable: null,
            displayDelay: null,
            closeTimeout: null,
            targetSelector: null,
            displayType: null,
            template: null,

            mediumRectangleTemplate: null,
            barTemplate: null,
            htmlTemplate: null,

            alignmentOptions: [],
            dimensionOptions: [],
            positionOptions: [],
            previewShow: true,
        }),
        watch: {
            'transition': function () {
                let vm = this;
                setTimeout(function() { vm.previewShow = false }, 100);
                setTimeout(function() { vm.previewShow = true }, 800);
            }
        },
    }
</script>