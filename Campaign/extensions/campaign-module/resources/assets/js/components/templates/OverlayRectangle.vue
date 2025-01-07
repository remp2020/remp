<style type="text/css">
    span.color {
        width: 20px;
        height: 20px;
        border-radius: 2px;
        position: absolute;
        right: 45px;
    }
</style>

<template>
    <div>
        <ul class="tab-nav" role="tablist" data-tab-color="teal">
            <li class="active">
                <a href="#html-template" role="tab" data-toggle="tab" aria-expanded="true">Overlay rectangle template</a>
            </li>
        </ul>

        <div class="card m-t-15">
            <div class="tab-content p-0">
                <div role="tabpanel" class="active tab-pane" id="html-template">
                    <div class="card-body card-padding p-l-15">
                        <div class="input-group">
                            <span class="input-group-addon"><i class="zmdi zmdi-swap-alt"></i></span>
                            <div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <label for="color_scheme" class="fg-label">Color scheme</label>
                                    </div>
                                    <div class="col-md-12">
                                        <v-select v-model="colorScheme"
                                                  name="color_scheme"
                                                  id="color_scheme"
                                                  :value="colorScheme"
                                                  :options.sync="colorSchemeOptions"
                                                  :required="true"
                                        ></v-select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="input-group v-select">
                            <span class="input-group-addon"><i class="zmdi zmdi-swap-alt"></i></span>
                            <div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <label for="color_scheme" class="fg-label">Dimensions</label>
                                    </div>
                                    <div class="col-md-12">
                                        <v-select v-model="dimensions"
                                                id="dimensions"
                                                :value="dimensions"
                                                :options.sync="dimensionOptions"
                                                :required="true"
                                        ></v-select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="input-group fg-float">
                            <span class="input-group-addon"><i class="zmdi zmdi-link"></i></span>
                            <div class="fg-line">
                                <label for="image_link" class="fg-label">Image link</label>
                                <input v-model="imageLink" class="form-control fg-input" name="image_link" id="image_link" type="text">
                            </div>
                        </div>

                        <input type="hidden" name="width" v-bind:value="_width" />
                        <input type="hidden" name="height" v-bind:value="_height" />

                        <div class="input-group fg-float m-t-30">
                            <span class="input-group-addon"><i class="zmdi zmdi-text-format"></i></span>
                            <div class="fg-line">
                                <label for="header_text" class="fg-label">Header text</label>
                                <input v-model="headerText" class="form-control fg-input remp-banner-text-input" name="header_text" id="header_text" type="text">
                            </div>
                            <small class="help-block" v-pre>You can use <i class="zmdi zmdi-code"></i> Snippets in this field as <code>{{&nbsp;snippet_name&nbsp;}}</code>.</small>
                        </div>

                        <div class="input-group fg-float m-t-30">
                            <span class="input-group-addon"><i class="zmdi zmdi-text-format"></i></span>
                            <div class="fg-line">
                                <label for="main_text" class="fg-label">Main text</label>
                                <input v-model="mainText" class="form-control fg-input remp-banner-text-input" name="main_text" id="main_text" type="text">
                            </div>
                            <div class="help-block">
                                <small v-html="$parent.fieldParamsMessage"></small><br>
                                <small v-pre>You can use <i class="zmdi zmdi-code"></i> Snippets in this field as <code>{{&nbsp;snippet_name&nbsp;}}</code>.</small>
                            </div>
                        </div>

                        <div class="input-group fg-float m-t-30">
                            <span class="input-group-addon"><i class="zmdi zmdi-text-format"></i></span>
                            <div class="fg-line">
                                <label for="button_text" class="fg-label">Button text</label>
                                <input v-model="buttonText" class="form-control fg-input remp-banner-text-input" name="button_text" id="button_text" type="text">
                            </div>
                            <small class="help-block" v-pre>You can use <i class="zmdi zmdi-code"></i> Snippets in this field as <code>{{&nbsp;snippet_name&nbsp;}}</code>.</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <input type="hidden" name="template" value="overlay_rectangle" />
    </div>
</template>

<script type="text/javascript">
    import vSelect from "@remp/js-commons/js/components/vSelect";

    let props = [
        "_templateId",
        "_headerText",
        "_mainText",
        "_buttonText",
        "_colorSchemes",
        "_colorScheme",
        "_width",
        "_height",
        "_imageLink",
    ];
    export default {
        name: "overlay-rectangle-template",
        components: { vSelect },
        props: props,
        created: function(){
            props.forEach((prop) => {
                this[prop.slice(1)] = this[prop] || this[prop.slice(1)];
            });

            if (!this.templateId) {
                this.mainText = "Limited time offer<br/>30% discount";
                this.buttonText = "Visit offer"
            }

            this.dimensions = this.dimensionsByWidthHeight(this._width, this._height) || this.dimensions;
            this.emitValuesChanged();
        },
        data: () => ({
            templateId: null,
            headerText: null,
            mainText: null,
            buttonText: null,
            width: null,
            height: null,
            imageLink: null,

            colorScheme: "green",

            dimensions: "dynamic",
            availableDimensions: {
                "dynamic": {
                    "label": "Dynamic",
                    "width": null,
                    "height": null,
                },
                "300x600": {
                    "label": "300x600",
                    "width": "300px",
                    "height": "600px",
                },
            }
        }),
        computed: {
            colorSchemeOptions: function() {
                let options = [];
                for (let idx in this.colorSchemes) {
                    if (!this.colorSchemes.hasOwnProperty(idx)) {
                        continue;
                    }
                    options.push({
                        "label": this.colorSchemes[idx].label,
                        "sublabel": '<span class="color" style="background-color: ' + this.colorSchemes[idx].backgroundColor + '">',
                        "value": idx,
                    });
                }
                return options
            },
            dimensionOptions: function() {
                let options = [];
                for (let idx in this.availableDimensions) {
                    if (!this.availableDimensions.hasOwnProperty(idx)) {
                        continue;
                    }
                    options.push({
                        "label": this.availableDimensions[idx].label,
                        "value": idx,
                    });
                }
                return options
            }
        },
        updated: function() {
           this.emitValuesChanged();
        },
        methods: {
            emitValuesChanged: function() {
                let val = {
                    headerText: this.headerText,
                    mainText: this.mainText,
                    buttonText: this.buttonText,
                    imageLink: this.imageLink,
                    colorScheme: this.colorScheme,
                };
                if (this.availableDimensions[this.dimensions]) {
                    let d = this.availableDimensions[this.dimensions];
                    val.width = d.width;
                    val.height = d.height;
                }
                this.$parent.$emit("values-changed", [{
                    key: "overlayRectangleTemplate",
                    val: val,
                }]);
            },
            dimensionsByWidthHeight: function(width, height) {
                for (let idx in this.availableDimensions) {
                    if (!this.availableDimensions.hasOwnProperty(idx)) {
                        continue;
                    }
                    if (this.availableDimensions[idx].width === width && this.availableDimensions[idx].width === height) {
                        return idx;
                    }
                }
                return null;
            },
        }
    }
</script>
