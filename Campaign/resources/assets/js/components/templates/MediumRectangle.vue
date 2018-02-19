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
                <a href="#html-template" role="tab" data-toggle="tab" aria-expanded="true">Medium rectangle template</a>
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

                        <input type="hidden" name="background_color" v-bind:value="_backgroundColor" />
                        <input type="hidden" name="text_color" v-bind:value="_textColor" />
                        <input type="hidden" name="button_background_color" v-bind:value="_buttonBackgroundColor" />
                        <input type="hidden" name="button_text_color" v-bind:value="_buttonTextColor" />
                        <input type="hidden" name="width" v-bind:value="_width" />
                        <input type="hidden" name="height" v-bind:value="_height" />

                        <div class="input-group fg-float m-t-30">
                            <span class="input-group-addon"><i class="zmdi zmdi-text-format"></i></span>
                            <div class="fg-line">
                                <label for="header_text" class="fg-label">Header text</label>
                                <input v-model="headerText" class="form-control fg-input" name="header_text" id="header_text" type="text">
                            </div>
                        </div>

                        <div class="input-group fg-float m-t-30">
                            <span class="input-group-addon"><i class="zmdi zmdi-text-format"></i></span>
                            <div class="fg-line">
                                <label for="main_text" class="fg-label">Main text</label>
                                <input v-model="mainText" class="form-control fg-input" name="main_text" id="main_text" type="text" requried>
                            </div>
                        </div>

                        <div class="input-group fg-float m-t-30">
                            <span class="input-group-addon"><i class="zmdi zmdi-text-format"></i></span>
                            <div class="fg-line">
                                <label for="button_text" class="fg-label">Button text</label>
                                <input v-model="buttonText" class="form-control fg-input" name="button_text" id="button_text" type="text" requried>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <input type="hidden" name="template" value="medium_rectangle" />
    </div>
</template>

<script type="text/javascript">
    let vSelect = require("remp/js/components/vSelect.vue");

    let props = [
        "_headerText",
        "_mainText",
        "_buttonText",
        "_backgroundColor",
        "_textColor",
        "_buttonBackgroundColor",
        "_buttonTextColor",
        "_width",
        "_height",
    ];
    export default {
        name: "medium-rectangle-template",
        components: { vSelect },
        props: props,
        created: function(){
            props.forEach((prop) => {
                this[prop.slice(1)] = this[prop] || this[prop.slice(1)];
            });
            this.colorScheme = this.colorSchemeByBackground(this._backgroundColor) || this.colorScheme;
            this.dimensions = this.dimensionsByWidthHeight(this._width, this._height) || this.dimensions;
            this.emitValuesChanged();
        },
        data: () => ({
            headerText: null,
            mainText: "Limited time offer<br/>30% discount",
            buttonText: "Visit offer",
            width: null,
            height: null,

            colorScheme: "green",
            colorSchemes: {
                "grey": {
                    "label": "Grey",
                    "textColor": "#000000", "backgroundColor": "#ededed",
                    "buttonTextColor": "#ffffff", "buttonBackgroundColor": "#000000",
                },
                "yellow": {
                    "label": "Yellow",
                    "backgroundColor": "#f7bc1e", "textColor": "#000000",
                    "buttonTextColor": "#ffffff", "buttonBackgroundColor": "#000000",
                },
                "blue": {
                    "label": "Blue",
                    "textColor": "#ffffff", "backgroundColor": "#00b7db",
                    "buttonTextColor": "#ffffff", "buttonBackgroundColor": "#000000",
                },
                "green": {
                    "label": "Green",
                    "textColor": "#ffffff", "backgroundColor": "#009688",
                    "buttonTextColor": "#ffffff", "buttonBackgroundColor": "#000000",
                },
                "violet": {
                    "label": "Violet",
                    "textColor": "#ffffff", "backgroundColor": "#9c27b0",
                    "buttonTextColor": "#ffffff", "buttonBackgroundColor": "#000000",
                },
                "red": {
                    "label": "Red",
                    "backgroundColor": "#e91e63", "textColor": "#ffffff",
                    "buttonTextColor": "#ffffff", "buttonBackgroundColor": "#000000",
                },
                "darkRed": {
                    "label": "Dark red",
                    "backgroundColor": "#b00c28", "textColor": "#ffffff",
                    "buttonTextColor": "#ffffff", "buttonBackgroundColor": "#000000",
                },
                "black": {
                    "label": "Black",
                    "textColor": "#ffffff", "backgroundColor": "#262325",
                    "buttonTextColor": "#000000", "buttonBackgroundColor": "#ffffff",
                },
            },

            dimensions: "dynamic",
            availableDimensions: {
                "dynamic": {
                    "label": "Dynamic",
                    "width": null,
                    "height": null,
                },
                "300x300": {
                    "label": "300x300",
                    "width": "300px",
                    "height": "300px",
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
                };
                if (this.colorSchemes[this.colorScheme]) {
                    let cs = this.colorSchemes[this.colorScheme];
                    val.backgroundColor = cs.backgroundColor;
                    val.textColor = cs.textColor;
                    val.buttonBackgroundColor = cs.buttonBackgroundColor;
                    val.buttonTextColor = cs.buttonTextColor;
                }
                if (this.availableDimensions[this.dimensions]) {
                    let d = this.availableDimensions[this.dimensions];
                    val.width = d.width;
                    val.height = d.height;
                }
                this.$parent.$emit("values-changed", [{
                    key: "mediumRectangleTemplate",
                    val: val,
                }]);
            },
            colorSchemeByBackground: function(bgColor) {
                for (let idx in this.colorSchemes) {
                    if (!this.colorSchemes.hasOwnProperty(idx)) {
                        continue;
                    }
                    if (this.colorSchemes[idx].backgroundColor === bgColor) {
                        return idx;
                    }
                }
                return null;
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
