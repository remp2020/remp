import MailPreview from '../components/MailPreview.vue';
import icons from "trumbowyg/dist/ui/icons.svg";
import "trumbowyg/dist/ui/trumbowyg.css"
import "trumbowyg/dist/trumbowyg.js";

$.trumbowyg.svgPath = icons;

window.remplib = typeof(remplib) === 'undefined' ? {} : window.remplib;

(function() {
    'use strict';
    remplib.templateForm = {
        textareaSelector: '.js-mail-body-html-input',
        codeMirror: (element) => {
            return CodeMirror( element, {
                value: $(remplib.templateForm.textareaSelector).val(),
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
        htmlEditor: (element) => {
            return $(element).trumbowyg({
                semanticKeepAttributes: true,
                semantic: false,
                autogrow: true,
            });
        },
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
                        "htmlLayout": getLayoutTemplate(),
                    }
                },
                render: h => h(MailPreview),
            });
            mailLayoutSelect.addEventListener('change', function(e) {
                vue.htmlLayout = getLayoutTemplate();
                $('body').trigger('preview:change');
            });
            return vue;
        },
        showHtmlEditor: (codeMirror, htmlEditor) => {
            htmlEditor.data('trumbowyg').$box.show();
            $(codeMirror.display.wrapper).hide();
        },
        showCodemirror: (codeMirror, htmlEditor) => {
            htmlEditor.data('trumbowyg').$box.hide();
            $(codeMirror.display.wrapper).show();
        },
        showCorrectEditor: (codeMirror, htmlEditor) => {
            function chooseEditor(codeMirror, htmlEditor) {
                if (remplib.templateForm.editorChoice() === 'editor')
                    remplib.templateForm.showHtmlEditor(codeMirror, htmlEditor);
                else {
                    remplib.templateForm.showCodemirror(codeMirror, htmlEditor);
                }
            }
            chooseEditor(codeMirror, htmlEditor);

            $('.js-editor-choice').on('change', function(e) {
                e.stopPropagation();
                chooseEditor(codeMirror, htmlEditor)
            });
        },
        syncEditorWithPreview: (vue, htmlEditor) => {
            htmlEditor.on('tbwchange', () => {
                if (remplib.templateForm.editorChoice() !== 'editor') {
                    return;
                }
                vue.htmlContent = htmlEditor.trumbowyg('html');
                $('body').trigger('preview:change');
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
            });
        },
        init: () => {
            // initialize on tab change, prevents bugs with initialisation of invisible elements.
            $('a[data-toggle="tab"]').one('shown.bs.tab', function (e) {
                const target = $(e.target).attr("href") // activated tab
                if (target === '#email') {
                    const codeMirror = remplib.templateForm.codeMirror($('.js-codemirror')[0]);
                    const htmlEditor = remplib.templateForm.htmlEditor('.js-html-editor');
                    const vue = remplib.templateForm.previewInit(
                        '#js-mail-preview',
                        $('[name="mail_layout_id"]')[0],
                        $('.js-mail-layouts-templates').data('mail-layouts'),
                        $('.js-mail-body-html-input').val(),
                    );
                    remplib.templateForm.syncCodeMirrorWithPreview(vue, codeMirror);
                    remplib.templateForm.syncEditorWithPreview(vue, htmlEditor);
                    remplib.templateForm.showCorrectEditor(codeMirror, htmlEditor);
                }
            });
         }
    }

})();