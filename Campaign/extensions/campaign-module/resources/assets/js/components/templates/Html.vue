<template>
    <div>
        <ul class="tab-nav" role="tablist" data-tab-color="teal">
            <li class="active">
                <a href="#html-template" role="tab" data-toggle="tab" aria-expanded="true">HTML template</a>
            </li>
        </ul>

        <div class="card m-t-15">
            <div class="tab-content p-0">
                <div role="tabpanel" class="active tab-pane" id="html-template">
                    <div class="card-body card-padding p-l-15">
                        <div class="input-group">
                            <span class="input-group-addon"><i class="zmdi zmdi-aspect-ratio-alt"></i></span>
                            <div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <label for="dimensions" class="fg-label">Dimensions</label>
                                    </div>
                                    <div class="col-md-12">
                                        <v-select v-model="dimensions"
                                                  name="dimensions"
                                                  id="dimensions"
                                                  v-bind:value="dimensions"
                                                  v-bind:options.sync="mappedDimensionOptions"
                                        ></v-select>
                                    </div>
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
                            </div><!-- .input-group -->
                        </div>

                        <div class="input-group fg-float m-t-30">
                            <span class="input-group-addon"><i class="zmdi zmdi-format-subject"></i></span>
                            <div class="fg-line">
                                <label for="html_text" class="fg-label">HTML Text</label>
                                <textarea v-model="text" class="form-control fg-input remp-banner-text-input" rows="6" name="text" cols="50" id="html_text"></textarea>
                            </div>
                            <div>
                                <small class="help-block" v-html="$parent.fieldParamsMessage"></small>
                            </div>
                            <small class="help-block" v-pre>You can use <i class="zmdi zmdi-code"></i> Snippets in this field as <code>{{&nbsp;snippet_name&nbsp;}}</code>.</small>
                        </div><!-- .input-group -->

                        <div class="input-group fg-float m-t-30">
                            <span class="input-group-addon"><i class="zmdi zmdi-format-subject"></i></span>
                            <div class="fg-line">
                                <label for="css" class="fg-label">Custom CSS</label>
                                <textarea v-model="css" class="form-control fg-input" rows="6" name="css" cols="50" id="css"></textarea>
                            </div>
                            <small class="help-block" v-pre>Styles in this field are applied globally.<br> Prevent colliding with other styles by prefixing your classes.<br> You can use <i class="zmdi zmdi-code"></i> Snippets in this field as <code>{{&nbsp;snippet_name&nbsp;}}</code>.</small>
                        </div><!-- .input-group -->

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
                        </div><!-- .input-group -->

                        <div class="input-group fg-float m-t-30">
                            <span class="input-group-addon"><i class="zmdi zmdi-format-size"></i></span>
                            <div class="fg-line">
                                <label for="font_size" class="fg-label">Font Size</label>
                                <input v-model="fontSize" class="form-control fg-input" name="font_size" type="number" id="font_size">
                            </div>
                        </div><!-- .input-group -->

                        <div class="input-group m-t-30">
                            <span class="input-group-addon"><i class="zmdi zmdi-swap"></i></span>
                            <div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <label for="text_align" class="fg-label">Text Alignment</label>
                                    </div>
                                    <div class="col-md-12">
                                        <v-select v-model="textAlign"
                                                  name="text_align"
                                                  id="text_align"
                                                  v-bind:value="textAlign"
                                                  v-bind:options.sync="mappedTextAlignOptions"
                                        ></v-select>
                                    </div>
                                </div>
                            </div>
                        </div><!-- .input-group -->
                    </div>
                </div>
            </div>
        </div>

        <input type="hidden" name="template" value="html" />
    </div>
</template>

<script type="text/javascript">
    import vSelect from "@remp/js-commons/js/components/vSelect";

    let props = [
        "_backgroundColor",
        "_text",
        "_css",
        "_js",
        "_includes",
        "_textColor",
        "_fontSize",
        "_textAlign",
        "_dimensions",

        "alignmentOptions",
        "dimensionOptions",
    ];
    export default {
        name: "html-template",
        components: { vSelect },
        props: props,
        created: function(){
            props.forEach((prop) => {
                this[prop.slice(1)] = this[prop] || this[prop.slice(1)];
            });
            this.emitValuesChanged();
        },
        data: () => ({
            backgroundColor: null,
            text: null,
            css: null,
            js: null,
            includes: null,
            textColor: null,
            fontSize: null,
            textAlign: null,
            dimensions: null,
        }),
        updated: function() {
            this.emitValuesChanged();
        },
        methods: {
            emitValuesChanged: function() {
                this.$parent.$emit("values-changed", [
                    {key: "htmlTemplate", val: {
                        backgroundColor: this.backgroundColor,
                        text: this.text,
                        css: this.css,
                        textColor: this.textColor,
                        fontSize: this.fontSize,
                        textAlign: this.textAlign,
                        dimensions: this.dimensions,
                    }},
                ]);
            }
        },
        computed: {
            mappedTextAlignOptions: function () {
                let opts = [];

                for (let i in this.alignmentOptions) {
                    opts.push({
                        label: this.alignmentOptions[i].name,
                        value: this.alignmentOptions[i].key,
                    });
                }

                return opts;
            },
            mappedDimensionOptions: function() {
                let opts = [];
                for (let i in this.dimensionOptions) {
                    opts.push({
                        label: this.dimensionOptions[i].name,
                        value: this.dimensionOptions[i].key,
                    });
                }
                return opts;
            }
        }
    }
</script>