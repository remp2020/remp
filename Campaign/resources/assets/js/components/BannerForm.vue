<style type="text/css">
    .cp-value {
        cursor: pointer;
    }
</style>

<template>
    <div class="row">
        <div class="col-md-4">
            <html-template v-if="template === 'html'"
                v-bind:_backgroundColor="htmlTemplate.backgroundColor"
                v-bind:_text="htmlTemplate.text"
                v-bind:_css="htmlTemplate.css"
                v-bind:_textColor="htmlTemplate.textColor"
                v-bind:_fontSize="htmlTemplate.fontSize"
                v-bind:_textAlign="htmlTemplate.textAlign"
                v-bind:_dimensions="htmlTemplate.dimensions"
                v-bind:alignmentOptions="alignmentOptions"
                v-bind:dimensionOptions="dimensionOptions"
                v-bind:show="show"
            ></html-template>

            <medium-rectangle-template v-if="template === 'medium_rectangle'"
               v-bind:_headerText="mediumRectangleTemplate.headerText"
               v-bind:_mainText="mediumRectangleTemplate.mainText"
               v-bind:_buttonText="mediumRectangleTemplate.buttonText"
               v-bind:_width="mediumRectangleTemplate.height"
               v-bind:_height="mediumRectangleTemplate.width"
               v-bind:_backgroundColor="mediumRectangleTemplate.backgroundColor"
               v-bind:_textColor="mediumRectangleTemplate.textColor"
               v-bind:_buttonBackgroundColor="mediumRectangleTemplate.buttonBackgroundColor"
               v-bind:_buttonTextColor="mediumRectangleTemplate.buttonTextColor"
               v-bind:show="show"
            ></medium-rectangle-template>

            <overlay-rectangle-template v-if="template === 'overlay_rectangle'"
               v-bind:_templateId="overlayRectangleTemplate.id"
               v-bind:_headerText="overlayRectangleTemplate.headerText"
               v-bind:_mainText="overlayRectangleTemplate.mainText"
               v-bind:_buttonText="overlayRectangleTemplate.buttonText"
               v-bind:_width="overlayRectangleTemplate.height"
               v-bind:_height="overlayRectangleTemplate.width"
               v-bind:_backgroundColor="overlayRectangleTemplate.backgroundColor"
               v-bind:_textColor="overlayRectangleTemplate.textColor"
               v-bind:_buttonBackgroundColor="overlayRectangleTemplate.buttonBackgroundColor"
               v-bind:_buttonTextColor="overlayRectangleTemplate.buttonTextColor"
               v-bind:_imageLink="overlayRectangleTemplate.imageLink"
               v-bind:show="show"
            ></overlay-rectangle-template>

            <html-overlay-template v-if="template === 'html_overlay'"
                v-bind:_backgroundColor="htmlOverlayTemplate.backgroundColor"
                v-bind:_text="htmlOverlayTemplate.text"
                v-bind:_css="htmlOverlayTemplate.css"
                v-bind:_textColor="htmlOverlayTemplate.textColor"
                v-bind:_fontSize="htmlOverlayTemplate.fontSize"
                v-bind:_textAlign="htmlOverlayTemplate.textAlign"
                v-bind:alignmentOptions="alignmentOptions"
                v-bind:show="show"
            ></html-overlay-template>

            <bar-template v-if="template === 'bar'"
               v-bind:_mainText="barTemplate.mainText"
               v-bind:_buttonText="barTemplate.buttonText"
               v-bind:_backgroundColor="barTemplate.backgroundColor"
               v-bind:_textColor="barTemplate.textColor"
               v-bind:_buttonBackgroundColor="barTemplate.buttonBackgroundColor"
               v-bind:_buttonTextColor="barTemplate.buttonTextColor"
               v-bind:show="show"
            ></bar-template>

            <collapsible-bar-template v-if="template === 'collapsible_bar'"
               v-bind:_mainText="collapsibleBarTemplate.mainText"
               v-bind:_buttonText="collapsibleBarTemplate.buttonText"
               v-bind:_headerText="collapsibleBarTemplate.headerText"
               v-bind:_collapseText="collapsibleBarTemplate.collapseText"
               v-bind:_expandText="collapsibleBarTemplate.expandText"
               v-bind:_backgroundColor="collapsibleBarTemplate.backgroundColor"
               v-bind:_textColor="collapsibleBarTemplate.textColor"
               v-bind:_buttonBackgroundColor="collapsibleBarTemplate.buttonBackgroundColor"
               v-bind:_buttonTextColor="collapsibleBarTemplate.buttonTextColor"
               v-bind:_initialState="collapsibleBarTemplate.initialState"
               v-bind:show="show"
            ></collapsible-bar-template>

            <short-message-template v-if="template === 'short_message'"
              v-bind:_text="shortMessageTemplate.text"
              v-bind:_backgroundColor="shortMessageTemplate.backgroundColor"
              v-bind:_textColor="shortMessageTemplate.textColor"
              v-bind:show="show"
            ></short-message-template>

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

                            <div class="input-group" :class="{ hidden: collapsibleBarTemplate != null }">
                                <span class="input-group-addon"><i class="zmdi zmdi-swap-alt"></i></span>
                                <div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <label class="fg-label">Transition</label>
                                        </div>
                                        <div class="col-md-12">
                                            <v-select v-model="transition"
                                                    id="transition"
                                                    :name="'transition'"
                                                    :value="transition"
                                                    :options.sync="transitionOptions"
                                            ></v-select>
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
                                <div><small>UTM params will be automatically appended to this link.</small></div>
                            </div>

                            <div class="input-group fg-float m-t-30">
                                <span class="input-group-addon"><i class="zmdi zmdi-format-subject"></i></span>
                                <div class="fg-line">
                                    <label for="js" class="fg-label">Custom JS</label>
                                    <textarea v-model="js" class="form-control fg-input" rows="6" name="js" cols="50" id="js"></textarea>
                                </div>
                            </div><!-- .input-group -->

                            <div class="input-group fg-float m-t-30">
                                <span class="input-group-addon"><i class="zmdi zmdi-format-subject"></i></span>
                                <div class="fg-line">
                                    <label for="jsIncludesStr" class="fg-label">Javascript includes</label>
                                    <textarea v-model="jsIncludesStr" class="form-control fg-input" rows="6" name="jsIncludesStr" cols="50" id="jsIncludesStr"></textarea>
                                </div>
                                <div style="margin-top: 5px;">Enter include script urls separated by new line.</div>

                                <div v-if="jsIncludes">
                                    <input v-for="jsInclude in jsIncludes" type="hidden" name="js_includes[]" :value="jsInclude">
                                </div>
                                <input v-else type="hidden" name="js_includes[]">
                            </div><!-- .input-group -->

                            <div class="input-group fg-float m-t-30">
                                <span class="input-group-addon"><i class="zmdi zmdi-format-subject"></i></span>
                                <div class="fg-line">
                                    <label for="cssIncludesStr" class="fg-label">Stylesheet includes</label>
                                    <textarea v-model="cssIncludesStr" class="form-control fg-input" rows="6" name="cssIncludesStr" cols="50" id="cssIncludesStr"></textarea>
                                </div>
                                <div style="margin-top: 5px;">Enter include stylesheets urls separated by new line.</div>

                                <div v-if="cssIncludes">
                                    <input v-for="cssInclude in cssIncludes" type="hidden" name="css_includes[]" :value="cssInclude">
                                </div>
                                <input v-else type="hidden" name="css_includes[]">
                            </div><!-- .input-group -->

                        </div>
                    </div>
                </div>
            </div>

            <ul class="tab-nav m-t-30" role="tablist" data-tab-color="teal" :class="{ hidden: collapsibleBarTemplate != null }">
                <li v-on:click="displayType='overlay'" v-bind:class="{active: displayType === 'overlay'}">
                    <a href="#overlay-banner" role="tab" data-toggle="tab" aria-expanded="true">Overlay Banner</a>
                </li>
                <li v-on:click="displayType='inline'" v-bind:class="{active: displayType === 'inline'}" v-if="isOverlay">
                    <a href="#inline-banner" role="tab" data-toggle="tab" aria-expanded="false">Inline Banner</a>
                </li>
            </ul>

            <div class="card m-t-15" :class="{ hidden: collapsibleBarTemplate != null }">
                <div class="tab-content p-0">
                    <div role="tabpanel" v-bind:class="[{active: displayType === 'overlay'}, 'tab-pane']" id="overlay-banner">
                        <div class="card-body card-padding p-l-15">

                            <div class="input-group" v-if="isOverlay">
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
                            </div><!-- .input-group -->

                            <div class="input-group fg-float" v-if="isOverlay">
                                <span class="input-group-addon"><i class="zmdi zmdi-arrow-right"></i></span>

                                <div class="fg-line">
                                    <label for="offsetHorizontal" class="fg-label">Hortizontal offset (px)</label>
                                    <input v-model="offsetHorizontal" class="form-control fg-input" name="offset_horizontal" type="number" id="offsetHorizontal">
                                </div>
                            </div><!-- .input-group -->
                            <input v-else type="hidden" name="offset_horizontal" value="0">

                            <div class="input-group fg-float" v-if="isOverlay">
                                <span class="input-group-addon"><i class="zmdi zmdi-long-arrow-down"></i></span>

                                <div class="fg-line">
                                    <label for="offsetVertical" class="fg-label">Vertical offset (px)</label>
                                    <input v-model="offsetVertical" class="form-control fg-input" name="offset_vertical" type="number" id="offsetVertical">
                                </div>
                            </div><!-- .input-group -->
                            <input v-else type="hidden" name="offset_vertical" value="0">

                            <div class="input-group fg-float">
                                <span class="input-group-addon"><i class="zmdi zmdi-timer"></i></span>
                                <div class="fg-line">
                                    <label for="display_delay" class="fg-label">Display delay (milliseconds)</label>
                                    <input v-model="displayDelay" class="form-control fg-input" name="display_delay" type="number" id="display_delay">
                                </div>
                            </div><!-- .input-group -->

                            <div class="input-group fg-float">
                                <span class="input-group-addon"><i class="zmdi zmdi-time-interval"></i></span>
                                <div class="fg-line">
                                    <label for="automatic_close" class="fg-label">Automatic close after (milliseconds)</label>
                                    <input v-model="closeTimeout" class="form-control fg-input" name="close_timeout" type="number" id="automatic_close">
                                </div>
                            </div><!-- .input-group -->

                            <div class="input-group fg-float checkbox">
                                <label class="m-l-15">
                                    Ability to close banner manually
                                    <input v-model="closeable" value="1" name="closeable" type="checkbox">
                                    <i class="input-helper"></i>
                                </label>
                            </div><!-- .input-group -->

                            <div class="input-group fg-float" v-if="closeable">
                                <span class="input-group-addon"><i class="zmdi zmdi-close-circle-o"></i></span>
                                <div class="fg-line">
                                    <label for="close_text" class="fg-label">Close Text</label>
                                    <input v-model="closeText" class="form-control fg-input" name="close_text" type="text" id="close_text">
                                </div>
                            </div><!-- .input-group -->

                        </div>
                    </div>

                    <div role="tabpanel" v-bind:class="[{active: displayType === 'inline'}, 'tab-pane']" id="inline-banner" v-if="isOverlay">
                        <div class="card-body card-padding p-l-15">
                            <div class="input-group fg-float m-t-10">
                                <span class="input-group-addon"><i class="zmdi zmdi-filter-center-focus"></i></span>
                                <div class="row">
                                    <div class="col-xs-10">
                                        <div class="fg-line" :class="{'fg-toggled': targetSelector && targetSelector.length}">
                                            <label for="target_selector" class="fg-label">Choose position</label>
                                            <input v-model="targetSelector" class="form-control fg-input" name="target_selector" type="text" id="target_selector">
                                        </div>
                                    </div>
                                    <div class="col-xs-2">
                                        <button class="btn btn-primary waves-effect" :class="{'disabled': !clientSiteUrl}" type="button" @click="openClientSiteAndSendKeepAliveMessages()">
                                            <i class="zmdi zmdi-my-location"></i>
                                        </button>
                                    </div>
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

        <div class="col-md-8">
            <ul class="tab-nav" role="tablist" data-tab-color="teal">
                <li class="active">
                    <a href="#preview" role="tab" data-toggle="tab" aria-expanded="true">Preview</a>
                </li>
                <li class="pull-right">
                    <button type="button" class="btn btn-default" v-on:click="show = !show">Toggle banner</button>
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
                                        :show="show"
                                        :template="template"

                                        :mediumRectangleTemplate="mediumRectangleTemplate"
                                        :overlayRectangleTemplate="overlayRectangleTemplate"
                                        :barTemplate="barTemplate"
                                        :collapsibleBarTemplate="collapsibleBarTemplate"
                                        :htmlTemplate="htmlTemplate"
                                        :htmlOverlayTemplate="htmlOverlayTemplate"
                                        :shortMessageTemplate="shortMessageTemplate"

                                        :position="position"
                                        :offsetVertical="offsetVertical"
                                        :offsetHorizontal="offsetHorizontal"
                                        :targetUrl="targetUrl"
                                        :closeable="closeable"
                                        :closeText="closeText"
                                        :transition="transition"
                                        :displayType="displayType"
                                        :forcedPosition="'absolute'"

                                        :js="js"
                                        :jsIncludes="jsIncludes"
                                        :cssIncludes="cssIncludes"
                                ></banner-preview>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <input type="hidden" name="display_type" v-bind:value="displayType" />

        <form-validator :url="validateUrl"></form-validator>
    </div>
</template>


<script>
    import HtmlTemplate from "./templates/Html"
    import MediumRectangleTemplate from "./templates/MediumRectangle";
    import BarTemplate from "./templates/Bar";
    import CollapsibleBarTemplate from "./templates/CollapsibleBar";
    import ShortMessageTemplate from "./templates/ShortMessage";
    import OverlayRectangleTemplate from "./templates/OverlayRectangle";
    import BannerPreview from "./BannerPreview";
    import vSelect from "remp/js/components/vSelect";
    import FormValidator from "remp/js/components/FormValidator";
    import HtmlOverlayTemplate from "./templates/HtmlOverlay";

    const props = {
        "_name": String,
        "_targetUrl": String,
        "_position": String,
        "_offsetVertical": Number,
        "_offsetHorizontal": Number,
        "_transition": {
            "type": String,
            "default": 'none'
        },
        "_closeable": Boolean,
        "_closeText": String,
        "_displayDelay": Number,
        "_closeTimeout": Number,
        "_targetSelector": String,
        "_displayType": String,
        "_template": String,

        "_mediumRectangleTemplate": Object,
        "_barTemplate": Object,
        "_collapsibleBarTemplate": Object,
        "_htmlTemplate": Object,
        "_htmlOverlayTemplate": Object,
        "_shortMessageTemplate": Object,
        "_overlayRectangleTemplate": Object,

        "_alignmentOptions": Object,
        "_dimensionOptions": Object,
        "_positionOptions": Object,

        "_validateUrl": String,
        "_clientSiteUrl": String,

        "_js": String,
        "_jsIncludes": Array,
        "_cssIncludes": Array
    };

    export default {
        components: {
            HtmlOverlayTemplate,
            HtmlTemplate,
            MediumRectangleTemplate,
            BarTemplate,
            CollapsibleBarTemplate,
            ShortMessageTemplate,
            BannerPreview,
            vSelect,
            FormValidator,
            OverlayRectangleTemplate
        },
        name: 'banner-form',
        props: props,
        created: function() {
            for (var key in props) {
                this[key.slice(1)] = this.$props[key];
            }

            this.$on('values-changed', function(data) {
                for (let item of data) {
                    this[item.key] = item.val;
                }
            });
            window.addEventListener("message", this.receiveSelector, false);
        },
        data: () => ({
            name: null,
            targetUrl: null,
            position: null,
            offsetVertical: null,
            offsetHorizontal: null,
            transition: null,
            closeable: null,
            closeText: null,
            displayDelay: null,
            closeTimeout: null,
            targetSelector: null,
            displayType: null,
            template: null,

            mediumRectangleTemplate: null,
            barTemplate: null,
            collapsibleBarTemplate: null,
            htmlTemplate: null,
            htmlOverlayTemplate: null,
            shortMessageTemplate: null,
            overlayRectangleTemplate: null,

            alignmentOptions: [],
            dimensionOptions: [],
            positionOptions: [],
            show: true,

            submitAction: null,

            transitionOptions: [
                {"label": "None", "value": "none"},
                {"label": "Fade", "value": "fade"},
                {"label": "Bounce", "value": "bounce"},
                {"label": "Shake", "value": "shake"},
                {"label": "Fade in down", "value": "fade-in-down"},
            ],

            validateUrl: null,
            clientSiteUrl: null,

            js: null,
            jsIncludes: null,
            cssIncludes: null,

            fieldParamsMessage: "UTM params will be automatically appended to every link in this field.<br> If you want to add custom parameter to specific link: add data-param-* attribute. e.g.: data-param-foo=\"baz\""
        }),
        computed: {
            isOverlay: function() {
                return this.overlayRectangleTemplate == null && this.htmlOverlayTemplate == null;
            },
            jsIncludesStr: {
                get: function () {
                    return this.jsIncludes ? this.jsIncludes.join("\n") : null;
                },
                set: function (value) {
                    this.jsIncludes = value ? value.split("\n") : null;
                }
            },
            cssIncludesStr: {
                get: function () {
                    return this.cssIncludes ? this.cssIncludes.join("\n") : null;
                },
                set: function (value) {
                    this.cssIncludes = value ? value.split("\n") : null;
                }
            }
        },
        methods: {
            openClientSiteAndSendKeepAliveMessages() {
                if(!this.clientSiteUrl.length) {
                    alert('In order to use interactive selector, please specify a CLIENT_SITE_URL in your .env file');
                    return;
                }

                var clientSiteInstance = window.open(this.clientSiteUrl + '#bannerPicker');

                setInterval(() => {
                    clientSiteInstance.postMessage('Keep alive', this.clientSiteUrl);
                }, 300);
            },
            receiveSelector(event) {
                if (typeof event.data.selector !== 'undefined' && event.data.selector.length) {
                    this.targetSelector = event.data.selector;
                }
            }
        }
    }
</script>
