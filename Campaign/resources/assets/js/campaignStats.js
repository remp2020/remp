import Transformer from './components/_transformer.js';
import CampaignStats from './components/CampaignStats.vue';

remplib = typeof(remplib) === 'undefined' ? {} : remplib;

(function() {
    'use strict';

    remplib.campaignStats = {
        bind: (el, campaign) => {
            return new Vue({
                el: el,
                render: h => h(CampaignStats, {
                    props: Transformer.transformKeys(campaign)
                }),
            });
        }
    }
})();
