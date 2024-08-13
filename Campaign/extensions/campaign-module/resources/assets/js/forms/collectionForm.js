import Transformer from '../components/_transformer.js';
import CollectionForm from '../components/CollectionForm.vue';

remplib = typeof(remplib) === 'undefined' ? {} : remplib;

(function() {
    'use strict';

    remplib.collectionForm = {
        bind: (el, collection) => {
            return new Vue({
                el: el,
                render: h => h(CollectionForm, {
                    props: Transformer.transformKeys(collection)
                }),
            });
        }
    }
})();