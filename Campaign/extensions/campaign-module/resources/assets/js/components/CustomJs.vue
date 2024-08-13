<style>
.CodeMirror {
    height: 100%;
}
.sweet-alert {
    width: 520px;
    margin-top: -200px !important;
}

.sweet-alert .text-muted {
    text-align: left;
}
</style>
<template>
    <div>
        <label for="custom-js">Custom JS</label>
        <div id="custom-js">
            <textarea type="text" name="js" :value="js" hidden />
            <button v-on:click="textAreaClicked" type="button" class="btn btn-default waves-effect">
                <template v-if="js && js.trim().length > 0"><i class="zmdi zmdi-edit" ></i> Edit custom JS ({{ js.split(/\r\n|\r|\n/).length }} lines)</template>
                <template v-else><i class="zmdi zmdi-plus" ></i> Add custom JS</template>
            </button>
        </div>

        <div class="modal" id="modal-custom-js" style="z-index: 15;" data-keyboard="false" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg" style="height: 90%; width: 90%;">
                <div class="modal-content" style="height: 90%; display: flex; flex-direction: column;">
                    <div class="modal-header p-15">
                        <h4 class="modal-title">Custom JS <span v-if="bannerName">({{ bannerName }})</span>
                            <button type="button" v-on:click="syntaxButtonClicked" class="btn btn-default waves-effect pull-right"><i class="zmdi zmdi-code"></i> Syntax</button>
                        </h4>
                    </div>
                    <div class="modal-body" style="flex-grow: 1; overflow: hidden;">
                        <div class="row" style="height: 100%;">

                            <div class="col-md-9" style="height: 100%;">
                                <codemirror
                                    ref="myCm"
                                    v-model="code"
                                    :options="cmOptions"
                                    style="height: 100%;"
                                    @input="onCmCodeChange"
                                />
                            </div>

                            <div class="col-md-3 p-0 o-auto" style="height: 100%;">
                                <h4>Variables:</h4>
                                <div>
                                    <p>You can also use inline variables defined as <code>$$variableName$$</code> to use them on another place of JS code.</p>
                                </div>
                                <div v-for="variable in variables" class="p-b-10">
                                    <div class="form-group fg-line">
                                        <label><code>{{ variable.name }}</code></label>
                                        <input
                                            v-on:keyup="changeVariableValue"
                                            :variable-name="variable.name"
                                            v-model="variable.value"
                                            type="text"
                                            class="form-control bgm-white p-5"
                                            autocomplete="off"/>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                    <div class="modal-footer">
                        <span v-if="hasParseError" class="m-r-10">Parse error. Unable to save.</span>
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
        "bannerName": String,
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
                name: this.bannerName,
                errors: [],
                codemirror: null,
                variables: [],
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
                this.variables = this.parseVariables(this.code);
            },
            saveChanges: function() {
                this.$parent.js = this.code;
                this.$parent.$refs.saveButton.click();
            },
            closeModal: function() {
                this.code = this.js;
            },
            handleErrors: function(errors) {
                this.errors = errors;
            },
            parseVariables: function(code) {
                let variables = [];
                // parse all occurrences of internal variables ($$variableName$$ = 'value') from code
                let regexp = new RegExp(/\$\$(\w+\d*)\$\$\s?=\s?['"](.*)['"];?/gm);
                for (const variable of code.matchAll(regexp)) {
                    variables.push({name: variable[1], value: variable[2].replace(/\\(["'])/gm, '$1')});
                }
                return variables;
            },
            onCmCodeChange: function(code) {
                this.variables = this.parseVariables(code);
            },
            changeVariableValue: function(event) {
                let variableName = event.target.getAttribute('variable-name');

                // replace $$variableName$$ = 'oldValue' with new value
                this.codemirror.getDoc().setValue(
                    this.code.replace(
                        new RegExp('(\\$\\$' + variableName + '\\$\\$\\s?=\\s?[\'\"]).*([\'\"])', 'gm'),
                        '$1' + event.target.value.replace(/(["'])/gm, '\\$1') + '$2'
                    )
                );
            },
            syntaxButtonClicked: function () {
                swal({
                    'html': true,
                    'title': '<i class="zmdi zmdi-code"></i> Syntax',
                    'text': 'Custom JS is run as a function with single a function parameter params. Object <code>params</code> contains several properties of the banner you can access (<code>rtmSource, rtmMedium, rtmCampaign, rtmContent, rtmVariant</code>). <br><br>'
                        + 'You can use <a href="/snippets" target="_blank">defined snippets</a> in this field as <code>{{&nbsp;snippet_name&nbsp;}}</code>. <br><br>'
                        + 'You can also use inline variables defined as <code>$$variableName$$</code> to use them on another place of JS code.'
                });
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