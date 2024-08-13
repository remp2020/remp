<template>
    <div>
        <ul class="tab-nav" role="tablist" data-tab-color="teal">
            <li class="active">
                <a href="#overlay-two-buttons-signature" role="tab" data-toggle="tab" aria-expanded="true">Overlay with two buttons and signature</a>
            </li>
        </ul>

        <div class="card m-t-15">
            <div class="tab-content p-0">
                <div role="tabpanel" class="active tab-pane" id="overlay-two-buttons-signature">

                    <div class="card-body card-padding p-l-15">
                        <div class="input-group fg-float m-t-30">
                            <span class="input-group-addon"><i class="zmdi zmdi-format-subject"></i></span>
                            <div class="fg-line">
                                <label for="text_before" class="fg-label">Text Before</label>
                                <textarea v-model="textBefore" class="form-control fg-input remp-banner-text-input" rows="10" name="text_before" cols="50" id="text_before"></textarea>
                            </div>
                            <div class="help-block">
                                <small>Single new lines are converted to <code>&lt;br&gt;</code>, double new lines to <code>&lt;p&gt;</code></small>
                                <small v-pre>You can use <i class="zmdi zmdi-code"></i> Snippets in this field as <code>{{&nbsp;snippet_name&nbsp;}}</code>.</small>
                            </div>

                        </div><!-- .input-group -->

                        <div class="input-group fg-float m-t-30">
                            <span class="input-group-addon"><i class="zmdi zmdi-format-subject"></i></span>
                            <div class="fg-line">
                                <label for="text_after" class="fg-label">Text After</label>
                                <textarea v-model="textAfter" class="form-control fg-input remp-banner-text-input" rows="3" name="text_after" cols="50" id="text_after"></textarea>
                            </div>
                            <div class="help-block">
                                <small>Single new lines are converted to <code>&lt;br&gt;</code>, double new lines to <code>&lt;p&gt;</code></small>
                                <small v-pre>You can use <i class="zmdi zmdi-code"></i> Snippets in this field as <code>{{&nbsp;snippet_name&nbsp;}}</code>.</small>
                            </div>

                        </div><!-- .input-group -->

                        <div class="input-group fg-float m-t-30">
                            <span class="input-group-addon"><i class="zmdi zmdi-n-1-square"></i></span>
                            <div class="fg-line">
                                <label for="text_btn_primary" class="fg-label">Primary button text</label>
                                <input v-model="textBtnPrimary" class="form-control fg-input remp-banner-text-input" name="text_btn_primary" id="text_btn_primary">
                            </div>
                            <small class="help-block" v-pre>You can use <i class="zmdi zmdi-code"></i> Snippets in this field as <code>{{&nbsp;snippet_name&nbsp;}}</code>.</small>
                        </div><!-- .input-group -->

                        <div class="input-group fg-float m-t-30">
                            <span class="input-group-addon"><i class="zmdi zmdi-info-outline"></i></span>
                            <div class="fg-line">
                                <label for="text_btn_primary" class="fg-label">Primary button minor text</label>
                                <input v-model="textBtnPrimaryMinor" class="form-control fg-input remp-banner-text-input" name="text_btn_primary_minor" id="text_btn_primary_minor">
                            </div>
                            <small class="help-block" v-pre>You can use <i class="zmdi zmdi-code"></i> Snippets in this field as <code>{{&nbsp;snippet_name&nbsp;}}</code>.</small>
                        </div><!-- .input-group -->

                        <div class="input-group fg-float m-t-30">
                            <span class="input-group-addon"><i class="zmdi zmdi-n-2-square"></i></span>
                            <div class="fg-line">
                                <label for="text_btn_secondary" class="fg-label">Secondary button text</label>
                                <input v-model="textBtnSecondary" class="form-control fg-input remp-banner-text-input" name="text_btn_secondary" id="text_btn_secondary">
                            </div>
                            <div class="help-block">
                                <small>If not set, secondary button disappears</small>
                                <br>
                                <small v-pre>You can use <i class="zmdi zmdi-code"></i> Snippets in this field as <code>{{&nbsp;snippet_name&nbsp;}}</code>.</small>
                            </div>
                        </div><!-- .input-group -->

                        <div class="input-group fg-float m-t-30">
                            <span class="input-group-addon"><i class="zmdi zmdi-info-outline"></i></span>
                            <div class="fg-line">
                                <label for="text_btn_secondary" class="fg-label">Secondary button minor text</label>
                                <input v-model="textBtnSecondaryMinor" class="form-control fg-input remp-banner-text-input" name="text_btn_secondary_minor" id="text_btn_secondary_minor">
                            </div>
                            <small class="help-block" v-pre>You can use <i class="zmdi zmdi-code"></i> Snippets in this field as <code>{{&nbsp;snippet_name&nbsp;}}</code>.</small>
                        </div><!-- .input-group -->

                        <div class="input-group fg-float m-t-30">
                            <span class="input-group-addon"><i class="zmdi zmdi-link"></i></span>
                            <div class="fg-line">
                                <label for="target_url_secondary" class="fg-label">Target URL for secondary button</label>
                                <input v-model="targetUrlSecondary" class="form-control fg-input remp-banner-text-input" name="target_url_secondary" id="target_url_secondary">
                            </div>
                            <div class="help-block"><small>If not set, secondary button disappears. <br>RTM (REMP's UTM) params will be automatically appended to this link.</small></div>
                        </div><!-- .input-group -->

                        <div class="input-group fg-float m-t-30">
                            <span class="input-group-addon"><i class="zmdi zmdi-image-o"></i></span>
                            <div class="fg-line">
                                <label for="target_url_secondary" class="fg-label">Signature image URL</label>
                                <input v-model="signatureImageUrl" class="form-control fg-input remp-banner-text-input" name="signature_image_url" id="signature_image_url">
                            </div>
                        </div><!-- .input-group -->

                        <div class="input-group fg-float m-t-30">
                            <span class="input-group-addon"><i class="zmdi zmdi-format-subject"></i></span>
                            <div class="fg-line">
                                <label for="text_signature" class="fg-label">Text under signature</label>
                                <textarea v-model="textSignature" class="form-control fg-input remp-banner-text-input" rows="2" cols="50" name="text_signature" id="text_signature"></textarea>
                            </div>
                            <div class="help-block">
                                <small>Single new lines are converted to <code>&lt;br&gt;</code>, double new lines to <code>&lt;p&gt;</code></small><br>
                                <small v-pre>You can use <i class="zmdi zmdi-code"></i> Snippets in this field as <code>{{&nbsp;snippet_name&nbsp;}}</code>.</small>
                            </div>
                        </div><!-- .input-group -->

                    </div>
                </div>
            </div>
        </div>

        <input type="hidden" name="template" value="overlay_two_buttons_signature" />
    </div>
</template>

<script type="text/javascript">
    let props = [
        "_templateId",
        '_textBefore',
        '_textAfter',
        '_textBtnPrimary',
        '_textBtnPrimaryMinor',
        '_textBtnSecondary',
        '_textBtnSecondaryMinor',
        '_targetUrlSecondary',
        '_signatureImageUrl',
        '_textSignature'
    ];
    export default {
        name: "overlay-two-buttons-signature-template",
        components: { },
        props: props,
        created: function(){
            props.forEach((prop) => {
                this[prop.slice(1)] = this[prop] || this[prop.slice(1)];
            });

            if (!this.templateId) {
                this.textBefore = "Multiline text before buttons. \n\nConverts newline to <code>&lt;br&gt;</code>  and double new lines to <code>&lt;p&gt;</code>";
                this.textAfter = 'Multiline text after buttons, works same as text before buttons.';
                this.textBtnPrimary = 'Main CTA button';
                this.textBtnPrimaryMinor = 'Main button description';
                this.textBtnSecondary = 'Secondary CTA button';
                this.textBtnSecondaryMinor = 'Secondary button description';
                this.textSignature = 'Signature text<br>with optional image url';
                this.targetUrlSecondary = 'https://exmaple.com';
            }

            this.emitValuesChanged();
        },
        data: () => ({
            textBefore: null,
            textAfter: null,
            textBtnPrimary: null,
            textBtnPrimaryMinor: null,
            textBtnSecondary: null,
            textBtnSecondaryMinor: null,
            targetUrlSecondary: null,
            signatureImageUrl: null,
            textSignature: null
        }),
        updated: function() {
            this.emitValuesChanged();
        },
        methods: {
            emitValuesChanged: function() {
                this.$parent.$emit("values-changed", [
                    {
                        key: "overlayTwoButtonsSignatureTemplate",
                        val: {
                            textBefore: this.textBefore,
                            textAfter: this.textAfter,
                            textBtnPrimary: this.textBtnPrimary,
                            textBtnPrimaryMinor: this.textBtnPrimaryMinor,
                            textBtnSecondary: this.textBtnSecondary,
                            textBtnSecondaryMinor: this.textBtnSecondaryMinor,
                            targetUrlSecondary: this.targetUrlSecondary,
                            signatureImageUrl: this.signatureImageUrl,
                            textSignature: this.textSignature
                        }
                    },
                ]);
            }
        },
        computed: {

        }
    }
</script>