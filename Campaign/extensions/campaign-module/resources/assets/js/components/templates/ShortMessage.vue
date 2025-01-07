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

                        <div class="input-group fg-float m-t-30">
                            <span class="input-group-addon"><i class="zmdi zmdi-text-format"></i></span>
                            <div class="fg-line">
                                <label for="text" class="fg-label">Text</label>
                                <input v-model="text" class="form-control fg-input remp-banner-text-input" name="text" id="text" type="text" required>
                            </div>
                            <div class="help-block">
                                <small v-html="$parent.fieldParamsMessage"></small><br>
                                <small v-pre>You can use <i class="zmdi zmdi-code"></i> Snippets in this field as <code>{{&nbsp;snippet_name&nbsp;}}</code>.</small>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <input type="hidden" name="template" value="short_message" />
    </div>
</template>

<script type="text/javascript">
    import vSelect from "@remp/js-commons/js/components/vSelect";

    let props = [
        "_text",
        "_colorSchemes",
        "_colorScheme",
    ];
    export default {
        name: "short-message-template",
        components: { vSelect },
        props: props,
        created: function() {
            props.forEach((prop) => {
                this[prop.slice(1)] = this[prop] || this[prop.slice(1)];
            });
            this.emitValuesChanged();
        },
        data: () => ({
            text: "We think you might like this...",

            colorScheme: "green",
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
                    colorScheme: this.colorScheme,
                };
                this.$parent.$emit("values-changed", [{
                    key: "shortMessageTemplate",
                    val: val,
                }]);
            },
        }
    }
</script>
