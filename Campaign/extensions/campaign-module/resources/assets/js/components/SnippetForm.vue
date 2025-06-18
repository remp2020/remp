<style>
.CodeMirror-wrap {
    height: auto;
    width: 100%;
}
</style>

<template>
    <div>
        <div class="row" style="height: 100%;">
            <div class="col-md-6 form-group" style="height: 100%;">
                <div class="dtp-container fg-line">
                    <label class="fg-label">Name</label>
                    <input type="text" class="form-control" v-model="name" name="name">
                </div>
            </div>
            <div class="col-md-12 form-group">
                <div style="display: flex; flex-direction: column; min-height: 150px">
                    <label class="fg-label">Value</label>
                    <codemirror
                        v-model="value"
                        name="value"
                        :options="cmOptions"
                        style="flex: 1 1 100%; display: flex"
                    />
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12 col-lg-8">
                <div class="input-group m-t-20 m-b-30">
                    <div class="fg-line">
                        <input type="hidden" name="action" :value="submitAction">

                        <button class="btn btn-info waves-effect" type="submit" @click="submitAction = 'save'">
                            <i class="zmdi zmdi-check"></i> Save
                        </button>
                        <button class="btn btn-info waves-effect" type="submit" @click="submitAction = 'save_close'">
                            <i class="zmdi zmdi-mail-send"></i> Save and close
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <form-validator :url="validateUrl"></form-validator>
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

import FormValidator from "@remp/js-commons/js/components/FormValidator";

window.JSHINT = JSHINT;

const props = {
    "_name": String,
    "_value": String,
    "_validateUrl": String,
};

import CodeMirror from 'codemirror';
CodeMirror.defineMode("jstwig", function(config, parserConfig) {
    return CodeMirror.overlayMode(CodeMirror.getMode(config, parserConfig.backdrop || "text/javascript"), CodeMirror.getMode(config, "twig"));
});

export default {
    name: "snippet-form",
    components: {
        codemirror,
        FormValidator,
    },
    props: props,
    data() {
        return {
            value: this._value,
            name: this._name,
            validateUrl: this._validateUrl,
            errors: [],
            codemirror: null,
            submitAction: null,
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
                lineWrapping: true,
            }
        }
    },
    methods: {
        handleErrors: function(errors) {
            this.errors = errors;
        },
    },
    computed: {
        hasParseError() {
            return this.errors.filter(
                (error) => { return error.severity === 'error'}
            ).length > 0;
        }
    },
}
</script>
