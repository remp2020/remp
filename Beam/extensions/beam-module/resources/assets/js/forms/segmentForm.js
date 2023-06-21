import Transformer from '../components/_transformer.js';
import SegmentForm from '../components/SegmentForm.vue';

window.remplib = typeof(remplib) === 'undefined' ? {} : window.remplib;

(function() {

    'use strict';

    remplib.segmentForm = {

        bind: (el, segment) => {
            return new Vue({
                el: el,
                render: h => h(SegmentForm, {
                    props: Transformer.transformKeys(segment)
                }),
            });
        }

    }

})();