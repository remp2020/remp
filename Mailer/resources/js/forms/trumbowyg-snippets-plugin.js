(function ($) {
    'use strict';

    // Adds the language variables
    $.extend(true, $.trumbowyg, {
        langs: {
            // jshint camelcase:false
            en: {
                snippets: 'Snippets'
            },
            // jshint camelcase:true
        }
    });

    // Adds the extra button definition
    $.extend(true, $.trumbowyg, {
        plugins: {
            snippets: {
                shouldInit: function (trumbowyg) {
                    return trumbowyg.o.plugins.hasOwnProperty('snippets');
                },
                init: function (trumbowyg) {
                    trumbowyg.addBtnDef('snippets', {
                        dropdown: snippetsSelector(trumbowyg),
                        hasIcon: false,
                        text: trumbowyg.lang.snippets
                    });
                }
            }
        }
    });

    // Creates the snippets-selector dropdown.
    function snippetsSelector(trumbowyg) {
        var available = trumbowyg.o.plugins.snippets;
        var snippets = [];

        $.each(available, function (index, snippet) {
            trumbowyg.addBtnDef('snippet_' + index, {
                fn: function () {
                    trumbowyg.execCmd('insertHTML', snippet.html);
                },
                hasIcon: false,
                title: `${snippet.code}`
            });
            snippets.push('snippet_' + index);
        });

        return snippets;
    }
})(jQuery);
