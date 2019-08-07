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
                <a href="#html-template" role="tab" data-toggle="tab" aria-expanded="true">Short message template</a>
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

                        <input type="hidden" name="background_color" v-bind:value="_backgroundColor" />
                        <input type="hidden" name="text_color" v-bind:value="_textColor" />

                        <div class="input-group fg-float m-t-30">
                            <span class="input-group-addon"><i class="zmdi zmdi-text-format"></i></span>
                            <div class="fg-line">
                                <label for="text" class="fg-label">Text</label>
                                <input v-model="text" class="form-control fg-input" name="text" id="text" type="text" requried>
                            </div>
                            <div><small v-html="$parent.fieldParamsMessage"></small></div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <input type="hidden" name="template" value="short_message" />
    </div>
</template>

<script type="text/javascript">
    import vSelect from "remp/js/components/vSelect";

    let props = [
        "_text",
        "_backgroundColor",
        "_textColor",
    ];
    export default {
        name: "short-message-template",
        components: { vSelect },
        props: props,
        created: function(){
            props.forEach((prop) => {
                this[prop.slice(1)] = this[prop] || this[prop.slice(1)];
            });
            this.colorScheme = this.colorSchemeByBackground(this._backgroundColor) || this.colorScheme;
            this.emitValuesChanged();
        },
        data: () => ({
            text: "We think you might like this...",

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
        },
        updated: function() {
            this.emitValuesChanged();
        },
        methods: {
            emitValuesChanged: function() {
                let val = {
                    text: this.text,
                };
                if (this.colorSchemes[this.colorScheme]) {
                    let cs = this.colorSchemes[this.colorScheme];
                    val.backgroundColor = cs.backgroundColor;
                    val.textColor = cs.textColor;
                }
                this.$parent.$emit("values-changed", [{
                    key: "shortMessageTemplate",
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
        }
    }
</script>
