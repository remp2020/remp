import MailPreview from '../components/MailPreview.vue';

window.remplib = typeof(remplib) === 'undefined' ? {} : window.remplib;

window.test = "content";

(function() {

    'use strict';

    remplib.templateForm = {

        bind: (el, htmlContent, htmlLayout) => {
            return new Vue({
                el: el,
                data: function() {
                    return {
                        "htmlContent": htmlContent,
                        "htmlLayout": htmlLayout,
                    }
                },
                render: h => h(MailPreview),
            });
        },

    }

})();