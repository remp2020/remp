import MailPreview from '../components/MailPreview.vue';
import icons from "trumbowyg/dist/ui/icons.svg";
import "trumbowyg/dist/ui/trumbowyg.css";
import "trumbowyg/dist/trumbowyg.js";
import "./trumbowyg-snippets-plugin.js";
import 'codemirror/mode/twig/twig.js';
import 'codemirror/addon/mode/overlay.js';

$.trumbowyg.svgPath = icons;

window.remplib = typeof(remplib) === 'undefined' ? {} : window.remplib;

let beautify = require('js-beautify').html;

CodeMirror.defineMode("htmltwig", function(config, parserConfig) {
    return CodeMirror.overlayMode(CodeMirror.getMode(config, parserConfig.backdrop || "text/html"), CodeMirror.getMode(config, "twig"));
});

(function() {
    'use strict';
    remplib.templateForm = {
        textareaSelector: '.js-mail-body-html-input',
        codeMirror: (element) => {
            return CodeMirror( element, {
                value: beautify($(remplib.templateForm.textareaSelector).val()),
                mode: 'htmltwig',
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
                },
            });
        },
        trumbowyg: (element) => {
            let el = $(element);

            // trumbowyg removes head/body tags, so we need to edit only body content in WYSIWYG
            const mailBody = remplib.templateForm.parseBodyFromMail(el.val());
            el.val(remplib.templateForm.screwTwig(mailBody));

            let buttons = $.trumbowyg.defaultOptions.btns;
            let plugins = {};
            const snippetsData = $(element).data('snippets');

            const buttonsToRemove = ['fullscreen', 'viewHTML'];
            buttons = $.grep(buttons, function (value) {
                return !buttonsToRemove.includes(value.toString());
            });

            if (snippetsData) {
                buttons.push([['snippets']]);
                for (const item in snippetsData) {
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
                        "textLayout": getLayoutTemplate().layout_text,
                    }
                },
                render: h => h(MailPreview),
            });
            mailLayoutSelect.addEventListener('change', function(e) {
                vue.htmlLayout = getLayoutTemplate().layout_html;
                vue.textLayout = getLayoutTemplate().layout_text;
                $('body').trigger('preview:change');
            });

            return vue;
        },
        showTrumbowyg: (codeMirror, trumbowyg) => {
            trumbowyg.data('trumbowyg').$box.show();

            // load changed data from codemirror
            if (remplib.templateForm.codeMirrorChanged) {
                const mailBody = remplib.templateForm.parseBodyFromMail(codeMirror.doc.getValue());
                trumbowyg.trumbowyg('html', remplib.templateForm.screwTwig(mailBody));
                remplib.templateForm.codeMirrorChanged = false;
            }
            $(codeMirror.display.wrapper).closest('.js-codemirror').hide();
        },
        showCodemirror: (codeMirror, trumbowyg) => {
            trumbowyg.data('trumbowyg').$box.hide();

            // load changed and beautified data from trumbowyg
            if (remplib.templateForm.trumbowygChanged) {
                let twCode = remplib.templateForm.buildMailFromTrumbowyg(trumbowyg.trumbowyg('html'));

                codeMirror.doc.setValue(beautify(twCode));
                remplib.templateForm.trumbowygChanged = false;
            }

            setTimeout(function() {
                codeMirror.refresh();
            }, 0);
            $(codeMirror.display.wrapper).closest('.js-codemirror').show();
        },
        selectEditor: (codeMirror, trumbowyg) => {
            if (remplib.templateForm.editorChoice() === 'editor')
                remplib.templateForm.showTrumbowyg(codeMirror, trumbowyg);
            else {
                remplib.templateForm.showCodemirror(codeMirror, trumbowyg);
            }

            remplib.templateForm.updateFullscreenGUIHeightVariables();
        },
        init: () => {
            // initialize preview right away so user can see the email
            const vue = remplib.templateForm.previewInit(
                '#js-mail-preview',
                $('[name="mail_layout_id"]')[0],
                $('.js-mail-layouts-templates').data('mail-layouts'),
                $('.js-mail-body-html-input').val(),
            );

            remplib.templateForm.displayedMailContent = $(remplib.templateForm.textareaSelector).val();

            const codeMirror = remplib.templateForm.codeMirror($('.js-codemirror')[0]);
            const trumbowyg = remplib.templateForm.trumbowyg('.js-html-editor');
            const textarea = $('.textarea-editor')

            remplib.templateForm.fullscreenEditToggle();
            remplib.templateForm.syncCodeMirrorWithPreview(vue, codeMirror);
            remplib.templateForm.syncTrumbowygWithPreview(vue, trumbowyg);
            remplib.templateForm.syncTextareaWithPreview(vue, textarea);

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
            const syncWithPreview = () => {
                vue.htmlContent = remplib.templateForm.buildMailFromTrumbowyg(trumbowyg.trumbowyg('html'));
                $('body').trigger('preview:change');
                remplib.templateForm.trumbowygChanged = true;
            }

            trumbowyg.on('tbwchange tbwfocus', () => {
                if (remplib.templateForm.editorChoice() !== 'editor') {
                    return;
                }
                syncWithPreview();
            });

            $('.fullscreen-edit-btn[data-sync="html"]').on('click', () => {
                if (remplib.templateForm.editorChoice() === 'editor') {
                    syncWithPreview();
                }
            })
        },
        syncCodeMirrorWithPreview: (vue, codeMirror) => {
            const syncWithPreview = () => {
                vue.htmlContent = codeMirror.doc.getValue();
                remplib.templateForm.displayedMailContent = codeMirror.doc.getValue();
                $('body').trigger('preview:change');
                remplib.templateForm.codeMirrorChanged = true;
            }

            codeMirror.on('change', function( editor, change ) {
                if (remplib.templateForm.editorChoice() !== 'code') {
                    return;
                }
                // ignore if update is made programmatically and not by user (avoid circular loop)
                if ( change.origin === 'setValue' ) {
                    return;
                }

                syncWithPreview();
            });

            $('.fullscreen-edit-btn[data-sync="html"]').on('click', () => {
                if (remplib.templateForm.editorChoice() === 'code') {
                    syncWithPreview();
                }
            })
            $(codeMirror.display.wrapper).on('click', syncWithPreview)
        },
        syncTextareaWithPreview: (vue, textarea) => {
            const syncWithPreview = () => {
                vue.htmlContent = textarea.val();
                $('body').trigger('preview:change', { textarea: true });
            }

            $('.fullscreen-edit-btn[data-sync="text"]').on('click', () => {
                syncWithPreview();
                autosize.update(textarea);
            });
            textarea.on('input click', syncWithPreview);
        },
        fullscreenEditToggle: () => {
            $('.fullscreen-edit-btn').click(function () {
                $('body').toggleClass('fullscreen-edit');
                $(this).closest('.email-editors').toggleClass('full-screen');
                $(this).children('span').text(
                    $(this).children('span').text() == 'Fullscreen edit' ? 'Exit fullscreen' : 'Fullscreen edit'
                );

                remplib.templateForm.updateFullscreenGUIHeightVariables();
            });
        },
        updateFullscreenGUIHeightVariables: () => {
            if ($('.fullscreen-edit').length === 0) {
                return;
            }

            //calculating height for SCSS vars (see app.scss) so the editors are correctly stretched to 100% of viewport height
            $(':root').css({
                '--editor-choice-height': $('.fullscreen-edit .editors-container').outerHeight(true) + 'px',
            });

            if (remplib.templateForm.editorChoice() === 'editor') {
                $(':root').css({
                    '--trumbowyg-header-height': $('.fullscreen-edit .trumbowyg-button-pane').outerHeight(true) + 'px',
                });
            }
        },

        // TWIG vars are not compatible with WYSIWYG editor Trumbowyg,
        // they are stripped away and put back when switching to/from Trumbowyg.
        twigVars: {},
        unscrewTwig: (text) => {
            for (const varName in remplib.templateForm.twigVars) {
                // varName cannot contain character with regex meaning - it doesn't, only "TWGVAR__<NUMBER>__TWGVAR"
                text = text.replace(new RegExp(varName, "ig"), remplib.templateForm.twigVars[varName]);
            }

            // do not reset twig vars. unscrew might be called multiple times
            return text;
        },
        screwTwig: (text) => {
            // First, replace all Twig variables with text replacements containing:
            // "TGWVAR__<NUMBER>__TWGVAR"
            // to protect them against WYSIWYG editor parser

            const startTag = "TWGVAR__";
            const endTag = "__TWGVAR";

            let newText = text;
            let it = 0;
            let variableNum = 0;

            let vars = {};
            while (it < newText.length) {
                let start = newText.indexOf("{{", it);
                if (start === -1) {
                    break;
                }
                let end = newText.indexOf("}}", start);
                if (end === -1) {
                    break;
                }
                end += 2; // end of parentheses
                let varName = startTag + variableNum++ + endTag;
                vars[varName] = newText.slice(start, end);

                newText = newText.slice(0, start) + varName + newText.slice(end);
                it = newText.indexOf(varName) + varName.length + 1; // find new start

            }
            remplib.templateForm.twigVars =vars;


            // Second, revert replacements to Twig variables in text nodes,
            // so user can edit them even in WYSIWYG editor.

            // Very simple state automaton, detecting tag/non-tag context and replacing vars back.
            let tagContext = false;
            let textQueue = [];
            let varStartIndex = null;

            for (const c of newText) {
                if (c === '<') {
                    tagContext = true;
                }
                if (c === '>') {
                    tagContext = false;
                }
                textQueue.push(c);

                // if we are in an HTML tag, do not replace variables
                if (tagContext) {
                    continue;
                }

                // otherwise, replace them back
                if (varStartIndex === null) {
                    if (textQueue.slice(-startTag.length).join('') === startTag) {
                        varStartIndex = textQueue.length - startTag.length;
                    }
                } else {
                    if (textQueue.slice(-endTag.length).join('') === endTag) {
                        let varName = textQueue.splice(varStartIndex).join('');
                        textQueue.push(...(remplib.templateForm.twigVars[varName].split('')));
                        varStartIndex = null;
                    }
                }
            }
            return textQueue.join('');
        },
        displayedMailContent: null,
        // returns content inside body tag or whole mail if no body tag
        parseBodyFromMail: (mailContent) => {
            const matches = [...mailContent.matchAll(/<body>(.*)<\/body>/gsm)]
            return matches?.[0]?.[1] ?? mailContent;
        },
        buildMailFromTrumbowyg: (mailContent) => {
            mailContent = remplib.templateForm.unscrewTwig(mailContent);
            if (/<body>.*<\/body>/gsm.test(remplib.templateForm.displayedMailContent)) {
                remplib.templateForm.displayedMailContent = remplib.templateForm.displayedMailContent.replace(/<body>.*<\/body>/gsm, '<body>' + mailContent + '</body>');
            } else {
                remplib.templateForm.displayedMailContent = mailContent;
            }

            return remplib.templateForm.displayedMailContent;
        }
    }
})();
