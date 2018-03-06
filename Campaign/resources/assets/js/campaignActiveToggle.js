import Transformer from './components/_transformer.js';
import Toggle from 'remp/js/components/Toggle.vue';

remplib = typeof(remplib) === 'undefined' ? {} : remplib;

(function() {
    'use strict';

    remplib.campaignActiveToggle = {

        bind: (el, toggleProps) => {
            return new Vue({
                el: el,
                render: h => h(Toggle, {
                    props: Transformer.transformKeys(toggleProps)
                }),
            });
        }

    }

})();
