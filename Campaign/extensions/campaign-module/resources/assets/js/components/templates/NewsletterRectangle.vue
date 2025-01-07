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
                <a href="#newsletter-rectangle" role="tab" data-toggle="tab" aria-expanded="true">Newsletter Rectangle</a>
            </li>
        </ul>

        <div class="card m-t-15">
            <div class="tab-content p-0">
                <div role="tabpanel" class="active tab-pane" id="newsletter-rectangle">
                    <div class="card-body card-padding p-l-15">

                        <div class="input-group fg-float m-t-30">
                            <span class="input-group-addon"><i class="zmdi zmdi-email"></i></span>
                            <div class="fg-line">
                                <label for="newsletter_id" class="fg-label">Newsletter ID</label>
                                <input v-model="newsletterId" class="form-control fg-input remp-banner-text-input"
                                       name="newsletter_id" id="newsletter_id">
                            </div>
                            <div><small v-html="newsletterIdHint"></small></div>
                        </div><!-- .input-group -->

                        <div class="input-group fg-float m-t-30">
                            <span class="input-group-addon"><i class="zmdi zmdi-n-1-square"></i></span>
                            <div class="fg-line">
                                <label for="btn_submit" class="fg-label">Submit Button Text</label>
                                <input v-model="btnSubmit" class="form-control fg-input remp-banner-text-input"
                                       name="btn_submit" id="btn_submit">
                            </div>
                            <small v-pre>You can use &lt;&nbsp;&gt; Snippets in this field as {{&nbsp;snippet_name&nbsp;}}.</small>
                        </div><!-- .input-group -->

                        <div class="input-group fg-float m-t-30">
                            <span class="input-group-addon"><i class="zmdi zmdi-format-subject"></i></span>
                            <div class="fg-line">
                                <label for="title" class="fg-label">Title</label>
                                <input v-model="title" class="form-control fg-input remp-banner-text-input"
                                       name="title" id="title">
                            </div>
                            <small v-pre>You can use &lt;&nbsp;&gt; Snippets in this field as {{&nbsp;snippet_name&nbsp;}}.</small>
                        </div><!-- .input-group -->

                        <div class="input-group fg-float m-t-30">
                            <span class="input-group-addon"><i class="zmdi zmdi-format-subject"></i></span>
                            <div class="fg-line">
                                <label for="text" class="fg-label">Text</label>
                                <textarea v-model="text" class="form-control fg-input remp-banner-text-input"
                                          rows="4" name="text" cols="50" id="text"></textarea>
                            </div>
                            <small v-pre>You can use &lt;&nbsp;&gt; Snippets in this field as {{&nbsp;snippet_name&nbsp;}}.</small>
                        </div><!-- .input-group -->

                        <div class="input-group fg-float m-t-30">
                            <span class="input-group-addon"><i class="zmdi zmdi-format-subject"></i></span>
                            <div class="fg-line">
                                <label for="success" class="fg-label">Success message</label>
                                <input v-model="success" class="form-control fg-input remp-banner-text-input"
                                          name="success" id="success">
                            </div>
                            <div><small>Replaces button text if submission is successful</small></div>
                        </div><!-- .input-group -->

                        <div class="input-group fg-float m-t-30">
                            <span class="input-group-addon"><i class="zmdi zmdi-format-subject"></i></span>
                            <div class="fg-line">
                                <label for="failure" class="fg-label">Failure message</label>
                                <input v-model="failure" class="form-control fg-input remp-banner-text-input"
                                       name="failure" id="failure">
                            </div>
                            <div><small>Replaces button text if submission fails</small></div>
                        </div><!-- .input-group -->

                        <div class="input-group fg-float m-t-30">
                            <span class="input-group-addon"><i class="zmdi zmdi-format-subject"></i></span>
                            <div class="fg-line">
                                <label for="terms" class="fg-label">Terms</label>
                                <textarea v-model="terms" class="form-control fg-input remp-banner-text-input"
                                          rows="2" name="terms" cols="50" id="terms"></textarea>
                            </div>
                            <div class="has-warning" v-if="!termsHasLink()">
                                <small class="help-block"><i class="zmdi zmdi-alert-triangle"></i> HTML does not contain any links</small>
                            </div>
                        </div><!-- .input-group -->

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
                        </div><!-- .input-group -->

                        <div class="input-group v-select">
                            <span class="input-group-addon"><i class="zmdi zmdi-swap-alt"></i></span>
                            <div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <label for="dimensions" class="fg-label">Dimensions</label>
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
                        </div><!-- .input-group -->

                        <input type="hidden" name="width" v-bind:value="_width" />
                        <input type="hidden" name="height" v-bind:value="_height" />
                    </div>
                </div>
            </div>
        </div>

        <input type="hidden" name="template" value="newsletter_rectangle" />
    </div>
</template>

<script type="text/javascript">
import vSelect from "@remp/js-commons/js/components/vSelect";

let props = [
    '_templateId',
    '_newsletterId',
    '_btnSubmit',
    '_title',
    '_text',
    '_success',
    '_failure',
    "_colorSchemes",
    "_colorScheme",
    '_width',
    '_height',
    '_terms',
    '_endpoint',
    '_useXhr',
    '_requestBody',
    '_requestHeaders',
    '_paramsTransposition',
    '_paramsExtra',
    '_rempMailerAddr',

];
export default {
    name: "newsletter-rectangle",
    components: { vSelect },
    props: props,
    created: function () {
        props.forEach((prop) => {
            this[prop.slice(1)] = this[prop] || this[prop.slice(1)];
        });

        if (!this.templateId) {
            this.newsletterId = '';
            this.btnSubmit = 'Subscribe';
            this.title = 'Headline';
            this.text = 'Lorem Ipsum...';
            this.success = 'Subscription successful';
            this.failure = 'Subscription failed';
            this.terms = 'By clicking <em>Subscribe</em>, you agree with Terms & Conditions';
        }

        this.dimensions = this.dimensionsByWidthHeight(this._width, this._height) || this.dimensions;
        this.emitValuesChanged();
    },
    data: () => ({
        newsletterId: null,
        btnSubmit: null,
        title: null,
        text: null,
        success: null,
        failure: null,
        backgroundColor: null,
        buttonBackgroundColor: null,
        buttonTextColor: null,
        width: null,
        height: null,
        terms: '',
        endpoint: null,
        useXhr: null,
        requestBody: null,
        requestHeaders: null,
        paramsTransposition: null,
        paramsExtra: null,
        rempMailerAddr: null,

        colorScheme: "green",

        dimensions: "dynamic",
        availableDimensions: {
            "dynamic": {
                "label": "Dynamic",
                "width": null,
                "height": null,
            },
            "300x250": {
                "label": "300x250",
                "width": "300px",
                "height": "250px",
            },
            "336x280": {
                "label": "336x280",
                "width": "336px",
                "height": "280px",
            },
        }

    }),
    computed: {
        newsletterIdHint: function(){
            if (this.rempMailerAddr !== null){
                let url = this.rempMailerAddr + '/mailer/list';
                return `Newsletter <code>code</code> as found in REMP Mailer <a href="${url}" target="_blank">Newsletter lists</a>.`;

            }
            return `It appears you are not using REMP Mailer. In case of another provider, please use newsletter
                identification code/number that you wish to signup for. In most cases you can find this id in backend.`;
        },
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
    updated: function () {
        this.emitValuesChanged();
    },
    methods: {
        termsHasLink: function () {
            const re = /<\s*a[^>]*>(.*?)<\s*\/\s*a>/g;
            const found = this.terms.match(re);
            return (found && found.length > 0);
        },
        emitValuesChanged: function() {
            let val = {
                newsletterId: this.newsletterId,
                btnSubmit: this.btnSubmit,
                title: this.title,
                text: this.text,
                success: this.success,
                failure: this.failure,
                terms: this.terms,
                endpoint: this.endpoint,
                useXhr: this.useXhr,
                requestBody: this.requestBody,
                requestHeaders: this.requestHeaders,
                paramsTransposition: this.paramsTransposition,
                paramsExtra: this.paramsExtra,
                rempMailerAddr: this.rempMailerAddr,
                colorScheme: this.colorScheme,
            };
            if (this.availableDimensions[this.dimensions]) {
                let d = this.availableDimensions[this.dimensions];
                val.width = d.width;
                val.height = d.height;
            }
            this.$parent.$emit("values-changed", [{
                key: "newsletterRectangleTemplate",
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
    },
}
</script>
