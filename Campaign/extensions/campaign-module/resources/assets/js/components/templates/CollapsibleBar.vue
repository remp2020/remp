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
                <a href="#collapsible-bar-template" role="tab" data-toggle="tab" aria-expanded="true">Collapsible bar template</a>
            </li>
        </ul>

        <div class="card m-t-15">
            <div class="tab-content p-0">
                <div role="tabpanel" class="active tab-pane" id="collapsible-bar-template">
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
                                                  v-bind:value="colorScheme"
                                                  v-bind:options.sync="colorSchemeOptions"
                                        ></v-select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="input-group">
                            <span class="input-group-addon"><i class="zmdi zmdi-swap-alt"></i></span>
                            <div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <label for="initial_state" class="fg-label">Initial state</label>
                                    </div>
                                    <div class="col-md-12">
                                        <v-select v-model="initialState"
                                                  id="initial_state"
                                                  name="initial_state"
                                                  :value="initialState"
                                                  :options.sync="initialStateOptions"
                                        ></v-select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="input-group fg-float checkbox">
                            <label class="m-l-15">
                                Always display banner in initial state
                                <input v-model="forceInitialState" value="1" name="force_initial_state" type="checkbox">
                                <i class="input-helper"></i>
                                <small class="help-block">
                                    Banner is displayed in the same state in which it was on the last display or
                                    in initial state. Enable if you want to always display in initial state.
                                </small>
                            </label>
                        </div><!-- .input-group -->

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
                                <label for="collapse_text" class="fg-label">Collapse text</label>
                                <input v-model="collapseText" class="form-control fg-input remp-banner-text-input" name="collapse_text" id="collapse_text" type="text">
                            </div>
                            <small class="help-block" v-pre>You can use <i class="zmdi zmdi-code"></i> Snippets in this field as <code>{{&nbsp;snippet_name&nbsp;}}</code>.</small>
                        </div>

                        <div class="input-group fg-float m-t-30">
                            <span class="input-group-addon"><i class="zmdi zmdi-text-format"></i></span>
                            <div class="fg-line">
                                <label for="expand_text" class="fg-label">Expand text</label>
                                <input v-model="expandText" class="form-control fg-input remp-banner-text-input" name="expand_text" id="expand_text" type="text">
                            </div>
                            <small class="help-block" v-pre>You can use <i class="zmdi zmdi-code"></i> Snippets in this field as <code>{{&nbsp;snippet_name&nbsp;}}</code>.</small>
                        </div>

                        <div class="input-group fg-float m-t-30">
                            <span class="input-group-addon"><i class="zmdi zmdi-text-format"></i></span>
                            <div class="fg-line">
                                <label for="main_text" class="fg-label">Main text</label>
                                <input v-model="mainText" class="form-control fg-input remp-banner-text-input" name="main_text" id="main_text" type="text">
                            </div>
                            <div>
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

        <input type="hidden" name="template" value="collapsible_bar" />
    </div>
</template>

<script type="text/javascript">
    import vSelect from "@remp/js-commons/js/components/vSelect.vue";

    let props = [
        "_templateId",
        "_mainText",
        "_headerText",
        "_collapseText",
        "_expandText",
        "_buttonText",
        "_colorScheme",
        "_colorSchemes",
        "_initialState",
        "_forceInitialState",
    ];
    export default {
        name: "collapsible-bar-template",
        components: {
            vSelect,
        },
        props: props,
        created: function() {
            props.forEach((prop) => {
                this[prop.slice(1)] = this[prop] || this[prop.slice(1)];
            });

            if (!this.templateId) {
                this.mainText = "Limited time offer<br/>30% discount";
                this.headerText = "Offer";
                this.collapseText = "Collapse";
                this.expandText = "Expand";
                this.buttonText = "Visit offer";
            }

            this.emitValuesChanged();
        },
        data: () => ({
            mainText: null,
            headerText: null,
            collapseText: null,
            expandText: null,
            buttonText: null,
            colorScheme: "green",
            colorSchemes: null,
            initialStateOptions: [
                {
                    "label": "Collapsed",
                    "value": "collapsed"
                },
                {
                    "label": "Expanded",
                    "value": "expanded"
                }
            ],
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
                    mainText: this.mainText,
                    headerText: this.headerText,
                    collapseText: this.collapseText,
                    expandText: this.expandText,
                    buttonText: this.buttonText,
                    colorScheme: this.colorScheme,
                };
                this.$parent.$emit("values-changed", [{
                    key: "collapsibleBarTemplate",
                    val: val,
                }]);
            },
        }
    }
</script>
