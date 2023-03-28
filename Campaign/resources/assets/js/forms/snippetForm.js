import Transformer from '../components/_transformer.js';
import SnippetForm from '../components/SnippetForm.vue';

remplib = typeof(remplib) === 'undefined' ? {} : remplib;

(function() {

    'use strict';

    remplib.snippetForm = {

        bind: (el, snippet) => {
            return new Vue({
                el: el,
                render: h => h(SnippetForm, {
                    props: Transformer.transformKeys(snippet)
                }),
            });
        }

    }

})();
