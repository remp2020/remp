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
                                        <label for="background_color" class="fg-label">Background color</label>
                                    </div>
                                    <div class="col-md-12">
                                        <v-select v-model="backgroundColor"
                                                  name="background_color"
                                                  id="background_color"
                                                  v-bind:value="backgroundColor"
                                                  v-bind:options.sync="backgroundColorOptions"
                                        ></v-select>
                                    </div>
                                </div>
                            </div>
                        </div>

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
                                <input v-model="mainText" class="form-control fg-input" name="main_text" id="main_text" type="text">
                            </div>
                        </div>

                        <div class="input-group fg-float m-t-30">
                            <span class="input-group-addon"><i class="zmdi zmdi-text-format"></i></span>
                            <div class="fg-line">
                                <label for="button_text" class="fg-label">Button text</label>
                                <input v-model="buttonText" class="form-control fg-input" name="button_text" id="button_text" type="text">
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
    ];
    export default {
        name: "medium-rectangle-template",
        components: { vSelect },
        props: props,
        mounted: function(){
            props.forEach((prop) => {
                this[prop.slice(1)] = this[prop] || this[prop.slice(1)];
            });
            this.emitValuesChanged();
        },
        data: () => ({
            headerText: "REMP2020.com",
            mainText: "Limited time offer<br/>30% discount",
            buttonText: "Visit offer",
            backgroundColor: '#009688',

            backgroundColorOptions: [
                {"label": "Grey", "value": "#ededed"},
                {"label": "Yellow", "value": "#f7bc1e"},
                {"label": "Blue", "value": "#00b7db"},
                {"label": "Green", "value": "#009688"},
                {"label": "Violet", "value": "#9c27b0"},
                {"label": "Red", "value": "#e91e63"},
                {"label": "Black", "value": "#262325"},
            ],
        }),
        updated: function() {
           this.emitValuesChanged();
        },
        methods: {
            emitValuesChanged: function() {
                this.$parent.$emit("values-changed", [
                    {key: "mediumRectangleTemplate", val: {
                        headerText: this.headerText,
                        mainText: this.mainText,
                        buttonText: this.buttonText,
                        backgroundColor: this.backgroundColor,
                    }},
                ]);
            }
        }
    }
</script>