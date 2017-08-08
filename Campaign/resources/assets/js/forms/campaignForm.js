import Transformer from '../components/_transformer.js';
import CampaignForm from '../components/CampaignForm.vue';

remplib = typeof(remplib) === 'undefined' ? {} : remplib;

(function() {

    'use strict';

    remplib.campaignForm = {

        bind: (el, campaign) => {
            return new Vue({
                el: el,
                render: h => h(CampaignForm, {
                    props: Transformer.transformKeys(campaign)
                }),
            });
        }

    }

})();