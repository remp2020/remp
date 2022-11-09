/*
* Own implementation of linting javascript code with twig variables.
* Original implementation you can find: codemirror/addon/lint/javascript-lint.js
*
* We need to override content input data for JSHINT library to get errors only on the javascript part of code.
* So the line of code with twig variables is enveloped by javascript comment.
*/

import CodeMirror from 'codemirror/lib/codemirror';

(function() {

    "use strict";

    function validator(text, options) {

        // Replace all twig variables with JS comment to prevent errors from JS Hint
        let regex = new RegExp(/{{\s?\w+\s?}}/gm);
        text = text.replace(regex, "/* $& */");

        if (!window.JSHINT) {
            if (window.console) {
                window.console.error("Error: window.JSHINT not defined, CodeMirror JavaScript linting cannot run.");
            }
            return [];
        }
        if (!options.indent) { // JSHint error.character actually is a column index, this fixes underlining on lines using tabs for indentation
            options.indent = 1; // JSHint default value is 4
        }

        JSHINT(text, options, options.globals);
        var errors = JSHINT.data().errors;
        var result = [];

        if (errors) {
            parseErrors(errors, result);
        }

        return result;
    }

    CodeMirror.registerHelper("lint", "javascript", validator);

    function parseErrors(errors, output) {
        for ( var i = 0; i < errors.length; i++) {
            var error = errors[i];
            if (error) {
                if (error.line <= 0) {
                    if (window.console) {
                        window.console.warn("Cannot display JSHint error (invalid line " + error.line + ")", error);
                    }
                    continue;
                }

                var start = error.character - 1, end = start + 1;
                if (error.evidence) {
                    var index = error.evidence.substring(start).search(/.\b/);
                    if (index > -1) {
                        end += index;
                    }
                }

                // Convert to format expected by validation service
                var hint = {
                    message: error.reason,
                    severity: error.code ? (error.code.startsWith('W') ? "warning" : "error") : "error",
                    from: CodeMirror.Pos(error.line - 1, start),
                    to: CodeMirror.Pos(error.line - 1, end)
                };

                output.push(hint);
            }
        }
    }
})();
