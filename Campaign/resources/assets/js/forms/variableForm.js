import Transformer from '../components/_transformer.js';
import VariableForm from '../components/VariableForm.vue';

remplib = typeof(remplib) === 'undefined' ? {} : remplib;

(function() {

    'use strict';

    remplib.variableForm = {

        bind: (el, variable) => {
            return new Vue({
                el: el,
                render: h => h(VariableForm, {
                    props: Transformer.transformKeys(variable)
                }),
            });
        }

    }

})();
