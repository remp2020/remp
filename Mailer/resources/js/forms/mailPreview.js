import MailPreview from '../components/MailPreview.vue';
import pell from 'pell'
import beautify from 'js-beautify'

window.remplib = typeof(remplib) === 'undefined' ? {} : window.remplib;

(function() {
    'use strict';
    remplib.templateForm = {
        events: {
            htmlEditorChange: 'remp.html-editor.change',
        },
        codeMirror: (element) => {
            return CodeMirror.fromTextArea( element, {
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
            return pell.init({
                element: element,
                defaultParagraphSeparator: 'p',
                actions: [
                    {
                        name: 'undo',
                        icon: '&larr;',
                        title: 'Undo',
                        result: function result() {
                            return pell.exec('undo');
                        }
                    },
                    {
                        name: 'redo',
                        icon: '&rarr;',
                        title: 'Redo',
                        result: function result() {
                            return pell.exec('redo');
                        }
                    },
                    'link',
                    'bold',
                    'italic',
                    'image',
                    'paragraph',
                    'olist',
                    'ulist'
                ],
                onChange: function( html ) {
                    $('.js-codemirror').textContent = html;
                    // create and dispach custom event for easier binding with other parts of app
                    const event = new CustomEvent(remplib.templateForm.events.htmlEditorChange, {detail: {content: html}});
                    this.element.dispatchEvent(event);
                }
            });
        },
        syncEditorWithCodeMirror: (htmlEditor, codeMirror) => {
            htmlEditor.addEventListener(remplib.templateForm.events.htmlEditorChange, function (e) {
                codeMirror.doc.setValue(beautify.html(e.detail.content, {
                    indent_size: 1,
                    indent_char: '\t',
                    indent_with_tabs: true,
                    brace_style: 'collapse-preserve-inline'
                }));
            }, false);
        },
        syncCodeMirrorWithEditor: (codeMirror, htmlEditor) => {
            // codemirror automatically fires change event - @see https://codemirror.net/doc/manual.html#events
            codeMirror.on('change', function( editor, change ) {
                // ignore if update is made programmatically and not by user (avoid circular loop)
                if ( change.origin === 'setValue' ) {
                    return;
                }
                htmlEditor.content.innerHTML = editor.doc.getValue();
            });
        },
        syncEditorWithPreview: (element, htmlEditor, mailLayoutSelect, layoutsHtmlTemplates) => {
            const getLayoutValue = () => mailLayoutSelect[mailLayoutSelect.selectedIndex].value;
            const getLayoutTemplate = () => layoutsHtmlTemplates[getLayoutValue()];
            const vue = new Vue({
                el: element,
                data: function() {
                    return {
                        "htmlContent": htmlEditor.content.innerHTML,
                        "htmlLayout": getLayoutTemplate(),
                    }
                },
                render: h => h(MailPreview),
            });
            htmlEditor.addEventListener(remplib.templateForm.events.htmlEditorChange, function (e) {
                vue.htmlContent = e.detail.content;
                $('body').trigger('preview:change');
            }, false);
            mailLayoutSelect.addEventListener('change', function(e) {
                vue.htmlLayout = getLayoutTemplate()
            });

            return vue;
        },
        init: () => {
            // initialise editor
            const htmlEditor = remplib.templateForm.htmlEditor(document.getElementById('js-html-editor'));
            htmlEditor.content.innerHTML = $('[name="mail_body_html"]').val(); // inital content;
            // initialise codeMirror
            const codeMirror = remplib.templateForm.codeMirror($('.js-codemirror')[0]);
            remplib.templateForm.syncEditorWithCodeMirror(htmlEditor, codeMirror);
            remplib.templateForm.syncCodeMirrorWithEditor(codeMirror, htmlEditor);

            remplib.templateForm.syncEditorWithPreview(
                '#js-mail-preview',
                htmlEditor,
                $('[name="mail_layout_id"]')[0],
                $('.js-mail-layouts-templates').data('mail-layouts'),
            );
            // simple show / hide prototype
            $('.js-toggle-codemirror').on('click', () => {
                $('.CodeMirror').toggle(0);
            })
        }
    }

})();