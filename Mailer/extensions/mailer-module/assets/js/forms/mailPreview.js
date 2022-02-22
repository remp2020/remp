import MailPreview from '../components/MailPreview.vue';
import icons from "trumbowyg/dist/ui/icons.svg";
import "trumbowyg/dist/ui/trumbowyg.css";
import "trumbowyg/dist/trumbowyg.js";
import "./trumbowyg-snippets-plugin.js";

$.trumbowyg.svgPath = icons;

window.remplib = typeof(remplib) === 'undefined' ? {} : window.remplib;

let beautify = require('js-beautify').html;

(function() {
    'use strict';
    remplib.templateForm = {
        textareaSelector: '.js-mail-body-html-input',
        codeMirror: (element) => {
            return CodeMirror( element, {
                value: beautify($(remplib.templateForm.textareaSelector).val()),
                theme: 'base16-dark',
                mode: 'htmlmixed',
                indentUnit: 4,
                indentWithTabs: true,
                lineNumbers: true,
                lineWrapping: false,
                styleActiveLine: true,
                styleSelectedText: true,
                continueComments: true,
                gutters:[
                    'CodeMirror-lint-markers'
                ],
                lint: true,
                autoRefresh: true,
                autoCloseBrackets: true,
                autoCloseTags: true,
                matchBrackets: true,
                matchTags: {
                    bothTags: true
                },
                htmlhint: {
                    'doctype-first': false,
                    'alt-require': false,
                    'space-tab-mixed-disabled': 'tab'
                }
            });
        },
        trumbowyg: (element) => {
            let buttons = $.trumbowyg.defaultOptions.btns;
            let plugins = {};
            const snippetsData = $(element).data('snippets');

            const viewHTMLButton = 'viewHTML';
            buttons = $.grep(buttons, function (value) {
                return value.toString() !== viewHTMLButton;
            });

            if (snippetsData) {
                buttons.push([['snippets']]);
                for (const item in snippetsData) {
                    // let html = `<div contentEditable="false">{{ include('${snippetsData[item].name}') }}</div>`;
                    let html = `{{ include('${snippetsData[item].code}') }}`;
                    snippetsData[item].html = html;
                }
                plugins.snippets = snippetsData;
            }

            return $(element).trumbowyg({
                semanticKeepAttributes: true,
                semantic: false,
                autogrow: true,
                btns: buttons,
                plugins: plugins,
            });
        },

        codeMirrorChanged: false,
        trumbowygChanged: false,

        editorChoice: () => {
            return $('.js-editor-choice:checked').val();
        },
        previewInit: (element, mailLayoutSelect, layoutsHtmlTemplates, initialContent) => {
            const getLayoutValue = () => mailLayoutSelect[mailLayoutSelect.selectedIndex].value;
            const getLayoutTemplate = () => layoutsHtmlTemplates[getLayoutValue()];
            const vue = new Vue({
                el: element,
                data: function() {
                    return {
                        "htmlContent": initialContent,
                        "htmlLayout": getLayoutTemplate().layout_html,
                    }
                },
                render: h => h(MailPreview),
            });
            mailLayoutSelect.addEventListener('change', function(e) {
                vue.htmlLayout = getLayoutTemplate().layout_html;
                $('body').trigger('preview:change');
            });
            return vue;
        },
        showTrumbowyg: (codeMirror, trumbowyg) => {
            trumbowyg.data('trumbowyg').$box.show();

            // load changed data from codemirror
            if (remplib.templateForm.codeMirrorChanged) {
                trumbowyg.trumbowyg('html', codeMirror.doc.getValue());
                remplib.templateForm.codeMirrorChanged = false;
            }
            $(codeMirror.display.wrapper).hide();
        },
        showCodemirror: (codeMirror, trumbowyg) => {
            trumbowyg.data('trumbowyg').$box.hide();

            // load changed and beautified data from trumbowyg
            if (remplib.templateForm.trumbowygChanged) {
                codeMirror.doc.setValue(beautify(trumbowyg.trumbowyg('html')));
                remplib.templateForm.trumbowygChanged = false;
            }

            setTimeout(function() {
                codeMirror.refresh();
            }, 0);
            $(codeMirror.display.wrapper).show();
        },
        selectEditor: (codeMirror, trumbowyg) => {
            if (remplib.templateForm.editorChoice() === 'editor')
                remplib.templateForm.showTrumbowyg(codeMirror, trumbowyg);
            else {
                remplib.templateForm.showCodemirror(codeMirror, trumbowyg);
            }
        },
        init: () => {
            // initialize preview right away so user can see the email
            const vue = remplib.templateForm.previewInit(
                '#js-mail-preview',
                $('[name="mail_layout_id"]')[0],
                $('.js-mail-layouts-templates').data('mail-layouts'),
                $('.js-mail-body-html-input').val(),
            );

            const codeMirror = remplib.templateForm.codeMirror($('.js-codemirror')[0]);
            const trumbowyg = remplib.templateForm.trumbowyg('.js-html-editor');

            remplib.templateForm.syncCodeMirrorWithPreview(vue, codeMirror);
            remplib.templateForm.syncTrumbowygWithPreview(vue, trumbowyg);

            // initialize code editors on tab change, prevents bugs with initialisation of invisible elements.
            $('a[data-toggle="tab"]').one('shown.bs.tab', function (e) {
                const target = $(e.target).attr("href") // activated tab
                if (target === '#email') {
                    remplib.templateForm.selectEditor(codeMirror, trumbowyg);
                }
            });

            // change editor when user wants to change it (radio buttons)
            $('.js-editor-choice').on('change', function(e) {
                e.stopPropagation();
                remplib.templateForm.selectEditor(codeMirror, trumbowyg)
            });
        },
        syncTrumbowygWithPreview: (vue, trumbowyg) => {
            trumbowyg.on('tbwchange', () => {
                if (remplib.templateForm.editorChoice() !== 'editor') {
                    return;
                }
                vue.htmlContent = trumbowyg.trumbowyg('html');
                $('body').trigger('preview:change');
                remplib.templateForm.trumbowygChanged = true;
            });
        },
        syncCodeMirrorWithPreview: (vue, codeMirror) => {
            codeMirror.on('change', function( editor, change ) {
                if (remplib.templateForm.editorChoice() !== 'code') {
                    return;
                }
                // ignore if update is made programmatically and not by user (avoid circular loop)
                if ( change.origin === 'setValue' ) {
                    return;
                }
                vue.htmlContent = editor.doc.getValue();
                $(remplib.templateForm.textareaSelector).val(editor.doc.getValue());
                $('body').trigger('preview:change');
                remplib.templateForm.codeMirrorChanged = true;
            });
        }
    }

})();
