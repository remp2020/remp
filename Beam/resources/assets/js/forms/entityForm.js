import Transformer from '../components/_transformer.js';
import EntityForm from '../components/entity/EntityForm.vue';

window.remplib = typeof(remplib) === 'undefined' ? {} : window.remplib;

(function() {

    'use strict';

    remplib.entityForm = {

        bind: (el, entity) => {
            return new Vue({
                el: el,
                render: h => h(EntityForm, {
                    props: Transformer.transformKeys(entity)
                }),
            });
        }

    }

})();