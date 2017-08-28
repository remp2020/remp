<style type="text/css">
    .cp-value {
        cursor: pointer;
    }
</style>

<template>
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h2>Settings</h2>
                </div>
                <div class="card-body card-padding p-l-15">
                    <div class="input-group fg-float m-t-30">
                        <span class="input-group-addon"><i class="zmdi zmdi-account"></i></span>
                        <div class="fg-line">
                            <label for="name" class="fg-label">Name</label>
                            <input v-model="name" class="form-control fg-input" name="name" id="name" type="text">
                        </div>
                    </div>

                    <div class="cp-container">
                        <div class="input-group fg-float m-t-30">
                            <span class="input-group-addon"><i class="zmdi zmdi-format-color-text"></i></span>
                            <div class="fg-line dropdown">
                                <label for="text_color" class="fg-label">Text Color</label>
                                <input v-model="textColor" class="form-control cp-value" data-toggle="dropdown" name="text_color" id="text_color" type="text">

                                <div class="dropdown-menu">
                                    <div class="color-picker" data-cp-default="#03A9F4"></div>
                                </div>
                                <i class="cp-value"></i>
                            </div>
                        </div>
                    </div>

                    <div class="cp-container">
                        <div class="input-group fg-float m-t-30">
                            <span class="input-group-addon"><i class="zmdi zmdi-format-color-fill"></i></span>
                            <div class="fg-line dropdown">
                                <label for="background_color" class="fg-label">Background Color</label>
                                <input v-model="backgroundColor" class="form-control cp-value" data-toggle="dropdown" name="background_color" id="background_color" type="text">

                                <div class="dropdown-menu">
                                    <div class="color-picker"></div>
                                </div>
                                <i class="cp-value"></i>
                            </div>
                        </div>
                    </div>

                    <div class="input-group fg-float m-t-30">
                        <span class="input-group-addon"><i class="zmdi zmdi-format-subject"></i></span>
                        <div class="fg-line">
                            <label for="html_text" class="fg-label">HTML Text</label>
                            <textarea v-model="text" class="form-control fg-input" rows="3" name="text" cols="50" id="html_text"></textarea>
                        </div>
                    </div>

                    <div class="input-group fg-float m-t-30">
                        <span class="input-group-addon"><i class="zmdi zmdi-format-size"></i></span>
                        <div class="fg-line">
                            <label for="font_size" class="fg-label">Font Size</label>
                            <input v-model="fontSize" class="form-control fg-input" name="font_size" type="number" id="font_size">
                        </div>
                    </div>

                    <div class="input-group m-t-30">
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

                    <div class="input-group m-t-30">
                        <span class="input-group-addon"><i class="zmdi zmdi-swap"></i></span>
                        <div>
                            <div class="row">
                                <div class="col-md-12">
                                    <label for="text_align" class="fg-label">Text Alignment</label>
                                </div>
                                <div class="col-md-12">
                                    <select v-model="textAlign" class="selectpicker" name="text_align" id="text_align">
                                        <option v-for="option in alignmentOptions" v-bind:value="option.key">
                                            {{ option.name }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="input-group m-t-30">
                        <span class="input-group-addon"><i class="zmdi zmdi-aspect-ratio-alt"></i></span>
                        <div>
                            <div class="row">
                                <div class="col-md-12">
                                    <label for="dimensions" class="fg-label">Dimensions</label>
                                </div>
                                <div class="col-md-12">
                                    <select v-model.lazy="dimensions" class="selectpicker" name="dimensions" id="dimensions">
                                        <option v-for="option in dimensionOptions" v-bind:value="option.key">
                                            {{ option.name }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="input-group fg-float m-t-30">
                        <span class="input-group-addon"><i class="zmdi zmdi-link"></i></span>
                        <div class="fg-line">
                            <label for="target_url" class="fg-label">Target URL</label>
                            <input v-model="targetUrl" class="form-control fg-input" name="target_url" type="text" id="target_url">
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

                            <div class="input-group fg-float m-t-30">
                                <span class="input-group-addon"><i class="zmdi zmdi-timer"></i></span>
                                <div class="fg-line">
                                    <label for="display_delay" class="fg-label">Display delay (milliseconds)</label>
                                    <input v-model="displayDelay" class="form-control fg-input" name="display_delay" type="number" id="display_delay">
                                </div>
                            </div>

                            <div class="input-group fg-float m-t-30">
                                <span class="input-group-addon"><i class="zmdi zmdi-time-interval"></i></span>
                                <div class="fg-line">
                                    <label for="automatic_close" class="fg-label">Automatic close after (milliseconds)</label>
                                    <input v-model="closeTimeout" class="form-control fg-input" name="close_timeout" type="number" id="automatic_close">
                                </div>
                            </div>

                            <div class="input-group fg-float m-t-30 checkbox">
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
            <div class="card">
                <div class="card-header">
                    <h2>
                        Preview
                        <span class="btn btn-default m-l-20" v-on:click="previewShow = !previewShow">Toggle banner</span>
                    </h2>
                </div>
                <div class="card-body card-padding" id="banner-preview">
                    <div class="row p-relative" style="width: 560px; height: 700px">
                        <img src="../../img/website_mockup.png" class="preview-image" alt="Mockup" height="700px">
                        <banner-preview
                                v-bind:alignmentOptions="alignmentOptions"
                                v-bind:dimensionOptions="dimensionOptions"
                                v-bind:positionOptions="positionOptions"
                                v-bind:textAlign="textAlign"
                                v-bind:transition="transition"
                                v-bind:position="position"
                                v-bind:dimensions="dimensions"
                                v-bind:show="previewShow"
                                v-bind:textColor="textColor"
                                v-bind:fontSize="fontSize"
                                v-bind:backgroundColor="backgroundColor"
                                v-bind:targetUrl="targetUrl"
                                v-bind:closeable="closeable"
                                v-bind:text="text"
                                v-bind:displayType="displayType"
                                v-bind:forcedPosition="'absolute'"
                        ></banner-preview>
                    </div>
                </div>
            </div>
        </div>

        <input type="hidden" name="display_type" v-bind:value="displayType" />
    </div>
</template>


<script>
    import BannerPreview from "./BannerPreview.vue";

    const props = [
        "_name",
        "_dimensions",
        "_text",
        "_textAlign",
        "_fontSize",
        "_targetUrl",
        "_textColor",
        "_backgroundColor",
        "_position",
        "_transition",
        "_closeable",
        "_displayDelay",
        "_closeTimeout",
        "_targetSelector",
        "_displayType",

        "_alignmentOptions",
        "_dimensionOptions",
        "_positionOptions",
    ];

    export default {
        components: { BannerPreview },
        name: 'banner-form',
        props: props,
        mounted: function(){
            let self = this;
            props.forEach((prop) => {
                this[prop.slice(1)] = this[prop];
            });
        },
        data: () => ({
            name: null,
            dimensions: null,
            text: null,
            textAlign: null,
            fontSize: null,
            targetUrl: null,
            textColor: null,
            backgroundColor: null,
            position: null,
            transition: null,
            closeable: null,
            displayDelay: null,
            closeTimeout: null,
            targetSelector: null,
            displayType: null,

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