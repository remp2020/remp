<style>
.CodeMirror {
    height: 100%;
}
</style>
<template>
    <div>
        <label for="custom-js">Custom JS</label>
        <div id="custom-js">
            <textarea type="text" name="js" :value="js" hidden />
            <button v-on:click="textAreaClicked" type="button" class="btn btn-default waves-effect">
                <template v-if="js.trim().length > 0"><i class="zmdi zmdi-edit" ></i> Edit custom JS ({{ js.split(/\r\n|\r|\n/).length }} lines)</template>
                <template v-else><i class="zmdi zmdi-plus" ></i> Add custom JS</template>
            </button>
        </div>

        <div class="modal" id="modal-custom-js" style="z-index: 15;" data-keyboard="false" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg" style="height: 90%; width: 90%;">
                <div class="modal-content" style="height: 90%; display: flex; flex-direction: column;">
                    <div class="modal-header">
                        <h4 class="modal-title">Custom JS</h4>
                        <small>Custom JS is run as a function with single a function parameter params. Object <i>params</i> contains several properties of the banner you can access.
                            <span data-toggle="tooltip"
                                  data-original-title="properties: rtmSource, rtmMedium, rtmCampaign, rtmContent, rtmVariant"
                                  class="glyphicon glyphicon-question-sign"></span>
                        </small> <br>
                        <span v-pre>You can use <i class="zmdi zmdi-code"></i> Variables in this field as <code>{{&nbsp;variable_name&nbsp;}}</code>.</span>
                    </div>
                    <div class="modal-body" style="flex-grow: 1; overflow: hidden;">
                        <div class="row" style="height: 100%;">

                            <codemirror
                                ref="myCm"
                                v-model="code"
                                :options="cmOptions"
                                style="height: 100%;"
                            />

                        </div>
                    </div>
                    <div class="modal-footer">
                        <button v-on:click="saveChanges" type="button" class="btn btn-info waves-effect" data-dismiss="modal" :disabled="hasParseError"><i class="zmdi zmdi-check"></i> Save changes</button>
                        <button v-on:click="closeModal" type="button" class="btn btn-default waves-effect" data-dismiss="modal"><i class="zmdi zmdi-close"></i> Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import { codemirror } from 'vue-codemirror'
    import { JSHINT } from "jshint";

    import 'codemirror/lib/codemirror';
    import 'codemirror/lib/codemirror.css';

    import 'codemirror/addon/mode/overlay.js';
    import 'codemirror/addon/lint/lint.js';
    import 'codemirror/addon/lint/lint.css';
    import 'codemirror/addon/display/autorefresh.js';
    import 'codemirror/addon/lint/javascript-lint.js';

    import 'codemirror/mode/javascript/javascript.js';
    import 'codemirror/mode/twig/twig.js';

    window.JSHINT = JSHINT;

    const props = {
        "js": String,
    };

    import CodeMirror from 'codemirror';
    CodeMirror.defineMode("jstwig", function(config, parserConfig) {
        return CodeMirror.overlayMode(CodeMirror.getMode(config, parserConfig.backdrop || "text/javascript"), CodeMirror.getMode(config, "twig"));
    });

    export default {
        name: "custom-js",
        components: {codemirror},
        props: props,
        data() {
            return {
                code: this.js,
                errors: [],
                codemirror: null,
                cmOptions: {
                    gutters: ['CodeMirror-lint-markers'],
                    tabSize: 4,
                    styleActiveLine: true,
                    mode: "jstwig",
                    lineNumbers: true,
                    line: true,
                    autoRefresh: true,
                    autoCloseBrackets: true,
                    autoCloseTags: true,
                    matchBrackets: true,
                    lint: {
                        onUpdateLinting: this.handleErrors,
                    },
                    selfContain: true,
                    highlightLines: true,
                    styleSelectedText: true,
                }
            }
        },
        methods: {
            textAreaClicked: function() {
                $("#modal-custom-js").modal('show');
                this.codemirror.refresh();
            },
            saveChanges: function() {
                this.$parent.js = this.code;
            },
            closeModal: function() {
                this.code = this.js;
            },
            handleErrors: function(errors) {
                this.errors = errors;
            }
        },
        computed: {
            hasParseError() {
                return this.errors.filter(
                    (error) => { return error.severity === 'error'}
                ).length > 0;
            }
        },
        mounted() {
            this.codemirror = this.$refs.myCm.codemirror;
        }
    }
</script>